# SGControlBundle

The SGControlBundle is a Symfony2 bundle that provides command line tools to interact with the ServerGrove Control Panel.

The following command line tools are provided:

* API client Command Line Interface

The API client CLI allows to connect to the ServerGrove Control Panel through a HTTP API. To connect to the API you will
need to be a registered customer with access to the Control Panel and API, and you will need to enable API access in the
user profile.

## Installation:

Download or clone the bundle. If you use deps file add it like this:

	[SGControlBundle]
		git=git://github.com/servergrove/SGControlBundle.git
		target=/bundles/ServerGrove/Bundle/SGControlBundle

Add ServerGrove namespace to app/autoload.php:

	$loader->registerNamespaces(array(
		...
		'ServerGrove' => __DIR__.'/../vendor/bundles',
		...
	));


Enable it in your app/AppKernel.php

	public function registerBundles()
	{
		$bundles = array(
			...

			new ServerGrove\Bundle\SGControlBundle\SGControlBundle(),
		);

		...
	}


## Configuration:

By default, the API client will use our demo API secret/key combination. This only lets you runs some tests against the
server but it won't allow you to access your account and servers.

You will need to enable API access in your user profile in https://control.servergrove.com/profile

Once you have the API key and secret, add it to app/config.yml:

	parameters:
		sgc_api.client.apiKey: your-key
		sgc_api.client.apiSecret: your-secret


## Usage:

	./console sgc:api:client call [args]

* call: call composed of namespace and action (ie. server/list)
* args: (optional) list of variables to send to the call (ie. serverId=abc123&isActive=0)

## Example:

	./console sgc:api:client test/version
	./console sgc:api:client server/list
	./console sgc:api:client server/stop serverId=abc123

## WARNING

**Notice:** The API is still under heavy development, so things MAY change. Please be aware of this.

## More information:

* [List of available API calls and parameters](https://control.servergrove.com/docs/api)
* [ServerGrove Website](http://www.servergrove.com/)
* [ServerGrove Blog](http://blog.servergrove.com/)
* [ServerGrove Control Panel](https://control.servergrove.com/)
* [ServerGrove Knowledge Base](https://secure.servergrove.com/clients)
* [Follow ServerGrove @ Twitter](http://twitter.com/servergrove)
* [GitHub Downloads](http://github.com/servergrove)