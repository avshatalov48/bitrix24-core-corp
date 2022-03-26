<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class TasksWidgetResultField extends \CBitrixComponent
{
	/**
	 * @param null $component
	 */
	public function __construct($component = null)
	{
		parent::__construct($component);
	}

	/**
	 * @param array $params
	 * @return array
	 */
	public function onPrepareComponentParams($params)
	{
		if (
			!isset($params['HIDDEN'])
			|| $params['HIDDEN'] !== 'Y'
		)
		{
			$params['HIDDEN'] = 'N';
		}

		return $params;
	}

	/**
	 * @return mixed|void|null
	 */
	public function executeComponent()
	{
		try
		{
			$this->includeComponentTemplate();
		}
		catch (\Bitrix\Main\SystemException $exception)
		{

		}
	}
}