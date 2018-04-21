#!/usr/bin/env bash

# this script is dependent on swagger-codegen@2.2.3
# install with brew on mac os x - brew version is currently 2.3.0 so follow this procedure to install
# https://stackoverflow.com/questions/39187812/homebrew-how-to-install-older-versions 

echo "Copying json spec to core.pydio"

cp $GOPATH/src/github.com/pydio/cells/common/proto/rest/rest.swagger.json ../plugins/core.pydio/routes/api3.json

echo "Generating PHP client"

swagger-codegen generate -i $GOPATH/src/github.com/pydio/cells/common/proto/rest/rest.swagger.json -l php -o /tmp/php-client

rm -rf src/pydio/Swagger

mv /tmp/php-client/SwaggerClient-php/lib src/pydio/Swagger

echo "Generating Javascript client"

swagger-codegen generate -i $GOPATH/src/github.com/pydio/cells/common/proto/rest/rest.swagger.json -l javascript -c jsclient.json -o /tmp/js-client

rm -rf ../plugins/gui.ajax/res/js/core/http/gen

mv /tmp/js-client/src ../plugins/gui.ajax/res/js/core/http/gen
