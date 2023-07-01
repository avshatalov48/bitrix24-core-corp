<?php

use Bitrix\Crm\Order\Order;
use Bitrix\Crm\RequisiteAddress;
use Bitrix\Crm\Service\Container;
use Bitrix\Location\Entity\Address;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service;
use Bitrix\Crm\Item\Company;
use Bitrix\Crm\Item;

IncludeModuleLangFile(__FILE__);

class CCrmEntitySelectorHelper
{
	public static function getIdWithEntityPrefix($id, string $entityName): string
	{
		return \CCrmOwnerTypeAbbr::ResolveByTypeName($entityName) . '_' . $id;
	}

	public static function getHiddenTitle(string $entityName): ?string
	{
		$message = Loc::getMessage('CRM_ENT_SEL_HLP_HIDDEN_' . $entityName);

		if (!$message)
		{
			$message = Loc::getMessage('CRM_ENT_SEL_HLP_HIDDEN_DYNAMIC');
		}

		return $message;
	}

	public static function PrepareEntityInfo($entityTypeName, $entityID, $options = array())
	{
		$enableSlider = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled();

		$entityTypeName = mb_strtoupper(strval($entityTypeName));
		$entityID = intval($entityID);
		if(!is_array($options))
		{
			$options = array();
		}

		$userPermissions = isset($options['USER_PERMISSIONS']) && $options['USER_PERMISSIONS'] instanceof \CCrmPerms
			? $options['USER_PERMISSIONS'] : \CCrmPerms::GetCurrentUserPermissions();

		$bEntityEditorFormat = (
			isset($options['ENTITY_EDITOR_FORMAT'])
			&& ($options['ENTITY_EDITOR_FORMAT'] === true || mb_strtoupper($options['ENTITY_EDITOR_FORMAT']) === 'Y')
		);
		$bEntityPrefixEnabled = (
			isset($options['ENTITY_PREFIX_ENABLED'])
			&& ($options['ENTITY_PREFIX_ENABLED'] === true || mb_strtoupper($options['ENTITY_PREFIX_ENABLED']) === 'Y')
		);

		$isHidden = (
			isset($options['IS_HIDDEN'])
			&& ($options['IS_HIDDEN'] === true || $options['IS_HIDDEN'] === 'Y')
		);

		$largeImages = (
			isset($options['LARGE_IMAGES'])
			&& ($options['LARGE_IMAGES'] === true || $options['LARGE_IMAGES'] === 'Y')
		);

		$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
		$serviceUserPermissions = Container::getInstance()->getUserPermissions($userPermissions->GetUserID());
		$isMyCompany = (int)$entityTypeId === CCrmOwnerType::Company && CCrmCompany::isMyCompany((int)$entityID);
		if (!$isHidden)
		{
			if ($entityTypeId > 0)
			{
				$isHidden = $isMyCompany
					? !$serviceUserPermissions->getMyCompanyPermissions()->canReadBaseFields((int)$entityID)
					: !$serviceUserPermissions->checkReadPermissions($entityTypeId, $entityID)
				;
			}
		}

		if($isHidden)
		{
			$requireMultifields = $requireBindings = $requireRequisiteData = $requireEditRequisiteData =  false;
		}
		else
		{
			$requireMultifields = !isset($options['REQUIRE_MULTIFIELDS']) || $options['REQUIRE_MULTIFIELDS'] === true;
			$requireBindings = !isset($options['REQUIRE_BINDINGS']) || $options['REQUIRE_BINDINGS'] === true;

			$requireRequisiteData = (
				isset($options['REQUIRE_REQUISITE_DATA'])
				&& ($options['REQUIRE_REQUISITE_DATA'] === true || $options['REQUIRE_REQUISITE_DATA'] === 'Y')
			);

			$requireEditRequisiteData = (
				isset($options['REQUIRE_EDIT_REQUISITE_DATA'])
				&& ($options['REQUIRE_EDIT_REQUISITE_DATA'] === true || $options['REQUIRE_EDIT_REQUISITE_DATA'] === 'Y')
			);
		}

		$normalizeMultifields = (
			isset($options['NORMALIZE_MULTIFIELDS'])
			&& ($options['NORMALIZE_MULTIFIELDS'] === true || $options['NORMALIZE_MULTIFIELDS'] === 'Y')
		);

		$result = array();
		if ($bEntityEditorFormat)
		{
			$result['id'] = $entityID;
			$result['type'] = mb_strtolower($entityTypeName);
			$result['typeName'] = $entityTypeName;
			$result['typeNameTitle'] = \CCrmOwnerType::GetDescription($entityTypeId);
			$result['place'] = $result['type'];
			$result['hidden'] = $isHidden;
		}

		$titleKey = $bEntityEditorFormat ? 'title' : 'TITLE';
		$result[$titleKey] = "{$entityTypeName}_{$entityID}";

		$urlKey = $bEntityEditorFormat ? 'url' : 'URL';
		$result[$urlKey] = '';

		$descKey = $bEntityEditorFormat ? 'desc' : 'DESC';
		$result[$descKey] = '';

		$imageKey = $bEntityEditorFormat ? 'image' : 'IMAGE';
		$largeImageKey = $bEntityEditorFormat ? 'largeImage' : 'LARGE_IMAGE';
		$result[$imageKey] = '';

		if($entityTypeName === '' || $entityID <= 0)
		{
			return $result;
		}

		$advancedInfoKey = $bEntityEditorFormat ? 'advancedInfo' : 'ADVANCED_INFO';
		$contactTypeKey = $bEntityEditorFormat ? 'contactType' : 'CONTACT_TYPE';
		$contactTypeIdKey = $bEntityEditorFormat ? 'id' : 'ID';
		$contactTypeNameKey = $bEntityEditorFormat ? 'name' : 'NAME';
		$multiFieldsKey = $bEntityEditorFormat ? 'multiFields' : 'MULTI_FIELDS';
		$requisiteDataKey = $bEntityEditorFormat ? 'requisiteData' : 'REQUISITE_DATA';
		$bindingDataKey = $bEntityEditorFormat ? 'bindings' : 'BINDINGS';
		$permissionsKey = $bEntityEditorFormat ? 'permissions' : 'PERMISSIONS';
		$canUpdateKey = $bEntityEditorFormat ? 'canUpdate' : 'CAN_UPDATE';

		if ($bEntityEditorFormat && $bEntityPrefixEnabled)
		{
			$result['id'] = static::getIdWithEntityPrefix($result['id'], $entityTypeName);
		}
		if ($isHidden)
		{
			$result[$titleKey] = static::getHiddenTitle($entityTypeName);
			$result[$advancedInfoKey]['hasEditRequisiteData'] = true;

			return $result;
		}
		$result[$permissionsKey] = [
			$canUpdateKey => $isMyCompany
				? $serviceUserPermissions->getMyCompanyPermissions()->canUpdateByOwnerEntity(
					(int)($options['ownerEntityTypeId'] ?? $entityTypeId),
					(int)($options['ownerEntityId'] ?? $entityID)
				)
				: $serviceUserPermissions->checkUpdatePermissions($entityTypeId, $entityID),
		];
		if($entityTypeName === 'CONTACT')
		{
			$arImages = array();
			$arLargeImages = array();
			$contactTypes = CCrmStatus::GetStatusList('CONTACT_TYPE');

			$obRes = CCrmContact::GetListEx(
				array(),
				array('=ID'=> $entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'POST', 'PHOTO', 'TYPE_ID')
			);
			if($arRes = $obRes->Fetch())
			{
				$photoID = intval($arRes['PHOTO']);
				if ($photoID > 0 && !isset($arImages[$photoID]))
				{
					if ($largeImages)
					{
						// the same size and resize type as in `crm.contact.details` (do not multiply images)
						$arImages[$photoID] = $arLargeImages[$photoID] = CFile::ResizeImageGet(
							$photoID,
							['width' => 200, 'height' => 200],
							BX_RESIZE_IMAGE_EXACT,
							false,
							false,
							true
						);
					}
					else
					{
						$arImages[$photoID] = CFile::ResizeImageGet(
							$photoID,
							['width' => 25, 'height' => 25],
							BX_RESIZE_IMAGE_EXACT
						);
						$arLargeImages[$photoID] = CFile::ResizeImageGet(
							$photoID,
							['width' => 38, 'height' => 38],
							BX_RESIZE_IMAGE_EXACT
						);
					}
				}
				$result[$titleKey] = CCrmContact::PrepareFormattedName(
					array(
						'HONORIFIC' => isset($arRes['HONORIFIC']) ? $arRes['HONORIFIC'] : '',
						'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
						'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : '',
						'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : ''
					),
					isset($options['NAME_TEMPLATE']) ? $options['NAME_TEMPLATE'] : ''
				);

				$result[$urlKey] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', $enableSlider ? 'path_to_contact_details' : 'path_to_contact_show'),
					array(
						'contact_id' => $entityID
					)
				);

				$result[$descKey] = isset($arRes['POST']) && $arRes['POST'] !== ''
					? $arRes['POST']
					: (isset($arRes['COMPANY_TITLE']) ? $arRes['COMPANY_TITLE'] : '');

				$result[$imageKey] = isset($arImages[$photoID]['src']) ? $arImages[$photoID]['src'] : '';
				$result[$largeImageKey] = isset($arLargeImages[$photoID]['src']) ? $arLargeImages[$photoID]['src'] : '';

				// advanced info
				$advancedInfo = array();
				if (isset($arRes['TYPE_ID']) && $arRes['TYPE_ID'] != '' && isset($contactTypes[$arRes['TYPE_ID']]))
				{
					$advancedInfo[$contactTypeKey] = array(
						$contactTypeIdKey => $arRes['TYPE_ID'],
						$contactTypeNameKey => $contactTypes[$arRes['TYPE_ID']]
					);
				}
				if (!empty($advancedInfo))
					$result[$advancedInfoKey] = $advancedInfo;

				if ($requireMultifields)
				{
					$phoneCountryList = static::getPhoneCountryList('CONTACT', $entityID);

					// advanced info - phone number, e-mail
					$obRes = CCrmFieldMulti::GetList(['ID' => 'asc'], ['ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $entityID]);
					while ($arRes = $obRes->Fetch())
					{
						if (
							$arRes['TYPE_ID'] === 'PHONE'
							|| $arRes['TYPE_ID'] === 'EMAIL'
							|| ($arRes['TYPE_ID'] === 'IM' && preg_match('/^imol\|/', $arRes['VALUE']) === 1)
						)
						{
							$formattedValue = $arRes['TYPE_ID'] === 'PHONE'
								? \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($arRes['VALUE'])->format()
								: $arRes['VALUE'];

							$multiFieldId = $normalizeMultifields ? $arRes['ID'] : $entityID;

							if (!isset($result[$advancedInfoKey]) || !is_array($result[$advancedInfoKey]))
							{
								$result[$advancedInfoKey] = [];
							}

							if (
								!isset($result[$advancedInfoKey][$multiFieldsKey])
								|| !is_array($result[$advancedInfoKey][$multiFieldsKey])
							)
							{
								$result[$advancedInfoKey][$multiFieldsKey] = [];
							}

							$result[$advancedInfoKey][$multiFieldsKey][] = [
								'ID' => $multiFieldId,
								'ENTITY_ID' => $normalizeMultifields ? $entityID : $arRes['ID'],
								'ENTITY_TYPE_NAME' => $entityTypeName,
								'TYPE_ID' => $arRes['TYPE_ID'],
								'VALUE_TYPE' => $arRes['VALUE_TYPE'],
								'VALUE' => $arRes['VALUE'],
								'VALUE_EXTRA' => [
									'COUNTRY_CODE' => $phoneCountryList[$multiFieldId] ?? ''
								],
								'VALUE_FORMATTED' => $formattedValue,
								'COMPLEX_ID' => $arRes['COMPLEX_ID'],
								'COMPLEX_NAME' => \CCrmFieldMulti::GetEntityNameByComplex($arRes['COMPLEX_ID'], false)
							];
						}
					}
				}

				if($requireBindings)
				{
					$result[$advancedInfoKey][$bindingDataKey][CCrmOwnerType::CompanyName] =
						\Bitrix\Crm\Binding\ContactCompanyTable::getContactCompanyIDs($entityID);
				}

				// requisites
				if ($requireRequisiteData)
				{
					$requisiteDataParams =
						$requireEditRequisiteData ?
							[
								'VIEW_FORMATTED' => true,
								'ADDRESS_AS_JSON' => true,
							]
							:
							[
								'VIEW_DATA_ONLY' => true
							];

					$result[$advancedInfoKey][$requisiteDataKey] = self::PrepareRequisiteData(
						CCrmOwnerType::Contact, $entityID, $requisiteDataParams
					);
				}
				$result[$advancedInfoKey]['hasEditRequisiteData'] = $requireEditRequisiteData;
			}
			else
			{
				$result['notFound'] = true;
			}
		}
		elseif($entityTypeName === 'COMPANY')
		{
			$arImages = array();
			$arLargeImages = array();

			$arCompanyTypeList = CCrmStatus::GetStatusList('COMPANY_TYPE');
			$arCompanyIndustryList = CCrmStatus::GetStatusList('INDUSTRY');

			$obRes = CCrmCompany::GetListEx(
				array(),
				array('=ID'=> $entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				[
					'ID',
					'TITLE',
					'COMPANY_TYPE',
					'INDUSTRY',
					'LOGO',
				]
			);

			if ($arRes = $obRes->fetch())
			{
				$result[$titleKey] = $arRes['TITLE'];

				$result[$urlKey] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', $enableSlider ? 'path_to_company_details' : 'path_to_company_show'),
					array(
						'company_id' => $entityID
					)
				);

				$category = Container::getInstance()->getFactory(CCrmOwnerType::Company)
					->getItemCategory((int)$arRes['ID']);
				$categoryDependentDisabledFields = $category ? $category->getDisabledFieldNames() : [];

				$arDesc = [];
				if (
					isset($arCompanyTypeList[$arRes['COMPANY_TYPE']])
					&& !in_array(Item::FIELD_NAME_TYPE_ID, $categoryDependentDisabledFields, true)
				)
				{
					$arDesc[] = $arCompanyTypeList[$arRes['COMPANY_TYPE']];
				}

				if (
					isset($arCompanyIndustryList[$arRes['INDUSTRY']])
					&& !in_array(Company::FIELD_NAME_INDUSTRY, $categoryDependentDisabledFields, true)
				)
				{
					$arDesc[] = $arCompanyIndustryList[$arRes['INDUSTRY']];
				}

				$result[$descKey] = implode(', ', $arDesc);

				$logoID = intval($arRes['LOGO']);
				if ($logoID > 0 && !isset($arImages[$logoID]))
				{
					if ($largeImages)
					{
						// the same size and resize type as in `crm.company.details` (do not multiply images)
						$arImages[$logoID] = $arLargeImages[$logoID] = CFile::ResizeImageGet(
							$logoID,
							['width' => 300, 'height' => 300],
							false,
							false,
							true
						);
					}
					else
					{
						$arImages[$logoID] = CFile::ResizeImageGet(
							$logoID,
							['width' => 25, 'height' => 25],
							BX_RESIZE_IMAGE_EXACT
						);
						$arLargeImages[$logoID] = CFile::ResizeImageGet(
							$logoID,
							['width' => 38, 'height' => 38],
							BX_RESIZE_IMAGE_EXACT
						);
					}
				}
				$result[$imageKey] = isset($arImages[$logoID]['src']) ? $arImages[$logoID]['src'] : '';
				$result[$largeImageKey] = isset($arLargeImages[$logoID]['src']) ? $arLargeImages[$logoID]['src'] : '';

				if ($requireMultifields)
				{
					$phoneCountryList = static::getPhoneCountryList('COMPANY', $entityID);

					// advanced info - phone number, e-mail
					$obRes = CCrmFieldMulti::GetList(['ID' => 'asc'], ['ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $entityID]);
					while ($arRes = $obRes->Fetch())
					{
						if ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL')
						{
							$formattedValue = $arRes['TYPE_ID'] === 'PHONE'
								? \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($arRes['VALUE'])->format()
								: $arRes['VALUE'];

							$multiFieldId = $normalizeMultifields ? $arRes['ID'] : $entityID;

							if (!isset($result[$advancedInfoKey]) || !is_array($result[$advancedInfoKey]))
							{
								$result[$advancedInfoKey] = [];
							}

							if (
								!isset($result[$advancedInfoKey][$multiFieldsKey])
								|| !is_array($result[$advancedInfoKey][$multiFieldsKey])
							)
							{
								$result[$advancedInfoKey][$multiFieldsKey] = [];
							}

							$result[$advancedInfoKey][$multiFieldsKey][] = [
								'ID' => $multiFieldId,
								'ENTITY_ID' => $normalizeMultifields ? $entityID : $arRes['ID'],
								'ENTITY_TYPE_NAME' => $entityTypeName,
								'TYPE_ID' => $arRes['TYPE_ID'],
								'VALUE_TYPE' => $arRes['VALUE_TYPE'],
								'VALUE' => $arRes['VALUE'],
								'VALUE_EXTRA' => [
									'COUNTRY_CODE' => $phoneCountryList[$multiFieldId] ?? ''
								],
								'VALUE_FORMATTED' => $formattedValue,
								'COMPLEX_ID' => $arRes['COMPLEX_ID'],
								'COMPLEX_NAME' => \CCrmFieldMulti::GetEntityNameByComplex($arRes['COMPLEX_ID'], false)
							];
						}
					}
				}
			}
			else
			{
				$result['notFound'] = true;
			}

			// requisites
			if ($requireRequisiteData)
			{
				$requisiteDataParams =
					$requireEditRequisiteData ?
					[
						'VIEW_FORMATTED' => true,
						'ADDRESS_AS_JSON' => true,
					]
					:
					[
						'VIEW_DATA_ONLY' => true
					];

				$result[$advancedInfoKey][$requisiteDataKey] = self::PrepareRequisiteData(
					CCrmOwnerType::Company, $entityID, $requisiteDataParams
				);
			}
			$result[$advancedInfoKey]['hasEditRequisiteData'] = $requireEditRequisiteData;
		}
		elseif($entityTypeName === 'LEAD')
		{
			$obRes = CCrmLead::GetListEx(
				array(),
				array('=ID'=> $entityID),
				false,
				false,
				array('TITLE', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME')
			);
			if($arRes = $obRes->Fetch())
			{
				$result[$titleKey] = isset($arRes['TITLE']) ? $arRes['TITLE'] : '';
				if($result[$titleKey] === '')
				{
					$result[$titleKey] = CCrmLead::PrepareFormattedName(
						array(
							'HONORIFIC' => isset($arRes['HONORIFIC']) ? $arRes['HONORIFIC'] : '',
							'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
							'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : '',
							'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : ''
						),
						isset($options['NAME_TEMPLATE']) ? $options['NAME_TEMPLATE'] : ''
					);
				}

				$result[$urlKey] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', $enableSlider ? 'path_to_lead_details' : 'path_to_lead_show'),
					array(
						'lead_id' => $entityID
					)
				);

				$result[$descKey] = CCrmLead::PrepareFormattedName(
					array(
						'HONORIFIC' => isset($arRes['HONORIFIC']) ? $arRes['HONORIFIC'] : '',
						'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
						'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
						'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
					)
				);

				if ($requireMultifields)
				{
					$phoneCountryList = static::getPhoneCountryList('LEAD', $entityID);

					// advanced info - phone number, e-mail
					$obRes = CCrmFieldMulti::GetList(['ID' => 'asc'], ['ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $entityID]);
					while ($arRes = $obRes->Fetch())
					{
						if ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL')
						{
							$formattedValue = $arRes['TYPE_ID'] === 'PHONE'
								? \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($arRes['VALUE'])->format()
								: $arRes['VALUE'];

							$multiFieldId = $normalizeMultifields ? $arRes['ID'] : $entityID;

							if (!isset($result[$advancedInfoKey]) || !is_array($result[$advancedInfoKey]))
							{
								$result[$advancedInfoKey] = [];
							}

							if (
								!isset($result[$advancedInfoKey][$multiFieldsKey])
								|| !is_array($result[$advancedInfoKey][$multiFieldsKey])
							)
							{
								$result[$advancedInfoKey][$multiFieldsKey] = [];
							}

							$result[$advancedInfoKey][$multiFieldsKey][] = [
								'ID' => $multiFieldId,
								'ENTITY_ID' => $normalizeMultifields ? $entityID : $arRes['ID'],
								'ENTITY_TYPE_NAME' => $entityTypeName,
								'TYPE_ID' => $arRes['TYPE_ID'],
								'VALUE_TYPE' => $arRes['VALUE_TYPE'],
								'VALUE' => $arRes['VALUE'],
								'VALUE_EXTRA' => [
									'COUNTRY_CODE' => $phoneCountryList[$multiFieldId] ?? ''
								],
								'VALUE_FORMATTED' => $formattedValue,
								'COMPLEX_ID' => $arRes['COMPLEX_ID'],
								'COMPLEX_NAME' => \CCrmFieldMulti::GetEntityNameByComplex($arRes['COMPLEX_ID'], false)
							];
						}
					}
				}
			}
			else
			{
				$result['notFound'] = true;
			}
		}
		elseif($entityTypeName === 'DEAL')
		{
			$obRes = CCrmDeal::GetListEx(
				array(),
				array('=ID'=> $entityID),
				false,
				false,
				array('TITLE', 'COMPANY_TITLE', 'CONTACT_FULL_NAME')
			);
			if($arRes = $obRes->Fetch())
			{
				$result[$titleKey] = $arRes['TITLE'];

				$result[$urlKey] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', $enableSlider ? 'path_to_deal_details' : 'path_to_deal_show'),
					array(
						'deal_id' => $entityID
					)
				);

				$clientTitle = (!empty($arRes['COMPANY_TITLE'])) ? $arRes['COMPANY_TITLE'] : '';
				$clientTitle .= (($clientTitle !== '' && !empty($arRes['CONTACT_FULL_NAME'])) ? ', ' : '').
					$arRes['CONTACT_FULL_NAME'];
				$result[$descKey] = $clientTitle;
			}
			else
			{
				$result['notFound'] = true;
			}
		}
		elseif($entityTypeName === 'QUOTE')
		{
			$obRes = CCrmQuote::GetList(
				array(), array('=ID'=> $entityID), false, false,
				array('QUOTE_NUMBER', 'TITLE', 'COMPANY_TITLE', 'CONTACT_FULL_NAME', 'BEGINDATE')
			);
			if($arRes = $obRes->Fetch())
			{
				$result[$titleKey] = \Bitrix\Crm\Item\Quote::getTitlePlaceholderFromData($arRes);
				$result[$urlKey] = Container::getInstance()->getRouter()->getItemDetailUrl(\CCrmOwnerType::Quote, $entityID);

				$clientTitle = (!empty($arRes['COMPANY_TITLE'])) ? $arRes['COMPANY_TITLE'] : '';
				$clientTitle .= (($clientTitle !== '' && !empty($arRes['CONTACT_FULL_NAME'])) ? ', ' : '').$arRes['CONTACT_FULL_NAME'];
				$result[$descKey] = $clientTitle;
			}
			else
			{
				$result['notFound'] = true;
			}
		}
		elseif($entityTypeName === 'ORDER')
		{
			$order = Order::getList([
				'select' => ['ID', 'ACCOUNT_NUMBER'],
				'filter' => [
					'=ID'=> $entityID,
				],
			])->fetchRaw();

			if ($order)
			{
				$result[$titleKey] = Loc::getMessage(
					'CRM_ENT_SEL_HLP_ORDER_SUMMARY',
					[
						'#ORDER_NUMBER#' => (
							isset($order['ACCOUNT_NUMBER'])
							? htmlspecialcharsbx($order['ACCOUNT_NUMBER'])
							: $order['ID']
						),
					]
				);

				$result[$urlKey] = Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()
					->getOrderDetailsLink($entityID);
			}
			else
			{
				$result['notFound'] = true;
			}
		}
		elseif(\CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId))
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if (!$factory)
			{
				return $result;
			}

			$item = $factory->getItem($entityID);

			if (!$item)
			{
				$result['notFound'] = true;
				return $result;
			}

			$result[$titleKey] = $item->getHeading();

			$result[$urlKey] = Container::getInstance()
				->getRouter()
				->getItemDetailUrl($entityTypeId, $entityID)
			;
		}

		return $result;
	}

	public static function PreparePopupItems($entityTypeNames, $addPrefix = true, $nameFormat = '', $count = 50, $options = array())
	{
		if(!is_array($entityTypeNames))
		{
			$entityTypeNames = array(strval($entityTypeNames));

		}

		$addPrefix =  (bool)$addPrefix;
		$count = intval($count);
		if($count <= 0)
		{
			$count = 50;
		}

		// options
		$requireRequisiteData = (
			is_array($options) && isset($options['REQUIRE_REQUISITE_DATA'])
			&& ($options['REQUIRE_REQUISITE_DATA'] === true || $options['REQUIRE_REQUISITE_DATA'] === 'Y')
		);
		$companiesFilter = array();
		if (is_array($options['SEARCH_OPTIONS']))
		{
			if (isset($options['SEARCH_OPTIONS']['ONLY_MY_COMPANIES'])
				&& $options['SEARCH_OPTIONS']['ONLY_MY_COMPANIES'] === 'Y')
			{
				$companiesFilter['=IS_MY_COMPANY'] = 'Y';
			}
			else if (isset($options['SEARCH_OPTIONS']['NOT_MY_COMPANIES'])
				&& $options['SEARCH_OPTIONS']['NOT_MY_COMPANIES'] === 'Y')
			{
				$companiesFilter['=IS_MY_COMPANY'] = 'N';
			}
		}

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$arItems = array();
		$i = 0;
		foreach($entityTypeNames as $typeName)
		{
			$typeName = mb_strtoupper(strval($typeName));

			if($typeName === 'CONTACT')
			{
				$entityIDs = CCrmContact::GetTopIDsInCategory(0, $count, 'DESC', $userPermissions);
				if(!empty($entityIDs))
				{
					$contactTypes = CCrmStatus::GetStatusList('CONTACT_TYPE');
					$contactIndex = array();

					$dbResult = CCrmContact::GetListEx(
						array('ID' => 'DESC'),
						array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
						false,
						false,
						array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO', 'TYPE_ID')
					);

					while ($arRes = $dbResult->Fetch())
					{
						$arImg = array();
						if (!empty($arRes['PHOTO']) && !isset($arFiles[$arRes['PHOTO']]))
						{
							if(intval($arRes['PHOTO']) > 0)
							{
								$arImg = CFile::ResizeImageGet($arRes['PHOTO'], array('width' => 25, 'height' => 25), BX_RESIZE_IMAGE_EXACT);
							}
						}

						$arRes['SID'] = $addPrefix ? 'C_'.$arRes['ID']: $arRes['ID'];

						// advanced info
						$advancedInfo = array();
						if (isset($arRes['TYPE_ID']) && $arRes['TYPE_ID'] != '' && isset($contactTypes[$arRes['TYPE_ID']]))
						{
							$advancedInfo['contactType'] = array(
								'id' => $arRes['TYPE_ID'],
								'name' => $contactTypes[$arRes['TYPE_ID']]
							);
						}

						$arItems[$i] = array(
							'title' => CCrmContact::PrepareFormattedName(
								array(
									'HONORIFIC' => isset($arRes['HONORIFIC']) ? $arRes['HONORIFIC'] : '',
									'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
									'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
									'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
								),
								$nameFormat
							),
							'desc'  => empty($arRes['COMPANY_TITLE'])? "": $arRes['COMPANY_TITLE'],
							'id' => $arRes['SID'],
							'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_contact_show'),
								array(
									'contact_id' => $arRes['ID']
								)
							),
							'image' => $arImg['src'],
							'type'  => 'contact',
							'selected' => 'N'
						);
						if (!empty($advancedInfo))
							$arItems[$i]['advancedInfo'] = $advancedInfo;
						unset($advancedInfo);

						// requisites
						if ($requireRequisiteData)
							$arItems[$i]['advancedInfo']['requisiteData'] = self::PrepareRequisiteData(
								CCrmOwnerType::Contact, $arRes['ID'], array('VIEW_DATA_ONLY' => true)
							);

						$contactIndex[$arRes['ID']] = &$arItems[$i];
						$i++;
					}

					// advanced info - phone number, e-mail
					$obRes = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => array_keys($contactIndex)));
					while($arRes = $obRes->Fetch())
					{
						if (isset($contactIndex[$arRes['ELEMENT_ID']])
							&& ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL'))
						{
							$item = &$contactIndex[$arRes['ELEMENT_ID']];
							if (!is_array($item['advancedInfo']))
								$item['advancedInfo'] = array();
							if (!is_array($item['advancedInfo']['multiFields']))
								$item['advancedInfo']['multiFields'] = array();
							$item['advancedInfo']['multiFields'][] = array(
								'ID' => $arRes['ID'],
								'TYPE_ID' => $arRes['TYPE_ID'],
								'VALUE_TYPE' => $arRes['VALUE_TYPE'],
								'VALUE' => $arRes['VALUE']
							);
							unset($item);
						}
					}
					unset($contactIndex);
				}
			}
			elseif($typeName === 'COMPANY')
			{
				if(empty($companiesFilter))
				{
					$entityIDs = CCrmCompany::GetTopIDsInCategory(0, $count, 'DESC', $userPermissions);
				}
				else
				{
					$dbResult = CCrmCompany::GetListEx(
						array('ID' => 'DESC'),
						array_merge($companiesFilter, ['@CATEGORY_ID' => 0,]),
						false,
						array('nTopCount' => $count),
						array('ID')
					);
					$entityIDs = array();
					while ($arRes = $dbResult->Fetch())
					{
						$entityIDs[] = (int)$arRes['ID'];
					}
				}

				if(!empty($entityIDs))
				{
					$companyIndex = array();
					$arCompanyTypeList = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
					$arCompanyIndustryList = CCrmStatus::GetStatusListEx('INDUSTRY');

					$dbResult = CCrmCompany::GetListEx(
						array('ID' => 'DESC'),
						array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
						false,
						false,
						array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO')
					);

					$arFiles = array();
					while ($arRes = $dbResult->Fetch())
					{
						$arImg = array();
						if (!empty($arRes['LOGO']) && !isset($arFiles[$arRes['LOGO']]))
						{
							if(intval($arRes['LOGO']) > 0)
								$arImg = CFile::ResizeImageGet($arRes['LOGO'], array('width' => 25, 'height' => 25), BX_RESIZE_IMAGE_EXACT);

							$arFiles[$arRes['LOGO']] = $arImg['src'];
						}

						$arRes['SID'] = $addPrefix ? 'CO_'.$arRes['ID']: $arRes['ID'];

						$arDesc = Array();
						if (isset($arCompanyTypeList[$arRes['COMPANY_TYPE']]))
							$arDesc[] = $arCompanyTypeList[$arRes['COMPANY_TYPE']];
						if (isset($arCompanyIndustryList[$arRes['INDUSTRY']]))
							$arDesc[] = $arCompanyIndustryList[$arRes['INDUSTRY']];


						$arItems[$i] = array(
							'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
							'desc' => implode(', ', $arDesc),
							'id' => $arRes['SID'],
							'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_company_show'),
								array(
									'company_id' => $arRes['ID']
								)
							),
							'image' => $arImg['src'],
							'type'  => 'company',
							'selected' => 'N'
						);

						// requisites
						if ($requireRequisiteData)
							$arItems[$i]['advancedInfo']['requisiteData'] = self::PrepareRequisiteData(
								CCrmOwnerType::Company, $arRes['ID'], array('VIEW_DATA_ONLY' => true)
							);

						$companyIndex[$arRes['ID']] = &$arItems[$i];
						$i++;
					}

					// advanced info - phone number, e-mail
					$obRes = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => array_keys($companyIndex)));
					while($arRes = $obRes->Fetch())
					{
						if (isset($companyIndex[$arRes['ELEMENT_ID']])
							&& ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL'))
						{
							$item = &$companyIndex[$arRes['ELEMENT_ID']];
							if (!is_array($item['advancedInfo']))
								$item['advancedInfo'] = array();
							if (!is_array($item['advancedInfo']['multiFields']))
								$item['advancedInfo']['multiFields'] = array();
							$item['advancedInfo']['multiFields'][] = array(
								'ID' => $arRes['ID'],
								'TYPE_ID' => $arRes['TYPE_ID'],
								'VALUE_TYPE' => $arRes['VALUE_TYPE'],
								'VALUE' => $arRes['VALUE']
							);
							unset($item);
						}
					}
					unset($companyIndex);
				}
			}
			elseif($typeName === 'LEAD')
			{
				$entityIDs = CCrmLead::GetTopIDs($count, 'DESC', $userPermissions);
				if(!empty($entityIDs))
				{
					$leadIndex = array();
					$dbResult = CCrmLead::GetListEx(
						array('ID' => 'DESC'),
						array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
						false,
						false,
						array('ID', 'TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'STATUS_ID')
					);

					while ($arRes = $dbResult->Fetch())
					{
						$arRes['SID'] = $addPrefix ? 'L_'.$arRes['ID']: $arRes['ID'];

						$arItems[$i] = array(
							'title' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
							'desc' => CCrmLead::PrepareFormattedName(
								array(
									'HONORIFIC' => isset($arRes['HONORIFIC']) ? $arRes['HONORIFIC'] : '',
									'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
									'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
									'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
								),
								$nameFormat
							),
							'id' => $arRes['SID'],
							'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_lead_show'),
								array(
									'lead_id' => $arRes['ID']
								)
							),
							'type'  => 'lead',
							'selected' => 'N'
						);
						$leadIndex[$arRes['ID']] = &$arItems[$i];
						$i++;
					}

					// advanced info - phone number, e-mail
					$obRes = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => array_keys($leadIndex)));
					while($arRes = $obRes->Fetch())
					{
						if (isset($leadIndex[$arRes['ELEMENT_ID']])
							&& ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL'))
						{
							$item = &$leadIndex[$arRes['ELEMENT_ID']];
							if (!is_array($item['advancedInfo']))
								$item['advancedInfo'] = array();
							if (!is_array($item['advancedInfo']['multiFields']))
								$item['advancedInfo']['multiFields'] = array();
							$item['advancedInfo']['multiFields'][] = array(
								'ID' => $arRes['ID'],
								'TYPE_ID' => $arRes['TYPE_ID'],
								'VALUE_TYPE' => $arRes['VALUE_TYPE'],
								'VALUE' => $arRes['VALUE']
							);
							unset($item);
						}
					}
					unset($leadIndex);
				}
			}
			elseif($typeName === 'DEAL')
			{
				$entityIDs = CCrmDeal::GetTopIDs($count, 'DESC', $userPermissions);
				if(!empty($entityIDs))
				{
					$dbResult = CCrmDeal::GetListEx(
						array('ID' => 'DESC'),
						array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
						false,
						false,
						array('ID', 'TITLE', 'STAGE_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME')
					);

					while ($arRes = $dbResult->Fetch())
					{
						$arRes['SID'] = $addPrefix ? 'D_'.$arRes['ID']: $arRes['ID'];

						$clientTitle = (!empty($arRes['COMPANY_TITLE'])) ? $arRes['COMPANY_TITLE'] : '';
						$clientTitle .= (($clientTitle !== '' && !empty($arRes['CONTACT_FULL_NAME'])) ? ', ' : '').$arRes['CONTACT_FULL_NAME'];

						$arItems[] = array(
							'title' => isset($arRes['TITLE']) ? str_replace(array(';', ','), ' ', $arRes['TITLE']) : '',
							'desc' => $clientTitle,
							'id' => $arRes['SID'],
							'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_deal_show'),
								array(
									'deal_id' => $arRes['ID']
								)
							),
							'type'  => 'deal',
							'selected' => 'N'
						);
					}
				}
			}
			elseif($typeName === 'QUOTE')
			{
				$entityIDs = CCrmQuote::GetTopIDs($count, 'DESC', $userPermissions);
				if(!empty($entityIDs))
				{
					$dbResult = CCrmQuote::GetList(
						array('ID' => 'DESC'),
						array('@ID' => $entityIDs, 'CHECK_PERMISSIONS' => 'N'),
						false,
						false,
						array('ID', 'QUOTE_NUMBER', 'TITLE', 'COMPANY_TITLE', 'CONTACT_FULL_NAME')
					);

					while ($arRes = $dbResult->Fetch())
					{
						$arRes['SID'] = $addPrefix ? CCrmQuote::OWNER_TYPE.'_'.$arRes['ID']: $arRes['ID'];

						$clientTitle = (!empty($arRes['COMPANY_TITLE'])) ? $arRes['COMPANY_TITLE'] : '';
						$clientTitle .= (($clientTitle !== '' && !empty($arRes['CONTACT_FULL_NAME'])) ? ', ' : '').$arRes['CONTACT_FULL_NAME'];

						$quoteTitle = empty($arRes['TITLE']) ? $arRes['QUOTE_NUMBER'] : $arRes['QUOTE_NUMBER'].' - '.$arRes['TITLE'];

						$arItems[] = array(
							'title' => empty($quoteTitle) ? '' : str_replace(array(';', ','), ' ', $quoteTitle),
							'desc' => $clientTitle,
							'id' => $arRes['SID'],
							'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_quote_show'),
								array(
									'quote_id' => $arRes['ID']
								)
							),
							'type'  => 'quote',
							'selected' => 'N'
						);
					}
				}
			}
		}
		unset($typeName);

		return $arItems;
	}

	public static function PrepareListItems($arSource)
	{
		$result = array();
		if(is_array($arSource))
		{
			foreach($arSource as $k => &$v)
			{
				$result[] = array('value' => $k, 'text' => $v);
			}
			unset($v);
		}
		return $result;
	}

	public static function PrepareCommonMessages()
	{
		return array(
			'lead'=> GetMessage('CRM_FF_LEAD'),
			'contact' => GetMessage('CRM_FF_CONTACT'),
			'company' => GetMessage('CRM_FF_COMPANY'),
			'deal'=> GetMessage('CRM_FF_DEAL'),
			'quote'=> GetMessage('CRM_FF_QUOTE'),
			'ok' => GetMessage('CRM_FF_OK'),
			'cancel' => GetMessage('CRM_FF_CANCEL'),
			'close' => GetMessage('CRM_FF_CLOSE'),
			'wait' => GetMessage('CRM_FF_WAIT'),
			'noresult' => GetMessage('CRM_FF_NO_RESULT'),
			'add' => GetMessage('CRM_FF_CHOISE'),
			'edit' => GetMessage('CRM_FF_CHANGE'),
			'search' => GetMessage('CRM_FF_SEARCH'),
			'last' => GetMessage('CRM_FF_LAST')
		);
	}

	public static function PrepareRequisiteData($entityTypeId, $entityId, $options = array())
	{
		$entityTypeId = (int)$entityTypeId;
		$entityId = (int)$entityId;

		// Options
		$copyMode = (
			isset($options['COPY_MODE']) && ($options['COPY_MODE'] === true || $options['COPY_MODE'] === 'Y')
		);
		$viewDataOnly = (
			isset($options['VIEW_DATA_ONLY'])
			&& ($options['VIEW_DATA_ONLY'] === true || $options['VIEW_DATA_ONLY'] === 'Y')
		);
		$viewFormatted = (
			isset($options['VIEW_FORMATTED'])
			&& ($options['VIEW_FORMATTED'] === true || $options['VIEW_FORMATTED'] === 'Y')
		);
		$addressAsJson = (
			isset($options['ADDRESS_AS_JSON'])
			&& ($options['ADDRESS_AS_JSON'] === true || $options['ADDRESS_AS_JSON'] === 'Y')
		);

		$result = array();

		$requisite = new \Bitrix\Crm\EntityRequisite();
		$preset = new \Bitrix\Crm\EntityPreset();
		$fieldsInfo = $requisite->getFormFieldsInfo();

		if ($requisite->validateEntityReadPermission($entityTypeId, $entityId))
		{
			// selected
			$requisiteIdSelected = 0;
			$bankDetailIdSelected = 0;
			$settings = $requisite->loadSettings($entityTypeId, $entityId);
			if (is_array($settings))
			{
				if (isset($settings['REQUISITE_ID_SELECTED']))
				{
					$requisiteIdSelected = (int)$settings['REQUISITE_ID_SELECTED'];
					if ($requisiteIdSelected < 0)
						$requisiteIdSelected = 0;
				}
				if (isset($settings['BANK_DETAIL_ID_SELECTED']))
				{
					$bankDetailIdSelected = (int)$settings['BANK_DETAIL_ID_SELECTED'];
					if ($bankDetailIdSelected < 0)
						$bankDetailIdSelected = 0;
				}
			}
			$bSelected = false;

			$fieldsAllowedMap = array();
			foreach ($fieldsInfo as $fieldName => $fieldInfo)
			{
				if ($fieldInfo['isRQ'])
				{
					$fieldsAllowedMap[$fieldName] = true;
				}
			}
			unset($fieldName, $fieldInfo);
			$select = array_keys($fieldsInfo);

			// address field
			$needLoadAddresses = false;
			if (array_search(Bitrix\Crm\EntityRequisite::ADDRESS, $select, true))
			{
				$needLoadAddresses = true;
				unset($select[Bitrix\Crm\EntityRequisite::ADDRESS]);
			}

			$requisiteList = array();
			$presetList = array();
			$presetIds = array();
			$requisiteAddresses = array();
			if (is_array($select) && !empty($select))
			{
				$res = $requisite->getList(
					array(
						'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
						'filter' => array(
							'=ENTITY_TYPE_ID' => $entityTypeId,
							'=ENTITY_ID' => $entityId
						),
						'select' => $select
					)
				);
				while ($row = $res->fetch())
				{
					if ($needLoadAddresses)
					{
						$row[Bitrix\Crm\EntityRequisite::ADDRESS] = [];
					}
					$presetIds[] = (int)$row['PRESET_ID'];
					$requisiteList[$row['ID']] = $row;
					if (!$bSelected && $requisiteIdSelected === intval($row['ID']))
						$bSelected = true;
				}
				if (!empty($requisiteList))
				{
					if (!empty($presetIds))
					{
						$presetIds = array_unique($presetIds);
						$res = $preset->getList(
							array(
								'filter' => array(
									'=ID' => $presetIds,
									'=ENTITY_TYPE_ID' => \Bitrix\Crm\EntityPreset::Requisite/*,
									'=ACTIVE' => 'Y'*/
								),
								'select' => array('ID', 'NAME', 'COUNTRY_ID', 'SETTINGS')
							)
						);
						while ($row = $res->Fetch())
						{
							$presetList[$row['ID']] = $row;
						}
					}

					// load addresses
					if ($needLoadAddresses)
					{
						$rqAddr = new Bitrix\Crm\RequisiteAddress();
						$res = $rqAddr->getList(
							array(
								'filter' => array(
									'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
									'ENTITY_ID' => array_keys($requisiteList)
								),
								'select' => array(
									'ENTITY_ID',
									'TYPE_ID',
									'ADDRESS_1',
									'ADDRESS_2',
									'CITY',
									'POSTAL_CODE',
									'REGION',
									'PROVINCE',
									'COUNTRY',
									'COUNTRY_CODE',
									'LOC_ADDR_ID'
								)
							)
						);
						while ($row = $res->fetch())
						{
							$requisiteId = (int)$row['ENTITY_ID'];
							$typeId = (int)$row['TYPE_ID'];
							unset($row['ENTITY_ID'], $row['TYPE_ID']);
							$requisiteAddresses[$typeId] = \Bitrix\Crm\Format\AddressFormatter::getSingleInstance()->formatTextComma($row);

							if ($addressAsJson)
							{
								if (RequisiteAddress::isLocationModuleIncluded())
								{
									/** @var $locationAddress Address */
									$locationAddress = RequisiteAddress::makeLocationAddressByFields($row);
									if ($locationAddress)
									{
										$requisiteList[$requisiteId][Bitrix\Crm\EntityRequisite::ADDRESS][$typeId] =
											$locationAddress->toJson();
									}
									unset($locationAddress);
								}
							}
							else
							{
								$requisiteList[$requisiteId][Bitrix\Crm\EntityRequisite::ADDRESS][$typeId] = $row;
							}
						}
					}

					$index = 0;
					foreach ($requisiteList as $requisiteId => $fields)
					{
						$presetID = isset($fields['PRESET_ID']) ? (int)$fields['PRESET_ID'] : 0;
						$bankDetailCountryId = 0;
						$dataFields = array();
						foreach ($fields as $fName => $fValue)
						{
							if ($copyMode && ($fName === 'ID' || $fName === 'ENTITY_ID'))
								$fValue = 0;

							if ($fValue instanceof \Bitrix\Main\Type\DateTime)
								$dataFields[$fName] = $fValue->toString();
							else
								$dataFields[$fName] = $fValue;
						}
						unset($fName, $fValue);

						$presetFieldsMap = [];
						$presetFieldsIndex = [];
						$presetFieldsSort = [
							'ID' => [],
							'SORT' => [],
							'FIELD_NAME' => []
						];
						if (is_array($presetList[$fields['PRESET_ID']]))
						{
							if (is_array($presetList[$fields['PRESET_ID']]['SETTINGS']))
							{
								$presetFieldsInfo = $preset->settingsGetFields($presetList[$fields['PRESET_ID']]['SETTINGS']);
								foreach ($presetFieldsInfo as $fieldInfo)
								{
									if (isset($fieldInfo['FIELD_NAME']))
									{
										$presetFieldsSort['ID'][] = isset($fieldInfo['ID']) ? (int)$fieldInfo['ID'] : 0;
										$presetFieldsSort['SORT'][] = isset($fieldInfo['SORT']) ? (int)$fieldInfo['SORT'] : 0;
										$presetFieldsSort['FIELD_NAME'][] = $fieldInfo['FIELD_NAME'];
									}
								}
								unset($presetFieldsInfo, $fieldInfo);
							}
							if (is_array($presetList[$fields['PRESET_ID']]['COUNTRY_ID']))
							{
								$bankDetailCountryId = (int)$presetList[$fields['PRESET_ID']]['COUNTRY_ID'];
							}
						}
						if (!empty($presetFieldsSort['FIELD_NAME']))
						{
							if(array_multisort(
								$presetFieldsSort['SORT'], SORT_ASC, SORT_NUMERIC,
								$presetFieldsSort['ID'], SORT_ASC, SORT_NUMERIC,
								$presetFieldsSort['FIELD_NAME']))
							{
								$presetFieldsMap = array_fill_keys($presetFieldsSort['FIELD_NAME'], true);
								$presetFieldsIndex = array_flip($presetFieldsSort['FIELD_NAME']);
							}
						}
						unset($presetFieldsSort);

						// sort fields by preset
						$viewDataFields = array();
						if (!empty($presetFieldsIndex))
						{
							$dataFieldsSortedIndex = array();
							$dataFieldsUnsortedIndex = array();
							foreach ($dataFields as $dataFieldName => &$dataField)
							{
								if (isset($presetFieldsIndex[$dataFieldName]))
									$dataFieldsSortedIndex[$presetFieldsIndex[$dataFieldName]] = $dataFieldName;
								else
									$dataFieldsUnsortedIndex[] = $dataFieldName;
							}
							unset($dataFieldName, $dataField);
							if (!empty($dataFieldsSortedIndex))
							{
								ksort($dataFieldsSortedIndex, SORT_NUMERIC);
								foreach ($dataFieldsSortedIndex as $dataFieldName)
									$viewDataFields[$dataFieldName] = &$dataFields[$dataFieldName];
								unset($dataFieldName);
							}
							unset($dataFieldsSortedIndex);
							if (!empty($dataFieldsUnsortedIndex))
							{
								foreach ($dataFieldsUnsortedIndex as $dataFieldName)
									$viewDataFields[$dataFieldName] = &$dataFields[$dataFieldName];
								unset($dataFieldName);
							}
							unset($dataFieldsUnsortedIndex);
						}
						else
						{
							$viewDataFields = &$dataFields;
						}

						$requisiteData = array();
						if (!$viewDataOnly)
						{
							$requisiteData['fields'] = $dataFields;
						}
						$fieldsInView = array_intersect_assoc($presetFieldsMap, $fieldsAllowedMap);

						if ($viewFormatted)
						{
							$requisiteData['viewData'] = $requisite->prepareViewDataFormatted($viewDataFields, $fieldsInView);
						}
						else
						{
							$requisiteData['viewData'] = $requisite->prepareViewData($viewDataFields, $fieldsInView);
						}

						unset($presetFields, $fieldsInView);
						if ($bankDetailCountryId <= 0)
							$bankDetailCountryId = \Bitrix\Crm\EntityPreset::getCurrentCountryId();
						$bankDetailsData = self::PrepareBankDetailsData(
							\CCrmOwnerType::Requisite,
							$requisiteId,
							array(
								'VIEW_DATA_ONLY' => $viewDataOnly,
								'SKIP_CHECK_PERMISSION' => true,
								'COUNTRY_ID' => $bankDetailCountryId,
								'BANK_DETAIL_ID_SELECTED' => $bankDetailIdSelected
							)
						);
						if (!$viewDataOnly)
						{
							$requisiteData['bankDetailFieldsList'] = &$bankDetailsData['bankDetailFieldsList'];
						}
						$requisiteData['bankDetailViewDataList'] = &$bankDetailsData['bankDetailViewDataList'];
						$requisiteData['bankDetailIdSelected'] = &$bankDetailsData['bankDetailIdSelected'];
						$requisiteData['formattedAddresses'] = $requisiteAddresses;

						unset($viewDataFields, $bankDetailsData, $requisiteAddress);
						$requisiteDataJson = '';
						$requisiteDataSign = '';
						if (is_array($requisiteData))
						{
							$jsonData = null;
							try
							{
								$jsonData = \Bitrix\Main\Web\Json::encode($requisiteData);
							}
							catch (\Bitrix\Main\SystemException $e)
							{}
							if ($jsonData)
							{
								if ($viewDataOnly)
								{
									$requisiteDataJson = $jsonData;
								}
								else
								{
									$signer = new \Bitrix\Main\Security\Sign\Signer();
									$requisiteDataSign = '';
									try
									{
										$requisiteDataSign = $signer->getSignature(
											$jsonData,
											'crm.requisite.edit-'.$entityTypeId
										);
									}
									catch (\Bitrix\Main\SystemException $e)
									{}

									if (!empty($requisiteDataSign))
									{
										$requisiteDataJson = $jsonData;
									}
								}
							}
							unset($jsonData);
						}

						if (!empty($requisiteDataJson) && ($viewDataOnly || !empty($requisiteDataSign)))
						{
							if (is_array($presetList[$presetID])
								&& isset($presetList[$presetID]['COUNTRY_ID']))
							{
								$presetCountryId = (int)$presetList[$presetID]['COUNTRY_ID'];
							}
							else
							{
								$presetCountryId = 0;
							}
							$resultItem = array(
								'presetId' => $presetID,
								'presetCountryId' => $presetCountryId,
								'requisiteId' => $copyMode ? 0 : $requisiteId,
								'entityTypeId' => $entityTypeId,
								'entityId' => $copyMode ? 0 : $entityId,
								'requisiteData' => $requisiteDataJson
							);

							if (!$viewDataOnly)
							{
								$resultItem['requisiteDataSign'] = $requisiteDataSign;
							}
							$resultItem['selected'] = (!$bSelected && $index === 0
								|| $requisiteIdSelected === intval($requisiteId));
							$result[$index++] = $resultItem;
							unset($resultItem);
						}
						unset($dataFields);
					}
					unset($requisiteId, $fields);
				}
			}
		}

		return $result;
	}

	public static function PrepareBankDetailsData($entityTypeId, $entityId, $options = array())
	{
		$entityTypeId = (int)$entityTypeId;
		$entityId = (int)$entityId;
		$copyMode = (isset($options['COPY_MODE'])
			&& ($options['COPY_MODE'] === true || $options['COPY_MODE'] === 'Y'));
		$viewDataOnly = (isset($options['VIEW_DATA_ONLY'])
			&& ($options['VIEW_DATA_ONLY'] === true || $options['VIEW_DATA_ONLY'] === 'Y'));
		$skipCheckPermission = (isset($options['SKIP_CHECK_PERMISSION'])
			&& ($options['SKIP_CHECK_PERMISSION'] === true || $options['SKIP_CHECK_PERMISSION'] === 'Y'));

		$countryId = isset($options['COUNTRY_ID']) ? (int)$options['COUNTRY_ID'] : 0;
		$currentCountryId = \Bitrix\Crm\EntityPreset::getCurrentCountryId();

		$bankDetailIdSelected = 0;
		if (isset($options['BANK_DETAIL_ID_SELECTED']))
		{
			$bankDetailIdSelected = (int)$options['BANK_DETAIL_ID_SELECTED'];
			if ($bankDetailIdSelected < 0)
				$bankDetailIdSelected = 0;
		}

		$result = array();
		if (!$viewDataOnly)
			$result['bankDetailFieldsList'] = array();
		$result['bankDetailViewDataList'] = array();
		$result['bankDetailIdSelected'] = $bankDetailIdSelected;

		$bankDetail = new \Bitrix\Crm\EntityBankDetail();
		if ($skipCheckPermission || $bankDetail->validateEntityReadPermission($entityTypeId, $entityId))
		{
			$select = array_merge(
				array('ID', 'ENTITY_TYPE_ID', 'ENTITY_ID', 'COUNTRY_ID', 'NAME'),
				$bankDetail->getRqFields(),
				array('COMMENTS')
			);
			$res = $bankDetail->getList(
				array(
					'order' => array('SORT', 'ID'),
					'filter' => array('=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite, '=ENTITY_ID' => $entityId),
					'select' => $select
				)
			);
			$curDateTime = new \Bitrix\Main\Type\DateTime();
			$curUserId = CCrmSecurityHelper::GetCurrentUserID();
			$n = 0;
			$index = 0;
			$selectedIndex = -1;
			while ($row = $res->fetch())
			{
				if ($copyMode)
				{
					$row['ID'] = 0;
					$row['DATE_CREATE'] = $curDateTime;
					$row['DATE_MODIFY'] = $curDateTime;
					$row['CREATED_BY_ID'] = $curUserId;
					$row['MODIFY_BY_ID'] = $curUserId;
				}

				foreach ($row as $fName => $fValue)
				{
					if ($fValue instanceof \Bitrix\Main\Type\DateTime)
						$row[$fName] = $fValue->toString();
				}

				$pseudoId = ($row['ID'] > 0) ? $row['ID'] : 'n'.$n++;
				if (!$viewDataOnly)
					$result['bankDetailFieldsList'][$pseudoId] = $row;
				if ($countryId <= 0)
					$countryId = isset($row['COUNTRY_ID']) ? (int)$row['COUNTRY_ID'] : $currentCountryId;
				if ($selectedIndex < 0 && $bankDetailIdSelected === intval($row['ID']))
					$selectedIndex = $index;
				$result['bankDetailViewDataList'][] = array(
					'pseudoId' => $pseudoId,
					'viewData' => $bankDetail->prepareViewData(
						$row, array_keys($bankDetail->getFormFieldsInfoByCountry($countryId))
					),
					'selected' => false
				);

				$index++;
			}
			unset($select, $res, $row);

			if (!empty($result['bankDetailViewDataList']))
			{
				if ($selectedIndex < 0)
				{
					$selectedIndex = 0;
					$result['bankDetailIdSelected'] = 0;
				}
				$result['bankDetailViewDataList'][$selectedIndex]['selected'] = true;
			}
		}

		return $result;
	}

	private static function getPhoneCountryList(string $entityId, int $elementId): array
	{
		$multiFieldIds = [];
		$dbResultIds = CCrmFieldMulti::GetList(['ID' => 'asc'], ['ENTITY_ID' => $entityId, 'ELEMENT_ID' => $elementId]);
		while ($row = $dbResultIds->fetch())
		{
			$multiFieldIds[] = (int)$row['ID'];
		}

		return CCrmFieldMulti::GetPhoneCountryList($multiFieldIds);
	}
}
