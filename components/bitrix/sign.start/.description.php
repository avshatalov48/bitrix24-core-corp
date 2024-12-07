<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

$arComponentDescription = [
	'NAME' => getMessage('SIGN_CMP_START_NAME'),
	'DESCRIPTION' => getMessage('SIGN_CMP_START_DESCRIPTION'),
	'SORT' => 5,
	'COMPLEX' => 'Y',
	'PATH' => [
		'ID' => 'sign',
		'NAME' => getMessage('SIGN_CMP_NAMESPACE_NAME')
	]
];
