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