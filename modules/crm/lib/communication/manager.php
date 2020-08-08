<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Communication;

use Bitrix\Crm;

/**
 * Class Manager
 *
 * @package Bitrix\Crm\Communication
 */
class Manager
{
	public static function resolveEntityCommunicationData($entityTypeID, $entityID, array $typeIDs = null)
	{
		if($entityTypeID === \CCrmOwnerType::Contact)
		{
			return self::getCommunicationData(
				\CCrmOwnerType::Contact,
				array($entityID),
				$typeIDs,
				array('deduplicate' => true)
			);
		}

		if($entityTypeID === \CCrmOwnerType::Company)
		{
			$results = self::getCommunicationData(
				$entityTypeID,
				array($entityID),
				$typeIDs,
				array('deduplicate' => true)
			);
			$contactsIDs = Crm\Binding\ContactCompanyTable::getCompanyContactIDs($entityID);
			if(!empty($contactsIDs))
			{
				$results = array_merge(
					$results,
					self::getCommunicationData(
						\CCrmOwnerType::Contact,
						$contactsIDs,
						$typeIDs,
						array('deduplicate' => true)
					)
				);
			}
			return $results;
		}

		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			$dbResult = \CCrmLead::GetListEx(
				array(),
				array('ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'IS_RETURN_CUSTOMER', 'COMPANY_ID')
			);

			$entityFields = $dbResult->Fetch();
			if(!is_array($entityFields))
			{
				return array();
			}

			if(!(isset($entityFields['IS_RETURN_CUSTOMER']) && $entityFields['IS_RETURN_CUSTOMER'] === 'Y'))
			{
				$results = self::getCommunicationData(
					\CCrmOwnerType::Lead,
					array($entityID),
					$typeIDs,
					array('deduplicate' => true)
				);
			}
			else
			{
				$results = array();

				$companyID = isset($entityFields['COMPANY_ID']) ? $entityFields['COMPANY_ID'] : 0;
				if($companyID > 0)
				{
					$results = self::getCommunicationData(
						\CCrmOwnerType::Company,
						array($companyID),
						$typeIDs,
						array('deduplicate' => true)
					);
				}

				$contactsIDs = Crm\Binding\LeadContactTable::getLeadContactIDs($entityID);
				if(!empty($contactsIDs))
				{
					$results = array_merge(
						$results,
						self::getCommunicationData(
							\CCrmOwnerType::Contact,
							$contactsIDs,
							$typeIDs,
							array('deduplicate' => true)
						)
					);
				}
			}
			return $results;
		}

		if($entityTypeID === \CCrmOwnerType::Deal)
		{
			$dbResult = \CCrmDeal::GetListEx(
				array(),
				array('ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'COMPANY_ID')
			);

			$entityFields = $dbResult->Fetch();
			if(!is_array($entityFields))
			{
				return array();
			}

			$results = array();

			$companyID = isset($entityFields['COMPANY_ID']) ? $entityFields['COMPANY_ID'] : 0;
			if($companyID > 0)
			{
				$results = self::getCommunicationData(
					\CCrmOwnerType::Company,
					array($companyID),
					$typeIDs,
					array('deduplicate' => true)
				);
			}

			$contactsIDs = Crm\Binding\DealContactTable::getDealContactIDs($entityID);
			if(!empty($contactsIDs))
			{
				$results = array_merge(
					$results,
					self::getCommunicationData(
						\CCrmOwnerType::Contact,
						$contactsIDs,
						$typeIDs,
						array('deduplicate' => true)
					)
				);
			}
			return $results;
		}

		if($entityTypeID === \CCrmOwnerType::Quote)
		{
			$dbResult = \CCrmQuote::GetList(
				array(),
				array('ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'COMPANY_ID')
			);

			$entityFields = $dbResult->Fetch();
			if(!is_array($entityFields))
			{
				return array();
			}

			$results = array();

			$companyID = isset($entityFields['COMPANY_ID']) ? $entityFields['COMPANY_ID'] : 0;
			if($companyID > 0)
			{
				$results = self::getCommunicationData(
					\CCrmOwnerType::Company,
					array($companyID),
					$typeIDs,
					array('deduplicate' => true)
				);
			}

			$contactsIDs = Crm\Binding\QuoteContactTable::getQuoteContactIDs($entityID);
			if(!empty($contactsIDs))
			{
				$results = array_merge(
					$results,
					self::getCommunicationData(
						\CCrmOwnerType::Contact,
						$contactsIDs,
						$typeIDs,
						array('deduplicate' => true)
					)
				);
			}
			return $results;
		}

		if($entityTypeID === \CCrmOwnerType::Invoice)
		{
			$dbResult = \CCrmInvoice::GetList(
				array(),
				array('ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'UF_COMPANY_ID', 'UF_CONTACT_ID')
			);

			$entityFields = $dbResult->Fetch();
			if(!is_array($entityFields))
			{
				return array();
			}

			$results = array();

			$companyID = isset($entityFields['UF_COMPANY_ID']) ? $entityFields['UF_COMPANY_ID'] : 0;
			if($companyID > 0)
			{
				$results = self::getCommunicationData(
					\CCrmOwnerType::Company,
					array($companyID),
					$typeIDs,
					array('deduplicate' => true)
				);
			}

			$contactID = isset($entityFields['UF_CONTACT_ID']) ? $entityFields['UF_CONTACT_ID'] : 0;
			if($contactID > 0)
			{
				$results = array_merge(
					$results,
					self::getCommunicationData(
						\CCrmOwnerType::Contact,
						array($contactID),
						$typeIDs,
						array('deduplicate' => true)
					)
				);
			}
			return $results;
		}

		return array();
	}
	protected static function getCommunicationData(
		$entityTypeID,
		array $entityIDs,
		array $communicationTypeIDs = null,
		array $options = null
	)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$typeIDs = array();
		if(is_array($communicationTypeIDs))
		{
			foreach($communicationTypeIDs as $communicationTypeID)
			{
				if($communicationTypeID === Crm\Communication\Type::PHONE)
				{
					$typeIDs[] = \CCrmFieldMulti::PHONE;
				}
				elseif($communicationTypeID === Crm\Communication\Type::EMAIL)
				{
					$typeIDs[] = \CCrmFieldMulti::EMAIL;
				}
			}
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		$filter = array('=ENTITY_ID' => $entityTypeName, '@ELEMENT_ID' => $entityIDs);

		if(!empty($typeIDs))
		{
			$filter['@TYPE_ID'] = $typeIDs;
		}

		$dbResult = \CCrmFieldMulti::GetListEx(
			array(),
			$filter,
			false,
			false,
			array('ELEMENT_ID', 'TYPE_ID', 'VALUE', 'VALUE_TYPE')
		);

		$deduplicate = isset($options['deduplicate']) && $options['deduplicate'] === true;

		$results = array();
		while($fields = $dbResult->Fetch())
		{
			$elementID = $fields['ELEMENT_ID'];
			$typeID = $fields['TYPE_ID'];
			$value = isset($fields['VALUE']) ? $fields['VALUE'] : '';

			$key = $elementID.':'.$typeID.':'.md5(mb_strtoupper(trim($value)));
			if($deduplicate && isset($results[$key]))
			{
				continue;
			}

			$results[$key] = array(
				'ENTITY_ID' => $elementID,
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_TYPE' => $entityTypeName,
				'TYPE' => $typeID,
				'VALUE' => $value,
				'VALUE_TYPE' => isset($fields['VALUE_TYPE']) ? $fields['VALUE_TYPE'] : ''
			);
		}
		return array_values($results);
	}
}