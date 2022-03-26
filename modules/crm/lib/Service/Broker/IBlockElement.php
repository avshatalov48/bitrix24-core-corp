<?php


namespace Bitrix\Crm\Service\Broker;


use Bitrix\Crm\Service\Broker;
use Bitrix\Crm\Product;
use Bitrix\Iblock;
use Bitrix\Main\Loader;
use Bitrix\Main\Type;

class IBlockElement extends Broker
{
	private $iblockIncluded;
	private $catalogIncluded;

	protected function loadEntry(int $id): ?array
	{
		if (!$this->isIblockIncluded())
		{
			return null;
		}
		if ($id <= 0)
		{
			return null;
		}

		$row = Iblock\ElementTable::getList([
			'select' => [
				'ID',
				'IBLOCK_ID',
				'NAME',
			],
			'filter' => [
				'=ID' => $id,
			],
		])->fetch();
		if (empty($row))
		{
			return null;
		}

		$row['ID'] = (int)$row['ID'];
		$row['IBLOCK_ID'] = (int)$row['IBLOCK_ID'];
		if ($this->isCrmCatalog($row['IBLOCK_ID']))
		{
			$urls = $this->getDetailUrls($row['IBLOCK_ID'], [$row['ID']]);
			$row['DETAIL_PAGE_URL'] = $urls[$row['ID']];
			unset($urls);
		}
		else
		{
			$iterator = \CIBlockElement::GetList(
				[],
				[
					'ID' => $id,
					'IBLOCK_ID' => $row['IBLOCK_ID'],
					'CHECK_PERMISSIONS' => 'N',
				],
				false,
				false,
				[
					'ID',
					'IBLOCK_ID',
					'DETAIL_PAGE_URL',
				]
			);
			$element = $iterator->GetNext();
			$row['DETAIL_PAGE_URL'] = $element['DETAIL_PAGE_URL'];
			unset($element, $iterator);
		}

		return $row;
	}

	/**
	 * @inheritDoc
	 */
	protected function loadEntries(array $ids): array
	{
		if (!$this->isIblockIncluded())
		{
			return [];
		}

		Type\Collection::normalizeArrayValuesByInt($ids);
		if (empty($ids))
		{
			return [];
		}

		$result = [];
		$iblockElements = [];
		foreach (array_chunk($ids, 500) as $pageIds)
		{
			$iterator = Iblock\ElementTable::getList([
				'select' => [
					'ID',
					'IBLOCK_ID',
					'NAME',
				],
				'filter' => [
					'@ID' => $pageIds,
				],
			]);
			while ($row = $iterator->fetch())
			{
				$row['ID'] = (int)$row['ID'];
				$row['IBLOCK_ID'] = (int)$row['IBLOCK_ID'];
				$id = $row['ID'];
				$iblockId = $row['IBLOCK_ID'];
				if (!isset($iblockElements[$iblockId]))
				{
					$iblockElements[$iblockId] = [];
				}
				$iblockElements[$iblockId][] = $id;
				$result[$id] = $row;
			}
			unset($row, $iterator);
		}


		foreach (array_keys($iblockElements) as $iblockId)
		{
			if ($this->isCrmCatalog($iblockId))
			{
				$urlList = $this->getDetailUrls($iblockId, $iblockElements[$iblockId]);
				foreach ($urlList as $id => $url)
				{
					$result[$id]['DETAIL_PAGE_URL'] = $url;
				}
				unset($urlList);
			}
			else
			{
				foreach ($iblockElements[$iblockId] as $pageIds)
				{
					$iterator = \CIBlockElement::GetList(
						[],
						[
							'ID' => $pageIds,
							'IBLOCK_ID' => $iblockId,
							'CHECK_PERMISSIONS' => 'N',
						],
						false,
						false,
						[
							'ID',
							'IBLOCK_ID',
							'DETAIL_PAGE_URL',
						]
					);
					while ($row = $iterator->GetNext())
					{
						$id = (int)$row['ID'];
						$result[$id]['DETAIL_PAGE_URL'] = $row['DETAIL_PAGE_URL'];
					}
					unset($row, $iterator);
				}
			}
		}
		unset($iblockElements);

		return $result;
	}

	private function includeModules(): void
	{
		if ($this->iblockIncluded === null)
		{
			$this->iblockIncluded = Loader::includeModule('iblock');
		}
		if ($this->catalogIncluded === null)
		{
			$this->catalogIncluded = Loader::includeModule('catalog');
		}
	}

	private function isIblockIncluded(): bool
	{
		$this->includeModules();

		return $this->iblockIncluded;
	}

	private function isCrmCatalog(int $id): bool
	{
		if (!$this->isIblockIncluded())
		{
			return false;
		}
		if (!$this->catalogIncluded)
		{
			return false;
		}

		return (Product\Catalog::getDefaultId() === $id);
	}

	private function getDetailUrls(int $iblockId, array $idList): array
	{
		$result = [];

		$urlBuilder = new Product\Url\ProductBuilder();
		$urlBuilder->setIblockId($iblockId);
		$urlBuilder->setUrlParams([]);

		foreach ($idList as $id)
		{
			$result[$id] = $urlBuilder->getElementDetailUrl($id);
		}

		return $result;
	}
}
