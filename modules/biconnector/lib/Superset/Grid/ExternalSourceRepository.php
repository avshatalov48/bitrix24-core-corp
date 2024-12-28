<?php

namespace Bitrix\BIConnector\Superset\Grid;

use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable;
use Bitrix\BIConnector\ExternalSource\SourceManager;
use Bitrix\BIConnector\Superset\ExternalSource\CrmTracking\SourceProvider;
use Bitrix\Crm\Tracking\Internals\SourceTable;
use Bitrix\Crm\Tracking\Provider;
use Bitrix\Main\Entity\ExpressionField;

class ExternalSourceRepository
{
	private ExternalSourceGrid $grid;

	public function __construct(ExternalSourceGrid $grid)
	{
		$this->grid = $grid;
	}

	public static function getStaticSourceList(): array
	{
		$rawDataSource = Provider::getStaticSources();
		$actualSourceList = [];
		$filter = self::getSourceFilter();
		foreach ($rawDataSource as $source)
		{
			if (in_array($source['CODE'], $filter, true))
			{
				$actualSourceList[] = [
					'CODE' => $source['CODE'],
					'ICON_CLASS' => $source['ICON_CLASS'],
					'NAME' => $source['NAME'],
				];
			}
		}

		if (SourceManager::is1cConnectionsAvailable())
		{
			$actualSourceList[] = [
				'CODE' => '1c',
				'ICON_CLASS' => 'ui-icon ui-icon-service-1c',
				'NAME' => '1C',
			];
		}

		return $actualSourceList;
	}

	public static function getSourceFilter(): array
	{
		$sourceList = SourceProvider::getSources();

		return array_keys($sourceList);
	}

	public function getTotalCountForPagination(): int
	{
		$queryForBiconnectorTable = ExternalSourceTable::query()
			->setSelect(['ID'])
			->setFilter($this->grid->getOrmFilter())
			->setCacheTtl(3600)
		;

		$queryForCrmTable = SourceTable::query()
			->setSelect(['TITLE' => 'NAME', 'TYPE' => 'CODE'])
			->addSelect(new ExpressionField('CREATED_BY_ID', 'NULL'))
			->setFilter($this->grid->getOrmFilter())
			->addFilter('=TYPE', self::getSourceFilter())
			->setCacheTtl(3600)
		;

		return $queryForBiconnectorTable->queryCountTotal() + $queryForCrmTable->queryCountTotal();
	}

	public function getRawData(): array
	{
		return ExternalSourceTable::query()
			->setSelect($this->getBiconnectorSourceSelect())
			->addSelect(new ExpressionField('MODULE', '\'BI\''))
			->setFilter($this->grid->getOrmFilter())
			->unionAll(SourceTable::query()
				->setSelect($this->getCrmSourceTableSelect())
				->addSelect(new ExpressionField('CREATED_BY_ID', 'NULL'))
				->addSelect(new ExpressionField('DESCRIPTION', 'NULL'))
				->addSelect(new ExpressionField('MODULE', '\'CRM\''))
				->setFilter($this->grid->getOrmFilter())
				->addFilter('=TYPE', self::getSourceFilter())
			)
			->setUnionOrder($this->grid->getOrmOrder())
			->setUnionLimit($this->grid->getOrmParams()['limit'])
			->setUnionOffset($this->grid->getOrmParams()['offset'])
			->fetchAll();
	}

	private function getBiconnectorSourceSelect(): array
	{
		return ['ID', 'TITLE', 'TYPE', 'ACTIVE', 'DATE_CREATE', 'CREATED_BY_ID', 'DESCRIPTION'];
	}

	private function getCrmSourceTableSelect(): array
	{
		return ['ID', 'TITLE' => 'NAME', 'TYPE' => 'CODE', 'ACTIVE', 'DATE_CREATE'];
	}
}
