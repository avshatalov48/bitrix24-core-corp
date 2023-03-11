<?php
namespace Bitrix\ImOpenLines;

use
	Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Main\UserTable,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Application;

class Common
{
	public const
		TYPE_BITRIX24 = 'B24',
		TYPE_CP = 'CP';

	public const
		MODE_AGENT = 'agent',
		MODE_CRON = 'cron';

	protected const CACHE_TTL_MONTH = 2700000;

	/**
	 * @return string
	 */
	public static function getPortalType(): string
	{
		$type = self::TYPE_CP;
		if (defined('BX24_HOST_NAME'))
		{
			$type = self::TYPE_BITRIX24;
		}

		return $type;
	}

	/**
	 * @return string
	 */
	public static function getPublicFolder(): string
	{
		if (
			self::getPortalType() === self::TYPE_BITRIX24
			|| Main\IO\Directory::isDirectoryExists(Application::getDocumentRoot(). '/openlines/')
		)
		{
			return '/openlines/';
		}

		return SITE_DIR. 'services/openlines/';

	}

	/**
	 * @return string
	 */
	public static function getDialogListUrl(): string
	{
		return self::getPublicFolder(). 'list/';
	}

	/**
	 * @return string
	 */
	public static function getContactCenterPublicFolder(): string
	{
		if (
			self::getPortalType() === self::TYPE_BITRIX24
			|| Main\IO\Directory::isDirectoryExists(Application::getDocumentRoot(). '/contact_center/')
		)
		{
			return '/contact_center/';
		}

		return SITE_DIR . 'services/contact_center/';
	}

	/**
	 * @param string $connectorId
	 * @return string|null
	 */
	public static function getAddConnectorUrl(string $connectorId): ?string
	{
		if (!Loader::includeModule('imconnector'))
		{
			return null;
		}
		$connectors = \Bitrix\ImConnector\Connector::getListConnectorMenu(true);
		if (!array_key_exists($connectorId, $connectors))
		{
			return null;
		}

		return Common::getContactCenterPublicFolder() . 'connector/' . '?ID=' . $connectorId;
	}

	/**
	 * Returns from settings or detects from request external public url.
	 * @return string
	 */
	public static function getServerAddress(): string
	{
		$publicUrl = Option::get('imopenlines', 'portal_url');

		if (empty($publicUrl))
		{
			$context = Application::getInstance()->getContext();
			$scheme = $context->getRequest()->isHttps() ? 'https' : 'http';
			$server = $context->getServer();
			$domain = Option::get('main', 'server_name', '');
			if (empty($domain))
			{
				$domain = $server->getServerName();
			}
			if (preg_match('/^(?<domain>.+):(?<port>\d+)$/', $domain, $matches))
			{
				$domain = $matches['domain'];
				$port = (int)$matches['port'];
			}
			else
			{
				$port = (int)$server->getServerPort();
			}
			$port = in_array($port, [0, 80, 443]) ? '' : ':'.$port;

			$publicUrl = $scheme.'://'.$domain.$port;
		}
		if (!(mb_strpos($publicUrl, 'https://') === 0 || mb_strpos($publicUrl, 'http://') === 0))
		{
			$publicUrl = 'https://' . $publicUrl;
		}

		return $publicUrl;
	}

	/**
	 * Checks availability of the external public url.
	 * @param string $publicUrl Portal public url.
	 * @return Result
	 */
	public static function checkPublicUrl(string $publicUrl): Result
	{
		$result = new Result;

		if (empty($publicUrl))
		{
			$message = Loc::getMessage('IMOL_ERROR_PUBLIC_URL_EMPTY');
			if (empty($message))
			{
				$message = 'Cannot detect a value of the portal public url.';
			}

			return $result->addError(new Error($message, 'PUBLIC_URL_EMPTY'));
		}

		if (
			(mb_strlen($publicUrl) < 11)
			|| !($parsedUrl = \parse_url($publicUrl))
			|| empty($parsedUrl['host'])
			|| strpos($parsedUrl['host'], '.') === false
			|| !in_array($parsedUrl['scheme'], ['http', 'https'])
		)
		{
			$message = Loc::getMessage('IMOL_ERROR_PUBLIC_URL_MALFORMED');
			if (empty($message))
			{
				$message = 'Portal public url is malformed.';
			}

			return $result->addError(new Error($message, 'PUBLIC_URL_MALFORMED'));
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
			$message = Loc::getMessage('IMOL_ERROR_PUBLIC_URL_LOCALHOST', ['#HOST#' => $host]);
			if (empty($message))
			{
				$message = 'Portal public url points to localhost: '.$host;
			}

			return $result->addError(new Error($message, 'PUBLIC_URL_LOCALHOST'));
		}

		$error = (new \Bitrix\Main\Web\Uri($publicUrl))->convertToPunycode();
		if ($error instanceof \Bitrix\Main\Error)
		{
			$message = Loc::getMessage('IMOL_ERROR_CONVERTING_PUNYCODE', ['#HOST#' => $host, '#ERROR#' => $error->getMessage()]);
			if (empty($message))
			{
				$message = 'Error converting hostname '.$host.' to punycode: '.$error->getMessage();
			}

			return $result->addError(new Error($message, 'PUBLIC_URL_MALFORMED'));
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public static function getExecMode(): string
	{
		$execMode = Option::get("imopenlines", "exec_mode");

		if (!empty($execMode) && in_array($execMode, [self::MODE_AGENT, self::MODE_CRON]))
		{
			return $execMode;
		}

		return self::MODE_AGENT;
	}

	/**
	 * @param $params
	 * @return bool
	 */
	public static function setUserAgrees($params): bool
	{
		if (empty($params['USER_CODE']))
		{
			return false;
		}

		$params['AGREEMENT_ID'] = (int)$params['AGREEMENT_ID'];
		if ($params['AGREEMENT_ID'] <= 0)
		{
			return false;
		}

		$params['FLAG'] = $params['FLAG'] == 'N'? 'N': 'Y';

		\Bitrix\Imopenlines\Model\UserRelationTable::update($params['USER_CODE'], Array('AGREES' => $params['FLAG']));

		if ($params['FLAG'] == 'Y' && Main\Loader::includeModule('crm'))
		{
			Main\UserConsent\Consent::addByContext(
				(int)$params['AGREEMENT_ID'],
				\Bitrix\Crm\Integration\UserConsent::PROVIDER_CODE,
				(int)$params['CRM_ACTIVITY_ID'],
				['IP' => '', 'URL' => self::getHistoryLink($params['SESSION_ID'], $params['CONFIG_ID'])]
			);
		}

		return true;
	}

	/**
	 * @param int $agreementId
	 * @param string $languageId
	 * @param bool $iframe
	 * @return string
	 */
	public static function getAgreementLink($agreementId, $languageId = null, $iframe = false): string
	{
		$agreementId = (int)$agreementId;

		$ag = new \Bitrix\Main\UserConsent\Agreement($agreementId);
		$data = $ag->getData();

		return
			self::getServerAddress()
			. '/pub/imol.php?id='.$agreementId
			. '&sec='.$data['SECURITY_CODE']
			. ($iframe ? '&iframe=Y': '')
			. ($languageId ? '&user_lang='.$languageId: '')
		;
	}

	/**
	 * @param int $sessionId
	 * @param int $configId
	 * @return string
	 */
	public static function getHistoryLink($sessionId, $configId): string
	{
		$sessionId = (int)$sessionId;
		$configId = (int)$configId;

		return
			self::getServerAddress()
			. self::getContactCenterPublicFolder()
			. 'dialog_list/?'
			. ($configId? 'CONFIG_ID=' . $configId . '&': '')
			. 'IM_HISTORY=imol|' . $sessionId
		;
	}

	/**
	 * @param string $lang
	 * @return string
	 */
	public static function getBitrixUrlByLang($lang = null): string
	{
		if (Main\Loader::includeModule('bitrix24'))
		{
			if (!$lang)
			{
				if (defined('B24_LANGUAGE_ID'))
				{
					$lang = \B24_LANGUAGE_ID;
				}
				else
				{
					$lang = mb_substr((string)Option::get('main', '~controller_group_name'), 0, 2);
				}
			}

			$areaConfig = \CBitrix24::getAreaConfig($lang);
			if ($areaConfig)
			{
				$url = 'www'.$areaConfig['DEFAULT_DOMAIN'];
			}
			else
			{
				$url = 'www.bitrix24.com';
			}
		}
		else
		{
			if (\LANGUAGE_ID == 'de')
			{
				$url = 'www.bitrix24.de';
			}
			elseif (\LANGUAGE_ID == 'ua')
			{
				$url = 'www.bitrix24.ua';
			}
			elseif (\LANGUAGE_ID == 'kz')
			{
				$url = 'www.bitrix24.kz';
			}
			elseif (\LANGUAGE_ID == 'by')
			{
				$url = 'www.bitrix24.by';
			}
			elseif (\LANGUAGE_ID == 'ru')
			{
				$url = 'www.bitrix24.ru';
			}
			else
			{
				$url = 'www.bitrix24.com';
			}
		}

		$partnerId = Option::get("bitrix24", "partner_id", 0);
		if ($partnerId)
		{
			$url .= '/?p='.$partnerId;
		}

		return "https://".$url;
	}

	/**
	 * @param string $tag
	 * @param int $cacheTtl
	 * @return bool
	 */
	public static function setCacheTag(string $tag, int $cacheTtl = self::CACHE_TTL_MONTH): bool
	{
		$managedCache = Application::getInstance()->getManagedCache();
		$managedCache->clean("imol_cache_tag_".$tag);
		$managedCache->read($cacheTtl, "imol_cache_tag_".$tag);
		$managedCache->setImmediate("imol_cache_tag_".$tag, true);

		return true;
	}

	/**
	 * @param string $tag
	 * @param int $cacheTtl
	 * @return bool
	 */
	public static function getCacheTag(string $tag, int $cacheTtl = self::CACHE_TTL_MONTH): bool
	{
		$managedCache = Application::getInstance()->getManagedCache();
		if ($result = $managedCache->read($cacheTtl, "imol_cache_tag_".$tag))
		{
			$result = $managedCache->get("imol_cache_tag_".$tag) === false ? false : true;
		}

		return $result;
	}

	/**
	 * @param string $tag
	 * @return bool
	 */
	public static function removeCacheTag(string $tag): bool
	{
		Application::getInstance()->getManagedCache()->clean("imol_cache_tag_".$tag);

		return true;
	}

	/**
	 * @param $date
	 * @return Main\Type\DateTime
	 */
	public static function getWorkTimeEnd($date = null): Main\Type\DateTime
	{
		$workTimeEnd = explode('.', Option::get('calendar', 'work_time_end', '18'));

		if (!($date instanceof Main\Type\DateTime))
		{
			$date = new Main\Type\DateTime();
		}
		$date->setTime($workTimeEnd[0], $workTimeEnd[1], 0);

		return $date;
	}

	/**
	 * @param $params
	 * @return string
	 */
	public static function objectEncode($params): string
	{
		if (is_array($params))
		{
			array_walk_recursive($params, function(&$item, $key){
				if ($item instanceof Main\Type\DateTime)
				{
					$item = date('c', $item->getTimestamp());
				}
			});
		}

		return \CUtil::phpToJSObject($params);
	}

	/**
	 * @param string $userCode
	 * @return false|mixed
	 */
	public static function getUserIdByCode(string $userCode)
	{
		if (mb_substr($userCode, 0, 5) === 'imol|')
		{
			$userCode = mb_substr($userCode, 5);
		}

		$entity = Chat::parseLinesChatEntityId($userCode);
		if (empty($entity['connectorUserId']))
		{
			return false;
		}

		$userData = UserTable::getList([
			'select' => ['ID', 'EXTERNAL_AUTH_ID'],
			'filter' => ['=ID' => $entity['connectorUserId']]
		])->fetch();
		if ($userData['EXTERNAL_AUTH_ID'] !== 'imconnector')
		{
			return false;
		}

		return $userData['ID'];
	}

	/**
	 * @param int $userId
	 * @return bool
	 */
	public static function depersonalizationLinesUser($userId): bool
	{
		$userData = UserTable::getList([
			'select' => ['ID', 'EXTERNAL_AUTH_ID', 'PERSONAL_PHOTO', ],
			'filter' => ['=ID' => $userId]
		])->fetch();
		if ($userData['EXTERNAL_AUTH_ID'] !== 'imconnector')
		{
			return false;
		}

		$photo = '';
		if ($userData['PERSONAL_PHOTO'])
		{
			$photo = [
				'del' => 'Y',
				'old_file' => $userData['PERSONAL_PHOTO'],
			];
		}

		$user = new \CUser();
		$user->update($userData['ID'], [
			'NAME' => Loc::getMessage('IMOL_COMMON_GUEST_NAME'),
			'LAST_NAME' => '',
			'EMAIL' => $userData['ID'].'@temporary.temp',
			'PERSONAL_PHOTO' => $photo,
			'PERSONAL_PROFESSION' => '',
			'PERSONAL_WWW' => '',
			'PERSONAL_GENDER' => '',
			'WORK_POSITION' => '',
		]);

		return true;
	}

	/**
	 * List of administrator users.
	 * @return int[]
	 */
	public static function getAdministrators(): array
	{
		$users = [];
		if (Loader::includeModule('bitrix24'))
		{
			$users = \CBitrix24::getAllAdminId();
		}
		else
		{
			$res = \CGroup::getGroupUserEx(1);
			while ($row = $res->fetch())
			{
				$users[] = (int)$row['USER_ID'];
			}
		}

		return $users;
	}
}
