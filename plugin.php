<?php
/**
 * Plugin Name: MagicLink
 * Plugin URI: https://github.com/virtuosoft-dev/hcpp-magiclink
 * Description: Secures an entire website domain until a magic link URL is first visited.
 * Author: Virtuosoft/Stephen J. Carnam
 * License AGPL-3.0, for other licensing options contact support@virtuosoft.com
 */

// Register the install and uninstall scripts
global $hcpp;
require_once( dirname(__FILE__) . '/magiclink.php' );