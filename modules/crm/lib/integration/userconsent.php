<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\Integration;

use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\EventResult;
use Bitrix\Crm\Requisite;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Format;
use Bitrix\Crm\EntityAddress;
use Bitrix\Main\UserConsent\Agreement;
use Bitrix\Main\UserConsent\Internals\AgreementTable;
use Bitrix\Main\UserConsent\Intl;
use Bitrix\Main\UserConsent\Text;
use Bitrix\Main\UserConsent\Policy;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

use Bitrix\Crm\WebForm\Manager as WebFormManager;
use Bitrix\Crm\WebForm\Internals\FormTable;
use Bitrix\Crm\WebForm\Internals\QueueTable;

Loc::loadLanguageFile(__FILE__);

class UserConsent
{
	const PROVIDER_CODE = 'crm/activity';
	const DATA_PROVIDER_CODE = 'crm/requisites';

	/**
	 * Notify.
	 *
	 * @return bool
	 */
	public static function notify()
	{
		if(!Loader::includeModule('im'))
		{
			return false;
		}

		$lang = Context::getCurrent()->getLanguage();
		if(!Policy::isRequired($lang) || !Policy::hasText($lang))
		{
			return false;
		}

		$intl = new Intl($lang);
		if (!$intl->getNotifyText())
		{
			return false;
		}

		$text = $intl->getNotifyText();
		$text .= " <a href=\"" . htmlspecialcharsbx(WebFormManager::getUrl()) . "\">";
		$text .= Loc::getMessage('CRM_USER_CONSENT_NOTIFY_TEXT_BTN');
		$text .= "</a>";

		$serverName = (Context::getCurrent()->getRequest()->isHttps() ? "https" : "http") . "://";
		if(defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0)
		{
			$serverName .= SITE_SERVER_NAME;
		}
		else
		{
			$serverName .= Option::get("main", "server_name", "");
		}
		$textOut = $text . " (". $serverName . WebFormManager::getUrl() . ")";

		$userList = array(1);

		// from forms
		$formUserDb = FormTable::getList(array(
			'select' => array('ACTIVE_CHANGE_BY'),
		));
		while ($formUser = $formUserDb->fetch())
		{
			$userList[] = $formUser['ACTIVE_CHANGE_BY'];
		}

		// from queue
		$queueUserDb = QueueTable::getList(array(
			'select' => array('USER_ID'),
		));
		while ($queueUser = $queueUserDb->fetch())
		{
			$userList[] = $queueUser['USER_ID'];
		}

		TrimArr($userList);
		$userList = array_unique($userList);
		foreach ($userList as $userId)
		{
			$imNotifyFields = array(
				"TO_USER_ID" => $userId,
				"FROM_USER_ID" => 1,
				"NOTIFY_TYPE" => IM_NOTIFY_FROM,
				"NOTIFY_MODULE" => "crm",
				"NOTIFY_MESSAGE" => $text,
				"NOTIFY_MESSAGE_OUT" => $textOut
			);
			\CIMNotify::Add($imNotifyFields);
		}

		return true;
	}

	/**
	 * Event `main/OnUserConsentProviderList` handler.
	 *
	 * @return EventResult
	 */
	public static function onProviderList()
	{
		$parameters = array(
			array(
				'CODE' => self::PROVIDER_CODE,
				'NAME' => Loc::getMessage('CRM_USER_CONSENT_PROVIDER_NAME'),
				'DATA' => function ($id = null)
				{
					return array(
						'NAME' => Loc::getMessage('CRM_USER_CONSENT_PROVIDER_ITEM_NAME', array('%id%' => $id)),
						'URL' => str_replace('%id%', $id, '/crm/activity/?open_view=%id%')
					);
				}
			)
		);

		return new EventResult(EventResult::SUCCESS, $parameters, 'crm');
	}

	/**
	 * Event `main/OnUserConsentDataProviderList` handler.
	 *
	 * @return EventResult
	 */
	public static function onDataProviderList()
	{
		/** @var static $className Class name */
		$className = __CLASS__;

		$parameters = array(
			array(
				'CODE' => self::DATA_PROVIDER_CODE,
				'NAME' => Loc::getMessage('CRM_USER_CONSENT_DATA_PROVIDER_NAME'),
				'EDIT_URL' => '/crm/configs/mycompany/',
				'DATA' => function () use ($className)
				{
					\Bitrix\Main\Loader::includeModule('crm');
					$reqData = $className::getRequisites();
					if (!is_array($reqData))
					{
						$reqData = array();
					}

					$data = array();
					$intl = new \Bitrix\Main\UserConsent\Intl(LANGUAGE_ID);
					if (isset($reqData['RQ_OGRN']) && $reqData['RQ_OGRN'] && LANGUAGE_ID == 'ru')
					{
						$data['COMPANY_NAME'] = $intl->getPhrase('COMPANY_NAME');
					}
					else if (isset($reqData['RQ_OGRNIP']) && $reqData['RQ_OGRNIP'] && LANGUAGE_ID == 'ru')
					{
						$data['COMPANY_NAME'] = $intl->getPhrase('IP_NAME');
					}
					else if (isset($reqData['RQ_COMPANY_NAME']) && $reqData['RQ_COMPANY_NAME'])
					{
						$data['COMPANY_NAME'] = $reqData['RQ_COMPANY_NAME'];
					}
					else if (isset($reqData['RQ_NAME']) && $reqData['RQ_NAME'])
					{
						$data['COMPANY_NAME'] = $reqData['RQ_NAME'];
					}
					$data['COMPANY_NAME'] = Text::replace($data['COMPANY_NAME'], $reqData);

					if (isset($reqData['COMPANY_ADDRESS']) && $reqData['COMPANY_ADDRESS'])
					{
						$data['COMPANY_ADDRESS'] = $reqData['COMPANY_ADDRESS'];
					}

					return $data;
				}
			)
		);

		return new EventResult(EventResult::SUCCESS, $parameters, 'crm');
	}

	/**
	 * Get requisites.
	 *
	 * @return array|null
	 */
	public static function getRequisites()
	{
		// get my company id
		$myCompanyId = Requisite\EntityLink::getDefaultMyCompanyId();
		if (!$myCompanyId)
		{
			return null;
		}

		// get requisites
		$req = new EntityRequisite;
		$res = $req->getList(array(
			'filter' => array(
				'=ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
				'=ENTITY_ID' => $myCompanyId
			)
		));
		$data = $res->fetch();
		if (!$data)
		{
			return null;
		}

		// prepare requisites
		$result = array();
		foreach ($data as $key => $value)
		{
			if (substr($key, 0, 3) == 'RQ_')
			{
				$result[$key] = $value;
			}
		}

		// format person name
		$result[EntityRequisite::PERSON_FULL_NAME] = \CUser::formatName(
			Format\PersonNameFormatter::getFormat(),
			array(
				'NAME' => $result[EntityRequisite::PERSON_FIRST_NAME],
				'LAST_NAME' => $result[EntityRequisite::PERSON_LAST_NAME],
				'SECOND_NAME' => $result[EntityRequisite::PERSON_SECOND_NAME],
			)
		);

		// get address requisites
		$addresses = EntityRequisite::getAddresses($data['ID']);
		$addressTypes = array(
			EntityAddress::Registered
		);

		$address = null;
		foreach ($addressTypes as $addressType)
		{
			if (isset($addresses[$addressType]))
			{
				$address = $addresses[$addressType];
			}
		}

		if (!$address && count($addresses) > 0)
		{
			$address = current($addresses);
		}

		if ($address && is_array($address))
		{
			$address = Format\EntityAddressFormatter::format($address, array(
				'SEPARATOR' => Format\AddressSeparator::Comma
			));
		}
		else
		{
			// get address from entity fields
			$address = \CCrmCompany::getByID($myCompanyId, false);
			if (!is_array($address))
			{
				$address = array();
			}
			if ($address['REG_ADDRESS'])
			{
				$addressTypeId =  EntityAddress::Registered;
			}
			else
			{
				$addressTypeId =  EntityAddress::Primary;
			}

			$address = Format\CompanyAddressFormatter::format($address, array(
				'SEPARATOR' => Format\AddressSeparator::Comma,
				'TYPE_ID' => $addressTypeId
			));
		}

		$result['COMPANY_ADDRESS'] = $address;

		return $result;
	}

	/**
	 * Get default agreement id.
	 *
	 * @return integer|null
	 */
	public static function getDefaultAgreementId()
	{
		if (Loader::includeModule('bitrix24'))
		{
			$lang = \CBitrix24::getPortalZone();
		}
		else
		{
			$lang = Context::getCurrent()->getLanguage();
		}

		if(!Policy::isRequired($lang) || !Policy::hasText($lang))
		{
			return null;
		}

		$code = 'crm_def';
		$existed = AgreementTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=CODE' => $code),
			'limit' => 1
		));
		if ($agreement = $existed->fetch())
		{
			return $agreement['ID'];
		}

		$addResult = AgreementTable::add(array(
			"CODE" => $code,
			"NAME" => Loc::getMessage('CRM_USER_CONSENT_DEF_NAME'),
			"TYPE" => Agreement::TYPE_STANDARD,
			"LANGUAGE_ID" => $lang,
			"DATA_PROVIDER" => self::DATA_PROVIDER_CODE,
		));
		if (!$addResult->isSuccess())
		{
			return null;
		}

		return $addResult->getId();
	}

	/**
	 * Notify.
	 *
	 * @return bool
	 */
	public static function applyDefaultAgreement()
	{
		$agreementId = self::getDefaultAgreementId();
		if (!$agreementId)
		{
			return false;
		}

		// update forms
		$forms = FormTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'=AGREEMENT_ID' => null,
			)
		));
		foreach ($forms as $form)
		{
			$updateResult = FormTable::update($form['ID'], array(
				'USE_LICENCE' => 'Y',
				'LICENCE_BUTTON_IS_CHECKED' => 'Y',
				'AGREEMENT_ID' => $agreementId,
			));
			$updateResult->isSuccess();
		}

		return true;
	}

	/**
	 * Install default agreement.
	 *
	 * @return bool
	 */
	public static function install()
	{
		if (self::applyDefaultAgreement())
		{
			return true;//self::notify();
		}

		return false;
	}
}