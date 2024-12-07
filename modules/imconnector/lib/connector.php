<?php
namespace Bitrix\ImConnector;

use Bitrix\ImConnector\Tools\Connectors\Messageservice;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Context;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\DI\ServiceLocator;

use Bitrix\ImOpenLines\LiveChatManager;
use Bitrix\ImConnector\Connectors;

Loc::loadMessages(__FILE__);
Library::loadMessages();

/**
 * Auxiliary class for work with connectors.
 * @package Bitrix\ImConnector
 */
class Connector
{
	private const META_RU_SUFFIX = '_RESTRICTION_RU';

	/** @var Connectors\Base[] */
	private static array $connectors = [];

	/**
	 * @param string $connectorId
	 * @return Connectors\Base|Connectors\Facebook|Connectors\FacebookComments|Connectors\FbInstagram|Connectors\IMessage|Connectors\Olx|Connectors\Viber|Connectors\Network|Connectors\TelegramBot
	 */
	public static function initConnectorHandler(string $connectorId = ''): Connectors\Base
	{
		if (
			!isset(self::$connectors[$connectorId])
			|| !(self::$connectors[$connectorId] instanceof Connectors\Base)
		)
		{
			$class = self::getConnectorHandlerClass($connectorId);
			self::$connectors[$connectorId] = new $class($connectorId);
		}

		return self::$connectors[$connectorId];
	}

	/**
	 * Detects connector's class name.
	 *
	 * @param string $connectorId Mnemonic connector name.
	 * @return string|Connectors\Base
	 */
	public static function getConnectorHandlerClass(string $connectorId): string
	{
		static $handlers = [];
		$classDefault = '\\Bitrix\\ImConnector\\Connectors\\Base';
		if (!isset($handlers[$connectorId]) && !empty($connectorId))
		{
			$class = $classDefault;
			$realIdConnector = self::getConnectorRealId($connectorId);
			$className = '\\Bitrix\\ImConnector\\Connectors\\' . $realIdConnector;
			if (
				class_exists($className, true)
				&& is_subclass_of($className, $classDefault)
			)
			{
				$class = $className;
			}
			$handlers[$connectorId] = $class;
		}

		return $handlers[$connectorId] ?? $classDefault;
	}

	/**
	 * @param string $connectorId Mnemonic connector name.
	 * @param Connectors\Base
	 */
	public static function setConnectorHandler(string $connectorId, Connectors\Base $connector): void
	{
		self::$connectors[$connectorId] = $connector;
	}

	//region Connector and Portal region

	/**
	 * @param string $connectorId Mnemonic connector name.
	 * @param string $region Portal region.
	 * @return bool
	 */
	public static function isAllowedConnectorInRegion(string $connectorId, string $region): bool
	{
		$result = true;

		if (!empty($region) && !empty(Library::CONNECTOR_PER_REGION_LIMITATION[$connectorId]))
		{
			$allow = Library::CONNECTOR_PER_REGION_LIMITATION[$connectorId]['allow'] ?? null;
			$deny = Library::CONNECTOR_PER_REGION_LIMITATION[$connectorId]['deny'] ?? null;

			if (!empty($deny) && in_array($region, $deny))
			{
				$result = false;
			}
			elseif (!empty($allow) && !in_array($region, $allow))
			{
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * @param array $connectors
	 * @return array
	 */
	public static function filterConnectorsByPortalRegion($connectors = [], $portalRegion = ''): array
	{
		$portalRegion = $portalRegion ?: self::getPortalRegion();
		foreach ($connectors as $connector => $name)
		{
			if (!self::isAllowedConnectorInRegion($connector, $portalRegion))
			{
				unset($connectors[$connector]);
			}
		}

		return $connectors;
	}

	//endregion

	/**
	 * @return array
	 */
	protected static function getListConnectorBase(): array
	{
		$serviceLocator = ServiceLocator::getInstance();

		$connectors = [];
		$connectors[Library::ID_LIVE_CHAT_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_LIVECHAT');
		$connectors[Library::ID_WHATSAPPBYTWILIO_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_WHATSAPPBYTWILIO');
		$connectors[Library::ID_AVITO_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_AVITO');
		$connectors[Library::ID_VIBER_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_VIBER_BOT');
		$connectors[Library::ID_TELEGRAMBOT_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_TELEGRAM_BOT');
		$connectors[Library::ID_IMESSAGE_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_IMESSAGE_NEW');
		if ($serviceLocator->has('ImConnector.toolsWeChat'))
		{
			/** @var \Bitrix\ImConnector\Tools\Connectors\WeChat $toolsWeChat */
			$toolsWeChat = $serviceLocator->get('ImConnector.toolsWeChat');
			if ($toolsWeChat->isEnabled())
			{
				$connectors[Library::ID_WECHAT_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_WECHAT');
			}
		}
		$connectors[Library::ID_VKGROUP_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_VK_GROUP');
		$connectors[Library::ID_OK_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_OK');
		$connectors[Library::ID_OLX_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_OLX');

		if (\Bitrix\Main\Application::getInstance()->getLicense()->getRegion() === 'ru')
		{
			$connectors[Library::ID_FB_MESSAGES_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_FACEBOOK_PAGE' . self::META_RU_SUFFIX);
			$connectors[Library::ID_FB_COMMENTS_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_FACEBOOK_COMMENTS_PAGE' . self::META_RU_SUFFIX);
			$connectors[Library::ID_FBINSTAGRAMDIRECT_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_FBINSTAGRAMDIRECT' . self::META_RU_SUFFIX);
			$connectors[Library::ID_FBINSTAGRAM_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_FBINSTAGRAM' . self::META_RU_SUFFIX);
		}
		else
		{
			$connectors[Library::ID_FB_MESSAGES_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_FACEBOOK_PAGE');
			$connectors[Library::ID_FB_COMMENTS_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_FACEBOOK_COMMENTS_PAGE');
			$connectors[Library::ID_FBINSTAGRAMDIRECT_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_FBINSTAGRAMDIRECT');
			$connectors[Library::ID_FBINSTAGRAM_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_FBINSTAGRAM');
		}

		$connectors[Library::ID_NETWORK_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_NETWORK_MSGVER_1');
		if ($serviceLocator->has('ImConnector.toolsNotifications'))
		{
			/** @var \Bitrix\ImConnector\Tools\Connectors\Notifications $toolsNotifications */
			$toolsNotifications = $serviceLocator->get('ImConnector.toolsNotifications');
			if($toolsNotifications->isEnabled())
			{
				$connectors[Library::ID_NOTIFICATIONS_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_NOTIFICATIONS_2');
			}
		}
		if ($serviceLocator->has('ImConnector.toolsMessageservice'))
		{
			/** @var Messageservice $toolsMessageservice */
			$toolsMessageservice = $serviceLocator->get('ImConnector.toolsMessageservice');
			if ($toolsMessageservice->isEnabled())
			{
				if (\Bitrix\MessageService\Providers\Edna\RegionHelper::isInternational())
				{
					$ednaPostfix = '_IO';
				}
				else
				{
					$ednaPostfix = '';
				}

				$connectors[Library::ID_EDNA_WHATSAPP_CONNECTOR] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_WHATSAPPBYEDNA' . $ednaPostfix);
			}
		}

		return $connectors;
	}

	/**
	 * @return array
	 */
	protected static function getListCustomConnectorBase(): array
	{
		return CustomConnectors::getListConnector();
	}

	/**
	 * @param bool|integer $reduced To shorten the channel names.
	 * @param array $connectors
	 * @return array
	 */
	protected static function getListReducedConnectorBase($reduced = false, array $connectors = []): array
	{
		if (!empty($reduced))
		{
			if ($reduced > 5)
			{
				$number = $reduced;
			}
			else
			{
				$number = 30;
			}

			foreach ($connectors as $cell=>$connector)
			{
				if (mb_strlen($connector) > $number)
				{
					$connectors[$cell] = mb_substr($connector, 0, ($number - 3)).'...';
				}
			}
		}

		return $connectors;
	}

	/**
	 * List of connectors, available on the client. connector id => connector Name.
	 * @param bool|integer $reduced To shorten the channel names.
	 * @param bool $customConnectorsEnable Return custom connectors
	 *
	 * @return array
	 */
	public static function getListConnector($reduced = false, bool $customConnectorsEnable = true): array
	{
		$connectors = self::getListConnectorBase();
		$customConnectors = [];

		if ($customConnectorsEnable)
		{
			$customConnectors = self::getListCustomConnectorBase();
		}

		$connectors = array_merge($connectors, $customConnectors);

		$connectors = self::filterConnectorsByPortalRegion($connectors);

		return self::getListReducedConnectorBase($reduced, $connectors);
	}

	/**
	 * Real list of connectors, available on the client. connector id => connector Name.
	 * @param bool|integer $reduced To shorten the channel names.
	 * @param bool $customConnectorsEnable Return custom connectors
	 *
	 * @return array
	 */
	public static function getListConnectorReal($reduced = false, bool $customConnectorsEnable = true): array
	{
		$connectors = self::getListConnectorBase();
		$customConnectors = [];

		if ($customConnectorsEnable)
		{
			$customConnectors = self::getListCustomConnectorBase();
		}

		$connectors = array_merge($connectors, $customConnectors);

		$connectors = self::filterConnectorsByPortalRegion($connectors);

		return self::getListReducedConnectorBase($reduced, $connectors);
	}

	/**
	 * @param bool $customConnectors
	 * @return string[]
	 */
	public static function getListConnectorActive(bool $customConnectors = true): array
	{
		$connectors = mb_strtolower(Option::get(Library::MODULE_ID, 'list_connector'));
		$connectors = explode(',', $connectors);

		if ($customConnectors)
		{
			$connectors = array_merge(CustomConnectors::getListConnectorId(), $connectors);
		}

		return $connectors;
	}


	/**
	 * @return array
	 */
	public static function getListConnectorShowDeliveryStatus(): array
	{
		return array_keys(static::getListConnector());
	}

	/**
	 * A list of matching id of the connector component.
	 *
	 * @return array
	 */
	public static function getListComponentConnector(): array
	{
		$components = [];
		$components[Library::ID_LIVE_CHAT_CONNECTOR] = 'bitrix:imconnector.livechat';
		$components[Library::ID_WHATSAPPBYTWILIO_CONNECTOR] = 'bitrix:imconnector.whatsappbytwilio';
		$components[Library::ID_AVITO_CONNECTOR] = 'bitrix:imconnector.avito';
		$components[Library::ID_VIBER_CONNECTOR] = 'bitrix:imconnector.viber';
		$components[Library::ID_TELEGRAMBOT_CONNECTOR] = 'bitrix:imconnector.telegrambot';
		$components[Library::ID_WECHAT_CONNECTOR] = 'bitrix:imconnector.wechat';
		$components[Library::ID_IMESSAGE_CONNECTOR] = 'bitrix:imconnector.imessage';
		$components[Library::ID_VKGROUP_CONNECTOR] = 'bitrix:imconnector.vkgroup';
		$components[Library::ID_OK_CONNECTOR] = 'bitrix:imconnector.ok';
		$components[Library::ID_OLX_CONNECTOR] = 'bitrix:imconnector.olx';
		$components[Library::ID_FB_MESSAGES_CONNECTOR] = 'bitrix:imconnector.facebook';
		$components[Library::ID_FB_COMMENTS_CONNECTOR] = 'bitrix:imconnector.facebookcomments';
		$components[Library::ID_FBINSTAGRAMDIRECT_CONNECTOR] = 'bitrix:imconnector.fbinstagramdirect';
		$components[Library::ID_FBINSTAGRAM_CONNECTOR] = 'bitrix:imconnector.fbinstagram';
		$components[Library::ID_NETWORK_CONNECTOR] = 'bitrix:imconnector.network';
		$components[Library::ID_NOTIFICATIONS_CONNECTOR] = 'bitrix:imconnector.notifications';
		$components[Library::ID_EDNA_WHATSAPP_CONNECTOR] = 'bitrix:imconnector.whatsappbyedna';

		$customComponents = CustomConnectors::getListComponentConnector();
		if (!empty($customComponents))
		{
			$components = array_merge($customComponents, $components);
		}

		return self::filterConnectorsByPortalRegion($components);
	}

	/**
	 * List of connectors where you can delete other people's posts
	 *
	 * @return array
	 */
	public static function getListConnectorDelExternalMessages(): array
	{
		$listConnectorDelExternalMessages = Library::$listConnectorDelExternalMessages;

		$customConnectorDelExternalMessages = CustomConnectors::getListConnectorDelExternalMessages();
		if (!empty($customConnectorDelExternalMessages))
		{
			$listConnectorDelExternalMessages = array_merge($customConnectorDelExternalMessages, $listConnectorDelExternalMessages);
		}

		return $listConnectorDelExternalMessages;
	}

	/**
	 * List of connectors where you can edit your posts
	 *
	 * @return array
	 */
	public static function getListConnectorEditInternalMessages(): array
	{
		$listConnectorEditInternalMessages = Library::$listConnectorEditInternalMessages;

		$customConnectorEditInternalMessages = CustomConnectors::getListConnectorEditInternalMessages();
		if (!empty($customConnectorEditInternalMessages))
		{
			$listConnectorEditInternalMessages = array_merge($customConnectorEditInternalMessages, $listConnectorEditInternalMessages);
		}

		return $listConnectorEditInternalMessages;
	}

	/**
	 * List of connectors where you can delete your posts
	 *
	 * @return array
	 */
	public static function getListConnectorDelInternalMessages(): array
	{
		$listConnectorDelInternalMessages = Library::$listConnectorDelInternalMessages;

		$customConnectorDelInternalMessages = CustomConnectors::getListConnectorEditInternalMessages();
		if (!empty($customConnectorDelInternalMessages))
		{
			$listConnectorDelInternalMessages = array_unique(array_merge($customConnectorDelInternalMessages, $listConnectorDelInternalMessages));
		}

		return $listConnectorDelInternalMessages;
	}

	/**
	 * A list of connectors, where it is impossible to send a newsletter
	 *
	 * @return array
	 */
	public static function getListConnectorNotNewsletter(): array
	{
		$listNotNewsletterChats = Library::$listNotNewsletterChats;

		$customNotNewsletterChat = CustomConnectors::getListConnectorNotNewsletter();
		if (!empty($customNotNewsletterChat))
		{
			$listNotNewsletterChats = array_unique(array_merge($customNotNewsletterChat, $listNotNewsletterChats));
		}

		return $listNotNewsletterChats;
	}

	/**
	 * List of connectors where you can send newsletter
	 *
	 * @return array
	 */
	public static function getListConnectorNewsletter(): array
	{
		return array_diff(array_keys(static::getListConnector()), self::getListConnectorNotNewsletter());
	}

	/**
	 * Do I need to send system messages?
	 *
	 * @param string $connectorId ID connector
	 * @return bool
	 */
	public static function isNeedSystemMessages(string $connectorId): bool
	{
		$listNotNeedSystemMessages = Library::$listNotNeedSystemMessages;

		$customNotNeedSystemMessages = CustomConnectors::getListNotNeedSystemMessages();
		if (!empty($customNotNeedSystemMessages))
		{
			$listNotNeedSystemMessages = array_unique(array_merge($customNotNeedSystemMessages, $listNotNeedSystemMessages));
		}

		return !in_array($connectorId, $listNotNeedSystemMessages, true);
	}

	/**
	 * Whether to send a signature?
	 *
	 * @param string $connectorId ID connector
	 * @return bool
	 */
	public static function isNeedSignature(string $connectorId): bool
	{
		$listNotNeedSignature = Library::$listNotNeedSignature;

		$customNotNeedSignature = CustomConnectors::getListNotNeedSignature();
		if (!empty($customNotNeedSignature))
		{
			$listNotNeedSignature = array_unique(array_merge($customNotNeedSignature, $listNotNeedSignature));
		}

		return !in_array($connectorId, $listNotNeedSignature);
	}

	/**
	 * This chat group?
	 *
	 * @param string $connectorId ID connector
	 * @return bool
	 */
	public static function isChatGroup(string $connectorId): bool
	{
		$listGroupChats = Library::$listGroupChats;

		$customGroupChats = CustomConnectors::getListChatGroup();
		if (!empty($customGroupChats))
		{
			$listGroupChats = array_unique(array_merge($customGroupChats, $listGroupChats));
		}

		return in_array($connectorId, $listGroupChats);
	}

	/**
	 * Check that we can use message tracker on this chat messages
	 *
	 * @param string $connectorId
	 *
	 * @return bool
	 */
	public static function isTrackedChat(string $connectorId): bool
	{
		$listSingleThreadGroupChats = Library::$listSingleThreadGroupChats;
		$isChatGroup = self::isChatGroup($connectorId);

		return !$isChatGroup || $isChatGroup && in_array($connectorId, $listSingleThreadGroupChats);
	}

	/**
	 * This chat for newsletter?
	 *
	 * @param string $connectorId ID connector
	 * @return bool
	 */
	public static function isChatNewsletter(string $connectorId): bool
	{
		$listConnectorNewsletter = self::getListConnectorNewsletter();

		return in_array($connectorId, $listConnectorNewsletter);
	}

	/**
	 * The list are available also connectors, active on the client.
	 * The connector has to be available on the client, it has to be included in settings and be supported on the server.
	 * @param bool|integer $reduced To shorten the channel names.
	 *
	 * @return array.
	 */
	public static function getListActiveConnector($reduced = false): array
	{
		$result = [];
		foreach (self::getListConnector($reduced) as $id => $value)
		{
			if (self::isConnector($id))
			{
				$result[$id] = $value;
			}
		}

		return $result;
	}

	/**
	 * The list are available also real connectors, active on the client.
	 * The connector has to be available on the client, it has to be included in settings and be supported on the server.
	 * @param bool|integer $reduced To shorten the channel names.
	 *
	 * @return array.
	 */
	public static function getListActiveConnectorReal($reduced = false)
	{
		$result = [];
		foreach (self::getListConnectorReal($reduced) as $id => $value)
		{
			if (self::isConnector($id))
			{
				$result[$id] = $value;
			}
		}

		return $result;
	}

	/**
	 * Returns a list of connectors, ready for sharing.
	 *
	 * @param int $lineId ID Open Line.
	 * @return array
	 */
	public static function getListConnectedConnector($lineId)
	{
		$result = [];
		$listActiveConnector = self::getListActiveConnector();
		Status::getInstanceAllConnector($lineId);

		foreach ($listActiveConnector as $connector => $value)
		{
			if (Status::getInstance($connector, (int)$lineId)->isStatus())
			{
				$result[$connector] = $value;
			}
		}

		return $result;
	}

	/**
	 * List of connectors displayed in the menu.
	 *
	 * @param bool|integer $reduced To shorten the channel names.
	 * @return array
	 */
	public static function getListConnectorMenu($reduced = false)
	{
		$result = [];
		foreach (self::getListConnectorReal($reduced) as $id => $value)
		{
			if (self::isConnector($id))
			{
				$result[$id]['name'] = $value;

				if ($reduced)
				{
					$result[$id]['short_name'] = self::getNameConnector($value, $reduced);
				}
			}
		}

		return $result;
	}

	/**
	 * Returns a list of real connectors, ready for sharing.
	 *
	 * @param int $lineId ID Open Line.
	 * @return array
	 */
	public static function getListConnectedConnectorReal($lineId)
	{
		$result = [];
		$listActiveConnector = self::getListActiveConnectorReal();
		Status::getInstanceAllConnector($lineId);

		foreach ($listActiveConnector as $connector => $value)
		{
			if (Status::getInstance($connector, (int)$lineId)->isStatus())
			{
				$result[$connector] = $value;
			}
		}

		return $result;
	}

	/**
	 * Returns a connector name.
	 *
	 * @param string $id ID connector.
	 * @param bool|integer $reduced To shorten the channel names.
	 * @return bool|mixed
	 */
	public static function getNameConnector($id, $reduced = false)
	{
		$listConnector = self::getListConnector($reduced);

		if (isset($listConnector[$id]))
		{
			return $listConnector[$id];
		}

		return false;
	}

	/**
	 * Returns a real connector name.
	 *
	 * @param string $id ID connector.
	 * @param bool|integer $reduced To shorten the channel names.
	 * @return bool|mixed
	 */
	public static function getNameConnectorReal($id, $reduced = false)
	{
		$listConnector = self::getListConnectorReal($reduced);

		if (isset($listConnector[$id]))
		{
			return $listConnector[$id];
		}

		return false;
	}

	/**
	 * Returns a real connector id.
	 *
	 * @param string $id ID connector.
	 * @return string $id ID connector real.
	 */
	public static function getConnectorRealId($id)
	{
		$id = mb_strtolower($id);

		$positionSeparator = mb_strpos($id, '.');

		if ($positionSeparator != false)
		{
			$id = mb_substr($id, 0, $positionSeparator);
		}

		return $id;
	}

	/**
	 * Check of the connector. That it is included in settings is checked and supported by the server.
	 *
	 * @param string $connectorId ID connector.
	 * @return bool
	 */
	public static function isConnector(string $connectorId): bool
	{
		$connectorId = self::getConnectorRealId($connectorId);
		$connectors = self::getListConnectorActive();

		return in_array($connectorId, $connectors);
	}

	/**
	 * Returns the domain by default of the current client.
	 *
	 * @return string
	 */
	public static function getDomainDefault(): string
	{
		$uriOption = Option::getRealValue(Library::MODULE_ID, 'uri_client', '');

		if (!empty($uriOption))
		{
			$uri = $uriOption;
		}
		elseif (defined('BX24_HOST_NAME') && !empty(BX24_HOST_NAME))
		{
			$uri = (Context::getCurrent()->getRequest()->isHttps() ? 'https://' :  'http://') . BX24_HOST_NAME;
		}
		else
		{
			$uri = Library::getCurrentServerUrl();
		}

		return $uri;
	}

	/**
	 * Returns information about all connected connectors specific open line.
	 *
	 * @param int $lineId
	 *
	 * @return array<string, array>
	 */
	public static function infoConnectorsLine($lineId)
	{
		$result = [];

		$info = InfoConnectors::infoConnectorsLine($lineId);

		if (!empty($info['DATA']))
		{
			$result = Json::decode($info['DATA']) ?? [];

			$expiresTime =  new DateTime($info['EXPIRES']);
			if ($expiresTime->getTimestamp() < time())
			{
				InfoConnectors::addSingleLineUpdateAgent($info['LINE_ID'], Library::LOCAL_AGENT_EXEC_INTERVAL);
			}
		}
		else
		{
			$infoConnectors = InfoConnectors::refreshInfoConnectors($lineId);
			if ($infoConnectors->isSuccess())
			{
				$result = $infoConnectors->getResult() ?? [];
			}
		}

		return $result;
	}

	/**
	 * Generate url to redirect into messenger app.
	 *
	 * @param int $lineId
	 * @param string $connectorId
	 * @param array|string|null $additional
	 * @return array{web: string, mob: string}
	 */
	public static function getImMessengerUrl(int $lineId, string $connectorId, $additional = null): array
	{
		$url = [];
		$connectorClass = self::getConnectorHandlerClass($connectorId);
		if (is_subclass_of($connectorClass, Connectors\MessengerUrl::class))
		{
			/** @var Connectors\MessengerUrl $connector */
			$connector = self::initConnectorHandler($connectorId);
			$url = $connector->getMessengerUrl($lineId, $additional);
		}

		return $url;
	}

	/**
	 * Returns information about all connected connectors specific open line from connector server.
	 *
	 * @param $lineId
	 *
	 * @return array
	 */
	public static function getOutputInfoConnectorsLine($lineId)
	{
		$result = [];
		$rawInfo = Output::infoConnectorsLine($lineId);
		$infoConnectors = $rawInfo->getData();

		if (!empty($infoConnectors))
		{
			$connectors = self::getListActiveConnector();

			foreach ($connectors as $connectorId => $value)
			{
				if (!empty($infoConnectors[$connectorId]))
				{
					$result[$connectorId] = $infoConnectors[$connectorId];
					if (empty($result[$connectorId]['name']))
					{
						$result[$connectorId]['name'] = $value;
					}

					$result[$connectorId]['connector_name'] = $value;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns the ID of the cache settings of the connector.
	 *
	 * @param string $line ID open line.
	 * @param string $connector ID connector.
	 * @return string ID for the cache.
	 */
	public static function getCacheIdConnector($line, $connector)
	{
		return $connector . '|' . $line;
	}

	/**
	 * Resets the cache settings connectors open lines.
	 *
	 * @param string $line ID open line.
	 * @param string $cacheId ID for the cache.
	 */
	public static function cleanCacheConnector($line, $cacheId)
	{
		$cache = Cache::createInstance();
		$cache->clean($cacheId, Library::CACHE_DIR_COMPONENT);
		$cache->clean($line, Library::CACHE_DIR_INFO_CONNECTORS_LINE);
	}

	/**
	 * Full resets the cache settings connectors open lines.
	 */
	public static function cleanFullCacheConnector()
	{
		$allConnector = Status::getInstanceAll();

		foreach ($allConnector as $connector => $item)
		{
			foreach ($item as $line => $status)
			{
				self::cleanCacheConnector($line, self::getCacheIdConnector($line, $connector));
			}
		}
	}

	/**
	 * Return icon name for all connectors
	 *
	 * @return array
	 */
	public static function getIconClassMap()
	{
		$result = self::getConnectorIconMap();
		$customConnectorsList = CustomConnectors::getListConnectorId();

		foreach ($customConnectorsList as $customConnectorId)
		{
			$result[$customConnectorId] = $customConnectorId;
		}

		return $result;
	}

	/**
	 * Return map 'connector id' - 'icon name' for UI-lib icon classes
	 * Not an actual connector list - it's just a list of various names of actual connectors
	 *
	 * @return array
	 */
	public static function getConnectorIconMap(): array
	{
		return [
			'livechat' => 'livechat',
			'viber' => 'viber',
			'telegrambot' => 'telegram',
			'telegram' => 'telegram',
			'imessage' => 'imessage', //apple
			'vkgroup' => 'vk',
			'vkgrouporder' => 'vk-order',
			'ok' => 'ok',
			'facebook' => 'fb',
			Library::ID_FBINSTAGRAMDIRECT_CONNECTOR => 'instagram-direct',
			'wechat' => 'wechat',
			'facebookcomments' => 'fb-comments',
			'facebookmessenger' => 'fb-messenger',
			'fbinstagram' => 'instagram-fb',
			'network' => 'bitrix24',
			Library::ID_NOTIFICATIONS_CONNECTOR => 'bitrix24-sms',
			'notifications_virtual_wa' => 'whatsapp',
			'notifications_reverse_wa' => 'bitrix24-sms',
			'botframework' => 'microsoft',
			'skypebot' => 'skype',
			'skype' => 'skype',
			'slack' => 'slack',
			'kik' => 'kik',
			'groupme' => 'groupme',
			'twilio' => 'twilio',
			'emailoffice365' => 'outlook',
			'webchat' => 'webchat',
			'msteams' => 'envelope',
			'whatsappbytwilio' => 'whatsapp',
			'avito' => 'avito',
			'olx' => 'olx',
			Library::ID_EDNA_WHATSAPP_CONNECTOR => 'edna',
			'directline' => 'directline',
			'botframework.skype' => 'skype',
			'botframework.slack' => 'slack',
			'botframework.kik' => 'kik',
			'botframework.telegram' => 'telegram',
			'botframework.groupme' => 'groupme',
			'botframework.twilio' => 'twilio',
			'botframework.emailoffice365' => 'outlook',
			'botframework.facebookmessenger' => 'fb-messenger',
			'botframework.webchat' => 'webchat',
			'botframework.msteams' => 'envelope',
			'botframework.directline' => 'directline'
		];
	}

	/**
	 * Returns icon name for current connector
	 *
	 * @param $connectorId
	 * @return mixed
	 */
	public static function getIconByConnector($connectorId)
	{
		$iconMap = self::getIconClassMap();

		return $iconMap[$connectorId];
	}

	/**
	 * Return additional css-style string
	 *
	 * @return string
	 */
	public static function getAdditionalStyles()
	{
		return CustomConnectors::getStyleCss();
	}

	/**
	 * Inits icon UI-lib and custom connector active icon styles
	 */
	public static function initIconCss()
	{
		\Bitrix\Main\UI\Extension::load('ui.icons');

		$iconStyle = self::getAdditionalStyles();

		if (!empty($iconStyle))
		{
			Asset::getInstance()->addString('<style>' . $iconStyle . '</style>', true);
		}
	}

	/**
	 * Inits icon UI-lib and custom connector disabled icon styles
	 */
	public static function initIconDisabledCss()
	{
		\Bitrix\Main\UI\Extension::load('ui.icons');

		$iconStyle = CustomConnectors::getStyleCssDisabled();

		if (!empty($iconStyle))
		{
			Asset::getInstance()->addString('<style>' . $iconStyle . '</style>', true);
		}
	}

	/**
	 * Adding a channel is an open line.
	 *
	 * @param string $line ID open line.
	 * @param string $connector ID open line.
	 * @param array $params Settings.
	 * @return Result The result of the addition.
	 */
	public static function add($line, $connector, $params = [])
	{
		$result = new Result();

		if (
			empty($line)
			|| empty($connector)
		)
		{
			$result->addError(new Error(
				Loc::getMessage('IMCONNECTOR_EMPTY_PARAMETRS'),
				Library::ERROR_IMCONNECTOR_EMPTY_PARAMETRS,
				__METHOD__,
				['line' => $line, 'connector' => $connector, 'params' => $params]
			));
		}
		elseif (!self::isConnector($connector))
		{
			$result->addError(new Error(
				Loc::getMessage('IMCONNECTOR_NOT_AVAILABLE_CONNECTOR'),
				Library::ERROR_NOT_AVAILABLE_CONNECTOR,
				__METHOD__,
				$connector
			));
		}
		else
		{
			$status = Status::getInstance($connector, (int)$line);
			$cacheId = self::getCacheIdConnector($line, $connector);

			if ($status->getActive())
			{
				$result->addError(new Error(
					Loc::getMessage('IMCONNECTOR_ADD_EXISTING_CONNECTOR'),
					Library::ERROR_ADD_EXISTING_CONNECTOR,
					__METHOD__,
					$connector
				));
			}

			if ($result->isSuccess())
			{
				switch ($connector)
				{
					case 'livechat':
						if (Loader::includeModule(Library::MODULE_ID_OPEN_LINES))
						{
							$liveChatManager = new LiveChatManager($line);
							if (!$liveChatManager->add($params))
							{
								$result->addError(new Error(
									Loc::getMessage('IMCONNECTOR_FAILED_TO_ADD_CONNECTOR'),
									Library::ERROR_FAILED_TO_ADD_CONNECTOR,
									__METHOD__,
									$connector
								));
							}
						}
						else
						{
							$result->addError(new Error(
								Loc::getMessage('IMCONNECTOR_FAILED_TO_LOAD_MODULE_OPEN_LINES'),
								Library::ERROR_FAILED_TO_LOAD_MODULE_OPEN_LINES,
								__METHOD__,
								$connector
							));
						}
						break;

					case 'network':
						if (Loader::includeModule(Library::MODULE_ID_OPEN_LINES))
						{
							$output = new Output($connector, $line);
							$resultRegister = $output->register($params);

							if ($resultRegister->isSuccess())
							{
								$status->setData($resultRegister->getResult());
							}
							else
							{
								$result->addError(new Error(
									Loc::getMessage('IMCONNECTOR_FAILED_TO_ADD_CONNECTOR'),
									Library::ERROR_FAILED_TO_ADD_CONNECTOR,
									__METHOD__,
									$connector
								));
								$result->addErrors($resultRegister->getErrors());
							}

						}
						else
						{
							$result->addError(new Error(
								Loc::getMessage('IMCONNECTOR_FAILED_TO_LOAD_MODULE_OPEN_LINES'),
								Library::ERROR_FAILED_TO_LOAD_MODULE_OPEN_LINES,
								__METHOD__,
								$connector
							));
						}
						break;

					case 'telegrambot':
					case 'botframework':
					case 'ok':
						$output = new Output($connector, $line);
						$saved = $output->saveSettings($params);

						if ($saved->isSuccess())
						{
							$status->setActive(true);

							$testConnect = $output->testConnect();
							if($testConnect->isSuccess())
							{
								$status->setConnection(true);

								$register = $output->register();
								if(!$register->isSuccess())
								{
									$result->addError(new Error(
										Loc::getMessage('IMCONNECTOR_FAILED_REGISTER_CONNECTOR'),
										Library::ERROR_FAILED_REGISTER_CONNECTOR,
										__METHOD__,
										$connector
									));
									$result->addErrors($testConnect->getErrors());
								}
							}
							else
							{
								$result->addError(new Error(
									Loc::getMessage('IMCONNECTOR_FAILED_TO_TEST_CONNECTOR'),
									Library::ERROR_FAILED_TO_TEST_CONNECTOR,
									__METHOD__,
									$connector
								));
								$result->addErrors($testConnect->getErrors());
							}
						}
						else
						{
							$result->addError(new Error(
								Loc::getMessage('IMCONNECTOR_FAILED_TO_SAVE_SETTINGS_CONNECTOR'),
								Library::ERROR_FAILED_TO_SAVE_SETTINGS_CONNECTOR,
								__METHOD__,
								$connector
							));
							$result->addErrors($saved->getErrors());
						}
						break;

					case 'vkgroup':
					case 'facebook':
					case Library::ID_FBINSTAGRAMDIRECT_CONNECTOR:
					case 'facebookcomments':
					case Library::ID_FBINSTAGRAM_CONNECTOR:
					default:
						$result->addError(new Error(
							Loc::getMessage('IMCONNECTOR_FEATURE_IS_NOT_SUPPORTED'),
							Library::ERROR_FEATURE_IS_NOT_SUPPORTED,
							__METHOD__,
							$connector
						));
						break;
				}
			}

			if ($result->isSuccess())
			{
				$status
					->setActive(true)
					->setConnection(true)
					->setRegister(true)
					->setError(false)
					->save()
				;
			}

			self::cleanCacheConnector($line, $cacheId);
		}

		return $result;
	}

	/**
	 * Update a channel is an open line.
	 *
	 * @param string $line ID open line.
	 * @param string $connector ID open line.
	 * @param array $params Settings.
	 * @return Result The result of the addition.
	 */
	public static function update($line, $connector, $params = [])
	{
		$result = new Result();

		if (
			empty($line)
			|| empty($connector)
		)
		{
			$result->addError(new Error(
				Loc::getMessage('IMCONNECTOR_EMPTY_PARAMETRS'),
				Library::ERROR_IMCONNECTOR_EMPTY_PARAMETRS,
				__METHOD__,
				array
				('line' => $line, 'connector' => $connector, 'params' => $params)));
		}
		elseif (!self::isConnector($connector))
		{
			$result->addError(new Error(
				Loc::getMessage('IMCONNECTOR_NOT_AVAILABLE_CONNECTOR'),
				Library::ERROR_NOT_AVAILABLE_CONNECTOR,
				__METHOD__,
				$connector
			));
		}
		else
		{
			$status = Status::getInstance($connector, (int)$line);
			$cacheId = self::getCacheIdConnector($line, $connector);

			if (!$status->getActive())
			{
				$result->addError(new Error(
					Loc::getMessage('IMCONNECTOR_UPDATE_NOT_EXISTING_CONNECTOR'),
					Library::ERROR_UPDATE_NOT_EXISTING_CONNECTOR,
					__METHOD__,
					$connector
				));
			}

			if($result->isSuccess())
			{
				switch ($connector)
				{
					case 'livechat':
						if (Loader::includeModule(Library::MODULE_ID_OPEN_LINES))
						{
							$liveChatManager = new LiveChatManager($line);
							if (!$liveChatManager->update($params))
								$result->addError(new Error(
									Loc::getMessage('IMCONNECTOR_FAILED_TO_UPDATE_CONNECTOR'),
									Library::ERROR_FAILED_TO_UPDATE_CONNECTOR,
									__METHOD__,
									$connector
								));
						}
						else
						{
							$result->addError(new Error(
								Loc::getMessage('IMCONNECTOR_FAILED_TO_LOAD_MODULE_OPEN_LINES'),
								Library::ERROR_FAILED_TO_LOAD_MODULE_OPEN_LINES,
								__METHOD__,
								$connector
							));
						}

						if (!$result->isSuccess())
						{
							$status->setConnection(false);
							$status->setRegister(false);
						}
						break;

					case 'network':
						if (Loader::includeModule(Library::MODULE_ID_OPEN_LINES))
						{
							$output = new Output($connector, $line);
							$resultUpdate = $output->update($params);

							if ($resultUpdate->isSuccess())
							{
								$dataStatus = $status->getData();
								$dataStatus = array_merge($dataStatus, $params);
								$status->setData($dataStatus);
							}
							else
							{
								$result->addError(new Error(
									Loc::getMessage('IMCONNECTOR_FAILED_TO_UPDATE_CONNECTOR'),
									 Library::ERROR_FAILED_TO_UPDATE_CONNECTOR,
									__METHOD__,
									$connector
								));
								$result->addErrors($resultUpdate->getErrors());
							}
						}
						else
						{
							$result->addError(new Error(
								Loc::getMessage('IMCONNECTOR_FAILED_TO_LOAD_MODULE_OPEN_LINES'),
								Library::ERROR_FAILED_TO_LOAD_MODULE_OPEN_LINES,
								__METHOD__,
								$connector
							));
						}

						if(!$result->isSuccess())
						{
							$status->setConnection(false);
							$status->setRegister(false);
						}
						break;

					case 'telegrambot':
					case 'botframework':
					case 'ok':
						$output = new Output($connector, $line);
						$saved = $output->saveSettings($params);

						if ($saved->isSuccess())
						{
							$testConnect = $output->testConnect();
							if ($testConnect->isSuccess())
							{
								$status->setConnection(true);

								$register = $output->register();
								if(!$register->isSuccess())
								{
									$result->addError(new Error(
										Loc::getMessage('IMCONNECTOR_FAILED_REGISTER_CONNECTOR'),
										Library::ERROR_FAILED_REGISTER_CONNECTOR,
										__METHOD__,
										 $connector
									));
									$result->addErrors($testConnect->getErrors());

									$status->setRegister(false);
								}
							}
							else
							{
								$result->addError(new Error(
									Loc::getMessage('IMCONNECTOR_FAILED_TO_TEST_CONNECTOR'),
									 Library::ERROR_FAILED_TO_TEST_CONNECTOR,
									__METHOD__,
									$connector
								));
								$result->addErrors($testConnect->getErrors());

								$status->setConnection(false);
							}
						}
						else
						{
							$result->addError(new Error(
								Loc::getMessage('IMCONNECTOR_FAILED_TO_SAVE_SETTINGS_CONNECTOR'),
								Library::ERROR_FAILED_TO_SAVE_SETTINGS_CONNECTOR,
								__METHOD__,
								$connector
));
							$result->addErrors($saved->getErrors());

							$status->setConnection(false);
							$status->setRegister(false);
						}
						break;

					case 'vkgroup':
					case 'facebook':
					case Library::ID_FBINSTAGRAMDIRECT_CONNECTOR:
					case 'facebookcomments':
					case Library::ID_FBINSTAGRAM_CONNECTOR:
					default:
						$result->addError(new Error(
							Loc::getMessage('IMCONNECTOR_FEATURE_IS_NOT_SUPPORTED'),
							Library::ERROR_FEATURE_IS_NOT_SUPPORTED,
							__METHOD__,
							$connector
				));
						break;
				}
			}

			if ($result->isSuccess())
			{
				$status
					->setActive(true)
					->setConnection(true)
					->setRegister(true);
			}

			$status->setError(false);
			$status->save();

			self::cleanCacheConnector($line, $cacheId);
		}

		return $result;
	}

	/**
	 * Delete a channel is an open line.
	 *
	 * @param string $line ID open line.
	 * @param string $connector ID open line.
	 * @return Result The result of the addition.
	 */
	public static function delete($line, $connector)
	{
		$result = new Result();

		if (
			empty($line)
			|| empty($connector)
		)
		{
			$result->addError(new Error(
				Loc::getMessage('IMCONNECTOR_EMPTY_PARAMETRS'),
				Library::ERROR_IMCONNECTOR_EMPTY_PARAMETRS,
				__METHOD__,
				array('line' => $line, 'connector' => $connector)
			));
		}
		elseif (!self::isConnector($connector))
		{
			$result->addError(new Error(
				Loc::getMessage('IMCONNECTOR_NOT_AVAILABLE_CONNECTOR'),
				Library::ERROR_NOT_AVAILABLE_CONNECTOR,
				__METHOD__,
				$connector
			));
		}
		else
		{
			$status = Status::getInstance($connector, (int)$line);
			$cacheId = self::getCacheIdConnector($line, $connector);

			if (!$status->getActive())
			{
				$result->addError(new Error(
					Loc::getMessage('IMCONNECTOR_DELETE_NOT_EXISTING_CONNECTOR'),
					Library::ERROR_DELETE_NOT_EXISTING_CONNECTOR,
					__METHOD__,
					$connector
				));
			}

			if ($result->isSuccess())
			{
				switch ($connector)
				{
					case 'livechat':
						if (Loader::includeModule(Library::MODULE_ID_OPEN_LINES))
						{
							$liveChatManager = new LiveChatManager($line);
							if (!$liveChatManager->delete())
							{
								$result->addError(new Error(
									Loc::getMessage('IMCONNECTOR_FAILED_TO_DELETE_CONNECTOR'),
									Library::ERROR_FAILED_TO_DELETE_CONNECTOR,
									__METHOD__,
									$connector
								));
							}
						}
						else
						{
							$result->addError(new Error(
								Loc::getMessage('IMCONNECTOR_FAILED_TO_LOAD_MODULE_OPEN_LINES'),
								Library::ERROR_FAILED_TO_LOAD_MODULE_OPEN_LINES,
								__METHOD__,
								$connector
							));
						}

						break;

					case 'network':
						if (Loader::includeModule(Library::MODULE_ID_OPEN_LINES))
						{
							$output = new Output($connector, $line);
							$resultDelete = $output->delete();
							if (!$resultDelete->isSuccess())
							{
								$result->addError(new Error(
									Loc::getMessage('IMCONNECTOR_FAILED_TO_DELETE_CONNECTOR'),
									Library::ERROR_FAILED_TO_DELETE_CONNECTOR,
									__METHOD__,
									$connector
								));
								$result->addErrors($resultDelete->getErrors());
							}
						}
						else
						{
							$result->addError(new Error(
								Loc::getMessage('IMCONNECTOR_FAILED_TO_LOAD_MODULE_OPEN_LINES'),
								Library::ERROR_FAILED_TO_LOAD_MODULE_OPEN_LINES,
								__METHOD__,
								$connector
							));
						}
						break;

					case 'facebook':
					case Library::ID_FBINSTAGRAMDIRECT_CONNECTOR:
					case 'vkgroup':
					case 'ok':
					case 'telegrambot':
					case 'botframework':
					case 'facebookcomments':
					case Library::ID_FBINSTAGRAM_CONNECTOR:
					case 'avito':
					case 'wechat':
					case 'imessage':
						$output = new Output($connector, $line);
						$rawDelete = $output->deleteConnector();
						if(!$rawDelete->isSuccess())
						{
							$result->addError(new Error(
								Loc::getMessage('IMCONNECTOR_FAILED_TO_DELETE_CONNECTOR'),
								Library::ERROR_FAILED_TO_DELETE_CONNECTOR,
								__METHOD__,
								$connector
							));
							$result->addErrors($rawDelete->getErrors());

						}
						break;

					default:
						$result->addError(new Error(
							Loc::getMessage('IMCONNECTOR_FEATURE_IS_NOT_SUPPORTED'),
							Library::ERROR_FEATURE_IS_NOT_SUPPORTED,
							__METHOD__,
							$connector
						));
						break;
				}
			}

			if ($result->isSuccess())
			{
				Status::delete($connector, (int)$line);
			}

			self::cleanCacheConnector($line, $cacheId);
		}

		return $result;
	}

	/**
	 * Get reply limit for connector, if the limit exists.
	 *
	 * @param string $connectorId Connector ID.
	 * @return array|null
	 */
	public static function getReplyLimit(string $connectorId): ?array
	{
		$result = null;

		if (
			isset(Library::TIME_LIMIT_RESTRICTIONS[$connectorId])
			&& Library::TIME_LIMIT_RESTRICTIONS[$connectorId]['LIMIT_START_DATE'] < (new DateTime())->getTimestamp()
		)
		{
			$result = Library::TIME_LIMIT_RESTRICTIONS[$connectorId];
		}

		return $result;
	}

	/**
	 * Returns true if we need to delete block after incoming message.
	 *
	 * @param string $connectorId
	 * @return bool
	 */
	public static function isNeedToAutoDeleteBlock(string $connectorId): bool
	{
		if (in_array($connectorId, Library::AUTO_DELETE_BLOCK, true))
		{
			return true;
		}

		return false;
	}

	/**
	 * Prepares attachments data to send.
	 *
	 * @param array $message
	 * @return array|null
	 */
	public static function sendMessageProcessing(array $message): array
	{
		$richData = [];
		if (!empty($message['message']['attachments']) && is_array($message['message']['attachments']))
		{
			foreach ($message['message']['attachments'] as $attachment)
			{
				$attachment = \Bitrix\Main\Web\Json::decode($attachment);
				if (isset($attachment['BLOCKS']) && is_array($attachment['BLOCKS']))
				{
					foreach ($attachment['BLOCKS'] as $block)
					{
						if (isset($block['RICH_LINK']) && is_array($block['RICH_LINK']))
						{
							foreach ($block['RICH_LINK'] as $richData)
							{
								if (!empty($richData))
								{
									if ($richData['LINK'])
									{
										$richData['richData']['url'] = $richData['LINK'];
									}

									if ($richData['NAME'])
									{
										$richData['richData']['title'] = $richData['NAME'];
									}

									if ($richData['DESC'])
									{
										$richData['richData']['description'] = $richData['DESC'];
									}

									if ($richData['PREVIEW'])
									{
										$uri = new Uri($richData['PREVIEW']);
										if ($uri->getHost())
										{
											$richData['richData']['image'] = $richData['PREVIEW'];
										}
										else
										{
											$richData['richData']['image'] = self::getDomainDefault() .'/'. $richData['PREVIEW'];
										}
									}
									elseif($richData['EXTRA_IMAGE'])
									{
										$richData['richData']['image'] = $richData['EXTRA_IMAGE'];
									}
								}
							}
						}
					}
				}
			}
		}

		$message['message']['attachments'] = $richData;

		return $message;
	}

	/**
	 * Returns true if the connector is restricted in RU.
	 *
	 * @param string $connector Connector code.
	 * @param string $region Portal region.
	 * @param string $lang Language.
	 * @return bool
	 */
	public static function needRestrictionNote(string $connector, string $region, string $lang): bool
	{
		if (!in_array(mb_strtolower($lang), ['ru', 'en'], true))
		{
			return false;
		}

		if (mb_strtolower($region) !== 'ru')
		{
			return false;
		}

		if (!in_array($connector, Library::RU_RESTRICTED_META_CONNECTORS, true))
		{
			return false;
		}

		return true;
	}

	/**
	 * Detects portal's region.
	 * @return string
	 */
	public static function getPortalRegion(): string
	{
		return Main\Application::getInstance()->getLicense()->getRegion() ?: '';
	}

	/**
	 * Checks availability of the external public url.
	 * @see \Bitrix\ImConnector\Output::checkPublicUrl
	 * @param string $publicUrl Portal public url to validate.
	 * @param bool $checkHandlerPath
	 * @return Result
	 */
	public static function checkPublicUrl(string $publicUrl, bool $checkHandlerPath = true): Result
	{
		$result = new Result();

		if (empty($publicUrl))
		{
			$message = Loc::getMessage('IMCONNECTOR_ERROR_PUBLIC_URL_EMPTY');
			if (empty($message))
			{
				$message = 'Cannot detect a value of the portal public url.';
			}

			return $result->addError(new Error($message, Library::ERROR_IMCONNECTOR_PUBLIC_URL_EMPTY));
		}

		if (
			!($parsedUrl = \parse_url($publicUrl))
			|| empty($parsedUrl['host'])
			|| strpos($parsedUrl['host'], '.') === false
			|| !in_array($parsedUrl['scheme'], ['http', 'https'])
		)
		{
			$message = Loc::getMessage('IMCONNECTOR_ERROR_PUBLIC_URL_MALFORMED');
			if (empty($message))
			{
				$message = 'Portal public url is malformed.';
			}

			return $result->addError(new Error($message, Library::ERROR_IMCONNECTOR_PUBLIC_URL_MALFORMED));
		}

		// check for local address
		$host = $parsedUrl['host'];
		if (
			strtolower($host) == 'localhost'
			|| $host == '0.0.0.0'
			||
			(
				preg_match('#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#', $host)
				&& preg_match('#^(127|10|172\.16|192\.168)\.#', $host)
			)
		)
		{
			$message = Loc::getMessage('IMCONNECTOR_ERROR_PUBLIC_URL_LOCALHOST', ['#HOST#' => $host]);
			if (empty($message))
			{
				$message = 'Portal public url points to localhost: '.$host;
			}

			return $result->addError(new Error($message, Library::ERROR_IMCONNECTOR_PUBLIC_URL_LOCALHOST));
		}

		$error = (new \Bitrix\Main\Web\Uri($publicUrl))->convertToPunycode();
		if ($error instanceof \Bitrix\Main\Error)
		{
			$message = Loc::getMessage('IMCONNECTOR_ERROR_CONVERTING_PUNYCODE', ['#HOST#' => $host, '#ERROR#' => $error->getMessage()]);
			if (empty($message))
			{
				$message = 'Error converting hostname '.$host.' to punycode: '.$error->getMessage();
			}

			return $result->addError(new Error($message, Library::ERROR_IMCONNECTOR_PUBLIC_URL_MALFORMED));
		}

		if ($checkHandlerPath)
		{
			$documentRoot = '';
			$siteList = \CSite::getList('', '', ['DOMAIN' => $host, 'ACTIVE' => 'Y']);
			if ($site = $siteList->fetch())
			{
				$documentRoot = $site['ABS_DOC_ROOT'];
			}
			else
			{
				$siteList = \CSite::getList('', '', ['DEFAULT' => 'Y', 'ACTIVE' => 'Y']);
				if ($site = $siteList->fetch())
				{
					$documentRoot = $site['ABS_DOC_ROOT'];
				}
			}
			if ($documentRoot)
			{
				$documentRoot = \Bitrix\Main\IO\Path::normalize($documentRoot);
				$publicHandler = new \Bitrix\Main\IO\File($documentRoot. Library::PORTAL_PATH);
				if (!$publicHandler->isExists())
				{
					$message = Loc::getMessage('IMCONNECTOR_ERROR_PUBLIC_URL_HANDLER_PATH', ['#PATH#' => Library::PORTAL_PATH]);
					if (empty($message))
					{
						$message = 'The file handler has not been found within the site document root. Expected: '. Library::PORTAL_PATH;
					}

					return $result->addError(new Error($message, Library::ERROR_IMCONNECTOR_PUBLIC_URL_HANDLER_PATH));
				}
			}
		}

		return $result;
	}
}
