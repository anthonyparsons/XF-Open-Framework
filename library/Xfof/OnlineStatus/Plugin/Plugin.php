<?php

class Xfof_OnlineStatus_Plugin_Plugin
{

    protected static $_session_timeout;
    protected static $_online_status_template;
    protected static $_online_location;

    public static function extendModel($class, array &$extend)
    {
        if($class == 'XenForo_Model_Post')
        {
            $extend[] = 'Xfof_OnlineStatus_Model_AspPost';
        }
    }

    public static function messageUserInfo_TemplateHook($name, &$contents, $params, XenForo_Template_Abstract $template)
    {
        if(in_array($name, array('message_user_info_avatar', 'message_user_info_text', 'message_user_info_extra')) && isset($params['user']['message']) && !isset($params['user']['conversation_id']))
        {
            
            
            $visitor = XenForo_Visitor::getInstance();
            
            //if the user is online, and their online status is visible, show them regardless
            if(($params['user']['view_date'] > self::_getSessionTimeout()) && $params['user']['visible'])
            {
                $userStatus['text'] = new XenForo_Phrase('online');
                $userStatus['class'] = 'UserOnline';
            }
            //if the user is online, their online status is invisible, but the current user is an admin, or they're the current user, we'll call them online-invisible
            else if(($params['user']['view_date'] > self::_getSessionTimeout()) && ($params['user']['visible'] == 0) && ($visitor['is_admin'] || $visitor['user_id'] == $params['user']['user_id']))
            {
                $userStatus['text'] = new XenForo_Phrase('online_invisible');
                $userStatus['class'] = 'UserOnlineInvisible';
            }
            //if the user is an admin, their online status is invisible, but the current user is a moderator, we'll call them online_invisible
            else if($params['user']['view_date'] > self::_getSessionTimeout() && $params['user']['visible'] == 0 && $params['user']['is_admin'] && $visitor['is_moderator'])
            {
                $userStatus['text'] = new XenForo_Phrase('online_invisible');
                $userStatus['class'] = 'UserOnlineInvisible';
            }
            //otherwise, we'll call them offline
            else
            {
                $userStatus['text'] = new XenForo_Phrase('offline');
                $userStatus['class'] = 'UserOffline';
            }

            self::_getOnlineStatusTemplate()->setParam('userStatus', $userStatus);
            foreach(array('message_user_info_avatar', 'message_user_info_text', 'message_user_info_extra') AS $location => $hook_name)
            {
                /* 
                 * This logic is kind of obtuse
                 * The location of the the online indicator tag is stored as a number between 1 to 6
                 * I'm using the $location variable to determine where we should be displaying the online status based on that.
                 * ($location + 1) * 2 is one of 2, 4, or 6. 
                 * So, if it's 1 or 2, and the template hook name is "message_user_info_avatar", we execute the code...and so on.
                 * But, I don't care about the actual number, just if it's even or odd.
                 * The unfortunate part is that we don't have a great way of extending it. I could drop it into the middle of the first two blocks
                 * and multiply by 3, but the third block would need special logic
                 * So, this is what it is for now.
                 */
                if($name == $hook_name && (self::_getOnlineLocation() == ((($location + 1) * 2) -1) || self::_getOnlineLocation() == (($location + 1) * 2)))
                {
                    if(self::_getOnlineLocation() %2 == 0)
                    {
                        $contents .= self::_getOnlineStatusTemplate();
                    }
                    else
                    {
                        $contents = self::_getOnlineStatusTemplate() . $contents;
                    }
                }
            }
        }
    }

    protected static function _getOnlineStatusTemplate()
    {
        if(!isset(self::$_online_status_template))
        {
            self::$_online_status_template = new XenForo_Template_Public('xfof_message_user_online');
        }

        return self::$_online_status_template;
    }

    protected static function _getSessionTimeout()
    {
        if(!isset(self::$_session_timeout))
        {
            self::$_session_timeout = XenForo_Model::create('XenForo_Model_Session')->getOnlineStatusTimeout();
        }

        return self::$_session_timeout;
    }

    protected static function _getOnlineLocation()
    {
        if(!isset(self::$_online_location))
        {
            self::$_online_location = XenForo_Application::get('options')->displayOnlineLocation;
        }

        return self::$_online_location;
    }
}

