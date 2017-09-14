# XML_Brute

This is my first effort at creating an open source project, so your patience will be appreciated.

XML_Brute is


# XML Brute

Table of contents:
* [Introduction](#introduction)
* [Setup](#setup)
* [TO DO](#to-do)

## Introduction

XML Brute is a PHP application that will accept an arbitrary XML file and generate a relational database as output.  At this stage 
it has undergone limited testing, and practically speaking it is limited to use in Windows environments, though further development
should make it usable and useful on other OSes.

XML Brute was originally developed for use at the Washtenaw Intermediate School District as a tool for analyzing data in our county's state
reporting files, which include several file specifications that have slowly changed over time.  As a result, XML Brute was built to live up
to its name, simply bulldozing its way through an XML file to develop a data structure map, instantiate that map as a relational database,
and then run through the file a second time to insert the data into the database.

As a convenience for those who don't have PHP servers lying around, I have packaged a version of XML Brute with [PHP Desktop](../../../../cztomczak/phpdesktop)
so that it can be run as a more-or-less standalone application by anybody who downloads it to their local machine.

## Setup

### MS Access ODBC Drivers
At the moment XML Brute is limited to producing MS Access 2010 .accdb database files as output.  This requires that the appropriate ODBC 
drivers be installed on the computer that will be running the script.  While most modern Windows machines are running a 64-bit OS, many PHP
installations are still 32-bit because the 64-bit versions are still experimental.  This creates a conflict because the 32-bit version of 
PHP wants to use 32-bit ODBC drivers which are usually absent on 64-bit Windows, but they can be installed; guidance on doing so can be
found at http://www.weberpackaging.com/pdfs/How%20to%20get%2032_bit%20ODBC%20Drivers%20on%20Win7_64bit%20PC.pdf

## TO DO

There are several outstanding issues that need work to get this ready for primetime:

* Convert the current database format-specific functions into object methods so separate methods can be created for each database format
* Write up a local version of the instructions for installing 32-bit ODBC drivers on 64-bit machines to prevent link rot
* Add SQLite support (it was originally present in early drafts, but was pulled out while refining the Access code)
* Test SQLite mode on non-Windows environments
