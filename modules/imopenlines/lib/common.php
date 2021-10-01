<?php
namespace Bitrix\ImOpenLines;

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Common
{
	const TYPE_BITRIX24 = 'B24';
	const TYPE_CP = 'CP';

	const MODE_AGENT = 'agent';
	const MODE_CRON = 'cron';

	const CACHE_TTL_MONTH = 2700000;

	/**
	 * Unsupported old-fashioned permission check.
	 * @return bool
	 * @deprecated Use Bitrix\ImOpenLines\Security\Permissions instead.
	 */
	public static function hasAccessForAdminPages()
	{
		if (\IsModuleInstalled('bitrix24'))
		{
			return $GLOBALS['USER']->CanDoOperation('bitrix24_config');
		}
		else
		{
			return $GLOBALS["USER"]->IsAdmin();
		}
	}

	public static function getPortalType()
	{
		$type = '';
		if(defined('BX24_HOST_NAME'))
		{
			$type = self::TYPE_BITRIX24;
		}
		else
		{
			$type = self::TYPE_CP;
		}
		return $type;
	}

	/**
	 * @return string
	 */
	public static function getPublicFolder():string
	{
		return
			self::GetPortalType() === self::TYPE_BITRIX24
			|| file_exists($_SERVER['DOCUMENT_ROOT'] . '/openlines/')?
				'/openlines/':
				SITE_DIR . 'services/openlines/';
	}

	/**
	 * @return string
	 */
	public static function getContactCenterPublicFolder():string
	{
		return
			self::GetPortalType() === self::TYPE_BITRIX24
			|| file_exists($_SERVER['DOCUMENT_ROOT'] . '/contact_center/')?
				'/contact_center/':
				SITE_DIR . 'services/contact_center/';
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

	public static function getServerAddress()
	{
		$publicUrl = \Bitrix\Main\Config\Option::get("imopenlines", "portal_url");

		if ($publicUrl != '')
			return $publicUrl;
		else
			return (\Bitrix\Main\Context::getCurrent()->getRequest()->isHttps() ? "https" : "http")."://".$_SERVER['SERVER_NAME'].(in_array($_SERVER['SERVER_PORT'], Array(80, 443))?'':':'.$_SERVER['SERVER_PORT']);
	}

	public static function getExecMode()
	{
		$execMode = \Bitrix\Main\Config\Option::get("imopenlines", "exec_mode");

		if (!empty($execMode) && in_array($execMode, array(self::MODE_AGENT, self::MODE_CRON)))
			return $execMode;
		else
			return self::MODE_AGENT;
	}

	public static function deleteBrokenSession()
	{
		$orm = \Bitrix\ImOpenLines\Model\SessionTable::getList(array(
			'select' => Array('ID'),
			'filter' => Array('=CONFIG.ID' => '')
		));
		while ($session = $orm->fetch())
		{
			Session::deleteSession($session['ID']);
		}

		$orm = \Bitrix\ImOpenLines\Model\SessionCheckTable::getList(array(
			'filter' => Array('=SESSION.ID' => '')
		));
		while ($session = $orm->fetch())
		{
			\Bitrix\ImOpenLines\Model\SessionCheckTable::delete($session['SESSION_ID']);
		}

		if (\Bitrix\Main\Loader::includeModule('pull'))
		{
			\Bitrix\Pull\Event::send();
		}

		return '\Bitrix\ImOpenLines\Common::deleteBrokenSession();';
	}

	public static function setUserAgrees($params)
	{
		if (empty($params['USER_CODE']))
			return false;

		$params['AGREEMENT_ID'] = intval($params['AGREEMENT_ID']);
		if ($params['AGREEMENT_ID'] <= 0)
			return false;

		$params['FLAG'] = $params['FLAG'] == 'N'? 'N': 'Y';

		\Bitrix\Imopenlines\Model\UserRelationTable::update($params['USER_CODE'], Array('AGREES' => $params['FLAG']));

		if ($params['FLAG'] == 'Y' && \Bitrix\Main\Loader::includeModule('crm'))
		{
			\Bitrix\Main\UserConsent\Consent::addByContext(
				intval($params['AGREEMENT_ID']),
				\Bitrix\Crm\Integration\UserConsent::PROVIDER_CODE,
				intval($params['CRM_ACTIVITY_ID']),
				array('IP' => '', 'URL' => self::getHistoryLink($params['SESSION_ID'], $params['CONFIG_ID']))
			);
		}

		return true;
	}

	public static function getAgreementLink($agreementId, $languageId = null, $iframe = false)
	{
		$agreementId = intval($agreementId);

		$ag = new \Bitrix\Main\UserConsent\Agreement($agreementId);
		$data = $ag->getData();

		return \Bitrix\ImOpenLines\Common::getServerAddress().'/pub/imol.php?id='.$agreementId.'&sec='.$data['SECURITY_CODE'].($iframe? '&iframe=Y': '').($languageId? '&user_lang='.$languageId: '');
	}

	/**
	 * @param $sessionId
	 * @param $configId
	 * @return string
	 */
	public static function getHistoryLink($sessionId, $configId): string
	{
		$sessionId = (int)$sessionId;
		$configId = (int)$configId;

		return self::getServerAddress() . self::getContactCenterPublicFolder() . 'dialog_list/?' . ($configId? 'CONFIG_ID=' . $configId . '&': '') . 'IM_HISTORY=imol|' . $sessionId;
	}

	public static function getBitrixUrlByLang($lang = null)
	{
		$url = '';
		if (\Bitrix\Main\Loader::includeModule('bitrix24'))
		{
			if (!$lang)
			{
				if (defined('B24_LANGUAGE_ID'))
					$lang = B24_LANGUAGE_ID;
				else
					$lang = mb_substr((string)\Bitrix\Main\Config\Option::get('main', '~controller_group_name'), 0, 2);
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
			if (LANGUAGE_ID == 'de')
			{
				$url = 'www.bitrix24.de';
			}
			else if (LANGUAGE_ID == 'ua')
			{
				$url = 'www.bitrix24.ua';
			}
			else if (LANGUAGE_ID == 'kz')
			{
				$url = 'www.bitrix24.kz';
			}
			else if (LANGUAGE_ID == 'by')
			{
				$url = 'www.bitrix24.by';
			}
			else if (LANGUAGE_ID == 'ru')
			{
				$url = 'www.bitrix24.ru';
			}
			else
			{
				$url = 'www.bitrix24.com';
			}
		}

		$partnerId = \Bitrix\Main\Config\Option::get("bitrix24", "partner_id", 0);
		if ($partnerId)
		{
			$url .= '/?p='.$partnerId;
		}

		return "https://".$url;
	}

	public static function setCacheTag($tag, $cacheTtl = self::CACHE_TTL_MONTH)
	{
		if (!is_string($tag))
			return false;

		$app = \Bitrix\Main\Application::getInstance();
		$managedCache = $app->getManagedCache();
		$managedCache->clean("imol_cache_tag_".$tag);
		$managedCache->read($cacheTtl, "imol_cache_tag_".$tag);
		$managedCache->setImmediate("imol_cache_tag_".$tag, true);

		return true;
	}

	public static function getCacheTag($tag, $cacheTtl = self::CACHE_TTL_MONTH)
	{
		if (!is_string($tag))
			return false;

		$app = \Bitrix\Main\Application::getInstance();
		$managedCache = $app->getManagedCache();
		if ($result = $managedCache->read($cacheTtl, "imol_cache_tag_".$tag))
		{
			$result = $managedCache->get("imol_cache_tag_".$tag) === false? false: true;
		}
		return $result;
	}

	public static function removeCacheTag($tag)
	{
		if (!is_string($tag))
			return false;

		$app = \Bitrix\Main\Application::getInstance();
		$managedCache = $app->getManagedCache();
		$managedCache->clean("imol_cache_tag_".$tag);

		return true;
	}

	public static function getWorkTimeEnd($date = null)
	{
		$workTimeEnd = explode('.', \Bitrix\Main\Config\Option::get('calendar', 'work_time_end', '18'));

		if (!($date instanceof \Bitrix\Main\Type\DateTime))
		{
			$date = new \Bitrix\Main\Type\DateTime();
		}
		$date->setTime($workTimeEnd[0], $workTimeEnd[1], 0);

		return $date;
	}

	public static function objectEncode($params)
	{
		if (is_array($params))
		{
			array_walk_recursive($params, function(&$item, $key){
				if ($item instanceof \Bitrix\Main\Type\DateTime)
				{
					$item = date('c', $item->getTimestamp());
				}
			});
		}

		return \CUtil::PhpToJSObject($params);
	}

	/**
	 * @deprecated
	 *
	 * @return int
	 */
	public static function getMaxSessionCount()
	{
		return 100;
	}

	public static function getUserIdByCode(string $userCode)
	{
		if (mb_substr($userCode, 0, 5) === 'imol|')
		{
			$userCode = mb_substr($userCode, 5);
		}

		$entity = \Bitrix\ImOpenLines\Chat::parseLinesChatEntityId($userCode);
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

	public static function depersonalizationLinesUser($userId)
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
		$user->Update($userData['ID'], [
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
			$res = \CGroup::GetGroupUserEx(1);
			while ($row = $res->fetch())
			{
				$users[] = (int)$row['USER_ID'];
			}
		}

		return $users;
	}
}
