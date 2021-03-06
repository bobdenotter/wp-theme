<?php

namespace Bolt\Extension\Bobdenotter\WordpressTheme;

use Cocur\Slugify\Slugify;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class WordpressCustomize {

    public static $markCssOutputted;

    public static $cssQueue;
    public static $scriptQueue;

    /** @var \Symfony\Component\Filesystem\Filesystem */
    protected $filesystem;

    public function __construct($app)
    {
        $this->app = $app;
        $this->filesystem = new Filesystem();
    }

    public function get_setting()
    {
        return new \stdClass();
    }

    public function add_setting($id, $args = array())
    {
        $this->settings[$id] = $args;
    }

    public function add_panel($id, $args = array())
    {
        $this->panels[$id] = $args;
    }

    public function add_control($id, $args = array())
    {
        if ( $id instanceof \WP_Customize_Control ) {
            $control = $id;
        } else {
            $control = new \WP_Customize_Control( $this, $id, $args );
        }
        $this->controls[$control->id] = $control;
    }

    public function remove_control()
    {
        return true;
    }

    public function get_section()
    {
        return new \stdClass();
    }


    public function get_control()
    {
        return new \stdClass();
    }


    public function add_section($id, $args = array())
    {
        $this->sections[$id] = $args;
    }

    public function dumpSettings()
    {

        dump($this->controls);
        dump($this->settings);
        dump($this->sections);
        dump($this->panels);
    }

    public function getYaml()
    {
        $output = '';
        $lastsection = '';

        $slugify = Slugify::create('/[^a-z0-9_ -]+/');

        $escaper = new \Symfony\Component\Yaml\Escaper();

        foreach($this->controls as $id => $control) {

            if ($control->section != $lastsection) {

                if (isset($this->sections[$control->section]) && !empty($this->sections[$control->section]['title'])) {
                    $section = $this->sections[$control->section]['title'];
                } else {
                    $section = $control->section;
                }

                $output .= sprintf("\n# ---- SECTION %s ---- \n", strtoupper($section));

                if (isset($this->sections[$control->section]) && !empty($this->sections[$control->section]['description'])) {
                    $output .= sprintf("# ---- %s ---- \n", $this->sections[$control->section]['description']);
                }

                $lastsection = $control->section;
            }

            $output .= "\n";
            if (!empty($control->label)) {
                $output .= sprintf("# %s \n", $control->label);
            }
            if (!empty($control->description)) {
                $output .= sprintf("# %s \n", strip_tags($control->description));
            }

            if (!empty($control->choices) && is_array($control->choices)) {
                $output .= "# Valid choices are: \n";

                foreach ($control->choices as $key => $value) {
                    $output .= sprintf("#  - %s (%s)\n", $key, $value);
                }
            }

            $default = "";
            if (isset($this->settings[$id])) {
                $default = $this->settings[$id]['default'];
                if ($escaper->requiresSingleQuoting($default)) {
                    $default = $escaper->escapeWithSingleQuotes($default);
                }
            }

            $id = $slugify->slugify($control->id);

            $output .= sprintf("%s: %s\n", $id, $default);

        }

        return $output;

    }

    public function writeThemeYaml($yaml)
    {
        $filename = $this->app['paths']['themepath'] . "/theme.yml";
        try {
            $this->filesystem->dumpFile($filename, $yaml);
        } catch (IOException $e) {
            dump($e);
            return false;
        }
        return true;
    }


}
