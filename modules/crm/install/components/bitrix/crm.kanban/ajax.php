<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Crm\Integration\Recyclebin\RecyclingManager;
use Bitrix\Recyclebin\Recyclebin;

class KanbanAjaxController extends \Bitrix\Main\Engine\Controller
{
	/**
	 * @param array $entityIds
	 * @param int $entityTypeId
	 * @return array|null
	 */
	public function restoreAction(array $entityIds, int $entityTypeId): ?array
	{
		try
		{
			Loader::includeModule('recyclebin');
			Loader::includeModule('crm');

			$result = [];

			foreach ($entityIds as $entityId)
			{
				$recyclebinEntityId = Recyclebin::findId(
					'crm',
					RecyclingManager::resolveRecyclableEntityType($entityTypeId),
					$entityId
				);

				$result[$recyclebinEntityId] = Recyclebin::restore($recyclebinEntityId);
			}

			return $result;
		}
		catch (\Exception $e)
		{
			$this->errorCollection[] = new Error($e->getMessage(), $e->getCode());

			return null;
		}
	}
}