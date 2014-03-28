<?php
/**
 * Stack Module Entry Point
 *
 * @package    jevolve.extensions
 * @subpackage Modules
 * @link       http://jevolve.net
 * @license    GNU/GPL, see LICENSE.php
 * mod_stack is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// Include the syndicate functions only once
require_once __DIR__ .'/helper.php';

$template = $params->get('template', 'default');

$list = modVideoHelper::getItem( $params );

require( JModuleHelper::getLayoutPath( 'mod_video', $template ) );