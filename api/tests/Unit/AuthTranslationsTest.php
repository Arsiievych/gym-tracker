<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AuthTranslationsTest extends TestCase
{
    /** @param array<string, string> $messages */
    #[DataProvider('locales')]
    public function test_custom_authentication_messages_are_translated(string $locale, array $messages): void
    {
        $loader = new FileLoader(new Filesystem, dirname(__DIR__, 2).'/lang');
        $translator = new Translator($loader, $locale);

        foreach ($messages as $key => $message) {
            self::assertSame($message, $translator->get('auth.'.$key));
        }
    }

    /** @return array<string, array{string, array<string, string>}> */
    public static function locales(): array
    {
        return [
            'English' => [
                'en',
                [
                    'failed' => 'These credentials do not match our records.',
                    'already_authenticated' => 'An account is already signed in.',
                    'registration_login_failed' => 'Unable to authenticate the newly registered account.',
                    'email_taken' => 'The email has already been taken.',
                ],
            ],
            'Ukrainian' => [
                'uk',
                [
                    'failed' => 'Неправильна електронна адреса або пароль.',
                    'already_authenticated' => 'Ви вже увійшли в обліковий запис.',
                    'registration_login_failed' => 'Не вдалося увійти до щойно створеного облікового запису.',
                    'email_taken' => 'Ця електронна адреса вже використовується.',
                ],
            ],
        ];
    }
}
