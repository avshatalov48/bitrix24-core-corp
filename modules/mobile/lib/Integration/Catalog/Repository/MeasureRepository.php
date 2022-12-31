<?php

namespace Bitrix\Mobile\Integration\Catalog\Repository;

use Bitrix\Main\Loader;
use Bitrix\Mobile\Integration\Catalog\Dto\Measure;
use CCatalogMeasure;

Loader::includeModule('catalog');

final class MeasureRepository
{
	/** @var Measure[] */
	private static $measures = [];

	/** @var bool */
	private static $inited = false;

	/**
	 * @param string|null $code
	 * @return Measure|null
	 */
	public static function findByCode(?string $code): ?Measure
	{
		$measures = self::findAll();
		return $measures[$code] ?? self::getDefaultMeasure();
	}

	/**
	 * @param int|null $id
	 * @return Measure|null
	 */
	public static function findById(?int $id): ?Measure
	{
		if ($id !== null)
		{
			foreach (self::findAll() as $measure)
			{
				if ($measure->id === $id)
				{
					return $measure;
				}
			}
		}
		return self::getDefaultMeasure();
	}

	/**
	 * @return Measure[]
	 */
	public static function findAll(): array
	{
		if (!self::$inited)
		{
			self::$inited = true;

			$measuresCollection = self::loadDbMeasuresCollection();
			$hasDefault = false;

			foreach ($measuresCollection as $code => $measure)
			{
				$measureItem = new Measure([
					'id' => (int)$measure['ID'],
					'code' => $code,
					'name' => $measure['SYMBOL_RUS'] ?? $measure['SYMBOL_INTL'],
					'isDefault' => ($measure['IS_DEFAULT'] === 'Y'),
				]);
				if ($measure['IS_DEFAULT'] === 'Y')
				{
					$hasDefault = true;
				}

				self::$measures[$code] = $measureItem;
			}

			if (!$hasDefault)
			{
				self::resetDefaultMeasure();
			}
		}

		return self::$measures;
	}

	/**
	 * @return Measure|null
	 */
	public static function getDefaultMeasure(): ?Measure
	{
		foreach (self::findAll() as $measure)
		{
			if ($measure->isDefault)
			{
				return $measure;
			}
		}

		return null;
	}

	/**
	 * @return array<string, array>
	 */
	private static function loadDbMeasuresCollection(): array
	{
		$sort = ['CODE' => 'ASC'];
		$filter = [];
		$select = ['ID', 'CODE', 'SYMBOL_RUS', 'SYMBOL_INTL', 'IS_DEFAULT'];
		$measuresCollection = [];
		$rows = CCatalogMeasure::getList($sort, $filter, false, false, $select);
		while ($row = $rows->Fetch())
		{
			$measuresCollection[$row['CODE']] = $row;
		}

		if (empty($measuresCollection))
		{
			$defaultMeasure = CCatalogMeasure::getDefaultMeasure(true);
			if (!empty($defaultMeasure))
			{
				$measuresCollection[$defaultMeasure['CODE']] = $defaultMeasure;
			}
		}
		return $measuresCollection;
	}

	/**
	 * @return void
	 */
	private static function resetDefaultMeasure()
	{
		$defaultMeasure = CCatalogMeasure::getDefaultMeasure(true);
		foreach (self::$measures as $code => $measure)
		{
			self::$measures[$code]->isDefault = false;
		}

		if (isset(self::$measures[$defaultMeasure['CODE']]))
		{
			self::$measures[$defaultMeasure['CODE']]->isDefault = true;
		}
		else
		{
			foreach (self::$measures as $key => $measure)
			{
				self::$measures[$key]->isDefault = true;
				break;
			}
		}
	}
}
