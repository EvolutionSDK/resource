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

Showing Files/Images
====================
You can access files by going to `/@resource/{bundle}/{model}/{id}/{hash}` if you leave out the hash it will show the last uploaded image for the model. If you want specific image dimensions you can add `/x:30` and `/y:20` etc. You can also replace the `@resource` by setting the environment variable `resource.path`. This field will not be prompted on use. It will only be present if you set it manually from the `/@manage` manager on the environment tile.