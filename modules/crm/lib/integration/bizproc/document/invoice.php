<?

namespace Bitrix\Crm\Integration\BizProc\Document;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;

if (!Main\Loader::includeModule('bizproc'))
	return;

Loc::loadMessages(__FILE__);

class Invoice extends \CCrmDocument
	implements \IBPWorkflowDocument
{
	static public function GetDocumentFields($documentType)
	{
		$arDocumentID = self::GetDocumentInfo($documentType.'_0');
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		$arResult = self::getEntityFields($arDocumentID['TYPE']);

		return $arResult;
	}

	public static function getEntityFields($entityType)
	{
		$arResult = array(
			'ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_ID'),
				'Type' => 'int',
				'Editable' => false,
				'Required' => false,
			),
			'LID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_LID'),
				'Type' => 'string',
				'Editable' => false,
				'Required' => true,
			),
			'ACCOUNT_NUMBER' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_ACCOUNT_NUMBER'),
				'Type' => 'string',
				'Editable' => false,
				'Required' => true,
			),
			'DATE_INSERT' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_DATE_INSERT'),
				'Type' => 'datetime',
				'Editable' => false,
				'Required' => true,
			),
			'DATE_UPDATE' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_DATE_UPDATE'),
				'Type' => 'datetime',
				'Editable' => false,
				'Required' => true,
			),
			'PERSON_TYPE_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_PERSON_TYPE_ID'),
				'Type' => 'string',
				'Editable' => false,
				'Required' => true,
			),
			'USER_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_USER_ID'),
				'Type' => 'user',
				'Editable' => false,
				'Required' => true,
			),
			'PAYED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_PAYED'),
				'Type' => 'bool',
				'Editable' => true,
				'Required' => false,
			),
			'DATE_PAYED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_DATE_PAYED'),
				'Type' => 'datetime',
				'Editable' => true,
				'Required' => false,
			),
			'EMP_PAYED_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_EMP_PAYED_ID'),
				'Type' => 'user',
				'Editable' => false,
				'Required' => false,
			),
			'DEDUCTED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_DEDUCTED'),
				'Type' => 'bool',
				'Editable' => true,
				'Required' => false,
			),
			'DATE_DEDUCTED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_DATE_DEDUCTED'),
				'Type' => 'datetime',
				'Editable' => true,
				'Required' => false,
			),
			'EMP_DEDUCTED_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_EMP_DEDUCTED_ID'),
				'Type' => 'user',
				'Editable' => false,
				'Required' => false,
			),
			'REASON_UNDO_DEDUCTED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_REASON_UNDO_DEDUCTED'),
				'Type' => 'string',
				'Editable' => true,
				'Required' => false,
			),
			'STATUS_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_STATUS_ID'),
				'Type' => 'select',
				'Options' => self::getStatusOptions(),
				'Editable' => true,
				'Required' => true,
			),
			'DATE_STATUS' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_DATE_STATUS'),
				'Type' => 'datetime',
				'Editable' => false,
				'Required' => false,
			),
			'EMP_STATUS_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_EMP_STATUS_ID'),
				'Type' => 'user',
				'Editable' => false,
				'Required' => false,
			),
			'MARKED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_MARKED'),
				'Type' => 'bool',
				'Editable' => true,
				'Required' => false,
			),
			'DATE_MARKED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_DATE_MARKED'),
				'Type' => 'datetime',
				'Editable' => false,
				'Required' => false,
			),
			'EMP_MARKED_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_EMP_MARKED_ID'),
				'Type' => 'user',
				'Editable' => false,
				'Required' => false,
			),
			'REASON_MARKED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_REASON_MARKED'),
				'Type' => 'string',
				'Editable' => false,
				'Required' => false,
			),
			'PRICE_DELIVERY' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_PRICE_DELIVERY'),
				'Type' => 'double',
				'Editable' => false,
				'Required' => false,
			),
			'ALLOW_DELIVERY' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_ALLOW_DELIVERY'),
				'Type' => 'bool',
				'Editable' => true,
				'Required' => false,
			),
			'DATE_ALLOW_DELIVERY' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_DATE_ALLOW_DELIVERY'),
				'Type' => 'datetime',
				'Editable' => false,
				'Required' => false,
			),
			'EMP_ALLOW_DELIVERY_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_EMP_ALLOW_DELIVERY_ID'),
				'Type' => 'user',
				'Editable' => false,
				'Required' => false,
			),
			'RESERVED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_RESERVED'),
				'Type' => 'bool',
				'Editable' => true,
				'Required' => false,
			),
			'PRICE' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_PRICE'),
				'Type' => 'double',
				'Editable' => false,
				'Required' => false,
			),
			'CURRENCY' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_CURRENCY'),
				'Type' => 'select',
				'Options' => \CCrmCurrencyHelper::PrepareListItems(),
				'Editable' => false,
				'Required' => false,
			),
			'TAX_VALUE' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_TAX_VALUE'),
				'Type' => 'double',
				'Editable' => false,
				'Required' => false,
			),
			'SUM_PAID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_SUM_PAID'),
				'Type' => 'double',
				'Editable' => false,
				'Required' => false,
			),
			'USER_DESCRIPTION' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_USER_DESCRIPTION'),
				'Type' => 'string',
				'Editable' => false,
				'Required' => false,
			),
			'ADDITIONAL_INFO' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_ADDITIONAL_INFO'),
				'Type' => 'string',
				'Editable' => true,
				'Required' => false,
			),
			'COMMENTS' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_COMMENTS'),
				'Type' => 'string',
				'Editable' => true,
				'Required' => false,
			),
			'COMPANY_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_COMPANY_ID'),
				'Type' => 'int',
				'Editable' => false,
				'Required' => false,
			),
			'CREATED_BY' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_CREATED_BY'),
				'Type' => 'user',
				'Editable' => false,
				'Required' => false,
			),
			'RESPONSIBLE_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_RESPONSIBLE_ID'),
				'Type' => 'user',
				'Editable' => true,
				'Required' => false,
			),
			'DATE_PAY_BEFORE' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_DATE_PAY_BEFORE'),
				'Type' => 'date',
				'Editable' => true,
				'Required' => false,
			),
			'DATE_BILL' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_DATE_BILL'),
				'Type' => 'date',
				'Editable' => true,
				'Required' => false,
			),
			'CANCELED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_CANCELED'),
				'Type' => 'bool',
				'Editable' => true,
				'Required' => false,
			),
			'EMP_CANCELED_ID' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_EMP_CANCELED_ID'),
				'Type' => 'user',
				'Editable' => false,
				'Required' => false,
			),
			'DATE_CANCELED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_DATE_CANCELED'),
				'Type' => 'datetime',
				'Editable' => false,
				'Required' => false,
			),
			'REASON_CANCELED' => array(
				'Name' => GetMessage('CRM_BP_DOCUMENT_INVOICE_FIELD_REASON_CANCELED'),
				'Type' => 'string',
				'Editable' => true,
				'Required' => false,
			),
		);

		return $arResult;
	}

	static public function GetDocument($documentId)
	{
		$arDocumentID = static::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		$arResult = null;

		//TODO: use new API
		$invoiceFields = \CCrmInvoice::GetByID($arDocumentID['ID'], false);

		if ($invoiceFields)
		{
			$userKeys = [
				'USER_ID', 'EMP_PAYED_ID', 'EMP_DEDUCTED_ID', 'EMP_STATUS_ID', 'EMP_MARKED_ID',
				'EMP_ALLOW_DELIVERY_ID', 'CREATED_BY', 'RESPONSIBLE_ID', 'EMP_CANCELED_ID',
			];
			foreach ($userKeys as $userKey)
			{
				if (isset($invoiceFields[$userKey]))
				{
					$invoiceFields[$userKey] = 'user_'.$invoiceFields[$userKey];
				}
			}

			return $invoiceFields;
		}
		return null;
	}

	static public function GetDocumentType($documentId)
	{
		$arDocumentID = static::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
		{
			throw new CBPArgumentNullException('documentId');
		}

		//TODO: use new API
		if (!\CCrmInvoice::Exists($arDocumentID['ID']))
		{
			throw new Main\SystemException('Document is not found.');
		}

		return $arDocumentID['TYPE'];
	}

	public static function CreateDocument($parentDocumentId, $arFields)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	static public function UpdateDocument($documentId, $arFields)
	{
		$arDocumentID = self::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		//TODO: use new API
		if (!\CCrmInvoice::Exists($arDocumentID['ID']))
		{
			throw new CBPArgumentNullException('document is not exists');
		}

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

		if(empty($arFields))
		{
			return;
		}

		$invoice = new \CCrmInvoice(false);
		return $invoice->update(
			$arDocumentID['ID'],
			$arFields,
			['REGISTER_SONET_EVENT' => true, 'CURRENT_USER' => static::getSystemUserId()]
		);
	}

	static public function DeleteDocument($documentId)
	{
		$arDocumentID = self::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		//TODO: use new API
		$CCrmEntity = new \CCrmInvoice(false);
		$result = $CCrmEntity->Delete($arDocumentID['ID']);

		return $result;
	}

	public static function getEntityName($entity)
	{
		return Loc::getMessage('CRM_BP_DOCUMENT_INVOICE_ENTITY_NAME');
	}

	public function getDocumentName($documentId)
	{
		$arDocumentID = self::GetDocumentInfo($documentId);
		return \CCrmOwnerType::GetCaption(\CCrmOwnerType::Invoice, $arDocumentID['ID'], false);
	}

	public static function normalizeDocumentId($documentId)
	{
		return parent::normalizeDocumentIdInternal(
			$documentId,
			\CCrmOwnerType::InvoiceName,
			\CCrmOwnerTypeAbbr::Invoice
		);
	}

	public static function createAutomationTarget($documentType)
	{
		return \Bitrix\Crm\Automation\Factory::createTarget(\CCrmOwnerType::Invoice);
	}

	private static function getStatusOptions()
	{
		$options = [];
		$statuses = \CCrmStatus::GetStatus('INVOICE_STATUS');
		foreach ($statuses as $status)
		{
			$options[$status['STATUS_ID']] = $status['NAME'];
		}
		return $options;
	}
}
