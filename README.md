# Cells Front

PHP frontend for Pydio Cells

## Setting up your dev environment

Cells Front requires **PHP5.5.9** and upper. 

Create a virtual host to point to the root folder, set up your webserver to use index.php as default page. This is generally done by default. 

Pydio uses Composer and NPM to manage dependencies respectively in PHP and JS. It uses Grunt to build javascript sources. In order to start Pydio locally after a fresh `git clone`, you will first have to run these tools in both the core and in many plugins. 

 - First install Composer (see https://getcomposer.org) and NPM (https://docs.npmjs.com/getting-started/installing-node)
 - Install Grunt globally by running `npm install -g grunt-cli``
 - Inside the core/ folder, run `composer install`
 - For each plugin that contains a composer.json file, run `composer install` as well.
 - For each plugin tat contains a package.json file, run
   - `npm install`
   - `grunt`

On a unix-based machine, this can be achieved by the following command (from the webroot directory):  
```
find . -maxdepth 3 -name Gruntfile.js -execdir bash -c "npm install && grunt" \;  
find . -maxdepth 3 -name composer.json -execdir composer install \;
```

You should be good to go. When modifying JS files that require transpilation, there is generally a `grunt watch` task available to automatically run grunt on each file change.
