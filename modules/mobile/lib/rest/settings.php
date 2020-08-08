<?php

namespace Bitrix\Mobile\Rest;

class Settings extends \IRestService
{
	public static function getMethods()
	{
		return [
			'mobile.settings.tabs.set' => ['callback' => [__CLASS__, 'setTabs'], 'options' => ['private' => false]],
			'mobile.settings.energy.set' => ['callback' => [__CLASS__, 'setOtherSettings'], 'options' => ['private' => false]],
			'mobile.settings.energy.get' => ['callback' => [__CLASS__, 'getOtherSettings'], 'options' => ['private' => false]],
			'mobile.settings.other.set' => ['callback' => [__CLASS__, 'setOtherSettings'], 'options' => ['private' => false]],
			'mobile.settings.other.get' => ['callback' => [__CLASS__, 'getOtherSettings'], 'options' => ['private' => false]],
		];
	}

	public static function setTabs($params, $offset, \CRestServer $server)
	{
		//TODO
	}

	public static function setOtherSettings($params, $offset, \CRestServer $server)
	{
		global $USER;
		$userId = $USER->getId();
		if($userId)
		{
			if(array_key_exists("push_low_activity", $params))
			{
				\Bitrix\Main\Config\Option::set("mobile", "push_save_energy_".$userId,  $params["push_low_activity"] == true);
			}
			if(array_key_exists("allow_invite_users", $params))
			{
				\Bitrix\Main\Config\Option::set("bitrix24", "allow_invite_users",  ($params["allow_invite_users"] ? 'Y' : 'N'));
			}
		}
	}

	public function getOtherSettings($params, $offset, \CRestServer $server)
	{
		global $USER;
		$userId = $USER->getId();

		return [
			"push_low_activity" => \Bitrix\Main\Config\Option::get("mobile", "push_save_energy_".$userId,  false),
			"allow_invite_users" => (\Bitrix\Main\Config\Option::get("bitrix24", "allow_invite_users", "N") == "Y")
		];
	}

}