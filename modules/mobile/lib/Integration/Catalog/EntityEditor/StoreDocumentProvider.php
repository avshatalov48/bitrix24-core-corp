<?php

namespace Bitrix\Mobile\Integration\Catalog\EntityEditor;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Mobile\UI\File;

Loader::requireModule('catalog');

class StoreDocumentProvider extends \Bitrix\Catalog\v2\Integration\UI\EntityEditor\StoreDocumentProvider
{
	protected const GUID_PREFIX = 'MOBILE_STORE_DOCUMENT_DETAIL_';

	private const USER_FIELD = 'user';
	private const STRING_FIELD = 'string';
	private const SELECT_FIELD = 'select';
	private const ENTITY_SELECTOR_FIELD = 'entity-selector';
	private const FILE_FIELD = 'file';

	private const USER_PROVIDER_CONTEXT = 'CATALOG_DOCUMENT';
	private const CONTRACTOR_PROVIDER_CONTEXT = 'catalog_document_contractors';

	private const CLIENT_FIELD = 'client_light';
	private const CLIENT_COMPANY_SELECTOR_TYPE = 'client_company';
	private const CONTRACTOR_SELECTOR_TYPE = 'contractor';

	private const ENTITY_LIST_POSTFIX = '_ENTITY_LIST';
	private const FILE_INFO_POSTFIX = '_FILE_INFO';

	public function getEntityFieldsForListView(): array
	{
		$hiddenFields = [
			'TITLE' => true,
			'DATE_CREATE' => true,
			'DOCUMENT_PRODUCTS' => true,
		];

		$entityConfig = $this->getEntityConfig();
		$fieldNames = $this->flattenConfigToFieldNames($entityConfig);
		$fields = array_column($this->getEntityFields(), null, 'name');

		$results = [];
		foreach ($fieldNames as $name)
		{
			if (empty($hiddenFields[$name]) && !empty($fields[$name]))
			{
				$results[] = $fields[$name];
			}
		}

		return $results;
	}

	private function flattenConfigToFieldNames(array $entityConfig): array
	{
		$fields = [];

		foreach ($entityConfig as $column)
		{
			if (!empty($column['elements']) && is_array($column['elements']))
			{
				foreach ($column['elements'] as $section)
				{
					if (!empty($section['elements']) && is_array($section['elements']))
					{
						foreach ($section['elements'] as $field)
						{
							$fields[] = $field['name'];
						}
					}
				}
			}
		}

		return $fields;
	}

	protected function getDocumentFields(): array
	{
		$fields = parent::getDocumentFields();

		foreach ($fields as &$field)
		{
			if ($field['type'] === 'text')
			{
				$field['type'] = self::STRING_FIELD;
			}
			elseif ($field['type'] === 'list')
			{
				$field['type'] = self::SELECT_FIELD;
			}
			elseif ($field['type'] === 'datetime')
			{
				$enableTime = $field['data']['enableTime'] ?? true;
				$field['type'] = $enableTime ? 'datetime' : 'date';
			}
			elseif (in_array($field['type'], ['money', 'moneyPay', 'document_total'], true))
			{
				$field['type'] = 'opportunity';
			}

			$field['multiple'] = $field['data']['multiple'] ?? false;
		}

		unset($field);

		$fields[] = [
			'name' => 'DOC_STATUS',
			'title' => Loc::getMessage('CATALOG_STORE_DOCUMENT_DETAIL_FIELD_DOC_STATUS'),
			'type' => 'status',
			'editable' => false,
			'showAlways' => true,
		];

		return $fields;
	}

	public function getEntityConfig(): array
	{
		$config = parent::getEntityConfig();

		if (!empty($config[0]['elements']))
		{
			$firstColumnSections =& $config[0]['elements'];
			if (!empty($firstColumnSections[0]['elements']))
			{
				array_splice(
					$firstColumnSections[0]['elements'],
					1,
					0,
					[['name' => 'DOC_STATUS']]
				);
			}
		}

		return $config;
	}

	private function getStatuses(): array
	{
		$statuses = [];

		$wasCancelled = ($this->document['WAS_CANCELLED'] ?? 'N') === 'Y';
		$hasStatus = ($this->document['STATUS'] ?? 'N') === 'Y';

		$allStatuses = $this->getStatusesList();

		if ($hasStatus)
		{
			$statuses[] = $allStatuses['Y'];
		}
		elseif ($wasCancelled)
		{
			$statuses[] = $allStatuses['C'];
		}
		else
		{
			$statuses[] = $allStatuses['N'];
		}

		return $statuses;
	}

	public function getEntityData(): array
	{
		$data = parent::getEntityData();

		foreach ($data as &$field)
		{
			if ($field instanceof Date)
			{
				$field = $field->getTimestamp();
			}
		}
		unset($field);

		$data['DOC_STATUS'] = $this->getStatuses();
		return $data;
	}

	public function getStatusesList(): array
	{
		return [
			'Y' => [
				'name' => Loc::getMessage('CATALOG_STORE_DOCUMENT_DETAIL_FIELD_DOC_STATUS_CONDUCTED'),
				'backgroundColor' => '#e0f5c2',
				'color' => '#589309',
			],
			'N' => [
				'name' => Loc::getMessage('CATALOG_STORE_DOCUMENT_DETAIL_FIELD_DOC_STATUS_NOT_CONDUCTED'),
				'backgroundColor' => '#e0e2e4',
				'color' => '#79818b',
			],
			'C' => [
				'name' => Loc::getMessage('CATALOG_STORE_DOCUMENT_DETAIL_FIELD_DOC_STATUS_CANCELLED'),
				'backgroundColor' => '#faf4a0',
				'color' => '#9d7e2b',
			],
		];
	}

	protected function getAdditionalDocumentData(array $document): array
	{
		$document = parent::getAdditionalDocumentData($document);

		foreach ($this->getEntityFields() as $field)
		{
			if ($field['type'] === self::FILE_FIELD && empty($this->config['skipFiles']))
			{
				$document[$field['name'] . self::FILE_INFO_POSTFIX] = [];

				if (!empty($document[$field['name']]))
				{
					$files = $document[$field['name']];
					if (!is_array($files))
					{
						$files = [$files];
					}

					foreach ($files as $fileId)
					{
						$fileId = (int)$fileId;
						if (!$fileId)
						{
							continue;
						}

						$fileInfo = $this->getFileInfo($fileId);
						if ($fileInfo)
						{
							$document[$field['name'] . self::FILE_INFO_POSTFIX][$fileId] = $fileInfo;
						}
					}
				}
			}
		}

		return $document;
	}

	/**
	 * @return array
	 */
	protected function getContractorField(): array
	{
		$field = parent::getContractorField();

		if ($this->contractorsProvider)
		{
			/**
			 * @TODO keep DRY
			 * @see \Bitrix\CrmMobile\UI\EntityEditor\Provider::getEntityFields()
			 */

			$permissions = [];

			$categoryParams = $field['data']['categoryParams'] ?? [];
			if (!empty($categoryParams) && is_array($categoryParams))
			{
				foreach ($categoryParams as $entityTypeId => $entity)
				{
					$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
					$serviceUserPermissions = Container::getInstance()->getUserPermissions();
					$permissions[$entityTypeName] = [
						'read' => $serviceUserPermissions->checkReadPermissions($entityTypeId),
						'add' => $serviceUserPermissions->checkAddPermissions($entityTypeId),
					];
				}
			}

			$field['data']['permissions'] = $permissions;
		}
		else
		{
			$field['type'] = self::ENTITY_SELECTOR_FIELD;
			$field['data'] = [
				'selectorType' => self::CONTRACTOR_SELECTOR_TYPE,
				'provider' => [
					'context' => self::CONTRACTOR_PROVIDER_CONTEXT,
					'options' => [],
				],
				'enableCreation' => true,
				'entityListField' => $field['name'] . self::ENTITY_LIST_POSTFIX,
			];
		}

		return $field;
	}

	protected function getFileInfo(int $fileId): ?File
	{
		static $fileInfoCache = [];

		if (!isset($fileInfoCache[$fileId]))
		{
			$fileInfoCache[$fileId] = File::loadWithPreview($fileId);
		}

		return $fileInfoCache[$fileId];
	}

	/**
	 * @param array $document
	 * @return array
	 */
	protected function getContractorData(array $document): array
	{
		if (!$this->contractorsProvider)
		{
			return [
				'CONTRACTOR_ID' . self::ENTITY_LIST_POSTFIX => [
					[
						'id' => $document['CONTRACTOR_ID'],
						'title' => $this->getContractorName(),
					],
				],
			];
		}

		return parent::getContractorData($document);
	}

	protected function getAdditionalUserData(array $document, array $userFields, array $usersInfo): array
	{
		foreach ($userFields as $fieldName => $userId)
		{
			if (!$userId || empty($usersInfo[$userId]))
			{
				continue;
			}

			$user = $usersInfo[$userId];

			$userName = \CUser::FormatName(
				\CSite::GetNameFormat(false),
				[
					'LOGIN' => $user['LOGIN'],
					'NAME' => $user['NAME'],
					'LAST_NAME' => $user['LAST_NAME'],
					'SECOND_NAME' => $user['SECOND_NAME'],
				],
				true,
				false
			);

			$imageUrl = null;
			if ((int)$user['PERSONAL_PHOTO'] > 0)
			{
				$fileInfo = \CFile::ResizeImageGet(
					(int)$user['PERSONAL_PHOTO'],
					[
						'width' => 60,
						'height' => 60,
					],
					BX_RESIZE_IMAGE_EXACT
				);
				if (isset($fileInfo['src']))
				{
					$imageUrl = $fileInfo['src'];
				}
			}

			$document[$fieldName . self::ENTITY_LIST_POSTFIX] = [
				[
					'id' => (int)$user['ID'],
					'title' => $userName,
					'imageUrl' => $imageUrl,
				],
			];
		}

		return $document;
	}

	protected function getAdditionalFieldKeys($fields): array
	{
		foreach ($fields as &$field)
		{
			if ($field['type'] === self::USER_FIELD)
			{
				$field['data'] = [
					'entityListField' => $field['name'] . self::ENTITY_LIST_POSTFIX,
					'provider' => [
						'context' => static::USER_PROVIDER_CONTEXT,
					],
				];
			}
			elseif ($field['type'] === self::FILE_FIELD)
			{
				$field['data'] = array_merge($field['data'], [
					'fileInfoField' => $field['name'] . self::FILE_INFO_POSTFIX,
					'controller' => [
						'entityId' => 'catalog-document',
					],
				]);
			}

			if ($field['type'] === self::USER_FIELD || $field['type'] === self::CLIENT_FIELD)
			{
				$field['data']['hasSolidBorder'] = true;
			}
		}

		unset($field);

		return $fields;
	}

	public function getEntityControllers(): array
	{
		$controllers = parent::getEntityControllers();
		foreach ($controllers as $key => $controller)
		{
			if ($controller['name'] === 'PRODUCT_LIST_CONTROLLER')
			{
				$controllers[$key] = $this->prepareProductListController($controller);
				break;
			}
		}

		return $controllers;
	}

	private function prepareProductListController(array $controller): array
	{
		$config = $controller['config'] ?? [];

		$config['currencyFieldName'] = 'CURRENCY';
		$config['priceWithCurrencyFieldName'] = 'TOTAL_WITH_CURRENCY';
		$config['productSummaryFieldName'] = 'DOCUMENT_PRODUCTS';

		return array_merge($controller, ['config' => $config]);
	}

	/**
	 * @inheritDoc
	 */
	protected function prepareCurrencyListItem(array $currency): array
	{
		return array_change_key_case(
			parent::prepareCurrencyListItem($currency),
			CASE_LOWER
		);
	}

	protected function getTotalInfoControlForNewDocument(): array
	{
		return $this->getTotalInfoControlForExistingDocument();
	}

	protected function shouldPrepareDateFields(): bool
	{
		return false;
	}
}
