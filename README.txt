 OGS version 2 Readme
-====================-

----------------------------------------------------------------------
 Copyright (C) 2006 by Arijit De.
 Copyright (C) 2009 by Gaurav Menghani (For the code pertaining to the 'leagues')

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.
----------------------------------------------------------------------

Installation:
-------------

Requirements: 
- Apache 1.3 or 2.0 with PHP 4.3.10+
- Mysql 3.23 or higher, with PHP module support
- GCC C and C++ compilers (3.x or above)
- GCJ compiler for Java (optional)
  Note: Sun JDK is not supported at the moment.

Instructions:
1] Extract the archive into your htdocs folder.
2] Change user & group ownership of all files in the OGS folder to 
   apache. ("chown -R apache *" and "chgrp -R apache *").
3] Create a Mysql database for the website.
4] Edit the config.inc.php, find the database variables section,
   and change the database name, username, password, 
   and host (default is localhost) fields.
5] Point your browser to the install.php script in the root OGS
   directory. Follow the instructions on that page.
   
HOWTO setup a Contest:
----------------------

1] Decide who the contest manager will be, and get that person
   to register as a user.
2] Go to 'Users' under 'Administration' menu, and click 'edit'
   action. Assign the user to the 'Managers' group.
3] Go to 'Contests' under 'Administration' menu, and fill up
   the add contest form. Select the username from the drop-down
   list for the contest manager.
4] Done! Now the contest manager will be able to view a Contest-
   Management menu for the newly created contest when he/she 
   logs in. 

Problem XML Format:
-------------------
Note: Anything between <!-- and --> will be discarded as a comment.

<problem>
    <body>
        Problem statement here. HTML tags like <p>, <b> etc. are allowed.
    </body>

    <constraints>
        <input>
            <constraint>
                First Input constraint/formatting-rule.
            </constraint>
            
            <constraint>
                Second input constraint/formatting-rule. And so on...
            </constraint>
            
            <!-- (There can be 0 or more input constraints in this way, each
                enclosed in <constraint></constraint> tags.) -->
        </input>
        
        <output>
            <constraint>
                First Output constraint/formatting-rule.
            </constraint>
            
            <constraint>
                Second input constraint/formatting-rule. And so on...
            </constraint>
            
            <!-- (There can be 0 or more output constraints in this way, each
                enclosed in <constraint></constraint> tags.) -->
        </output>
    </constraints>
    
    <tests>
        <test id="0">
            <input>
                Input for test 0.
            </input>
            <output>
                Correct output for test 0.
            </output>
        </test>
        
        <test id="1">
            <input>
                Input for test 1.
            </input>
            <output>
                Correct output for test 1.
            </output>
        </test>
        
        <!-- (And so on...) These tests will be used for grading solutions.
            The ones referred from the examples below by their IDs will be displayed
            to contestants, and be tested while submitting. The rest will be hidden,
            and only used during final system-testing phase for grading points. -->
    </tests>
    
    <examples>
        <example id="1">
            Description/analysis of example. The ID attribute given must refer to a
            specified test case.
        </example>
        
        <!-- More examples can follow. -->
    </examples>
</problem>
