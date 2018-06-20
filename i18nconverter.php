<?php

define(PYDIO_EXEC, true);

convertFolder($argv[1]);

function convertFolder($folder){
    print "Reading folder " . $folder . "\n";
    $h = opendir($folder);
    while ($filename = readdir($h)) {
        if ($filename == "." || $filename == "..") {
            continue;
        }
        $file = $folder . "/" . $filename;
        if (is_dir($file)) {
            convertFolder($file);
            continue;
        }
        if (strpos($filename, ".php") === FALSE){
            continue;
        }
        $mess = [];
        include_once $file;
        if(count($mess)) {
            $new = str_replace(".php", ".json", $file);
            file_put_contents($new, json_encode($mess, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}

