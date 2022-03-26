<?php
namespace Bitrix\Crm\Product;

use Bitrix\Iblock;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\LanguageTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

if (Loader::includeModule('catalog'))
{
	class Catalog
	{
		public const DEFAULT_TYPE_ID = 'CRM_PRODUCT_CATALOG';

		public static function execAgent(): string
		{
			return '';
		}

		/**
		 * Returns catalog type id for crm.
		 *
		 * @return string
		 */
		public static function getTypeId(): string
		{
			$result = Main\Config\Option::get('crm', 'product_catalog_type_id');
			return ($result !== '' ? $result : self::DEFAULT_TYPE_ID);
		}

		/**
		 * Returns default crm catalog id.
		 *
		 * @return int|null
		 */
		public static function getDefaultId(): ?int
		{
			static $iblockList = [];

			$id = (int)Main\Config\Option::get('crm', 'default_product_catalog_id');

			if ($id > 0)
			{
				if (!isset($iblockList[$id]))
				{
					$filter = ['=ID' => $id];
					if (ModuleManager::isModuleInstalled('bitrix24'))
					{
						$filter['=IBLOCK_TYPE_ID'] = static::getTypeId();
					}
					$iterator = Iblock\IblockTable::getList([
						'select' => ['ID'],
						'filter' => $filter,
					]);
					$row = $iterator->fetch();
					$iblockList[$id] = !empty($row) ? $id : 0;
					unset($row, $iterator);
				}
				$id = $iblockList[$id];
			}

			return ($id > 0 ? $id : null);
		}

		/**
		 * Returns default crm offers iblock id.
		 *
		 * @return int|null
		 */
		public static function getDefaultOfferId(): ?int
		{
			$productCatalogId = static::getDefaultId();
			if (!$productCatalogId)
			{
				return null;
			}

			$offerCatalogId = null;
			$iblockInfo = \CCatalogSku::GetInfoByProductIBlock($productCatalogId);

			if (!empty($iblockInfo))
			{
				$offerCatalogId = $iblockInfo['IBLOCK_ID'];
			}

			return $offerCatalogId;
		}

		public static function getDefaultProductSettings(): array
		{
			return static::getDefaultSettings();
		}

		public static function getDefaultOfferSettings(): array
		{
			$result = static::getDefaultSettings();
			$result['LIST_MODE'] = Iblock\IblockTable::LIST_MODE_SEPARATE;
			return $result;
		}

		protected static function getDefaultSettings(): array
		{
			return [
				'ACTIVE' => 'Y',
				'IBLOCK_TYPE_ID' => static::getTypeId(),
				'INDEX_SECTION' => 'N',
				'INDEX_ELEMENT' => 'N',
				'WORKFLOW' => 'N',
				'BIZPROC' => 'N',
				'VERSION' => Iblock\IblockTable::PROPERTY_STORAGE_COMMON,
				'RIGHTS_MODE' => Iblock\IblockTable::RIGHTS_SIMPLE,
				'GROUP_ID' => static::getDefaultRights(),
				'LIST_MODE' => Iblock\IblockTable::LIST_MODE_COMBINED
			];
		}

		public static function getDefaultFieldSettings(): array
		{
			return \CIBlock::GetFieldsDefaults();
		}

		/**
		 * @return Main\Result
		 */
		public static function createType(): Main\Result
		{
			$result = new Main\Result();
			$typeId = static::getTypeId();
			if (!static::isTypeExists())
			{
				$fields = [
					'ID' => $typeId,
					'SECTIONS' => 'Y',
					'IN_RSS' => 'N',
					'SORT' => 100,
				];
				$languages = static::getTypeMessages();
				$internalResult = Iblock\TypeTable::add($fields);
				if ($internalResult->isSuccess())
				{
					if (!empty($languages))
					{
						foreach ($languages as $messages)
						{
							$messages['IBLOCK_TYPE_ID'] = $typeId;
							$internalResult = Iblock\TypeLanguageTable::add($messages);
							if (!$internalResult->isSuccess())
							{
								$result->addErrors($internalResult->getErrors());
								break;
							}
						}
						unset($messages);
					}
				}
				else
				{
					$result->addErrors($internalResult->getErrors());
				}
				unset($internalResult);
				unset($languages, $fields);
			}
			if ($result->isSuccess())
			{
				$result->setData(['ID' => $typeId]);
			}
			unset($typeId);
			return $result;
		}

		public static function isTypeExists(): bool
		{
			$iterator = Iblock\TypeTable::getList([
				'select' => ['ID'],
				'filter' => ['=ID' => static::getTypeId()]
			]);
			$row = $iterator->fetch();
			unset($iterator);
			return (!empty($row));
		}

		public static function isExists(int $catalogId): bool
		{
			$iterator = Iblock\IblockTable::getList([
				'select' => ['ID'],
				'filter' => ['=ID' => $catalogId]
			]);
			$row = $iterator->fetch();
			unset($iterator);
			return (!empty($row));
		}

		public static function getIblock(int $catalogId, array $fields = []): Main\Result
		{
			$success = true;
			$result = new Main\Result();
			if ($catalogId <= 0)
			{
				$result->addError(new Main\Error(Loc::getMessage('CRM_PRODUCT_CATALOG_BAD_IBLOCK_ID')));
				$success = false;
			}
			if ($success)
			{
				if (empty($fields))
				{
					$fields = ['*'];
				}
				$iterator = Iblock\IblockTable::getList([
					'select' => $fields,
					'filter' => ['=ID' => $catalogId]
				]);
				$row = $iterator->fetch();
				unset($iterator);
				if (empty($row))
				{
					$result->addError(new Main\Error(Loc::getMessage('CRM_PRODUCT_CATALOG_IBLOCK_IS_ABSENT')));
				}
				else
				{
					$row['ID'] = (int)$row['ID'];
					$result->setData($row);
				}
				unset($row);
			}
			return $result;
		}

		public static function applyDefaultRights(int $catalogId): Main\Result
		{
			$result = static::getIblock($catalogId, ['ID', 'IBLOCK_TYPE_ID']);
			if (!$result->isSuccess())
			{
				return $result;
			}

			$catalog = $result->getData();
			$rightList = static::getDefaultRights();
			foreach ($rightList as $groupId => $right)
			{
				\CIBlockRights::setGroupRight($groupId, $catalog['IBLOCK_TYPE_ID'], $right, $catalog['ID']);
			}
			unset($groupId, $right, $rightList);
			unset($catalog);

			return $result;
		}

		protected static function getTypeMessages(): ?array
		{
			$result = [];

			$iterator = LanguageTable::getList([
				'select' => ['ID'],
				'filter' => ['=ACTIVE' => 'Y']
			]);
			while ($row = $iterator->fetch())
			{
				$messages = Loc::loadLanguageFile(__FILE__, $row['ID']);
				if (!empty($messages))
				{
					if (
						!empty($messages['CRM_PRODUCT_CATALOG_TYPE_TITLE'])
						&& !empty($messages['CRM_PRODUCT_CATALOG_SECTION_NAME'])
						&& !empty($messages['CRM_PRODUCT_CATALOG_PRODUCT_NAME'])
					)
					{
						$result[$row['ID']] = [
							'LANGUAGE_ID' => $row['ID'],
							'NAME' => $messages['CRM_PRODUCT_CATALOG_TYPE_TITLE'],
							'SECTIONS_NAME' => $messages['CRM_PRODUCT_CATALOG_SECTION_NAME'],
							'ELEMENTS_NAME' => $messages['CRM_PRODUCT_CATALOG_PRODUCT_NAME']
						];
					}
				}
			}
			unset($messages, $row, $iterator);

			return (!empty($result) ? $result: null);
		}

		protected static function getDefaultRights(): array
		{
			return \CIBlock::getDefaultRights();
		}
	}
}
