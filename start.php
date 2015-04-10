<?php
/**
 * Elgg Connect plugin
 *
 * @package elgg_connect
 * @author Alexander Stifel, Pascal Orth
 *
 */

elgg_register_event_handler('init', 'system', 'elgg_connect_init');

/**
 * register and loads libraries
 */
function elgg_connect_init()
{
    //Base
    $lib_dir = elgg_get_plugins_path() . "elgg_connect/lib";
    elgg_register_library('elgg_connect:functions', "$lib_dir/functions.php");
	elgg_load_library('elgg_connect:functions');

}

