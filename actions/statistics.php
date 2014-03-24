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
        $stats["users_count"] = $user->COUNT("id");
        
        // Add all users logins and fullnames, ignoring
        // private-streamed guys.
        $user = new User();
        $user->query("SELECT user.id, user.nickname, profile.fullname, profile.bio, COUNT(notice.id) as notice_count, (SELECT created FROM notice WHERE profile_id=user.id ORDER BY id DESC LIMIT 1) as last_notice FROM user JOIN profile ON profile.id=user.id JOIN notice ON notice.profile_id=user.id WHERE user.private_stream=0;");
        while ($user->fetch())
        {
            $stats["users"][$user->nickname] = array(
                                                "id" => $user->id,
                                                "nickname" => $user->nickname,
                                                "fullname" => $user->fullname,
                                                "bio" => $user->bio,
                                                "notices" => $user->notice_count,
                                                "last_notice_on" => $user->last_notice
                                                );
        }
        
        // Add local groups.
        $group = new Local_group();
        $group->query("SELECT user_group.id, user_group.nickname, user_group.fullname, user_group.homepage, user_group.description, (SELECT COUNT(group_inbox.notice_id) FROM group_inbox WHERE group_inbox.group_id=user_group.id) as notices_count FROM user_group JOIN local_group ON user_group.id=local_group.group_id;");
        while ($group->fetch())
        {
            $stats["groups"][$group->id] = array(
                                                "id" => $group->id,
                                                "name" => $group->nickname,
                                                "fullname" => $group->fullname,
                                                "homepage" => $group->homepage,
                                                "description" => $group->description,
                                                "notices_count" => $group->notices_count
                                                );
        }
                
        // Get notices count.
        $notice = new Notice();
        $stats["notices_count"] = $notice->COUNT("id");
        
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
