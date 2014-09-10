<?php

if (!defined("GNUSOCIAL") && !defined("STATUSNET")) { exit(1); }

class StatisticsversionAction extends Action
{

    var $plugins = array();

    function prepare(array $args=array())
    {
        parent::prepare($args);

        $args = array();

        Event::handle('PluginVersion', array(&$this->plugins));

        header('Content-type: text/plain; charset=utf-8');

        return true;
    }

    function handle()
    {
        $data_printed = false;
        foreach($this->plugins as $plugin)
        {
            if ($plugin["name"] == "Statistics")
            {
                echo $plugin["version"];
                $data_printed = true;
            }
        }
        
        if (!$data_printed)
        {
            echo "ERROR: can't obtain Statistics plugin version.";
        }
    }
}
