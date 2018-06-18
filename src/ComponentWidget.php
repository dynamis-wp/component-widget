<?php namespace Dynamis\ComponentWidget;

use Tekton\Support\Contracts\Manifest;
use Tekton\Components\Contracts\Component as ComponentContract;
use Tekton\Components\ComponentManager;
use ErrorException;
use Exception;
use Throwable;
use NSRosenqvist\CMB2\Widgets\CMB2_Widget;

class ComponentWidget extends CMB2_Widget
{
    protected $component;
    protected $manager;

    function __construct($component, ComponentManager $manager, $name, $widget_options = [], $control_options = [])
    {
        if (! $component instanceof ComponentContract) {
            $component = $manager->get($component);
        }

        $this->component = $component;
        $this->manager = $manager;

        parent::__construct(
            // Base ID of widget
            'component_'.str_replace('.', '_', $component->getName()),
            // Widget name will appear in UI
            $name,
            // Widget description
            $widget_options,
            // Widget options
            $control_options
        );
    }

    public static function makeClass($component, $name, $widget_options = [], $control_options = [])
    {
        if ($component instanceof ComponentContract) {
            $component = $component->getName();
        }

        $namespace = 'Dynamis\\ComponentWidget';
        $class = ucfirst(camel_case(str_replace('.', '_', $component)).'ComponentWidget');
        $namespacedClass = $namespace.'\\'.$class;
        $cachePath = get_path('widgets.cache').DS.$class.'.php';

        if (class_exists($namespacedClass)) {
            return $namespacedClass;
        }

        if (! file_exists($cachePath)) {
            $baseClass = self::class;
            $tab = "    ";

            $output = "<?php namespace $namespace;\n\n";
            $output .= "class $class extends \\$baseClass \n{";
            $output .= $tab."public function __construct() {\n";
            $output .= $tab.$tab.'$manager = app(\'components\')'.";\n";
            $output .= $tab.$tab.'$component = '.var_export($component, true).";\n";
            $output .= $tab.$tab.'$name = '.var_export($name, true).";\n";
            $output .= $tab.$tab.'$widget_options = '.var_export($widget_options, true).";\n";
            $output .= $tab.$tab.'$control_options = '.var_export($control_options, true).";\n\n";
            $output .= $tab.$tab.'parent::__construct($component, $manager, $name, $widget_options, $control_options);'."\n";
            $output .= $tab."}\n";
            $output .= "}\n";

            write_string_to_file($cachePath, $output);
        }

        // Include file
        include_global($cachePath);

        return $namespacedClass;
    }

    protected function getFields()
    {
        // If it's already been loaded we retrieve it from a class property
        if (! is_null($this->fields)) {
            return $this->fields;
        }

        // Load field configuration
        if ($fieldsDef = $this->component->get('fields')) {
            $this->processFields(require $fieldsDef);
        }

        return $this->fields;
    }

    protected function getDefaults()
    {
        $defaults = [];

        // If it's already been loaded we retrieve it from a class property
        if (! is_null($this->defaults)) {
            return $this->defaults;
        }

        if ($dataDef = $this->component->get('data')) {
            $defaults = require $dataDef;
        }

        return $this->defaults = $defaults;
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];
        echo $this->manager->include($this->component, $instance);
        echo $args['after_widget'];
    }
}
