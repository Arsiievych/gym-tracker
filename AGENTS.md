# GymTracker

GymTracker has a Laravel API in [`api/`](api/) and a separate React and TypeScript frontend in [`web/`](web/). The frontend communicates with Laravel over HTTP. Run PHP, Composer, and Artisan in the Laravel Docker environment. See the [root README](README.md) for development commands.

## Laravel API architecture

Organize backend code by business module under `api/app/Modules/<Module>`, with namespaces under `App\Modules\<Module>`. Use these paths and naming conventions. Each PHP filename matches its class name.

| Path within a module | Naming convention | Example | Allowed dependencies on other application layers |
| --- | --- | --- | --- |
| `Http/Requests` | End with `Request` | `StoreWorkoutRequest` | `Data` |
| `Http/Controllers` | End with `Controller` | `WorkoutController` | `Http/Requests`, `Actions`, `Http/Resources` |
| `Http/Resources` | End with `Resource` | `WorkoutResource` | `Data` |
| `Actions` | Describe the operation and end with `Action` | `StoreWorkoutAction` | `Repositories`, `Data` |
| `Repositories` | End with `Repository` | `WorkoutRepository` | `Models`, `Data` |
| `Models` | Use the singular entity name | `Workout` | None |
| `Data` | End with `Data` | `StoreWorkoutData` | None |

- A `FormRequest` validates input and creates an input DTO. Keep controllers thin. They call an action with the validated data and return a `JsonResource`.
- Actions handle use cases and work with repositories. Repositories query and persist Eloquent models, then return DTOs or scalar values. Resources turn DTOs into JSON responses.
- Use `Actions/Api` for public actions and interfaces and `Data/Api` for their DTOs. Name a public interface after its operation, such as `GetUserSummary`. Its implementation uses the `Action` suffix, as in `GetUserSummaryAction`.
- Declare cross-module dependencies in `allowedDependencies` in [`api/deptrac.modules.php`](api/deptrac.modules.php). A module with an explicit dependency can use the target module's public API. The [layer rules](api/deptrac.layers.yaml) still apply to cross-module dependencies.

## Checks

Run `make backend-check` for Pint, Larastan, and Deptrac. Run `make frontend-check` for TypeScript, Oxlint, and Prettier. `make pre-commit` runs the checks, tests, and Composer audit.
