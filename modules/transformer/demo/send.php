<?php

require_once ($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Transformer\DocumentTransformer;

if(!\Bitrix\Main\Loader::includeModule('transformer'))
	die('module transformer is not installed');

$file = 'test.doc';

//$file = 790;

if($_GET['file'])
	$file = $_GET['file'];

$transformer = new DocumentTransformer();

$result = $transformer->transform($file, array(DocumentTransformer::PDF, DocumentTransformer::IMAGE, DocumentTransformer::TEXT, DocumentTransformer::MD5, DocumentTransformer::CRC32, DocumentTransformer::SHA1), 'transformer', array('Bitrix\Transformer\DocumentTransformer'));
if($result->isSuccess())
{
	echo 'command send successfully'.PHP_EOL;
}
else
{
	echo 'error'.PHP_EOL;
	echo implode('<br />', $result->getErrorMessages());
}
