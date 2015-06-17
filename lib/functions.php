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

	$lastrequest = elggconnect_get_lastrequest();

    if (elgg_is_active_plugin('chat')) {

		$cmc = chat_messages_count(false);
		$cmn = chat_messages_count($lastrequest);
		
		if($cmn == 1) {
			$cmt = elgg_echo('elggconnect:chat:new:singular');
		} else if ($cmn > 1 ) {
			$cmt = elgg_echo('elggconnect:chat:new:plural',array($cmn));
		} else {
			$cmt = '';	
		}
		
        $return[] = array("object" => array(
            "type" => "notification",
            "count" => $cmc,
            "new" => $cmn,
            "text" => $cmt,
            "url" => "chat/all",
            "name" => elgg_echo('elggconnect:chat:name'),
            /*
            "items" => chat_messages_get(),
            */
        ));
    }

    if (elgg_is_active_plugin('site_notifications')) {

        $noc = notifications_count(false);
        $non = notifications_count($lastrequest);

		if($non == 1) {
			$not = elgg_echo('elggconnect:notification:new:singular');
		} else if ($non > 1 ) {
			$not = elgg_echo('elggconnect:notification:new:plural',array($non));
		} else {
			$not = '';	
		}

        $return[] = array("object" => array(
            "type" => "notification",
            "count" => $noc,
            "new" => $non,
            "text" => $not,
            "url" => "site_notifications/view/",
            "name" => elgg_echo('elggconnect:notification:name'),
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
                "name" => elgg_echo('elggconnect:supportticket:name',array(user_support_count())),
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
	
	elggconnect_set_lastrequest();

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
function chat_messages_count($lastrequest = false)
{
    $user_guid = elgg_get_logged_in_user_guid();

	$options = array(
        'type' => 'object',
        'subtype' => 'chat_message',
        'count' => true,
        'relationship' => 'unread',
        'relationship_guid' => $user_guid,
        'inverse_relationship' => true);
     
     if($lastrequest != false){
		$options['created_time_lower'] = $lastrequest;
		$options['created_time_upper'] = time();
     }

    return elgg_get_entities_from_relationship($options);

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
function notifications_count($lastrequest = false)
{

    $owner_guid = elgg_get_logged_in_user_guid();

    $options = array(
        'type' => 'object',
        'subtype' => 'site_notification',
        'owner_guid' => $owner_guid,
        'full_view' => false,
        'metadata_name' => 'read',
        'metadata_value' => false,
        'count' => true
    );

     if($lastrequest != false){
		$options['created_time_lower'] = $lastrequest;
		$options['created_time_upper'] = time();
     }

	return elgg_get_entities_from_metadata($options);

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

function elggconnect_get_lastrequest() {
	$user = elgg_get_logged_in_user_entity();
	return $user->elggconnectlastrequest;
}

function elggconnect_set_lastrequest() {
	$user = elgg_get_logged_in_user_entity();
	$user->elggconnectlastrequest = time();
}


