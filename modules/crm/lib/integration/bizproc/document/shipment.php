<?

namespace Bitrix\Crm\Integration\BizProc\Document;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;

if (!Main\Loader::includeModule('bizproc'))
	return;

class Shipment extends \CCrmDocument
	implements \IBPWorkflowDocument
{
	public static function getDocumentFields($documentType)
	{
		$arDocumentID = self::GetDocumentInfo($documentType.'_0');
		if (empty($arDocumentID))
		{
			throw new \CBPArgumentNullException('documentId');
		}

		return [
			'STATUS_ID' => [
				'Name' => Loc::getMessage("CRM_BP_DOCUMENT_SHIPMENT_FIELD_STATUS_ID"),
				'Type' => 'select',
				'Options' => self::getStatusOptions(),
			],
			'DELIVERY_ID' => [
				'Name' => Loc::getMessage('CRM_BP_DOCUMENT_SHIPMENT_FIELD_DELIVERY_ID'),
				'Type' => 'select',
				'Options' => self::getDeliveryOptions(),
			],
			'PRICE_DELIVERY' => [
				'Name' => Loc::getMessage('CRM_BP_DOCUMENT_SHIPMENT_FIELD_PRICE_DELIVERY'),
				'Type' => 'int',
			],
			'ALLOW_DELIVERY' => [
				'Name' => Loc::getMessage('CRM_BP_DOCUMENT_SHIPMENT_FIELD_ALLOW_DELIVERY'),
				'Type' => 'bool',
			],
			'DEDUCTED' => [
				'Name' => Loc::getMessage('CRM_BP_DOCUMENT_SHIPMENT_FIELD_DEDUCTED'),
				'Type' => 'bool',
			],
			'TRACKING_NUMBER' => [
				'Name' => Loc::getMessage('CRM_BP_DOCUMENT_SHIPMENT_FIELD_TRACKING_NUMBER'),
				'Type' => 'string',
			],
			'COMMENTS' => [
				'Name' => Loc::getMessage('CRM_BP_DOCUMENT_SHIPMENT_FIELD_COMMENTS'),
				'Type' => 'string',
			],
		];
	}

	public static function GetDocument($documentId)
	{
		//TODO – make real document
		return [
			'STATUS_ID' => null,
			'DELIVERY_ID' => null,
			'PRICE_DELIVERY' => null,
			'ALLOW_DELIVERY' => null,
			'DEDUCTED' => null,
			'TRACKING_NUMBER' => null,
			'COMMENTS' => null,
		];
	}

	public static function CreateDocument($parentDocumentId, $arFields)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	public static function UpdateDocument($documentId, $arFields)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	public static function DeleteDocument($documentId)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	private static function getStatusOptions()
	{
		return \Bitrix\Crm\Order\DeliveryStatus::getAllStatusesNames();
	}

	private static function getDeliveryOptions()
	{
		$result = [];
		if (Main\Loader::includeModule('sale'))
		{
			foreach(\Bitrix\Sale\Delivery\Services\Manager::getActiveList() as $service)
			{
				$result[$service['ID']] = $service['NAME'];
			}
		}
		return $result;
	}
}
