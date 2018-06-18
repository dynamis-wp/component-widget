<?php namespace Dynamis\ComponentWidget\Providers;

use Dynamis\ServiceProvider;
use Tekton\Components\ComponentManager;
use Tekton\Components\Component;
use Dynamis\ComponentWidget\ComponentWidget;

class ComponentWidgetProvider extends ServiceProvider
{
    public function register()
    {
        // Register widgets cache folder
        $cacheDir = ensure_dir_exists(get_path('cache').DS.'widgets');
        $this->app->registerPath('widgets.cache', $cacheDir);

        // Register CMB2 Widgets
        $this->app->register(\NSRosenqvist\CMB2\Widgets\Providers\DynamisProvider::class);
    }

    public function boot()
    {
        // Register widgets and wrap components in widgets
        add_action('widgets_init', function() {
            $components = app('components');
            $config = app('config');
            $widgets = $config->get('components.widgets', []);
            $componentClasses = $config->get('components.widget-classes', false);

            // Refresh cache directory if we're in dev
            if ($componentClasses && app_env('development')) {
                delete_dir_contents(get_path('widgets.cache'));
            }

            foreach ($widgets as $widget => $meta) {
                $component = $components->get($widget);
                $name = $meta['title'] ?? $component->getName();

                if (! is_null($component)) {
                    if ($componentClasses) {
                        $class = ComponentWidget::makeClass($component, $name, $meta);
                        register_widget($class);
                    }
                    else {
                        $class = new ComponentWidget($component, $components, $name, $meta);
                        register_widget($class);
                    }
                }
            }
        });
    }
}
