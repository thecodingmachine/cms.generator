About CMS Generator
=================================

What is it?
-----------

Mouf CMS Scaffolder is a PHP library designed to easily create CMS components.


How to use ?
---------------------

To use the CMS Scaffolder, you just need to define your component name, and set it into the CMS => Scaffolder tab in Mouf's interface.
Here are the steps :

1. You set your component's name, for example : Blog
2. You click on "Generate component"
    - The library will automatically :
        - Generate an SQL file
        - Generate a database patch using this SQL file
        - Apply the database patch
        - Generate the DAOs and Beans (using TDBM) ; BlogDao, BlogBean etc.
        - Generate views
        - Generate a controller -- BlogController -- with methods allowing to :
            - Display a front-office list
            - Display a back-office list
            - Display an item
            - Edit / Save / Delete an item
3. Purge cache -- to map the new URLs
4. Let's use it !

The CMS Scaffolder does not provide (for now) a pretty display, it will let you totally free to modify the views and integer it easily in your custom template.


Design choices
--------------

In the base version, the CMS component contains :
    - Title
    - Slug (auto generated from title)
    - Short text
    - Content
    - Image
    - Creation date
    - Update date

We think these are the minimum of useful datas for a CMS component.
You don't have many useless components, and you're totally free to override the component with your custom needed datas.
