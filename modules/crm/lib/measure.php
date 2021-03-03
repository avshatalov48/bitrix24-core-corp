<?php
namespace Bitrix\Crm;
use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Measure
{
	private static $defaultMeasure = null;
	private static $isDefaultMeasureLoaded = false;
	private static $fieldInfos = null;

	public static function getFieldsInfo()
	{
		if(!self::$fieldInfos)
		{
			self::$fieldInfos = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'CODE' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::Required)
				),
				'MEASURE_TITLE' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::Required)
				),
				'SYMBOL_RUS' => array(
					'TYPE' => 'string'
				),
				'SYMBOL_INTL' => array(
					'TYPE' => 'string'
				),
				'SYMBOL_LETTER_INTL' => array(
					'TYPE' => 'string'
				),
				'IS_DEFAULT' => array(
					'TYPE' => 'char'
				)
			);
		}

		return self::$fieldInfos;
	}
	public static function getProductMeasures($productID)
	{
		if (!Main\Loader::includeModule('catalog'))
		{
			throw new Main\SystemException("Could not load 'catalog' module.");
		}

		$productIDs = is_array($productID) ? $productID : array($productID);

		$measure2product = array();
		if (!empty($productIDs))
		{
			$productEntity = new \CCatalogProduct();
			$dbProductResult = $productEntity->GetList(array(), array('@ID' => $productIDs), false, false, array('ID', 'MEASURE'));
			if(is_object($dbProductResult))
			{
				while($productFields = $dbProductResult->Fetch())
				{
					$measureID = isset($productFields['MEASURE'])  ? intval($productFields['MEASURE']) : 0;
					if($measureID <= 0)
					{
						continue;
					}

					if(!isset($measure2product[$measureID]))
					{
						$measure2product[$measureID] = array();
					}

					$measure2product[$measureID][] =  intval($productFields['ID']);
				}
			}
		}
		$result = array();

		if(!empty($measure2product))
		{
			$dbMeasureResult = \CCatalogMeasure::getList(
				array(),
				array('@ID' => array_keys($measure2product)),
				false,
				false,
				array('ID', 'CODE', 'SYMBOL_RUS', 'SYMBOL_INTL', 'IS_DEFAULT')
			);

			if(is_object($dbMeasureResult))
			{
				while($measureFields = $dbMeasureResult->Fetch())
				{
					$measureID = intval($measureFields['ID']);
					$measureInfo = array(
						'ID' => $measureID,
						'CODE' => intval($measureFields['CODE']),
						'IS_DEFAULT' => isset($measureFields['IS_DEFAULT']) && $measureFields['IS_DEFAULT'] === 'Y',
						'SYMBOL' => isset($measureFields['SYMBOL_RUS'])
							? $measureFields['SYMBOL_RUS'] : $measureFields['SYMBOL_INTL']
					);

					foreach($measure2product[$measureID] as $productID)
					{
						$result[$productID] = array($measureInfo);
					}
				}
			}
		}

		return $result;
	}
	public static function getDefaultMeasure()
	{
		if(self::$isDefaultMeasureLoaded)
		{
			return self::$defaultMeasure;
		}

		if (!Main\Loader::includeModule('catalog'))
		{
			throw new Main\SystemException("Could not load 'catalog' module.");
		}

		self::$isDefaultMeasureLoaded = true;

		$measureFields = \CCatalogMeasure::getDefaultMeasure(true, false);
		if(!is_array($measureFields))
		{
			return null;
		}

		return (
			self::$defaultMeasure = array(
				'ID' => intval($measureFields['ID']),
				'CODE' => intval($measureFields['CODE']),
				'IS_DEFAULT' => isset($measureFields['IS_DEFAULT']) && $measureFields['IS_DEFAULT'] === 'Y',
				'SYMBOL' => isset($measureFields['SYMBOL_RUS'])
					? $measureFields['SYMBOL_RUS'] : $measureFields['SYMBOL_INTL']
			)
		);
	}
	public static function getMeasures($top = 0)
	{
		if (!Main\Loader::includeModule('catalog'))
		{
			throw new Main\SystemException("Could not load 'catalog' module.");
		}

		$top = intval($top);
		$dbMeasureResult = \CCatalogMeasure::getList(
			array('CODE' => 'ASC'),
			array(),
			false,
			($top > 0 ? array('nTopCount' => $top) : false),
			array('ID', 'CODE', 'SYMBOL_RUS', 'SYMBOL_INTL', 'IS_DEFAULT')
		);

		if(!is_object($dbMeasureResult))
		{
			return array();
		}

		$result = array();
		while($measureFields = $dbMeasureResult->Fetch())
		{
			$result[] = array(
				'ID' => intval($measureFields['ID']),
				'CODE' => intval($measureFields['CODE']),
				'IS_DEFAULT' => isset($measureFields['IS_DEFAULT']) && $measureFields['IS_DEFAULT'] === 'Y',
				'SYMBOL' => isset($measureFields['SYMBOL_RUS'])
					? $measureFields['SYMBOL_RUS'] : $measureFields['SYMBOL_INTL']
			);
		}
		return $result;
	}
	public static function getMeasureByCode($code)
	{
		if (!Main\Loader::includeModule('catalog'))
		{
			throw new Main\SystemException("Could not load 'catalog' module.");
		}

		$dbMeasureResult = \CCatalogMeasure::getList(
			array(),
			array('=CODE' => $code),
			false,
			false,
			array('ID', 'CODE', 'SYMBOL_RUS', 'SYMBOL_INTL', 'IS_DEFAULT')
		);

		$measureFields = is_object($dbMeasureResult) ? $dbMeasureResult->Fetch() : null;
		if(!is_array($measureFields))
		{
			return null;
		}

		return array(
			'ID' => intval($measureFields['ID']),
			'CODE' => intval($measureFields['CODE']),
			'IS_DEFAULT' => isset($measureFields['IS_DEFAULT']) && $measureFields['IS_DEFAULT'] === 'Y',
			'SYMBOL' => isset($measureFields['SYMBOL_RUS'])
				? $measureFields['SYMBOL_RUS'] : $measureFields['SYMBOL_INTL']
		);
	}

	/**
	 * @param int $measureId
	 * @return array|null
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	 */
	public static function getMeasureById(int $measureId): ?array
	{
		if (!Main\Loader::includeModule('catalog'))
		{
			throw new Main\SystemException("Could not load 'catalog' module.");
		}
		if ($measureId <= 0)
		{
			return null;
		}

		$dbMeasureResult = \CCatalogMeasure::getList(
			[],
			['=ID' => $measureId],
			false,
			false,
			['ID', 'CODE', 'SYMBOL_RUS', 'SYMBOL_INTL', 'IS_DEFAULT']
		);

		$measureFields = is_object($dbMeasureResult) ? $dbMeasureResult->Fetch() : null;
		if(!is_array($measureFields))
		{
			return null;
		}

		return [
			'ID' => (int)$measureFields['ID'],
			'CODE' => (int)$measureFields['CODE'],
			'IS_DEFAULT' => isset($measureFields['IS_DEFAULT']) && $measureFields['IS_DEFAULT'] === 'Y',
			'SYMBOL' => (isset($measureFields['SYMBOL_RUS'])
				? $measureFields['SYMBOL_RUS']
				: $measureFields['SYMBOL_INTL']
			)
		];
	}

	public static function getFieldCaption($fieldName)
	{
		$result = Loc::getMessage("CRM_MEASURE_FIELD_{$fieldName}");
		return is_string($result) ? $result : '';
	}
}