[![Build Status](https://travis-ci.org/concrete5/concrete5-legacy.png?branch=master)](https://travis-ci.org/concrete5/concrete5-legacy)

# The Rebar Project

*(noun.) a steel rod with ridges for use in reinforced concrete* - Merriam-Webster

Rebar is an effort to continue maintenance of the legacy ("ancient") Concrete5 version 5.6.x by modernizing code syntax to make it compatible with supported versions of web technologies (namely, MySQL and PHP). This effort is neither affiliated with, nor endorsed by, Portland Labs (the makers of Concrete5/ConcreteCMS).

## It's a Legacy Version
Small enhancements including the modernization of core functionality to replace deprecated elements may occur, but major new functionality is not planned at this time - it's really just an effort to keep the CMS "going" for a little while longer.

It is <u>strongly discouraged</u> to use this repository for any new development projects; New versions of concrete5 (now called ConcreteCMS) can be found at this repository: [ConcreteCMS Official Repository](http://github.com/concrete5/concrete5/)

# Updating Your Concrete5 Installation to Rebar
Because of the experimental nature of this package, it is advised for anyone who wishes to update their Concrete5 Installation to Rebar to do so in a development environment before making any changes in production. The instructions for how to do that are below. These instructions assume you are comfortable with web hosting.

Disclaimer: **BACK UP YOUR WEB SERVER - THIS INCLUDES ALL FILES AND THE DATABASE** - This software comes with absolutely no warranty and by using it, you are accepting all risks involved. By having a backup, you enable yourself to revert any changes made in the event that something goes wrong. This is also why using a development environment is advised until you are able to thoroughly test and verify the operation of your website.

1. Ensure you are running Concrete5 Version 5.6.4.0 - You can check this by logging in to the CMS and searching for "Environment Information" in the smart search bar. If not, you will need to update your CMS to this version before continuing.

2. Initialize a new development environment using your desired PHP7 version (the recommended version is 7.4.33, but you will need to ensure that any custom code such as packages and add-ons supports this as well) and the latest version of **MySQL 8.3**. Instructions on how to do this are outside the scope of this guide, but popular choices include XAMPP or WampServer for Windows. (**Note:** PHP 8 is not yet supported)

3. Once you have initialized your new development environment and copied the files/database to it, try visiting your site to confirm it's reachable. If you are running a PHP version later than 7.2, you will likely run into errors, which is normal.

4. Delete or rename your `concrete` directory (such as renaming it to `concrete_old`).

5. Delete anything contained in the `updates` folder. If this folder was **not empty**, open the file `config/site.php` in an IDE or text editor. Delete or comment out (`//`) any define lines that provide update information (for example: `define('DIRNAME_APP_UPDATED', 'concrete5.x.x.x');`) and save the file.

6. Copy the `web/concrete` folder from this repository into your website files where the old `concrete` folder was that you renamed/deleted.

7. Visit your site again in the development environment - any errors related to the Concrete5 core should now be gone. If you still run into errors, it's likely that you have unsupported code in a package or theme, or that some configuration of the development environment is not initialized correctly.

8. Verify that you are running Rebar by logging in to the dashboard and searching for "Environment Information" - The reported version should be listed as `5.6.4.1`.

## Short Tags
As part of the code modernization, short tags should have been removed from the entirety of this repository, and it is safe to turn them off (as long as any packages, themes, or other add-ons you have do not require them).