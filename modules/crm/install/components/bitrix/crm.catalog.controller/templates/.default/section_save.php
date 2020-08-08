<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

if (!defined('URL_BUILDER_TYPE'))
{
	define('URL_BUILDER_TYPE', 'CRM');
}
include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/iblock/admin/iblock_section_edit.php');