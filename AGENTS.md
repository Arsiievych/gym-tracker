# GymTracker

## Project goal

- Help athletes find a trainer whose specialization matches their goals and whose availability fits their schedule. Make it easy to book a training session and see when their next sessions are scheduled.
- Help trainers manage the athletes they coach and maintain each athlete's training program.

## Repository structure

GymTracker has a Laravel API in [`api/`](api/) and a separate React and TypeScript frontend in [`web/`](web/). The frontend communicates with Laravel over HTTP. Run PHP, Composer, and Artisan in the Laravel Docker environment. See the [root README](README.md) for development commands.

## Development rules

- Before changing an API endpoint, check its route, backend tests, and frontend callers. When its contract changes, update the affected callers and tests in the same change.
- Keep business rules in Laravel. For example, the UI can display available training times, but the booking action must verify availability when it accepts a booking.
- Keep local credentials in `api/.env`. When adding a required setting, document it in [`api/.env.example`](api/.env.example) with a local example value.
- Keep each change focused on the requested behavior. Preserve unrelated working-tree changes and commit or push only when explicitly requested.

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

## Frontend architecture

- Put React application code in [`web/src/`](web/src/). Use `main.tsx` as the entry point and `App.tsx` as the root component.
- Call Laravel using relative `/api/...` URLs. The [Vite proxy](web/vite.config.ts) supplies the backend address during development. Handle loading, success, and failure where the user sees the result.

## Naming conventions

- Use English domain terms consistently. Use `athlete` and `trainer` for the product roles. Name operations with a verb and an object, such as `BookTrainingSession`.
- Use PascalCase for PHP classes and interfaces, and camelCase for methods and variables. Apply the layer-specific suffixes in the architecture table above.
- Use PascalCase for React components and TypeScript types. Match a component file to its component, for example `TrainerCard.tsx`. Use camelCase for functions and variables, and start custom hook names with `use`, as in `useTrainerAvailability`.

## Commit messages

Write the subject in English using `<type>: <summary>`. Start the summary with an imperative verb such as `add`, `fix`, or `document`, and omit the trailing period. Keep each commit focused enough that its subject describes one change.

| Type | Use for |
| --- | --- |
| `feat` | New user-facing behavior |
| `fix` | A correction to existing behavior |
| `docs` | Documentation changes |
| `test` | Test coverage changes |
| `refactor` | Code restructuring that preserves behavior |
| `chore` | Tooling, dependency, or maintenance work |

Examples from the repository history include `feat: connect React frontend to Laravel API` and `docs: add step-by-step local setup instructions to README`.

## Checks

Run `make backend-check` for Pint, Larastan, and Deptrac. Run `make frontend-check` for TypeScript, Oxlint, and Prettier. `make pre-commit` runs the checks, tests, and Composer audit.
