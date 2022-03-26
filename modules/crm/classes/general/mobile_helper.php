<?php

use Bitrix\Crm\ContactAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\LeadAddress;
use Bitrix\Crm\UserField\Visibility\VisibilityManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Ui\EntityForm\Scope;
use Bitrix\UI\Form\EntityEditorConfigScope;

Loc::loadMessages(__FILE__);

class CCrmMobileHelper
{
	private const USER_OPTION_CATEGORY = 'crm.entity.editor';

	private static $LEAD_STATUSES = null;
	private static $DEAL_STAGES = null;
	private static $INVOICE_STATUSES = null;
	private static $INVOICE_PROPERTY_INFOS = null;
	private static $STATUS_LISTS = array();

	private static function GetStatusList($entityId)
	{
		if(!isset(self::$STATUS_LISTS[$entityId]))
		{
			self::$STATUS_LISTS[$entityId] = CCrmStatus::GetStatusList($entityId);
			if(!is_array(self::$STATUS_LISTS[$entityId]))
			{
				self::$STATUS_LISTS[$entityId] = array();
			}
		}

		return self::$STATUS_LISTS[$entityId];
	}

	public static function getInvoiceSortFields()
	{
		$fields = array(
			'ACCOUNT_NUMBER' => array('id' => 'ACCOUNT_NUMBER', 'name' => GetMessage('CRM_COLUMN_INVOICE_ACCOUNT_NUMBER'), 'sort' => 'account_number'),
			'ORDER_TOPIC' => array('id' => 'ORDER_TOPIC', 'name' => GetMessage('CRM_COLUMN_INVOICE_ORDER_TOPIC'), 'sort' => 'order_topic'),
			'STATUS_ID' => array('id' => 'STATUS_ID', 'name' => GetMessage('CRM_COLUMN_INVOICE_STATUS_ID'), 'sort' => 'status_id'),
			'PRICE' => array('id' => 'PRICE', 'name' => GetMessage('CRM_COLUMN_INVOICE_PRICE'), 'sort' => 'price'),
			'DATE_PAY_BEFORE' => array('id' => 'DATE_PAY_BEFORE', 'name' => GetMessage('CRM_COLUMN_INVOICE_DATE_PAY_BEFORE'), 'sort' => 'date_pay_before'),
			'DATE_INSERT' => array('id' => 'DATE_INSERT', 'name' => GetMessage('CRM_COLUMN_INVOICE_DATE_INSERT'), 'sort' => 'date_insert'),
			'RESPONSIBLE_ID' => array('id' => 'RESPONSIBLE_ID', 'name' => GetMessage('CRM_COLUMN_INVOICE_RESPONSIBLE'), 'sort' => 'responsible'),

			// advanced fields
			'ID' => array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_INVOICE_ID'), 'sort' => 'id'),
			'COMMENTS' => array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_INVOICE_COMMENTS'), 'sort' => 'comments'),
			'CURRENCY' => array('id' => 'CURRENCY', 'name' => GetMessage('CRM_COLUMN_INVOICE_CURRENCY'), 'sort' => 'currency'),
			'DATE_BILL' => array('id' => 'DATE_BILL', 'name' => GetMessage('CRM_COLUMN_INVOICE_DATE_BILL'), 'sort' => 'date_bill'),
			'DATE_MARKED' => array('id' => 'DATE_MARKED', 'name' => GetMessage('CRM_COLUMN_INVOICE_DATE_MARKED'), 'sort' => 'date_marked'),
			'DATE_STATUS' => array('id' => 'DATE_STATUS', 'name' => GetMessage('CRM_COLUMN_INVOICE_DATE_STATUS'), 'sort' => 'date_status'),
			'DATE_UPDATE' => array('id' => 'DATE_UPDATE', 'name' => GetMessage('CRM_COLUMN_INVOICE_DATE_UPDATE'), 'sort' => 'date_update'),
			'PAY_SYSTEM_ID' => array('id' => 'PAY_SYSTEM_ID', 'name' => GetMessage('CRM_COLUMN_INVOICE_PAY_SYSTEM_ID'), 'sort' => 'pay_system_id'),
			'PAY_VOUCHER_DATE' => array('id' => 'PAY_VOUCHER_DATE', 'name' => GetMessage('CRM_COLUMN_INVOICE_PAY_VOUCHER_DATE'), 'sort' => 'pay_voucher_date'),
			//	'PAY_VOUCHER_NUM' => array('id' => 'PAY_VOUCHER_NUM', 'name' => GetMessage('CRM_COLUMN_INVOICE_PAY_VOUCHER_NUM'), 'sort' => 'pay_voucher_num'),
			'PERSON_TYPE_ID' => array('id' => 'PERSON_TYPE_ID', 'name' => GetMessage('CRM_COLUMN_INVOICE_PERSON_TYPE_ID'), 'sort' => 'person_type_id'),
			'REASON_MARKED' => array('id' => 'REASON_MARKED', 'name' => GetMessage('CRM_COLUMN_INVOICE_REASON_MARKED'), 'sort' => 'reason_marked'),
			'TAX_VALUE' => array('id' => 'TAX_VALUE', 'name' => GetMessage('CRM_COLUMN_INVOICE_TAX_VALUE'), 'sort' => 'tax_value'),
			'USER_DESCRIPTION' => array('id' => 'USER_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_INVOICE_USER_DESCRIPTION'), 'sort' => 'user_description')
		);

		return $fields;
	}

	public static function getInvoiceFields($includeUserFields = true)
	{
		$fields = array(
			'ID' => array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_INVOICE_ID'), 'align' => 'right'),
		//	'ORDER_TOPIC' => array('id' => 'ORDER_TOPIC', 'name' => GetMessage('CRM_COLUMN_INVOICE_ORDER_TOPIC')),
			'ACCOUNT_NUMBER' => array('id' => 'ACCOUNT_NUMBER', 'name' => GetMessage('CRM_COLUMN_INVOICE_ACCOUNT_NUMBER')),
			'STATUS_ID' => array('id' => 'STATUS_ID', 'name' => GetMessage('CRM_COLUMN_INVOICE_STATUS_ID'), 'type' => 'HTML'),
			'FORMATTED_PRICE' => array('id' => 'FORMATTED_PRICE', 'name' => GetMessage('CRM_COLUMN_INVOICE_FORMATTED_PRICE'), 'align' => 'right'),
			'ENTITIES_LINKS' => array('id' => 'ENTITIES_LINKS', 'name' => GetMessage('CRM_COLUMN_INVOICE_ENTITIES_LINKS')),
			'DATE_PAY_BEFORE' => array('id' => 'DATE_PAY_BEFORE', 'name' => GetMessage('CRM_COLUMN_INVOICE_DATE_PAY_BEFORE')),
			'DATE_INSERT' => array('id' => 'DATE_INSERT', 'name' => GetMessage('CRM_COLUMN_INVOICE_DATE_INSERT')),
			'RESPONSIBLE' => array('id' => 'RESPONSIBLE', 'name' => GetMessage('CRM_COLUMN_INVOICE_RESPONSIBLE')),

			// advanced fields
			'COMMENTS' => array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_INVOICE_COMMENTS')),
			'DATE_BILL' => array('id' => 'DATE_BILL', 'name' => GetMessage('CRM_COLUMN_INVOICE_DATE_BILL')),
			'DATE_MARKED' => array('id' => 'DATE_MARKED', 'name' => GetMessage('CRM_COLUMN_INVOICE_DATE_MARKED')),
			'DATE_STATUS' => array('id' => 'DATE_STATUS', 'name' => GetMessage('CRM_COLUMN_INVOICE_DATE_STATUS')),
			'DATE_UPDATE' => array('id' => 'DATE_UPDATE', 'name' => GetMessage('CRM_COLUMN_INVOICE_DATE_UPDATE')),
			'PAY_SYSTEM_ID' => array('id' => 'PAY_SYSTEM_ID', 'name' => GetMessage('CRM_COLUMN_INVOICE_PAY_SYSTEM_ID')),
			'PAY_VOUCHER_DATE' => array('id' => 'PAY_VOUCHER_DATE', 'name' => GetMessage('CRM_COLUMN_INVOICE_PAY_VOUCHER_DATE')),
			'PAY_VOUCHER_NUM' => array('id' => 'PAY_VOUCHER_NUM', 'name' => GetMessage('CRM_COLUMN_INVOICE_PAY_VOUCHER_NUM')),
			'PERSON_TYPE_ID' => array('id' => 'PERSON_TYPE_ID', 'name' => GetMessage('CRM_COLUMN_INVOICE_PERSON_TYPE_ID')),
			'REASON_MARKED' => array('id' => 'REASON_MARKED', 'name' => GetMessage('CRM_COLUMN_INVOICE_REASON_MARKED')),
			'TAX_VALUE' => array('id' => 'TAX_VALUE', 'name' => GetMessage('CRM_COLUMN_INVOICE_TAX_VALUE'), 'align' => 'right'),
			'USER_DESCRIPTION' => array('id' => 'USER_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_INVOICE_USER_DESCRIPTION')),
			'AUDIO_CALL' => array('id' => 'AUDIO_CALL', 'name' => GetMessage('CRM_COLUMN_AUDIO_CALL'), 'type' => 'HTML')
		);

		if ($includeUserFields)
		{
			self::getFieldUser($fields, CCrmInvoice::$sUFEntityID);
		}

		return $fields;
	}

	public static function getInvoiceFilterFields()
	{
		$arStatuses = array();
		$arStatuses[""] = GetMessage("M_CRM_NOT_SELECTED");
		$invoiceStatuses = CCrmStatus::GetStatusList('INVOICE_STATUS');
		foreach($invoiceStatuses as $code => $name)
		{
			$arStatuses[$code] = $name;
		}
		$filterFields = array(
			array(
				"type" => "text",
				"id" => "ORDER_TOPIC",
				"name" => GetMessage('CRM_COLUMN_INVOICE_ORDER_TOPIC'),
				"value" => ""
			),
			array(
				"type" => "select",
				"id" => "STATUS_ID",
				"name" => GetMessage('CRM_COLUMN_INVOICE_STATUS_ID'),
				"items" => $arStatuses,
				"value" => ""
			),
			array(
				"type" => "select-user",
				"id" => "RESPONSIBLE_ID",
				"name" => GetMessage('CRM_COLUMN_INVOICE_RESPONSIBLE'),
				"value" => ""
			),
		);

		return $filterFields;
	}

	public static function PrepareInvoiceItem(&$item, &$params, $enums = array(), $options = array())
	{
		$itemID = intval($item['~ID']);

		if(!isset($item['~ACCOUNT_NUMBER']))
		{
			$item['~ACCOUNT_NUMBER'] = $item['ACCOUNT_NUMBER'] = '';
		}

		if(!isset($item['~ORDER_TOPIC']))
		{
			$item['~ORDER_TOPIC'] = $item['ORDER_TOPIC'] = '';
		}

		// COMMENTS -->
		if(!isset($item['~COMMENTS']))
		{
			$item['~COMMENTS'] = $item['COMMENTS'] = '';
		}
		$item['COMMENTS'] = $item['~COMMENTS'];

		if(!isset($item['~USER_DESCRIPTION']))
		{
			$item['~USER_DESCRIPTION'] = $item['USER_DESCRIPTION'] = '';
		}
		$item['USER_DESCRIPTION'] = $item['~USER_DESCRIPTION'];
		//<-- COMMENTS

		// STATUS -->
		$statusID = $item['~STATUS_ID'];
		if ($statusID)
		{
			if (isset($enums["STATUS_LIST"]) && is_array($enums["STATUS_LIST"]))
				$statusList = $enums["STATUS_LIST"];
			else
				$statusList = CCrmViewHelper::GetInvoiceStatusInfos();

			$statusName = htmlspecialcharsbx($statusList[$statusID]["NAME"]);

			$jsStatusList = array();
			$i=0;
			foreach ($statusList as $id => $statusInfo)
			{
				$jsStatusList["s".$i] = array(
					"STATUS_ID" => $statusInfo["STATUS_ID"],
					"NAME" => htmlspecialcharsbx($statusInfo["NAME"]),
					"COLOR" => $statusInfo["COLOR"]
				);
				$i++;
			}

			$onStatusClick = "";
			if (
				isset($enums["IS_EDIT_PERMITTED"])
				&& $enums["IS_EDIT_PERMITTED"]
			)
				$onStatusClick = 'BX.Mobile.Crm.List.showStatusList('.$itemID.','.CUtil::PhpToJSObject($jsStatusList).', \'onCrmInvoiceDetailUpdate\')';

			$item['STATUS_ID'] = '
			<div class="mobile-grid-field" onclick="'.$onStatusClick.'">
				<span class="mobile-grid-field-progress" data-role="mobile-crm-status-entity-'.$itemID.'">';

			$stopColor = false;
			foreach($statusList as $statusCode => $statusInfo)
			{
				$item['STATUS_ID'].= '<span data-role="mobile-crm-status-block-'.$statusCode.'" class="mobile-grid-field-progress-step" '.($stopColor ? '' : 'style="background: '.$statusList[$statusID]["COLOR"].'"').'>&nbsp;</span>';

				if ($statusID == $statusCode)
					$stopColor = true;

				if ($statusCode == "P" || $statusCode == "D" )
					break;
			}
			$item['STATUS_ID'].= '</span>
				<span class="mobile-grid-field-textarea-title">'.GetMessage("CRM_COLUMN_LEAD_STATUS").' - <span data-role="mobile-crm-status-name-'.$itemID.'">'.$statusName.'</span></span>
			</div>';
		}

		//<-- STATUS

		//PRICE, CURRENCY -->
		$price = isset($item['~PRICE']) ? doubleval($item['~PRICE']) : 0.0;
		$currencyID = isset($item['~CURRENCY']) ? $item['~CURRENCY'] : '';
		if($currencyID === '')
		{
			$currencyID = $item['~CURRENCY'] = CCrmCurrency::GetBaseCurrencyID();
		}

		$item['FORMATTED_PRICE'] = strip_tags(CCrmCurrency::MoneyToString($price, $currencyID));

		if (isset($item["TAX_VALUE"]))
		{
			$item['TAX_VALUE'] = strip_tags(CCrmCurrency::MoneyToString($item["TAX_VALUE"], $currencyID));
		}
		//<-- PRICE, CURRENCY

		//DEAL -->
		if (isset($item['UF_DEAL_ID']) && \CCrmDeal::CheckReadPermission($item['UF_DEAL_ID']))
		{
			$dealID = intval($item['~UF_DEAL_ID']);
			if($dealID > 0)
			{
				$dealName = htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $dealID));
				$url = $dealID > 0  ?
					CComponentEngine::MakePathFromTemplate(
						$params['DEAL_URL_TEMPLATE'],
						array('deal_id' => $dealID)
					) : '';
				$item["DEAL"] = "<span class='mobile-grid-field-link' onclick=\"BX.Mobile.Crm.loadPageBlank('".$url."');\">".$dealName."</span><br/>";
			}
		}
		//<-- DEAL

		//QUOTE -->
		if (isset($item['UF_QUOTE_ID']) && \CCrmQuote::CheckReadPermission($item['UF_QUOTE_ID']))
		{
			$quoteID = intval($item['~UF_QUOTE_ID']);
			if($quoteID > 0)
			{
				$quoteName = htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Quote, $quoteID));
				$url = $dealID > 0  ?
					CComponentEngine::MakePathFromTemplate(
						$params['QUOTE_URL_TEMPLATE'],
						array('quote_id' => $quoteID)
					) : '';
				$item["QUOTE"] = "<span class='mobile-grid-field-link' onclick=\"BX.Mobile.Crm.loadPageBlank('".$url."');\">".$quoteName."</span><br/>";
			}
		}
		//<-- QUOTE

		//CONTACT -->
		if (isset($item['UF_CONTACT_ID']) && \CCrmContact::CheckReadPermission($item['UF_CONTACT_ID']))
		{
			$contactID = intval($item['UF_CONTACT_ID']);
			if($contactID > 0)
			{
				$dbContact = CCrmContact::GetListEx(array(), array('=ID' => $contactID), false, false, array('HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'POST', 'PHOTO'));
				if ($contact = $dbContact->Fetch())
				{
					$contactName = CCrmContact::PrepareFormattedName(
						array(
							'LOGIN' => '',
							'HONORIFIC' => isset($contact['HONORIFIC']) ? $contact['HONORIFIC'] : '',
							'NAME' => isset($contact['NAME']) ? $contact['NAME'] : '',
							'SECOND_NAME' => isset($contact['SECOND_NAME']) ? $contact['SECOND_NAME'] : '',
							'LAST_NAME' => isset($contact['LAST_NAME']) ? $contact['LAST_NAME'] : ''
						)
					);
					$contactName = htmlspecialcharsbx($contactName);
					$url = $contactID > 0  ?
						CComponentEngine::MakePathFromTemplate(
							$params['CONTACT_URL_TEMPLATE'],
							array('contact_id' => $contactID)
						) : '';
					$item["CONTACT"] = "<span class='mobile-grid-field-link' onclick=\"BX.Mobile.Crm.loadPageBlank('".$url."');\">".$contactName."</span><br/>";
					/*
					 * $dbMultiFields = CCrmFieldMulti::GetList(
						array('ID' => 'asc'),
						array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $contactID)
					);

					if($dbMultiFields)
					{
						while($multiFields = $dbMultiFields->Fetch())
						{
							$item['CONTACT_FM'][$multiFields['TYPE_ID']][] = array('VALUE' => $multiFields['VALUE'], 'VALUE_TYPE' => $multiFields['VALUE_TYPE']);
						}
					}*/
				}
			}
		}
		//<-- CONTACT

		//COMPANY -->
		if (isset($item['UF_COMPANY_ID']) && \CCrmCompany::CheckReadPermission($item['UF_COMPANY_ID']))
		{
			$companyID = intval($item['UF_COMPANY_ID']);
			if($companyID > 0)
			{
				$dbCompany = CCrmCompany::GetListEx(array(), array('=ID' => $companyID), false, false, array('TITLE'));
				if ($company = $dbCompany->Fetch())
				{
					$url = $companyID > 0  ?
						CComponentEngine::MakePathFromTemplate(
							$params['COMPANY_URL_TEMPLATE'],
							array('company_id' => $companyID)
						) : '';
					$item["COMPANY"] = "<span class='mobile-grid-field-link' onclick=\"BX.Mobile.Crm.loadPageBlank('".$url."');\">".htmlspecialcharsbx($company["TITLE"])."</span><br/>";
					/*
					 * if($enableMultiFields)
						{
							$item['COMPANY_FM'] = array();
							$dbMultiFields = CCrmFieldMulti::GetList(
								array('ID' => 'asc'),
								array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $companyID)
							);

							if($dbMultiFields)
							{
								while($multiFields = $dbMultiFields->Fetch())
								{
									$item['COMPANY_FM'][$multiFields['TYPE_ID']][] = array('VALUE' => $multiFields['VALUE'], 'VALUE_TYPE' => $multiFields['VALUE_TYPE']);
								}
							}
						}*/
				}
			}
		}
		//<-- COMPANY

		// PERSON TYPE INFO -->
		$personTypeID = intval($item["PERSON_TYPE_ID"]);
		if(isset($item['PERSON_TYPE_ID']))
		{
			$personTypes = is_array($enums) && isset($enums["PERSON_TYPES"]) && is_array($enums['PERSON_TYPES'])
				? $enums["PERSON_TYPES"]
				: CCrmPaySystem::getPersonTypesList();
			$item["PERSON_TYPE_ID"] = $personTypes[$item['PERSON_TYPE_ID']];
		}

		// PAY_SYSTEM -->
		if(isset($item['PAY_SYSTEM_ID']) && $personTypeID)
		{
			$paySystemID = $item['PAY_SYSTEM_ID'];
			$paySystems = is_array($enums) && isset($enums['PAY_SYSTEMS']) && is_array($enums['PAY_SYSTEMS'])
				? $enums['PAY_SYSTEMS'][$personTypeID]
				: ($personTypeID > 0 ? CCrmPaySystem::GetPaySystemsListItems($personTypeID) : array());

			$item['PAY_SYSTEM_ID'] = isset($paySystems[$paySystemID]) ? htmlspecialcharsbx($paySystems[$paySystemID]) : "";
		}
		//<-- PAY_SYSTEM

		// RESPONSIBLE -->
		if (in_array("RESPONSIBLE", $enums["FIELDS"]))
		{
			self::PrepareUserLink($item, "RESPONSIBLE", $params);
		}
		//<-- RESPONSIBLE

		if (isset($item["DATE_MARKED"]))
		{
			$item["DATE_MARKED"] = ConvertTimeStamp(MakeTimeStamp($item['DATE_MARKED']));
		}

		if (isset($item["DATE_UPDATE"]))
		{
			$item["DATE_UPDATE"] = ConvertTimeStamp(MakeTimeStamp($item['DATE_UPDATE']));
		}

		if (isset($item["DATE_BILL"]))
		{
			$item["DATE_BILL"] = ConvertTimeStamp(MakeTimeStamp($item['DATE_BILL']));
		}

		if (isset($item["DATE_PAY_BEFORE"]))
		{
			$item["DATE_PAY_BEFORE"] = ConvertTimeStamp(MakeTimeStamp($item['DATE_PAY_BEFORE']));
		}

		if (isset($item["DATE_INSERT"]))
		{
			$item["DATE_INSERT"] = ConvertTimeStamp(MakeTimeStamp($item['DATE_INSERT']));
		}

		if (in_array("AUDIO_CALL", $enums["FIELDS"]))
		{
			CCrmMobileHelper::prepareAudioField($item, CCrmOwnerType::Invoice);
		}

		if (isset($enums['CHECKBOX_USER_FIELDS']) && is_array($enums['CHECKBOX_USER_FIELDS']) && !empty($enums['CHECKBOX_USER_FIELDS']))
		{
			foreach($enums['CHECKBOX_USER_FIELDS'] as $fieldId)
			{
				$item[$fieldId] = $item[$fieldId] == 1 ? GetMessage("CRM_TEXT_YES") : GetMessage("CRM_TEXT_NO");
			}
		}
	}

	public static function PrepareInvoiceData(&$fields)
	{
		$statusID = $fields['~STATUS_ID'];
		$success = CCrmStatusInvoice::isStatusSuccess($statusID);
		$failed = CCrmStatusInvoice::isStatusFailed($statusID);

		$paymentTimeStamp = 0;
		$paymentDate = '';
		$paymentDoc = '';
		$paymentComment = '';
		$cancelTimeStamp = 0;
		$cancelDate = '';
		$cancelReason = '';
		if($success)
		{
			$paymentTimeStamp = MakeTimeStamp($fields['~PAY_VOUCHER_DATE']);
			$paymentDate = isset($fields['~PAY_VOUCHER_DATE'])
				? ConvertTimeStamp($paymentTimeStamp, 'SHORT', SITE_ID)
				: '';
			$paymentDoc =  isset($fields['~PAY_VOUCHER_NUM']) ? $fields['~PAY_VOUCHER_NUM'] : '';
			$paymentComment =  isset($fields['~REASON_MARKED']) ? $fields['~REASON_MARKED'] : '';
		}
		else
		{
			$cancelTimeStamp = MakeTimeStamp($fields['~DATE_MARKED']);
			$cancelDate = isset($fields['~DATE_MARKED'])
				? ConvertTimeStamp($cancelTimeStamp, 'SHORT', SITE_ID)
				: '';
			$cancelReason =  isset($fields['~REASON_MARKED']) ? $fields['~REASON_MARKED'] : '';
		}

		return array(
			'ID' => $fields['~ID'],
			'SHOW_URL' => $fields['SHOW_URL'],
			'EDIT_URL' => isset($fields['EDIT_URL']) ? $fields['EDIT_URL'] : '',
			'ACCOUNT_NUMBER' => $fields['~ACCOUNT_NUMBER'],
			'ORDER_TOPIC' => $fields['~ORDER_TOPIC'],
			'STATUS_ID' => $statusID,
			'STATUS_TEXT' => $fields['~STATUS_TEXT'],
			'PRICE' => $fields['~PRICE'],
			'CURRENCY' => $fields['~CURRENCY'],
			'FORMATTED_PRICE' => $fields['~FORMATTED_PRICE'],
			'DEAL_ID' => $fields['~DEAL_ID'],
			'DEAL_TITLE' => $fields['~DEAL_TITLE'],
			'CONTACT_ID' => $fields['~CONTACT_ID'],
			'CONTACT_FULL_NAME' => $fields['~CONTACT_FULL_NAME'],
			'COMPANY_ID' => $fields['~COMPANY_ID'],
			'COMPANY_TITLE' => $fields['~COMPANY_TITLE'],
			'PAYMENT_TIME_STAMP' => $paymentTimeStamp,
			'PAYMENT_DATE' => $paymentDate,
			'PAYMENT_DOC' => $paymentDoc,
			'PAYMENT_COMMENT' => $paymentComment,
			'CANCEL_TIME_STAMP' => $cancelTimeStamp,
			'CANCEL_DATE' => $cancelDate,
			'CANCEL_REASON' => $cancelReason,
			'IS_FINISHED' => ($success || $failed),
			'IS_SUCCESSED' => $success
		);
	}

	public static function PrepareInvoiceClientRequisites($personTypeID, &$properties)
	{
		if(!is_int($personTypeID))
		{
			$personTypeID = intval($personTypeID);
		}

		if($personTypeID <= 0)
		{
			return array();
		}

		if(!self::$INVOICE_PROPERTY_INFOS)
		{
			self::$INVOICE_PROPERTY_INFOS = CCrmInvoice::GetPropertiesInfo(0, true);
		}

		$propertyInfos = isset(self::$INVOICE_PROPERTY_INFOS[$personTypeID]) ? self::$INVOICE_PROPERTY_INFOS[$personTypeID] : array();
		$result = array();
		foreach($properties as $alias => &$property)
		{
			$propertyFields = isset($property['FIELDS']) ? $property['FIELDS'] : null;
			if(!is_array($propertyFields) || empty($propertyFields))
			{
				continue;
			}

			$id = isset($propertyFields['ID']) ? $propertyFields['ID'] : 0;
			$code = isset($propertyFields['CODE']) ? $propertyFields['CODE'] : '';

			if(!isset($propertyInfos[$code]))
			{
				// Property is not allowed (or required) in CRM context
				continue;
			}

			$result[] = array(
				'ID' => $id,
				'CODE' => $code,
				'ALIAS' => $alias,
				'TYPE' => isset($propertyFields['TYPE']) ? $propertyFields['TYPE'] : 'TEXT',
				'SORT' => isset($propertyFields['SORT']) ? intval($propertyFields['SORT']) : 0,
				'REQUIRED' => isset($propertyFields['REQUIRED']) && $propertyFields['REQUIRED'] === 'Y',
				'TITLE' => isset($propertyInfos[$code]) && isset($propertyInfos[$code]['NAME']) ? $propertyInfos[$code]['NAME'] : $code,
				'VALUE' => isset($property['VALUE']) ? $property['VALUE'] : ''
			);
		}
		unset($property);
		return $result;
	}

	public static function PrepareInvoiceClientInfoFormat($personTypeID)
	{
		$personTypeID = intval($personTypeID);
		if($personTypeID <= 0)
		{
			return '';
		}

		if(!self::$INVOICE_PROPERTY_INFOS)
		{
			self::$INVOICE_PROPERTY_INFOS = CCrmInvoice::GetPropertiesInfo(0, true);
		}

		$propertyInfos = isset(self::$INVOICE_PROPERTY_INFOS[$personTypeID]) ? self::$INVOICE_PROPERTY_INFOS[$personTypeID] : null;
		if(!is_array($propertyInfos))
		{
			return '';
		}

		$result = array();
		foreach ($propertyInfos as $code => &$fields)
		{
			$type = $fields['TYPE'];
			if($type !== 'TEXT' && $type !== 'TEXTAREA')
			{
				continue;
			}

			$result[] = $code;
		}
		unset($fields);

		return implode(',', $result);
	}

	public static function PrepareInvoiceTaxInfo(&$taxList, $enableTotals = false)
	{
		IncludeModuleLangFile(__FILE__);

		$result = array(
			'SUM_INCUDED_IN_PRICE' => 0.0,
			'SUM_EXCLUDED_FROM_PRICE' => 0.0,
			'SUM_TOTAL' => 0.0,
			'ITEMS' => array()
		);
		foreach($taxList as &$tax)
		{
			$name = isset($tax['TAX_NAME']) ? $tax['TAX_NAME'] : '';
			if($name === '')
			{
				$name = isset($tax['NAME'])
					? $tax['NAME']
					: (isset($tax['CODE']) ? $tax['CODE'] : '');
			}

			$taxSum = isset($tax['VALUE_MONEY']) ? doubleval($tax['VALUE_MONEY']) : 0.0;
			$isInPrice = isset($tax['IS_IN_PRICE']) && $tax['IS_IN_PRICE'] === 'Y';
			$title = $isInPrice
				? GetMessage('CRM_INVOICE_TAX_INCLUDED_TITLE', array('#TAX_NAME#' => $name))
				: $name;

			$taxInfo = array(
				'NAME' => $name,
				'TITLE' => $title,
				'SUM' => $taxSum,
				'IS_IN_PRICE' => $isInPrice
			);

			$taxInfo['FORMATTED_SUM'] = isset($tax['VALUE_MONEY_FORMATED'])
				? $tax['VALUE_MONEY_FORMATED'] : CCrmCurrency::MoneyToString($taxSum, $currencyID);

			$result['ITEMS'][] = &$taxInfo;

			if($enableTotals)
			{
				$result['SUM_TOTAL'] += $taxSum;
				if($isInPrice)
				{
					$result['SUM_INCUDED_IN_PRICE'] += $taxSum;
				}
				else
				{
					$result['SUM_EXCLUDED_FROM_PRICE'] += $taxSum;
				}
			}
			unset($taxInfo);
		}
		unset($tax);
		return $result;
	}

	public static function getQuoteSortFields()
	{
		$fields = array(
			'QUOTE_SUMMARY' => array('id' => 'QUOTE_SUMMARY', 'name' => GetMessage('CRM_COLUMN_QUOTE_QUOTE'), 'sort' => 'quote_summary'),
			'STATUS_ID' => array('id' => 'STATUS_ID', 'name' => GetMessage('CRM_COLUMN_QUOTE_STATUS_ID'), 'sort' => 'status_sort'),
			'CLOSEDATE' => array('id' => 'CLOSEDATE', 'name' => GetMessage('CRM_COLUMN_QUOTE_CLOSEDATE'), 'sort' => 'closedate'),
			'BEGINDATE' => array('id' => 'BEGINDATE', 'name' => GetMessage('CRM_COLUMN_QUOTE_BEGINDATE'), 'sort' => 'begindate'),
			'ASSIGNED_BY' => array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_QUOTE_ASSIGNED_BY'), 'sort' => 'assigned_by'),

			'ID' => array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_QUOTE_ID'), 'sort' => 'id'),
			'QUOTE_NUMBER' => array('id' => 'QUOTE_NUMBER', 'name' => GetMessage('CRM_COLUMN_QUOTE_QUOTE_NUMBER'), 'sort' => 'quote_number'),
			'TITLE' => array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_QUOTE_TITLE'), 'sort' => 'title'),
			'OPPORTUNITY' => array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_QUOTE_OPPORTUNITY'), 'sort' => 'opportunity'),
			'CURRENCY_ID' => array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_QUOTE_CURRENCY_ID'), 'sort' => 'currency_id'),
			'CLOSED' => array('id' => 'CLOSED', 'name' => GetMessage('CRM_COLUMN_QUOTE_CLOSED'), 'sort' => 'closed'),
			'DATE_CREATE' => array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_QUOTE_DATE_CREATE'), 'sort' => 'date_create'),
			'CREATED_BY' => array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_QUOTE_CREATED_BY'), 'sort' => 'created_by'),
			'DATE_MODIFY' => array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_QUOTE_DATE_MODIFY'), 'sort' => 'date_modify'),
			'MODIFY_BY' => array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_QUOTE_MODIFY_BY'), 'sort' => 'modify_by'),
		);

		return $fields;
	}

	public static function getQuoteFields($includeUserFields = true)
	{
		$fields = array(
			'ID' => array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_QUOTE_ID')),
			//'TITLE' => array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_QUOTE_TITLE')),
			//'QUOTE_SUMMARY' => array('id' => 'QUOTE_SUMMARY', 'name' => GetMessage('CRM_COLUMN_QUOTE_QUOTE')),
			'STATUS_ID' => array('id' => 'STATUS_ID', 'name' => GetMessage('CRM_COLUMN_QUOTE_STATUS_ID'), 'type' => 'HTML'),
			'FORMATTED_OPPORTUNITY' => array('id' => 'FORMATTED_OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_QUOTE_FORMATTED_OPPORTUNITY'), 'align' => 'right'),
			'ENTITIES_LINKS' => array('id' => 'ENTITIES_LINKS', 'name' => GetMessage('CRM_COLUMN_QUOTE_ENTITIES_LINKS')),
			'CLOSEDATE' => array('id' => 'CLOSEDATE', 'name' => GetMessage('CRM_COLUMN_QUOTE_CLOSEDATE')),
			'BEGINDATE' => array('id' => 'BEGINDATE', 'name' => GetMessage('CRM_COLUMN_QUOTE_BEGINDATE')),
			'ASSIGNED_BY' => array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_QUOTE_ASSIGNED_BY')),

			'QUOTE_NUMBER' => array('id' => 'QUOTE_NUMBER', 'name' => GetMessage('CRM_COLUMN_QUOTE_QUOTE_NUMBER')),
			'CLOSED' => array('id' => 'CLOSED', 'name' => GetMessage('CRM_COLUMN_QUOTE_CLOSED')),
			'DATE_CREATE' => array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_QUOTE_DATE_CREATE')),
			'CREATED_BY' => array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_QUOTE_CREATED_BY')),
			'DATE_MODIFY' => array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_QUOTE_DATE_MODIFY')),
			'MODIFY_BY' => array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_QUOTE_MODIFY_BY')),
			'PRODUCT_ID' => array('id' => 'PRODUCT_ID', 'name' => GetMessage('CRM_COLUMN_QUOTE_PRODUCT_ID')),
			'COMMENTS' => array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_QUOTE_COMMENTS')),
			'AUDIO_CALL' => array('id' => 'AUDIO_CALL', 'name' => GetMessage('CRM_COLUMN_AUDIO_CALL'), 'type' => 'HTML')
		);

		if ($includeUserFields)
		{
			self::getFieldUser($fields, CCrmQuote::$sUFEntityID);
		}

		return $fields;
	}

	public static function getQuoteFilterFields()
	{
		$arStatuses = array();
		$arStatuses[""] = GetMessage("M_CRM_NOT_SELECTED");
		$quoteStatuses = CCrmStatus::GetStatusList('QUOTE_STATUS');
		foreach($quoteStatuses as $code => $name)
		{
			$arStatuses[$code] = $name;
		}
		$filterFields = array(
			array(
				"type" => "text",
				"id" => "TITLE",
				"name" => GetMessage('CRM_COLUMN_QUOTE_TITLE'),
				"value" => ""
			),
			array(
				"type" => "select",
				"id" => "STATUS_ID",
				"name" => GetMessage('CRM_COLUMN_QUOTE_STATUS_ID'),
				"items" => $arStatuses,
				"value" => ""
			),
			array(
				"type" => "select-user",
				"id" => "ASSIGNED_BY_ID",
				"name" => GetMessage('CRM_COLUMN_QUOTE_ASSIGNED_BY'),
				"value" => ""
			),
			array(
				"type" => "number",
				"id" => "OPPORTUNITY",
				"name" => GetMessage('CRM_COLUMN_QUOTE_OPPORTUNITY'),
				"value" => "",
				"item" => array(
					"from" => "",
					"to" => ""
				)
			),
			array(
				"type" => "date",
				"id" => "BEGINDATE",
				"name" => GetMessage('CRM_COLUMN_QUOTE_BEGINDATE'),
				"value" => ""
			),
			array(
				"type" => "date",
				"id" => "CLOSEDATE",
				"name" => GetMessage('CRM_COLUMN_QUOTE_CLOSEDATE'),
				"value" => ""
			),
		);

		return $filterFields;
	}

	public static function PrepareQuoteItem(&$item, &$params, $enums = array())
	{
		$itemID = intval($item['~ID']);

		$item['SHOW_URL'] = CComponentEngine::MakePathFromTemplate(
			$params['DEAL_SHOW_URL_TEMPLATE'],
			array('deal_id' => $itemID)
		);

		if(!isset($item['~TITLE']))
		{
			$item['~TITLE'] = $item['TITLE'] =  '';
		}

		if(!isset($item['~OPPORTUNITY']))
		{
			$item['~OPPORTUNITY'] = $item['OPPORTUNITY'] = 0.0;
		}

		if(!isset($item['~PROBABILITY']))
		{
			$item['~PROBABILITY'] = $item['PROBABILITY'] = 0;
		}

		$currencyID = isset($item['~CURRENCY_ID']) ? $item['~CURRENCY_ID'] : '';
		if($currencyID === '')
		{
			$currencyID = $item['~CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
			$item['CURRENCY_ID'] = htmlspecialcharsbx($currencyID);
		}

		$item['~CURRENCY_NAME'] = CCrmCurrency::GetCurrencyName($currencyID);
		$item['CURRENCY_NAME'] = htmlspecialcharsbx($item['~CURRENCY_NAME']);

		$item['~FORMATTED_OPPORTUNITY'] = CCrmCurrency::MoneyToString($item['~OPPORTUNITY'], $currencyID);
		$item['FORMATTED_OPPORTUNITY'] = strip_tags($item['~FORMATTED_OPPORTUNITY']);

		//CONTACT -->
		if (isset($item['CONTACT_ID']) && \CCrmContact::CheckReadPermission($item['CONTACT_ID']))
		{
			$contactID = intval($item['CONTACT_ID']);
			if($contactID > 0)
			{
				$contactName = CCrmContact::PrepareFormattedName(
					array(
						'HONORIFIC' => isset($item['~CONTACT_HONORIFIC']) ? $item['~CONTACT_HONORIFIC'] : '',
						'NAME' => isset($item['~CONTACT_NAME']) ? $item['~CONTACT_NAME'] : '',
						'LAST_NAME' => isset($item['~CONTACT_LAST_NAME']) ? $item['~CONTACT_LAST_NAME'] : '',
						'SECOND_NAME' => isset($item['~CONTACT_SECOND_NAME']) ? $item['~CONTACT_SECOND_NAME'] : ''
					),
					$params['NAME_TEMPLATE']
				);
				$contactName = htmlspecialcharsbx($contactName);

				$url = CComponentEngine::MakePathFromTemplate(
					$params['CONTACT_URL_TEMPLATE'],
					array('contact_id' => $contactID)
				);
				$item["CONTACT"] = "<span class='mobile-grid-field-link' onclick=\"BX.Mobile.Crm.loadPageBlank('".$url."');\">".$contactName."</span><br/>";
			}
		}
		//<-- CONTACT

		//COMPANY -->
		if (isset($item['COMPANY_ID']) && \CCrmCompany::CheckReadPermission($item['COMPANY_ID']))
		{
			$companyID = intval($item['COMPANY_ID']);
			if($companyID > 0)
			{
				$url = $companyID > 0  ?
					CComponentEngine::MakePathFromTemplate(
						$params['COMPANY_URL_TEMPLATE'],
						array('company_id' => $companyID)
					) : '';
				$item["COMPANY"] = "<span class='mobile-grid-field-link' onclick=\"BX.Mobile.Crm.loadPageBlank('".$url."');\">".HtmlFilter::encode($item['~COMPANY_TITLE'])."</span><br/>";
			}
		}
		//<-- COMPANY

		//DEAL -->
		if (isset($item['DEAL_ID']) && \CCrmDeal::CheckReadPermission($item['DEAL_ID']))
		{
			$dealID = intval($item['DEAL_ID']);
			if($dealID > 0)
			{
				$url = $dealID > 0  ?
					CComponentEngine::MakePathFromTemplate(
						$params['DEAL_URL_TEMPLATE'],
						array('deal_id' => $dealID)
					) : '';
				$item["DEAL"] = "<span class='mobile-grid-field-link' onclick=\"BX.Mobile.Crm.loadPageBlank('".$url."');\">".HtmlFilter::encode($item['~DEAL_TITLE'])."</span><br/>";
			}
		}
		//<-- DEAL

		//LEAD -->
		if (isset($item['LEAD_ID']) && \CCrmLead::CheckReadPermission($item['LEAD_ID']))
		{
			$leadID = intval($item['LEAD_ID']);
			if($leadID > 0)
			{
				$url = $leadID > 0  ?
					CComponentEngine::MakePathFromTemplate(
						$params['LEAD_URL_TEMPLATE'],
						array('lead_id' => $leadID)
					) : '';
				$item["LEAD"] = "<span class='mobile-grid-field-link' onclick=\"BX.Mobile.Crm.loadPageBlank('".$url."');\">".HtmlFilter::encode($item['~LEAD_TITLE'])."</span><br/>";
			}
		}
		//<-- LEAD

		if (is_array($enums["FIELDS"]) && in_array("ASSIGNED_BY", $enums["FIELDS"]))
		{
			self::PrepareUserLink($item, "ASSIGNED_BY", $params);
		}

		if (is_array($enums["FIELDS"]) && in_array("CREATED_BY", $enums["FIELDS"]))
		{
			self::PrepareUserLink($item, "CREATED_BY", $params);
		}

		if (is_array($enums["FIELDS"]) && in_array("MODIFY_BY", $enums["FIELDS"]))
		{
			self::PrepareUserLink($item, "MODIFY_BY", $params);
		}

		$statusID = $item['~STATUS_ID'];
		if ($statusID)
		{
			if (isset($enums["STATUS_LIST"]) && is_array($enums["STATUS_LIST"]))
				$statusList = $enums["STATUS_LIST"];
			else
				$statusList = CCrmViewHelper::GetQuoteStatusInfos();

			$statusName = htmlspecialcharsbx($statusList[$statusID]["NAME"]);

			$jsStatusList = array();
			if (isset($enums["JS_STATUS_LIST"]) && is_array($enums["JS_STATUS_LIST"]))
			{
				$jsStatusList = $enums["JS_STATUS_LIST"];
			}
			else
			{
				$i=0;
				foreach ($statusList as $id => $statusInfo)
				{
					$jsStatusList["s".$i] = array(
						"STATUS_ID" => $statusInfo["STATUS_ID"],
						"NAME" => htmlspecialcharsbx($statusInfo["NAME"]),
						"COLOR" => $statusInfo["COLOR"]
					);
					$i++;
				}
			}

			$onStatusClick = "";
			if (
				isset($enums["IS_EDIT_PERMITTED"])
				&& $enums["IS_EDIT_PERMITTED"]
			)
				$onStatusClick = 'BX.Mobile.Crm.List.showStatusList('.$itemID.','.CUtil::PhpToJSObject($jsStatusList).', \'onCrmQuoteDetailUpdate\')';

			$item['STATUS_ID'] = '
			<div class="mobile-grid-field" onclick="'.$onStatusClick.'">
				<span class="mobile-grid-field-progress" data-role="mobile-crm-status-entity-'.$itemID.'">';

			$stopColor = false;
			foreach($statusList as $statusCode => $statusInfo)
			{
				$item['STATUS_ID'].= '<span data-role="mobile-crm-status-block-'.$statusCode.'" class="mobile-grid-field-progress-step" '.($stopColor ? '' : 'style="background: '.$statusList[$statusID]["COLOR"].'"').'>&nbsp;</span>';

				if ($statusID == $statusCode)
					$stopColor = true;

				if ($statusCode == "APPROVED" || $statusCode == "DECLAINED" )
					break;
			}
			$item['STATUS_ID'].= '</span>
				<span class="mobile-grid-field-textarea-title">'.GetMessage("CRM_COLUMN_LEAD_STATUS").' - <span data-role="mobile-crm-status-name-'.$itemID.'">'.$statusName.'</span></span>
			</div>';
		}

		if(!isset($item['~COMMENTS']))
		{
			$item['~COMMENTS'] = $item['COMMENTS'] = '';
		}
		$item['COMMENTS'] = $item['~COMMENTS'];

		if (isset($item["DATE_CREATE"]))
		{
			$item["DATE_CREATE"] = ConvertTimeStamp(MakeTimeStamp($item['DATE_CREATE']));
		}

		if (isset($item["DATE_MODIFY"]))
		{
			$item["DATE_MODIFY"] = ConvertTimeStamp(MakeTimeStamp($item['DATE_MODIFY']));
		}

		if (isset($item["BEGINDATE"]))
		{
			$item["BEGINDATE"] = ConvertTimeStamp(MakeTimeStamp($item['BEGINDATE']));
		}

		if (isset($item["CLOSEDATE"]))
		{
			$item["CLOSEDATE"] = ConvertTimeStamp(MakeTimeStamp($item['CLOSEDATE']));
		}

		if (isset($item["CLOSED"]))
		{
			$item["CLOSED"] = $item["CLOSED"] == "Y" ? GetMessage("CRM_TEXT_YES") : GetMessage("CRM_TEXT_NO");
		}

		if (is_array($enums["FIELDS"]) && in_array("AUDIO_CALL", $enums["FIELDS"]))
		{
			CCrmMobileHelper::prepareAudioField($item, CCrmOwnerType::Quote);
		}

		if (isset($enums['CHECKBOX_USER_FIELDS']) && is_array($enums['CHECKBOX_USER_FIELDS']) && !empty($enums['CHECKBOX_USER_FIELDS']))
		{
			foreach($enums['CHECKBOX_USER_FIELDS'] as $fieldId)
			{
				$item[$fieldId] = $item[$fieldId] == 1 ? GetMessage("CRM_TEXT_YES") : GetMessage("CRM_TEXT_NO");
			}
		}

		if (isset($item["PRODUCT_ID"]))
		{
			$item["PRODUCT_ID"] = htmlspecialcharsbx($item["PRODUCT_ID"]);
		}
	}

	public static function PrepareDealData(&$fields)
	{
		$clientImageID = 0;
		$clientTitle = '';
		//$clientLegend = '';
		if($fields['~CONTACT_ID'] > 0)
		{
			$clientImageID = $fields['~CONTACT_PHOTO'];
			$clientTitle = $fields['~CONTACT_FORMATTED_NAME'];
			//$clientLegend = $fields['~CONTACT_POST'];
		}
		if($fields['~COMPANY_ID'] > 0)
		{
			if($clientImageID === 0)
			{
				$clientImageID = $fields['~COMPANY_LOGO'];
			}
			if($clientTitle !== '')
			{
				$clientTitle .= ', ';
			}
			$clientTitle .= $fields['~COMPANY_TITLE'];
		}

		$stageID = $fields['~STAGE_ID'];
		$stageSort = CCrmDeal::GetStageSort($stageID);
		$finalStageSort = CCrmDeal::GetFinalStageSort();

		return array(
			'ID' => $fields['~ID'],
			'TITLE' => $fields['~TITLE'],
			'STAGE_ID' => $fields['~STAGE_ID'],
			'STAGE_NAME' => $fields['~STAGE_NAME'],
			'TYPE_ID' => $fields['~TYPE_ID'],
			'TYPE_NAME' => $fields['~TYPE_NAME'],
			'PROBABILITY' => $fields['~PROBABILITY'],
			'OPPORTUNITY' => $fields['~OPPORTUNITY'],
			'FORMATTED_OPPORTUNITY' => $fields['FORMATTED_OPPORTUNITY'],
			'CURRENCY_ID' => $fields['~CURRENCY_ID'],
			'ASSIGNED_BY_ID' => $fields['~ASSIGNED_BY_ID'],
			'ASSIGNED_BY_FORMATTED_NAME' => $fields['~ASSIGNED_BY_FORMATTED_NAME'],
			'CONTACT_ID' => $fields['~CONTACT_ID'],
			'CONTACT_FORMATTED_NAME' => $fields['~CONTACT_FORMATTED_NAME'],
			'COMPANY_ID' => $fields['~COMPANY_ID'],
			'COMPANY_TITLE' => $fields['~COMPANY_TITLE'],
			'COMMENTS' => $fields['~COMMENTS'],
			'DATE_CREATE' => $fields['~DATE_CREATE'],
			'DATE_MODIFY' => $fields['~DATE_MODIFY'],
			'SHOW_URL' => $fields['SHOW_URL'],
			'CONTACT_SHOW_URL' => $fields['CONTACT_SHOW_URL'],
			'COMPANY_SHOW_URL' => $fields['COMPANY_SHOW_URL'],
			'ASSIGNED_BY_SHOW_URL' => $fields['ASSIGNED_BY_SHOW_URL'],
			'CLIENT_TITLE' => $clientTitle,
			'CLIENT_IMAGE_ID' => $clientImageID,
			'IS_FINISHED' => $stageSort >= $finalStageSort,
			'IS_SUCCESSED' => $stageSort === $finalStageSort
		);
	}

	public static function getDealSortFields()
	{
		$fields = array(
			array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_DEAL_ID'), 'sort' => 'id'),
			array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_DEAL_TITLE'), 'sort' => 'title'),
			array('id' => 'STAGE_ID', 'name' => GetMessage('CRM_COLUMN_DEAL_STAGE_ID'), 'sort' => 'stage_sort'),
			array('id' => 'PROBABILITY', 'name' => GetMessage('CRM_COLUMN_DEAL_PROBABILITY'), 'sort' => 'probability'),
			array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_DEAL_ASSIGNED_BY'), 'sort' => 'assigned_by'),

			array('id' => 'TYPE_ID', 'name' => GetMessage('CRM_COLUMN_DEAL_TYPE_ID'), 'sort' => 'type_id'),
			array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_OPPORTUNITY'), 'sort' => 'opportunity'),
			array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_DEAL_CURRENCY_ID'), 'sort' => 'currency_id'),
			array('id' => 'COMPANY_ID', 'name' => GetMessage('CRM_COLUMN_DEAL_COMPANY_ID'), 'sort' => 'company_id'),
			array('id' => 'CONTACT_ID', 'name' => GetMessage('CRM_COLUMN_DEAL_CONTACT_ID'), 'sort' => 'contact_full_name'),

			array('id' => 'CLOSED', 'name' => GetMessage('CRM_COLUMN_DEAL_CLOSED'), 'sort' => 'closed'),
			array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DEAL_DATE_CREATE'), 'sort' => 'date_create'),
			array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_DEAL_CREATED_BY'), 'sort' => 'created_by'),
			array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DEAL_DATE_MODIFY'), 'sort' => 'date_modify'),
			array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_DEAL_MODIFY_BY'), 'sort' => 'modify_by'),
			array('id' => 'BEGINDATE', 'name' => GetMessage('CRM_COLUMN_DEAL_BEGINDATE'), 'sort' => 'begindate'),
			array('id' => 'CLOSEDATE', 'name' => GetMessage('CRM_COLUMN_DEAL_CLOSEDATE'), 'sort' => 'closedate'),
			array('id' => 'EVENT_DATE', 'name' => GetMessage('CRM_COLUMN_DEAL_EVENT_DATE'), 'sort' => 'event_date'),
			array('id' => 'EVENT_ID', 'name' => GetMessage('CRM_COLUMN_DEAL_EVENT_ID'), 'sort' => 'event_id')
		);

		return $fields;
	}

	public static function getDealFields($includeUserFields = true)
	{
		$fields = array(
			'ID' => array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_DEAL_ID')),
		//	'TITLE' => array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_DEAL_TITLE')),
			'TYPE_ID' => array('id' => 'TYPE_ID', 'name' => GetMessage('CRM_COLUMN_DEAL_TYPE_ID'), 'type' => 'list'),
			//'DEAL_SUMMARY' => array('id' => 'DEAL_SUMMARY', 'name' => GetMessage('CRM_COLUMN_DEAL_DEAL')),
			'STAGE_ID' => array('id' => 'STAGE_ID', 'name' => GetMessage('CRM_COLUMN_DEAL_STAGE_ID'), 'type' => 'HTML'),
			'PROBABILITY' => array('id' => 'PROBABILITY', 'name' => GetMessage('CRM_COLUMN_DEAL_PROBABILITY'), 'align' => 'right'),
			'FORMATTED_OPPORTUNITY' => array('id' => 'FORMATTED_OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_DEAL_FORMATTED_OPPORTUNITY'), 'align' => 'right'),
			'ASSIGNED_BY' => array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_DEAL_ASSIGNED_BY'), 'class' => 'username'),
			'ORIGINATOR_ID' => array('id' => 'ORIGINATOR_ID', 'name' => GetMessage('CRM_COLUMN_DEAL_BINDING'), 'type' => 'list'),

			//'CURRENCY_ID' => array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_DEAL_CURRENCY_ID'), 'sort' => 'currency_id', 'editable' => array('items' => CCrmCurrencyHelper::PrepareListItems()), 'type' => 'list'),
			'COMPANY' => array('id' => 'COMPANY', 'name' => GetMessage('CRM_COLUMN_DEAL_COMPANY_ID')),
			'CONTACT' => array('id' => 'CONTACT', 'name' => GetMessage('CRM_COLUMN_DEAL_CONTACT_ID')),

			'CLOSED' => array('id' => 'CLOSED', 'name' => GetMessage('CRM_COLUMN_DEAL_CLOSED'), 'align' => 'center', 'editable' => array('items' => array('' => '', 'Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'))), 'type' => 'list'),
			'DATE_CREATE' => array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DEAL_DATE_CREATE'), 'class' => 'date'),
			'CREATED_BY' => array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_DEAL_CREATED_BY'), 'editable' => false, 'class' => 'username'),
			'DATE_MODIFY' => array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DEAL_DATE_MODIFY'), 'class' => 'date'),
			'MODIFY_BY' => array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_DEAL_MODIFY_BY'), 'class' => 'username'),
			'BEGINDATE' => array('id' => 'BEGINDATE', 'name' => GetMessage('CRM_COLUMN_DEAL_BEGINDATE'), 'type' => 'date', 'class' => 'date'),
			'CLOSEDATE' => array('id' => 'CLOSEDATE', 'name' => GetMessage('CRM_COLUMN_DEAL_CLOSEDATE'), 'type' => 'date'),
			'PRODUCT_ID' => array('id' => 'PRODUCT_ID', 'name' => GetMessage('CRM_COLUMN_DEAL_PRODUCT_ID'), 'type' => 'list'),
			'COMMENTS' => array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_DEAL_COMMENTS')),
			'EVENT_DATE' => array('id' => 'EVENT_DATE', 'name' => GetMessage('CRM_COLUMN_DEAL_EVENT_DATE')),
			'EVENT_ID' => array('id' => 'EVENT_ID', 'name' => GetMessage('CRM_COLUMN_DEAL_EVENT_ID'), 'editable' => array('items' => CCrmStatus::GetStatusList('EVENT_TYPE')), 'type' => 'list'),
			'EVENT_DESCRIPTION' => array('id' => 'EVENT_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_DEAL_EVENT_DESCRIPTION')),
			'AUDIO_CALL' => array('id' => 'AUDIO_CALL', 'name' => GetMessage('CRM_COLUMN_AUDIO_CALL'), 'type' => 'HTML')
		);

		if ($includeUserFields)
		{
			self::getFieldUser($fields, CCrmDeal::$sUFEntityID);
		}
		return $fields;
	}

	public static function getDealFilterFields()
	{
		$arStatuses = array();
		$arStatuses[""] = GetMessage("M_CRM_NOT_SELECTED");
		$dealStatuses = CCrmStatus::GetStatusList('DEAL_STAGE');
		foreach($dealStatuses as $code => $name)
		{
			$arStatuses[$code] = $name;
		}
		$filterFields = array(
			array(
				"type" => "text",
				"id" => "TITLE",
				"name" => GetMessage('CRM_COLUMN_DEAL_TITLE'),
				"value" => ""
			),
			array(
				"type" => "select",
				"id" => "STAGE_ID",
				"name" => GetMessage('CRM_COLUMN_DEAL_STAGE_ID'),
				"items" => $arStatuses,
				"value" => ""
			),
			array(
				"type" => "select-user",
				"id" => "ASSIGNED_BY_ID",
				"name" => GetMessage('CRM_COLUMN_DEAL_ASSIGNED_BY'),
				"value" => ""
			),
			array(
				"type" => "number",
				"id" => "OPPORTUNITY",
				"name" => GetMessage('CRM_COLUMN_DEAL_OPPORTUNITY'),
				"value" => "",
				"item" => array(
					"from" => "",
					"to" => ""
				)
			),
			array(
				"type" => "date",
				"id" => "DATE_CREATE",
				"name" => GetMessage('CRM_COLUMN_DEAL_DATE_CREATE'),
				"value" => ""
			),
			array(
				"type" => "date",
				"id" => "CLOSEDATE",
				"name" => GetMessage('CRM_COLUMN_DEAL_CLOSEDATE'),
				"value" => ""
			),
		);

		return $filterFields;
	}

	public static function PrepareDealItem(&$item, &$params, $enums = array())
	{
		$itemID = intval($item['~ID']);

		if(!isset($item['~PROBABILITY']))
		{
			$item['~PROBABILITY'] = $item['PROBABILITY'] = 0;
		}

		$currencyID = isset($item['~CURRENCY_ID']) ? $item['~CURRENCY_ID'] : '';
		if($currencyID === '')
		{
			$currencyID = $item['~CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
		}

		$item['~FORMATTED_OPPORTUNITY'] = CCrmCurrency::MoneyToString(
			isset($item['~OPPORTUNITY']) ? $item['~OPPORTUNITY'] : '',
			$currencyID
		);
		$item['FORMATTED_OPPORTUNITY'] = strip_tags($item['~FORMATTED_OPPORTUNITY']);


		$contactID = isset($item['~CONTACT_ID']) ? intval($item['~CONTACT_ID']) : 0;
		$item['~CONTACT_ID'] = $item['CONTACT_ID'] = 0;
		$item['CONTACT'] = $item['~CONTACT_FORMATTED_NAME'] = $item['CONTACT_FORMATTED_NAME'] = '';
		if (\CCrmContact::CheckReadPermission($contactID))
		{
			$item['~CONTACT_ID'] = $item['CONTACT_ID'] = $contactID;
			$item['CONTACT_SHOW_URL'] = $contactID > 0 && $params['CONTACT_SHOW_URL_TEMPLATE'] !== ''
				? CComponentEngine::MakePathFromTemplate(
					$params['CONTACT_SHOW_URL_TEMPLATE'], array('contact_id' => $contactID)
				) : '';

			$item['~CONTACT_FORMATTED_NAME'] = $contactID > 0
				? CCrmContact::PrepareFormattedName(
					array(
						'HONORIFIC' => isset($item['~CONTACT_HONORIFIC']) ? $item['~CONTACT_HONORIFIC'] : '',
						'NAME' => isset($item['~CONTACT_NAME']) ? $item['~CONTACT_NAME'] : '',
						'LAST_NAME' => isset($item['~CONTACT_LAST_NAME']) ? $item['~CONTACT_LAST_NAME'] : '',
						'SECOND_NAME' => isset($item['~CONTACT_SECOND_NAME']) ? $item['~CONTACT_SECOND_NAME'] : ''
					),
					$params['NAME_TEMPLATE']
				) : '';
			$item['CONTACT_FORMATTED_NAME'] = htmlspecialcharsbx($item['~CONTACT_FORMATTED_NAME']);

			if ($item['CONTACT_ID'] && $item['CONTACT_FORMATTED_NAME'])
			{
				$url = CComponentEngine::MakePathFromTemplate(
					$params['CONTACT_SHOW_URL_TEMPLATE'],
					array('contact_id' => $item['CONTACT_ID'])
				);

				$item['CONTACT'] = "<span class='mobile-grid-field-link' onclick=\"BX.Mobile.Crm.loadPageBlank('".$url."');\">".$item['CONTACT_FORMATTED_NAME']."</span>";
			}
		}

		$companyID = isset($item['~COMPANY_ID']) ? intval($item['~COMPANY_ID']) : 0;
		$item['~COMPANY_ID'] = $item['COMPANY_ID'] = 0;
		if ($companyID > 0 && $item['COMPANY_TITLE'] && \CCrmCompany::CheckReadPermission($companyID))
		{
			$url = CComponentEngine::MakePathFromTemplate(
				$params['COMPANY_SHOW_URL_TEMPLATE'],
				array('company_id' => $companyID)
			);

			$item['COMPANY'] = "<span class='mobile-grid-field-link' onclick=\"BX.Mobile.Crm.loadPageBlank('".$url."');\">".HtmlFilter::encode($item['~COMPANY_TITLE'])."</span>";
		}

		if(!isset($item['~COMPANY_TITLE']))
		{
			$item['~COMPANY_TITLE'] = $item['COMPANY'] = $item['COMPANY_TITLE'] = '';
		}

		if (is_array($enums["FIELDS"]) && in_array("ASSIGNED_BY", $enums["FIELDS"]))
		{
			self::PrepareUserLink($item, "ASSIGNED_BY", $params);
		}

		if (is_array($enums["FIELDS"]) && in_array("CREATED_BY", $enums["FIELDS"]))
		{
			self::PrepareUserLink($item, "CREATED_BY", $params);
		}

		if (is_array($enums["FIELDS"]) && in_array("MODIFY_BY", $enums["FIELDS"]))
		{
			self::PrepareUserLink($item, "MODIFY_BY", $params);
		}

		$stageList = is_array($enums) && isset($enums['STAGE_LIST'])
			? $enums['STAGE_LIST'] : CCrmViewHelper::GetDealStageInfos($item['~CATEGORY_ID']);

		$stageID = isset($item['~STAGE_ID']) ? $item['~STAGE_ID'] : '';
		$stageName = htmlspecialcharsbx($stageList[$stageID]["NAME"]);

		$jsStageList = is_array($enums) && isset($enums['JS_STAGE_LIST'])
			? $enums['JS_STAGE_LIST'] : array();

		if (empty($jsStageList))
		{
			$i=0;
			foreach ($stageList as $id => $info)
			{
				$jsStageList["s".$i] = array(
					"STATUS_ID" => $info["STATUS_ID"],
					"NAME" => $info["NAME"],
					"COLOR" => $info["COLOR"]
				);
				$i++;
			}
		}

		$onStatusClick = "";
		if (
			isset($enums["IS_EDIT_PERMITTED"])
			&& $enums["IS_EDIT_PERMITTED"]
		)
			$onStatusClick = 'BX.Mobile.Crm.List.showStatusList('.$itemID.','.CUtil::PhpToJSObject($jsStageList).', \'onCrmDealDetailUpdate\')';

		$item['STAGE_ID'] = '
			<div class="mobile-grid-field" onclick="'.$onStatusClick.'">
				<span class="mobile-grid-field-progress" data-role="mobile-crm-status-entity-'.$itemID.'">';

		$stopColor = false;
		foreach($stageList as $code => $info)
		{
			$item['STAGE_ID'].= '<span data-role="mobile-crm-status-block-'.$code.'" class="mobile-grid-field-progress-step" '.($stopColor ? '' : 'style="background: '.$stageList[$stageID]["COLOR"].'"').'>&nbsp;</span>';

			if ($stageID == $code)
				$stopColor = true;

			if ($code == "WON" || $code == "LOSE" )
				break;
		}
		$item['STAGE_ID'].= '</span>
				<span class="mobile-grid-field-textarea-title">'.GetMessage("CRM_COLUMN_DEAL_STAGE").' - <span data-role="mobile-crm-status-name-'.$itemID.'">'.$stageName.'</span></span>
			</div>';

		$typeList = $enums && isset($enums['TYPE_LIST'])
			? $enums['TYPE_LIST'] : self::GetStatusList('DEAL_TYPE');

		if(!isset($item['~TYPE_ID']))
		{
			$item['~TYPE_ID'] = $item['TYPE_ID'] = '';
		}

		$typeID = $item['~TYPE_ID'];
		if($typeID === '' || !isset($typeList[$typeID]))
		{
			$item['~TYPE_NAME'] = $item['TYPE_NAME'] = '';
		}
		else
		{
			$item['~TYPE_NAME'] = $typeList[$typeID];
			$item['TYPE_NAME'] = htmlspecialcharsbx($item['~TYPE_NAME']);
		}
		$item['TYPE_ID'] = $item['TYPE_NAME'];

		if (isset($item["DATE_CREATE"]))
		{
			$item["DATE_CREATE"] = ConvertTimeStamp(MakeTimeStamp($item['DATE_CREATE']));
		}

		if (isset($item["DATE_MODIFY"]))
		{
			$item["DATE_MODIFY"] = ConvertTimeStamp(MakeTimeStamp($item['DATE_MODIFY']));
		}

		if (isset($item["CLOSED"]))
		{
			$item["CLOSED"] = $item["CLOSED"] == "Y" ? GetMessage("CRM_TEXT_YES") : GetMessage("CRM_TEXT_NO");
		}

		if (is_array($enums["FIELDS"]) && in_array("AUDIO_CALL", $enums["FIELDS"]))
		{
			CCrmMobileHelper::prepareAudioField($item, CCrmOwnerType::Deal);
		}

		if (isset($enums['CHECKBOX_USER_FIELDS']) && is_array($enums['CHECKBOX_USER_FIELDS']) && !empty($enums['CHECKBOX_USER_FIELDS']))
		{
			foreach($enums['CHECKBOX_USER_FIELDS'] as $fieldId)
			{
				$item[$fieldId] = $item[$fieldId] == 1 ? GetMessage("CRM_TEXT_YES") : GetMessage("CRM_TEXT_NO");
			}
		}

		if (isset($item["PRODUCT_ID"]))
		{
			$item["PRODUCT_ID"] = htmlspecialcharsbx($item["PRODUCT_ID"]);
		}

		$item['COMMENTS'] = $item['~COMMENTS'];
	}

	public static function getContactSortFields()
	{
		$addressLabels = Bitrix\Crm\EntityAddress::getShortLabels();

		$fields = array(
			'ID' => array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_CONTACT_ID'), 'sort' => 'id'),
			'CONTACT_COMPANY' => array('id' => 'CONTACT_COMPANY', 'name' => GetMessage('CRM_COLUMN_CONTACT_CONTACT_COMPANY_INFO'), 'sort' => 'company_title'),
			'NAME' => array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_CONTACT_NAME'), 'sort' => 'name'),
			'LAST_NAME' => array('id' => 'LAST_NAME', 'name' => GetMessage('CRM_COLUMN_CONTACT_LAST_NAME'), 'sort' => 'last_name'),
			'SECOND_NAME' => array('id' => 'SECOND_NAME', 'name' => GetMessage('CRM_COLUMN_CONTACT_SECOND_NAME'), 'sort' => 'second_name'),
			'BIRTHDATE' => array('id' => 'BIRTHDATE', 'name' => GetMessage('CRM_COLUMN_CONTACT_BIRTHDATE'), 'sort' => 'BIRTHDATE'),
			'POST' => array('id' => 'POST', 'name' => GetMessage('CRM_COLUMN_CONTACT_POST'), 'sort' => 'post'),
			'COMPANY_ID' => array('id' => 'COMPANY_ID', 'name' => GetMessage('CRM_COLUMN_CONTACT_COMPANY_ID'), 'sort' => 'company_title'),
			'TYPE_ID' => array('id' => 'TYPE_ID', 'name' => GetMessage('CRM_COLUMN_CONTACT_TYPE'), 'sort' => 'type_id'),
			'ASSIGNED_BY' => array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_CONTACT_ASSIGNED_BY'), 'sort' => 'assigned_by'),

			'ADDRESS' => array('id' => 'ADDRESS', 'name' => $addressLabels['ADDRESS'], 'sort' => 'address'),
			'ADDRESS_2' => array('id' => 'ADDRESS_2', 'name' => $addressLabels['ADDRESS_2'], 'sort' => 'address_2'),
			'ADDRESS_CITY' => array('id' => 'ADDRESS_CITY', 'name' => $addressLabels['CITY'], 'sort' => 'address_city'),
			'ADDRESS_REGION' => array('id' => 'ADDRESS_REGION', 'name' => $addressLabels['REGION'], 'sort' => 'address_region'),
			'ADDRESS_PROVINCE' => array('id' => 'ADDRESS_PROVINCE', 'name' => $addressLabels['PROVINCE'], 'sort' => 'address_province'),
			'ADDRESS_POSTAL_CODE' => array('id' => 'ADDRESS_POSTAL_CODE', 'name' => $addressLabels['POSTAL_CODE'], 'sort' => 'address_postal_code'),
			'ADDRESS_COUNTRY' => array('id' => 'ADDRESS_COUNTRY', 'name' => $addressLabels['COUNTRY'], 'sort' => 'address_country'),

			'SOURCE_ID' => array('id' => 'SOURCE_ID', 'name' => GetMessage('CRM_COLUMN_CONTACT_SOURCE'), 'sort' => 'source_id'),
			'CREATED_BY' => array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_CONTACT_CREATED_BY'), 'sort' => 'created_by'),
			'DATE_CREATE' => array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_CONTACT_DATE_CREATE'), 'sort' => 'date_create'),
			'MODIFY_BY' => array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_CONTACT_MODIFY_BY'), 'sort' => 'modify_by'),
			'DATE_MODIFY' => array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_CONTACT_DATE_MODIFY'), 'sort' => 'date_modify')
		);

		return $fields;
	}

	public static function getContactFields($includeUserFields = true)
	{
		$addressLabels = Bitrix\Crm\EntityAddress::getShortLabels();

		$fields = array(
			'ID' => array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_CONTACT_ID')),
			'CONTACT_SUMMARY' => array('id' => 'CONTACT_SUMMARY', 'name' => GetMessage('CRM_COLUMN_CONTACT_CONTACT')),
			'CONTACT_COMPANY' => array('id' => 'CONTACT_COMPANY', 'name' => GetMessage('CRM_COLUMN_CONTACT_CONTACT_COMPANY_INFO')),
			'PHOTO' => array('id' => 'PHOTO', 'name' => GetMessage('CRM_COLUMN_CONTACT_PHOTO')),
			//'NAME_LAST_NAME' => array('id' => 'NAME_LAST_NAME', 'name' => GetMessage('CRM_COLUMN_CONTACT_TITLE_NAME_LAST_NAME')),
			//'NAME' => array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_CONTACT_NAME')),
			//'LAST_NAME' => array('id' => 'LAST_NAME', 'name' => GetMessage('CRM_COLUMN_CONTACT_LAST_NAME')),
			'SECOND_NAME' => array('id' => 'SECOND_NAME', 'name' => GetMessage('CRM_COLUMN_CONTACT_SECOND_NAME')),
			'BIRTHDATE' => array('id' => 'BIRTHDATE', 'name' => GetMessage('CRM_COLUMN_CONTACT_BIRTHDATE')),
			'POST' => array('id' => 'POST', 'name' => GetMessage('CRM_COLUMN_CONTACT_POST')),
			'COMPANY_ID' => array('id' => 'COMPANY_ID', 'name' => GetMessage('CRM_COLUMN_CONTACT_COMPANY_ID')),
			'TYPE_ID' => array('id' => 'TYPE_ID', 'name' => GetMessage('CRM_COLUMN_CONTACT_TYPE')),
			'ASSIGNED_BY' => array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_CONTACT_ASSIGNED_BY')),

			'FULL_ADDRESS' => array('id' => 'FULL_ADDRESS', 'name' => Bitrix\Crm\EntityAddress::getFullAddressLabel(), 'sort' => false, 'default' => false, 'editable' => false),

			'COMMENTS' => array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_CONTACT_COMMENTS'), 'sort' => false /**because of MSSQL**/, 'default' => false, 'editable' => false),
			'SOURCE_ID' => array('id' => 'SOURCE_ID', 'name' => GetMessage('CRM_COLUMN_CONTACT_SOURCE'), 'sort' => 'source_id', 'default' => false, 'editable' => array('items' => CCrmStatus::GetStatusList('SOURCE')), 'type' => 'list'),
			'SOURCE_DESCRIPTION' => array('id' => 'SOURCE_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_CONTACT_SOURCE_DESCRIPTION'), 'sort' => false /**because of MSSQL**/, 'default' => false, 'editable' => false),
			'EXPORT' => array('id' => 'EXPORT', 'name' => GetMessage('CRM_COLUMN_CONTACT_EXPORT'), 'type' => 'checkbox', 'default' => false, 'editable' => true),
			'CREATED_BY' => array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_CONTACT_CREATED_BY'), 'sort' => 'created_by', 'default' => false, 'editable' => false, 'class' => 'username'),
			'DATE_CREATE' => array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_CONTACT_DATE_CREATE'), 'sort' => 'date_create', 'default' => false, 'class' => 'date'),
			'MODIFY_BY' => array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_CONTACT_MODIFY_BY'), 'sort' => 'modify_by', 'default' => false, 'editable' => false, 'class' => 'username'),
			'DATE_MODIFY' => array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_CONTACT_DATE_MODIFY'), 'sort' => 'date_modify', 'default' => false, 'class' => 'date'),
			'AUDIO_CALL' => array('id' => 'AUDIO_CALL', 'name' => GetMessage('CRM_COLUMN_AUDIO_CALL'), 'type' => 'HTML')
		);

		self::getFieldMulti($fields);
		if ($includeUserFields)
		{
			self::getFieldUser($fields, CCrmContact::$sUFEntityID);
		}

		return $fields;
	}

	public static function getContactFilterFields()
	{
		$contactTypeList = array("" => GetMessage("M_CRM_NOT_SELECTED"));
		$contactTypeList2 = CCrmStatus::GetStatusListEx('CONTACT_TYPE');
		foreach ($contactTypeList2 as $key => $val)
    	{
			$contactTypeList[$key] = $val;
   		 }

		$filterFields = array(
			array(
				"type" => "text",
				"id" => "NAME",
				"name" => GetMessage('CRM_COLUMN_CONTACT_NAME'),
				"value" => ""
			),
			array(
				"type" => "text",
				"id" => "LAST_NAME",
				"name" => GetMessage('CRM_COLUMN_CONTACT_LAST_NAME'),
				"value" => ""
			),
			array(
				"type" => "select",
				"id" => "TYPE_ID",
				"name" => GetMessage('CRM_COLUMN_CONTACT_TYPE'),
				"items" => $contactTypeList,
				"value" => ""
			),
			array(
				"type" => "date",
				"id" => "DATE_CREATE",
				"name" => GetMessage('CRM_COLUMN_CONTACT_DATE_CREATE'),
				"value" => ""
			),
			array(
				"type" => "date",
				"id" => "DATE_MODIFY",
				"name" => GetMessage('CRM_COLUMN_CONTACT_DATE_MODIFY'),
				"value" => ""
			),
		);

		return $filterFields;
	}

	public static function PrepareContactItem(&$item, &$params, $enums = array())
	{
		$itemID = intval($item['~ID']);

		if(isset($params['CONTACT_EDIT_URL_TEMPLATE']))
		{
			$item['EDIT_URL'] = CComponentEngine::MakePathFromTemplate(
				$params['CONTACT_EDIT_URL_TEMPLATE'],
				array('contact_id' => $itemID)
			);
		}

		$item['~FORMATTED_NAME'] = CCrmContact::PrepareFormattedName(
			array(
				'HONORIFIC' => $item['~HONORIFIC'],
				'NAME' => $item['~NAME'],
				'LAST_NAME' => $item['~LAST_NAME'],
				'SECOND_NAME' => $item['~SECOND_NAME']
			)
		);
		$item['FORMATTED_NAME'] = htmlspecialcharsbx($item['~FORMATTED_NAME']);

		$lastName = $item['~LAST_NAME'];
		$item['CLASSIFIER'] = $lastName !== ''? mb_strtoupper(mb_substr($lastName, 0, 1)) : '';

		if(!isset($item['~POST']))
		{
			$item['~POST'] = $item['POST'] = '';
		}

		$companyID = isset($item['~COMPANY_ID']) ? intval($item['~COMPANY_ID']) : 0;

		if ($item['COMPANY_ID'] && $item['COMPANY_TITLE'])
		{
			$url = CComponentEngine::MakePathFromTemplate(
				$params['COMPANY_SHOW_URL_TEMPLATE'],
				array('company_id' => $item['COMPANY_ID'])
			);

			$item['COMPANY_ID'] = "<span class='mobile-grid-field-link' onclick=\"BX.Mobile.Crm.loadPageBlank('".$url."');\">".$item['COMPANY_TITLE']."</span>";
		}

		if(!isset($item['~COMPANY_TITLE']))
		{
			$item['~COMPANY_TITLE'] = $item['COMPANY_TITLE'] = '';
		}

		/*$item['COMPANY_SHOW_URL'] = $companyID > 0
			? CComponentEngine::MakePathFromTemplate(
				$params['COMPANY_SHOW_URL_TEMPLATE'], array('company_id' => $companyID)
			) : '';*/

		if (is_array($enums["FIELDS"]) && in_array("ASSIGNED_BY", $enums["FIELDS"]))
		{
			self::PrepareUserLink($item, "ASSIGNED_BY", $params);
		}

		if (is_array($enums["FIELDS"]) && in_array("CREATED_BY", $enums["FIELDS"]))
		{
			self::PrepareUserLink($item, "CREATED_BY", $params);
		}

		if (is_array($enums["FIELDS"]) && in_array("MODIFY_BY", $enums["FIELDS"]))
		{
			self::PrepareUserLink($item, "MODIFY_BY", $params);
		}

		if(!isset($item['~POST']))
		{
			$item['~POST'] = $item['POST'] = '';
		}

		$item['FULL_ADDRESS'] = AddressFormatter::getSingleInstance()->formatHtmlMultiline(
			ContactAddress::mapEntityFields(
				[
					'ADDRESS' => $item['~ADDRESS'],
					'ADDRESS_2' => $item['~ADDRESS_2'],
					'ADDRESS_CITY' => $item['~ADDRESS_CITY'],
					'ADDRESS_REGION' => $item['~ADDRESS_REGION'],
					'ADDRESS_PROVINCE' => $item['~ADDRESS_PROVINCE'],
					'ADDRESS_POSTAL_CODE' => $item['~ADDRESS_POSTAL_CODE'],
					'ADDRESS_COUNTRY' => $item['~ADDRESS_COUNTRY'],
					'ADDRESS_LOC_ADDR_ID' => $item['~ADDRESS_LOC_ADDR_ID']
				]
			)
		);

		if(!isset($item['~COMMENTS']))
		{
			$item['~COMMENTS'] = $item['COMMENTS'] = '';
		}

		$item['COMMENTS'] = $item['~COMMENTS'];

		if(!isset($item['~TYPE_ID']))
		{
			$item['~TYPE_ID'] = $item['TYPE_ID'] = '';
		}

		$typeList = $enums && isset($enums['CONTACT_TYPE'])
			? $enums['CONTACT_TYPE'] : null;

		if(is_array($typeList))
		{
			$item['TYPE_ID'] = htmlspecialcharsbx($typeList[$item['~TYPE_ID']]);
		}

		$sourceList = $enums && isset($enums['SOURCE_LIST'])
			? $enums['SOURCE_LIST'] : null;

		if(is_array($sourceList))
		{
			$item['SOURCE_ID'] = htmlspecialcharsbx($enums['SOURCE_LIST'][$item['SOURCE_ID']]);
		}

		if (isset($item["DATE_CREATE"]))
		{
			$item["DATE_CREATE"] = ConvertTimeStamp(MakeTimeStamp($item['DATE_CREATE']));
		}

		if (isset($item["DATE_MODIFY"]))
		{
			$item["DATE_MODIFY"] = ConvertTimeStamp(MakeTimeStamp($item['DATE_MODIFY']));
		}

		if (isset($item["EXPORT"]))
		{
			$item["EXPORT"] = $item["EXPORT"] == "Y" ? GetMessage("CRM_TEXT_YES") : GetMessage("CRM_TEXT_NO");
		}

		$photoD = isset($item['PHOTO']) ? intval($item['PHOTO']) : 0;
		if($photoD > 0)
		{
			$listImageInfo = CFile::ResizeImageGet(
				$photoD, array('width' => 33, 'height' => 33), BX_RESIZE_IMAGE_PROPORTIONAL );
			$item['PHOTO_SRC'] = $listImageInfo["src"];
		}

		if (is_array($enums["FIELDS"]) && in_array("AUDIO_CALL", $enums["FIELDS"]))
		{
			CCrmMobileHelper::prepareAudioField($item, CCrmOwnerType::Contact);
		}

		if (isset($enums['CHECKBOX_USER_FIELDS']) && is_array($enums['CHECKBOX_USER_FIELDS']) && !empty($enums['CHECKBOX_USER_FIELDS']))
		{
			foreach($enums['CHECKBOX_USER_FIELDS'] as $fieldId)
			{
				$item[$fieldId] = $item[$fieldId] == 1 ? GetMessage("CRM_TEXT_YES") : GetMessage("CRM_TEXT_NO");
			}
		}
	}

	public static function PrepareContactData(&$fields)
	{
		$legend = '';
		$companyTitle = isset($fields['~COMPANY_TITLE']) ? $fields['~COMPANY_TITLE'] : '';
		$post = isset($fields['~POST']) ? $fields['~POST'] : '';

		if($companyTitle !== '' && $post !== '')
		{
			$legend = "{$companyTitle}, {$post}";
		}
		elseif($companyTitle !== '')
		{
			$legend = $companyTitle;
		}
		elseif($post !== '')
		{
			$legend = $post;
		}

		$listImageInfo = null;
		$viewImageInfo = null;
		$photoID = isset($fields['PHOTO']) ? intval($fields['PHOTO']) : 0;
		if($photoID > 0)
		{
			$listImageInfo = CFile::ResizeImageGet(
				$photoID, array('width' => 40, 'height' => 40), BX_RESIZE_IMAGE_EXACT);
			$viewImageInfo = CFile::ResizeImageGet(
				$photoID, array('width' => 55, 'height' => 55), BX_RESIZE_IMAGE_EXACT);
		}
		else
		{
			$listImageInfo = array('src' => SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_contact_small.png?ver=1');
			$viewImageInfo = array('src' => SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_contact_big.png?ver=1');
		}

		$multi = "";
		if (isset($fields[CCrmFieldMulti::PHONE]))
		{
			foreach($fields[CCrmFieldMulti::PHONE] as $type => $value)
			{
				$multi.= htmlspecialcharsbx($value)."<br>";
			}
		}
		if (isset($fields[CCrmFieldMulti::EMAIL]))
		{
			foreach($fields[CCrmFieldMulti::EMAIL] as $type => $value)
			{
				$multi.= htmlspecialcharsbx($value)."<br>";
			}
		}

		return array(
			'ID' => $fields['~ID'],
			'NAME' => isset($fields['~NAME']) ? $fields['~NAME'] : '',
			'LAST_NAME' => isset($fields['~LAST_NAME']) ? $fields['~LAST_NAME'] : '',
			'SECOND_NAME' => isset($fields['~SECOND_NAME']) ? $fields['~SECOND_NAME'] : '',
			'FORMATTED_NAME' => isset($fields['~FORMATTED_NAME']) ? $fields['~FORMATTED_NAME'] : '',
			'COMPANY_ID' => isset($fields['~COMPANY_ID']) ? $fields['~COMPANY_ID'] : '',
			'COMPANY_TITLE' => $companyTitle,
			'POST' => $post,
			'ASSIGNED_BY_ID' => isset($fields['~ASSIGNED_BY_ID']) ? $fields['~ASSIGNED_BY_ID'] : '',
			'ASSIGNED_BY_FORMATTED_NAME' => isset($fields['~ASSIGNED_BY_FORMATTED_NAME']) ? $fields['~ASSIGNED_BY_FORMATTED_NAME'] : '',
			'COMMENTS' => isset($fields['~COMMENTS']) ? $fields['~COMMENTS'] : '',
			'DATE_CREATE' => isset($fields['~DATE_CREATE']) ? $fields['~DATE_CREATE'] : '',
			'DATE_MODIFY' => isset($fields['~DATE_MODIFY']) ? $fields['~DATE_MODIFY'] : '',
			'LEGEND' => $legend,
			'CLASSIFIER' => isset($fields['CLASSIFIER']) ? $fields['CLASSIFIER'] : '',
			//'COMPANY_SHOW_URL' => isset($fields['COMPANY_SHOW_URL']) ? $fields['COMPANY_SHOW_URL'] : '',
			'ASSIGNED_BY_SHOW_URL' => isset($fields['ASSIGNED_BY_SHOW_URL']) ? $fields['ASSIGNED_BY_SHOW_URL'] : '',
			'SHOW_URL' => isset($fields['SHOW_URL']) ? $fields['SHOW_URL'] : '',
			'EDIT_URL' => isset($fields['EDIT_URL']) ? $fields['EDIT_URL'] : '',
			'IMAGE_ID' => $photoID,
			'LIST_IMAGE_URL' => $listImageInfo && isset($listImageInfo['src']) ? $listImageInfo['src'] : '',
			'VIEW_IMAGE_URL' => $viewImageInfo && isset($viewImageInfo['src']) ? $viewImageInfo['src'] : '',
			'MULTI_FIELDS' => $multi
		);
	}

	public static function getCompanySortFields()
	{
		$addressLabels = Bitrix\Crm\EntityAddress::getShortLabels();
		$regAddressLabels = Bitrix\Crm\EntityAddress::getShortLabels(EntityAddressType::Registered);

		$fields = array(
			'ID' => array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_COMPANY_ID'), 'sort' => 'id'),
			'COMPANY_SUMMARY' => array('id' => 'COMPANY_SUMMARY', 'name' => GetMessage('CRM_COLUMN_COMPANY_COMPANY'), 'sort' => 'title'),
			'TITLE' => array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_COMPANY_TITLE'), 'sort' => 'title'),
			'COMPANY_TYPE' => array('id' => 'COMPANY_TYPE', 'name' => GetMessage('CRM_COLUMN_COMPANY_COMPANY_TYPE'), 'sort' => 'company_type'),
			'EMPLOYEES' => array('id' => 'EMPLOYEES', 'name' => GetMessage('CRM_COLUMN_COMPANY_EMPLOYEES'), 'sort' => 'employees'),
			'ASSIGNED_BY' => array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_COMPANY_ASSIGNED_BY'), 'sort' => 'assigned_by'),

			'ADDRESS' => array('id' => 'ADDRESS', 'name' => $addressLabels['ADDRESS'], 'sort' => 'address'),
			'ADDRESS_2' => array('id' => 'ADDRESS_2', 'name' => $addressLabels['ADDRESS_2'], 'sort' => 'address_2'),
			'ADDRESS_CITY' => array('id' => 'ADDRESS_CITY', 'name' => $addressLabels['CITY'], 'sort' => 'address_city'),
			'ADDRESS_REGION' => array('id' => 'ADDRESS_REGION', 'name' => $addressLabels['REGION'], 'sort' => 'address_region'),
			'ADDRESS_PROVINCE' => array('id' => 'ADDRESS_PROVINCE', 'name' => $addressLabels['PROVINCE'], 'sort' => 'address_province'),
			'ADDRESS_POSTAL_CODE' => array('id' => 'ADDRESS_POSTAL_CODE', 'name' => $addressLabels['POSTAL_CODE'], 'sort' => 'address_postal_code'),
			'ADDRESS_COUNTRY' => array('id' => 'ADDRESS_COUNTRY', 'name' => $addressLabels['COUNTRY'], 'sort' => 'address_country'),

			'ADDRESS_LEGAL' => array('id' => 'ADDRESS_LEGAL', 'name' => $regAddressLabels['ADDRESS'], 'sort' => 'registered_address'),
			'REG_ADDRESS_2' => array('id' => 'REG_ADDRESS_2', 'name' => $regAddressLabels['ADDRESS_2'], 'sort' => 'registered_address_2'),
			'REG_ADDRESS_CITY' => array('id' => 'REG_ADDRESS_CITY', 'name' => $regAddressLabels['CITY'], 'sort' => 'registered_address_city'),
			'REG_ADDRESS_REGION' => array('id' => 'REG_ADDRESS_REGION', 'name' => $regAddressLabels['REGION'], 'sort' => 'registered_address_region'),
			'REG_ADDRESS_PROVINCE' => array('id' => 'REG_ADDRESS_PROVINCE', 'name' => $regAddressLabels['PROVINCE'], 'sort' => 'registered_address_province'),
			'REG_ADDRESS_POSTAL_CODE' => array('id' => 'REG_ADDRESS_POSTAL_CODE', 'name' => $regAddressLabels['POSTAL_CODE'], 'sort' => 'registered_address_postal_code'),
			'REG_ADDRESS_COUNTRY' => array('id' => 'REG_ADDRESS_COUNTRY', 'name' => $regAddressLabels['COUNTRY'], 'sort' => 'registered_address_country'),

			'INDUSTRY' => array('id' => 'INDUSTRY', 'name' => GetMessage('CRM_COLUMN_COMPANY_INDUSTRY'), 'sort' => 'industry'),
			'REVENUE' => array('id' => 'REVENUE', 'name' => GetMessage('CRM_COLUMN_COMPANY_REVENUE'), 'sort' => 'revenue'),
			'CURRENCY_ID' => array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_COMPANY_CURRENCY_ID'), 'sort' => 'currency_id'),
			'CREATED_BY' => array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_COMPANY_CREATED_BY'), 'sort' => 'created_by'),
			'DATE_CREATE' => array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_COMPANY_DATE_CREATE'), 'sort' => 'date_create'),
			'MODIFY_BY' => array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_COMPANY_MODIFY_BY'), 'sort' => 'modify_by'),
			'DATE_MODIFY' => array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_COMPANY_DATE_MODIFY'), 'sort' => 'date_modify')
		);

		return $fields;
	}

	public static function getCompanyFilterFields()
	{
		$companyTypeList = array("" => GetMessage("M_CRM_NOT_SELECTED"));
		$companyTypeList2 = CCrmStatus::GetStatusList('COMPANY_TYPE');
		foreach ($companyTypeList2 as $key => $val)
		{
			$companyTypeList[$key] = $val;
		}
		$industryList = CCrmStatus::GetStatusList('INDUSTRY');

		$filterFields = array(
			array(
				"type" => "text",
				"id" => "TITLE",
				"name" => GetMessage('CRM_COLUMN_COMPANY_TITLE'),
				"value" => ""
			),
			array(
				"type" => "select",
				"id" => "COMPANY_TYPE",
				"name" => GetMessage('CRM_COLUMN_COMPANY_COMPANY_TYPE'),
				"items" => $companyTypeList,
				"value" => ""
			),
			array(
				"type" => "select",
				"id" => "INDUSTRY",
				"name" => GetMessage('CRM_COLUMN_COMPANY_INDUSTRY'),
				"items" => array_merge(array("" => GetMessage("M_CRM_NOT_SELECTED")), $industryList),
				"value" => ""
			),
			array(
				"type" => "date",
				"id" => "DATE_CREATE",
				"name" => GetMessage('CRM_COLUMN_COMPANY_DATE_CREATE'),
				"value" => ""
			),
			array(
				"type" => "date",
				"id" => "DATE_MODIFY",
				"name" => GetMessage('CRM_COLUMN_COMPANY_DATE_MODIFY'),
				"value" => ""
			),
		);

		return $filterFields;
	}

	public static function getCompanyFields($includeUserFields = true)
	{
		$addressLabels = Bitrix\Crm\EntityAddress::getShortLabels();
		$regAddressLabels = Bitrix\Crm\EntityAddress::getShortLabels(EntityAddressType::Registered);

		$fields = array(
			'ID' => array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_COMPANY_ID')),
		//	'COMPANY_SUMMARY' => array('id' => 'COMPANY_SUMMARY', 'name' => GetMessage('CRM_COLUMN_COMPANY_COMPANY')),
			'LOGO' => array('id' => 'LOGO', 'name' => GetMessage('CRM_COLUMN_COMPANY_LOGO')),
		//	'TITLE' => array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_COMPANY_TITLE')),
			'COMPANY_TYPE' => array('id' => 'COMPANY_TYPE', 'name' => GetMessage('CRM_COLUMN_COMPANY_COMPANY_TYPE')),
			'EMPLOYEES' => array('id' => 'EMPLOYEES', 'name' => GetMessage('CRM_COLUMN_COMPANY_EMPLOYEES')),
			'ASSIGNED_BY' => array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_COMPANY_ASSIGNED_BY')),

			'FULL_ADDRESS' => array('id' => 'FULL_ADDRESS', 'name' => Bitrix\Crm\EntityAddress::getFullAddressLabel()),
			'FULL_REG_ADDRESS' => array('id' => 'FULL_REG_ADDRESS', 'name' => Bitrix\Crm\EntityAddress::getFullAddressLabel(EntityAddressType::Registered)),

			'BANKING_DETAILS' => array('id' => 'BANKING_DETAILS', 'name' => GetMessage('CRM_COLUMN_COMPANY_BANKING_DETAILS')),
			'INDUSTRY' => array('id' => 'INDUSTRY', 'name' => GetMessage('CRM_COLUMN_COMPANY_INDUSTRY')),
			'FORMATTED_REVENUE' => array('id' => 'FORMATTED_REVENUE', 'name' => GetMessage('CRM_COLUMN_COMPANY_FORMATTED_REVENUE')),
			'COMMENTS' => array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMPANY_COMMENTS')),
			'CREATED_BY' => array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_COMPANY_CREATED_BY')),
			'DATE_CREATE' => array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_COMPANY_DATE_CREATE')),
			'MODIFY_BY' => array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_COMPANY_MODIFY_BY')),
			'DATE_MODIFY' => array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_COMPANY_DATE_MODIFY')),
			'AUDIO_CALL' => array('id' => 'AUDIO_CALL', 'name' => GetMessage('CRM_COLUMN_AUDIO_CALL'), 'type' => 'HTML')
		);

		self::getFieldMulti($fields);
		if ($includeUserFields)
		{
			self::getFieldUser($fields, CCrmCompany::$sUFEntityID);
		}
		return $fields;
	}

	public static function PrepareCompanyItem(&$item, &$params, $enums = array())
	{
		$itemID = intval($item['~ID']);

		if (isset($item["COMPANY_TYPE"]))
		{
			$typeList = $enums && isset($enums['COMPANY_TYPE'])
				? $enums['COMPANY_TYPE'] : CCrmStatus::GetStatusList('COMPANY_TYPE');

			$item["COMPANY_TYPE"] = htmlspecialcharsbx($typeList[$item['~COMPANY_TYPE']]);
		}

		if (isset($item["INDUSTRY"]))
		{
			$industryList = $enums && isset($enums['INDUSTRY'])
				? $enums['INDUSTRY'] : CCrmStatus::GetStatusList('INDUSTRY');

			$item["INDUSTRY"] = htmlspecialcharsbx($industryList[$item['INDUSTRY']]);
		}

		if (isset($item["EMPLOYEES"]))
		{
			$employeesList = $enums && isset($enums['EMPLOYEES_LIST'])
				? $enums['EMPLOYEES_LIST'] : CCrmStatus::GetStatusList('EMPLOYEES');

			$item["EMPLOYEES"] = htmlspecialcharsbx($employeesList[$item['EMPLOYEES']]);
		}

		$item['~FORMATTED_REVENUE'] = CCrmCurrency::MoneyToString(
			isset($item['~REVENUE']) ? $item['~REVENUE'] : '',
			isset($item['~CURRENCY_ID']) ? $item['~CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID()
		);
		$item['FORMATTED_REVENUE'] = strip_tags($item['~FORMATTED_REVENUE']);

		if (is_array($enums["FIELDS"]) && in_array("ASSIGNED_BY", $enums["FIELDS"]))
		{
			self::PrepareUserLink($item, "ASSIGNED_BY", $params);
		}

		if (is_array($enums["FIELDS"]) && in_array("CREATED_BY", $enums["FIELDS"]))
		{
			self::PrepareUserLink($item, "CREATED_BY", $params);
		}

		if (is_array($enums["FIELDS"]) && in_array("MODIFY_BY", $enums["FIELDS"]))
		{
			self::PrepareUserLink($item, "MODIFY_BY", $params);
		}

		if (isset($item["DATE_CREATE"]))
		{
			$item["DATE_CREATE"] = ConvertTimeStamp(MakeTimeStamp($item['DATE_CREATE']));
		}

		if (isset($item["DATE_MODIFY"]))
		{
			$item["DATE_MODIFY"] = ConvertTimeStamp(MakeTimeStamp($item['DATE_MODIFY']));
		}

		$logoID = isset($item['LOGO']) ? intval($item['LOGO']) : 0;
		if($logoID > 0)
		{
			$listImageInfo = CFile::ResizeImageGet(
				$logoID, array('width' => 33, 'height' => 33), BX_RESIZE_IMAGE_PROPORTIONAL );
			$item['LOGO_SRC'] = $listImageInfo["src"];
		}

		if (is_array($enums["FIELDS"]) && in_array("AUDIO_CALL", $enums["FIELDS"]))
		{
			CCrmMobileHelper::prepareAudioField($item, CCrmOwnerType::Company);
		}

		if (isset($enums['CHECKBOX_USER_FIELDS']) && is_array($enums['CHECKBOX_USER_FIELDS']) && !empty($enums['CHECKBOX_USER_FIELDS']))
		{
			foreach($enums['CHECKBOX_USER_FIELDS'] as $fieldId)
			{
				$item[$fieldId] = $item[$fieldId] == 1 ? GetMessage("CRM_TEXT_YES") : GetMessage("CRM_TEXT_NO");
			}
		}

		$item['COMMENTS'] = $item['~COMMENTS'];
	}

	public static function PrepareCompanyData(&$fields)
	{
		$listImageInfo = null;
		$viewImageInfo = null;
		$logoID = isset($fields['LOGO']) ? intval($fields['LOGO']) : 0;
		if($logoID > 0)
		{
			$listImageInfo = CFile::ResizeImageGet(
				$logoID, array('width' => 32, 'height' => 32), BX_RESIZE_IMAGE_EXACT);
			$viewImageInfo = CFile::ResizeImageGet(
				$logoID, array('width' => 55, 'height' => 55), BX_RESIZE_IMAGE_EXACT);
		}
		else
		{
			$viewImageInfo = array('src' => SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_company_big.png?ver=1');
			$listImageInfo = array('src' => SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_company_small.png?ver=1');
		}

		return array(
			'ID' => $fields['~ID'],
			'TITLE' => isset($fields['~TITLE']) ? $fields['~TITLE'] : '',
			'COMPANY_TYPE' => isset($fields['~COMPANY_TYPE']) ? $fields['~COMPANY_TYPE'] : '',
			'COMPANY_TYPE_NAME' => isset($fields['~COMPANY_TYPE_NAME']) ? $fields['~COMPANY_TYPE_NAME'] : '',
			'INDUSTRY' => isset($fields['~INDUSTRY']) ? $fields['~INDUSTRY'] : '',
			'INDUSTRY_NAME' => isset($fields['~INDUSTRY_NAME']) ? $fields['~INDUSTRY_NAME'] : '',
			'EMPLOYEES' => isset($fields['~EMPLOYEES']) ? $fields['~EMPLOYEES'] : '',
			'EMPLOYEES_NAME' => isset($fields['~EMPLOYEES_NAME']) ? $fields['~EMPLOYEES_NAME'] : '',
			'REVENUE' => isset($fields['~REVENUE']) ? doubleval($fields['~REVENUE']) : 0.0,
			'ASSIGNED_BY_ID' => isset($fields['~ASSIGNED_BY_ID']) ? $fields['~ASSIGNED_BY_ID'] : '',
			'ASSIGNED_BY_FORMATTED_NAME' => isset($fields['~ASSIGNED_BY_FORMATTED_NAME']) ? $fields['~ASSIGNED_BY_FORMATTED_NAME'] : '',
			'ADDRESS' => isset($fields['~ADDRESS']) ? $fields['~ADDRESS'] : '',
			'ADDRESS_LEGAL' => isset($fields['~ADDRESS_LEGAL']) ? $fields['~ADDRESS_LEGAL'] : '',
			'BANKING_DETAILS' => isset($fields['~BANKING_DETAILS']) ? $fields['~BANKING_DETAILS'] : '',
			'COMMENTS' => isset($fields['~COMMENTS']) ? $fields['~COMMENTS'] : '',
			'DATE_CREATE' => isset($fields['~DATE_CREATE']) ? $fields['~DATE_CREATE'] : '',
			'DATE_MODIFY' => isset($fields['~DATE_MODIFY']) ? $fields['~DATE_MODIFY'] : '',
			'ASSIGNED_BY_SHOW_URL' => isset($fields['ASSIGNED_BY_SHOW_URL']) ? $fields['ASSIGNED_BY_SHOW_URL'] : '',
			'SHOW_URL' => isset($fields['SHOW_URL']) ? $fields['SHOW_URL'] : '',
			'IMAGE_ID' => $logoID,
			'LIST_IMAGE_URL' => $listImageInfo && isset($listImageInfo['src']) ? $listImageInfo['src'] : '',
			'VIEW_IMAGE_URL' => $viewImageInfo && isset($viewImageInfo['src']) ? $viewImageInfo['src'] : ''
		);
	}

	public static function PrepareUserLink(&$item, $prefix = "", &$params)
	{
		$id = isset($item[$prefix.'_ID']) ? intval($item[$prefix.'_ID']) : 0;
		$url = $id > 0  ?
			CComponentEngine::MakePathFromTemplate(
				$params['USER_PROFILE_URL_TEMPLATE'],
				array('user_id' => $id)
			) : '';

		$value = $id > 0
			? CUser::FormatName(
				$params['NAME_TEMPLATE'],
				array(
					'LOGIN' => isset($item['~'.$prefix.'_LOGIN']) ? $item['~'.$prefix.'_LOGIN'] : '',
					'NAME' => isset($item['~'.$prefix.'_NAME']) ? $item['~'.$prefix.'_NAME'] : '',
					'LAST_NAME' => isset($item['~'.$prefix.'_LAST_NAME']) ? $item['~'.$prefix.'_LAST_NAME'] : '',
					'SECOND_NAME' => isset($item['~'.$prefix.'_SECOND_NAME']) ? $item['~'.$prefix.'_SECOND_NAME'] : ''
				),
				true, false
			) : '';

		$item[$prefix] = "<span class='mobile-grid-field-link' onclick=\"BX.Mobile.Crm.loadPageBlank('".$url."');\">".htmlspecialcharsbx($value)."</span>";
	}

	public static function PrepareImageUrl(&$fields, $fieldID, $size)
	{
		$fieldID = strval($fieldID);
		if($fieldID === '')
		{
			return '';
		}

		$width = is_array($size) && isset($size['WIDTH']) ? intval($size['WIDTH']) : 50;
		$height = is_array($size) && isset($size['HEIGHT']) ? intval($size['HEIGHT']) : 50;

		if($fieldID)
			$imageID = isset($fields[$fieldID]) ? intval($fields[$fieldID]) : 0;
		if($imageID > 0)
		{
			$info = CFile::ResizeImageGet(
				$imageID, array('width' => $width, 'height' => $height), BX_RESIZE_IMAGE_EXACT);

			return isset($info['src']) ? $info['src'] : '';
		}

		return '';
	}

	public static function PrepareCompanyImageUrl(&$fields, $size)
	{
		$url = self::PrepareImageUrl($fields, 'LOGO', $size);
		return $url !== ''
			? $url : SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_company_small.png?ver=1';
	}

	public static function PrepareContactImageUrl(&$fields, $size)
	{
		$url = self::PrepareImageUrl($fields, 'PHOTO', $size);
		return $url !== ''
			? $url : SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_contact_small.png?ver=1';
	}

	public static function getLeadSortFields()
	{
		$addressLabels = Bitrix\Crm\EntityAddress::getShortLabels();

		$sortFields = array(
			array('id' => 'LEAD_FORMATTED_NAME', 'name' => GetMessage('CRM_COLUMN_LEAD_FULL_NAME'), 'sort' => 'last_name'),
			array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_LEAD_TITLE'), 'sort' => 'title'),
			array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_LEAD_NAME'), 'sort' => 'name'),
			array('id' => 'SECOND_NAME', 'name' => GetMessage('CRM_COLUMN_LEAD_SECOND_NAME'), 'sort' => 'second_name'),
			array('id' => 'LAST_NAME', 'name' => GetMessage('CRM_COLUMN_LEAD_LAST_NAME'), 'sort' => 'last_name'),
			array('id' => 'BIRTHDATE', 'name' => GetMessage('CRM_COLUMN_LEAD_BIRTHDATE'), 'sort' => 'BIRTHDATE'),
			array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_LEAD_DATE_CREATE'), 'sort' => 'date_create'),
			array('id' => 'SOURCE_ID', 'name' => GetMessage('CRM_COLUMN_LEAD_SOURCE'), 'sort' => 'source_id'),
			array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_LEAD_ASSIGNED_BY'), 'sort' => 'assigned_by'),
			array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_LEAD_CREATED_BY'), 'sort' => 'created_by'),
			array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_LEAD_DATE_MODIFY'), 'sort' => 'date_modify'),
			array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_LEAD_MODIFY_BY'), 'sort' => 'modify_by'),
			array('id' => 'COMPANY_TITLE', 'name' => GetMessage('CRM_COLUMN_LEAD_COMPANY_TITLE'), 'sort' => 'company_title'),
			array('id' => 'POST', 'name' => GetMessage('CRM_COLUMN_LEAD_POST'), 'sort' => 'post'),

			array('id' => 'ADDRESS', 'name' => $addressLabels['ADDRESS'], 'sort' => 'address'),
			array('id' => 'ADDRESS_2', 'name' => $addressLabels['ADDRESS_2'], 'sort' => 'address_2'),
			array('id' => 'ADDRESS_CITY', 'name' => $addressLabels['CITY'], 'sort' => 'address_city'),
			array('id' => 'ADDRESS_REGION', 'name' => $addressLabels['REGION'], 'sort' => 'address_region'),
			array('id' => 'ADDRESS_PROVINCE', 'name' => $addressLabels['PROVINCE'], 'sort' => 'address_province'),
			array('id' => 'ADDRESS_POSTAL_CODE', 'name' => $addressLabels['POSTAL_CODE'], 'sort' => 'address_postal_code'),
			array('id' => 'ADDRESS_COUNTRY', 'name' => $addressLabels['COUNTRY'], 'sort' => 'address_country'),

			array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_LEAD_OPPORTUNITY_2'), 'sort' => 'opportunity'),
			array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_LEAD_CURRENCY_ID'), 'sort' => 'currency_id'),
		);

		return $sortFields;
	}

	public static function getLeadFields($includeUserFields = true)
	{
		$addressLabels = Bitrix\Crm\EntityAddress::getShortLabels();

		$fields = array(
			'ID' => array('id' => 'ID', 'name' => 'ID'),
			'FULL_NAME' => array('id' => 'FULL_NAME', 'name' => GetMessage('CRM_COLUMN_LEAD_FULL_NAME')),
			//'TITLE' => array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_LEAD_TITLE')),
			'STATUS_ID' => array('id' => 'STATUS_ID', 'name' => GetMessage('CRM_COLUMN_LEAD_STATUS_ID'), 'type' => 'HTML'),

			/*'NAME' => array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_LEAD_NAME'), 'class' => 'username'),
			'SECOND_NAME' => array('id' => 'SECOND_NAME', 'name' => GetMessage('CRM_COLUMN_LEAD_SECOND_NAME'), 'class' => 'username'),
			'LAST_NAME' => array('id' => 'LAST_NAME', 'name' => GetMessage('CRM_COLUMN_LEAD_LAST_NAME'), 'class' => 'username'),*/
			'BIRTHDATE' => array('id' => 'BIRTHDATE', 'name' => GetMessage('CRM_COLUMN_LEAD_BIRTHDATE'), 'type' => 'date'),
			'DATE_CREATE' => array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_LEAD_DATE_CREATE'), 'class' => 'date'),
			'SOURCE_ID' => array('id' => 'SOURCE_ID', 'name' => GetMessage('CRM_COLUMN_LEAD_SOURCE'), 'type' => 'list'),

			'ASSIGNED_BY' => array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_LEAD_ASSIGNED_BY')),
			//'STATUS_DESCRIPTION' => array('id' => 'STATUS_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_LEAD_STATUS_DESCRIPTION'), 'sort' => false ),
			'SOURCE_DESCRIPTION' => array('id' => 'SOURCE_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_LEAD_SOURCE_DESCRIPTION')),
			'CREATED_BY' => array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_LEAD_CREATED_BY'), 'class' => 'username'),
			'DATE_MODIFY' => array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_LEAD_DATE_MODIFY'), 'class' => 'date'),
			'MODIFY_BY' => array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_LEAD_MODIFY_BY'), 'class' => 'username'),
			'COMPANY_TITLE' => array('id' => 'COMPANY_TITLE', 'name' => GetMessage('CRM_COLUMN_LEAD_COMPANY_TITLE')),
			'POST' => array('id' => 'POST', 'name' => GetMessage('CRM_COLUMN_LEAD_POST')),

			'FULL_ADDRESS' => array('id' => 'FULL_ADDRESS', 'name' => Bitrix\Crm\EntityAddress::getFullAddressLabel()),
			'COMMENTS' => array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_LEAD_COMMENTS')),
			'FORMATTED_OPPORTUNITY' => array('id' => 'FORMATTED_OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_LEAD_FORMATTED_OPPORTUNITY'), 'align' => 'right'),
			//	'OPPORTUNITY' => array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_LEAD_OPPORTUNITY_2'), 'align' => 'right'),
			//	'CURRENCY_ID' => array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_LEAD_CURRENCY_ID')),
			'PRODUCT_ID' => array('id' => 'PRODUCT_ID', 'name' => GetMessage('CRM_COLUMN_LEAD_PRODUCT_ID')),
			'AUDIO_CALL' => array('id' => 'AUDIO_CALL', 'name' => GetMessage('CRM_COLUMN_AUDIO_CALL'), 'type' => 'HTML')
		);

		self::getFieldMulti($fields);

		if ($includeUserFields)
		{
			self::getFieldUser($fields, CCrmLead::$sUFEntityID);
		}

		return $fields;
	}

	public static function getLeadFilterFields()
	{
		$arStatuses = array();
		$arStatuses[""] = GetMessage("M_CRM_NOT_SELECTED");
		$leadStatuses = CCrmStatus::GetStatusList('STATUS');
		foreach($leadStatuses as $code => $name)
		{
			$arStatuses[$code] = $name;
		}

		$resSources = ["" => GetMessage("M_CRM_NOT_SELECTED")];
		$sources = self::GetStatusList('SOURCE');
		foreach($sources as $code => $name)
		{
			$resSources[$code] = $name;
		}

		$filterFields = array(
			array(
				"type" => "text",
				"id" => "TITLE",
				"name" => GetMessage('CRM_COLUMN_LEAD_TITLE'),
				"value" => ""
			),
			array(
				"type" => "select",
				"id" => "STATUS_ID",
				"name" => GetMessage('CRM_COLUMN_LEAD_STATUS_ID'),
				"items" => $arStatuses,
				"value" => ""
			),
			array(
				"type" => "select-user",
				"id" => "ASSIGNED_BY_ID",
				"name" => GetMessage('CRM_COLUMN_LEAD_ASSIGNED_BY'),
				"value" => ""
			),
			array(
				"type" => "date",
				"id" => "DATE_CREATE",
				"name" => GetMessage('CRM_COLUMN_LEAD_DATE_CREATE'),
				"value" => ""
			),
			array(
				"type" => "select",
				"id" => "SOURCE_ID",
				"name" => GetMessage('CRM_COLUMN_LEAD_SOURCE'),
				"items" => $resSources,
				"value" => ""
			)
		);

		return $filterFields;
	}

	public static function PrepareLeadItem(&$item, &$params, $enums = array())
	{
		$itemID = intval($item['~ID']);

		$statusList = $enums && isset($enums['STATUS_LIST'])
			? $enums['STATUS_LIST'] : CCrmViewHelper::GetLeadStatusInfos();

		$statusID = isset($item['~STATUS_ID']) ? $item['~STATUS_ID'] : '';
		$statusName = htmlspecialcharsbx($statusList[$statusID]["NAME"]);

		$jsStatusList = array();
		$i=0;
		foreach ($statusList as $statusId => $statusInfo)
		{
			$jsStatusList["s".$i] = array(
				"STATUS_ID" => $statusInfo["STATUS_ID"],
				"NAME" => htmlspecialcharsbx($statusInfo["NAME"]),
				"COLOR" => $statusInfo["COLOR"]
			);
			$i++;
		}

		$onStatusClick = "";
		if (
			isset($enums["IS_EDIT_PERMITTED"])
			&& $enums["IS_EDIT_PERMITTED"]
			&& $statusID !== "CONVERTED"
		)
			$onStatusClick = 'BX.Mobile.Crm.List.showStatusList('.$itemID.','.CUtil::PhpToJSObject($jsStatusList).', \'onCrmLeadDetailUpdate\')';

		$item['STATUS_ID'] = '
			<div class="mobile-grid-field" onclick="'.$onStatusClick.'">
				<span class="mobile-grid-field-progress" data-role="mobile-crm-status-entity-'.$itemID.'">';

		$stopColor = false;
		foreach($statusList as $statusCode => $statusInfo)
		{
			$item['STATUS_ID'].= '<span data-role="mobile-crm-status-block-'.$statusCode.'" class="mobile-grid-field-progress-step" '.($stopColor ? '' : 'style="background: '.$statusList[$statusID]["COLOR"].'"').'>&nbsp;</span>';

			if ($statusID == $statusCode)
				$stopColor = true;

			if ($statusCode == "JUNK" || $statusCode == "CONVERTED" )
				break;
		}
		$item['STATUS_ID'].= '</span>
				<span class="mobile-grid-field-textarea-title">'.GetMessage("CRM_COLUMN_LEAD_STATUS").' - <span data-role="mobile-crm-status-name-'.$itemID.'">'.$statusName.'</span></span>
			</div>';

		$sourceList = $enums && isset($enums['SOURCE_LIST'])
			? $enums['SOURCE_LIST'] : self::GetStatusList('SOURCE');

		$sourceID = isset($item['~SOURCE_ID']) ? $item['~SOURCE_ID'] : '';
		if($sourceID === '' || !isset($sourceList[$sourceID]))
		{
			$item['~SOURCE_ID'] = $item['SOURCE_ID'] = '';
		}
		else
		{
			$item['~SOURCE_ID'] = $sourceList[$sourceID];
			$item['SOURCE_ID'] = htmlspecialcharsbx($item['~SOURCE_ID']);
		}

		$currencyID = isset($item['~CURRENCY_ID']) ? $item['~CURRENCY_ID'] : '';
		if($currencyID === '')
		{
			$currencyID = $item['~CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
		}

		$item['~FORMATTED_OPPORTUNITY'] = CCrmCurrency::MoneyToString(
			isset($item['~OPPORTUNITY']) ? $item['~OPPORTUNITY'] : '',
			$currencyID
		);
		$item['FORMATTED_OPPORTUNITY'] = strip_tags($item['~FORMATTED_OPPORTUNITY']);

		/*
		$item['~FORMATTED_NAME'] = CCrmLead::PrepareFormattedName(
			array(
				'HONORIFIC' => isset($item['~HONORIFIC']) ? $item['~HONORIFIC'] : '',
				'NAME' => isset($item['~NAME']) ? $item['~NAME'] : '',
				'LAST_NAME' => isset($item['~LAST_NAME']) ? $item['~LAST_NAME'] : '',
				'SECOND_NAME' => isset($item['~SECOND_NAME']) ? $item['~SECOND_NAME'] : ''
			)
		);
		$item['FORMATTED_NAME'] = htmlspecialcharsbx($item['~FORMATTED_NAME']);*/

		if (is_array($enums["FIELDS"]) && in_array("ASSIGNED_BY", $enums["FIELDS"]))
		{
			self::PrepareUserLink($item, "ASSIGNED_BY", $params);
		}

		if (is_array($enums["FIELDS"]) && in_array("CREATED_BY", $enums["FIELDS"]))
		{
			self::PrepareUserLink($item, "CREATED_BY", $params);
		}

		if (is_array($enums["FIELDS"]) && in_array("MODIFY_BY", $enums["FIELDS"]))
		{
			self::PrepareUserLink($item, "MODIFY_BY", $params);
		}

		if (is_array($enums["FIELDS"]) && in_array("FULL_ADDRESS", $enums["FIELDS"]))
		{
			$item['FULL_ADDRESS'] = AddressFormatter::getSingleInstance()->formatHtmlMultiline(
				LeadAddress::mapEntityFields(
					[
						'ADDRESS' => $item['ADDRESS'],
						'ADDRESS_2' => $item['ADDRESS_2'],
						'ADDRESS_CITY' => $item['ADDRESS_CITY'],
						'ADDRESS_REGION' => $item['ADDRESS_REGION'],
						'ADDRESS_PROVINCE' => $item['ADDRESS_PROVINCE'],
						'ADDRESS_POSTAL_CODE' => $item['ADDRESS_POSTAL_CODE'],
						'ADDRESS_COUNTRY' => $item['ADDRESS_COUNTRY'],
						'ADDRESS_LOC_ADDR_ID' => $item['ADDRESS_LOC_ADDR_ID']
					]
				)
			);
		}
		if (isset($item["DATE_CREATE"]))
		{
			$item["DATE_CREATE"] = ConvertTimeStamp(MakeTimeStamp($item['DATE_CREATE']));
		}

		if (isset($item["DATE_MODIFY"]))
		{
			$item["DATE_MODIFY"] = ConvertTimeStamp(MakeTimeStamp($item['DATE_MODIFY']));
		}

		if (is_array($enums["FIELDS"]) && in_array("AUDIO_CALL", $enums["FIELDS"]))
		{
			CCrmMobileHelper::prepareAudioField($item, CCrmOwnerType::Lead);
		}

		if (isset($enums['CHECKBOX_USER_FIELDS']) && is_array($enums['CHECKBOX_USER_FIELDS']) && !empty($enums['CHECKBOX_USER_FIELDS']))
		{
			foreach($enums['CHECKBOX_USER_FIELDS'] as $fieldId)
			{
				$item[$fieldId] = $item[$fieldId] == 1 ? GetMessage("CRM_TEXT_YES") : GetMessage("CRM_TEXT_NO");
			}
		}

		if (isset($item["PRODUCT_ID"]))
		{
			$item["PRODUCT_ID"] = htmlspecialcharsbx($item["PRODUCT_ID"]);
		}

		$item['COMMENTS'] = $item['~COMMENTS'];
	}

	public static function getFieldMultiInfo()
	{
		$multiFields = CCrmFieldMulti::GetEntityTypes();
		return $multiFields;
	}

	public static function getFieldMulti(&$arHeaders)
	{
		$arTypeInfo = CCrmFieldMulti::GetEntityTypeInfos();
		foreach($arTypeInfo as $typeID => $info)
		{
			$arHeaders[$typeID] = array(
				'id' => $typeID,
				'name' => $info['NAME'],
				'sort' => false
			);
		}
	}

	public static function getFieldUser(&$arHeaders, $entity)
	{
		global $USER_FIELD_MANAGER;

		$arTypeInfo = array();
		$userType = new CCrmUserType($USER_FIELD_MANAGER, $entity);
		$userType->PrepareFieldsInfo($arTypeInfo);

		if (!empty($arTypeInfo))
		{
			foreach($arTypeInfo as $typeID => $info)
			{
				$arHeaders[$typeID] = array(
					'id' => $typeID,
					'name' => $info['LABELS']['LIST'],
					'sort' => false,
					'TYPE_ID' => $info['TYPE'],
					'SETTINGS' => $info['SETTINGS'],
					'USER_TYPE' => $info['USER_TYPE']
				);

				if ($info['TYPE'] == 'boolean')
					$arHeaders[$typeID]['type'] = "CHECKBOX";
			}
		}
	}

	public static function PrepareLeadData(&$fields)
	{
		return array(
			'ID' => $fields['~ID'],
			'TITLE' => isset($fields['~TITLE']) ? $fields['~TITLE'] : '',
			'STATUS_ID' => isset($fields['~STATUS_ID']) ? $fields['~STATUS_ID'] : '',
			'STATUS_NAME' => isset($fields['~STATUS_NAME']) ? $fields['~STATUS_NAME'] : '',
			'SOURCE_ID' => isset($fields['~SOURCE_ID']) ? $fields['~SOURCE_ID'] : '',
			'SOURCE_NAME' => isset($fields['~SOURCE_NAME']) ? $fields['~SOURCE_NAME'] : '',
			'FORMATTED_NAME' => isset($fields['~FORMATTED_NAME']) ? $fields['~FORMATTED_NAME'] : '',
			'COMPANY_TITLE' => isset($fields['~COMPANY_TITLE']) ? $fields['~COMPANY_TITLE'] : '',
			'POST' => isset($fields['~POST']) ? $fields['~POST'] : '',
			'OPPORTUNITY' => isset($fields['~OPPORTUNITY']) ? $fields['~OPPORTUNITY'] : '',
			'FORMATTED_OPPORTUNITY' => isset($fields['FORMATTED_OPPORTUNITY']) ? $fields['FORMATTED_OPPORTUNITY'] : '',
			'ASSIGNED_BY_ID' => isset($fields['~ASSIGNED_BY_ID']) ? $fields['~ASSIGNED_BY_ID'] : '',
			'ASSIGNED_BY_FORMATTED_NAME' => isset($fields['~ASSIGNED_BY_FORMATTED_NAME']) ? $fields['~ASSIGNED_BY_FORMATTED_NAME'] : '',
			'COMMENTS' => isset($fields['~COMMENTS']) ? $fields['~COMMENTS'] : '',
			'DATE_CREATE' => isset($fields['~DATE_CREATE']) ? $fields['~DATE_CREATE'] : '',
			'DATE_MODIFY' => isset($fields['~DATE_MODIFY']) ? $fields['~DATE_MODIFY'] : '',
			'ASSIGNED_BY_SHOW_URL' => isset($fields['ASSIGNED_BY_SHOW_URL']) ? $fields['ASSIGNED_BY_SHOW_URL'] : '',
			'SHOW_URL' => isset($fields['SHOW_URL']) ? $fields['SHOW_URL'] : '',
			//'LIST_IMAGE_URL' => SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_lead_small.png?ver=1',
			'LIST_IMAGE_URL' => '',
			//'VIEW_IMAGE_URL' => SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_lead_big.png?ver=1'
			'VIEW_IMAGE_URL' => ''
		);
	}

	public static function PrepareActivityItem(&$item, &$params, $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$itemID = intval($item['ID']);

		if(!isset($item['SUBJECT']))
		{
			$item['SUBJECT'] = '';
		}

		if(!isset($item['DESCRIPTION']))
		{
			$item['DESCRIPTION'] = '';
		}

		if(!isset($item['LOCATION']))
		{
			$item['LOCATION'] = '';
		}

		$typeID = isset($item['TYPE_ID']) ? intval($item['TYPE_ID']) : CCrmActivityType::Undefined;
		$item['TYPE_ID'] = $typeID;

		$direction = isset($item['DIRECTION']) ? intval($item['DIRECTION']) : CCrmActivityDirection::Undefined;
		$item['DIRECTION'] = $direction;

		$priority = isset($item['PRIORITY']) ? intval($item['PRIORITY']) : CCrmActivityPriority::None;
		$item['PRIORITY'] = $priority;
		$item['IS_IMPORTANT'] = $priority === CCrmActivityPriority::High;

		$completed = isset($item['COMPLETED']) ? $item['COMPLETED'] === 'Y' : false;
		$item['COMPLETED'] = $completed ? 'Y' : 'N';

		if($typeID === CCrmActivityType::Task)
		{
			$taskID = isset($item['ASSOCIATED_ENTITY_ID']) ? intval($item['ASSOCIATED_ENTITY_ID']) : 0;
			$item['SHOW_URL'] = $taskID > 0 && isset($params['TASK_SHOW_URL_TEMPLATE'])
				? CComponentEngine::MakePathFromTemplate(
					$params['TASK_SHOW_URL_TEMPLATE'],
					array(
						'user_id' => isset($params['USER_ID']) ? $params['USER_ID'] : CCrmSecurityHelper::GetCurrentUserID(),
						'task_id' => $taskID
					)
				) : '';
			$item['DEAD_LINE'] = isset($item['DEADLINE'])
				? $item['DEADLINE'] : (isset($item['END_TIME']) ? $item['END_TIME'] : '');
		}
		else
		{
			if(isset($params['ACTIVITY_SHOW_URL_TEMPLATE']))
			{
				$item['SHOW_URL'] = CComponentEngine::makePathFromTemplate(
					$params['ACTIVITY_SHOW_URL_TEMPLATE'],
					array('activity_id' => $itemID)
				);
			}
			$item['DEAD_LINE'] = isset($item['DEADLINE'])
				? $item['DEADLINE'] : (isset($item['START_TIME']) ? $item['START_TIME'] : '');
		}

		//OWNER_TITLE
		$ownerTitle = '';
		$ownerID = isset($item['OWNER_ID']) ? intval($item['OWNER_ID']) : 0;
		$item['OWNER_ID'] = $ownerID;

		$ownerTypeID = isset($item['OWNER_TYPE_ID']) ? intval($item['OWNER_TYPE_ID']) : 0;
		$item['OWNER_TYPE_ID'] = $ownerTypeID;

		if($ownerID > 0 && $ownerTypeID > 0)
		{
			$ownerTitle = CCrmOwnerType::GetCaption($ownerTypeID, $ownerID);
		}

		$item['OWNER_TITLE'] = $ownerTitle;

		//OWNER_SHOW_URL
		$ownerShowUrl = '';
		if($ownerID > 0)
		{
			if($ownerTypeID === CCrmOwnerType::Lead)
			{
				$ownerShowUrl = isset($params['LEAD_SHOW_URL_TEMPLATE']) ? CComponentEngine::makePathFromTemplate(
					$params['LEAD_SHOW_URL_TEMPLATE'],
					array('lead_id' => $ownerID)
				) : '';
			}
			elseif($ownerTypeID === CCrmOwnerType::Contact)
			{
				$ownerShowUrl = isset($params['CONTACT_SHOW_URL_TEMPLATE']) ? CComponentEngine::makePathFromTemplate(
					$params['CONTACT_SHOW_URL_TEMPLATE'],
					array('contact_id' => $ownerID)
				) : '';
			}
			elseif($ownerTypeID === CCrmOwnerType::Company)
			{
				$ownerShowUrl = isset($params['COMPANY_SHOW_URL_TEMPLATE']) ? CComponentEngine::makePathFromTemplate(
					$params['COMPANY_SHOW_URL_TEMPLATE'],
					array('company_id' => $ownerID)
				) : '';
			}
			elseif($ownerTypeID === CCrmOwnerType::Deal)
			{
				$ownerShowUrl = isset($params['DEAL_SHOW_URL_TEMPLATE']) ? CComponentEngine::makePathFromTemplate(
					$params['DEAL_SHOW_URL_TEMPLATE'],
					array('deal_id' => $ownerID)
				) : '';
			}
		}
		$item['OWNER_SHOW_URL'] = $ownerShowUrl;

		//IS_EXPIRED
		if($item['COMPLETED'] === 'Y')
		{
			$item['IS_EXPIRED'] = false;
		}
		else
		{
			$time = isset($item['DEAD_LINE']) ? MakeTimeStamp($item['DEAD_LINE']) : 0;
			$item['IS_EXPIRED'] = $time !== 0 && $time <= (time() + CTimeZone::GetOffset());
		}

		$responsibleID = isset($item['RESPONSIBLE_ID']) ? intval($item['RESPONSIBLE_ID']) : 0;
		$item['RESPONSIBLE_ID'] = $responsibleID;
		$item['RESPONSIBLE_SHOW_URL'] = $responsibleID > 0  ?
			CComponentEngine::MakePathFromTemplate(
				$params['USER_PROFILE_URL_TEMPLATE'],
				array('user_id' => $responsibleID)
			) : '';

		$item['RESPONSIBLE_FORMATTED_NAME'] = $responsibleID > 0 && isset($params['NAME_TEMPLATE'])
			? CUser::FormatName(
				$params['NAME_TEMPLATE'],
				array(
					'LOGIN' => isset($item['RESPONSIBLE_LOGIN']) ? $item['RESPONSIBLE_LOGIN'] : '',
					'NAME' => isset($item['RESPONSIBLE_NAME']) ? $item['RESPONSIBLE_NAME'] : '',
					'LAST_NAME' => isset($item['RESPONSIBLE_LAST_NAME']) ? $item['RESPONSIBLE_LAST_NAME'] : '',
					'SECOND_NAME' => isset($item['RESPONSIBLE_SECOND_NAME']) ? $item['RESPONSIBLE_SECOND_NAME'] : ''
				),
				true, false
			) : '';

		//COMMUNICATIONS
		if($itemID > 0 && isset($options['ENABLE_COMMUNICATIONS'])
			&& $options['ENABLE_COMMUNICATIONS']
			&& !isset($item['COMMUNICATIONS']))
		{
			$item['COMMUNICATIONS'] = CCrmActivity::GetCommunications($itemID);
		}

		$storageTypeID = isset($item['STORAGE_TYPE_ID']) ? intval($item['STORAGE_TYPE_ID']) : CCrmActivityStorageType::Undefined;
		if($storageTypeID === CCrmActivityStorageType::Undefined || !CCrmActivityStorageType::IsDefined($storageTypeID))
		{
			$storageTypeID = CCrmActivity::GetDefaultStorageTypeID();
		}
		$item['STORAGE_TYPE_ID'] = $storageTypeID;

		$item['FILES'] = array();
		$item['DISK_FILES'] = array();
		$item['WEBDAV_ELEMENTS'] = array();

		if(isset($options['ENABLE_FILES']) && $options['ENABLE_FILES'])
		{
			CCrmActivity::PrepareStorageElementIDs($item);
			CCrmActivity::PrepareStorageElementInfo($item);
		}
	}
	public static function PrepareActivityData(&$fields)
	{
		$typeID = isset($fields['TYPE_ID']) ? intval($fields['TYPE_ID']) : CCrmActivityType::Undefined;
		$direction = isset($fields['DIRECTION']) ? intval($fields['DIRECTION']) : CCrmActivityDirection::Undefined;
		$isCompleted = $fields['COMPLETED'] === 'Y';

		$imageFileName = '';
		if($typeID === CCrmActivityType::Call)
		{
			$imageFileName = $direction === CCrmActivityDirection::Incoming ? 'call_in' : 'call_out';
		}
		elseif($typeID === CCrmActivityType::Email)
		{
			$imageFileName = $direction === CCrmActivityDirection::Incoming ? 'email_in' : 'email_out';
		}
		elseif($typeID === CCrmActivityType::Meeting)
		{
			$imageFileName = 'cont';
		}
		elseif($typeID === CCrmActivityType::Task)
		{
			$imageFileName = 'check';
		}

		if($imageFileName !== '' && $isCompleted)
		{
			$imageFileName .= '_disabled';
		}

		$imageUrl = $imageFileName !== ''
			? SITE_DIR.'bitrix/templates/mobile_app/images/crm/'.$imageFileName.'.png?ver=1'
			: '';

		$data = array(
			'ID' => $fields['ID'],
			'TYPE_ID' => $fields['TYPE_ID'],
			'OWNER_ID' => $fields['OWNER_ID'],
			'OWNER_TYPE' => CCrmOwnerType::ResolveName($fields['OWNER_TYPE_ID']),
			'SUBJECT' => isset($fields['SUBJECT']) ? $fields['SUBJECT'] : '',
			'DESCRIPTION' => isset($fields['DESCRIPTION']) ? $fields['DESCRIPTION'] : '',
			'LOCATION' => isset($fields['LOCATION']) ? $fields['LOCATION'] : '',
			'START_TIME' => isset($fields['START_TIME']) ? CCrmComponentHelper::RemoveSeconds(ConvertTimeStamp(MakeTimeStamp($fields['START_TIME']), 'FULL', SITE_ID)) : '',
			'END_TIME' => isset($fields['END_TIME']) ? CCrmComponentHelper::RemoveSeconds(ConvertTimeStamp(MakeTimeStamp($fields['END_TIME']), 'FULL', SITE_ID)) : '',
			'DEAD_LINE' => isset($fields['DEAD_LINE']) ? CCrmComponentHelper::RemoveSeconds(ConvertTimeStamp(MakeTimeStamp($fields['DEAD_LINE']), 'FULL', SITE_ID)) : '',
			'COMPLETED' => isset($fields['COMPLETED']) ? $fields['COMPLETED'] === 'Y' : false,
			'PRIORITY' => isset($fields['PRIORITY']) ? intval($fields['PRIORITY']) : CCrmActivityPriority::None,
			'IS_IMPORTANT' => isset($fields['IS_IMPORTANT']) ? $fields['IS_IMPORTANT'] : false,
			'IS_EXPIRED' => isset($fields['IS_EXPIRED']) ? $fields['IS_EXPIRED'] : false,
			'OWNER_TITLE' => isset($fields['OWNER_TITLE']) ? $fields['OWNER_TITLE'] : '',
			'SHOW_URL' => isset($fields['SHOW_URL']) ? $fields['SHOW_URL'] : '',
			'LIST_IMAGE_URL' => $imageUrl,
			'VIEW_IMAGE_URL' => $imageUrl,
			'STORAGE_TYPE_ID' => $fields['STORAGE_TYPE_ID'],
			'FILES' => isset($fields['FILES']) ? $fields['FILES'] : array(),
			'WEBDAV_ELEMENTS' => isset($fields['WEBDAV_ELEMENTS']) ? $fields['WEBDAV_ELEMENTS'] : array()
		);

		//COMMUNICATIONS
		if(isset($fields['COMMUNICATIONS']))
		{
			$communications = $fields['COMMUNICATIONS'];
			foreach($communications as &$comm)
			{
				CCrmActivity::PrepareCommunicationInfo($comm);
				$comm['ENTITY_TYPE'] = CCrmOwnerType::ResolveName($comm['ENTITY_TYPE_ID']);
				unset($comm['ENTITY_TYPE_ID']);

				if(isset($comm['ENTITY_SETTINGS']))
				{
					// entity settings is useless for client
					unset($comm['ENTITY_SETTINGS']);
				}
			}
			unset($comm);
			$data['COMMUNICATIONS'] = $communications;
		}

		return $data;
	}
	public static function PrepareEventItem(&$item, &$params)
	{
		if(isset($item['EVENT_TEXT_1']))
		{
			$item['EVENT_TEXT_1'] = strip_tags($item['EVENT_TEXT_1'], '<br>');
		}

		if(isset($item['EVENT_TEXT_2']))
		{
			$item['EVENT_TEXT_2'] = strip_tags($item['EVENT_TEXT_2'], '<br>');
		}

		$authorID = isset($item['CREATED_BY_ID']) ? intval($item['CREATED_BY_ID']) : 0;
		$item['CREATED_BY_ID'] = $authorID;
		$item['CREATED_BY_SHOW_URL'] = $authorID > 0  ?
			CComponentEngine::MakePathFromTemplate(
				$params['USER_PROFILE_URL_TEMPLATE'],
				array('user_id' => $authorID)
			) : '';

		$item['CREATED_BY_FORMATTED_NAME'] = $authorID > 0
			? CUser::FormatName(
				$params['NAME_TEMPLATE'],
				array(
					'LOGIN' => isset($item['CREATED_BY_LOGIN']) ? $item['CREATED_BY_LOGIN'] : '',
					'NAME' => isset($item['CREATED_BY_NAME']) ? $item['CREATED_BY_NAME'] : '',
					'LAST_NAME' => isset($item['CREATED_BY_LAST_NAME']) ? $item['CREATED_BY_LAST_NAME'] : '',
					'SECOND_NAME' => isset($item['CREATED_BY_SECOND_NAME']) ? $item['CREATED_BY_SECOND_NAME'] : ''
				),
				true, false
			) : '';
	}
	public static function PrepareEventData(&$fields)
	{
		return array(
			'ID' => $fields['ID'],
			'EVENT_NAME' => isset($fields['EVENT_NAME']) ? $fields['EVENT_NAME'] : '',
			'EVENT_TEXT_1' => isset($fields['EVENT_TEXT_1']) ? $fields['EVENT_TEXT_1'] : '',
			'EVENT_TEXT_2' => isset($fields['EVENT_TEXT_2']) ? $fields['EVENT_TEXT_2'] : '',
			'CREATED_BY_ID' => isset($fields['CREATED_BY_ID']) ? $fields['CREATED_BY_ID'] : '',
			'CREATED_BY_FORMATTED_NAME' => isset($fields['CREATED_BY_FORMATTED_NAME']) ? $fields['CREATED_BY_FORMATTED_NAME'] : '',
			'DATE_CREATE' => isset($fields['DATE_CREATE']) ? ConvertTimeStamp(MakeTimeStamp($fields['DATE_CREATE']), 'SHORT', SITE_ID) : ''
		);
	}
	public static function PrepareInvoiceEventItem(&$item, &$params, &$entity, &$enums)
	{
		$types = isset($enums['EVENT_TYPES']) ? $enums['EVENT_TYPES'] : array();

		$ID = isset($item['ID']) ? intval($item['ID']) : 0;
		$item['ID'] = $ID;

		if(!isset($item['DATE_CREATE']))
		{
			$item['DATE_CREATE'] = '';
		}

		$type = isset($item['TYPE']) ? $item['TYPE'] : '';
		$item['NAME'] = isset($types[$type]) ? $types[$type] : $type;

		if(!isset($item['DATA']))
		{
			$item['DESCRIPTION_HTML'] = '';
		}
		else
		{
			$infoData = $entity->GetRecordDescription($type, $item['DATA']);
			$descr = isset($infoData['INFO']) ? strip_tags($infoData['INFO'], '<br>') : '';
			if(mb_strlen($descr) <= 128)
			{
				$item['DESCRIPTION_HTML'] = $descr;
			}
			else
			{
				$cutWrapperID = "invoice_event_descr_cut_{$ID}";
				$fullWrapperID = "invoice_event_descr_full_{$ID}";

				$item['DESCRIPTION_HTML'] = '<div id="'.$cutWrapperID.'">'
					.mb_substr($descr, 0, 128).'...<a href="#more" onclick="BX(\''.$cutWrapperID.'\').style.display=\'none\'; BX(\''.$fullWrapperID.'\').style.display=\'\'; return false;">'
					.GetMessage('CRM_EVENT_DESC_MORE').'</a></div>'
					.'<div id="'.$fullWrapperID.'" style="display:none;">'.$descr.'</div>';
			}
		}

		$authorID = isset($item['USER_ID']) ? intval($item['USER_ID']) : 0;
		$item['USER_ID'] = $authorID;

		$item['USER_FORMATTED_NAME'] = $authorID > 0
			? CUser::FormatName(
				$params['NAME_TEMPLATE'],
				array(
					'LOGIN' => isset($item['USER_LOGIN']) ? $item['USER_LOGIN'] : '',
					'NAME' => isset($item['USER_NAME']) ? $item['USER_NAME'] : '',
					'LAST_NAME' => isset($item['USER_LAST_NAME']) ? $item['USER_LAST_NAME'] : '',
					'SECOND_NAME' => isset($item['USER_SECOND_NAME']) ? $item['USER_SECOND_NAME'] : ''
				),
				true, false
			) : '';
	}
	public static function PrepareInvoiceEventData(&$fields)
	{
		return array(
			'ID' => $fields['ID'],
			'TYPE' => $fields['TYPE'],
			'NAME' => $fields['NAME'],
			'DESCRIPTION_HTML' => $fields['DESCRIPTION_HTML'],
			'DATE_CREATE' => ConvertTimeStamp(MakeTimeStamp($fields['DATE_CREATE']), 'SHORT', SITE_ID),
			'USER_ID' => $fields['USER_ID'],
			'USER_FORMATTED_NAME' => $fields['USER_FORMATTED_NAME']
		);
	}

	public static function getProductSortFields()
	{
		$sortFields = array(
			array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_PRODUCT_ID'), 'sort' => 'id'),
			array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_PRODUCT_NAME'), 'sort' => 'name'),
			array('id' => 'SORT', 'name' => GetMessage('CRM_COLUMN_PRODUCT_SORT'), 'sort' => 'sort'),
			array('id' => 'ACTIVE', 'name' => GetMessage('CRM_COLUMN_PRODUCT_ACTIVE'), 'sort' => 'active'),
			array('id' => 'DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_PRODUCT_DESCRIPTION'), 'sort' => 'description'),
		);

		return $sortFields;
	}

	public static function getProductFields()
	{
		$fields = array(
			'ID' => array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_PRODUCT_ID')),
		//	'NAME' => array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_PRODUCT_NAME')),
			'FORMATTED_PRICE' => array('id' => 'FORMATTED_PRICE', 'name' => GetMessage('CRM_COLUMN_PRODUCT_PRICE')),
			'MEASURE' => array('id' => 'MEASURE', 'name' => GetMessage('CRM_COLUMN_PRODUCT_MEASURE')),
			'SECTION_ID' => array('id' => 'SECTION_ID', 'name' => GetMessage('CRM_COLUMN_PRODUCT_SECTION')),
			'SORT' => array('id' => 'SORT', 'name' => GetMessage('CRM_COLUMN_PRODUCT_SORT')),
			'ACTIVE' => array('id' => 'ACTIVE', 'name' => GetMessage('CRM_COLUMN_PRODUCT_ACTIVE')),
			'DESCRIPTION' => array('id' => 'DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_PRODUCT_DESCRIPTION')),
			'PREVIEW_PICTURE' => array('id' => 'PREVIEW_PICTURE', 'name' => GetMessage('CRM_COLUMN_PRODUCT_PREVIEW_PICTURE')),
			'DETAIL_PICTURE' => array('id' => 'DETAIL_PICTURE', 'name' => GetMessage('CRM_COLUMN_PRODUCT_DETAIL_PICTURE')),
		);
		if ($bVatMode)
		{
			$fields['VAT_ID'] = array('id' => 'VAT_ID', 'name' => GetMessage('CRM_COLUMN_VAT_ID'));
			$fields['VAT_INCLUDED'] = array('id' => 'VAT_INCLUDED', 'name' => GetMessage('CRM_COLUMN_VAT_INCLUDED'));
		}

		return $fields;
	}

	public static function getProductFilterFields()
	{
		$arSections = array();
		$arSections[""] = GetMessage("M_CRM_NOT_SELECTED");
		if (CModule::IncludeModule('iblock'))
		{
			$catalogID = CCrmCatalog::EnsureDefaultExists();
			$dbSections = CIBlockSection::GetList(
				array('left_margin' => 'asc'),
				array(
					'IBLOCK_ID' => $catalogID,
					//'SECTION_ID' => $activeSectionID,
					/*'GLOBAL_ACTIVE' => 'Y',*/
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				array('ID', 'NAME'),
				false
			);

			while ($section = $dbSections->GetNext())
			{
				$arSections[$section["ID"]] = $section["NAME"];
			}
		}

		$filterFields = array(
			array(
				"type" => "text",
				"id" => "ID",
				"name" => GetMessage('CRM_COLUMN_PRODUCT_ID'),
				"value" => ""
			),
			array(
				"type" => "text",
				"id" => "NAME",
				"name" => GetMessage('CRM_COLUMN_PRODUCT_NAME'),
				"value" => ""
			),
			array(
				"type" => "select",
				"id" => "SECTION_ID",
				"name" => GetMessage('CRM_COLUMN_PRODUCT_SECTION'),
				"items" => $arSections,
				"value" => ""
			),
			array(
				"type" => "checkbox",
				"id" => "ACTIVE",
				"name" => "",//GetMessage('CRM_COLUMN_PRODUCT_ACTIVE'),
				"value" => "",
				"items" => array(
					"Y" => GetMessage('CRM_COLUMN_PRODUCT_ACTIVE')
				),
			),
			array(
				"type" => "text",
				"id" => "DESCRIPTION",
				"name" => GetMessage('CRM_COLUMN_PRODUCT_DESCRIPTION'),
				"value" => ""
			),
		);

		return $filterFields;
	}

	public static function PrepareProductItem(&$item, &$params)
	{
		$sectionID = $item['~SECTION_ID'] = isset($item['SECTION_ID']) ? intval($item['SECTION_ID']) : 0;
		if($sectionID <= 0)
		{
			$item['~SECTION_NAME'] = $item['SECTION_NAME'] = '';
		}
		else
		{
			$sections = isset($params['SECTIONS']) ? $params['SECTIONS'] : array();
			$item['~SECTION_NAME'] = isset($sections[$sectionID]) ? $sections[$sectionID]['NAME'] : '';
			$item['SECTION_NAME'] = htmlspecialcharsbx($item['~SECTION_NAME']);
		}

		$price = $item['~PRICE'] = isset($item['~PRICE']) ? doubleval($item['~PRICE']) : 0.0;

		$srcCurrencyID = $item['~CURRENCY_ID'] = isset($item['~CURRENCY_ID']) ? $item['~CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
		$dstCurrencyID = isset($params['CURRENCY_ID']) ? $params['CURRENCY_ID'] : '';
		if($dstCurrencyID === '')
		{
			$dstCurrencyID = $srcCurrencyID;
		}

		if($dstCurrencyID !== $srcCurrencyID)
		{
			$item['~CURRENCY_ID'] = $dstCurrencyID;
			$item['CURRENCY_ID'] = htmlspecialcharsbx($dstCurrencyID);

			$price = CCrmCurrency::ConvertMoney($price, $srcCurrencyID, $dstCurrencyID);
		}
		$item['FORMATTED_PRICE'] = CCrmCurrency::MoneyToString($price, $dstCurrencyID);

		$photoID = isset($item['PREVIEW_PICTURE'])
			? intval($item['PREVIEW_PICTURE'])
			: (isset($item['DETAIL_PICTURE']) ? intval($item['DETAIL_PICTURE']) : 0);
		if($photoID > 0)
		{
			$listImageInfo = CFile::ResizeImageGet(
				$photoID, array('width' => 33, 'height' => 33), BX_RESIZE_IMAGE_PROPORTIONAL );
			$item['PHOTO'] = $listImageInfo["src"];
		}

		if (isset($item["ACTIVE"]))
		{
			$item["ACTIVE"] = $item["ACTIVE"] == "Y" ? GetMessage("CRM_TEXT_YES") : GetMessage("CRM_TEXT_NO");
		}

		if (isset($params["MEASURES"]) && is_array($params["MEASURES"]))
		{
			if (isset($item["MEASURE"]))
			{
				$item["MEASURE_NAME"] = $params["MEASURES"][$item["MEASURE"]]["SYMBOL"];
				$item["MEASURE"] = $params["MEASURES"][$item["MEASURE"]]["SYMBOL"];
				$item["MEASURE_CODE"] = $params["MEASURES"][$item["MEASURE"]]["CODE"];
				$item["MEASURE_ID"] = $params["MEASURES"][$item["MEASURE"]]["ID"];
			}
		}
	}
	public static function PrepareProductData(&$fields)
	{
		return array(
			'ID' => $fields['~ID'],
			'NAME' => $fields['~NAME'],
			'PRICE' => $fields['~PRICE'],
			'CURRENCY_ID' => $fields['~CURRENCY_ID'],
			'SECTION_ID' => $fields['~SECTION_ID'],
			'SECTION_NAME' => $fields['SECTION_NAME'],
			'FORMATTED_PRICE' => $fields['FORMATTED_PRICE']
		);
	}
	public static function PrepareProductSectionItem(&$item, &$params)
	{
		$item['PRODUCT_SECTION_URL'] = isset($params['PRODUCT_SECTION_URL_TEMPLATE'])
			? CComponentEngine::MakePathFromTemplate(
				$params['PRODUCT_SECTION_URL_TEMPLATE'],
				array('section_id' => $item['~ID']))
			: '';

		$item['SECTION_URL'] = isset($params['SECTION_URL_TEMPLATE'])
			? CComponentEngine::MakePathFromTemplate(
				$params['SECTION_URL_TEMPLATE'],
				array('section_id' => $item['~ID']))
			: '';
	}
	public static function PrepareProductSectionData(&$fields)
	{
		return array(
			'ID' => $fields['~ID'],
			'NAME' => $fields['~NAME'],
			'PRODUCT_SECTION_URL' => $fields['PRODUCT_SECTION_URL']
		);
	}

	public static function PrepareMultiFieldsData($clientId, $entityId)
	{
		//multi fields
		$multi = array();
		$multiFields = CCrmMobileHelper::getFieldMultiInfo();

		$dbFields = CCrmFieldMulti::GetList(
			array('TYPE_ID' => 'desc'),
			array(
				'ENTITY_ID' => $entityId,
				'ELEMENT_ID' => $clientId
			)
		);

		while ($arMulti = $dbFields->Fetch())
		{
			if (!in_array($arMulti["TYPE_ID"], array(CCrmFieldMulti::PHONE, CCrmFieldMulti::EMAIL)))
				continue;

			$multi[] = array(
				"type" => $arMulti["TYPE_ID"],
				"name" => $multiFields[$arMulti["TYPE_ID"]][$arMulti["VALUE_TYPE"]]["SHORT"],
				"value" => $arMulti['VALUE']
			);
		}

		return $multi;
	}

	public static function RenderProgressBar($params)
	{
		$entityTypeID = isset($params['ENTITY_TYPE_ID']) ? intval($params['ENTITY_TYPE_ID']) : 0;
		//$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);

		$infos = isset($params['INFOS']) ? $params['INFOS'] : null;
		if(!is_array($infos) || empty($infos))
		{
			if($entityTypeID === CCrmOwnerType::Lead)
			{
				if(!self::$LEAD_STATUSES)
				{
					self::$LEAD_STATUSES = CCrmStatus::GetStatus('STATUS');
				}
				$infos = self::$LEAD_STATUSES;
			}
			elseif($entityTypeID === CCrmOwnerType::Deal)
			{
				if(!self::$DEAL_STAGES)
				{
					self::$DEAL_STAGES = CCrmStatus::GetStatus('DEAL_STAGE');
				}
				$infos = self::$DEAL_STAGES;
			}
			elseif($entityTypeID === CCrmOwnerType::Invoice)
			{
				if(!self::$INVOICE_STATUSES)
				{
					self::$INVOICE_STATUSES = CCrmStatus::GetStatus('INVOICE_STATUS');
				}
				$infos = self::$INVOICE_STATUSES;
			}
		}

		if(!is_array($infos) || empty($infos))
		{
			return;
		}

		$currentInfo = null;
		$currentID = isset($params['CURRENT_ID']) ? $params['CURRENT_ID'] : '';
		if($currentID !== '' && isset($infos[$currentID]))
		{
			$currentInfo = $infos[$currentID];
		}
		$currentSort = is_array($currentInfo) && isset($currentInfo['SORT']) ? intval($currentInfo['SORT']) : -1;

		$finalID = isset($params['FINAL_ID']) ? $params['FINAL_ID'] : '';
		if($finalID === '')
		{
			if($entityTypeID === CCrmOwnerType::Lead)
			{
				$finalID = 'CONVERTED';
			}
			elseif($entityTypeID === CCrmOwnerType::Deal)
			{
				//TODO: Resolve category ID
				$finalID = DealCategory::prepareStageID(0, 'WON');
			}
			elseif($entityTypeID === CCrmOwnerType::Invoice)
			{
				$finalID = 'P';
			}
		}

		$finalInfo = null;
		if($finalID !== '' && isset($infos[$finalID]))
		{
			$finalInfo = $infos[$finalID];
		}
		$finalSort = is_array($finalInfo) && isset($finalInfo['SORT']) ? intval($finalInfo['SORT']) : -1;

		$layout = isset($params['LAYOUT'])? mb_strtolower($params['LAYOUT']) : 'small';

		$wrapperClass = "crm-list-stage-bar-{$layout}";
		if($currentSort === $finalSort)
		{
			$wrapperClass .= ' crm-list-stage-end-good';
		}
		elseif($currentSort > $finalSort)
		{
			$wrapperClass .= ' crm-list-stage-end-bad';
		}

		//$prefix = isset($params['PREFIX']) ? $params['PREFIX'] : '';
		//$entityID = isset($params['ENTITY_ID']) ? intval($params['ENTITY_ID']) : 0;

		//$controlID = $entityTypeName !== '' && $entityID > 0
		//	? "{$prefix}{$entityTypeName}_{$entityID}" : uniqid($prefix);
		$wrapperID = isset($params['WRAPPER_ID']) ? $params['WRAPPER_ID'] : '';
		$tableClass = "crm-list-stage-bar-table-{$layout}";

		echo '<div class="', $wrapperClass,'" style="width:89%;"',
		($wrapperID !== '' ? ' id="'.htmlspecialcharsbx($wrapperID).'"' : ''),
		'><table class="', $tableClass, '"><tbody><tr>';

		foreach($infos as &$info)
		{
			$ID = isset($info['STATUS_ID']) ? $info['STATUS_ID'] : '';
			$sort = isset($info['SORT']) ? intval($info['SORT']) : 0;
			if($sort > $finalSort)
			{
				break;
			}

			echo '<td class="crm-list-stage-bar-part',
			($sort <= $currentSort ? ' crm-list-stage-passed' : ''), '">',
				'<div class="crm-list-stage-bar-block" data-progress-step-id="'.htmlspecialcharsbx(mb_strtolower($ID)).'"><div class="crm-list-stage-bar-btn"></div></div>',
			'<input class="crm-list-stage-bar-block-sort" type="hidden" value="', $sort ,'" />',
			'</td>';
		}
		unset($info);

		echo '</tr></tbody></table></div>';
	}
	public static function PrepareCalltoUrl($value)
	{
		return 'tel:'.$value;
	}
	public static function PrepareMailtoUrl($value)
	{
		return 'mailto:'.$value;
	}
	public static function PrepareCalltoParams($params)
	{
		$result = array(
			'URL' => '',
			'SCRIPT' => ''
		);

		$multiFields = isset($params['FM']) ? $params['FM'] : array();
		$c = count($multiFields['PHONE']);
		if($c === 0)
		{
			return $result;
		}


		$commListUrlTemplate = isset($params['COMMUNICATION_LIST_URL_TEMPLATE']) ? $params['COMMUNICATION_LIST_URL_TEMPLATE'] : '';
		$entityTypeID = isset($params['ENTITY_TYPE_ID']) ? intval($params['ENTITY_TYPE_ID']) : 0;
		$entityID = isset($params['ENTITY_ID']) ? intval($params['ENTITY_ID']) : 0;

		if($c === 1)
		{
			$result['URL'] = self::PrepareCalltoUrl($multiFields['PHONE'][0]['VALUE']);
		}
		elseif($commListUrlTemplate !== '' && $entityTypeID > 0 && $entityID > 0)
		{
			$url = CComponentEngine::MakePathFromTemplate(
				$commListUrlTemplate,
				array(
					'entity_type_id' => $entityTypeID,
					'entity_id' => $entityID,
					'type_id' => 'PHONE'
				)
			);

			$result['SCRIPT'] = 'BX.CrmMobileContext.redirect({ url: \''.CUtil::JSEscape($url).'\', pageid:\'crm_phone_list_'.$entityTypeID.'_'.$entityID.'\' }); return false;';
		}

		return $result;
	}
	public static function PrepareMailtoParams($params)
	{
		$result = array(
			'URL' => '',
			'SCRIPT' => ''
		);

		$multiFields = isset($params['FM']) ? $params['FM'] : array();
		$c = count($multiFields['EMAIL']);
		if($c === 0)
		{
			return $result;
		}


		$commListUrlTemplate = isset($params['COMMUNICATION_LIST_URL_TEMPLATE']) ? $params['COMMUNICATION_LIST_URL_TEMPLATE'] : '';
		$entityTypeID = isset($params['ENTITY_TYPE_ID']) ? intval($params['ENTITY_TYPE_ID']) : 0;
		$entityID = isset($params['ENTITY_ID']) ? intval($params['ENTITY_ID']) : 0;

		if($c === 1)
		{
			$result['URL'] = self::PrepareMailtoUrl($multiFields['EMAIL'][0]['VALUE']);
		}
		elseif($commListUrlTemplate !== '' && $entityTypeID > 0 && $entityID > 0)
		{
			$url = CComponentEngine::MakePathFromTemplate(
				$commListUrlTemplate,
				array(
					'entity_type_id' => $entityTypeID,
					'entity_id' => $entityID,
					'type_id' => 'EMAIL'
				)
			);

			$result['SCRIPT'] = 'BX.CrmMobileContext.redirect({ url: \''.CUtil::JSEscape($url).'\' }); return false;';
		}

		return $result;
	}
	public static function PrepareCut($src, &$text, &$cut)
	{
		$text = '';
		$cut = '';
		if($src === '' || preg_match('/^\s*(\s*<br[^>]*>\s*)+\s*$/i', $src) === 1)
		{
			return false;
		}

		$text = $src;
		if(mb_strlen($text) > 128)
		{
			$cut = mb_substr($text, 128);
			$text = mb_substr($text, 0, 128);
		}

		return true;
	}
	public static function GetContactViewImageStub()
	{
		return SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_contact_big.png?ver=1';
	}
	public static function GetCompanyViewImageStub()
	{
		return SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_company_big.png?ver=1';
	}
	public static function GetLeadViewImageStub()
	{
		return SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_lead_big.png?ver=1';
	}
	public static function GetLeadListImageStub()
	{
		return SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_lead_small.png?ver=1';
	}
	public static function GetUploadedFileIDs($ownerTypeID, $ownerID)
	{
		if(!CCrmOwnerType::IsDefined($ownerTypeID))
		{
			return array();
		}

		$key = 'CRM_MBL_'.CCrmOwnerType::ResolveName($ownerTypeID).'_'.$ownerID.'_FILES';
		return isset($_SESSION[$key]) && is_array($_SESSION[$key]) ? $_SESSION[$key] : array();
	}
	public static function TryUploadFile(&$result, $options = array())
	{
		//Options initialization -->
		$ownerTypeID = isset($options['OWNER_TYPE_ID']) ? intval($options['OWNER_TYPE_ID']) : CCrmOwnerType::Undefined;
		if($ownerTypeID !== CCrmOwnerType::Undefined && !CCrmOwnerType::IsDefined($ownerTypeID))
		{
			$ownerTypeID = CCrmOwnerType::Undefined;
		}
		$ownerID = isset($options['OWNER_ID']) ? max(intval($options['OWNER_ID']), 0) : 0;
		$scope = isset($options['SCOPE'])? mb_strtoupper($options['SCOPE']) : '';
		if(!in_array($scope, array('I', 'A', 'F'), true))
		{
			$scope = '';
		}
		$extensions = isset($options['EXTENSIONS']) && is_array($options['EXTENSIONS'])
			? $options['EXTENSIONS'] : array();

		$maxFileSize = isset($options['MAX_FILE_SIZE']) ? max(intval($options['MAX_FILE_SIZE']), 0) : 0;
		//<-- Options initialization
		if(!is_array($result))
		{
			$result = array();
		}

		$file = is_array($_FILES) && isset($_FILES['file']) ? $_FILES['file'] : null;
		if(!is_array($file))
		{
			$result['ERROR_MESSAGE'] = 'No files';
			return false;
		}
		$file['MODULE_ID'] = 'crm';

		if ($scope === 'I')
		{
			$error = CFile::CheckImageFile($file, $maxFileSize, 0, 0);
		}
		elseif ($scope === 'F')
		{
			$error = CFile::CheckFile($file, $maxFileSize, false, implode(',', $extensions));
		}
		else
		{
			$error = CFile::CheckFile($file, $maxFileSize, false, false);
		}
		$isValid = !(is_string($error) && $error !== '');

		if(!$isValid)
		{
			$result['ERROR_MESSAGE'] = $error;
			return false;
		}

		$fileID = CFile::SaveFile($file, 'crm');
		if(!is_int($fileID) || $fileID <= 0)
		{
			$result['ERROR_MESSAGE'] = 'General error.';
			return false;
		}

		if($ownerTypeID != CCrmOwnerType::Undefined)
		{
			$key = 'CRM_MBL_'.CCrmOwnerType::ResolveName($ownerTypeID).'_'.$ownerID.'_FILES';
			if (!isset($_SESSION[$key]))
			{
				$_SESSION[$key] = array();
			}

			$_SESSION[$key][] = $fileID;
		}
		$result['FILE_ID'] = $fileID;
		return true;
	}
	public static function SaveRecentlyUsedLocation($locationID, $userID = 0)
	{
		$locationID = intval($locationID);
		if($locationID <= 0)
		{
			return false;
		}

		$userID = intval($userID);
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$key = strval($locationID);

		$s = CUserOptions::GetOption('m_crm_invoice', 'locations', '', $userID);
		$ary = $s !== '' ? explode(',', $s) : array();
		$qty = count($ary);
		if($qty > 0)
		{
			if(in_array($key, $ary, true))
			{
				return true;
			}

			if($qty >= 10)
			{
				array_shift($ary);
			}
		}
		$ary[] = $key;
		CUserOptions::SetOption('m_crm_invoice', 'locations', implode(',', $ary));
		return true;
	}
	public static function GetRecentlyUsedLocations($userID = 0)
	{
		$userID = intval($userID);
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$s = CUserOptions::GetOption('m_crm_invoice', 'locations', '', $userID);
		$ary = $s !== '' ? explode(',', $s) : array();
		$qty = count($ary);
		for($i = 0; $i < $qty; $i++)
		{
			$ary[$i] = intval($ary[$i]);
		}
		return $ary;
	}

	public static function prepareAudioField(&$item, $ownerType)
	{
		$activityFilter = array(
			'TYPE_ID' => CCrmActivityType::Call,
			'BINDINGS' => array(array('OWNER_TYPE_ID' => $ownerType, 'OWNER_ID' => $item["ID"])),
			'CHECK_PERMISSIONS' => 'N'
		);
		$result = CCrmActivity::GetList(array("ID" => "desc"), $activityFilter);
		$numCalls = $result->SelectedRowsCount();
		if($call = $result->Fetch())
		{
			if ($call['STORAGE_TYPE_ID'] != Bitrix\Crm\Integration\StorageType::Disk)
				return;

			CCrmActivity::PrepareStorageElementIDs($call);
			CCrmActivity::PrepareStorageElementInfo($call);

			foreach($call['DISK_FILES'] as $fileInfo)
			{
				$item["AUDIO_CALL"] = "<div class='mobile-grid-field' onclick=\"BXMobileApp.UI.Document.open({'url':'".$fileInfo["VIEW_URL"]."', 'filename':'".$fileInfo["NAME"]."'});\">
					<span class='mobile-grid-field-call'>
						<img src='/mobile/crm/images/icon-wave2x.png' srcset='/mobile/crm/images/icon-wave2x.png 2x'>
						<span>".FormatDate('j F Y H:i', MakeTimeStamp($call["START_TIME"]))."</span>
						<img src='/mobile/crm/images/icon-play2x.png' srcset='/mobile/crm/images/icon-play2x.png 2x'>
					</span>
					<span class='mobile-grid-field-textarea-title'>".GetMessage("M_CRM_LIST_AUDIO").($numCalls > 1 ? " ".GetMessage("M_CRM_LIST_AUDIO_ALL", array("#NUM#" => $numCalls)) : "")."</span>
				</div>";
			}
		}
	}

	public static function prepareDateSeparator($dateSortField, &$arItems)
	{
		$arDateOrderItems = array();
		$currentDate = "";

		foreach($arItems as $item)
		{
			if (time() - MakeTimeStamp($item["FIELDS"][$dateSortField]) < 24*60*60)
			{
				$tmpDate = GetMessage("M_CRM_LIST_TODAY");
			}
			elseif (time() - MakeTimeStamp($item["FIELDS"][$dateSortField]) < 24*60*60*2)
			{
				$tmpDate = GetMessage("M_CRM_LIST_YESTERDAY");
			}
			else
			{
				$tmpDate = $item["FIELDS"][$dateSortField];
			}

			if (empty($currentDate) || $currentDate != $tmpDate)
			{
				$currentDate = $tmpDate;
				$arDateOrderItems[] = array(
					"TYPE" => "HR",
					"VALUE" => '<p>'.GetMessage("M_CRM_LIST_".$dateSortField).'<span> '.$currentDate.'</span></p>'
				);
			}
			$arDateOrderItems[] = $item;
		}

		$arItems = $arDateOrderItems;
	}

	protected function GetUserFields($entity_id, $value_id = 0, $LANG = false, $user_id = false)
	{
		global $USER_FIELD_MANAGER;

		$result = $USER_FIELD_MANAGER->GetUserFields($entity_id, $value_id, $LANG, $user_id);

		// remove invoice reserved fields
		if ($entity_id === CCrmInvoice::GetUserFieldEntityID())
			foreach (CCrmInvoice::GetUserFieldsReserved() as $ufId)
				if (isset($result[$ufId]))
					unset($result[$ufId]);

		return $result;
	}

	/**
	 * @param array $fields
	 * @param string $entityId
	 * @param int|sting $id
	 * @param bool $varsFromForm
	 * @param string|null $scopePrefix
	 * @param int|null $userId
	 * @throws LoaderException
	 */
	public function prepareUserFields(&$fields, $entityId, $id, $varsFromForm = false, $scopePrefix = null, ?int $userId = null)
	{
		try
		{
			$userFields = $this->GetUserFields($entityId, $id, LANGUAGE_ID);
		} catch(\Bitrix\Main\ObjectException $e)
		{
			$userFields = [];
		}

		if ($userId)
		{
			$userFields = VisibilityManager::getVisibleUserFields($userFields, $userId);
		}

		foreach($userFields as $FIELD_NAME => &$userField)
		{
			if(!isset($userField['ENTITY_VALUE_ID']))
			{
				$userField['ENTITY_VALUE_ID'] = (int)$id;
			}

			$userTypeId = $userField['USER_TYPE']['USER_TYPE_ID'];

			if($varsFromForm)
			{
				$value = $_REQUEST[$userField['FIELD_NAME']];
			}
			else
			{
				$value = ($userField['VALUE'] ?? '');
			}

			if($userTypeId === 'string' || $userTypeId === 'double')
			{
				$fieldType = 'text';
			}
			elseif($userTypeId === 'boolean')
			{
				$fieldType = 'checkbox';
				$value = ((int)$value > 0 ? 'Y' : 'N');
			}
			elseif($userTypeId === 'datetime')
			{
				$fieldType = 'date';
			}
			else
			{
				$fieldType = $userTypeId;
			}

			$field = [
				'id' => $FIELD_NAME,
				'params' => [],
				'type' => $fieldType,
				'value' => $value,
				'required' => ($userField['MANDATORY'] === 'Y'),
				'userField' => $userField
			];

			if($fieldType !== 'checkbox')
			{
				$field['name'] = (
				!empty($userField['EDIT_FORM_LABEL'])
					? $userField['EDIT_FORM_LABEL']
					: $userField['FIELD_NAME']
				);
			}
			else
			{
				$field['items'] = [
					'Y' => (
					!empty($userField['EDIT_FORM_LABEL'])
						? $userField['EDIT_FORM_LABEL']
						: $userField['FIELD_NAME']
					)
				];
			}

			$fields[] = $field;
		}
		unset($userField);

		$fields = $this->getVisibleUserFields($fields, $scopePrefix);
	}

	/**
	 * Get settings from b_user_options with config of visibilities uf fields in crm entities
	 * and return array of uf fields according to this config
	 * @param array $userFields
	 * @param string|null $scopePrefix
	 * @return array
	 * @throws LoaderException
	 */
	private function getVisibleUserFields(array $userFields, ?string $scopePrefix): array
	{
		$visibleUserFields = [];

		if(
			!empty($scopePrefix)
			&&
			Loader::includeModule('ui')
			&&
			count($userFields)
		)
		{
			$configScope = CUserOptions::GetOption(
				self::USER_OPTION_CATEGORY,
				"{$scopePrefix}_scope",
				EntityEditorConfigScope::UNDEFINED
			);

			$config = [];

			if(isset($configScope['scope']) && $configScope['scope'] === EntityEditorConfigScope::UNDEFINED)
			{
				$config = CUserOptions::GetOption(
					self::USER_OPTION_CATEGORY,
					$scopePrefix,
					null
				);

				if(!is_array($config) || empty($config))
				{
					$config = CUserOptions::GetOption(
						self::USER_OPTION_CATEGORY,
						"{$scopePrefix}_common",
						null,
						0
					);
				}
			}
			elseif(isset($configScope['scope']) && $configScope['scope'] === EntityEditorConfigScope::COMMON)
			{
				$config = CUserOptions::GetOption(
					self::USER_OPTION_CATEGORY,
					"{$scopePrefix}_common",
					null,
					0
				);
			}
			elseif(isset($configScope['scope']) && $configScope['scope'] === EntityEditorConfigScope::CUSTOM)
			{
				$config = Scope::getInstance()->getScopeById($configScope['userScopeId']);
			}

			if (!is_array($config) || empty($config))
			{
				$config = CUserOptions::GetOption(
					self::USER_OPTION_CATEGORY,
					$scopePrefix,
					null
				);
			}

			if($config && count($config))
			{
				$this->prepareVisibleUserFieldsSection($config, $userFields, $visibleUserFields);
			}
		}

		if (!count($visibleUserFields))
		{
			return [[
				'id' => 'main',
				'fields' => $userFields
			]];
		}

		return $visibleUserFields;
	}

	private function prepareVisibleUserFieldsSection(array $config, array $userFields, array &$visibleUserFields): array
	{
		$fieldNameMap = [
			'CONTACT_ID' => ['CLIENT', 'CONTACT'],
			'COMPANY_ID' => ['CLIENT', 'COMPANY'],
			'PRODUCT_ROWS' => ['PRODUCT_ROW_SUMMARY'],
			'OPPORTUNITY' => ['OPPORTUNITY_WITH_CURRENCY'],
			'CURRENCY_ID' => ['OPPORTUNITY_WITH_CURRENCY'],
			'STATUS_DESCRIPTION' => ['STATUS_ID'],
			'CONTACT_NAME_PHOTO' => ['PHOTO'],
			'REVENUE' => ['REVENUE_WITH_CURRENCY'],
			'ADDRESS_LEGAL' => ['ADDRESS'],
		];
		foreach ($config as $configCategory)
		{
			$categoryFields = [];
			if ($configCategory['type'] === 'column' && isset($configCategory['elements']))
			{
				$visibleUserFields = array_merge(
					$categoryFields,
					$this->prepareVisibleUserFieldsSection($configCategory['elements'], $userFields, $visibleUserFields)
				);
			}
			elseif ($configCategory['type'] === 'section' && isset($configCategory['elements']))
			{
				foreach($configCategory['elements'] as $element)
				{
					array_walk(
						$userFields,
						static function($item, $key) use (&$categoryFields, $element, $fieldNameMap)
						{
							if(
								$item['id'] === $element['name']
								|| (
									isset($fieldNameMap[$item['id']])
									&& is_array($fieldNameMap[$item['id']])
									&& in_array($element['name'], $fieldNameMap[$item['id']], true)
								)
							)
							{
								$categoryFields[] = $item;
							}
						});
				}
				if(count($categoryFields))
				{
					$visibleUserFields[] = [
						'id' => $configCategory['name'],
						'fields' => $categoryFields,
						'title' => $configCategory['title'],
					];
				}
				unset($categoryFields);
			}
		}

		return $visibleUserFields;
	}

	public static function PrepareAddressFormFields($arFields)
	{
		if (!is_array($arFields) || empty($arFields))
			return;

		$addressLabels = Bitrix\Crm\EntityAddress::getLabels();
		$html = "";

		foreach($arFields as $itemKey => $item)
		{
			$itemValue = isset($item['VALUE']) ? $item['VALUE'] : '';
			$itemName = isset($item['NAME']) ? $item['NAME'] : $itemKey;
			$itemLocality = isset($item['LOCALITY']) ? $item['LOCALITY'] : null;

			$html.= '<div class="mobile-grid-field-contact-info">';
			$html.= '<div class="mobile-grid-field-label"><span class="mobile-grid-field-contact-info-title">'.$addressLabels[$itemKey].'</span></div>';
			$html.= (isset($item['IS_MULTILINE']) && $item['IS_MULTILINE']) ?  '<div class="mobile-grid-field-textarea">' : '<div class="mobile-grid-field-text">';

			if(is_array($itemLocality))
			{
				$searchInputID = "{$arParams['FORM_ID']}_{$itemName}";
				$dataInputID = "{$arParams['FORM_ID']}_{$itemLocality['NAME']}";

				$html.='
				<input id="'.$searchInputID.'" name="'.$itemName.'" type="text" value="'.htmlspecialcharsEx($itemValue).'"/>
				<input type="hidden" id="'.$dataInputID .'" name="'.$itemLocality['NAME'].'" value="'.htmlspecialcharsbx($itemLocality['VALUE']) .'"/>
				';

			}
			else
			{
				if (isset($item['IS_MULTILINE']) && $item['IS_MULTILINE']):
					$html.='<textarea name="'.htmlspecialcharsEx($itemName).'">'.htmlspecialcharsbx($itemValue).'</textarea>';
				else:
					$html.='<input name="'.htmlspecialcharsEx($itemName) .'" type="text" value="'.htmlspecialcharsEx($itemValue).'" />';
				endif;
			}

			$html.= '</div>';
			$html.= '</div>';
		}

		return $html;
	}
}
