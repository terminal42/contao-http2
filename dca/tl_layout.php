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
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_layout']['palettes']['default'] = str_replace(
    'head',
    'head,http2ServerPushAssets',
    $GLOBALS['TL_DCA']['tl_layout']['palettes']['default']
);

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_layout']['fields']['http2ServerPushAssets'] = [
    'label'         => &$GLOBALS['TL_LANG']['tl_layout']['http2ServerPushAssets'],
    'inputType'     => 'fileTree',
    'eval'          => ['multiple'=>true, 'fieldType'=>'checkbox', 'filesOnly'=>true, 'isDownloads'=>true, 'tl_class' => 'clr'],
    'sql'           => "blob NULL",
];
