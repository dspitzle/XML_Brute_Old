Thanks for your interest in XML Brute.  At this early point in the project there's still some work I need to do before other people 
invest time in writing new code.  I need to refactor the code to split the database-specific functions (as distinct from the step of
mapping the data structure implied by the XML file) into object methods.  Once that's done the application will be ready for the 
addition of alternative database formats, at which point assistance on writing that code would be exceptionally useful.

In the meantime, however, the highest value help that people can give is testing the existing code for a) its ability to run on multiple
versions of Windows and PHP and b) its robustness with different XML files.  The code currently handles several special cases such as:

* Components that are absent in some records, present one time in others, and multiple times in still others
* Identically named components at different levels of the hierarchy (though identically named parents and children are only handled for
one generation)
* Attributes in tags

If you run into problems post them as Issues, and go ahead and file Pull requests if you have a proposal for fixing something.
