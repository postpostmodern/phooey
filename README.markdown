Phooey
===================================

*A crapload of boilerplate code for a simple web site is a bunch of phooey!*

A Simple Web Site Framework for Mostly-Static Sites
---------------------------------------------------

Not all web sites are web applications, nor are they all blogs (which are applications too, basically). For a simple web site, you don't need an application framework. But you shouldn't have to write a bunch of HTML from scratch for every single page either. Plain old HTML files suffer from all sorts of repetitive code and such. Phooey is a simple templating system that offers a bunch of convenience features:

* Simple templates built out of bits of HTML stored in separate files
* Master configuration options for the whole site in a YAML file including:
  * Site title
  * CSS files to include
  * Javascript files to include
  * Meta tags to include
* Custom configuration settings for each individual page
* A YAML site map defines every page in the site
* Pretty URLs are built-in (no configuration needed)
* Custom PHP functions (in the actions.php file) can be run before rendering pages
* Automatic navigation generation
* Query string patterns can be set on a per-page basis for parsing get variables from pretty URLs
* Google AJAX Libraries API built-in
* Google Analytics tracking code built-in
* A bunch of other goodies

If you're like me, you probably have a bunch of files that you use as a starting place for every site. And every time you start a site, you have to copy those files to a new project, go through and strip out stuff and replace it with new info, figure out how you're going to keep from having to duplicate the header, sidebar, nav and other code on each page, download the latest jQuery or Prototype, and so on. 

I wrote Phooey to basically automate all that stuff. The primary goal was to abstract the unique parts from the boilerplate stuff.

### Here's the basic idea

* Configuration of the whole site goes into a master.yaml file.
* Defining and configuring each page goes into a pages.yaml file, which is basically a site-map.
* To create templates, you create all the parts (header, footer, nav, sidebar, etc) in separate HTML files and put them in the templates folder.
* Once the parts are created, you group them by listing them in the templates.yaml file under a nickname.
* Once the templates are set up, you can create the content (the HTML that is specific to each individual page) in the 'content' folder.

Installation
------------

When you download Phooey, it's a complete phooey installation with a few example pages. The _public_ folder is meant to be used as the root web directory.

However, it's best to use Phooey as an installer. Once you download Phooey, execute the INSTALL script like so:

    ./INSTALL /path/to/installation
    
For example:

    ./INSTALL /Users/jason/Sites/phooey_demo
    
This will install all of the core files and some example files. If you want to update/upgrade Phooey, download it again and run the UPGRADE script in the same manner:

    ./UPGRADE /path/to/installation
    
That will just replace all of the core files without the example files. It will also leave all of your other files alone.

### UPGRADE Note

The `phooey/templates/open.part` and `phooey/templates/close.part` template files are special. They are not upgraded automatically because some users may want to modify them. 
When upgrading, it's probably a good idea to check those files against your existing installation to see if they have changed.

Upgrading from Phooey (1) to Phooey 2
-------------------------------------

Phooey, or Phooey 1 was entirely procedural and was mostly compatible with PHP 4. Phooey 2 is the Object-Oriented version of Phooey. It runs on PHP 5 or greater. It is not completely backwards-compatible with Phooey 1. Thus, the normal install/upgrade scripts will only get you part of the way there. The primary things you need to look for are:

* Actions are now encapsulated in the Actions class.
  * You need to put all of your action functions into the Action class. (see phooey/lib/Actions.class.php)
  * Actions no longer take an argument, but they have access to `$this->vars` and `$this->page`.
  * Actions no longer need to return the entire $vars array. Just the elements that need adding/changing.
* Helpers are now encapuslated in the Helper class.
  * You need to put all of your helper functions into the Helper class. (see phooey/lib/Helper.class.php)
  * All calls to the helper functions should now be prepended with `$helper->`
* Update your open.part and close.part files with the new helper methods.