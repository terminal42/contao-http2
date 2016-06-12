<?php

/**
 * http2 Extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2016, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/terminal42/contao-http2
 */

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['getPageLayout'][]      = ['Http2Support', 'redirectToHttps'];
$GLOBALS['TL_HOOKS']['getPageLayout'][]      = ['Http2Support', 'sendLinkHeadersForCustomHead'];
$GLOBALS['TL_HOOKS']['modifyFrontendPage'][] = ['Http2Support', 'handleAssets'];
