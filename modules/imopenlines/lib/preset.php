<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\ImOpenlines;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\ImConnector;
use Bitrix\Notifications;

Loc::loadMessages(__FILE__);

class Preset
{
	protected $errors = array();
	protected static $version = 1;
	protected static $versionOptionName = 'preset_version';

	protected static function getVersion()
	{
		return self::$version;
	}

	protected static function getInstalledVersion()
	{
		return (int) Option::get('imopenlines', self::$versionOptionName, 0);
	}

	public static function updateInstalledVersion($version = null)
	{
		if($version === null)
		{
			$version = self::getVersion();
		}

		Option::set('imopenlines', self::$versionOptionName, $version);
	}

	public static function checkVersion()
	{
		return self::getVersion() > self::getInstalledVersion();
	}

	public function install()
	{
		if(!self::checkVersion())
		{
			return true;
		}

		$result = $this->addEssentialConnectors();
		if($result)
		{
			self::updateInstalledVersion();
		}

		$callback = array(__CLASS__, 'installVersion' . self::getVersion());
		if (is_callable($callback))
		{
			call_user_func_array($callback, array());
		}

		return $result;
	}

	public function uninstall()
	{
	}

	public static function findActiveLineId(): ?int
	{
		$row = \Bitrix\ImOpenLines\Model\ConfigTable::getRow([
			'select' => ['ID'],
			'filter' => [
				'=ACTIVE' => 'Y'
			],
			'order' => [
				'STATISTIC.IN_WORK' => 'DESC',
				'STATISTIC.SESSION' => 'DESC'
			],
		]);

		return $row ? (int)$row['ID'] : null;
	}

	protected function addEssentialConnectors()
	{
		if (!\Bitrix\Main\Loader::includeModule('imconnector'))
		{
			return false;
		}
		$configManager = new \Bitrix\ImOpenLines\Config();
		$lineCreated = false;
		$lineId = static::findActiveLineId();
		if (!$lineId)
		{
			$lineId = $configManager->create();
			if (!$lineId)
			{
				return false;
			}
			$lineCreated = true;
		}

		$result = ImConnector\Connector::add($lineId, 'livechat');
		if (!$result->isSuccess() && $lineCreated)
		{
			$configManager->delete($lineId);
			return false;
		}

		if (!Loader::includeModule('notifications'))
		{
			return true;
		}

		$virtualWhatsappStatus = Notifications\Settings::getScenarioAvailability(Notifications\Settings::SCENARIO_VIRTUAL_WHATSAPP);
		if ($virtualWhatsappStatus === Notifications\FeatureStatus::AVAILABLE)
		{
			ImConnector\Tools\Connectors\Notifications::addToLine($lineId,Notifications\Settings::SCENARIO_VIRTUAL_WHATSAPP);
		}

		return true;
	}

	/*
	public static function installVersion2()
	{
	}
	*/
}
