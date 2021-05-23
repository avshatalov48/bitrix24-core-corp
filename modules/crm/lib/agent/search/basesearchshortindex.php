<?php

namespace Bitrix\Crm\Agent\Search;

use Bitrix\Crm\Search\SearchContentBuilderFactory;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Update\Stepper;

abstract class BaseSearchShortIndex extends Stepper
{
	protected static
		$moduleId = 'crm',
		$typeId;

	public function execute(array &$result)
	{
		if(!Loader::includeModule(static::$moduleId))
		{
			return false;
		}

		$className = static::class;
		$option = Option::get(static::$moduleId, $className, 0);
		$result['steps'] = $option;

		$limit = 50;
		$result['steps'] = ($result['steps'] ?? 0);
		$selectedRowsCount = 0;

		$objectQuery = $this->getList($limit, $result['steps']);

		if($objectQuery)
		{
			$selectedRowsCount = $objectQuery->SelectedRowsCount();
			while($fields = $objectQuery->fetch())
			{
				SearchContentBuilderFactory::create(static::$typeId)
					->build($fields['ID'], [
						'checkExist' => true,
						'onlyShortIndex' => true
					]);
			}
		}

		if($selectedRowsCount < $limit)
		{
			Option::delete(static::$moduleId, ['name' => $className]);
			return false;
		}
		else
		{
			$result['steps'] += $selectedRowsCount;
			$option = $result['steps'];
			Option::set(static::$moduleId, $className, $option);
			return true;
		}
	}

	abstract protected function getList(int $limit, int $steps);
}