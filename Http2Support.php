<?php

/**
 * http2 Extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2016, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/terminal42/contao-http2
 */

class Http2Support
{
    /**
     * Regular expressions to find assets in the html document.
     * It only searches for assets likely being embedded into the document and
     * not stuff like pdf files or mp4 files because this is rather content that
     * is going to be pulled only on demand.
     * Also, this only has to be best effort. The regex will cover most of the
     * files and if some are missing it doesn't harm anyway. Moreover, even
     * if the regex discovers invalid files, nothing will break. The Link header
     * will just be useless but won't really harm either.
     *
     * @var string
     */
    const assetsExtractorRegex = '@(href|src)=\"([^ ]*\.(jpe?g|png|gif|css|js|svg|svgz))\"@';

    /**
     * Ensure the root page settings are correct when enabling HTTP/2 support.
     *
     * @param $dc
     */
    public function fixRootPageSettings($dc)
    {
        if ($dc->activeRecord->enableHttp2Optimization) {
            $set = [
                'useSSL'        => 1,
                'staticFiles'   => '',
                'staticPlugins' => '',
            ];

            \Database::getInstance()->prepare('UPDATE tl_page %s WHERE id=?')
                ->set($set)
                ->execute($dc->id);
        }
    }

    /**
     * Force redirect to HTTPS if HTTP/2 support was enabled.
     */
    public function redirectToHttps()
    {
        if (false === \Environment::get('ssl')) {
            if ($this->http2IsEnabled()) {
                \Controller::redirect(preg_replace('/^http/', 'https', \Environment::get('uri')));
            }
        }
    }

    /**
     * Replace the dynamic script tags before the core does it so nothing
     * ever gets combined.
     *
     * @param string $buffer
     *
     * @return string
     */
    public function ensureFilesNotCombined($buffer)
    {
        if (!$this->http2IsEnabled()) {

            return $buffer;
        }

        // TL_JQUERY is not external, ignore.
        // TL_MOOTOOLS is not external, ignore.
        // TL_BODY is not external, ignore.
        // TL_HEAD is not combined, ignore.

        global $objPage;
        $xhtml = 'xhtml' === $objPage->outputFormat;
        $cssReplacements = [];
        $jsReplacements = [];

        // Add the CSS framework style sheets (TL_FRAMEWORK_CSS)
        foreach (array_unique((array) $GLOBALS['TL_FRAMEWORK_CSS']) as $stylesheet) {
            $options = \StringUtil::resolveFlaggedUrl($stylesheet);
            $cssReplacements[] = \Template::generateStyleTag($stylesheet, $options->media, $xhtml);
        }

        // Add the internal style sheets
        foreach (array_unique((array) $GLOBALS['TL_CSS']) as $stylesheet) {
            $options = \StringUtil::resolveFlaggedUrl($stylesheet);
            $cssReplacements[] = \Template::generateStyleTag($stylesheet, $options->media, $xhtml);
        }

        // Add the user style sheets
        foreach (array_unique((array) $GLOBALS['TL_USER_CSS']) as $stylesheet) {
            $options = \StringUtil::resolveFlaggedUrl($stylesheet);
            $cssReplacements[] = \Template::generateStyleTag($stylesheet, $options->media, $xhtml);
        }

        // Add the internal scripts
        foreach (array_unique((array) $GLOBALS['TL_JAVASCRIPT']) as $js) {
            $options = \StringUtil::resolveFlaggedUrl($js);
            $jsReplacements[] = \Template::generateScriptTag($js, $options->async);
        }

        // CSS can be replaced normally
        $buffer = str_replace('[[TL_CSS]]', implode("\n", $cssReplacements), $buffer);

        // JS will be added to TL_HEAD in the core so we have to replace it
        // and provide the place holder again so the core can add
        // more (e.g. custom head tags) to it.
        $buffer = str_replace('[[TL_HEAD]]', "\n" . implode("\n", $jsReplacements) . '[[TL_HEAD]]', $buffer);

        // Reset values so Contao itself does not process them anymore
        $GLOBALS['TL_FRAMEWORK_CSS'] = [];
        $GLOBALS['TL_CSS'] = [];
        $GLOBALS['TL_USER_CSS'] = [];
        $GLOBALS['TL_JAVASCRIPT'] = [];

        return $buffer;
    }

    /**
     * Analyzes an HTML structure and adds the Link headers to enable server
     * push.
     *
     * This is not cached because the regex is really fast.
     *
     * @param $buffer
     */
    public function handleServerPush($buffer)
    {
        if (!$this->http2IsEnabled()) {

            return $buffer;
        }

        $assets = [];

        preg_match_all(self::assetsExtractorRegex, $buffer, $matches);
        foreach ($matches[2] as $match) {
            // Skip absolute links and external protocol links
            if ('/' === $match[0] || null !== parse_url($match, PHP_URL_SCHEME)) {
                continue;
            }

            $assets[] = $match;
        }

        foreach ($assets as $asset) {
            header('Link: </' . $asset . '>; rel=prefetch', false);
        }

        return $buffer;
    }

    /**
     * Checks if in that page tree HTTP/2 support is enabled.
     *
     * @return bool
     */
    private function http2IsEnabled()
    {
        global $objPage;
        $root = \PageModel::findByPk($objPage->rootId);
        return (bool) $root->enableHttp2Optimization;
    }
}
