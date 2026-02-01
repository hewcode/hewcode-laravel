<?php

namespace Hewcode\Hewcode\Panel;

use Closure;
use Error;
use Hewcode\Hewcode\Hewcode;
use Hewcode\Hewcode\Panel\Controllers\DashboardController;
use Hewcode\Hewcode\Panel\Controllers\PageController;
use Hewcode\Hewcode\Panel\Controllers\Resources\ResourceController;
use Hewcode\Hewcode\Panel\Controllers\ServeResourceController;
use Hewcode\Hewcode\Panel\Navigation\Navigation;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

class Panel
{
    protected string $name;

    protected ?string $title = null;

    protected string $layout = 'sidebar';

    protected string|Closure|null $logo = null;

    protected string|Closure|null $logoIcon = null;

    protected Navigation $navigation;

    protected ?Closure $navigationUsing = null;

    protected bool $loginEnabled = true;

    protected bool $registerEnabled = true;

    protected bool $passwordResetEnabled = true;

    protected bool $emailVerificationEnabled = true;

    protected bool $profileSettingsEnabled = true;

    protected bool $passwordSettingsEnabled = true;

    protected bool $appearanceSettingsEnabled = true;

    protected array $middleware = [];

    protected ?string $dashboardController = null;

    public function __construct(?string $name)
    {
        $this->name = $name ?? Hewcode::config()->getDefaultPanel();
        $this->navigation = app(Navigation::class, ['panel' => $this->name]);
        $this->middleware = [
            \Hewcode\Hewcode\Http\Middleware\RedirectIfNotAuthenticated::class.':'.$this->name,
        ];
    }

    public static function make(?string $name): self
    {
        return new self($name);
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function sidebarLayout(): self
    {
        $this->layout = 'sidebar';

        return $this;
    }

    public function headerLayout(): self
    {
        $this->layout = 'header';

        return $this;
    }

    public function logo(string|Closure $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function logoIcon(string|Closure $logoIcon): self
    {
        $this->logoIcon = $logoIcon;

        return $this;
    }

    public function login(bool $enabled = true): self
    {
        $this->loginEnabled = $enabled;

        return $this;
    }

    public function registration(bool $enabled = true): self
    {
        $this->registerEnabled = $enabled;

        return $this;
    }

    public function passwordReset(bool $enabled = true): self
    {
        $this->passwordResetEnabled = $enabled;

        return $this;
    }

    public function emailVerification(bool $enabled = true): self
    {
        $this->emailVerificationEnabled = $enabled;

        return $this;
    }

    public function profileSettings(bool $enabled = true): self
    {
        $this->profileSettingsEnabled = $enabled;

        return $this;
    }

    public function passwordSettings(bool $enabled = true): self
    {
        $this->passwordSettingsEnabled = $enabled;

        return $this;
    }

    public function appearanceSettings(bool $enabled = true): self
    {
        $this->appearanceSettingsEnabled = $enabled;

        return $this;
    }

    public function dashboard(string $controller): self
    {
        if (! is_subclass_of($controller, DashboardController::class)) {
            throw new InvalidArgumentException(
                "Dashboard controller [{$controller}] must extend ".DashboardController::class
            );
        }

        $this->dashboardController = $controller;

        return $this;
    }

    public function middleware(array $middleware = [], array $append = []): self
    {
        if (! empty($append)) {
            $this->middleware = array_merge($this->middleware, $append);
        } else {
            $this->middleware = $middleware;
        }

        return $this;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function isLoginEnabled(): bool
    {
        return $this->loginEnabled;
    }

    public function isRegisterEnabled(): bool
    {
        return $this->registerEnabled;
    }

    public function isPasswordResetEnabled(): bool
    {
        return $this->passwordResetEnabled;
    }

    public function isEmailVerificationEnabled(): bool
    {
        return $this->emailVerificationEnabled;
    }

    public function isProfileSettingsEnabled(): bool
    {
        return $this->profileSettingsEnabled;
    }

    public function isPasswordSettingsEnabled(): bool
    {
        return $this->passwordSettingsEnabled;
    }

    public function isAppearanceSettingsEnabled(): bool
    {
        return $this->appearanceSettingsEnabled;
    }

    public function register(): void
    {
        $this->registerRoutes();
        $this->registerNavigation();
    }

    public function navigation(?Closure $navigationUsing = null): self
    {
        $this->navigationUsing = $navigationUsing;

        return $this;
    }

    public function registerRoutes(): void
    {
        Route::middleware('web')->prefix('/'.$this->name)->name('hewcode.'.$this->name.'.')->group(function () {
            $this->registerAuthRoutes();
            $this->registerSettingsRoutes();

            Route::middleware($this->middleware)->group(function () {
                $discovered = Hewcode::discovered($this->name);
                $customDashboardWasDiscovered = false;

                foreach ($discovered as $class) {
                    try {
                        // Skip default DashboardController if custom one is provided
                        if ($this->dashboardController && $class === DashboardController::class) {
                            continue;
                        }

                        // Track if custom dashboard was discovered
                        if ($this->dashboardController && $class === $this->dashboardController) {
                            $customDashboardWasDiscovered = true;
                        }

                        if (is_a($class, Resource::class, true)) {
                            /** @var \Hewcode\Hewcode\Panel\Resource $resource */
                            $resource = app($class);

                            /** @var ResourceController $pageController */
                            foreach ($resource->getPageControllers() as $pageController) {
                                $this->registerPage($pageController, ServeResourceController::class);
                            }
                        } else {
                            $this->registerPage($class);
                        }
                    } catch (Error) {
                        //
                    }
                }

                // If custom dashboard wasn't discovered, register it manually
                if ($this->dashboardController && ! $customDashboardWasDiscovered) {
                    $this->registerPage($this->dashboardController);
                }
            });
        });
    }

    protected function registerAuthRoutes(): void
    {
        Route::middleware('guest')->group(function () {
            if ($this->registerEnabled) {
                Route::get('register', [\Hewcode\Hewcode\Panel\Controllers\Auth\RegisteredUserController::class, 'create'])
                    ->name('register');

                Route::post('register', [\Hewcode\Hewcode\Panel\Controllers\Auth\RegisteredUserController::class, 'store'])
                    ->name('register.store');
            }

            if ($this->loginEnabled) {
                Route::get('login', [\Hewcode\Hewcode\Panel\Controllers\Auth\AuthenticatedSessionController::class, 'create'])
                    ->name('login');

                Route::post('login', [\Hewcode\Hewcode\Panel\Controllers\Auth\AuthenticatedSessionController::class, 'store'])
                    ->name('login.store');
            }

            if ($this->passwordResetEnabled) {
                Route::get('forgot-password', [\Hewcode\Hewcode\Panel\Controllers\Auth\PasswordResetLinkController::class, 'create'])
                    ->name('password.request');

                Route::post('forgot-password', [\Hewcode\Hewcode\Panel\Controllers\Auth\PasswordResetLinkController::class, 'store'])
                    ->name('password.email');

                Route::get('reset-password/{token}', [\Hewcode\Hewcode\Panel\Controllers\Auth\NewPasswordController::class, 'create'])
                    ->name('password.reset');

                Route::post('reset-password', [\Hewcode\Hewcode\Panel\Controllers\Auth\NewPasswordController::class, 'store'])
                    ->name('password.store');
            }
        });

        Route::middleware($this->middleware)->group(function () {
            if ($this->emailVerificationEnabled) {
                Route::get('verify-email', \Hewcode\Hewcode\Panel\Controllers\Auth\EmailVerificationPromptController::class)
                    ->name('verification.notice');

                Route::get('verify-email/{id}/{hash}', \Hewcode\Hewcode\Panel\Controllers\Auth\VerifyEmailController::class)
                    ->middleware(['signed', 'throttle:6,1'])
                    ->name('verification.verify');

                Route::post('email/verification-notification', [\Hewcode\Hewcode\Panel\Controllers\Auth\EmailVerificationNotificationController::class, 'store'])
                    ->middleware('throttle:6,1')
                    ->name('verification.send');
            }

            Route::get('confirm-password', [\Hewcode\Hewcode\Panel\Controllers\Auth\ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');

            Route::post('confirm-password', [\Hewcode\Hewcode\Panel\Controllers\Auth\ConfirmablePasswordController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('password.confirm.store');

            if ($this->loginEnabled) {
                Route::post('logout', [\Hewcode\Hewcode\Panel\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])
                    ->name('logout');
            }
        });
    }

    protected function registerSettingsRoutes(): void
    {
        Route::middleware($this->middleware)->group(function () {
            if ($this->profileSettingsEnabled || $this->passwordSettingsEnabled || $this->appearanceSettingsEnabled) {
                Route::get('settings', function () {
                    if ($this->profileSettingsEnabled) {
                        return redirect(Hewcode::route('profile.edit', panel: $this));
                    } elseif ($this->passwordSettingsEnabled) {
                        return redirect(Hewcode::route('password.edit', panel: $this));
                    } elseif ($this->appearanceSettingsEnabled) {
                        return redirect(Hewcode::route('appearance.edit', panel: $this));
                    }

                    return redirect(Hewcode::route('dashboard'));
                })->name('settings');
            }

            if ($this->profileSettingsEnabled) {
                Route::get('settings/profile', [\Hewcode\Hewcode\Panel\Controllers\Settings\ProfileController::class, 'edit'])->name('profile.edit');
                Route::patch('settings/profile', [\Hewcode\Hewcode\Panel\Controllers\Settings\ProfileController::class, 'update'])->name('profile.update');
                Route::delete('settings/profile', [\Hewcode\Hewcode\Panel\Controllers\Settings\ProfileController::class, 'destroy'])->name('profile.destroy');
            }

            if ($this->passwordSettingsEnabled) {
                Route::get('settings/password', [\Hewcode\Hewcode\Panel\Controllers\Settings\PasswordController::class, 'edit'])->name('password.edit');

                Route::put('settings/password', [\Hewcode\Hewcode\Panel\Controllers\Settings\PasswordController::class, 'update'])
                    ->middleware('throttle:6,1')
                    ->name('password.update');
            }

            if ($this->appearanceSettingsEnabled) {
                Route::get('settings/appearance', function () {
                    return \Inertia\Inertia::render('hewcode/settings/appearance');
                })->name('appearance.edit');
            }
        });
    }

    public function registerNavigation(): void
    {
        $discovered = Hewcode::discovered($this->name);
        $customDashboardWasDiscovered = false;

        foreach ($discovered as $class) {
            // Skip default DashboardController if custom one is provided
            if ($this->dashboardController && $class === DashboardController::class) {
                continue;
            }

            // Track if custom dashboard was discovered
            if ($this->dashboardController && $class === $this->dashboardController) {
                $customDashboardWasDiscovered = true;
            }

            if (is_a($class, Resource::class, true)) {
                /** @var \Hewcode\Hewcode\Panel\Resource $resource */
                $resource = app($class);

                /** @var ResourceController $controller */
                foreach ($resource->getPageControllers() as $controller) {
                    $controller->registerNavigation($this->navigation);
                }
            } else {
                /** @var PageController $controller */
                $controller = app($class);

                $controller->registerNavigation($this->navigation);
            }
        }

        // If custom dashboard wasn't discovered, register its navigation manually
        if ($this->dashboardController && ! $customDashboardWasDiscovered) {
            /** @var PageController $controller */
            $controller = app($this->dashboardController);
            $controller->registerNavigation($this->navigation);
        }
    }

    private function registerPage(string|ResourceController $controller, ?string $handler = null): void
    {
        $controller = is_object($controller) ? $controller : app($controller);

        $routePath = $controller->getRoutePath();
        $routeName = $controller->getRouteName();

        Route::get($routePath, $handler ?? $controller::class)->name($routeName);
    }

    public function getNavigation(): Navigation
    {
        if ($this->navigationUsing) {
            ($this->navigationUsing)($this->navigation);
        }

        return $this->navigation;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function getLogo(): ?string
    {
        return $this->logo instanceof Closure
            ? ($this->logo)()
            : $this->logo;
    }

    public function getLogoIcon(): ?string
    {
        return $this->logoIcon instanceof Closure
            ? ($this->logoIcon)()
            : $this->logoIcon;
    }

    public function toData(): array
    {
        return [
            'name' => $this->name,
            'title' => $this->title,
            'layout' => $this->layout,
            'logo' => $this->getLogo(),
            'logoIcon' => $this->getLogoIcon(),
            'navigation' => $this->getNavigation()->toData(),
            'features' => [
                'login' => $this->loginEnabled,
                'registration' => $this->registerEnabled,
                'passwordReset' => $this->passwordResetEnabled,
                'emailVerification' => $this->emailVerificationEnabled,
                'profileSettings' => $this->profileSettingsEnabled,
                'passwordSettings' => $this->passwordSettingsEnabled,
                'appearanceSettings' => $this->appearanceSettingsEnabled,
            ],
        ];
    }
}
