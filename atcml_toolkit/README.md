# ATCML PHP - Toolkit

The purpose of this PHP Class is to provide an example and a help to parse an ATCML file to process an ATCNA newswire.

## How to use the parser

     <?php  
         include("classes/class.atcmlparser.php");
         $parser = new ATCMLParser();
         $content = $parser->parseContent('52e28e1febb2854aa29eef56_generic.xml');  
         print_r($content);  
     ?>

## How to use the importer

    <?php
        $importer = new ATCMLImporter();   
        $contents = $importer->parseDirectory('directory');
        foreach($contents as $content) {
    	    $importer->cleanAllFiles($content, true);
        }
    ?>

## Success and Error Callback
Each time a content is parsed callback is call

    <?php
        function error_handling($file, $message, $exception) {
            ...
        }

        function success_handling($content, $file) {
            ...
        }

        $importer = new ATCMLImporter();
        $importer->addErrorCallback('error_handling');
        $importer->addSuccessCallback('success_handling');
    ?>

