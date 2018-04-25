<img src="https://github.com/pydio/cells/wiki/images/PydioCellsColor.png" width="400" />

![License Badge](https://img.shields.io/badge/License-AGPL%203%2B-blue.svg)

# Cells Front

This repository is hosting the PHP frontend for [Pydio Cells](https://github.com/pydio/cells). As such the resulting frontend is depending on a Pydio Cells backend to work properly. Please read the main project README for more information.

## Setting up your dev environment

Cells Front requires **PHP5.5.9** and upper. 

It uses Composer and NPM to manage dependencies respectively in PHP and JavaScript, and uses Grunt to build javascript sources. In order to start Cells Front locally after a fresh `git clone`, you will first have to run these tools in both the core and inside plugins. 

 - First install Composer (see https://getcomposer.org) and NPM (https://docs.npmjs.com/getting-started/installing-node)
 - Install Grunt globally by running `npm install -g grunt-cli`
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

## Building for deploying inside Pydio Cells

Use the `dist/build.sh` script to prepare the whole frontend then copy the content of the resulting `front/` folder inside Pydio Cells code (under `assets/src/pydio`).

## Authors

See the list of [contributors](https://github.com/pydio/cells/graphs/contributors) who participated in this project. Pydio Cells is also a continuation of the Pydio project and many contributions were ported from [pydio-core](https://github.com/pydio/pydio-core) to the [cells-front](https://github.com/pydio/cells-front) code.

## License

This project is licensed under the AGPLv3 License - see the [LICENSE](LICENSE) file for details
