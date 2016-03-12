Webservices
===========

This is a lab for investigating adding a layer of webservices to Joomla

For more details join #webservices channel at https://glip.com/

How to install
====
* Install a full current Joomla somewhere
* git clone https://github.com/joomla-projects/webservices.git OR https://github.com/chrisdavenport/webservices.git
* Zip or tar-gzip everything in the /component directory
* Install component package file as an extension in the usual way
* Copy all the files from the github clone to the web root of the CMS
* sudo composer install
* Copy config.dist.json to config.json
* Edit config.json database credentials
* Copy /www/htaccess.txt to /www/.htaccess and edit RewriteBase if required
* Log in to Administrator and go to Components -> Webservices
* Install web services one-by-one because batch doesn't work.
* Test

WARNING: Do not install on a public server with data you care about.  The current version is *totally* insecure!

NOTE: At the moment, the presence of an empty route for the home page causes PHP "Uninitialized string offset"
notices to be thrown by the router.  Ignore them for now, they don't affect functionality.

Routes and routing
====
The webservices application uses the Joomla Framework 2.0 router.
There is some documentation on it here: https://github.com/joomla-framework/router/blob/2.0-dev/docs/overview.md

Routes are stored in the routes.json file.  By default, this is in the web root, but it can be moved elsewhere
as long as you update the webservices.routes entry in config.json.

Although at present you will need to amend this file manually, the idea is that it should be possible to automatically
add routes to the routes.json file as part of the process of installing a new webservice.  The admin component should
also be extended to allow customisation of routes for a particular installation.  This is how routes can be customised
and routing conflicts resolved.

The routes.json file contains an array of resources.  Each resource has a name, a description (for humans only),
an interaction style, a collection of route templates and the HTTP methods associated with them, and an optional
collection of regular expressions for parsing named arguments.  These entries are described in more detail below.

* "name".  The resource name.  The name "contents" is reserved for use as the API entry point (it's an IANA-registed name).
* "description".  An optional brief description of the resource for human consumption.  Not used by the software.
* "style".  The interaction style associated with the resource.  By default this will be "rest" to indicate that
the REST style should be used.  Alternatively, use "soap" to use the SOAP interaction style.  This entry determines
which interaction style class, in the /Api directory, is loaded and executed.
* "routes".  This is is a collection of route definitions that will be added to the routing table.  The path contained
in a web request (or the --path argument in a CLI request) will be matched against the routing table using regular
expressions.  The route definition can contain named arguments which will be copied into the Input object.  By default,
named variables in the route use a matching regular expression of "[^/]*", which matches everything in the route, up
to the next /.  This can be overridden by adding a "regex" entry (see below).  Associated with each route definition
is an array of HTTP methods for which the route is valid.  If a path is matched with a route but the method is not
listed in this array then the API should return a 405 Method not allowed error. [Needs testing].
* "regex".  An optional collection of regular expressions to be associated with named arguments in the route definition.
Entries here will override the default regular expression for the argument concerned.  Note that backslashes in
regular expressions will need to be escaped in order to be compatible with JSON syntax.

The routes specified by default and strongly encouraged are the "industry standard" routes for web APIs, but you
can specify whatever routes you want.

If you install third-party extensions that have conflicting routes then these can be resolved by editing the routes.json
file.  The "routes" entry specifies the *public* routes available to client software.  The "name" entry specifies
the internal resource associated with the route.  That is, it will be used to determine which configuration XML file
will be loaded to handle the request.

Example routes and route definitions
=====
* "/contacts".  This will return a collection of contacts resources.
* "/contacts/:id".  This will match a path such as "/contacts/something" to return a contacts resource with id = "something".
The id will be passed in the Input object for use in the integration layer.
* "/contacts/:id" with "regex": { "id": "\\d+" }.  This tightens the specification of the :id argument so that only
a numeric argument in that position will be matched.  For example, the path "/contacts/1234" will match with the id
argument set to "1234", but the path "/contact/something" will not match. 

Linked resources
====
A linked resource is one where the resource returned is determined by a link from another resource.  For example,
the request "/categories/1234" will return the Categories resource with id 1234, whereas the request "/categories/1234/contacts"
will return the collection of Contacts resources which belong to the Categories resource with id 1234.  In this case,
the Contents collection resource returned is a linked resource of the Categories resource.

To create a route for a linked resource, modify the routes.json file using the :resource key to indicate the name of the
resource being linked to.  It's a good idea to also include a regular expression to limit the possible values this may
take.  For example, here is a possible entry for the Categories resource which includes Contacts as a linked resource.

```json
{
	"name": "categories",
	"description": "Categories collection and individual categories",
	"style": "rest",
	"routes": {
		"/categories": ["GET","POST"],
		"/categories/:id/:resource": ["GET"],
		"/categories/:id": ["GET","PUT","PATCH","DELETE"]
	},
	"regex": {
		"id": "\\d+",
		"resource": "contacts"
	}
}
```

In the profile XML file, you will need to add a link property to the resource named in the routes.json file.  This must
include a "linkField" attribute which defines the property in the resource that will be used to filter the
linked resource.  For example, here is a possible link property in the Categories resource that will link to the Contacts
resource using the "catid" field in the Categories resource to filter the linked Categories resource.
```xml
<resource
	displayName="contacts"
	transform="string"
	fieldFormat="/{webserviceName}/{id}/contacts"
	displayGroup="_links"
	linkField="catid"
	linkTitle=""
	linkName=""
	hrefLang=""
	linkTemplated="false"
	linkRel="j:contacts"
	resourceSpecific="rcwsGlobal"
/>
```

Note that the link rel visible in the public data will be that given in the linkRel attribute.  The displayName attribute
must correspond with the name of the linked resource.  @TODO This probably needs to be fixed/changed as it's not intuitive.

How to install [OLD - These instructions don't work at present]
====

1. Download release from releases packages and install

or

1. Rename build.properties.dist to build.properties and change settings inside. Run extension_packager.xml as a PHING file to get latest packages and install them


2. Enable Webservices plugin and enable webservices option in that same plugin (you can enable SOAP there too)

3. Go to Components -> Webservices and install one of the existing 3 webservices or create a new one


Tips
====

--- OAuth2 only works while having redCORE installed since we did not put OAuth2 server api yet

--- If using authorization required operations you can provide Basic authentication by providing a header ex (for admin/admin): Authorization: Basic YWRtaW46YWRtaW4=

--- Setup HAL Browser and Chrome POSTMAN for easier REST and HAL navigation ( https://github.com/mikekelly/hal-browser )

--- To get main webservices page visit http://yoursite/index.php?api=Hal


Documentation for setup
====

Detailed documentation of redCORE webservices can be found here http://redcomponent-com.github.io/redCORE/?chapters/webservices/overview.md It is planned to change major features in there but for now it is still a valid documentation for it.


Documentation for webservices and webservices working group
====

Documentation for Webservices working group can be found here: https://docs.joomla.org/Web_Services_Working_Group

# Tests
To prepare the system tests (Selenium) to be run in your local machine you are asked to:

- rename the file `tests/acceptance.suite.dist.yml` to `tests/acceptance.suite.yml`. Afterwards, please edit the file according to your system needs.
- rename the file `tests/api.suite.dist.yml` to `tests/api.suite.yml`. Afterwards, please edit the file according to your system needs.
- modify the file `config.json` according to your system needs. It will be copied by the tests to tests/joomla-cms3/

To run the tests please execute the following commands (for the moment only working in Linux and MacOS, for more information see: https://docs.joomla.org/Testing_Joomla_Extensions_with_Codeception):

```bash
$ composer install
$ vendor/bin/robo
$ vendor/bin/robo run:tests
```

What this commands do:
- Download latest joomla from the repository in the folder tests/joomla-cms3
- Copy the folders `www`, `src` and `config.json`to the tests/joomla-cms3
- Download and execute Selenium Standalone Server
- Run Codeception Tests

##For Windows:

The Tests for Windows are not yet working. The file RoboFile.php needs to be refactored to work in not *nix platforms.
