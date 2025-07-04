<?php

namespace App\Providers;

use App\Models\MondayTimeTracking;
use App\Observers\MondayTimeTrackingObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('datetime', function (string $expression) {
            return "<?php echo ($expression)->format('m/d/Y H:i'); ?>";
        });
        MondayTimeTracking::observe(MondayTimeTrackingObserver::class);
    }
}
