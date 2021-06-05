<?php

namespace Bitrix\Crm\Agent\Recyclebin;

use Bitrix\Main\Loader;
use Bitrix\Recyclebin\Internals\Models\RecyclebinTable;
use Bitrix\Recyclebin\Recyclebin;

final class EraseStepper extends \Bitrix\Main\Update\Stepper
{
	protected static $moduleId = "crm";

	private const ENTITY_LIMIT = 100;

	public function execute(array &$result): bool
	{
		if(!Loader::includeModule('recyclebin'))
		{
			return self::FINISH_EXECUTION;
		}

		if (empty($this->outerParams[0]))
		{
			return self::FINISH_EXECUTION;
		}

		if(empty($result))
		{
			$result['steps'] = 0;
			$result['count'] = $this->getCount();
		}
		$this->removeEntities();
		$result['steps']++;
		return ($result['steps'] * self::ENTITY_LIMIT <= $result['count'] ? self::CONTINUE_EXECUTION : self::FINISH_EXECUTION);
	}

	private function getCount(): int
	{
		return RecyclebinTable::getCount([
			'=MODULE_ID' => self::$moduleId,
			'=ENTITY_TYPE' => $this->outerParams[0]
		]);
	}

	private function removeEntities(): void
	{
		$list = RecyclebinTable::getList([
			'select' => [
				'ID'
			],
			'filter' => [
				'=MODULE_ID' => self::$moduleId,
				'=ENTITY_TYPE' => $this->outerParams[0]
			],
			'order' => ['TIMESTAMP' => 'ASC'],
			'limit' => self::ENTITY_LIMIT
		]);
		foreach($list as $item)
		{
			Recyclebin::remove($item['ID'], ['skipAdminRightsCheck' => true]);
		}
	}
}
