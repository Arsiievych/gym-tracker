<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SessionAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_rotates_the_session_and_logout_invalidates_it(): void
    {
        config(['sanctum.stateful' => ['spa.test']]);
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => ' Password with spaces ',
        ]);
        $credentials = ['email' => 'Member@EXAMPLE.com', 'password' => ' Password with spaces '];

        $guest = $this->browserRequest('GET', '/sanctum/csrf-cookie')->assertNoContent();
        $login = $this->browserRequest('POST', '/api/auth/login', $guest, $credentials)->assertNoContent();

        self::assertNotSame($this->sessionCookie($guest)->getValue(), $this->sessionCookie($login)->getValue());
        $this->browserRequest('GET', '/api/auth/me', $login)->assertOk()->assertJsonPath('id', $user->id);
        $this->browserRequest('GET', '/api/auth/me', $guest)->assertUnauthorized();

        $logout = $this->browserRequest('POST', '/api/auth/logout', $login)->assertNoContent();
        self::assertNotSame($this->sessionCookie($login)->getValue(), $this->sessionCookie($logout)->getValue());
        $this->browserRequest('GET', '/api/auth/me', $logout)->assertUnauthorized();
        $this->browserRequest('GET', '/api/auth/me', $login)->assertUnauthorized();
        $this->browserRequest('POST', '/api/auth/login', $logout, $credentials)->assertNoContent();
    }

    public function test_login_rejects_an_incorrect_password(): void
    {
        User::factory()->create(['email' => 'member@example.com', 'password' => 'correct password']);

        $this->postJson('/api/auth/login', ['email' => 'member@example.com', 'password' => 'wrong password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest('web');
    }

    public function test_login_rejects_credentials_supplied_only_in_the_query_string(): void
    {
        User::factory()->create(['email' => 'member@example.com', 'password' => 'correct password']);

        $this->postJson('/api/auth/login?email=member%40example.com&password=correct%20password')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);

        $this->assertGuest('web');
    }

    public function test_login_limits_attempts_per_ip(): void
    {
        $credentials = ['email' => 'member@example.com', 'password' => 'wrong password'];
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/login', $credentials)->assertUnprocessable();
        }

        $this->postJson('/api/auth/login', $credentials)->assertTooManyRequests();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.11']);
        $this->postJson('/api/auth/login', $credentials)->assertUnprocessable();
    }

    #[DataProvider('sessionEndpoints')]
    public function test_session_endpoints_wait_for_the_same_session_lock(string $method, string $uri): void
    {
        config([
            'sanctum.stateful' => ['spa.test'],
            'session.block_store' => 'array',
            'session.block_wait_seconds' => 0,
        ]);
        $guest = $this->browserRequest('GET', '/sanctum/csrf-cookie')->assertNoContent();
        $sessionId = $this->sessionCookie($guest)->getValue();
        $lock = Cache::lock('session:'.$sessionId, 10);
        self::assertTrue($lock->get());
        $this->withoutExceptionHandling();
        $this->expectException(LockTimeoutException::class);

        try {
            $this->browserRequest($method, $uri, $guest);
        } finally {
            $lock->release();
        }
    }

    /** @return array<string, array{string, string}> */
    public static function sessionEndpoints(): array
    {
        return [
            'current account' => ['GET', '/api/auth/me'],
            'logout' => ['POST', '/api/auth/logout'],
            'login' => ['POST', '/api/auth/login'],
            'CSRF cookie' => ['GET', '/sanctum/csrf-cookie'],
        ];
    }

    /**
     * @param  TestResponse<Response>|null  $sessionResponse
     * @param  array<string, string>|null  $payload
     * @return TestResponse<Response>
     */
    private function browserRequest(string $method, string $uri, ?TestResponse $sessionResponse = null, ?array $payload = null): TestResponse
    {
        // Reset in-memory state between requests while preserving the stored sessions.
        Auth::forgetGuards();
        Auth::shouldUse('web');
        Session::flush();

        $cookies = [];

        if ($sessionResponse !== null) {
            $cookie = $this->sessionCookie($sessionResponse, decrypt: false);
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_ORIGIN' => 'http://spa.test',
        ];

        if ($payload !== null) {
            $server['CONTENT_TYPE'] = 'application/json; charset=UTF-8';
        }

        return $this->call(
            $method,
            $uri,
            cookies: $cookies,
            server: $server,
            content: $payload === null ? null : json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }

    /** @param TestResponse<Response> $response */
    private function sessionCookie(TestResponse $response, bool $decrypt = true): Cookie
    {
        $cookie = $response->getCookie(config()->string('session.cookie'), $decrypt);
        self::assertNotNull($cookie);

        return $cookie;
    }
}
