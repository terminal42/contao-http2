# http2 - Extension for Contao 3.5

HTTP/2 provides a lot of new features and possibilities to speed up the
delivery of your page. Version 3.5 of Contao does have LTS support until
2019 but will not get any new features. While Contao 4+ will certainly
introduce new features into the core to bring you better HTTP/2 support,
this extension aims to bring the most important improvements to Contao
3.5.

## What it does

This extension aims to be as simple as possible by adding one simple
checkbox to the root page settings which lets you enable HTTP/2 support
for that domain. When you enable it, the following stuff will happen:

* Because HTTPS is de facto required to benefit from HTTP/2, this
extension will automatically force `https://` URL's and also redirect
any `http://` request to `https://`. This means when running Apache, you
don't need to edit your `.htaccess` to redirect anymore, although it is
obviously still recommended.

* Domain Sharding settings (files url and assets url, or more
specifically `TL_ASSETS_URL` and `TL_FILES_URL` will have no effect
and simply be ignored.

* The concatenation of files will be completely disabled. All JavaScript
as well as CSS files that are added to the layout or by third party
extensions won't get combined into one file anymore.

* The extension will analyze the generated HTML output before it gets
sent to the client and provide server push hints for all the detected
resources. That means that your JavaScript, CSS and image files that are
referenced in the HTML source code will get actively pushed to the client
before the client even asks for them.

## So what do I do?

First, you check for the prerequisites to run HTTP/2. That means:

* The server must support HTTP/2. You can check your own
domain here: https://tools.keycdn.com/http2-test (careful: `ALPN` has
to be supported as well!)

* The client must support HTTP/2. You can check the current support
here: http://caniuse.com/#feat=http2

* You have to have a valid TLS certificate.

As soon as these prerequisites are fulfilled, you can install this
extension, check the checkbox in the root pages you want to have
optimized HTTP/2 support for and your done. Welcome to the speedy side
of the web! You're welcome.
