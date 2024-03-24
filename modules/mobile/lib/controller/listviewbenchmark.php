<?php

namespace Bitrix\Mobile\Controller;

class ListViewBenchmark extends \Bitrix\Main\Engine\Controller 
{
    public function getDepthAction()
    {
        $filepath = \Bitrix\Main\Application::getDocumentRoot().'/listviewbenchmark/depth.txt';
        $fh = fopen($filepath, 'r');
        $line = fgets($fh);
        fclose($fh);
        if (is_numeric($line)) {
            return intval($line);
        } else {
            return 1;
        }
    }
}