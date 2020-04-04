<?php
class CCrmSipHelper
{
	private static $ENABLE_VOX_IMPLANT = null;

	public static function isEnabled()
	{
		if (self::$ENABLE_VOX_IMPLANT === null)
		{
			self::$ENABLE_VOX_IMPLANT = IsModuleInstalled('voximplant') && CModule::IncludeModule('voximplant');
		}

		return self::$ENABLE_VOX_IMPLANT;
	}

	public static function checkPhoneNumber($number)
	{
		if (!self::isEnabled())
		{
			return false;
		}

		return CVoxImplantMain::Enable($number);
	}

	public static function findByPhoneNumber($number, $params = array())
	{
		if (!is_string($number))
		{
			throw new \Bitrix\Main\ArgumentTypeException('number', 'string');
		}

		if ($number === '')
		{
			throw new \Bitrix\Main\ArgumentException('Is empty', 'number');
		}

		if (!is_array($params))
		{
			$params = array();
		}

		$dups = array();
		$criterion = new \Bitrix\Crm\Integrity\DuplicateCommunicationCriterion('PHONE', $number);
		$entityTypes = array(CCrmOwnerType::Contact, CCrmOwnerType::Company, CCrmOwnerType::Lead);
		foreach ($entityTypes as $entityType)
		{
			$duplicate = $criterion->find($entityType, 1);
			if ($duplicate !== null)
			{
				$dups[] = $duplicate;
			}
		}

		$entityByType = array();
		foreach ($dups as &$dup)
		{
			/** @var \Bitrix\Crm\Integrity\Duplicate $dup */
			$entities = $dup->getEntities();
			if (!(is_array($entities) && !empty($entities)))
			{
				continue;
			}

			//Each entity type limited by 50 items
			foreach ($entities as &$entity)
			{
				/** @var \Bitrix\Crm\Integrity\DuplicateEntity $entity */
				$entityTypeID = $entity->getEntityTypeID();
				$entityID = $entity->getEntityID();

				$fields = self::getEntityFields($entityTypeID, $entityID, $params);
				if(!is_array($fields))
					continue;

				$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
				if (!isset($entityByType[$entityTypeName]))
				{
					$entityByType[$entityTypeName] = array($fields);
				}
				elseif (!in_array($entityID, $entityByType[$entityTypeName], true))
				{
					$entityByType[$entityTypeName][] = $fields;
				}
			}
		}
		unset($dup);
		return $entityByType;
	}

	public static function getEntityFields($entityTypeID, $entityID, $params = array())
	{
		$fields = null;

		$userID = isset($params['USER_ID']) ? intval($params['USER_ID']) : 0;
		if ($userID <= 0)
		{
			$userID = CCrmPerms::GetCurrentUserID();
		}

		$isAdmin = CCrmPerms::IsAdmin($userID);

		$userPermissions = CCrmPerms::GetUserPermissions($userID);
		$enableExtendedMode = isset($params['ENABLE_EXTENDED_MODE']) ? (bool)$params['ENABLE_EXTENDED_MODE'] : true;

		if ($entityTypeID === CCrmOwnerType::Contact)
		{
			$dbEntity = CCrmContact::GetListEx(
				array(),
				array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array(
					'ID',
					'HONORIFIC',
					'NAME',
					'SECOND_NAME',
					'LAST_NAME',
					'PHOTO',
					'POST',
					'COMPANY_ID',
					'COMPANY_TITLE',
					'ASSIGNED_BY_ID'
				)
			);

			$entityFields = is_object($dbEntity) ? $dbEntity->Fetch() : null;
			if (is_array($entityFields))
			{
				$formattedName = CCrmContact::PrepareFormattedName(
					array(
						'HONORIFIC' => isset($entityFields['HONORIFIC']) ? $entityFields['HONORIFIC'] : '',
						'NAME' => isset($entityFields['NAME']) ? $entityFields['NAME'] : '',
						'SECOND_NAME' => isset($entityFields['SECOND_NAME']) ? $entityFields['SECOND_NAME'] : '',
						'LAST_NAME' => isset($entityFields['LAST_NAME']) ? $entityFields['LAST_NAME'] : ''
					)
				);

				$fields = array(
					'ID' => intval($entityFields['ID']),
					'FORMATTED_NAME' => $formattedName,
					'PHOTO' => isset($entityFields['PHOTO']) ? intval($entityFields['PHOTO']) : 0,
					'COMPANY_ID' => isset($entityFields['COMPANY_ID']) ? intval($entityFields['COMPANY_ID']) : 0,
					'COMPANY_TITLE' => isset($entityFields['COMPANY_TITLE']) ? $entityFields['COMPANY_TITLE'] : '',
					'POST' => isset($entityFields['POST']) ? $entityFields['POST'] : '',
					'ASSIGNED_BY_ID' => isset($entityFields['ASSIGNED_BY_ID']) ? intval($entityFields['ASSIGNED_BY_ID']) : 0,
					'CAN_READ' => CCrmContact::CheckReadPermission($entityID, $userPermissions)
				);

				if ($fields['CAN_READ'] && $enableExtendedMode)
				{
					$deals = array();
					$dbDeal = CCrmDeal::GetListEx(
						array('BEGINDATE' => 'ASC'),
						array('=ASSOCIATED_CONTACT_ID' => $entityID, 'CLOSED' => 'N', 'CHECK_PERMISSIONS' => $isAdmin ? 'N' : 'Y'),
						false,
						array('nTopCount' => 2),
						array('ID', 'TITLE', 'STAGE_ID', 'OPPORTUNITY', 'CURRENCY_ID', 'CATEGORY_ID'),
						array('PERMS' => $userPermissions)
					);

					if (is_object($dbDeal))
					{
						while ($dealFields = $dbDeal->Fetch())
						{
							$dealID = intval($dealFields['ID']);
							//$dealFields['CAN_READ'] = CCrmDeal::CheckReadPermission($dealID, $userPermissions);
							$dealFields['SHOW_URL'] = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Deal, $dealID);
							$dealFields['FORMATTED_OPPORTUNITY'] = CCrmCurrency::MoneyToString($dealFields['OPPORTUNITY'], $dealFields['CURRENCY_ID']);
							$deals[] = $dealFields;
						}
					}

					$fields['DEALS'] = &$deals;
					unset($deals);
					
					$invoices = array();
					$dbInvoice = CCrmInvoice::GetList(
						array('DATE_INSERT' => 'ASC'),
						array('=UF_CONTACT_ID' => $entityID, 'PAYED' => 'N', 'CANCELED' => 'N', 'CHECK_PERMISSIONS' => $isAdmin ? 'N' : 'Y'),
						false,
						array('nTopCount' => 2),
						array('ID', 'ORDER_TOPIC', 'DATE_BILL', 'PRICE', 'CURRENCY', 'STATUS_ID' )
					);
					if(is_object($dbInvoice))
					{
						while($invoiceFields = $dbInvoice->Fetch())
						{
							$invoiceFields['PRICE_FORMATTED'] = CCrmCurrency::MoneyToString($invoiceFields['PRICE'], $invoiceFields['CURRENCY']);
							$invoiceFields['SHOW_URL'] = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Invoice, $invoiceFields['ID']);
							$invoices[] = $invoiceFields;
						}
					}

					$fields['INVOICES'] = $invoices;
					unset($invoices);
				}
			}
		}
		elseif ($entityTypeID === CCrmOwnerType::Company)
		{
			$dbEntity = CCrmCompany::GetListEx(
				array(),
				array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array(
					'ID',
					'TITLE',
					'LOGO',
					'ASSIGNED_BY_ID'
				)
			);

			$entityFields = is_object($dbEntity) ? $dbEntity->Fetch() : null;
			if (is_array($entityFields))
			{
				$fields = array(
					'ID' => intval($entityFields['ID']),
					'TITLE' => isset($entityFields['TITLE']) ? $entityFields['TITLE'] : '',
					'LOGO' => isset($entityFields['LOGO']) ? intval($entityFields['LOGO']) : 0,
					'ASSIGNED_BY_ID' => isset($entityFields['ASSIGNED_BY_ID']) ? intval($entityFields['ASSIGNED_BY_ID']) : 0,
					'CAN_READ' => CCrmCompany::CheckReadPermission($entityID, $userPermissions)
				);

				if ($fields['CAN_READ'] && $enableExtendedMode)
				{
					$deals = array();
					$dbDeal = CCrmDeal::GetListEx(
						array('BEGINDATE' => 'ASC'),
						array('=COMPANY_ID' => $entityID, 'CLOSED' => 'N', 'CHECK_PERMISSIONS' => $isAdmin ? 'N' : 'Y'),
						false,
						array('nTopCount' => 2),
						array('ID', 'TITLE', 'STAGE_ID', 'OPPORTUNITY', 'CURRENCY_ID', 'CATEGORY_ID'),
						array('PERMS' => $userPermissions)
					);

					if (is_object($dbDeal))
					{
						while ($dealFields = $dbDeal->Fetch())
						{
							$dealID = intval($dealFields['ID']);
							//$dealFields['CAN_READ'] = CCrmDeal::CheckReadPermission($dealID, $userPermissions);
							$dealFields['SHOW_URL'] = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Deal, $dealID);
							$dealFields['FORMATTED_OPPORTUNITY'] = CCrmCurrency::MoneyToString($dealFields['OPPORTUNITY'], $dealFields['CURRENCY_ID']);
							$deals[] = $dealFields;
						}
					}

					$fields['DEALS'] = &$deals;
					unset($deals);

					$invoices = array();
					$dbInvoice = CCrmInvoice::GetList(
						array('DATE_INSERT' => 'ASC'),
						array('=UF_COMPANY_ID' => $entityID, 'PAYED' => 'N', 'CANCELED' => 'N', 'CHECK_PERMISSIONS' => $isAdmin ? 'N' : 'Y'),
						false,
						array('nTopCount' => 2),
						array('ID', 'ORDER_TOPIC', 'DATE_BILL', 'PRICE', 'CURRENCY', 'STATUS_ID' )
					);
					if(is_object($dbInvoice))
					{
						while($invoiceFields = $dbInvoice->Fetch())
						{
							$invoiceFields['PRICE_FORMATTED'] = CCrmCurrency::MoneyToString($invoiceFields['PRICE'], $invoiceFields['CURRENCY']);
							$invoiceFields['SHOW_URL'] = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Invoice, $invoiceFields['ID']);
							$invoices[] = $invoiceFields;
						}
					}

					$fields['INVOICES'] = $invoices;
					unset($invoices);
				}
			}
		}
		elseif ($entityTypeID === CCrmOwnerType::Lead)
		{
			$dbEntity = CCrmLead::GetListEx(
				array(),
				array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array(
					'ID',
					'TITLE',
					'HONORIFIC',
					'NAME',
					'SECOND_NAME',
					'LAST_NAME',
					'POST',
					'COMPANY_TITLE',
					'ASSIGNED_BY_ID',
					'STATUS_ID'
				)
			);

			$entityFields = is_object($dbEntity) ? $dbEntity->Fetch() : null;
			if (is_array($entityFields))
			{
				$formattedName = '';
				if (!empty($entityFields['NAME']) || !empty($entityFields['SECOND_NAME']) || !empty($entityFields['LAST_NAME']))
				{
					$formattedName = CCrmLead::PrepareFormattedName(
						array(
							'HONORIFIC' => isset($entityFields['HONORIFIC']) ? $entityFields['HONORIFIC'] : '',
							'NAME' => isset($entityFields['NAME']) ? $entityFields['NAME'] : '',
							'SECOND_NAME' => isset($entityFields['SECOND_NAME']) ? $entityFields['SECOND_NAME'] : '',
							'LAST_NAME' => isset($entityFields['LAST_NAME']) ? $entityFields['LAST_NAME'] : ''
						)
					);
				}

				$fields = array(
					'ID' => intval($entityFields['ID']),
					'TITLE' => isset($entityFields['TITLE']) ? $entityFields['TITLE'] : '',
					'FORMATTED_NAME' => $formattedName,
					'COMPANY_TITLE' => isset($entityFields['COMPANY_TITLE']) ? $entityFields['COMPANY_TITLE'] : '',
					'POST' => isset($entityFields['POST']) ? $entityFields['POST'] : '',
					'ASSIGNED_BY_ID' => isset($entityFields['ASSIGNED_BY_ID']) ? intval($entityFields['ASSIGNED_BY_ID']) : 0,
					'CAN_READ' => CCrmLead::CheckReadPermission($entityID, $userPermissions),
					'IS_FINAL' => \Bitrix\Crm\PhaseSemantics::isFinal(CCrmLead::GetSemanticID($entityFields['STATUS_ID']))
				);
			}
		}

		if (!is_array($fields))
		{
			return null;
		}


		if ($fields['CAN_READ'] && $enableExtendedMode)
		{
			$showUrl = $fields['SHOW_URL'] = CCrmOwnerType::GetEntityShowPath($entityTypeID, $entityID);
			$defaultFormId = '';
			switch ($entityTypeID)
			{
				case CCrmOwnerType::Lead:
					$defaultFormId = CCrmLead::DEFAULT_FORM_ID;
					break;
				case CCrmOwnerType::Contact:
					$defaultFormId = CCrmContact::DEFAULT_FORM_ID;
					break;
				case CCrmOwnerType::Company:
					$defaultFormId = CCrmCompany::DEFAULT_FORM_ID;
					break;
			}
			
			if ($showUrl !== '' && $defaultFormId != '')
			{
				$fields['ACTIVITY_LIST_URL'] = CCrmUrlUtil::AddUrlParams(
					$showUrl,
					array("{$defaultFormId}_active_tab" => 'tab_activity')
				);

				$fields['INVOICE_LIST_URL'] = CCrmUrlUtil::AddUrlParams(
					$showUrl,
					array("{$defaultFormId}_active_tab" => 'tab_invoice')
				);

				if ($entityTypeID === CCrmOwnerType::Contact || $entityTypeID === CCrmOwnerType::Company)
				{
					$fields['DEAL_LIST_URL'] = CCrmUrlUtil::AddUrlParams(
						$showUrl,
						array("{$defaultFormId}_active_tab" => 'tab_deal')
					);
				}
			}

			$activities = array();

			// We can skip permissions check, because user should be able to read activities,
			// bound to the entity, that he is able to read (see $fields['CAN_READ'])
			$dbActivity = CCrmActivity::GetList(
				array('DEADLINE' => 'ASC'),
				array(
					'COMPLETED' => 'N',
					'BINDINGS' => array(array('OWNER_TYPE_ID' => $entityTypeID, 'OWNER_ID' => $entityID)),
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				array('nTopCount' => 4),
				array('ID', 'SUBJECT', 'START_TIME', 'END_TIME', 'DEADLINE'),
				array('PERMS' => $userPermissions)
			);

			if (is_object($dbActivity))
			{
				while ($activityFields = $dbActivity->Fetch())
				{
					$activityFields['SHOW_URL'] = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Activity, $activityFields['ID']);
					if(CCrmDateTimeHelper::IsMaxDatabaseDate($activityFields['DEADLINE']))
					{
						$activityFields['DEADLINE'] = '';
					}
					$activities[] = &$activityFields;
					unset($activityFields);
				}
			}

			$fields['ACTIVITIES'] = &$activities;
			unset($activities);
		}

		return $fields;
	}
}