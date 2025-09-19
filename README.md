# Open-Education-Badges-Moodle-Plugin
A Moodle LMS Plugin to use the Open Badges from the platform Open Educational Badges.
=================

Open Educational Badges is a platform that provides the tools your organization needs to implement a meaningful and sustainable Open Badges system.

With the local_openeducationbadges plugin you can issue Badges created in Open Educational Badges. To use the plugin, you need an account on
[https://openbadges.education](https://openbadges.education) (You can register for free).


How to install
--------------

Moodle 4.1 and up:

1. Install the zip via Moodle's plugin page. Select "local" as the type of the plugin. (alternative: unzip to moodle's local subdirectory)
2. Update the database using the notifications page
3. Complete the [Post install steps](README.md#post-install)

Post install
------------------

To connect to Open Educational Badges, the plugin needs a request token or Credentials.

To generate the required Credentials, log in to Open Educational Badges. When logged in, navigate to `Konto > App Integrationen`.

This supports multiple clients on one Moodle installation.

On the Credentials page click on `Neue Credentials registrieren` for OAuth2 Client Credentials. Give a name for the credentials and copy the client id and secret values into OEB Moodle plugin settings, in `Site administration > Open Education Badges > Clients`.

Notice
------------------

If you are self hosting the Open Educational Badges platform together with your Moodle application, then you probably have to remove the private network 192.168.0.0/16 from the cURL blocked hosts list in the Moodle HTTP security settings.
