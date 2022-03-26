<?php

namespace Bitrix\Mobile\Integration\Catalog\EntityEditor;

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
	private const ENTITY_SELECTOR_FIELD = 'entity-selector';
	private const FILE_FIELD = 'file';

	private const USER_PROVIDER_CONTEXT = 'CATALOG_DOCUMENT';
	private const CONTRACTOR_PROVIDER_CONTEXT = 'catalog_document_contractors';

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
			elseif ($field['type'] === 'contractor')
			{
				$field['type'] = self::ENTITY_SELECTOR_FIELD;
				$field['data'] = [
					'selectorType' => self::CONTRACTOR_SELECTOR_TYPE,
					'enableCreation' => true,
				];
			}
			elseif ($field['type'] === 'datetime')
			{
				$enableTime = $field['data']['enableTime'] ?? true;
				$field['type'] = $enableTime ? 'datetime' : 'date';
			}
			elseif (in_array($field['type'], ['moneyPay', 'document_total'], true))
			{
				$field['type'] = 'money';
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
				'color' => '#589308',
				'backgroundColor' => '#e4f5c8',
			],
			'N' => [
				'name' => Loc::getMessage('CATALOG_STORE_DOCUMENT_DETAIL_FIELD_DOC_STATUS_NOT_CONDUCTED'),
				'color' => '#535c69',
				'backgroundColor' => '#eaebed',
			],
			'C' => [
				'name' => Loc::getMessage('CATALOG_STORE_DOCUMENT_DETAIL_FIELD_DOC_STATUS_CANCELLED'),
				'color' => '#b47a00',
				'backgroundColor' => '#ffdfa1',
			],
		];
	}

	protected function getAdditionalDocumentData(array $document): array
	{
		$document = parent::getAdditionalDocumentData($document);

		foreach ($this->getEntityFields() as $field)
		{
			if (
				$field['type'] === self::ENTITY_SELECTOR_FIELD
				&& $field['data']['selectorType'] === self::CONTRACTOR_SELECTOR_TYPE
			)
			{
				$document[$field['name'] . self::ENTITY_LIST_POSTFIX] = [];

				if (!empty($this->document[$field['name']]))
				{
					$document[$field['name'] . self::ENTITY_LIST_POSTFIX][] = [
						'id' => $this->document[$field['name']],
						'title' => $this->getContractorName(),
					];
				}
			}
			elseif ($field['type'] === self::FILE_FIELD && empty($this->config['skipFiles']))
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

	protected function getFileInfo(int $fileId): ?File
	{
		static $fileInfoCache = [];

		if (!isset($fileInfoCache[$fileId]))
		{
			$fileInfoCache[$fileId] = File::loadWithPreview($fileId);
		}

		return $fileInfoCache[$fileId];
	}

	protected function getAdditionalUserData(array $document, array $userFields, array $usersInfo): array
	{
		foreach ($userFields as $fieldName => $userId)
		{
			if (!$userId)
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
			elseif ($field['type'] === self::ENTITY_SELECTOR_FIELD)
			{
				if ($field['data']['selectorType'] === self::CONTRACTOR_SELECTOR_TYPE)
				{
					$field['data'] = array_merge($field['data'], [
						'entityListField' => $field['name'] . self::ENTITY_LIST_POSTFIX,
						'provider' => [
							'context' => self::CONTRACTOR_PROVIDER_CONTEXT,
						],
					]);
				}
			}
			elseif ($field['type'] === self::FILE_FIELD)
			{
				$field['data'] = array_merge($field['data'], [
					'fileInfoField' => $field['name'] . self::FILE_INFO_POSTFIX,
				]);
			}
		}

		unset($field);

		return $fields;
	}
}
