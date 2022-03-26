<?php
namespace Bitrix\Crm\Update\Catalog;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Crm\ProductRowTable;

final class CrmProductRowUpdater extends Main\Update\Stepper
{
	protected const PRODUCT_LIMIT = 100;

	public static function getTitle()
	{
		return Loc::getMessage('CRM_PRODUCT_ROW_UPDATER_STEPPER_TITLE');
	}

	public function execute(array &$option): bool
	{
		return self::FINISH_EXECUTION;

		if (
			!ModuleManager::isModuleInstalled('bitrix24')
			|| !Loader::includeModule('iblock')
			|| !Loader::includeModule('catalog')
			|| !Loader::includeModule('crm')
		)
		{
			return self::FINISH_EXECUTION;
		}

		if (empty($option))
		{
			$crmProductRowCount = \CCrmProductRow::GetList(
				[],
				['PRODUCT_NAME' => '', '!=ORIGINAL_PRODUCT_NAME' => null],
				[]
			);
			$option["steps"] = 0;
			$option["count"] = (int)ceil($crmProductRowCount / self::PRODUCT_LIMIT);
		}

		if ($option["count"] === 0)
		{
			return self::FINISH_EXECUTION;
		}

		$crmProductRowResult = \CCrmProductRow::GetList(
			['ID' => 'ASC'],
			['PRODUCT_NAME' => '', '!=ORIGINAL_PRODUCT_NAME' => null],
			false,
			false,
			['ID', 'ORIGINAL_PRODUCT_NAME'],
			[
				'QUERY_OPTIONS' => [
					'LIMIT' => self::PRODUCT_LIMIT,
					'OFFSET' => $option["steps"] * self::PRODUCT_LIMIT
				]
			]
		);

		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$query = 'UPDATE '.$helper->quote(ProductRowTable::getTableName()).' SET '.$helper->quote('PRODUCT_NAME').' = \'';
		$where = '\' WHERE '.$helper->quote('ID').' = ';
		while ($crmProductRow = $crmProductRowResult->Fetch())
		{
			$connection->query($query
				. $helper->forSql($crmProductRow['ORIGINAL_PRODUCT_NAME'])
				. $where . (int)$crmProductRow['ID']
			);
		}

		$option["steps"]++;

		return $option['steps'] < $option['count'] ? self::CONTINUE_EXECUTION : self::FINISH_EXECUTION;
	}
}
