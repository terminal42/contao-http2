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
 * Config
 */
$GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'][] = ['Http2Support', 'fixRootPageSettings'];

/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_page']['palettes']['root'] = str_replace(
    'useSSL',
    'useSSL,enableHttp2Support',
    $GLOBALS['TL_DCA']['tl_page']['palettes']['root']
);

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_page']['fields']['enableHttp2Support'] = [
    'label'         => &$GLOBALS['TL_LANG']['tl_page']['enableHttp2Support'],
    'inputType'     => 'checkbox',
    'eval'          => ['tl_class' => 'clr m12'],
    'sql'   => "char(1) NOT NULL default ''"
];
