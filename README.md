# XML_Brute

(This is my first effort at creating an open source project, so your patience will be appreciated.)

Table of contents:
* [Introduction](#introduction)
* [Setup](#setup)
* [Using the Application](#using-the-application)
* [TO DO](#to-do)

## Introduction

XML Brute is a PHP application that will accept an arbitrary XML file and generate a relational database as output.  At this stage 
it has undergone limited testing, and practically speaking it is limited to use in Windows environments, though further development
should make it usable and useful on other OSes.

XML Brute was originally developed for use at the Washtenaw Intermediate School District as a tool for analyzing data in our county's 
state reporting files, which include several file specifications that have slowly changed over time.  As a result, XML Brute was built
to live up to its name, simply bulldozing its way through an XML file to develop a data structure map, instantiate that map as a 
relational database, and then run through the file a second time to insert the data into the database.

XML Brute has been verified to run on Windows 10 serving 32-bit PHP versions 5.3.29 through PHP 5.6.30.  It makes use of [Twitter Bootstrap v3.1](https://getbootstrap.com) for layout purposes.

## Setup

### Storage Subfolder
The `IUSR` user account must be granted write access permission to the `storage` subfolder, as the script must be able to add both 
imported XML files and newly created database files to its subfolders.

### MS Access ODBC Drivers
At the moment XML Brute is limited to producing MS Access 2010 .accdb database files as output.  This requires that the appropriate ODBC 
drivers be installed on the computer that will be running the script.  While most modern Windows machines are running a 64-bit OS, many
PHP installations are still 32-bit because the 64-bit versions are experimental.  This creates a conflict because the 32-bit version of 
PHP wants to use 32-bit ODBC drivers which are usually absent on 64-bit Windows, but they can be installed; guidance on doing so can be
found at http://www.weberpackaging.com/pdfs/How%20to%20get%2032_bit%20ODBC%20Drivers%20on%20Win7_64bit%20PC.pdf

## Using the Application
The opening screen is simply a form containing a file download field and a dropdown for selecting the type of output file.  When the form is submitted, the application runs in three steps:

1.  The entire XML file is scanned to construct a map of the implicit data structure; the map is stored as an associative array.
1.  A relational database of the user's chosen format is instantiated, and the tables called for by the map are created within it.
1.  The XML file is scanned a second time, storing the data it contains in the appropriate tables.

The application dumps status and progress information to the browser as it goes, allowing debugging of the output, and providing the database map so the relationships between the tables are apparent.

### Standalone Use
For those who don't have PHP servers lying around, I recommend downloading [PHP Desktop](../../../../cztomczak/phpdesktop).  It 
basically sets up a single-use webserver for executing a particular PHP application.  Check the README file at the repository for links 
to download the compiled program.  After you've downloaded PHP Desktop, test it to ensure it's running, and then copy the XML Brute 
files into the `www` subfolder (this will overwrite PHP Desktop's default `index.php` file, so you'll get a popup verifying you want to 
do that).  Follow the instructions above under [Setup](#setup), and then you can run PHP Desktop to get an instance of XML Brute.

## TO DO

There are several outstanding issues that need work to get this ready for general use and enhancement, and [projects](../../projects) have been added for them.  If you're interested in getting involved, check out the [Contributor guidelines](../CONTRIBUTING.MD)
