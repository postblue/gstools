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
        if (common_config("site", "ssl") == "never")
        {
            $stats["instance_with_ssl"] = "0";
        }
        else
        {
            $stats["instance_with_ssl"] = "1";
        }
        $stats["instance_admin"] = common_config("site", "email");
        // Instance type: open, closed, etc.
        $stats["instance_type"] = common_config("site", "profile");

        if (defined("GNUSOCIAL"))
        {
            $stats["instance_version"] = "GNU Social-" . GNUSOCIAL_VERSION;
        }
        else if (defined("STATUSNET"))
        {
            $stats["instance_version"] = 'StatusNet-' . STATUSNET_VERSION;
        }
        else
        {
            $stats["instance_version"] = "Unknown";
        }

        $stats["twitter"] = common_config("twitter", "enabled");
        $stats["twitterimport"] = common_config("twitterimport", "enabled");

        // Get total notices count.
        $notice = new Notice();
        $stats["notices_count"] = $notice->COUNT("id");

        // Get users count.
        $user = new User();
        $stats["users_count"] = $user->COUNT("id");

        // Add all users logins and fullnames, ignoring
        // private-streamed guys.
        $user = new User();
        $user->query("SELECT user.id, user.nickname, profile.fullname, profile.bio FROM user JOIN profile ON profile.id=user.id WHERE user.private_stream=0;");
        while ($user->fetch())
        {
            if (defined("GNUSOCIAL"))
            {
                $thisuser = User::getKv('id', $user->id);
            }
            else if (defined("STATUSNET"))
            {
                $thisuser = User::staticGet('id', $user->id);
            }
            $stats["users"][$user->nickname] = array(
                                                "id" => $user->id,
                                                "nickname" => $user->nickname,
                                                "fullname" => $user->fullname,
                                                "bio" => $user->bio,
                                                "notices" => $thisuser->getProfile()->noticeCount(),
                                                "last_notice_on" => $thisuser->getCurrentNotice()->created
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
        $notice->query("SELECT source, count(id) as noticecount FROM notice WHERE is_local = '1' GROUP BY source");
        while ($notice->fetch())
        {
            $stats["sources"][$notice->source] = $notice->noticecount;
        }

        // Afterall, print this. In json.
        echo json_encode($stats);
    }
}

?>
