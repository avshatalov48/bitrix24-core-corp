<?php

namespace Bitrix\Crm\Integration\BizProc\Document;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Crm\Order\Permissions;

use Bitrix\Crm;

if (!Main\Loader::includeModule('bizproc'))
{
	return;
}

class Order extends \CCrmDocument implements \IBPWorkflowDocument
{
	public static function GetDocumentFields($documentType)
	{
		$arDocumentID = self::GetDocumentInfo($documentType.'_0');
		if (empty($arDocumentID))
			throw new \CBPArgumentNullException('documentId');

		$arResult = self::getEntityFields($arDocumentID['TYPE']);

		return $arResult;
	}

	public static function getEntityFields($entityType)
	{
		$fields = [
			'ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_ID'),
				'Type' => 'int',
			),
			'ACCOUNT_NUMBER' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_ACCOUNT_NUMBER'),
				'Type' => 'string',
			),
			'SHOP_TITLE' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_SHOP_TITLE'),
				'Type' => 'string',
			),
			'SHOP_PUBLIC_URL' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_SHOP_PUBLIC_URL'),
				'Type' => 'string',
			),
			'DATE_INSERT' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_DATE_INSERT'),
				'Type' => 'date',
			),
			'DATE_UPDATE' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_DATE_UPDATE'),
				'Type' => 'date',
			),
			'PERSON_TYPE_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_PERSON_TYPE_ID'),
				'Type' => 'string',
			),
			'USER_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_USER_ID'),
				'Type' => 'user',
			),
			'USER_ID_PRINTABLE' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_USER_ID_PRINTABLE'),
				'Type' => 'string',
			),
			'PAYED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_PAYED'),
				'Type' => 'bool',
			),
			'DATE_PAYED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_DATE_PAYED'),
				'Type' => 'date',
			),
			'EMP_PAYED_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_EMP_PAYED_ID'),
				'Type' => 'user',
			),
			'DEDUCTED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_DEDUCTED'),
				'Type' => 'bool',
			),
			'DATE_DEDUCTED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_DATE_DEDUCTED'),
				'Type' => 'date',
			),
			'EMP_DEDUCTED_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_EMP_DEDUCTED_ID'),
				'Type' => 'user',
			),
			'REASON_UNDO_DEDUCTED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_REASON_UNDO_DEDUCTED'),
				'Type' => 'string',
			),
			'STATUS_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_STATUS_ID'),
				'Type' => 'select',
				'Options' => self::getStatusOptions()
			),
			'DATE_STATUS' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_DATE_STATUS'),
				'Type' => 'date',
			),
			'EMP_STATUS_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_EMP_STATUS_ID'),
				'Type' => 'user',
			),
			'PRICE_DELIVERY' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_PRICE_DELIVERY'),
				'Type' => 'double',
			),
			'ALLOW_DELIVERY' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_ALLOW_DELIVERY'),
				'Type' => 'bool',
			),
			'DATE_ALLOW_DELIVERY' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_DATE_ALLOW_DELIVERY'),
				'Type' => 'date',
			),
			'EMP_ALLOW_DELIVERY_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_EMP_ALLOW_DELIVERY_ID'),
				'Type' => 'user',
			),
			'RESERVED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESERVED'),
				'Type' => 'bool',
			),
			'PRICE' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_PRICE'),
				'Type' => 'double',
			),
			'CURRENCY' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_CURRENCY'),
				'Type' => 'select',
				'Options' => \CCrmCurrencyHelper::PrepareListItems(),
			),
			'PRICE_FORMATTED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_PRICE_FORMATTED'),
				'Type' => 'string',
			),
			'TAX_VALUE' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_TAX_VALUE'),
				'Type' => 'double',
			),
			'SUM_PAID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_SUM_PAID'),
				'Type' => 'double',
			),
			'USER_DESCRIPTION' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_USER_DESCRIPTION'),
				'Type' => 'string',
			),
			'ADDITIONAL_INFO' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_ADDITIONAL_INFO'),
				'Type' => 'string',
				'Editable' => true,
			),
			'COMMENTS' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_COMMENTS'),
				'Type' => 'string',
				'Editable' => true,
			),
			'CREATED_BY' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_CREATED_BY'),
				'Type' => 'user',
			),
			'RESPONSIBLE_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID'),
				'Type' => 'user',
				'Editable' => true,
			)
		];

		$fields += self::getResponsibleFields();

		$fields += [
			'DATE_PAY_BEFORE' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_DATE_PAY_BEFORE'),
				'Type' => 'date',
				'Editable' => true,
			),
			'DATE_BILL' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_DATE_BILL'),
				'Type' => 'date',
			),
			'CANCELED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_CANCELED'),
				'Type' => 'bool',
			),
			'EMP_CANCELED_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_EMP_CANCELED_ID'),
				'Type' => 'user',
			),
			'DATE_CANCELED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_DATE_CANCELED'),
				'Type' => 'date',
			),
			'REASON_CANCELED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_REASON_CANCELED'),
				'Type' => 'string',
			),
			'LID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_LID'),
				'Type' => 'string',
			),
			'LID_PRINTABLE' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_LID_PRINTABLE'),
				'Type' => 'string',
			),
		];

		$fields += self::getShippingFields();
		$fields += self::getUserFieldsMap();

		self::appendReferenceFields(
			$fields,
			\CCrmDocumentContact::getEntityFields(\CCrmOwnerType::ContactName),
			\CCrmOwnerType::Contact
		);

		self::appendReferenceFields(
			$fields,
			\CCrmDocumentCompany::getEntityFields(\CCrmOwnerType::CompanyName),
			\CCrmOwnerType::Company
		);

		return $fields;
	}

	private static function appendReferenceFields(array &$thisFields, array $referenceFields, $entityTypeId)
	{
		$fieldNamePrefix = \CCrmOwnerType::GetDescription($entityTypeId) . ': ';
		$fieldIdPrefix = \CCrmOwnerType::ResolveName($entityTypeId);

		foreach ($referenceFields as $id => $field)
		{
			$field['Filterable'] = $field['Editable'] = false;
			$field['Name'] = $fieldNamePrefix.$field['Name'];
			$thisFields[$fieldIdPrefix.'.'.$id] = $field;
		}
	}

	public static function GetDocument($documentId)
	{
		$arDocumentID = static::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
		{
			throw new \CBPArgumentNullException('documentId');
		}

		return new Crm\Integration\BizProc\Document\ValueCollection\Order(
			\CCrmOwnerType::Order,
			$arDocumentID['ID']
		);
	}

	public static function GetDocumentType($documentId)
	{
		$arDocumentID = static::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
		{
			throw new \CBPArgumentNullException('documentId');
		}

		$dbRes = Crm\Order\Order::getList(array(
			'filter' => array('=ID' => $arDocumentID['ID'])
		));
		if (!$dbRes->fetch())
		{
			throw new Main\SystemException('Document is not found.');
		}

		return $arDocumentID['TYPE'];
	}

	public static function CreateDocument($parentDocumentId, $arFields)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	public static function updateDocument($documentId, $arFields, $modifiedById = null)
	{
		$arDocumentID = self::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
			throw new \CBPArgumentNullException('documentId');

		$order = Crm\Order\Order::load($arDocumentID['ID']);

		if ($order)
		{
			$userKeys = [
				'USER_ID', 'EMP_PAYED_ID', 'EMP_DEDUCTED_ID', 'EMP_STATUS_ID', 'EMP_MARKED_ID',
				'EMP_ALLOW_DELIVERY_ID', 'CREATED_BY', 'RESPONSIBLE_ID', 'EMP_CANCELED_ID',
			];
			foreach ($userKeys as $userKey)
			{
				if (isset($arFields[$userKey]))
				{
					$arFields[$userKey] = \CBPHelper::ExtractUsers(
						$arFields[$userKey],
						['crm', __CLASS__, $documentId],
						true
					);
				}
			}

			if (isset($arFields['STATUS_ID']) && $modifiedById)
			{
				$arFields['EMP_STATUS_ID'] = $modifiedById;
			}

			$orderFields = array_filter(
				$arFields,
				fn($key) => strpos($key, 'UF_') !== 0,
				ARRAY_FILTER_USE_KEY
			);

			if ($orderFields)
			{
				$order->setFields($orderFields);
				$result = $order->save();
			}

			self::updateDocumentUserFields($arDocumentID['ID'], $arFields);
		}
	}

	private static function updateDocumentUserFields($orderId, $fields)
	{
		$values = [];

		foreach (static::getUserFieldsMap() as $fieldId => $field)
		{
			if (!isset($fields[$fieldId]))
			{
				continue;
			}

			if ($field['Type'] === 'user')
			{
				$values[$fieldId] = \CBPHelper::ExtractUsers(
					$fields[$fieldId],
					['crm', __CLASS__, $orderId],
					!$field['Multiple']
				);
			}
			elseif ($field['Type'] === 'select')
			{
				static::InternalizeEnumerationField(
					\CCrmOwnerType::ResolveUserFieldEntityID(\CCrmOwnerType::Order),
					$fields,
					$fieldId
				);
				$values[$fieldId] = $fields[$fieldId];
			}
			elseif ($field['Type'] === 'bool')
			{
				$values[$fieldId] = \CBPHelper::getBool($fields[$fieldId]) ? 1 : 0;
			}
			else
			{
				$values[$fieldId] = $fields[$fieldId];
			}
		}

		if ($values)
		{
			Application::getUserTypeManager()->update(
				\CCrmOwnerType::ResolveUserFieldEntityID(\CCrmOwnerType::Order),
				$orderId,
				$values
			);
		}
	}

	public static function DeleteDocument($documentId)
	{
		$arDocumentID = self::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
			throw new \CBPArgumentNullException('documentId');

		$result = Crm\Order\Order::delete($arDocumentID['ID']);
		return $result->isSuccess();
	}

	public static function getEntityName($entity)
	{
		return Loc::getMessage('CRM_BP_DOCUMENT_ORDER_ENTITY_NAME');
	}

	private static function getShippingFields()
	{
		return [
			'SHIPPING.ALL.TRACKING_NUMBER' => [
				'Name' => Loc::getMessage('CRM_BP_DOCUMENT_ORDER_FIELD_SHIPPING_TRACK_NUMBERS'),
				'Type' => 'string',
				'Multiple' => true
			]
		];
	}

	private static function getUserFieldsMap()
	{
		$userFields = [];
		$CCrmUserType = new \CCrmUserType(
			Application::getUserTypeManager(),
			\CCrmOwnerType::ResolveUserFieldEntityID(\CCrmOwnerType::Order),
		);
		$CCrmUserType->addBPFields(
			$userFields,
			['PRINTABLE_SUFFIX' => Loc::getMessage('CRM_BP_DOCUMENT_ORDER_PRINTABLE')]
		);

		foreach ($userFields as &$field)
		{
			if ($field['Type'] === 'UF:date')
			{
				$field['Type'] = 'date';
			}
		}

		return $userFields;
	}

	public static function getDocumentName($documentId)
	{
		$arDocumentID = self::GetDocumentInfo($documentId);
		return \CCrmOwnerType::GetCaption(\CCrmOwnerType::Order, $arDocumentID['ID'], false);
	}

	public static function normalizeDocumentId($documentId)
	{
		return parent::normalizeDocumentIdInternal(
			$documentId,
			\CCrmOwnerType::OrderName,
			\CCrmOwnerTypeAbbr::Order
		);
	}

	public static function createAutomationTarget($documentType)
	{
		return \Bitrix\Crm\Automation\Factory::createTarget(\CCrmOwnerType::Order);
	}

	private static function getStatusOptions()
	{
		$options = [];
		$statuses = Crm\Order\OrderStatus::getListInCrmFormat();
		foreach ($statuses as $status)
		{
			$options[$status['STATUS_ID']] = $status['NAME'];
		}
		return $options;
	}

	private static function getResponsibleFields()
	{
		$fields = [
			'RESPONSIBLE_ID_PRINTABLE' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_PRINTABLE'),
				'Type' => 'string',
			),
			'RESPONSIBLE_ID.EMAIL' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_EMAIL'),
				'Type' => 'string',
			),
			'RESPONSIBLE_ID.WORK_PHONE' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_WORK_PHONE'),
				'Type' => 'string',
			),
			'RESPONSIBLE_ID.PERSONAL_MOBILE' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_PERSONAL_MOBILE'),
				'Type' => 'string',
			),
			'RESPONSIBLE_ID.UF_PHONE_INNER' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_UF_PHONE_INNER'),
				'Type' => 'string',
			),
			'RESPONSIBLE_ID.LOGIN' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_LOGIN'),
				'Type' => 'string',
			),
			'RESPONSIBLE_ID.ACTIVE' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_ACTIVE'),
				'Type' => 'bool',
			),
			'RESPONSIBLE_ID.LAST_NAME' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_LAST_NAME'),
				'Type' => 'string',
			),
			'RESPONSIBLE_ID.NAME' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_NAME'),
				'Type' => 'string',
			),
			'RESPONSIBLE_ID.SECOND_NAME' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_SECOND_NAME'),
				'Type' => 'string',
			),
			'RESPONSIBLE_ID.WORK_POSITION' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_WORK_POSITION'),
				'Type' => 'string',
			),
			'RESPONSIBLE_ID.PERSONAL_WWW' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_PERSONAL_WWW'),
				'Type' => 'string',
			),
			'RESPONSIBLE_ID.PERSONAL_CITY' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_PERSONAL_CITY'),
				'Type' => 'string',
			),
			'RESPONSIBLE_ID.UF_SKYPE' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_UF_SKYPE'),
				'Type' => 'string',
			),
			'RESPONSIBLE_ID.UF_TWITTER' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_UF_TWITTER'),
				'Type' => 'string',
			),
			'RESPONSIBLE_ID.UF_FACEBOOK' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_UF_FACEBOOK'),
				'Type' => 'string',
			),
			'RESPONSIBLE_ID.UF_LINKEDIN' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_UF_LINKEDIN'),
				'Type' => 'string',
			),
			'RESPONSIBLE_ID.UF_XING' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_UF_XING'),
				'Type' => 'string',
			),
			'RESPONSIBLE_ID.UF_WEB_SITES' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_ORDER_FIELD_RESPONSIBLE_ID_UF_WEB_SITES'),
				'Type' => 'string',
			),
		];

		return array_merge(
			$fields,
			static::getExtendedResponsibleFields('RESPONSIBLE_ID.')
		);
	}

	public static function isFeatureEnabled($documentType, $feature)
	{
		if ($feature === 'FEATURE_SET_MODIFIED_BY')
		{
			return false;
		}

		return parent::isFeatureEnabled($documentType, $feature);
	}

	public static function CanUserOperateDocument($operation, $userId, $documentId, $arParameters = array())
	{
		$arDocumentID = static::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
		{
			throw new \CBPArgumentNullException('documentId');
		}

		$userPermissions = \CCrmPerms::GetUserPermissions($userId);
		$result = false;

		if ($arDocumentID['ID'] > 0)
		{
			if (
				$operation == \CBPCanUserOperateOperation::ViewWorkflow
				||
				$operation == \CBPCanUserOperateOperation::ReadDocument
			)
			{
				$result = Permissions\Order::checkReadPermission($arDocumentID['ID'], $userPermissions);
			}
			else
			{
				$result = Permissions\Order::checkUpdatePermission($arDocumentID['ID'], $userPermissions);
			}
		}

		return $result;
	}

	public static function CanUserOperateDocumentType($operation, $userId, $documentType, $arParameters = array())
	{
		$userPermissions = \CCrmPerms::GetUserPermissions($userId);

		if (
			$operation == \CBPCanUserOperateOperation::CreateWorkflow
			|| $operation == \CBPCanUserOperateOperation::CreateAutomation
			|| $operation === \CBPCanUserOperateOperation::DebugAutomation
		)
		{
			return (\CCrmAuthorizationHelper::CheckConfigurationUpdatePermission($userPermissions));
		}

		if (
			$operation === \CBPCanUserOperateOperation::ViewWorkflow
			||
			$operation === \CBPCanUserOperateOperation::ReadDocument
		)
		{
			return Permissions\Order::checkReadPermission(0, $userPermissions);
		}

		return Permissions\Order::checkCreatePermission($userPermissions);
	}
}
