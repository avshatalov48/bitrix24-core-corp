<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\SiteButton;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Imopenlines;

Loc::loadMessages(__FILE__);

/**
 * Class Manager
 * @package Bitrix\Crm\SiteButton
 */
class Manager
{
	const ENUM_TYPE_OPEN_LINE = 'openline';
	const ENUM_TYPE_CRM_FORM = 'crmform';
	const ENUM_TYPE_CALLBACK = 'callback';
	const ENUM_TYPE_WHATSAPP = 'whatsapp';

	protected static $selectWidgets = true;

	public static function canUseOpenLine()
	{
		return ChannelManager::canUse(self::ENUM_TYPE_OPEN_LINE);
	}

	public static function canUseCrmForm()
	{
		return ChannelManager::canUse(self::ENUM_TYPE_CRM_FORM);
	}

	public static function canUseCallback()
	{
		return ChannelManager::canUse(self::ENUM_TYPE_CALLBACK);
	}

	public static function getTypeList()
	{
		return ChannelManager::getTypeNames();
	}

	public static function getWidgetByTypeId($typeId)
	{
		$types = self::getTypeList();
		if (isset($types[$typeId]))
		{
			return $types[$typeId];
		}
		else
		{
			return null;
		}
	}

	/**
	 * @internal
	 */
	public static function disableWidgetSelect()
	{
		self::$selectWidgets = false;
	}

	/**
	 * @internal
	 */
	public static function isWidgetSelectDisabled()
	{
		return !self::$selectWidgets;
	}

	public static function getWidgetList()
	{
		static $list = null;

		if ($list !== null)
		{
			return $list;
		}

		$list = array();
		$types = ChannelManager::getTypes();
		foreach ($types as $type)
		{
			$item = ChannelManager::getChannelArray($type);
			if ($item)
			{
				$list[] = $item;
			}
		}

		return $list;
	}

	/**
	 * Returns language list
	 *
	 * @return array
	 */
	public static function getLanguages()
	{
		$list = array();

		$found = false;
		if (ModuleManager::isModuleInstalled("bitrix24"))
		{

			$fileName = Application::getDocumentRoot() . SITE_TEMPLATE_PATH . "/languages.php";
			$fileExists = file_exists($fileName);
			if (!$fileExists)
			{
				$fileName = Application::getDocumentRoot()
					. getLocalPath('templates/bitrix24', BX_PERSONAL_ROOT)
					. "/languages.php"
				;
				$fileExists = file_exists($fileName);
			}
			if ($fileExists)
			{
				global $b24Languages;
				include_once $fileName;
				if (isset($b24Languages) && is_array($b24Languages))
				{
					$list = $b24Languages;
					$found = !empty($list);
				}
			}
		}

		if (!$found)
		{
			$langDir = Application::getDocumentRoot() . '/bitrix/modules/crm/lang/';
			$dir = new \Bitrix\Main\IO\Directory($langDir);
			if ($dir->isExists())
			{
				foreach($dir->getChildren() as $childDir)
				{
					if (!$childDir->isDirectory())
					{
						continue;
					}

					$list[] = $childDir->getName();
				}

				if (count($list) > 0)
				{
					$listDb = \Bitrix\Main\Localization\LanguageTable::getList(array(
						'select' => array('LID', 'NAME'),
						'filter' => array(
							'=LID' => $list,
							'=ACTIVE' => 'Y'
						),
						'order' => array('SORT' => 'ASC')
					));
					$list = array();
					while ($item = $listDb->fetch())
					{
						$list[$item['LID']] = array("NAME" => $item['NAME']);
					}
				}
			}
		}

		return $list;
	}

	/**
	 * Returns avatars file list
	 *
	 * @return array
	 */
	public static function getAvatars()
	{
		$list = array();
		$listDb = Internals\AvatarTable::getList(array(
			'order' => array('DATE_CREATE' => 'DESC')
		));
		while ($item = $listDb->fetch())
		{
			$file = \CFile::getFileArray($item['FILE_ID']);
			if (!$file)
			{
				continue;
			}

			$image = \CFile::resizeImageGet(
				$file,
				array('width' => 100, 'height' => 100),
				BX_RESIZE_IMAGE_PROPORTIONAL, false
			);
			if($image['src'])
			{
				$path = $image['src'];
			}
			else
			{
				$path = \CFile::getFileSRC($file);
			}

			if (mb_substr($path, 0, 1) == '/')
			{
				$path = ResourceManager::getServerAddress() . $path;
			}

			$list[] = array(
				'ID' => $item['FILE_ID'],
				'PATH' => $path
			);
		}

		return $list;
	}

	public static function updateScriptCacheWithForm($formId)
	{
		$buttons = Internals\ButtonTable::getList(['filter' => ['=ACTIVE' => 'Y']]);
		foreach ($buttons as $buttonData)
		{
			$button = new Button();
			$button->loadByData($buttonData);
			if (in_array($formId, $button->getWebFormIdList()))
			{
				Script::saveCache($button);
			}
		}
	}

	/**
	 * Updates widget script cache for the open line with the given id and activity flag.
	 *
	 * @param int $lineId Id of the open line.
	 * @param bool $active Is line active.
	 * @throws \Exception
	 */
	public static function updateScriptCacheWithLineId(int $lineId, bool $active): void
	{
		$widgets = self::getWidgetsByOpenlineId($lineId);
		foreach ($widgets as $widget)
		{
			self::changeOpenlinePresenceForWidget($widget, $active);
		}
	}

	public static function updateScriptCache($fromButtonId = null)
	{
		$filter = array();
		if ($fromButtonId)
		{
			$filter['>=ID'] = $fromButtonId;
		}
		$buttonDb = Internals\ButtonTable::getList(array(
			'filter' => $filter,
			'order' => array('ID' => 'ASC')
		));
		while($buttonData = $buttonDb->fetch())
		{
			$button = new Button();
			$button->loadByData($buttonData);
			if (!Script::saveCache($button))
			{
				return $buttonData['ID'];
			}
		}

		return null;
	}

	public static function updateScriptCacheAgent($fromButtonId = null)
	{
		/*@var $USER CUser*/
		global $USER;
		if (!is_object($USER))
		{
			$USER = new \CUser();
		}

		$resultButtonId = self::updateScriptCache($fromButtonId);
		if ($resultButtonId && $resultButtonId <> $fromButtonId)
		{
			return '\\Bitrix\\Crm\\SiteButton\\Manager::updateScriptCacheAgent(' . $resultButtonId . ');';
		}
		else
		{
			return '';
		}
	}

	public static function onImConnectorChange()
	{
		static $isAdded = false;
		if ($isAdded)
		{
			return;
		}

		$isAdded = true;

		$agent = new \CAgent();
		$list = $agent->getList(
			["ID" => "DESC"],
			[
				"MODULE_ID" => "crm",
				"NAME" => "\\Bitrix\\Crm\\SiteButton\\Manager::updateScriptCacheAgent%"
			]
		);
		if ($list->fetch())
		{
			return;
		}

		\CAgent::addAgent('\\Bitrix\\Crm\\SiteButton\\Manager::updateScriptCacheAgent();', "crm", "N", 60, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::getOffset(), "FULL"));
	}

	public static function getList($params = array('order' => array('ID' => 'DESC'), 'cache' => array('ttl' => 36000)))
	{
		$result = array();
		$typeList = self::getTypeList();
		$locationList = Internals\ButtonTable::getLocationList();
		$buttonDb = Internals\ButtonTable::getList($params);
		$domain = \Bitrix\Crm\WebForm\Script::getDomain();
		while($buttonData = $buttonDb->fetch())
		{
			$button = new Button();
			$button->loadByData($buttonData);

			$buttonData['IS_PAGES_USED'] = false;
			$items = array();
			foreach ($typeList as $typeId => $typeName)
			{
				if ($button->hasActiveItem($typeId))
				{
					$item = $button->getItemByType($typeId);
					$items[$typeId] = array(
						'ID' => $item['EXTERNAL_ID'],
						'NAME' => $item['EXTERNAL_NAME'],
						'TYPE_NAME' => $typeName,
					);
				}

				$buttonData['IS_PAGES_USED'] = $buttonData['IS_PAGES_USED'] || $button->hasItemPages($typeId);
			}
			$buttonData['ITEMS'] = $items;

			if ($buttonData['IS_PAGES_USED'])
			{
				$buttonData['PAGES_USE_DISPLAY'] = Loc::getMessage('CRM_BUTTON_MANAGER_PAGES_USE_DISPLAY_USER');
			}
			else
			{
				$buttonData['PAGES_USE_DISPLAY'] = Loc::getMessage('CRM_BUTTON_MANAGER_PAGES_USE_DISPLAY_ALL');
			}

			$buttonData['LOCATION_DISPLAY'] = null;
			if (isset($buttonData['LOCATION']))
			{
				$buttonData['LOCATION_DISPLAY'] = $locationList[$buttonData['LOCATION']] ?? null;
			}
			$buttonData['PATH_EDIT'] = $domain . str_replace(
				'#id#',
				$buttonData['ID'],
				Option::get('crm', 'path_to_button_edit', '/crm/button/edit/#id#/')
			);
			$buttonData['SCRIPT'] = Script::getScript($button);
			$result[] = $buttonData;
		}

		return $result;
	}

	/**
	 * Is button in use
	 * @return bool
	 */
	public static function isInUse()
	{
		$resultDb = Internals\ButtonTable::getList(array(
			'select' => array('ID'),
			'limit' => 1
		));
		if ($resultDb->fetch())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Is button with callback in use
	 * @return bool
	 */
	public static function isCallbackInUse()
	{
		return \Bitrix\Crm\WebForm\Manager::isInUse('Y');
	}

	/**
	 * Check read permissions
	 * @param null|\CCrmAuthorizationHelper $userPermissions User permissions.
	 * @return bool
	 */
	public static function checkReadPermission($userPermissions = null)
	{
		return \CCrmAuthorizationHelper::CheckReadPermission('BUTTON', 0, $userPermissions);
	}

	/**
	 * Check write permissions
	 * @param null|\CCrmAuthorizationHelper $userPermissions User permissions.
	 * @return bool
	 */
	public static function checkWritePermission($userPermissions = null)
	{
		return \CCrmAuthorizationHelper::CheckUpdatePermission('BUTTON', 0, $userPermissions);
	}

	/**
	 * Get path to button list page
	 * @return string
	 */
	public static function getUrl()
	{
		return Option::get('crm', 'path_to_button_list', '/crm/button/');
	}

	/**
	 * Can remove copyright
	 * @return bool
	 */
	public static function canRemoveCopyright()
	{
		if(!Loader::includeModule('bitrix24'))
		{
			return true;
		}

		if(\CBitrix24::IsDemoLicense())
		{
			return true;
		}

		return \CBitrix24::IsLicensePaid();
	}

	/**
	 * Can use multi lines.
	 * @return bool
	 */
	public static function canUseMultiLines()
	{
		if(!Loader::includeModule('bitrix24'))
		{
			return true;
		}

		if(!Loader::includeModule('imopenlines'))
		{
			return false;
		}

		return Imopenlines\Limit::getLinesLimit() != 1;
	}

	/**
	 * Event handler for openline activation/deactivation
	 * @throws \Exception
	 */
	public static function onAfterImopenlineActiveChange(\Bitrix\Main\Event $eventData): void
	{
		$lineId = (int)$eventData->getParameter('line');
		$active = $eventData->getParameter('active') === 'Y';
		$widgets = self::getWidgetsByOpenlineId($lineId);
		foreach ($widgets as $widget)
		{
			// processing affected widgets, place your methods here
			self::changeOpenlinePresenceForWidget($widget, $active);
		}
	}

	/**
	 * Event handler for openline deletion
	 * @throws \Exception
	 */
	public static function onImopenlineDelete(\Bitrix\Main\Event $eventData): void
	{
		$lineId = (int)$eventData->getParameter('line');
		$widgets = self::getWidgetsByOpenlineId($lineId);
		foreach ($widgets as $widget)
		{
			// processing affected widgets, place your methods here
			self::deleteRelatedOpenlineForWidget($widget);
		}
	}

	public static function getWidgetsByOpenlineId(int $lineId): array
	{
		$result = [];
		$widgets = Internals\ButtonTable::getList();
		if ($widgets)
		{
			foreach ($widgets as $buttonData)
			{
				$button = new Button();
				$button->loadByData($buttonData);
				if (($openLine = $button->getOpenLine()) && isset($openLine['EXTERNAL_ID']))
				{
					$widgetLineId = (int)$openLine['EXTERNAL_ID'];
					if ($lineId === $widgetLineId)
					{
						$result[] = $button;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * @throws \Exception
	 */
	private static function changeOpenlinePresenceForWidget(Button $widget, bool $active): void
	{
		if ($widget->changeOpenLineActivity($active))
		{
			self::updateScriptCache($widget->getId());
		}
	}

	/**
	 * @param Button $widget
	 * @throws \Exception
	 */
	private static function deleteRelatedOpenlineForWidget(Button $widget): void
	{
		if ($widget->deleteOpenLineItem())
		{
			self::updateScriptCache($widget->getId());
		}
	}

	/**
	 * Event handler of changing licence
	 * @return void
	 */
	public static function onBitrix24LicenseChange($licenseType)
	{
		if ($licenseType)
		{
			$isChanged = false;
			if(!self::canRemoveCopyright())
			{
				$buttonDb = Internals\ButtonTable::getList(array('select' => array('ID')));
				while($buttonData = $buttonDb->fetch())
				{
					$button = new Button($buttonData['ID']);
					$data = $button->getData();
					if ($data['SETTINGS']['COPYRIGHT_REMOVED'] == 'Y')
					{
						$data['SETTINGS']['COPYRIGHT_REMOVED'] = 'N';
						$updateResult = Internals\ButtonTable::update(
							$buttonData['ID'],
							array('SETTINGS' => $data['SETTINGS'])
						);
						$isChanged = $updateResult->isSuccess();
					}
				}

				if ($isChanged)
				{
					\CAgent::addAgent('\\Bitrix\\Crm\\SiteButton\\Manager::updateScriptCacheAgent();', "crm", "N", 60, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::getOffset()+60, "FULL"));
				}
			}
		}
	}

	/**
	 * Get plain button list.
	 *
	 * @return array
	 */
	public static function getListPlain()
	{
		$parameters = array();
		$parameters["cache"] = array("ttl" => 3600);
		return Internals\ButtonTable::getList($parameters)->fetchAll();
	}

	/**
	 * Get list form names list.
	 *
	 * @return array
	 */
	public static function getListNames()
	{
		static $result = null;
		if (!is_array($result))
		{
			$result = array();
			$list = self::getListPlain();
			foreach ($list as $item)
			{
				$result[$item['ID']] = $item['NAME'];
			}
		}

		return $result;
	}
}
