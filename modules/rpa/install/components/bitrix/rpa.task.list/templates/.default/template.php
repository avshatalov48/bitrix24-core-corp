<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

$APPLICATION->IncludeComponent(
	'bitrix:bizproc.task.list',
	"",
	[
		'MODULE_ID' => \Bitrix\Rpa\Driver::MODULE_ID,
		'SHOW_DOCUMENT_TYPES_TOOLBAR' => 'N',
		'TASK_EDIT_URL' => \Bitrix\Rpa\Driver::getInstance()->getUrlManager()->getTaskIdUrl('#ID#'),
		'SHOW_GROUP_ACTIONS' => 'N',
	],
	$this->getComponent()
);