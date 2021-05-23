<?php
namespace Bitrix\Crm\Integration\Rest\Configuration;

use Bitrix\Main\Event;

class ConfigChecker
{
	/**
	 * @param Event $event
	 */
	public static function onFinish(Event $event)
	{
		$manifestCode = $event->getParameter('MANIFEST_CODE');
		$currentManifest = $event->getParameter('IMPORT_MANIFEST');

		if (array_key_exists("USES", $currentManifest) && is_array($currentManifest["USES"]) &&
			!empty(array_intersect($currentManifest["USES"], ["crm", "bizproc_crm"])))
		{
			$res = \Bitrix\Rest\Configuration\Manifest::get($manifestCode);
			\Bitrix\Main\Config\Option::set("crm", "crm_was_imported", serialize([
				"ID" => time(),
				"CODE" => $res["CODE"],
				"TITLE" => $res["TITLE"],
				"DESCRIPTION" => $res["DESCRIPTION"],
				"ICON" => $res["ICON"],
				"COLOR" => $res["COLOR"],
				"CHECKED" => false
			]));
		}
	}

	/**
	 * @return bool
	 */
	public static function isNeedToCheck()
	{
		if (($res = \Bitrix\Main\Config\Option::get("crm", "crm_was_imported")) )
		{
			$options = unserialize($res, ['allowed_classes' => false]);
			return $options["CHECKED"] === false;
		}
		return false;
	}
}