<?php
namespace Bitrix\Crm\Integration;

use Bitrix\Catalog;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\RequisiteAddress;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
class SaleManager
{
	public static function ensureQuotePaySystemsCreated()
	{
		if(!Loader::includeModule('sale'))
		{
			return;
		}

		$siteID = '';
		$languageID = '';

		$dbSites = \CSite::GetList('sort', 'desc', array('DEFAULT' => 'Y', 'ACTIVE' => 'Y'));
		$defaultSite = is_object($dbSites) ? $dbSites->Fetch() : null;
		if(is_array($defaultSite))
		{
			$siteID = $defaultSite['LID'];
			$languageID = $defaultSite['LANGUAGE_ID'];
		}

		if($siteID === '')
		{
			$siteID = 's1';
		}

		if($languageID === '')
		{
			$languageID = 'ru';
		}

		$paySysName = "quote_{$languageID}";
		$paySystems = array();

		$newPSContactParams = $newPSCompanyParams = false;
		if (EntityPreset::isUTFMode())
			$rqCountryId = \CCrmPaySystem::getPresetCountryIdByPS('quote', $languageID);
		else
			$rqCountryId = EntityPreset::getCurrentCountryId();
		if ($rqCountryId > 0 && in_array($rqCountryId, EntityRequisite::getAllowedRqFieldCountries(), true))
		{
			/*$newPSContactParams = (Main\Config\Option::get('crm', '~CRM_TRANSFER_REQUISITES_TO_CONTACT', 'N') !== 'Y');
			$newPSCompanyParams = (Main\Config\Option::get('crm', '~CRM_TRANSFER_REQUISITES_TO_COMPANY', 'N') !== 'Y');*/
			$newPSContactParams = $newPSCompanyParams = true;
		}

		$customPaySystemPath = \COption::GetOptionString('sale', 'path2user_ps_files', '');
		if($customPaySystemPath === '')
		{
			$customPaySystemPath = BX_ROOT.'/php_interface/include/sale_payment/';
		}

		$personTypeIDs = \CCrmPaySystem::getPersonTypeIDs();
		if(isset($personTypeIDs['COMPANY']))
		{
			$psParams = array(
				'DATE_INSERT' => array('TYPE' => 'ORDER', 'VALUE' => 'DATE_BILL_DATE'),
				'DATE_PAY_BEFORE' => array('TYPE' => 'ORDER', 'VALUE' => 'DATE_PAY_BEFORE'),
				'BUYER_NAME' => array(
					'TYPE' => $newPSCompanyParams ? 'REQUISITE' : 'PROPERTY',
					'VALUE' => $newPSCompanyParams ? 'RQ_COMPANY_NAME|'.$rqCountryId : 'COMPANY'
				),
				'BUYER_INN' => array(
					'TYPE' => $newPSCompanyParams ? 'REQUISITE' : 'PROPERTY',
					'VALUE' => $newPSCompanyParams ? 'RQ_INN|'.$rqCountryId : 'INN'
				),
				'BUYER_ADDRESS' => array(
					'TYPE' => $newPSCompanyParams ? 'REQUISITE' : 'PROPERTY',
					'VALUE' => $newPSCompanyParams ?
						'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$rqCountryId : 'COMPANY_ADR'
				),
				'BUYER_PHONE' => array(
					'TYPE' => $newPSCompanyParams ? 'REQUISITE' : 'PROPERTY',
					'VALUE' => $newPSCompanyParams ? 'RQ_PHONE|'.$rqCountryId : 'PHONE'
				),
				'BUYER_FAX' => array(
					'TYPE' => $newPSCompanyParams ? 'REQUISITE' : 'PROPERTY',
					'VALUE' => $newPSCompanyParams ? 'RQ_FAX|'.$rqCountryId : 'FAX'
				),
				'BUYER_PAYER_NAME' => array(
					'TYPE' => $newPSCompanyParams ? 'REQUISITE' : 'PROPERTY',
					'VALUE' => $newPSCompanyParams ? 'RQ_CONTACT|'.$rqCountryId : 'CONTACT_PERSON'
				)
			);
			foreach (\CCrmPaySystem::getDefaultBuyerParams('CRM_COMPANY', 'quote', $rqCountryId)
						as $paramName => $paramValue)
			{
				$psParams[$paramName] = $paramValue;
			}
			foreach (\CCrmPaySystem::getDefaultMyCompanyParams('quote', $rqCountryId) as $paramName => $paramValue)
				$psParams[$paramName] = $paramValue;
			$psParams['COMMENT1'] = array('TYPE' => 'ORDER', 'VALUE' => 'USER_DESCRIPTION');
			$paySystems[] = array(
				'NAME' => Loc::getMessage('CRM_PS_QUOTE_COMPANY', null, $languageID),
				'SORT' => 200,
				'DESCRIPTION' => '',
				'ACTION' => array(
					array(
						'PERSON_TYPE_ID' => $personTypeIDs['COMPANY'],
						'NAME' => Loc::getMessage('CRM_PS_QUOTE_COMPANY', null, $languageID),
						'ACTION_FILE' => "$customPaySystemPath{$paySysName}",
						'RESULT_FILE' => '',
						'NEW_WINDOW' => 'Y',
						'PARAMS' => serialize($psParams),
						'HAVE_PAYMENT' => 'Y',
						'HAVE_ACTION' => 'N',
						'HAVE_RESULT' => 'N',
						'HAVE_PREPAY' => 'N',
						'HAVE_RESULT_RECEIVE' => 'N'
					)
				)
			);
		}

		if(isset($personTypeIDs['CONTACT']))
		{
			$psParams = array(
				'DATE_INSERT' => array('TYPE' => 'ORDER', 'VALUE' => 'DATE_BILL_DATE'),
				'DATE_PAY_BEFORE' => array('TYPE' => 'ORDER', 'VALUE' => 'DATE_PAY_BEFORE'),
				'BUYER_NAME' => array(
					'TYPE' => $newPSContactParams ? 'REQUISITE' : 'PROPERTY',
					'VALUE' => $newPSContactParams ? 'RQ_NAME|'.$rqCountryId : 'FIO'
				),
				'BUYER_INN' => array(
					'TYPE' => $newPSContactParams ? 'REQUISITE' : 'PROPERTY',
					'VALUE' => $newPSContactParams ? 'RQ_INN|'.$rqCountryId : 'INN'
				),
				'BUYER_ADDRESS' => array(
					'TYPE' => $newPSContactParams ? 'REQUISITE' : 'PROPERTY',
					'VALUE' => $newPSContactParams ?
						'RQ_ADDR_'.RequisiteAddress::Primary.'|'.$rqCountryId : 'ADDRESS'
				),
				'BUYER_PHONE' => array(
					'TYPE' => $newPSContactParams ? 'REQUISITE' : 'PROPERTY',
					'VALUE' => $newPSContactParams ? 'RQ_PHONE|'.$rqCountryId : 'PHONE'
				),
				'BUYER_FAX' => array('TYPE' => '', 'VALUE' => ''),
				'BUYER_PAYER_NAME' => array('TYPE' => '', 'VALUE' => '')
			);
			foreach (
				\CCrmPaySystem::getDefaultBuyerParams('CRM_CONTACT', 'quote', $rqCountryId)
				as $paramName => $paramValue
			)
			{
				$psParams[$paramName] = $paramValue;
			}
			foreach (\CCrmPaySystem::getDefaultMyCompanyParams('quote', $rqCountryId) as $paramName => $paramValue)
				$psParams[$paramName] = $paramValue;
			$psParams['COMMENT1'] = array('TYPE' => 'ORDER', 'VALUE' => 'USER_DESCRIPTION');
			$paySystems[] = array(
				'NAME' => Loc::getMessage('CRM_PS_QUOTE_CONTACT', null, $languageID),
				'SORT' => 300,
				'DESCRIPTION' => '',
				'ACTION' => array(
					array(
						'PERSON_TYPE_ID' => $personTypeIDs['CONTACT'],
						'NAME' => Loc::getMessage('CRM_PS_QUOTE_CONTACT', null, $languageID),
						'ACTION_FILE' => "$customPaySystemPath{$paySysName}",
						'RESULT_FILE' => '',
						'NEW_WINDOW' => 'Y',
						'PARAMS' => serialize($psParams),
						'HAVE_PAYMENT' => 'Y',
						'HAVE_ACTION' => 'N',
						'HAVE_RESULT' => 'N',
						'HAVE_PREPAY' => 'N',
						'HAVE_RESULT_RECEIVE' => 'N'
					)
				)
			);
		}

		$currencyID = \CCrmCurrency::GetBaseCurrencyID();
		foreach($paySystems as $paySystem)
		{
			$dbSalePaySystem = \CSalePaySystem::GetList(
				array(),
				array('LID' => $siteID, 'NAME' => $paySystem['NAME']),
				false,
				false,
				array('ID')
			);

			if(!$dbSalePaySystem->Fetch())
			{
				$paySystemID = \CSalePaySystem::Add(
					array(
						'NAME' => $paySystem['NAME'],
						'DESCRIPTION' => $paySystem['DESCRIPTION'],
						'SORT' => $paySystem['SORT'],
						'LID' => $siteID,
						'CURRENCY' => $currencyID,
						'ACTIVE' => 'Y'
					)
				);

				if($paySystemID > 0)
				{
					foreach($paySystem['ACTION'] as &$action)
					{
						$action['PAY_SYSTEM_ID'] = $paySystemID;
						\CSalePaySystemAction::Add($action);
					}
					unset($action);
				}
			}
		}
		unset($paySystem);
	}

	public static function createVatZero()
	{
		\Bitrix\Main\Config\Option::set('crm', 'check_vat_zero', '0', '');
		if(!Loader::includeModule('catalog'))
		{
			return;
		}

		$siteID = '';
		$languageID = '';

		$dbSites = \CSite::GetList('sort', 'desc', array('DEFAULT' => 'Y', 'ACTIVE' => 'Y'));
		$defaultSite = is_object($dbSites) ? $dbSites->Fetch() : null;
		if(is_array($defaultSite))
		{
			$siteID = $defaultSite['LID'];
			$languageID = $defaultSite['LANGUAGE_ID'];
		}

		if($siteID === '')
		{
			$siteID = 's1';
		}

		if($languageID === '')
		{
			$languageID = 'ru';
		}

		\Bitrix\Main\Config\Option::set('crm', 'check_vat_zero', '-1', '');
		if ($languageID == 'ru')
		{
			\Bitrix\Main\Config\Option::set('crm', 'check_vat_zero', '-2', '');
			$vat = Catalog\Model\Vat::getRow([
				'select' => [
					'ID',
				],
				'filter' => [
					'=EXCLUDE_VAT' => 'Y',
				],
			]);
			if ($vat === null)
			{
				\Bitrix\Main\Config\Option::set('crm', 'check_vat_zero', '-3', '');
				$result = Catalog\Model\Vat::add([
					'ACTIVE' => 'Y',
					'SORT' => '100',
					'NAME' => Loc::getMessage('CRM_VAT_ZERO', null, $languageID),
					'EXCLUDE_VAT' => 'Y',
					'RATE' => null,
				]);
				if ($result->isSuccess())
				{
					$vatID = (int)$result->getId();
				}
				else
				{
					$vatID = -4;
				}
				\Bitrix\Main\Config\Option::set('crm', 'check_vat_zero', $vatID, '');
			}
		}
	}
}
