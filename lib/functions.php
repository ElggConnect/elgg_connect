<?php
/**
 * Elgg Beaufort 8 Intranet API plugin
 *
 * @package beaufort8_intranet_api
 * @author Alexander Stifel
 *
 */

/**
 * Web service to get a status from the intranet
 *
 * Types:
 *  notification
 *  shortcut
 *
 * @return array with overview data
 *
 * if return value is -1 chat plugin is not active
 */
function status_user()
{

    if (elgg_is_active_plugin('chat')) {

        $return[] = array("object" => array(
            "type" => "notification",
            "count" => chat_messages_count(),
            "url" => "chat/all",
            "name" => "Messages",
            /*
            "items" => chat_messages_get(),
            */
        ));
    }

    if (elgg_is_active_plugin('site_notifications')) {

        $return[] = array("object" => array(
            "type" => "notification",
            "count" => notifications_count(),
            "url" => "site_notifications/view/",
            "name" => "Notifications",
            /*
            "items" => notifications_get(),
            */
        ));
    }

    if (elgg_is_active_plugin('user_support')) {
        if (user_support_staff_gatekeeper(false) || elgg_is_admin_logged_in()) {

            $return[] = array("object" => array(
                "type" => "shortcut",
                "count" => 0,
                "url" => "user_support/support_ticket",
                "name" => "Support tickets (" . user_support_count() . ")",
            ));
        }
    }

	$featuredMenuItems = elgg_get_config('site_featured_menu_names');

	$siteurl= elgg_get_site_url();
	$siteurlNum= strlen($siteurl);

	foreach ($featuredMenuItems as $featuredMenuItem ){
            
            $menuItem = elgg_get_menu_item('site',$featuredMenuItem);

			$menuText = $menuItem->getText();
			$menuUrl = $menuItem->getHref();
			$menuUrlShort = substr($menuUrl,$siteurlNum);
			$menuName = $menuItem->getName();
			            
            $return[] = array("object" => array(
                "type" => "shortcut",
                "count" => 0,
                "url" => $menuUrlShort,
                "name" => $menuText,
            ));		
	}

    return $return;
}

elgg_ws_expose_function('status.user',
    "status_user",
    array(),
    "Get a overview of the User Status ",
    'GET',
    false, true);

/**
 * Web service to get a count of the users unread chat messages
 *
 *
 * @return integer value of unread user chat message
 *
 * if return value is -1 chat plugin is not active
 */
function chat_messages_count()
{
    $user_guid = elgg_get_logged_in_user_guid();

    return elgg_get_entities_from_relationship(array(
        'type' => 'object',
        'subtype' => 'chat_message',
        'count' => true,
        'relationship' => 'unread',
        'relationship_guid' => $user_guid,
        'inverse_relationship' => true,
    ));

}

/**
 * Web service to get all unread messages form the user
 * @return array with messages
 */
function chat_messages_get()
{
    $user_guid = elgg_get_logged_in_user_guid();

    $list = elgg_get_entities_from_relationship(array(
        'type' => 'object',
        'subtype' => 'chat_message',
        'count' => false,
        'relationship' => 'unread',
        'relationship_guid' => $user_guid,
        'inverse_relationship' => true,
    ));

    if ($list) {
        foreach ($list as $message) {
            //Get an array of fields which can be exported.
            $object_entries = $message->getExportableValues();

            //save standard object_entries into array
            foreach ($object_entries as $value) {
                $m[$value] = $message->$value;
            }

            //save object url
            $m["url"] = $message->getURL() . "chat/view/" . $message->container_guid . "/";

            $return[] = $m;
        }

        return $return;

    }
}
/** //TODO: Only one Request API
 * expose_function('chat_messages.count',
 * "chat_messages_count",
 * array(),
 * "Get a count of the users unread chat messages",
 * 'GET',
 * false, true);
 */


/**
 * Web service to get a count of the users notifications
 *
 *
 * @return array value user notifications
 *
 *  * if return value is -1 chat plugin is not active

 */
function notifications_count()
{

    $owner_guid = elgg_get_logged_in_user_guid();

    return elgg_get_entities_from_metadata(array(
        'type' => 'object',
        'subtype' => 'site_notification',
        'owner_guid' => $owner_guid,
        'full_view' => false,
        'metadata_name' => 'read',
        'metadata_value' => false,
        'count' => true
    ));


}

/**
 * Web service to get all notifications form the user
 * @return array with notifications
 */
function notifications_get()
{
    $owner_guid = elgg_get_logged_in_user_guid();

    $list = elgg_get_entities_from_metadata(array(
        'type' => 'object',
        'subtype' => 'site_notification',
        'owner_guid' => $owner_guid,
        'full_view' => true,
        'limit' => 10,
        'metadata_name' => 'read',
        'metadata_value' => false,
        'count' => false

    ));


    if ($list) {
        foreach ($list as $notification) {
            //Get an array of fields which can be exported.
            $object_entries = $notification->getExportableValues();

            //save standard object_entries into array
            foreach ($object_entries as &$value) {
                $n[$value] = $notification->$value;
            }
            //save object url
            $n['url'] = $notification->getURL();;

            $return[] = $n;
        }
        return $return;
    }

}


/** //TODO: Only one Request API
 * expose_function('notifications.count',
 * "notifications_count",
 * array(),
 * "Get a count of the users notifications",
 * 'GET',
 * false, true);
 */

/**
 * Web service to get a count of the users support tickets
 *
 *
 * @return integer value of support tickets
 *
 */

function user_support_count()
{

// ignore access for support staff
$ia = elgg_set_ignore_access(true);

    return  elgg_get_entities_from_metadata(array(
        "type" => "object",
        "subtype" => UserSupportTicket::SUBTYPE,
        "full_view" => false,
        "metadata_name_value_pairs" => array("status" => UserSupportTicket::OPEN),
        "count" => true
    ));

// restore access
elgg_set_ignore_access($ia);

}



