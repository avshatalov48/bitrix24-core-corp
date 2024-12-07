<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

return [
	'loadFile' => [
		'active' => false,
		'code' => 'loadFile',
		'nextCode' => 'changePartner',
		'content' => 'load.php',
		'title' => Loc::getMessage('SIGN_CMP_MASTER_TPL_STEP_LOAD_FILE_TITLE')
	],
	'changePartner' => [
		'active' => false,
		'code' => 'changePartner',
		'nextCode' => 'sendDocument',
		'content' => 'partner.php',
		'title' => Loc::getMessage('SIGN_CMP_MASTER_TPL_STEP_CHANGE_PARTNER_PREPARING')
	],
	'sendDocument' => [
		'active' => false,
		'code' => 'sendDocument',
		'nextCode' => 'final',
		'content' => 'send.php',
		'title' => Loc::getMessage('SIGN_CMP_MASTER_TPL_STEP_SEND_DOCUMENT_TITLE')
	],
	'final' => [
		'active' => false,
		'code' => 'final',
		'nextCode' => null,
		'content' => 'final.php',
		'title' => null
	]
];