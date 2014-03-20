<?php

if (!defined("GNUSOCIAL") && !defined("STATUSNET")) { exit(1); }

class StatisticsAction extends Action
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
        $stats = array();
        // Inserting some vital stats.
        $stats["instance_name"] = common_config("site", "name");
        $stats["instance_address"] = common_config("site", "server");
        $stats["instance_with_ssl"] = common_config("site", "ssl");
        
        if (defined("GNUSOCIAL"))
        {
            $stats["instance_version"] = "GNU Social-" . GNUSOCIAL_VERSION;
        }
        else if (defined("STATUSNET"))
        {
            $stats["instance_version"] = 'Status.Net-' . STATUSNET_VERSION;
        }
        else
        {
            $stats["instance_version"] = "Unknown";
        }
        
        $stats["twitter"] = common_config("twitter", "enabled");
        $stats["twitterimport"] = common_config("twitterimport", "enabled");
        
        // Get users count.
        $user = new User();
        $user->query("SELECT COUNT(id) FROM user;");
        while ($user->fetch())
        {
            $stats["users_count"] = $user->COUNT("id");
        }
        
        // Add all users logins and fullnames, ignoring
        // private-streamed guys.
        $user = new User();
        $user->query("SELECT user.id, user.nickname, profile.fullname FROM user JOIN profile ON profile.id=user.id WHERE user.private_stream=0;");
        while ($user->fetch())
        {
            $stats["users"][$user->nickname] = array(
                                                "id" => $user->id,
                                                "nickname" => $user->nickname,
                                                "fullname" => $user->fullname
                                                );
        }
        
        // Add local groups.
        $group = new Local_group();
        $group->query("SELECT * FROM local_group;");
        while ($group->fetch())
        {
            $stats["groups"][$group->group_id] = array(
                                                "id" => $group->group_id,
                                                "name" => $group->nickname
                                                );
        }
                
        // Get notices count.
        $notice = new Notice();
        $notice->query("SELECT COUNT(id) FROM notice;");
        while ($notice->fetch())
        {
            $stats["notices_count"] = $notice->COUNT("id");
        }
        
        // Fill with plugins :)
        $stats["plugins"] = array();
        
        foreach ($this->plugins as $plugin)
        {
            $stats["plugins"][$plugin["name"]] = array(
                                                "name" => $plugin["name"],
                                                "version" => $plugin["version"],
                                                "homepage" => $plugin["homepage"]
                                                );
        }
        
        // Notice sources.
        $stats["sources"] = array();
        $notice = new Notice();
        $notice->query("SELECT DISTINCT(source) FROM notice;");
        //$sources = $notice->fetchAll();
        while ($notice->fetch())
        {
            $tmpnotice = new Notice();
            $tmpnotice->query("SELECT COUNT(id) FROM notice WHERE source='{$notice->source}' AND is_local='1';");
            while ($tmpnotice->fetch())
            {
                $tmpnoticevars = get_object_vars($tmpnotice);
                $stats["sources"][$notice->source] = $tmpnoticevars["COUNT(id)"];
            }            
        }
        
        // Afterall, print this. In json.
        echo json_encode($stats);
    }
}

?>
