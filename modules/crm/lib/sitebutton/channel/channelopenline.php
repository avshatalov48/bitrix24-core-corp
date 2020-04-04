<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\SiteButton\Channel;

use Bitrix\Crm\SiteButton\Manager;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\ImOpenLines\Common;
use Bitrix\Imopenlines\Model\ConfigTable;
use Bitrix\ImConnector;
use Bitrix\ImOpenLines\LiveChatManager;
use Bitrix\Imopenlines\Model\LivechatTable;
use Bitrix\Main\Web\WebPacker;
use Bitrix\Crm\Tracking;

Loc::loadMessages(__FILE__);

/**
 * Class ChannelOpenLine
 * @package Bitrix\Crm\SiteButton\Channel
 */
class ChannelOpenLine implements iProvider
{
	private static $excludedConnectors = array(
		'facebookcomments',
		'yandex'
	);
	/**
	 * Return true if it can be used.
	 *
	 * @return bool
	 */
	public static function canUse()
	{
		return Loader::includeModule('imopenlines') && Loader::includeModule('imconnector');
	}

	/**
	 * Get presets.
	 *
	 * @return array
	 */
	public static function getPresets()
	{
		if (!self::canUse())
		{
			return array();
		}

		return LivechatTable::getList(array(
			'select' => array(
				'ID' => 'CONFIG_ID',
				'NAME' => 'CONFIG.LINE_NAME'
			),
			'filter' => array(
				'=CONFIG.ACTIVE' => 'Y'
			),
		))->fetchAll();
	}

	/**
	 * Get list.
	 *
	 * @return array
	 */
	public static function getList()
	{
		if (!self::canUse())
		{
			return array();
		}

		$list = ConfigTable::getList(array(
			'select' => array(
				'ID', 'NAME' => 'LINE_NAME',
				'WORKTIME_ENABLE',
				'WORKTIME_FROM', 'WORKTIME_TO', 'WORKTIME_TIMEZONE',
				'WORKTIME_HOLIDAYS', 'WORKTIME_DAYOFF'
			),
			'filter' => array(
				'=ACTIVE' => 'Y'
			),
		))->fetchAll();

		$result = array();
		foreach ($list as $line)
		{
			$connectors = self::getConnectors($line['ID']);
			if (count($connectors) > 0)
			{
				$workTime = null;
				if ($line['WORKTIME_ENABLE'] == 'Y')
				{
					$workTime = array(
						'ENABLED' => $line['WORKTIME_ENABLE'] == 'Y',
						'TIME_FROM' => (float) $line['WORKTIME_FROM'],
						'TIME_TO' => (float) $line['WORKTIME_TO'],
						'TIME_ZONE' => $line['WORKTIME_TIMEZONE'],
						'HOLIDAYS' => explode(',', $line['WORKTIME_HOLIDAYS']),
						'DAY_OFF' => explode(',', $line['WORKTIME_DAYOFF']),
					);
				}

				$result[] = array(
					'ID' => $line['ID'],
					'NAME' => $line['NAME'],
					'CONNECTORS' => $connectors,
					'WORK_TIME' => $workTime,
				);
			}
		}

		return $result;
	}

	/**
	 * Get widgets.
	 *
	 * @param string $id Channel id
	 * @param bool $removeCopyright Remove copyright
	 * @param string|null $lang Language ID
	 * @param array $config Config
	 * @return array
	 */
	public static function getWidgets($id, $removeCopyright = true, $lang = null, array $config = array())
	{
		Loc::loadMessages(__FILE__); // TODO: remove with dependence main: deeply lazy Load loc files
		Loc::loadMessages(\Bitrix\Main\Application::getDocumentRoot() . '/bitrix/modules/imconnector/lib/connector.php');

		$result = array();
		$lines = explode(',', $id);
		foreach ($lines as $lineId)
		{
			$lineConfig = isset($config[$lineId]) ? $config[$lineId] : array();
			$widgets = self::getWidgetsById($lineId, $removeCopyright, $lang, $lineConfig);
			$result = array_merge($result, $widgets);
		}

		return $result;
	}

	/**
	 * Get resources.
	 *
	 * @return WebPacker\Resource\Asset[]
	 */
	public static function getResources()
	{
		if (!self::canUse())
		{
			return [];
		}

		//condition for changes in ui 18.5.5
		if (file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/js/ui/icons/service/ui.icons.service.css') &&
			file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/js/ui/icons/service/images/'))
		{
			$iconAssetPath = '/bitrix/js/ui/icons/service/ui.icons.service.css';
			$pathToIcons = '/bitrix/js/ui/icons/service/images/';

			$cssAssetIcons = new WebPacker\Resource\CssAsset($iconAssetPath);
			$content = str_replace(
				$pathToIcons,
				WebPacker\Builder::getDefaultSiteUri() . $pathToIcons,
				$cssAssetIcons->getContent()
			);
			$cssAssetIcons->setContent($content);

			$iconsBase = '/bitrix/js/ui/icons/base/ui.icons.base.css';
			$iconsB24 = '/bitrix/js/ui/icons/b24/ui.icons.b24.css';
			$iconsDisk = '/bitrix/js/ui/icons/disk/ui.icons.disk.css';
			$cssAssetIconsBase = new WebPacker\Resource\CssAsset($iconsBase);
			$cssAssetIconsB24 = new WebPacker\Resource\CssAsset($iconsB24);
			$cssAssetIconsDisk = new WebPacker\Resource\CssAsset($iconsDisk);

			$result = [
				$cssAssetIconsBase,
				$cssAssetIcons,
				$cssAssetIconsB24,
				$cssAssetIconsDisk
			];
		}
		else
		{
			$iconAssetPath = '/bitrix/js/ui/icons/ui.icons.css';
			$pathToIcons = '/bitrix/js/ui/icons/images/service/';

			$cssAssetIcons = new WebPacker\Resource\CssAsset($iconAssetPath);
			$content = str_replace(
				$pathToIcons,
				WebPacker\Builder::getDefaultSiteUri() . $pathToIcons,
				$cssAssetIcons->getContent()
			);
			$cssAssetIcons->setContent($content);
			$result = [$cssAssetIcons];
		}

		$cssCustomConnectors = ImConnector\CustomConnectors::getStyleCss();
		if ($cssCustomConnectors)
		{
			$result[] = (new WebPacker\Resource\CssAsset())->setContent($cssCustomConnectors);
		}

		return $result;
	}

	/**
	 * Get edit path.
	 *
	 * @return array
	 */
	public static function getPathEdit()
	{
		if (!self::canUse())
		{
			return null;
		}

		return array(
			'path' => Common::getPublicFolder()."list/edit.php?ID=#ID#",
			'id' => '#ID#'
		);
	}

	/**
	 * Get add path.
	 * @return string
	 */
	public static function getPathAdd()
	{
		if (!self::canUse())
		{
			return null;
		}

		$ratingRequest = \Bitrix\Imopenlines\Limit::canUseVoteClient() ? 'Y' : 'N';

		return Common::getPublicFolder()."list/edit.php?ID=0&action-line=create&rating-request=" . $ratingRequest;
	}

	/**
	 * Get list path.
	 *
	 * @return string
	 */
	public static function getPathList()
	{
		if (!self::canUse())
		{
			return null;
		}

		return Common::getPublicFolder()."list/edit.php?ID=0";
	}

	/**
	 * Get name.
	 *
	 * @return string
	 */
	public static function getName()
	{
		return Loc::getMessage('CRM_BUTTON_MANAGER_TYPE_NAME_' . strtoupper(self::getType()));
	}

	/**
	 * Get type.
	 *
	 * @return string
	 */
	public static function getType()
	{
		return 'openline';
	}

	protected static function getWidgetsById($lineId, $removeCopyright, $lang, array $config = array())
	{
		if (!self::canUse())
		{
			return array();
		}

		$excluded = isset($config['excluded']) ? $config['excluded'] : array();

		$widgets = array();
		$sort = 400;
		$type = self::getType();
		$connectors = self::getConnectors($lineId);

		foreach ($connectors as $connector)
		{
			if (in_array($connector['id'], $excluded))
			{
				continue;
			}

			$widget = array(
				'id' => $type . '_' . $connector['code'],
				'title' => $connector['title'],
				'script' => '',
				'show' => null,
				'hide' => null,
				'tracking' => [
					'detecting' => $connector['code'] !== 'livechat',
					'channel' => [
						'code' => Tracking\Channel\Base::Imol,
						'value' => $connector['code'],
					]
				]
			);

			if ($connector['code'] == 'livechat')
			{
				$liveChatManager = new LiveChatManager($lineId);
				$widget['script'] = $liveChatManager->getWidget(
					LiveChatManager::TYPE_BUTTON,
					$lang,
					array(
						'copyright' => !$removeCopyright
					),
					true
				);

				if (!$widget['script'])
				{
					continue;
				}

				$widget['show'] = 'window.BX.LiveChat.openLiveChat();';
				$widget['hide'] = 'window.BX.LiveChat.closeLiveChat();';
				$widget['freeze'] = true;
				$widget['sort'] = 100;
				$widget['useColors'] = true;
				$widget['classList'] = array('b24-widget-button-' . $widget['id']);
			}
			else
			{
				$widget['classList'] = array(
					'ui-icon',
					'ui-icon-service-' . $connector['icon'],
					'connector-icon-45'
				);
				$widget['sort'] = $sort;
				$sort += 100;
				$widget['show'] = array(
					'url' => $connector['url']
				);
			}

			$widgets[] = $widget;
		}

		return $widgets;
	}

	protected static function getConnectors($lineId)
	{
		$nameList = ImConnector\Connector::getListConnectorReal(40);
		if (Manager::isWidgetSelectDisabled())
		{
			$connectors = [];
			$connectorList = ImConnector\Connector::getListConnectedConnector($lineId);
			$virtualId = 1;
			foreach ($connectorList as $connectorCode => $connectorName)
			{
				$connectors[$connectorCode] = [
					'id' => 'virtual:' . ($virtualId++),
					'url' => 'https://bitrix24.com/',
					'url_im' => 'https://bitrix24.com/',
					'name' => $connectorName,
					'connector_name' => $connectorName,
				];
			}
		}
		else
		{
			$connectors = ImConnector\Connector::infoConnectorsLine($lineId);
		}

		if (count($connectors) == 0)
		{
			return array();
		}

		$list = array();
		$iconCodeMap = ImConnector\Connector::getIconClassMap();
		foreach ($connectors as $code => $connector)
		{
			if (in_array($code, self::$excludedConnectors))
			{
				continue;
			}

			if (empty($connector['url']) && empty($connector['url_im']))
			{
				continue;
			}

			$id = str_replace('.', '-', $code);
			if (!empty($connector['name']))
			{
				$title = $connector['name'];
			}
			else if (isset($nameList[$code]))
			{
				$title = $nameList[$code];
			}
			else
			{
				$title = $connector['connector_name'];
			}

			$list[] = array(
				'id' => $id,
				'code' => $code,
				'icon' => $iconCodeMap[$code],
				'title' => $title,
				'name' => $connector['connector_name'],
				'desc' => $connector['name'],
				'url' => $connector['url_im'] ? $connector['url_im'] : $connector['url']
			);
		}

		return $list;
	}

	/**
	 * Get config from post.
	 *
	 * @param array $item Item
	 * @return array
	 */
	public static function getConfigFromPost(array $item = array())
	{
		$config = array();
		if(isset($item['EXTERNAL_CONFIG']) && is_array($item['EXTERNAL_CONFIG']))
		{
			$config = $item['EXTERNAL_CONFIG'];
		}

		$result = array();
		foreach ($config as $lineId => $connectors)
		{
			if (!$lineId || !is_array($connectors) || count($connectors) == 0)
			{
				continue;
			}

			$list = self::getConnectors($lineId);
			foreach ($list as $item)
			{
				if (!isset($result[$lineId]))
				{
					$result[$lineId] = array();
				}

				if (!isset($result[$lineId]['excluded']))
				{
					$result[$lineId]['excluded'] = array();
				}

				if (in_array($item['id'], $connectors))
				{
					continue;
				}

				$result[$lineId]['excluded'][] = $item['id'];
			}

		}

		return $result;
	}
}
