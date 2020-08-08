<?php

namespace Bitrix\SalesCenter\Integration;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\SalesCenter\Driver;

class ImManager extends Base
{
	const IM_APP_CODE = 'salescenter';
	const OPTION_SALESCENTER_APPLICATION_ID = '~salescenter_app_id';

	/**
	 * @return string
	 */
	protected function getModuleName()
	{
		return 'im';
	}

	/**
	 * @return bool
	 */
	public function isApplicationInstalled()
	{
		$applicationId = static::getInstance()->getApplicationId();
		if(!$applicationId)
		{
			static::installApplication();
		}
		$applicationId = static::getInstance()->getApplicationId();
		return $applicationId > 0;
	}

	public static function installApplication()
	{
		if(!static::getInstance()->isEnabled())
		{
			return;
		}
		$application = \Bitrix\Im\Model\AppTable::getList([
			'filter' => [
				'=MODULE_ID' => Driver::MODULE_ID,
				'=CODE' => static::IM_APP_CODE,
			]
		])->fetch();

		if(!$application)
		{
			$iconId = static::getInstance()->saveApplicationIcon();
			// no icon - no application
			if($iconId > 0)
			{
				$applicationId = \Bitrix\Im\App::register([
					'MODULE_ID' => Driver::MODULE_ID,
					'BOT_ID' => 0,
					'CODE' => static::IM_APP_CODE,
					'REGISTERED' => 'Y',
					'ICON_ID' => $iconId,
					'JS' => 'BX.MessengerCommon.openStore()',
					'CONTEXT' => 'lines',
					'CLASS' => static::class,
					'METHOD_LANG_GET' => 'getAppLangInfo',
				]);
				if($applicationId > 0)
				{
					static::getInstance()->setApplicationId($applicationId);
				}
			}
		}
		else
		{
			static::getInstance()->setApplicationId($application['ID']);
		}
	}

	public static function unInstallApplication()
	{
		$applicationId = static::getInstance()->getApplicationId();
		if($applicationId > 0)
		{
			\Bitrix\Im\App::unRegister([
				'ID' => $applicationId,
				'FORCE' => 'Y'
			]);
			static::getInstance()->setApplicationId(0);
		}
	}

	/**
	 * @return int
	 */
	protected function getApplicationId()
	{
		return (int) Option::get(Driver::MODULE_ID, static::OPTION_SALESCENTER_APPLICATION_ID);
	}

	/**
	 * @param int $applicationId
	 */
	protected function setApplicationId($applicationId)
	{
		$applicationId = (int) $applicationId;
		Option::set(Driver::MODULE_ID, static::OPTION_SALESCENTER_APPLICATION_ID, $applicationId);
	}

	/**
	 * @param null $lang
	 * @return array|bool
	 */
	public static function getAppLangInfo($command, $lang = null)
	{
		$title = Loc::getMessage('SALESCENTER_APP_TITLE', null, $lang);
		$description = Loc::getMessage('SALESCENTER_APP_DESCRIPTION', null, $lang);

		$result = false;
		if($title <> '')
		{
			$result = [
				'TITLE' => $title,
				'DESCRIPTION' => $description,
				'COPYRIGHT' => ''
			];
		}

		return $result;
	}

	/**
	 * @return bool|int|null
	 */
	protected function saveApplicationIcon()
	{
		$iconPath = \Bitrix\Main\Application::getDocumentRoot().'/bitrix/modules/'.static::IM_APP_CODE.'/install/icon/icon_chat.png';
		if(\Bitrix\Main\IO\File::isFileExists($iconPath))
		{
			return \CFile::SaveFile(\CFile::MakeFileArray($iconPath), static::IM_APP_CODE);
		}

		return null;
	}
}