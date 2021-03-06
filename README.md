# SGControlBundle

The SGControlBundle is a Symfony2 bundle that provides command line tools to interact with the ServerGrove Control Panel. It also provides an interactive shell.

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

	./console sg:api:client call [args]

* call: call composed of namespace and action (ie. server/list)
* args: (optional) list of variables to send to the call (ie. serverId=abc123&isActive=0)

## Example:

	./console sg:api:client test/version
	./console sg:api:client server/list
	./console sg:api:client server/stop serverId=abc123

## Interactive shell:

	./console sg:shell

Commands:

* help/h/? - list commands
* servers - list servers
* server [option] - selects a server from the servers list. option can be an numeric option from the list or a server name. A partial name can also be provided and it will select the first match
* exec cmd - executes a command in the selected server
* reboot [server] - reboots a server. [server] is optional. If not given it will use the selected server. It will ask for confirmation before proceding
* shutdown [server] - shuts down a server. [server] is optional. If not given it will use the selected server. It will ask for confirmation before proceding
* bootup [server] - boots up a server. [server] is optional. If not given it will use the selected server
* discover [server] - sends a discover request to server. A discover request will update server and apps information. [server] is optional. If not given it will use the selected server.
* domains - list domains under selected server
* domain [option] - selects a domain from the domains list. option can be an numeric option from the list or a domain name. A partial name can also be provided and it will select the first match
* apps - list apps under selected server
* app [option] - selects a app from the apps list. option can be an numeric option from the list or a app name. A partial name can also be provided and it will select the first match
* restart [app] - restarts an application. [app] is optional. If not given it will use the selected app. It will ask for confirmation before proceding
* stop [app] - restarts an application. [app] is optional. If not given it will use the selected app. It will ask for confirmation before proceding
* start [app] - restarts an application. [app] is optional. If not given it will use the selected app
* . - runs the last command again
* x/q - resets internal selections
* exit/quit - exits shell

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