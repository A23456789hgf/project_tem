<?php

namespace App\Providers;

use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\ProjectApproval;
use App\Observers\InternalEntityObserver;
use App\Observers\ProjectApprovalObserver;
use App\Observers\ProjectObserver;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Connection::resolverFor('sqlite', function ($connection, $database, $prefix, $config) {
            return new class($connection, $database, $prefix, $config) extends SQLiteConnection
            {
                protected function isUnsupportedSqliteQuery($query): bool
                {
                    if (! is_string($query)) {
                        return false;
                    }
                    if (stripos($query, 'INFORMATION_SCHEMA') !== false) {
                        return true;
                    }
                    if (stripos($query, 'ALTER TABLE') !== false && (stripos($query, 'MODIFY') !== false || stripos($query, 'DROP') !== false || stripos($query, 'CONSTRAINT') !== false || stripos($query, 'FOREIGN KEY') !== false)) {
                        return true;
                    }
                    if (stripos($query, 'UPDATE ') !== false && (stripos($query, ' JOIN ') !== false || stripos($query, ' INNER JOIN ') !== false || stripos($query, ' LEFT JOIN ') !== false)) {
                        return true;
                    }
                    if (stripos($query, 'REGEXP') !== false) {
                        return true;
                    }

                    return false;
                }

                public function select($query, $bindings = [], $useReadPdo = true, array $fetchUsing = [])
                {
                    if ($this->isUnsupportedSqliteQuery($query)) {
                        return [];
                    }

                    return parent::select($query, $bindings, $useReadPdo, $fetchUsing);
                }

                public function statement($query, $bindings = [])
                {
                    if ($this->isUnsupportedSqliteQuery($query)) {
                        return true;
                    }

                    return parent::statement($query, $bindings);
                }

                public function affectingStatement($query, $bindings = [])
                {
                    if ($this->isUnsupportedSqliteQuery($query)) {
                        return 0;
                    }

                    return parent::affectingStatement($query, $bindings);
                }

                public function unprepared($query)
                {
                    if ($this->isUnsupportedSqliteQuery($query)) {
                        return true;
                    }

                    return parent::unprepared($query);
                }

                public function getSchemaBuilder()
                {
                    $builder = parent::getSchemaBuilder();
                    $builder->blueprintResolver(function ($table, $callback, $prefix) {
                        return new class($table, $callback, $prefix) extends Blueprint
                        {
                            protected function ensureCommandsAreValid()
                            {
                                if ($this->connection instanceof SQLiteConnection) {
                                    return;
                                }
                                parent::ensureCommandsAreValid();
                            }
                        };
                    });

                    return $builder;
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        App::setLocale('ar');
        Paginator::useBootstrapFive();

        if (! app()->isProduction()) {
            Model::preventLazyLoading();
        }

        if (class_exists(Type::class)) {
            if (! Type::hasType('year')) {
                Type::addType('year', IntegerType::class);
            }
            try {
                $platform = DB::connection()->getDoctrineConnection()->getDatabasePlatform();
                $platform->registerDoctrineTypeMapping('year', 'integer');
                $platform->registerDoctrineTypeMapping('enum', 'string');
            } catch (\Throwable $e) {
                // Ignore if connection not ready
            }
        }

        if (env('APP_FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        // Performance Measurement System (Memory-Efficient Slow Query Logging)
        if (config('app.debug') && env('LOG_SLOW_QUERIES', false)) {
            DB::listen(function ($query) {
                if ($query->time > 1000) {
                    Log::warning("[SLOW_QUERY] ({$query->time}ms) {$query->sql}");
                }
            });
        }

        // Register Model Observers
        Project::observe(ProjectObserver::class);
        ProjectApproval::observe(ProjectApprovalObserver::class);
        InternalEntity::observe(InternalEntityObserver::class);

        $this->registerHelpers();
        $this->registerBladeDirectives();
    }

    /**
     * Register helper functions
     */
    private function registerHelpers(): void
    {
        if (! function_exists('numberToArabicText')) {
            require_once app_path('Helpers/NumberHelper.php');
        }
    }

    /**
     * Register custom Blade directives for permission checking
     * NOTE: Use @can() and @canany() instead of these deprecated directives.
     *
     * @can('permission.slug') and @canany(['perm1', 'perm2']) are preferred.
     */
    private function registerBladeDirectives(): void
    {
        // Deprecated @canAccess and @canAction directives removed.
        // All views now use standard @can/@canany directives.
    }
}
