<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\UserField\Types\StatusType;

Loc::loadMessages(__FILE__);

/**
 * Class CUserTypeCrmStatus
 * @deprecated
 */
class CUserTypeCrmStatus extends CUserTypeString
{
	public static function getUserTypeDescription()
	{
		return StatusType::getUserTypeDescription();
	}

	function prepareSettings($userField)
	{
		return StatusType::prepareSettings($userField);
	}

	function getSettingsHtml($userField, $additionalParameters, $varsFromForm)
	{
		return StatusType::renderSettings($userField, $additionalParameters, $varsFromForm);
	}

	function getEditFormHtml($userField, $additionalParameters)
	{
		return StatusType::renderEditForm($userField, $additionalParameters);
	}

	function getFilterHtml($userField, $additionalParameters)
	{
		return StatusType::renderFilter($userField, $additionalParameters);
	}

	function getAdminListViewHtml($userField, $additionalParameters)
	{
		return StatusType::renderAdminListView($userField, $additionalParameters);
	}

	function getAdminListEditHtml($userField, $additionalParameters)
	{
		return StatusType::renderAdminListEdit($userField, $additionalParameters);
	}

	function checkFields($userField, $value)
	{
		return StatusType::checkFields($userField, $value);
	}

	function getList($userField)
	{
		return StatusType::getList($userField);
	}

	function onSearchIndex($userField)
	{
		return StatusType::onSearchIndex($userField);
	}

	public static function getPublicText($userField)
	{
		return StatusType::renderText($userField);
	}

	public static function getPublicEdit($userField, $additionalParameters = array())
	{
		return StatusType::renderEdit($userField, $additionalParameters);
	}

	public static function getPublicView($userField, $additionalParameters = array())
	{
		return StatusType::renderView($userField, $additionalParameters);
	}

	public static function renderEdit($userField, $additionalParameters = []): string
	{
		return StatusType::renderEdit($userField, $additionalParameters);
	}

	public static function renderView($userField, $additionalParameters = []): string
	{
		return StatusType::renderView($userField, $additionalParameters);
	}
}