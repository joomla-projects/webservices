Webservices
===========

This is a lab for investigating adding a layer of webservices to Joomla

For more details join #webservices channel at https://glip.com/


How to install
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
