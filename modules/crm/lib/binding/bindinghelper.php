<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\Binding;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

class BindingHelper
{
	public static function prepareBindingInfos($ownerTypeID, $ownerID, $entityTypeID, $formID)
	{
		Loc::loadMessages(__FILE__);

		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();

		$formFieldNames = \CCrmViewHelper::getFormFieldNames($formID);
		$entityIDs = null;
		if($ownerTypeID === \CCrmOwnerType::Contact && $entityTypeID === \CCrmOwnerType::Company)
		{
			$entityIDs = ContactCompanyTable::getContactCompanyIDs($ownerID);
		}
		elseif($ownerTypeID === \CCrmOwnerType::Deal && $entityTypeID === \CCrmOwnerType::Contact)
		{
			$entityIDs = DealContactTable::getDealContactIDs($ownerID);
		}
		elseif($ownerTypeID === \CCrmOwnerType::Quote && $entityTypeID === \CCrmOwnerType::Contact)
		{
			$entityIDs = QuoteContactTable::getQuoteContactIDs($ownerID);
		}

		if(empty($entityIDs))
		{
			return array();
		}

		$prefix = \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeID);
		$map = array();
		if($entityTypeID === \CCrmOwnerType::Company)
		{
			$dbRes = \CCrmCompany::GetListEx(
				array(),
				array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'TITLE', 'LOGO')
			);

			if(is_object($dbRes))
			{
				$file = new \CFile();
				while($fields = $dbRes->Fetch())
				{
					$entityID = (int)$fields['ID'];
					$isEntityReadPermitted = \CCrmCompany::CheckReadPermission($entityID, $userPermissions);

					if(!$isEntityReadPermitted)
					{
						$info = array(
							'ENTITY_TYPE_NAME' => \CCrmOwnerType::CompanyName,
							'ENTITY_ID' => $entityID,
							'ENABLE_MULTIFIELDS' => false,
							'NAME' => Loc::getMessage('BINDING_HLP_HIDDEN_COMPANY')
						);
					}
					else
					{
						$info = array(
							'ENTITY_TYPE_NAME' => \CCrmOwnerType::CompanyName,
							'ENTITY_ID' => $entityID,
							'NAME' => isset($fields['TITLE']) ? $fields['TITLE'] : '',
							'DESCRIPTION' => '',
							'SHOW_URL' => \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Company, $fields['ID'], false),
						);
						$imageID = isset($fields['LOGO']) ? (int)$fields['LOGO'] : 0;
						if($imageID <= 0)
						{
							$info['IMAGE_URL'] = '';
						}
						else
						{
							$fileInfo = $file->ResizeImageGet(
								$imageID,
								array('width' => 48, 'height' => 31),
								BX_RESIZE_IMAGE_PROPORTIONAL
							);
							$info['IMAGE_URL'] = is_array($fileInfo) && isset($fileInfo['src']) ? $fileInfo['src'] : '';
						}

						$multiFieldDbRes = \CCrmFieldMulti::GetList(
							array('ID' => 'asc'),
							array('ENTITY_ID' => \CCrmOwnerType::CompanyName, 'ELEMENT_ID' => $entityID)
						);


						$multiFieldData = array();
						while($multiFields = $multiFieldDbRes->Fetch())
						{
							$multiFieldData[$multiFields['TYPE_ID']][$multiFields['ID']] = array('VALUE' => $multiFields['VALUE'], 'VALUE_TYPE' => $multiFields['VALUE_TYPE']);
						}

						if(isset($multiFieldData['PHONE']))
						{
							$info['PHONE'] = self::prepareMultiFields(
								$multiFieldData['PHONE'],
								\CCrmOwnerType::CompanyName,
								$entityID,
								'PHONE',
								$formFieldNames
							);
						}

						if(isset($multiFieldData['EMAIL']))
						{
							$info['EMAIL'] = self::prepareMultiFields(
								$multiFieldData['EMAIL'],
								\CCrmOwnerType::CompanyName,
								$entityID,
								'EMAIL',
								$formFieldNames
							);
						}
					}

					$map["{$prefix}_{$entityID}"] = array('type' => 'client', 'data' => $info);
				}
			}
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			$dbRes = \CCrmContact::GetListEx(
				array(),
				array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'POST', 'PHOTO')
			);

			if(is_object($dbRes))
			{
				$file = new \CFile();
				while($fields = $dbRes->Fetch())
				{
					$entityID = (int)$fields['ID'];
					$isEntityReadPermitted = \CCrmContact::CheckReadPermission($entityID, $userPermissions);

					if(!$isEntityReadPermitted)
					{
						$info = array(
							'ENTITY_TYPE_NAME' => \CCrmOwnerType::ContactName,
							'ENTITY_ID' => $entityID,
							'ENABLE_MULTIFIELDS' => false,
							'NAME' => Loc::getMessage('BINDING_HLP_HIDDEN_CONTACT')
						);
					}
					else
					{
						$info = array(
							'ENTITY_TYPE_NAME' => \CCrmOwnerType::ContactName,
							'ENTITY_ID' => $entityID,
							'NAME' => \CCrmContact::PrepareFormattedName($fields),
							'DESCRIPTION' => isset($fields['POST']) ? $fields['POST'] : '',
							'SHOW_URL' => \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Contact, $fields['ID'], false),
						);
						$imageID = isset($fields['PHOTO']) ? (int)$fields['PHOTO'] : 0;
						if($imageID <= 0)
						{
							$info['IMAGE_URL'] = '';
						}
						else
						{
							$fileInfo = $file->ResizeImageGet(
								$imageID,
								array('width' => 38, 'height' => 38),
								BX_RESIZE_IMAGE_EXACT
							);
							$info['IMAGE_URL'] = is_array($fileInfo) && isset($fileInfo['src']) ? $fileInfo['src'] : '';
						}

						$multiFieldDbRes = \CCrmFieldMulti::GetList(
							array('ID' => 'asc'),
							array('ENTITY_ID' => \CCrmOwnerType::ContactName, 'ELEMENT_ID' => $entityID)
						);


						$multiFieldData = array();
						while($multiFields = $multiFieldDbRes->Fetch())
						{
							$multiFieldData[$multiFields['TYPE_ID']][$multiFields['ID']] = array('VALUE' => $multiFields['VALUE'], 'VALUE_TYPE' => $multiFields['VALUE_TYPE']);
						}

						if(isset($multiFieldData['PHONE']))
						{
							$info['PHONE'] = self::prepareMultiFields(
								$multiFieldData['PHONE'],
								\CCrmOwnerType::ContactName,
								$entityID,
								'PHONE',
								$formFieldNames
							);
						}

						if(isset($multiFieldData['EMAIL']))
						{
							$info['EMAIL'] = self::prepareMultiFields(
								$multiFieldData['EMAIL'],
								\CCrmOwnerType::ContactName,
								$entityID,
								'EMAIL',
								$formFieldNames
							);
						}
					}

					$map["{$prefix}_{$entityID}"] = array('type' => 'client', 'data' => $info);
				}
			}
		}

		$results = array();
		foreach($entityIDs as $entityID)
		{
			$key = "{$prefix}_{$entityID}";
			if(isset($map[$key]))
			{
				$results[] = $map[$key];
			}
		}
		return $results;
	}
	private static function prepareMultiFields(array $multiFields, $entityTypeName, $entityID, $typeID, array $formFieldNames = null)
	{
		if(empty($multiFields))
		{
			return null;
		}

		$arEntityTypeInfos = \CCrmFieldMulti::GetEntityTypeInfos();
		$arEntityTypes = \CCrmFieldMulti::GetEntityTypes();
		$sipConfig =  array(
			'STUB' => GetMessage('CRM_ENTITY_QPV_MULTI_FIELD_NOT_ASSIGNED'),
			'ENABLE_SIP' => true,
			'SIP_PARAMS' => array(
				'ENTITY_TYPE' => 'CRM_'.$entityTypeName,
				'ENTITY_ID' => $entityID)
		);

		$typeInfo = isset($arEntityTypeInfos[$typeID]) ? $arEntityTypeInfos[$typeID] : array();
		$caption = isset($typeInfo['NAME']) ? $typeInfo['NAME'] : $typeID;
		if(is_array($formFieldNames) && isset($formFieldNames[$typeID]))
		{
			$caption = $formFieldNames[$typeID];
		}

		$result = array(
			'type' => 'multiField',
			'caption' => $caption,
			'data' => array('type'=> $typeID, 'items'=> array())
		);
		foreach($multiFields as $multiField)
		{
			$value = isset($multiField['VALUE']) ? $multiField['VALUE'] : '';
			$valueType = isset($multiField['VALUE_TYPE']) ? $multiField['VALUE_TYPE'] : '';

			$entityType = $arEntityTypes[$typeID];
			$valueTypeInfo = isset($entityType[$valueType]) ? $entityType[$valueType] : null;

			$params = array('VALUE' => $value, 'VALUE_TYPE_ID' => $valueType, 'VALUE_TYPE' => $valueTypeInfo);
			$result['data']['items'][] = \CCrmViewHelper::PrepareMultiFieldValueItemData($typeID, $params, $sipConfig);
		}
		return $result;
	}
}