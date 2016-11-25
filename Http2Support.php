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
        if (false === \Environment::get('ssl') && $this->http2IsEnabled()) {
            \Controller::redirect(
                preg_replace('/^http/', 'https', \Environment::get('uri'))
            );
        }
    }

    /**
     * Add HTTP/2 server push assets from the page layout.
     *
     * @param \PageModel $page
     * @param \LayoutModel $layout
     */
    public function addLinkHeadersFromLayoutServerPushAssets($page, $layout)
    {
        if (!$this->http2IsEnabled($page)) {

            return;
        }

        $files = deserialize($layout->http2ServerPushAssets, true);

        if (0 === count($files)) {

            return;
        }

        $files = \FilesModel::findMultipleByUuids($files);

        if (null === $files) {

            return;
        }

        foreach ($files as $file) {
            $link = new Http2Link($file->path, 'preload');
            $link->guessAsType();

            $GLOBALS['HTTP2_PUSH_LINKS'][] = $link;
        }
    }

    /**
     * Parse custom head HTML to send the Link headers.
     *
     * @param \PageModel $page
     * @param \LayoutModel $layout
     */
    public function addLinkHeadersFromCustomHead($page, $layout)
    {
        if (!$this->http2IsEnabled($page)) {

            return;
        }

        $links = $this->findLinks($layout->head);

        $GLOBALS['HTTP2_PUSH_LINKS'] = array_merge($GLOBALS['HTTP2_PUSH_LINKS'], $links);
    }

    /**
     * @param string $buffer
     * @param string|null $template
     *
     * @return string
     */
    public function handleAssets($buffer, $template)
    {
        // No template -> output from cache
        if (null === $template) {
            $links = $this->findLinks($buffer);
            $this->sendLinkHeaders($links);

            return $buffer;
        }

        if (!$this->http2IsEnabled()) {

            return $buffer;
        }

        return $this->modifyBuffer($buffer);
    }

    /**
     * Extract <link rel="preload" ...> tags and return them.
     *
     * @param string $subject
     *
     * @return Http2Link[]
     */
    private function findLinks($subject)
    {
        preg_match_all(
            '@<link rel="(preload|prefetch)" href="([^"]+)"( as="([^"]+)")?>$@m',
            $subject,
            $matches);

        $links = [];
        foreach ($matches as $match) {
            if (!isset($match[2]) || '' === $match[2]) {
                continue;
            }

            $links[] = new Http2Link($match[2], $match[1], $match[4]);
        }

        return $links;
    }

    /**
     * Send link headers.
     *
     * @param Http2Link[] $links
     */
    private function sendLinkHeaders(array $links)
    {
        foreach ($links as $link) {
            header($link->getAsHeader(), false);
        }
    }

    /**
     * Modify the buffer.
     * - Replace the dynamic script tags before the core does it so nothing
     *   ever gets combined.
     * - Add the <link ...> tags to the <head>
     *
     * @param string $buffer
     *
     * @return string
     */
    private function modifyBuffer($buffer)
    {
        // TL_JQUERY is not external, ignore.
        // TL_MOOTOOLS is not external, ignore.
        // TL_BODY is not external, ignore.
        // TL_HEAD is not combined, ignore.
        global $objPage;
        $xhtml = 'xhtml' === $objPage->outputFormat;
        $assets = [];
        $links = [];

        $assets = array_merge($assets, $this->processCss($buffer, $xhtml));
        $assets = array_merge($assets, $this->processJs($buffer, $xhtml));

        /* @var Http2Asset $asset */
        foreach ($assets as $asset) {
            $links[] = $asset->getLinkForAsset('preload');
        }

        // Enrich CSS and JS assets with custom assets from the page layout or
        // third party modules
        foreach ((array) $GLOBALS['HTTP2_PUSH_LINKS'] as $link) {
            if (!($link instanceof Http2Link)) {
                throw new RuntimeException('Push assets have to be an instace of Http2Link');
            }

            $links[] = $link;
        }

        // Filter duplicates
        $links = array_unique($links);

        // Add the <link> tags to TL_HEAD
        /* @var Http2Link $link */
        foreach ($links as $link) {
            $GLOBALS['TL_HEAD'][] = $link->getAsTag();
        }

        // Send headers in addition to the head tags
        $this->sendLinkHeaders($links);

        return $buffer;
    }

    /**
     * Process the CSS files on the buffer and return all the assets as array.
     *
     * @param string $buffer
     * @param bool $xhtml
     *
     * @return Http2Asset[]
     */
    private function processCss(&$buffer, $xhtml)
    {
        $assets = [];

        // Add the CSS framework style sheets (TL_FRAMEWORK_CSS)
        // Add the internal style sheets
        // Add the user style sheets
        foreach (array_merge(
                     array_unique((array) $GLOBALS['TL_FRAMEWORK_CSS']),
                     array_unique((array) $GLOBALS['TL_CSS']),
                     array_unique((array) $GLOBALS['TL_USER_CSS'])
                 ) as $stylesheet) {
            $options  = \StringUtil::resolveFlaggedUrl($stylesheet);
            $asset    = new Http2Asset($stylesheet, 'css');
            $asset->setMedia($options->media);

            $assets[] = $asset;
        }

        $replacement = '';
        /* @var Http2Asset $asset */
        foreach ($assets as $asset) {
            $replacement .= $asset->getTag($xhtml) . "\n";
        }

        $buffer = str_replace('[[TL_CSS]]', $replacement, $buffer);

        // Reset values so Contao itself does not process them anymore
        $GLOBALS['TL_FRAMEWORK_CSS'] = [];
        $GLOBALS['TL_CSS'] = [];
        $GLOBALS['TL_USER_CSS'] = [];

        return $assets;
    }

    /**
     * Process the CSS files on the buffer and return all the assets as array.
     *
     * @param string $buffer
     * @param bool $xhtml
     *
     * @return Http2Asset[]
     */
    private function processJs(&$buffer, $xhtml)
    {
        $assets = [];

        foreach (array_unique((array) $GLOBALS['TL_JAVASCRIPT']) as $js) {
            $options  = \StringUtil::resolveFlaggedUrl($js);
            $asset    = new Http2Asset($js, 'js');
            $asset->setAsync($options->async);

            $assets[] = $asset;
        }

        // JS will be added to TL_HEAD in the core so we have to replace it
        // and provide the place holder again so the core can add
        // more (e.g. our own <link> tags plus, custom head tags etc.) to it.
        /* @var Http2Asset $asset */
        $replacement = '';
        foreach ($assets as $asset) {
            $replacement .= $asset->getTag($xhtml) . "\n";
        }

        $buffer = str_replace('[[TL_HEAD]]', $replacement . '[[TL_HEAD]]', $buffer);

        // Reset values so Contao itself does not process them anymore
        $GLOBALS['TL_JAVASCRIPT'] = [];

        return $assets;
    }

    /**
     * Checks if in that page tree HTTP/2 support is enabled.
     *
     * @param \PageModel $page
     *
     * @return bool
     */
    private function http2IsEnabled(\PageModel $page = null)
    {
        if (null === $page) {
            global $objPage;
            $page = $objPage;
        }

        $root = \PageModel::findByPk($page->rootId);
        return (bool) $root->enableHttp2Optimization;
    }
}
