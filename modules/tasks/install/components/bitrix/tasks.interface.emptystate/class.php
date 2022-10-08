<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2023 Bitrix
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class TasksInterfaseEmptystate extends \CBitrixComponent
{

	public function onPrepareComponentParams($params)
	{
		$params['TITLE'] = is_string($params['TITLE']) ? (string) $params['TITLE'] : '';
		$params['TEXT'] = is_string($params['TEXT']) ? (string) $params['TEXT'] : '';

		return $params;
	}

	public function executeComponent()
	{
		$this->includeComponentTemplate();
	}
}