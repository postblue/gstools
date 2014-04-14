<?php

class StatisticsPlugin extends Plugin {

    public function onRouterInitialized($m)
    {
        $m->connect('main/statistics', array('action' => 'statistics'));
        $m->connect('main/statistics_version', array('action' => 'statisticsversion'));
    }

    public function onPluginVersion(&$versions)
    {
        $versions[] = array('name' => 'Statistics',
                            'version' => '0.4',
                            'author' => 'Stanislav "pztrn" Nikitin',
                            'homepage' => 'https://dev.pztrn.name/projects/gnusoctools',
                            'description' =>
                            // TRANS: Plugin description.
                            _m('Display some statistics for this GNU Social instance.'));
        return true;
    }

}

?>
