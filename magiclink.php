<?php
/**
 * Extend the HestiaCP Pluginable object with our MagicLink object for
 * securing entire website domain from public view unless a magiclink
 * URL is first visited by the user's web browser.
 * 
 * @author Virtuosoft/Stephen J. Carnam
 * @license AGPL-3.0, for other licensing options contact support@virtuosoft.com
 * @link https://github.com/virtuosoft-dev/hcpp-magiclink
 * 
 */

 if ( ! class_exists( 'MagicLink' ) ) {
    class MagicLink extends HCPP_Hooks {

    }
    global $hcpp;
    $hcpp->register_plugin( MagicLink::class );
}