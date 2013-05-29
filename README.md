yourls-cas-plugin
=================
This plugin for [YOURLS](https://github.com/YOURLS/YOURLS) enables the use of [Central Authentication Service](http://www.jasig.org/cas) for user authentication. CAS is commonly used at higher-ed institutions to provide faculty, staff, and students with SSO (Single Sign-On) capability for web services.

Installation
------------
1. Download phpCAS and give your webserver (eg. Apache) read permissions on it
1. Download the latest release of yourls-cas-plugin.
1. Copy the plugin folder into your user/plugins folder for YOURLS.
1. Set up the parameters for phpCAS (details below)
1. Activate the plugin with the plugin manager in the admin interface.

Usage
-----
When yourls-cas-plugin is enabled, the login screen will never be shown. Instead, you will be immediately redirected to the CAS server. If CAS login is successful, then you will immediately go to the admin interface.

Configuration
-------------
  * `PHPCAS_PATH` The path to where you installed the phpCAS library. This should be an absolute path ending with CAS.php.
  * `PHPCAS_HOST` The hostname of your CAS server
  * `PHPCAS_CONTEXT` The webapp subdirectory of your CAS server (typically /cas)
  * `PHPCAS_CERTCHAIN_PATH` You need a local copy of the certificate chain from the CAS server. That's the only way for phpCAS to verify the server's authenticity. Also, you can concatenate multiple certs into a single PEM file.

Troubleshooting
---------------
Check all of your error logs.
  * PHP error log usually at `/var/log/php.log`
  * phpCAS error log usually at `/tmp/phpcas.log`
  * And your Apache error_log, of course.

License
-------
Free software. Do whatever you want with it.
