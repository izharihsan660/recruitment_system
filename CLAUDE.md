# CLAUDE.md — Sistem Rekrutmen & Talent Management

## Stack

| Layer | Package |
|---|---|
| Framework | Laravel 11 |
| Frontend bridge | Inertia.js v2 |
| UI | React 18 + TypeScript |
| Styling | Tailwind CSS v3 + shadcn/ui |
| State management | Zustand |
| Data fetching | TanStack Query v5 |
| Form & validation | React Hook Form + Zod |
| Date | date-fns |
| Auth scaffolding | Laravel Breeze (Inertia + React) |

---

## Folder Structure

```
project-root/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # Satu controller per modul
│   │   ├── Middleware/
│   │   └── Requests/           # Form Request untuk validasi
│   ├── Models/                 # Eloquent models
│   ├── Services/               # Business logic, satu service per modul
│   ├── Actions/                # Single-purpose action classes
│   ├── Enums/                  # Status enums (PHP 8.1 backed enums)
│   └── Notifications/          # Laravel notifications
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── resources/
│   └── js/
│       ├── Components/         # Reusable UI components
│       │   ├── ui/             # shadcn/ui components
│       │   └── shared/         # Custom shared components
│       ├── Features/           # Per-modul: components + hooks + types
│       │   ├── FPK/
│       │   ├── JobPosting/
│       │   ├── Candidate/
│       │   ├── Pipeline/
│       │   ├── Interview/
│       │   ├── Offering/
│       │   ├── PKWT/
│       │   ├── Preboarding/
│       │   ├── Probation/
│       │   └── TalentPool/
│       ├── Layouts/            # App layout, auth layout
│       ├── Pages/              # Inertia pages (per route)
│       ├── hooks/              # Global custom hooks
│       ├── stores/             # Zustand stores
│       ├── lib/                # Utilities, helpers
│       ├── types/              # Global TypeScript types
│       └── app.tsx
├── routes/
│   ├── web.php
│   └── auth.php
└── CLAUDE.md
```

---

## Naming Conventions

### PHP / Laravel

| Context | Convention | Contoh |
|---|---|---|
| Model | PascalCase singular | `Candidate`, `JobPosting`, `RecruitmentRequest` |
| Controller | PascalCase + Controller | `CandidateController`, `JobPostingController` |
| Service | PascalCase + Service | `CandidateService`, `OfferingService` |
| Action | PascalCase + verb | `CreateCandidate`, `MoveToTalentPool` |
| Form Request | PascalCase + Request | `StoreRecruitmentRequestRequest` |
| Migration | snake_case + timestamp | `create_candidates_table` |
| Enum | PascalCase | `CandidateStatus`, `ApprovalStatus` |
| Enum case | PascalCase | `CandidateStatus::Applied` |
| Route name | snake_case dengan titik | `candidates.index`, `job-postings.store` |
| Relationship | camelCase | `$candidate->jobPosting()` |

### TypeScript / React

| Context | Convention | Contoh |
|---|---|---|
| Component | PascalCase | `CandidateCard`, `PipelineBoard` |
| Hook | camelCase + use | `useCandidate`, `usePipeline` |
| Store | camelCase + Store | `useCandidateStore`, `usePipelineStore` |
| Type / Interface | PascalCase | `Candidate`, `JobPosting` |
| Enum | PascalCase | `CandidateStatus` |
| File component | PascalCase.tsx | `CandidateCard.tsx` |
| File hook | camelCase.ts | `useCandidate.ts` |
| File store | camelCase.ts | `candidateStore.ts` |
| File type | camelCase.ts | `candidate.ts` |
| Props type | PascalCase + Props | `CandidateCardProps` |
| CSS class | Tailwind utility only | tidak ada custom CSS kecuali terpaksa |

---

## Coding Standards

### PHP / Laravel

```php
// ✅ Gunakan Form Request untuk validasi, bukan validasi di controller
public function store(StoreRecruitmentRequestRequest $request): RedirectResponse
{
    $this->recruitmentRequestService->create($request->validated());
    return redirect()->route('recruitment-requests.index');
}

// ✅ Business logic di Service, bukan di Controller
class CandidateService
{
    public function moveToTalentPool(Candidate $candidate, string $reason): void
    {
        // logic di sini
    }
}

// ✅ Gunakan Enum untuk status
enum CandidateStatus: string
{
    case Applied = 'applied';
    case Screening = 'screening';
    case Rejected = 'rejected';
}

// ✅ Gunakan Eloquent relationship, bukan raw query
$candidate->jobPosting->department;

// ✅ Eager load untuk hindari N+1
Candidate::with(['jobPosting', 'applications'])->get();

// ❌ Jangan taruh logic di Controller
// ❌ Jangan gunakan DB::table() kecuali sangat perlu
// ❌ Jangan hardcode string status — gunakan Enum
```

### TypeScript / React

```tsx
// ✅ Selalu define Props type
interface CandidateCardProps {
  candidate: Candidate;
  onMove: (stage: PipelineStage) => void;
}

// ✅ Gunakan React Hook Form + Zod untuk semua form
const schema = z.object({
  name: z.string().min(1, 'Nama wajib diisi'),
  email: z.string().email('Email tidak valid'),
});

const { register, handleSubmit } = useForm<z.infer<typeof schema>>({
  resolver: zodResolver(schema),
});

// ✅ Gunakan TanStack Query untuk data fetching
const { data: candidates, isLoading } = useQuery({
  queryKey: ['candidates', jobPostingId],
  queryFn: () => fetchCandidates(jobPostingId),
});

// ✅ Pisahkan logic ke custom hook
function useCandidatePipeline(jobPostingId: number) {
  // data fetching + state logic di sini
  return { candidates, moveCandidate, isLoading };
}

// ✅ Komponen harus fokus ke UI saja
function PipelineBoard({ jobPostingId }: PipelineBoardProps) {
  const { candidates, moveCandidate } = useCandidatePipeline(jobPostingId);
  return <div>...</div>;
}

// ❌ Jangan fetch data langsung di komponen
// ❌ Jangan gunakan any type
// ❌ Jangan taruh business logic di komponen
```

---

## Status Management

Semua status menggunakan PHP Enum dan di-share ke frontend via Inertia props atau TypeScript types yang di-generate manual.

```php
// app/Enums/CandidateStatus.php
enum CandidateStatus: string
{
    case Applied = 'applied';
    case Screening = 'screening';
    case Test = 'test';
    case InterviewHR = 'interview_hr';
    case InterviewUser = 'interview_user';
    case BackgroundCheck = 'background_check';
    case Offering = 'offering';
    case MCUSimper = 'mcu_simper';
    case HiringDecision = 'hiring_decision';
    case PKWT = 'pkwt';
    case Hired = 'hired';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case TalentPool = 'talent_pool';
}
```

---

## Audit Trail

Setiap perubahan status penting wajib dicatat di tabel `audit_logs`:

```php
// Gunakan AuditService di setiap Service yang mengubah status penting
$this->auditService->log(
    actor: auth()->user(),
    action: 'candidate.stage_moved',
    subject: $candidate,
    meta: ['from' => $oldStage, 'to' => $newStage]
);
```

---

## Error Handling

```php
// ✅ Gunakan custom Exception untuk business rule violation
throw new BusinessRuleException('Offering tidak bisa dibuat sebelum Background Check Clear.');

// ✅ Return JSON error untuk request AJAX/Inertia
abort(422, 'Validasi gagal.');
```

---

## General Rules

- Satu controller method maksimal 10 baris — logic panjang pindah ke Service
- Tidak ada logic di migration — migration hanya untuk struktur tabel
- Semua string status menggunakan Enum — tidak boleh hardcode string
- Setiap modul baru wajib punya: Migration, Model, Service, Controller, Form Request, Inertia Page
- Tidak ada `dd()`, `var_dump()`, atau `console.log()` di kode yang di-commit
- Gunakan `php artisan make:` untuk generate file — jangan buat manual

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v2
- laravel/framework (LARAVEL) - v11
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- tightenco/ziggy (ZIGGY) - v2
- laravel/boost (BOOST) - v2
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- @inertiajs/react (INERTIA_REACT) - v2
- react (REACT) - v18
- tailwindcss (TAILWINDCSS) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/Pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v11 rules ===

# Laravel 11

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Laravel 11 brought a new streamlined file structure which this project now uses.

## Laravel 11 Structure

- In Laravel 11, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- No app\Console\Kernel.php - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Commands auto-register - files in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

## New Artisan Commands

- List Artisan commands using Boost's MCP tool, if available. New commands available in Laravel 11:
    - `php artisan make:enum`
    - `php artisan make:class`
    - `php artisan make:interface`

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>
