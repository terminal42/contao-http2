<?php

/**
 * http2 Extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2016, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/terminal42/contao-http2
 */

class Http2Asset
{
    private $url;
    private $type;
    private $media;
    private $async;

    /**
     * Http2Asset constructor.
     *
     * @param $url
     * @param string|null $type
     */
    public function __construct($url, $type = null)
    {
        if (!preg_match('@^https?://@', $url)) {
            throw new InvalidArgumentException('Must provide absolute links for Http2Asset instances!');
        }

        if (null !== $type && !in_array($type, ['js', 'css'])) {
            throw new LogicException('Sorry but this is only here for JS and CSS assets.');
        }

        $this->url  = $url;
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @param bool $media
     *
     * @return Http2Asset
     */
    public function setMedia($media)
    {
        $this->media = (bool) $media;

        return $this;
    }

    /**
     * @return bool
     */
    public function getAsync()
    {
        return $this->async;
    }

    /**
     * @param bool $async
     *
     * @return Http2Asset
     */
    public function setAsync($async)
    {
        $this->async = (bool) $async;

        return $this;
    }

    /**
     * Get the tag string for either js or css files.
     *
     * @param bool $xhtml
     *
     * @return string
     */
    public function getTag($xhtml)
    {
        if (null === $this->type) {
            throw new BadMethodCallException('This one is only here for js and css assets.');
        }

        $tag = '<';

        if ('js' === $this->type) {
            $tag .= 'script';

            if ($xhtml) {
                $tag .= ' type="text/javascript"';
            }

            $tag .= ' src="' . $this->url . '"';

            if ($this->async && !$xhtml) {
                $tag .= ' async';
            }

            $tag .= '></script>';
        } else {
            $tag .= 'link';

            if ($xhtml) {
                $tag .= ' type="text/css"';
            }

            $tag .= ' rel="stylesheet"';
            $tag .= ' href="' . $this->url . '"';

            if ($this->media && 'all' !== $this->media) {
                $tag .= ' media="' . $this->media . '"';
            }

            $tag .= $xhtml ? '/>' : '>';
        }

        return $tag;
    }

    /**
     * Get the link instance for a given asset.
     *
     * @param string $hint
     *
     * @return Http2Link
     */
    public function getLinkForAsset($hint)
    {
        $link = new Http2Link($this->url, $hint);

        if ('css' == $this->type) {
            $link->setAs('style');
        } elseif ('js' === $this->type) {
            $link->setAs('script');
        } else {
            $link->guessAsType();
        }

        return $link;
    }
}
