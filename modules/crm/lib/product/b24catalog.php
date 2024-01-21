<?php

namespace Bitrix\Crm\Product;

use Bitrix\Main\Loader;
use Bitrix\Iblock;

class B24Catalog extends Catalog
{
	public static function execAgent(bool $skipB24 = false): string
	{
		$result = '';
		if (!$skipB24 && !Loader::includeModule('bitrix24'))
		{
			return $result;
		}
		if (!static::isCatalogIncluded())
		{
			return $result;
		}

		$internalResult = self::updateSettings();
		if (!$internalResult)
		{
			$result = '\Bitrix\Crm\Product\B24Catalog::execAgent();';
		}
		unset($internalResult);

		return $result;
	}

	public static function getDefaultFieldSettings(): array
	{
		if (!static::isIblockIncluded())
		{
			return [];
		}

		$fields = parent::getDefaultFieldSettings();

		$code = $fields['CODE'];
		$code['DEFAULT_VALUE'] = unserialize($code['DEFAULT_VALUE'], ['allowed_classes' => false]);
		$code['DEFAULT_VALUE']['TRANSLITERATION'] = 'Y';
		$code['DEFAULT_VALUE']['UNIQUE'] = 'Y';
		$code['DEFAULT_VALUE']['USE_GOOGLE'] = 'N';
		$code['DEFAULT_VALUE']['TRANS_LEN'] = 255;

		$sectionCode = $fields['SECTION_CODE'];
		$sectionCode['DEFAULT_VALUE'] = unserialize($sectionCode['DEFAULT_VALUE'], ['allowed_classes' => false]);
		$sectionCode['DEFAULT_VALUE']['TRANSLITERATION'] = 'Y';
		$sectionCode['DEFAULT_VALUE']['UNIQUE'] = 'Y';
		$sectionCode['DEFAULT_VALUE']['USE_GOOGLE'] = 'N';
		$sectionCode['DEFAULT_VALUE']['TRANS_LEN'] = 255;

		$fields['CODE'] = $code;
		$fields['SECTION_CODE'] = $sectionCode;
		unset($sectionCode, $code);

		return $fields;
	}

	protected static function getDefaultRights(): array
	{
		if (!static::isIblockIncluded())
		{
			return [];
		}

		$result = parent::getDefaultRights();

		$crmAdminGroupId = \CCrmSaleHelper::getShopGroupIdByType(\CCrmSaleHelper::GROUP_CRM_ADMIN);
		$crmManagerGroupId = \CCrmSaleHelper::getShopGroupIdByType(\CCrmSaleHelper::GROUP_CRM_MANAGER);
		if ($crmAdminGroupId !== null)
		{
			$result[$crmAdminGroupId] = \CIBlockRights::FULL_ACCESS;
		}
		if ($crmManagerGroupId != null)
		{
			$result[$crmManagerGroupId] = \CIBlockRights::EDIT_ACCESS;
		}
		unset($crmManagerGroupId, $crmAdminGroupId);

		return $result;
	}

	private static function updateSettings(): bool
	{
		if (!static::isCatalogIncluded())
		{
			return false;
		}

		$result = true;
		$catalogId = static::getDefaultId();
		if (!empty($catalogId))
		{
			$settings = static::getDefaultProductSettings();
			$fields = static::getDefaultFieldSettings();
			$data = [
				'LIST_MODE' => $settings['LIST_MODE'],
				'FIELDS' => [
					'CODE' => $fields['CODE'],
					'SECTION_CODE' => $fields['SECTION_CODE']
				]
			];

			$iblock = new \CIBlock();
			$iblockResult = $iblock->Update($catalogId, $data);

			if ($iblockResult)
			{
				$internalResult = static::applyDefaultRights($catalogId);
				if (!$internalResult->isSuccess())
				{
					$iblockResult = false;
				}
				unset($internalResult);
			}

			if ($iblockResult)
			{
				$iblockResult = self::createMorePhotoIfNotExists($catalogId);
			}

			if ($iblockResult)
			{
				$offerCatalogId = static::getDefaultOfferId();
				if ($offerCatalogId)
				{
					$internalResult = static::applyDefaultRights($offerCatalogId);
					if (!$internalResult->isSuccess())
					{
						$iblockResult = false;
					}
					unset($internalResult);
					if ($iblockResult)
					{
						$iblockResult = self::createMorePhotoIfNotExists($offerCatalogId);
					}
				}
			}

			if (!$iblockResult)
			{
				$result = false;
			}
			unset($iblockResult, $iblock);
			unset($data, $fields, $settings);
		}
		unset($catalogId);

		return $result;
	}

	private static function createMorePhotoIfNotExists(int $iblockId): bool
	{
		if (!static::isIblockIncluded())
		{
			return false;
		}

		$propertyId = \CIBlockPropertyTools::createProperty(
			$iblockId,
			\CIBlockPropertyTools::CODE_MORE_PHOTO
		);
		if (empty($propertyId))
		{
			return false;
		}

		$features = [];
		$iterator = Iblock\PropertyFeatureTable::getList([
			'select' => ['*'],
			'filter' => [
				'=PROPERTY_ID' => $propertyId,
			],
		]);
		while ($row = $iterator->fetch())
		{
			$features[] = [
				'MODULE_ID' => $row['MODULE_ID'],
				'FEATURE_ID' => $row['FEATURE_ID'],
				'IS_ENABLED' => $row['IS_ENABLED']
			];
		}
		unset($row, $iterator);
		$features[] = [
			'MODULE_ID' => 'iblock',
			'FEATURE_ID' => Iblock\Model\PropertyFeature::FEATURE_ID_LIST_PAGE_SHOW,
			'IS_ENABLED' => 'Y',
		];
		$features[] = [
			'MODULE_ID' => 'iblock',
			'FEATURE_ID' => Iblock\Model\PropertyFeature::FEATURE_ID_DETAIL_PAGE_SHOW,
			'IS_ENABLED' => 'Y',
		];

		$internaResult = Iblock\Model\PropertyFeature::setFeatures($propertyId, $features);
		$result = $internaResult->isSuccess();
		unset($features, $internaResult);

		return $result;
	}
}
