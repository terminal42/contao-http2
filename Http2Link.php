<?php

/**
 * http2 Extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2016, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/terminal42/contao-http2
 */

class Http2Link
{
    private $asset;
    private $hint;
    private $as;

    /**
     * Http2Link constructor.
     *
     * @param string $asset
     * @param string $hint
     * @param string|null $as
     */
    public function __construct($asset, $hint, $as = null)
    {
        $this->asset = $asset;
        $this->hint  = $hint;
        $this->as    = $as;
    }

    /**
     * @return mixed
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * @param mixed $asset
     *
     * @return Http2Link
     */
    public function setAsset($asset)
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHint()
    {
        return $this->hint;
    }

    /**
     * @param mixed $hint
     *
     * @return Http2Link
     */
    public function setHint($hint)
    {
        $this->hint = $hint;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAs()
    {
        return $this->as;
    }

    /**
     * @param mixed $as
     *
     * @return Http2Link
     */
    public function setAs($as)
    {
        $this->as = $as;

        return $this;
    }

    /**
     * Get the link as header.
     *
     * @return string
     */
    public function getAsHeader()
    {
        $as = null !== $this->getAs() ? sprintf(' as=%s', $this->getAs()) : '';
        return sprintf('Link: <%s>; rel=%s;%s',
            $this->getAsset(),
            $this->getHint(),
            $as
        );
    }

    /**
     * Get the link as tag.
     *
     * @return string
     */
    public function getAsTag()
    {
        $as = null !== $this->getAs() ? sprintf(' as="%s"', $this->getAs()) : '';
        return sprintf('<link rel="%s" href="%s"%s>',
            $this->getHint(),
            $this->getAsset(),
            $as
        );
    }

    /**
     * Try to guess the as type of the link asset if none was given.
     */
    public function guessAsType()
    {
        $extension = pathinfo($this->asset, PATHINFO_EXTENSION);

        // Do not handle svg and svgz as it might be both, an image or a font
        switch ($extension) {
            // Image
            case 'jpg':
            case 'jpeg':
            case 'gif':
            case 'png':
                $this->setAs('image');
                break;

            // Script
            case 'js':
                $this->setAs('script');
                break;

            // Style
            case 'css':
                $this->setAs('style');
                break;

            // Font
            case 'eot':
            case 'ttf':
            case 'woff':
            case 'woff2':
                $this->setAs('font');
                break;

            // Media
            case 'mp4':
            case 'mp3':
            case 'webm':
            case 'ogg':
                $this->setAs('media');
                break;
        }
    }

    /**
     * Returns the header representation. Useful to compare links.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getAsHeader();
    }
}
