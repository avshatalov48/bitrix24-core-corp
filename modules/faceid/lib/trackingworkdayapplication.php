<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage faceid
 * @copyright  2001-2017 Bitrix
 */

namespace Bitrix\FaceId;

/**
 * @package    bitrix
 * @subpackage faceid
 */
class TrackingWorkdayApplication extends \Bitrix\Main\Authentication\Application
{
	protected $validUrls = array(
		"/bitrix/components/bitrix/faceid.timeman/ajax.php",
		"/bitrix/tools/faceid/auth.php",
	);

	public static function checkPermission()
	{
		return \Bitrix\Main\Loader::includeModule('timeman') && \CTimeMan::IsAdmin();
	}

	public static function OnApplicationsBuildList()
	{
		if (static::checkPermission())
		{
			IncludeModuleLangFile(__FILE__);

			// if admin or tm_manage_all
			return array(
				"ID" => "faceid_workday",
				"NAME" => GetMessage('TRACKING_WORKDAY_APPLICATION_NAME'),
				"DESCRIPTION" => GetMessage("TRACKING_WORKDAY_APPLICATION_DESC"),
				"SORT" => 4000,
				"CLASS" => "\\Bitrix\\FaceId\\TrackingWorkdayApplication",
			);
		}
	}
}
