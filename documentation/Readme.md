Resource Bundle
===============
The resource bundle is a replacement to the old Input, Imports, and Photo bundles. This bundle handles pretty much all the i/o channels for EvolutionSDK. Not only does it handle GET, POST, COOKIE, FILES, and STREAM requests. It also handles the output of photos, and downloads of files that have been stored on the platform.

Accessing GET, POST, FILES, Etc...
==================================

	$get = e::$resource->get
	$post = e::$resource->post
	$files = e::$resource->files
	$all = e::$resource->all

	// Not included in ->all (and not scanned)
	$stream = e::$resource->stream

Disabling XSS and Scanning
==========================
If you need to disable XSS, Antivirus, etc. Use `e::$resource->noscan->post`