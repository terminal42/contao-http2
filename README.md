# http2 - Extension for Contao 3.5

HTTP/2 provides a lot of new features and possibilities to speed up the
delivery of your page. Version 3.5 of Contao does have LTS support until
2019 but will not get any new features. While Contao 4+ will certainly
introduce new features into the core to bring you better HTTP/2 support
(which already happened with Contao 4.3),
this extension aims to bring the most important improvements to Contao
3.5.

## What the extension does

This extension aims to be as simple as possible by adding one simple
checkbox to the root page settings which lets you enable HTTP/2 support
for that domain. When you enable it, the following stuff will happen:

* Because HTTPS is de facto required to benefit from HTTP/2, this
extension will automatically force `https://` URL's and also redirect
any `http://` request to `https://`. This means you do not necessarily
need to add the redirect to your server configuration anymore, although
it is obviously still recommended for performance reasons.

* Domain Sharding settings (files url and assets url, or more
specifically `TL_ASSETS_URL` and `TL_FILES_URL` will be reset to empty
values when you store the root page settings.

* The concatenation of files will be completely disabled. All JavaScript
as well as CSS files that are added to the layout or by third party
extensions won't get combined into one file anymore.

* The extension will automatically generate `<link rel="preload"...>`
tags to the `<head>` part of your HTML document for all CSS as well as
JavaScript files so you can benefit from HTTP/2's ability for server
push automatically.

* It provides a new setting in the page layout to choose more assets
you want to preload automatically.

## Setup

First, check if all the requirements for a client to server communication
using HTTP/2 are fulfilled. That means:

* The server must support HTTP/2. You can check your own
domain here: https://tools.keycdn.com/http2-test (careful: `ALPN` has
to be supported as well, also see the notes on
http://caniuse.com/#feat=http2 for the clients!)

* The client must support HTTP/2. You can check the current support
here: http://caniuse.com/#feat=http2

* You have to have a valid TLS certificate.

All you need to do then is to install this extension and activate
the checkbox `Optimize for HTTP/2` in the root page settings.
Welcome to the speedy side of the web! You're welcome.

## FAQ

### Why is there no automated server push for images in the content?

It's not possible to determine whether an image should be pushed or not.
Imagine if the module would analyze the whole HTML output for the
occurrence of `.jpg` or `.png` files. What if it is just a lightbox
hint (`<a href="......jpg"...>`) that opens a huge file in the lightbox?
Or what about all the responsive images? Where's the point in pushing
all variants of the responsive image to the client? It would render
the whole point about responsive images completely useless.
If you e.g. have a logo that is loaded on every page, this sure is a 
use case for server push. Just go to the page layout settings and choose
the logo which this module will then automatically generate the 
`<link rel="preload"...>` and `Link:` HTTP headers for.

### I can add the preload link to the additional head section myself, why is there an option in the page layout settings?

You are right. The answer is: Convenience.

### I added my own preload link in the head section but I need the Link header :-(

No worries. The extension got you covered. The additional head tags part
of the page layout is parsed and the `Link: ` HTTP headers are
added for you :-)

### Why do you add the preload link to the HTML document? The HTTP header would be enough?

You are right. The `<link rel="preload"...>` is added to the HTML document
so the Contao page cache is supported as well. The extension will
automatically add the `Link: ` HTTP headers based on those tags when
sending the HTML output from the cache.

### What happens for older clients, only supporting HTTP/1.1?

HTTP/2 is 100% backwards compatible so everything will just work fine.
It's not possible to optimize for both, HTTP/1.1 and HTTP/2 at the same
time which means that as soon as you activated the `Optimize for HTTP/2`
checkbox in the root page settings, nobody will get any combined files
anymore and everyone will get the server push information.
This means that your HTTP/1.1 visitors will be hit by a performance
penalty but without any effects on the functionality of the website.
That's called progressive enhancement.

### So how do I decide whether I want HTTP/2 or not?

You want it. Period. There is actually only one exception to that rule:
If you think most of your target audience (e.g. say you're building an
intranet application) still runs clients that do not support HTTP/2, do
not use this extension. Otherwise, there's no reason not to use it.
If you think there are more reasons, feel free to file an issue ;-)
