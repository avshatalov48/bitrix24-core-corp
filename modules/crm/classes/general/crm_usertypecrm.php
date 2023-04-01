<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\UserField\Types\ElementType;

Loc::loadMessages(__FILE__);

/**
 * Class CUserTypeCrm
 * @deprecated
 */
class CUserTypeCrm extends CUserTypeString
{
	public static function getUserTypeDescription()
	{
		return ElementType::getUserTypeDescription();
	}

	function prepareSettings($userField)
	{
		return ElementType::prepareSettings($userField);
	}

	function getSettingsHtml($userField, $additionalParameters, $varsFromForm)
	{
		return ElementType::getSettingsHtml($userField, $additionalParameters, $varsFromForm);
	}

	function getEditFormHtml($userField, $additionalParameters)
	{
		return ElementType::renderEditForm($userField, $additionalParameters);
	}

	function getEditFormHtmlMulty($userField, $additionalParameters)
	{
		return ElementType::renderEditForm($userField, $additionalParameters);
	}

	function getFilterHtml($userField, $additionalParameters)
	{
		return ElementType::getFilterHtml($userField, $additionalParameters);
	}

	function getAdminListViewHtml($userField, $additionalParameters)
	{
		return ElementType::renderAdminListView($userField, $additionalParameters);
	}

	function getAdminListViewHtmlMulty($userField, $additionalParameters)
	{
		return ElementType::renderAdminListView($userField, $additionalParameters);
	}

	function getAdminListEditHtml($userField, $additionalParameters)
	{
		return ElementType::getAdminListEditHTML($userField, $additionalParameters);
	}

	function getAdminListEditHtmlMulty($userField, $additionalParameters)
	{
		return ElementType::renderAdminListEdit($userField, $additionalParameters);
	}

	function checkFields($userField, $value)
	{
		return ElementType::checkFields($userField, $value);
	}

	function checkPermission($userField, $userId = false)
	{
		return ElementType::checkPermission($userField, $userId);
	}

	function onSearchIndex($userField)
	{
		return ElementType::onSearchIndex($userField);
	}

	static function getShortEntityType($entityTypeName)
	{
		return ElementType::getShortEntityType($entityTypeName);
	}

	static function getLongEntityType($entityTypeName)
	{
		return ElementType::getLongEntityType($entityTypeName);
	}

	public static function getPublicText($userField)
	{
		return ElementType::renderText($userField);
	}

	public static function getPublicView($userField, $additionalParameters = [])
	{
		return ElementType::renderView($userField, $additionalParameters);
	}

	public static function getPublicEdit($userField, $additionalParameters = [])
	{
		return ElementType::renderEdit($userField, $additionalParameters);
	}
}
