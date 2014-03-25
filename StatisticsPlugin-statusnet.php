<?php

class StatisticsPlugin extends Plugin {
    
    function onAutoload($cls)
    {

        $dir = dirname(__FILE__);

        switch ($cls)
        {
        case 'StatisticsAction':
            include_once $dir . '/actions/statistics.php';
            return false;
        default:
            return true;
        }

    }

    public function onRouterInitialized($m)
    {
        $m->connect('main/statistics', array('action' => 'statistics'));
    }

    public function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'Statistics',
                            'version' => '0.3',
                            'author' => 'Stanislav "pztrn" Nikitin',
                            'homepage' => 'https://dev.pztrn.name/projects/gnusoctools',
                            'description' =>
                            // TRANS: Plugin description.
                            _m('Display some statistics for this Status.Net instance.'));
        return true;
    }

}

?>
