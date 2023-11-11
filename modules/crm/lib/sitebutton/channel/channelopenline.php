<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\SiteButton\Channel;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\WebPacker;
use Bitrix\Main\Web\Json;
use Bitrix\ImOpenLines;
use Bitrix\ImConnector;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\SiteButton\Manager;
use Bitrix\Notifications;

Loc::loadMessages(__FILE__);

/**
 * Class ChannelOpenLine
 * @package Bitrix\Crm\SiteButton\Channel
 */
class ChannelOpenLine implements iProvider
{
	private static array $excludedConnectors = array(
		ImConnector\Library::ID_FB_COMMENTS_CONNECTOR,
		'yandex'
	);

	private static array $connectorWithGeneratedUrl = [
		ImConnector\Library::ID_NOTIFICATIONS_CONNECTOR,
		ImConnector\Library::ID_EDNA_WHATSAPP_CONNECTOR,
	];

	private static array $trackingDetectingDisabled = [
		ImConnector\Library::ID_LIVE_CHAT_CONNECTOR,
		ImConnector\Library::ID_NOTIFICATIONS_CONNECTOR,
	];

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
			return [];
		}

		return Imopenlines\Model\LivechatTable::getList([
			'select' => [
				'ID' => 'CONFIG_ID',
				'NAME' => 'CONFIG.LINE_NAME'
			],
			'filter' => [
				'=CONFIG.ACTIVE' => 'Y'
			],
		])->fetchAll();
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
			return [];
		}

		$list = Imopenlines\Model\ConfigTable::getList([
			'select' => [
				'ID', 'NAME' => 'LINE_NAME',
				'WORKTIME_ENABLE',
				'WORKTIME_FROM', 'WORKTIME_TO', 'WORKTIME_TIMEZONE',
				'WORKTIME_HOLIDAYS', 'WORKTIME_DAYOFF'
			],
			'filter' => [
				'=ACTIVE' => 'Y'
			],
		])->fetchAll();

		$result = [];
		foreach ($list as $line)
		{
			$connectors = self::getConnectors($line['ID']);
			if (count($connectors) > 0)
			{
				$workTime = null;
				if ($line['WORKTIME_ENABLE'] == 'Y')
				{
					$workTime = [
						'ENABLED' => $line['WORKTIME_ENABLE'] == 'Y',
						'TIME_FROM' => (float) $line['WORKTIME_FROM'],
						'TIME_TO' => (float) $line['WORKTIME_TO'],
						'TIME_ZONE' => $line['WORKTIME_TIMEZONE'],
						'HOLIDAYS' => explode(',', $line['WORKTIME_HOLIDAYS']),
						'DAY_OFF' => explode(',', $line['WORKTIME_DAYOFF']),
					];
				}

				$result[] = [
					'ID' => $line['ID'],
					'NAME' => $line['NAME'],
					'CONNECTORS' => $connectors,
					'WORK_TIME' => $workTime,
				];
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
	public static function getWidgets($id, $removeCopyright = true, $lang = null, array $config = [])
	{
		Loc::loadMessages(__FILE__); // TODO: remove with dependence main: deeply lazy Load loc files
		Loc::loadMessages(\Bitrix\Main\Application::getDocumentRoot() . '/bitrix/modules/imconnector/lib/connector.php');

		$result = [];
		$lines = explode(',', $id);
		foreach ($lines as $lineId)
		{
			$lineConfig = $config[$lineId] ?? [];
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
		if (
			file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/js/ui/icons/service/ui.icons.service.css')
			&& file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/js/ui/icons/service/images/')
		)
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
			'path' => ImOpenLines\Common::getContactCenterPublicFolder() . 'lines_edit/?ID=#ID#',
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

		$ratingRequest = Imopenlines\Limit::canUseVoteClient() ? 'Y' : 'N';

		return ImOpenLines\Common::getContactCenterPublicFolder() . 'lines_edit/?ID=0&action-line=create&rating-request=' . $ratingRequest;
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

		return ImOpenLines\Common::getContactCenterPublicFolder() . 'lines_edit/?ID=0';
	}

	/**
	 * Get name.
	 *
	 * @return string
	 */
	public static function getName()
	{
		return Loc::getMessage('CRM_BUTTON_MANAGER_TYPE_NAME_'.mb_strtoupper(self::getType()));
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

	protected static function getWidgetsById($lineId, $removeCopyright, $lang, array $config = []): array
	{
		if (!self::canUse())
		{
			return [];
		}

		$excluded = $config['excluded'] ?? [];

		$widgets = [];
		$sort = 400;
		$type = self::getType();
		$connectors = self::getConnectors($lineId);
		foreach ($connectors as $connector)
		{
			if (in_array($connector['id'], $excluded))
			{
				continue;
			}

			$widget = [
				'id' => $type . '_' . $connector['code'],
				'title' => $connector['title'],
				'script' => '',
				'show' => null,
				'hide' => null,
				'tracking' => [
					'detecting' => !in_array($connector['code'], self::$trackingDetectingDisabled, true),
					'channel' => [
						'code' => Tracking\Channel\Base::Imol,
						'value' => $connector['code'],
					]
				]
			];

			if ($connector['code'] === ImConnector\Library::ID_LIVE_CHAT_CONNECTOR)
			{
				$widgetParams = self::getLiveChatWidget($lineId, $removeCopyright, $lang);
				if (!$widgetParams)
				{
					continue;
				}
				$widget = array_merge($widget, $widgetParams);
				$widget['classList'] = ['b24-widget-button-' . $widget['id']];
			}
			else
			{
				$widget['classList'] = [
					'ui-icon',
					'ui-icon-service-' . $connector['icon'],
					'connector-icon-45'
				];
				$widget['sort'] = $sort;
				$sort += 100;
				$widget['show'] = [
					'url' => $connector['url']
				];
			}

			if ($connector['code'] === ImConnector\Library::ID_NOTIFICATIONS_CONNECTOR)
			{
				$widgetParams = self::getNotificationsWidget($lang);
				if (!$widgetParams)
				{
					continue;
				}
				$widget = array_merge($widget, $widgetParams);
			}
			elseif ($connector['code'] === ImConnector\Library::ID_EDNA_WHATSAPP_CONNECTOR)
			{
				$widgetParams = self::getWhatsAppEdnaWidget((int)$lineId, $lang);
				if (!$widgetParams)
				{
					continue;
				}
				$widget = array_merge($widget, $widgetParams);
			}

			$widgets[] = $widget;
		}

		return $widgets;
	}

	/**
	 * @param int $lineId
	 * @param bool $removeCopyright
	 * @param string $lang
	 * @return array|null
	 */
	private static function getLiveChatWidget($lineId, $removeCopyright, $lang): ?array
	{
		$widget = [];
		$liveChatManager = new ImOpenLines\LiveChatManager($lineId);
		$widget['script'] = $liveChatManager->getWidget(
			ImOpenLines\LiveChatManager::TYPE_BUTTON,
			$lang,
			['copyright' => !$removeCopyright],
			true
		);

		if (!$widget['script'])
		{
			return null;
		}

		$widget['show'] = 'window.BX.LiveChat.openLiveChat();';
		$widget['hide'] = 'window.BX.LiveChat.closeLiveChat();';
		$widget['freeze'] = true;
		$widget['sort'] = 100;
		$widget['useColors'] = true;

		return $widget;
	}

	/**
	 * Provides localized title and url for notifications connector.
	 * @param int $lineId
	 * @param bool $removeCopyright
	 * @param string $lang
	 * @return array|null
	 */
	private static function getNotificationsWidget($lang): ?array
	{
		$widget = [];
		if (!Loader::includeModule('notifications'))
		{
			return null;
		}
		if (!Notifications\Settings::isScenarioEnabled(Notifications\Settings::SCENARIO_VIRTUAL_WHATSAPP))
		{
			return null;
		}

		$portalCode = Notifications\Alias::getCodeForScenario(Notifications\Settings::SCENARIO_VIRTUAL_WHATSAPP);
		$url = ImConnector\Tools\Connectors\Notifications::getVirtualWhatsappLink($portalCode, $lang);
		$onclick = ImConnector\Tools\Connectors\Notifications::getVirtualWhatsappOnClick($url);
		$widgetParams = [
			'url' => $url,
			'onclick' => $onclick,
			'messages' => ImConnector\Tools\Connectors\Notifications::getWidgetLocalization($lang),
			'disclaimerUrl' => ImConnector\Tools\Connectors\Notifications::getWidgetDisclaimerUrl($lang),
		];
		$widgetParamsEncoded = Json::encode($widgetParams);
		$widget['title'] = Loc::getMessage("CRM_BUTTON_MANAGER_OPENLINE_VIRTUAL_WHATSAPP_TITLE");
		$widget['script'] = ImConnector\Tools\Connectors\Notifications::getWidgetScript();
		$widget['show'] = [
			'js' => [
				'desktop' =>'BX.NotificationsWidgetLoader.init('.$widgetParamsEncoded.').then(function(){window.BX.NotificationsWidget.Instance.show();})',
			],
			'url' => [
				'mobile' => $url,
				'force' => true,
			],
		];
		$widget['hide'] = 'window.BX.NotificationsWidget.Instance.close();';
		$widget['freeze'] = true;
		$widget['classList'] = [
			'ui-icon',
			'ui-icon-service-' . ImConnector\Connector::getIconByConnector('notifications_virtual_wa'),
			'connector-icon-45'
		];

		return $widget;
	}

	/**
	 * Provides localized title and url for messageservice connector.
	 * @param int $lineId
	 * @param string $lang
	 * @return array|null
	 */
	private static function getWhatsAppEdnaWidget(int $lineId, $lang): ?array
	{
		$widget = [];
		if (!Loader::includeModule('messageservice'))
		{
			return null;
		}
		if (!ImConnector\Tools\Connectors\Messageservice::isEnabled())
		{
			return null;
		}

		$url = ImConnector\Tools\Connectors\Messageservice::getWhatsappLink($lineId, $lang);
		$onclick = ImConnector\Tools\Connectors\Messageservice::getWhatsappOnClick($url);
		$widgetParams = [
			'url' => $url,
			'onclick' => $onclick,
			'messages' => ImConnector\Tools\Connectors\Messageservice::getWidgetLocalization($lang),
			'disclaimerUrl' => '',
		];
		$widgetParamsEncoded = Json::encode($widgetParams);
		$widget['title'] = Loc::getMessage("CRM_BUTTON_MANAGER_OPENLINE_VIRTUAL_WHATSAPP_TITLE");
		$widget['script'] = ImConnector\Tools\Connectors\Messageservice::getWidgetScript();
		$widget['show'] = [
			'js' => [
				'desktop' =>'BX.NotificationsWidgetLoader.init('.$widgetParamsEncoded.').then(function(){window.BX.NotificationsWidget.Instance.show();})',
			],
			'url' => [
				'mobile' => $url,
				'force' => true,
			],
		];
		$widget['hide'] = 'window.BX.NotificationsWidget.Instance.close();';
		$widget['freeze'] = true;
		$widget['classList'] = [
			'ui-icon',
			'ui-icon-service-' . ImConnector\Connector::getIconByConnector('notifications_virtual_wa'),
			'connector-icon-45'
		];

		return $widget;
	}

	protected static function getConnectors($lineId): array
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
			return [];
		}

		$list = [];
		$iconCodeMap = ImConnector\Connector::getIconClassMap();
		foreach ($connectors as $code => $connector)
		{
			if (in_array($code, self::$excludedConnectors))
			{
				continue;
			}

			if (
				empty($connector['url'])
				&& empty($connector['url_im'])
				&& !in_array($code, self::$connectorWithGeneratedUrl)
			)
			{
				continue;
			}

			$id = str_replace('.', '-', $code);
			if (!empty($connector['name']))
			{
				$title = $connector['name'];
			}
			elseif (isset($nameList[$code]))
			{
				$title = $nameList[$code];
			}
			else
			{
				$title = $connector['connector_name'];
			}

			$list[] = [
				'id' => $id,
				'code' => $code,
				'icon' => $connector['icon'] ?? $iconCodeMap[$code],
				'title' => $title,
				'name' => $connector['connector_name'],
				'desc' => $connector['desc'] ?? $connector['name'],
				'url' => $connector['url_im'] ?? $connector['url']
			];
		}

		return $list;
	}

	/**
	 * Get config from post.
	 *
	 * @param array $item Item
	 * @return array
	 */
	public static function getConfigFromPost(array $item = array()): array
	{
		$externalConfigField = [];
		if(isset($item['EXTERNAL_CONFIG']) && is_array($item['EXTERNAL_CONFIG']))
		{
			$externalConfigField = $item['EXTERNAL_CONFIG'];
		}

		$externalIdField = is_string($item['EXTERNAL_ID']) ? $item['EXTERNAL_ID'] : '';
		$externalIds = explode(',', $externalIdField);

		$result = [];
		foreach ($externalIds as $openlineId)
		{
			$openlineId = (int)$openlineId;
			$config = $externalConfigField[$openlineId] ?? [];

			$connectors = self::getConnectors($openlineId);
			foreach ($connectors as $connector)
			{
				if (!isset($result[$openlineId]))
				{
					$result[$openlineId] = [];
				}

				if (!isset($result[$openlineId]['excluded']))
				{
					$result[$openlineId]['excluded'] = [];
				}

				if (in_array($connector['id'], $config, true))
				{
					continue;
				}

				$result[$openlineId]['excluded'][] = $connector['id'];
			}
		}

		return $result;
	}
}
