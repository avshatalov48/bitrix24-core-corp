<?php
namespace Bitrix\Crm\Restriction;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Crm;

Loc::loadMessages(__FILE__);

class Bitrix24SearchLimitRestriction extends Bitrix24QuantityRestriction
{
	public function __construct($name = '', $limit = 0)
	{
		$htmlInfo = null;
		$popupInfo = array(
			'ID' => 'crm_entity_search_limit',
			'TITLE' => Loc::getMessage('CRM_B24_SEARCH_LIMIT_RESTRICTION_TITLE'),
			'CONTENT' => Loc::getMessage('CRM_B24_SEARCH_LIMIT_RESTRICTION_LIMIT_CONTENT')
		);
		parent::__construct($name, $limit, $htmlInfo, $popupInfo);
	}

	public function isExceeded($entityTypeID)
	{
		$limit = $this->getQuantityLimit();
		if($limit <= 0)
		{
			return false;
		}

		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			$count = \CCrmLead::GetTotalCount();
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			$count = \CCrmDeal::GetTotalCount();
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			$count = \CCrmCompany::GetTotalCount();
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			$count = \CCrmContact::GetTotalCount();
		}
		elseif($entityTypeID === \CCrmOwnerType::Quote)
		{
			$count = \CCrmQuote::GetTotalCount();
		}
		elseif($entityTypeID === \CCrmOwnerType::Invoice)
		{
			$count = \CCrmInvoice::GetTotalCount();
		}
		elseif($entityTypeID === \CCrmOwnerType::Order)
		{
			$count = \Bitrix\Crm\Order\Manager::countTotal();
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		return ($count > $limit);
	}

	/**
	 * @param array|null $params
	 * @return array|null
	 */
	public function prepareStubInfo(array $params = null)
	{
		if($params === null)
		{
			$params = array();
		}

		if(!isset($params['REPLACEMENTS']))
		{
			$params['REPLACEMENTS'] = array();
		}
		$params['REPLACEMENTS']['#LIMIT#'] = $this->getQuantityLimit();

		$entityTypeName = isset($params['ENTITY_TYPE_ID']) ? \CCrmOwnerType::ResolveName($params['ENTITY_TYPE_ID']) : '';
		if($entityTypeName !== '')
		{
			/*
			 * CRM_B24_SEARCH_LIMIT_RESTRICTION_LEAD_TITLE
			 * CRM_B24_SEARCH_LIMIT_RESTRICTION_DEAL_TITLE
			 * CRM_B24_SEARCH_LIMIT_RESTRICTION_CONTACT_TITLE
			 * CRM_B24_SEARCH_LIMIT_RESTRICTION_COMPANY_TITLE
			 * CRM_B24_SEARCH_LIMIT_RESTRICTION_QUOTE_TITLE
			 * CRM_B24_SEARCH_LIMIT_RESTRICTION_INVOICE_TITLE
			 */
			$title = Loc::getMessage("CRM_B24_SEARCH_LIMIT_RESTRICTION_{$entityTypeName}_TITLE");
			$title = $params['GLOBAL_SEARCH'] ? str_replace('<br>', '', $title) : $title;
			$params['TITLE'] = $title;
		}
		return $this->restrictionInfo->prepareStubInfo($params);
	}
}