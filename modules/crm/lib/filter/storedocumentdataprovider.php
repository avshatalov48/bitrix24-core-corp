<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Main\Grid\Column;
use Bitrix\Main\Localization\Loc;

class StoreDocumentDataProvider extends \Bitrix\Main\Filter\EntityDataProvider
{
	private $mode;

	private static $fieldsOrder = [
		'shipment' => [
			'ID', 'TITLE', 'DOC_TYPE', 'DEDUCTED', 'DATE_UPDATE', 'DATE_INSERT', 'DATE_DEDUCTED', 'DELIVERY_NAME',
			'RESPONSIBLE_ID', 'TOTAL', 'CLIENT', 'STORES',
		],
	];

	private static $fields;

	public function __construct($mode)
	{
		$this->mode = $mode;
		self::$fields = [
			'ID' => [
				'id' => 'ID',
				'name' => Loc::getMessage('STORE_DOCUMENT_DATA_PROVIDER_ID_NAME'),
				'default' => false,
				'sort' => 'ID',
			],
			'TITLE' => [
				'id' => 'TITLE',
				'name' => Loc::getMessage('STORE_DOCUMENT_DATA_PROVIDER_TITLE_NAME'),
				'default' => true,
				'width' => '215',
			],
			'DOC_TYPE' => [
				'id' => 'DOC_TYPE',
				'name' => Loc::getMessage('STORE_DOCUMENT_DATA_PROVIDER_TYPE_NAME'),
				'default' => false,
				'sort' => false,
				'type' => Column\Type::LABELS,
			],
			'DATE_UPDATE' => [
				'id' => 'DATE_UPDATE',
				'name' => Loc::getMessage('STORE_DOCUMENT_DATA_PROVIDER_DATE_UPDATE_NAME'),
				'default' => true,
				'sort' => 'DATE_UPDATE',
			],
			'DATE_INSERT' => [
				'id' => 'DATE_INSERT',
				'name' => Loc::getMessage('STORE_DOCUMENT_DATA_PROVIDER_DATE_INSERT_NAME'),
				'default' => false,
				'sort' => 'DATE_INSERT',
			],
			'DEDUCTED' => [
				'id' => 'DEDUCTED',
				'name' => Loc::getMessage('STORE_DOCUMENT_DATA_PROVIDER_DEDUCTED_NAME'),
				'default' => true,
				'sort' => 'DEDUCTED',
				'type' => Column\Type::LABELS,
			],
			'DATE_DEDUCTED' => [
				'id' => 'DATE_DEDUCTED',
				'name' => Loc::getMessage('STORE_DOCUMENT_DATA_PROVIDER_DATE_DEDUCTED_NAME'),
				'default' => false,
				'sort' => 'DATE_DEDUCTED',
			],
			'DELIVERY_NAME' => [
				'id' => 'DELIVERY_NAME',
				'name' => Loc::getMessage('STORE_DOCUMENT_DATA_PROVIDER_DELIVERY_NAME_NAME'),
				'default' => false,
				'sort' => 'DELIVERY_NAME',
			],
			'RESPONSIBLE_ID' => [
				'id' => 'RESPONSIBLE_ID',
				'name' => Loc::getMessage('STORE_DOCUMENT_DATA_PROVIDER_RESPONSIBLE_ID_NAME'),
				'default' => true,
				'sort' => 'RESPONSIBLE_ID',
			],
			'TOTAL' => [
				'id' => 'TOTAL',
				'name' => Loc::getMessage('STORE_DOCUMENT_DATA_PROVIDER_TOTAL_NAME'),
				'default' => true,
				'sort' => false,
			],
			'CLIENT' => [
				'id' => 'CLIENT',
				'name' => Loc::getMessage('STORE_DOCUMENT_DATA_PROVIDER_CLIENT_NAME'),
				'default' => true,
				'sort' => false,
			],
			'STORES' => [
				'id' => 'STORES',
				'name' => Loc::getMessage('STORE_DOCUMENT_DATA_PROVIDER_STORES_NAME'),
				'default' => true,
				'sort' => false,
				'type' => Column\Type::LABELS,
			],
		];
	}

	public function getSettings()
	{
		// TODO: Implement getSettings() method.
	}

	public function prepareFields()
	{
		$fields = [
			'ID' => $this->createField('ID', [
				'type' => 'number',
			]),
			'DATE_UPDATE' => $this->createField('DATE_UPDATE', [
				'default' => true,
				'type' => 'date',
			]),
			'DATE_INSERT' => $this->createField('DATE_INSERT', [
				'default' => true,
				'type' => 'date',
			]),
			'DEDUCTED' => $this->createField('DEDUCTED', [
				'default' => true,
				'type' => 'list',
				'partial' => true,
			]),
			'DATE_DEDUCTED' => $this->createField('DATE_DEDUCTED', [
				'default' => false,
				'type' => 'date',
			]),
			'DELIVERY_NAME' => $this->createField('DELIVERY_NAME', [
				'type' => 'text',
				'default' => false,
			]),
			'RESPONSIBLE_ID' => $this->createField('RESPONSIBLE_ID', [
				'default' => true,
				'partial' => true,
				'type' => 'entity_selector',
			]),
			'CLIENT' => $this->createField('CLIENT', [
				'default' => true,
				'type' => 'dest_selector',
				'partial' => true,
			]),
			'STORES' => $this->createField('STORES', [
				'partial' => true,
				'type' => 'entity_selector',
			]),
			'PRODUCTS' => $this->createField('PRODUCTS', [
				'default' => true,
				'partial' => true,
				'type' => 'entity_selector',
			]),
		];

		return $fields;
	}

	protected function getFieldName($fieldID)
	{
		return Loc::getMessage("STORE_DOCUMENT_DATA_PROVIDER_{$fieldID}_NAME");
	}

	public function prepareFieldData($fieldID)
	{
		$userFields = ['RESPONSIBLE_ID'];
		if (in_array($fieldID, $userFields))
		{
			return $this->getUserEntitySelectorParams($this->mode . '_' . $fieldID . '_filter', ['fieldName' => $fieldID]);
		}

		if ($fieldID === 'DEDUCTED')
		{
			return [
				'params' => [
					'multiple' => 'Y',
				],
				'items' => [
					'Y' => Loc::getMessage('STORE_DOCUMENT_DATA_PROVIDER_STATUS_CONDUCTED'),
					'N' => Loc::getMessage('STORE_DOCUMENT_DATA_PROVIDER_STATUS_NOT_CONDUCTED'),
					'C' => Loc::getMessage('STORE_DOCUMENT_DATA_PROVIDER_STATUS_CANCELLED'),
				]
			];
		}

		if ($fieldID === 'STORES')
		{
			return [
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'height' => 200,
						'context' => $this->mode . '_store_filter',
						'entities' => [
							[
								'id' => 'store',
								'dynamicLoad' => true,
								'dynamicSearch' => true,
							]
						],
						'dropdownMode' => false,
					],
				],
			];
		}

		if ($fieldID === 'CLIENT')
		{
			return [
				'params' => [
					'apiVersion' => 3,
					'context' => 'CRM_TIMELINE_FILTER_CLIENT',
					'contextCode' => 'CRM',
					'useClientDatabase' => 'N',
					'enableAll' => 'N',
					'enableDepartments' => 'N',
					'enableUsers' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'enableCrm' => 'Y',
					'enableCrmContacts' => 'Y',
					'enableCrmCompanies' => 'Y',
					'addTabCrmContacts' => 'Y',
					'addTabCrmCompanies' => 'Y',
					'convertJson' => 'Y',
					'multiple' => 'Y',
				],
			];
		}

		if ($fieldID === 'PRODUCTS')
		{
			return [
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'height' => 200,
						'context' => $this->mode . '_product_filter',
						'entities' => [
							[
								'id' => 'product',
								'options' => [
									'iblockId' => \Bitrix\Crm\Product\Catalog::getDefaultId(),
									'basePriceId' => \Bitrix\Crm\Product\Price::getBaseId(),
								],
							]
						],
						'dropdownMode' => false,
					],
				],
			];
		}
	}

	public function getGridColumns()
	{
		$columns = [];
		$fieldsOrder = self::$fieldsOrder[$this->mode];
		foreach ($fieldsOrder as $field)
		{
			$columns[] = self::$fields[$field];
		}

		return $columns;
	}
}
