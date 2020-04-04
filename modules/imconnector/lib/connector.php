<?php
namespace Bitrix\ImConnector;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Context,
	\Bitrix\Main\Page\Asset,
	\Bitrix\Main\Data\Cache,
	\Bitrix\Main\Config\Option,
	\Bitrix\Main\Web\Json,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\ImOpenLines\Network,
	\Bitrix\ImOpenLines\LiveChatManager;
use \Bitrix\ImConnector\Model\InfoConnectorsTable;

Loc::loadMessages(__FILE__);
Library::loadMessages();

/**
 * Auxiliary class for work with connectors.
 * @package Bitrix\ImConnector
 */
class Connector
{
	const ERROR_CHOICE_DOMAIN_FOR_FEEDBACK = 'CHOICE_DOMAIN_FOR_FEEDBACK';

	/**
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function isLocationRussia()
	{
		$result = false;

		if(
			(Loader::includeModule('bitrix24') && \CBitrix24::getPortalZone() === 'ru') ||
			(!Loader::includeModule('bitrix24') && Loader::includeModule('intranet') && \CIntranetUtils::getPortalZone() === "ru")
		)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function isLocationUkraine()
	{
		$result = false;

		if(
			(!Loader::includeModule('bitrix24') && \CIntranetUtils::getPortalZone() === "ua") ||
			(Loader::includeModule('bitrix24') && \CBitrix24::getPortalZone() === 'ua')
		)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * List of connectors, available on the client. connector id => connector Name.
	 * @param bool|integer $reduced To shorten the channel names.
	 * @param bool $customConnectors Return custom connectors
	 *
	 * @return array.
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getListConnector($reduced = false, $customConnectors = true)
	{
		$connectors['livechat'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_LIVECHAT');
		$connectors['whatsappbytwilio'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_WHATSAPPBYTWILIO');
		// avito available only in Russia
		if (self::isLocationRussia())
		{
			$connectors['avito'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_AVITO');
		}
		// disabled in UA
		if(!self::isLocationUkraine())
		{
			$connectors["yandex"] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_YANDEX');
		}
		$connectors['viber'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_VIBER_BOT');
		$connectors['telegrambot'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_TELEGRAM_BOT');
		$connectors['wechat'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_WECHAT');
		// disabled in UA
		if(!self::isLocationUkraine())
		{
			$connectors["vkgroup"] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_VK_GROUP');
		}
		$connectors['facebook'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_FACEBOOK_PAGE');
		$connectors['facebookcomments'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_FACEBOOK_COMMENTS_PAGE');
		$connectors['fbinstagram'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_FBINSTAGRAM');
		//TODO: del
		$connectors['instagram'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_INSTAGRAM');
		$connectors['network'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_NETWORK');
		//Virtual connectors.
		$connectors['botframework.skype'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_SKYPE');
		$connectors['botframework.slack'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_SLACK');
		$connectors['botframework.kik'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_KIK');
		$connectors['botframework.groupme'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_GROUPME');
		$connectors['botframework.twilio'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_TWILIO');
		$connectors['botframework.msteams'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_MSTEAMS');
		$connectors['botframework.webchat'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_WEBCHAT');
		$connectors['botframework.emailoffice365'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_EMAILOFFICE365');
		$connectors['botframework.telegram'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_TELEGRAM');
		$connectors['botframework.facebookmessenger'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_FACEBOOKMESSENGER');
		$connectors['botframework.directline'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK_DIRECTLINE');

		if($customConnectors === true)
		{
			$customConnectors = CustomConnectors::getListConnector();

			if(!empty($customConnectors))
				$connectors = array_merge($customConnectors, $connectors);
		}

		if(!empty($reduced))
		{
			if($reduced>5)
				$number = $reduced;
			else
				$number = 30;

			foreach ($connectors as $cell=>$connector)
			{
				if(strlen($connector) > $number)
					$connectors[$cell] = substr($connector, 0, ($number-3)) . '...';
			}
		}

		return $connectors;
	}

	/**
	 * Real list of connectors, available on the client. connector id => connector Name.
	 * @param bool|integer $reduced To shorten the channel names.
	 * @param bool $customConnectors Return custom connectors
	 *
	 * @return array.
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getListConnectorReal($reduced = false, $customConnectors = true)
	{
		$connectors['livechat'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_LIVECHAT');
		$connectors['whatsappbytwilio'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_WHATSAPPBYTWILIO');
		// avito available only in Russia
		if (self::isLocationRussia())
		{
			$connectors['avito'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_AVITO');
		}
		// disabled in UA
		if(!self::isLocationUkraine())
		{
			$connectors["yandex"] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_YANDEX');
		}
		$connectors['viber'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_VIBER_BOT');
		$connectors['telegrambot'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_TELEGRAM_BOT');
		$connectors['wechat'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_WECHAT');
		// disabled in UA
		if(!self::isLocationUkraine())
		{
			$connectors["vkgroup"] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_VK_GROUP');
		}
		$connectors['facebook'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_FACEBOOK_PAGE');
		$connectors['facebookcomments'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_FACEBOOK_COMMENTS_PAGE');
		$connectors['fbinstagram'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_FBINSTAGRAM');
		//TODO: del
		$connectors['instagram'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_INSTAGRAM');
		$connectors['network'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_NETWORK');
		//Virtual connectors.
		$connectors['botframework'] = Loc::getMessage('IMCONNECTOR_NAME_CONNECTOR_BOTFRAMEWORK');

		if($customConnectors === true)
		{
			$customConnectors = CustomConnectors::getListConnector();

			if(!empty($customConnectors))
				$connectors = array_merge($customConnectors, $connectors);
		}

		if(!empty($reduced))
		{
			if($reduced>5)
				$number = $reduced;
			else
				$number = 30;

			foreach ($connectors as $cell=>$connector)
			{
				if(strlen($connector) > $number)
					$connectors[$cell] = substr($connector, 0, ($number-3)) . '...';
			}
		}

		return $connectors;
	}

	/**
	 * @param bool $customConnectors
	 * @return array|string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function getListConnectorActive($customConnectors = true)
	{
		$connectors = strtolower(Option::get(Library::MODULE_ID, "list_connector"));
		$connectors = explode(",", $connectors);

		if($customConnectors === true)
			$connectors = array_merge(CustomConnectors::getListConnectorId(), $connectors);

		return $connectors;
	}

	public static function getListConnectorNoServer()
	{
		return array_merge(Library::$noServerConnectors, CustomConnectors::getListConnectorId());
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getListConnectorShowDeliveryStatus()
	{
		return array_keys(static::getListConnector());
	}

	/**
	 * A list of matching id of the connector component.
	 *
	 * @return array.
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getListComponentConnector()
	{
		$components = array(
			'livechat' => 'bitrix:imconnector.livechat',
			'whatsappbytwilio' => 'bitrix:imconnector.whatsappbytwilio',
		);

		// avito available only in Russia
		if (!Loader::includeModule('bitrix24') || \CBitrix24::getPortalZone() === 'ru')
		{
			$components['avito'] = 'bitrix:imconnector.avito';
		}

		// disabled in b24.ua
		if(!Loader::includeModule('bitrix24') || \CBitrix24::getPortalZone() !== 'ua')
			$components["yandex"] = 'bitrix:imconnector.yandex';

		$components = array_merge($components, array(
			'viber' => 'bitrix:imconnector.viber',
			'telegrambot' => 'bitrix:imconnector.telegrambot',
			'wechat' => 'bitrix:imconnector.wechat'
		));

		// disabled in b24.ua
		if(!Loader::includeModule('bitrix24') || \CBitrix24::getPortalZone() !== 'ua')
			$components["vkgroup"] = 'bitrix:imconnector.vkgroup';

		$components = array_merge($components, array(
			'facebook' => 'bitrix:imconnector.facebook',
			'facebookcomments' => 'bitrix:imconnector.facebookcomments',
			'fbinstagram' => 'bitrix:imconnector.fbinstagram',
			'instagram' => 'bitrix:imconnector.instagram',
			'network' => 'bitrix:imconnector.network',
			'botframework' => 'bitrix:imconnector.botframework',
		));

		$customComponents = CustomConnectors::getListComponentConnector();

		if(!empty($customComponents))
			$components = array_merge($customComponents, $components);

		return $components;
	}

	/**
	 * List of connectors where you can delete other people's posts
	 *
	 * @return array
	 */
	public static function getListConnectorDelExternalMessages()
	{
		$listConnectorDelExternalMessages = Library::$listConnectorDelExternalMessages;

		$customConnectorDelExternalMessages = CustomConnectors::getListConnectorDelExternalMessages();

		if(!empty($customConnectorDelExternalMessages))
			$listConnectorDelExternalMessages = array_merge($customConnectorDelExternalMessages, $listConnectorDelExternalMessages);

		return $listConnectorDelExternalMessages;
	}

	/**
	 * List of connectors where you can edit your posts
	 *
	 * @return array
	 */
	public static function getListConnectorEditInternalMessages()
	{
		$listConnectorEditInternalMessages = Library::$listConnectorEditInternalMessages;

		$customConnectorEditInternalMessages = CustomConnectors::getListConnectorEditInternalMessages();

		if(!empty($customConnectorEditInternalMessages))
			$listConnectorEditInternalMessages = array_merge($customConnectorEditInternalMessages, $listConnectorEditInternalMessages);

		return $listConnectorEditInternalMessages;
	}

	/**
	 * List of connectors where you can delete your posts
	 *
	 * @return array
	 */
	public static function getListConnectorDelInternalMessages()
	{
		$listConnectorDelInternalMessages = Library::$listConnectorDelInternalMessages;

		$customConnectorDelInternalMessages = CustomConnectors::getListConnectorEditInternalMessages();

		if(!empty($customConnectorDelInternalMessages))
			$listConnectorDelInternalMessages = array_unique(array_merge($customConnectorDelInternalMessages, $listConnectorDelInternalMessages));

		return $listConnectorDelInternalMessages;
	}

	/**
	 * A list of connectors, where it is impossible to send a newsletter
	 *
	 * @return array
	 */
	public static function getListConnectorNotNewsletter()
	{
		$listNotNewsletterChats = Library::$listNotNewsletterChats;

		$customNotNewsletterChat = CustomConnectors::getListConnectorNotNewsletter();

		if(!empty($customNotNewsletterChat))
			$listNotNewsletterChats = array_unique(array_merge($customNotNewsletterChat, $listNotNewsletterChats));

		return $listNotNewsletterChats;
	}

	/**
	 * List of connectors where you can send newsletter
	 *
	 * @return array
	 */
	public static function getListConnectorNewsletter()
	{
		return array_diff(array_keys(static::getListConnector()), self::getListConnectorNotNewsletter());
	}

	/**
	 * Do I need to send system messages?
	 *
	 * @param string $id ID connector
	 * @return bool
	 */
	public static function isNeedSystemMessages($id)
	{
		$listNotNeedSystemMessages = Library::$listNotNeedSystemMessages;

		$customNotNeedSystemMessages = CustomConnectors::getListNotNeedSystemMessages();

		if(!empty($customNotNeedSystemMessages))
			$listNotNeedSystemMessages = array_unique(array_merge($customNotNeedSystemMessages, $listNotNeedSystemMessages));

		return !in_array($id, $listNotNeedSystemMessages);
	}

	/**
	 * Whether to send a signature?
	 *
	 * @param string $id ID connector
	 * @return bool
	 */
	public static function isNeedSignature($id)
	{
		$listNotNeedSignature = Library::$listNotNeedSignature;

		$customNotNeedSignature = CustomConnectors::getListNotNeedSignature();

		if(!empty($customNotNeedSignature))
			$listNotNeedSignature = array_unique(array_merge($customNotNeedSignature, $listNotNeedSignature));

		return !in_array($id, $listNotNeedSignature);
	}

	/**
	 * This chat group?
	 *
	 * @param string $id ID connector
	 * @return bool
	 */
	public static function isChatGroup($id)
	{
		$listGroupChats = Library::$listGroupChats;

		$customGroupChats = CustomConnectors::getListChatGroup();

		if(!empty($customGroupChats))
			$listGroupChats = array_unique(array_merge($customGroupChats, $listGroupChats));

		return in_array($id, $listGroupChats);
	}

	/**
	 * Check that we can use message tracker on this chat messages
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	public static function isTrackedChat($id)
	{
		$listSingleThreadGroupChats = Library::$listSingleThreadGroupChats;
		$isChatGroup = self::isChatGroup($id);

		return !$isChatGroup || $isChatGroup && in_array($id, $listSingleThreadGroupChats);
	}

	/**
	 * This chat for newsletter?
	 *
	 * @param string $id ID connector
	 * @return bool
	 */
	public static function isChatNewsletter($id)
	{
		$listConnectorNewsletter = self::getListConnectorNewsletter();

		return in_array($id, $listConnectorNewsletter);
	}

	/**
	 * The list are available also connectors, active on the client.
	 * The connector has to be available on the client, it has to be included in settings and be supported on the server.
	 * @param bool|integer $reduced To shorten the channel names.
	 * @param bool $local
	 *
	 * @return array.
	 */
	public static function getListActiveConnector($reduced = false, $local = false)
	{
		$result = array();
		foreach (self::getListConnector($reduced) as $id => $value)
		{
			if(self::isConnector($id, $local))
				$result[$id] = $value;
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
		$result = array();
		foreach (self::getListConnectorReal($reduced) as $id => $value)
		{
			if(self::isConnector($id))
				$result[$id] = $value;
		}

		return $result;
	}

	/**
	 * Returns a list of connectors, ready for sharing.
	 *
	 * @param string $lineId ID Open Line.
	 * @return array
	 */
	public static function getListConnectedConnector($lineId)
	{
		$result = array();
		$listActiveConnector = self::getListActiveConnector();

		foreach ($listActiveConnector as $id => $value)
		{
			if(Status::getInstance($id, $lineId)->isStatus())
				$result[$id] = $value;
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
		$result = array();
		foreach (self::getListConnectorReal($reduced) as $id => $value)
		{
			if(self::isConnector($id, true))
			{
				$result[$id]['name'] = $value;

				if($reduced)
					$result[$id]['short_name'] = self::getNameConnector($value, $reduced);
			}
		}

		return $result;
	}

	/**
	 * Returns a list of real connectors, ready for sharing.
	 *
	 * @param string $lineId ID Open Line.
	 * @return array
	 */
	public static function getListConnectedConnectorReal($lineId)
	{
		$result = array();
		$listActiveConnector = self::getListActiveConnectorReal();

		foreach ($listActiveConnector as $id => $value)
		{
			if(Status::getInstance($id, $lineId)->isStatus())
				$result[$id] = $value;
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

		if(isset($listConnector[$id]))
			return $listConnector[$id];
		else
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

		if(isset($listConnector[$id]))
			return $listConnector[$id];
		else
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
		$id = strtolower($id);

		$positionSeparator = strpos($id, '.');

		if($positionSeparator != false)
		{
			$id = substr($id, 0, $positionSeparator);
		}

		return $id;
	}

	/**
	 * Check of the connector. That it is included in settings is checked and supported by the server.
	 *
	 * @param string $id ID connector.
	 * @param bool $local Not to check on a remote server.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function isConnector($id, $local = false)
	{
		$id = self::getConnectorRealId($id);

		$connectors = self::getListConnectorActive();

		/*$noServerConnectors = self::getListConnectorNoServer();*/
		if(in_array($id, $connectors)/* && (in_array($id, $noServerConnectors) || $local || Output::isConnector($id)->isSuccess())*/)
			return true;
		else
			return false;
	}

	/**
	 * Returns the domain by default of the current client.
	 *
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function getDomainDefault()
	{
		$uriOption = Option::get(Library::MODULE_ID, "uri_client");

		if(!empty($uriOption))
		{
			$uri = $uriOption;
		}
		elseif(defined('BX24_HOST_NAME') && !empty(BX24_HOST_NAME))
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
	 * @param string $id ID open line.
	 * @return array.
	 */
	/*public static function infoConnectorsLine($id)
	{
		$result = array();
		$cache = Cache::createInstance();

		if ($cache->initCache(Library::CACHE_TIME_INFO_CONNECTORS_LINE, $id, Library::CACHE_DIR_INFO_CONNECTORS_LINE))
		{
			$result = $cache->getVars();
		}
		elseif ($cache->startDataCache())
		{
			$rawInfo = Output::infoConnectorsLine($id);

			$infoConnectors = $rawInfo->getData();

			if(!empty($infoConnectors))
			{
				$result = array();

				$connectors = self::getListActiveConnector();

				foreach ($connectors as $idConnector=>$value)
				{
					if(!empty($infoConnectors[$idConnector]))
					{
						$result[$idConnector] = $infoConnectors[$idConnector];
						if(empty($result[$idConnector]['name']))
							$result[$idConnector]['name'] = $value;

						$result[$idConnector]['connector_name'] = $value;
					}
				}

				if(empty($result))
				{
					$cache->abortDataCache();
				}
				else
				{
					$cache->endDataCache($result);
				}
			}
			else
			{
				$cache->abortDataCache();
			}
		}

		return $result;
	}*/

	/**
	 * Returns information about all connected connectors specific open line.
	 *
	 * @param $lineId
	 *
	 * @return array|mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function infoConnectorsLine($lineId)
	{
		$result = array();

		$info = InfoConnectors::infoConnectorsLine($lineId);

		if (!empty($info['DATA']))
		{
			$result = Json::decode($info['DATA']);
			$expiresTime =  new \Bitrix\Main\Type\DateTime($info['EXPIRES']);

			if ($expiresTime->getTimestamp() < time())
			{
				InfoConnectors::addSingleLineUpdateAgent($info['LINE_ID'], Library::LOCAL_AGENT_EXEC_INTERVAL);
			}

		}
		else
		{
			$infoConnectors = InfoConnectors::addInfoConnectors($lineId);

			if (!empty($infoConnectors))
			{
				if ($infoConnectors->isSuccess())
				{
					$info = $infoConnectors->getData();
					$result = Json::decode($info['DATA']);
				}
			}
		}

		return $result;
	}

	/**
	 * Returns information about all connected connectors specific open line from connector server.
	 *
	 * @param $lineId
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getOutputInfoConnectorsLine($lineId)
	{
		$result = array();
		$rawInfo = Output::infoConnectorsLine($lineId);
		$infoConnectors = $rawInfo->getData();

		if(!empty($infoConnectors))
		{
			$connectors = self::getListActiveConnector();

			foreach ($connectors as $idConnector=>$value)
			{
				if(!empty($infoConnectors[$idConnector]))
				{
					$result[$idConnector] = $infoConnectors[$idConnector];
					if(empty($result[$idConnector]['name']))
						$result[$idConnector]['name'] = $value;

					$result[$idConnector]['connector_name'] = $value;
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
	 * Return map "connector id" - "icon name" for UI-lib icon classes
	 * Not an actual connector list - it's just a list of various names of actual connectors
	 *
	 * @return array
	 */
	public static function getConnectorIconMap()
	{
		return array(
			'livechat' => 'livechat',
			'yandex' => 'ya-dialogs',
			'viber' => 'viber',
			'telegrambot' => 'telegram',
			'telegram' => 'telegram',
			'vkgroup' => 'vk',
			'vkgrouporder' => 'vk-order',
			'facebook' => 'fb',
			'wechat' => 'wechat',
			'facebookcomments' => 'fb-comments',
			'facebookmessenger' => 'fb-messenger',
			'instagram' => 'instagram',
			'fbinstagram' => 'instagram-fb',
			'network' => 'bitrix24',
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
		);
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
		$style = CustomConnectors::getStyleCss();
		//$style .= self::getServicesBackgroundColorCss();

		return $style;
	}

	/**
	 * Inits icon UI-lib and custom connector active icon styles
	 */
	public static function initIconCss()
	{
		\Bitrix\Main\UI\Extension::load("ui.icons");

		$iconStyle = self::getAdditionalStyles();

		if(!empty($iconStyle))
		{
			Asset::getInstance()->addString('<style>' . $iconStyle . '</style>', true);
		}
	}

	/**
	 * @deprecated
	 *
	 * @return string
	 */
	public static function getServicesBackgroundColorCss()
	{
		$style = '';
		$cssFile = (file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/js/ui/icons/service/ui.icons.service.css') ?
			'/bitrix/js/ui/icons/service/ui.icons.service.css' : '/bitrix/js/ui/icons/ui.icons.css');
		$cssFilePath = $_SERVER["DOCUMENT_ROOT"] . $cssFile;
		$cssFile = file_get_contents($cssFilePath);

		if (!empty($cssFile))
		{
			$cssList = \Bitrix\Main\Web\DOM\CssParser::parse($cssFile);

			if (!empty($cssList))
			{
				$column = array_column($cssList, 'SELECTOR');
				$connectorList = self::getConnectorIconMap();

				foreach ($connectorList as $key => $connector)
				{
					$position = array_search('.ui-icon-service-' . $connector . ' > i', $column);

					if ($position !== false)
					{
						$style .= '.imconnector-' . $key . '-background-color { background-color: ' . $cssList[$position]['STYLE']['background-color'] . '; }' . PHP_EOL;
						$style .= '.intranet-' . $key . '-background-color { background-color: ' . $cssList[$position]['STYLE']['background-color'] . '; }' . PHP_EOL;
					}
				}
			}
		}

		return $style;
	}

	/**
	 * Inits icon UI-lib and custom connector disabled icon styles
	 */
	public static function initIconDisabledCss()
	{
		\Bitrix\Main\UI\Extension::load("ui.icons");

		$iconStyle = CustomConnectors::getStyleCssDisabled();

		if(!empty($iconStyle))
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
	public static function add($line, $connector, $params = array())
	{
		$result = new Result();

		if(empty($line) || empty($connector))
		{
			$result->addError(new Error(Loc::getMessage('IMCONNECTOR_EMPTY_PARAMETRS'), Library::ERROR_IMCONNECTOR_EMPTY_PARAMETRS, __METHOD__, array('line' => $line, 'connector' => $connector, 'params' => $params)));
		}
		else
		{
			if(!self::isConnector($connector))
			{
				$result->addError(new Error(Loc::getMessage('IMCONNECTOR_NOT_AVAILABLE_CONNECTOR'), Library::ERROR_NOT_AVAILABLE_CONNECTOR, __METHOD__, $connector));
			}
			else
			{
				$status = Status::getInstance($connector, $line);
				$cacheId = self::getCacheIdConnector($line, $connector);

				if($status->getActive())
					$result->addError(new Error(Loc::getMessage('IMCONNECTOR_ADD_EXISTING_CONNECTOR'), Library::ERROR_ADD_EXISTING_CONNECTOR, __METHOD__, $connector));

				if($result->isSuccess())
				{
					switch ($connector)
					{
						case 'livechat':
							if(Loader::includeModule(Library::MODULE_ID_OPEN_LINES))
							{
								$liveChatManager = new LiveChatManager($line);
								if (!$liveChatManager->add($params))
									$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_TO_ADD_CONNECTOR'), Library::ERROR_FAILED_TO_ADD_CONNECTOR, __METHOD__, $connector));
							}
							else
							{
								$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_TO_LOAD_MODULE_OPEN_LINES'), Library::ERROR_FAILED_TO_LOAD_MODULE_OPEN_LINES, __METHOD__, $connector));
							}
							break;

						case 'network':
							if(Loader::includeModule(Library::MODULE_ID_OPEN_LINES))
							{
								$network = new Network();
								$resultRegister = $network->registerConnector($line, $params);
								if (!$resultRegister)
									$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_TO_ADD_CONNECTOR'), Library::ERROR_FAILED_TO_ADD_CONNECTOR, __METHOD__, $connector));
								else
									$status->setData($resultRegister);
							}
							else
							{
								$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_TO_LOAD_MODULE_OPEN_LINES'), Library::ERROR_FAILED_TO_LOAD_MODULE_OPEN_LINES, __METHOD__, $connector));
							}
							break;

						case 'telegrambot':
						case 'botframework':
							$output = new Output($connector, $line);
							$saved = $output->saveSettings($params);

							if($saved->isSuccess())
							{
								$status->setActive(true);

								$testConnect = $output->testConnect();
								if($testConnect->isSuccess())
								{
									$status->setConnection(true);

									$register = $output->register();
									if(!$register->isSuccess())
									{
										$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_REGISTER_CONNECTOR'), Library::ERROR_FAILED_REGISTER_CONNECTOR, __METHOD__, $connector));
										$result->addErrors($testConnect->getErrors());
									}
								}
								else
								{
									$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_TO_TEST_CONNECTOR'), Library::ERROR_FAILED_TO_TEST_CONNECTOR, __METHOD__, $connector));
									$result->addErrors($testConnect->getErrors());
								}
							}
							else
							{
								$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_TO_SAVE_SETTINGS_CONNECTOR'), Library::ERROR_FAILED_TO_SAVE_SETTINGS_CONNECTOR, __METHOD__, $connector));
								$result->addErrors($saved->getErrors());
							}
							break;

						case 'vkgroup':
						case 'facebook':
						case 'facebookcomments':
						case 'fbinstagram':
						case 'instagram':
						default:
							$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FEATURE_IS_NOT_SUPPORTED'), Library::ERROR_FEATURE_IS_NOT_SUPPORTED, __METHOD__, $connector));
							break;
					}
				}

				if($result->isSuccess())
				{
					$status->setActive(true);
					$status->setConnection(true);
					$status->setRegister(true);
					$status->setError(false);
					Status::save();
					Status::sendUpdateEvent();
				}

				self::cleanCacheConnector($line, $cacheId);
			}
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
	public static function update($line, $connector, $params = array())
	{
		$result = new Result();

		if(empty($line) || empty($connector))
		{
			$result->addError(new Error(Loc::getMessage('IMCONNECTOR_EMPTY_PARAMETRS'), Library::ERROR_IMCONNECTOR_EMPTY_PARAMETRS, __METHOD__, array('line' => $line, 'connector' => $connector, 'params' => $params)));
		}
		else
		{
			if(!self::isConnector($connector))
			{
				$result->addError(new Error(Loc::getMessage('IMCONNECTOR_NOT_AVAILABLE_CONNECTOR'), Library::ERROR_NOT_AVAILABLE_CONNECTOR, __METHOD__, $connector));
			}
			else
			{
				$status = Status::getInstance($connector, $line);
				$cacheId = self::getCacheIdConnector($line, $connector);

				if(!$status->getActive())
					$result->addError(new Error(Loc::getMessage('IMCONNECTOR_UPDATE_NOT_EXISTING_CONNECTOR'), Library::ERROR_UPDATE_NOT_EXISTING_CONNECTOR, __METHOD__, $connector));

				if($result->isSuccess())
				{
					switch ($connector)
					{
						case 'livechat':
							if(Loader::includeModule(Library::MODULE_ID_OPEN_LINES))
							{
								$liveChatManager = new LiveChatManager($line);
								if (!$liveChatManager->update($params))
									$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_TO_UPDATE_CONNECTOR'), Library::ERROR_FAILED_TO_UPDATE_CONNECTOR, __METHOD__, $connector));
							}
							else
							{
								$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_TO_LOAD_MODULE_OPEN_LINES'), Library::ERROR_FAILED_TO_LOAD_MODULE_OPEN_LINES, __METHOD__, $connector));
							}

							if(!$result->isSuccess())
							{
								$status->setConnection(false);
								$status->setRegister(false);
							}
							break;

						case 'network':
							if(Loader::includeModule(Library::MODULE_ID_OPEN_LINES))
							{
								$network = new Network();
								if (!$network->updateConnector($line, $params))
								{
									$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_TO_UPDATE_CONNECTOR'), Library::ERROR_FAILED_TO_UPDATE_CONNECTOR, __METHOD__, $connector));
								}
								else
								{
									$dataStatus = $status->getData();
									$dataStatus = array_merge($dataStatus, $params);
									$status->setData($dataStatus);
								}
							}
							else
							{
								$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_TO_LOAD_MODULE_OPEN_LINES'), Library::ERROR_FAILED_TO_LOAD_MODULE_OPEN_LINES, __METHOD__, $connector));
							}

							if(!$result->isSuccess())
							{
								$status->setConnection(false);
								$status->setRegister(false);
							}
							break;

						case 'telegrambot':
						case 'botframework':
							$output = new Output($connector, $line);
							$saved = $output->saveSettings($params);

							if($saved->isSuccess())
							{
								$testConnect = $output->testConnect();
								if($testConnect->isSuccess())
								{
									$status->setConnection(true);

									$register = $output->register();
									if(!$register->isSuccess())
									{
										$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_REGISTER_CONNECTOR'), Library::ERROR_FAILED_REGISTER_CONNECTOR, __METHOD__, $connector));
										$result->addErrors($testConnect->getErrors());

										$status->setRegister(false);
									}
								}
								else
								{
									$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_TO_TEST_CONNECTOR'), Library::ERROR_FAILED_TO_TEST_CONNECTOR, __METHOD__, $connector));
									$result->addErrors($testConnect->getErrors());

									$status->setConnection(false);
								}
							}
							else
							{
								$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_TO_SAVE_SETTINGS_CONNECTOR'), Library::ERROR_FAILED_TO_SAVE_SETTINGS_CONNECTOR, __METHOD__, $connector));
								$result->addErrors($saved->getErrors());

								$status->setConnection(false);
								$status->setRegister(false);
							}
							break;

						case 'vkgroup':
						case 'facebook':
						case 'facebookcomments':
						case 'fbinstagram':
						case 'instagram':
						default:
							$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FEATURE_IS_NOT_SUPPORTED'), Library::ERROR_FEATURE_IS_NOT_SUPPORTED, __METHOD__, $connector));
							break;
					}
				}

				if($result->isSuccess())
				{
					$status->setActive(true);
					$status->setConnection(true);
					$status->setRegister(true);
				}

				$status->setError(false);
				Status::save();
				Status::sendUpdateEvent();

				self::cleanCacheConnector($line, $cacheId);
			}
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

		if(empty($line) || empty($connector))
		{
			$result->addError(new Error(Loc::getMessage('IMCONNECTOR_EMPTY_PARAMETRS'), Library::ERROR_IMCONNECTOR_EMPTY_PARAMETRS, __METHOD__, array('line' => $line, 'connector' => $connector)));
		}
		else
		{
			if(!self::isConnector($connector))
			{
				$result->addError(new Error(Loc::getMessage('IMCONNECTOR_NOT_AVAILABLE_CONNECTOR'), Library::ERROR_NOT_AVAILABLE_CONNECTOR, __METHOD__, $connector));
			}
			else
			{
				$status = Status::getInstance($connector, $line);
				$cacheId = self::getCacheIdConnector($line, $connector);

				if(!$status->getActive())
					$result->addError(new Error(Loc::getMessage('IMCONNECTOR_DELETE_NOT_EXISTING_CONNECTOR'), Library::ERROR_DELETE_NOT_EXISTING_CONNECTOR, __METHOD__, $connector));

				if($result->isSuccess())
				{
					switch ($connector)
					{
						case 'livechat':
							if(Loader::includeModule(Library::MODULE_ID_OPEN_LINES))
							{
							$liveChatManager = new LiveChatManager($line);
							if (!$liveChatManager->delete())
								$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_TO_DELETE_CONNECTOR'), Library::ERROR_FAILED_TO_DELETE_CONNECTOR,  __METHOD__, $connector));
							}
							else
							{
								$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_TO_LOAD_MODULE_OPEN_LINES'), Library::ERROR_FAILED_TO_LOAD_MODULE_OPEN_LINES, __METHOD__, $connector));
							}

							break;

						case 'network':
							if(Loader::includeModule(Library::MODULE_ID_OPEN_LINES))
							{
								$network = new Network();
								if (!$network->unRegisterConnector($line))
									$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_TO_DELETE_CONNECTOR'), Library::ERROR_FAILED_TO_DELETE_CONNECTOR, __METHOD__, $connector));
							}
							else
							{
								$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_TO_LOAD_MODULE_OPEN_LINES'), Library::ERROR_FAILED_TO_LOAD_MODULE_OPEN_LINES, __METHOD__, $connector));
							}
							break;

						case 'facebook':
						case 'vkgroup':
						case 'telegrambot':
						case 'botframework':
						case 'facebookcomments':
						case 'fbinstagram':
						case 'instagram':
						case 'avito':
						case 'wechat':
							$output = new Output($connector, $line);
							$rawDelete = $output->deleteConnector();
							if(!$rawDelete->isSuccess())
							{
								$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FAILED_TO_DELETE_CONNECTOR'), Library::ERROR_FAILED_TO_DELETE_CONNECTOR, __METHOD__, $connector));
								$result->addErrors($rawDelete->getErrors());

							}
							break;

						default:
							$result->addError(new Error(Loc::getMessage('IMCONNECTOR_FEATURE_IS_NOT_SUPPORTED'), Library::ERROR_FEATURE_IS_NOT_SUPPORTED, __METHOD__, $connector));
							break;
					}
				}

				if($result->isSuccess())
				{
					Status::delete($connector, $line);
				}

				self::cleanCacheConnector($line, $cacheId);
			}
		}

		return $result;
	}
}