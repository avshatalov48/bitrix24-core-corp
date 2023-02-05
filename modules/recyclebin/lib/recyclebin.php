<?php
namespace Bitrix\Recyclebin;

use Bitrix\Main\AccessDeniedException;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Recyclebin\Internals\Entity;
use Bitrix\Recyclebin\Internals\Models\RecyclebinDataTable;
use Bitrix\Recyclebin\Internals\Models\RecyclebinFileTable;
use Bitrix\Recyclebin\Internals\Models\RecyclebinTable;
use Bitrix\Recyclebin\Internals\User;

class Recyclebin
{
	public static function restore($recyclebinId, array $params = [])
	{
		$entity = self::getEntityData($recyclebinId);
		if (!$entity)
		{
			return false;
		}

		if($entity->getOwnerId() != User::getCurrentUserId() && !User::isSuper())
		{
			throw new AccessDeniedException('Access Denied');
		}

		$handler = self::getHandler($entity);

		if (!class_exists($handler))
		{
			return null;
		}

		$result = call_user_func([$handler, 'moveFromRecyclebin'], $entity);

		if ($result)
		{
			self::removeRecyclebinInternal($recyclebinId);
		}

		return $result;
	}

	public static function getEntityData($recyclebinId)
	{
		try
		{
			$recyclebin = RecyclebinTable::getById($recyclebinId)->fetch();

			$data = $files = [];
			if ($recyclebin)
			{
				$recyclebinData = RecyclebinDataTable::getList(['filter' => ['=RECYCLEBIN_ID' => $recyclebinId]])->fetchAll();
				if ($recyclebinData)
				{
					foreach ($recyclebinData as $action => $value)
					{
						$data[$action] = $value;
					}
				}

				$recyclebinFiles = RecyclebinFileTable::getList(['filter' => ['=RECYCLEBIN_ID' => $recyclebinId]])->fetchAll();
				if ($recyclebinFiles)
				{
					foreach ($recyclebinFiles as $storage)
					{
						unset($storage['ID'], $storage['RECYCLEBIN_ID']);

						$files[$storage['FILE_ID']] = $storage;
					}
				}
			}

			$entity = new Entity($recyclebin['ENTITY_ID'], $recyclebin['ENTITY_TYPE'], $recyclebin['MODULE_ID']);
			$entity->setId($recyclebinId);
			if(isset($recyclebin['NAME']))
			{
				$entity->setTitle($recyclebin['NAME']);
			}
			$entity->setData($data);
			$entity->setFiles($files);
			$entity->setOwnerId($recyclebin['USER_ID']);

			return $entity;
		}
		catch (\Exception $e)
		{
		}

		return false;
	}

	private static function getHandler(Entity $entity)
	{
		$modules = self::getAvailableModules();
		$module = $modules[$entity->getModuleId()];
		$entityData = $module['LIST'][$entity->getEntityType()];

		return $entityData['HANDLER'];
	}

	/**
	 * @return array
	 */
	public static function getAdditionalData(): array
	{
		$additionalData = [];

		$event = new Event("recyclebin", "onAdditionalDataRequest");
		$event->send();

		if ($event->getResults())
		{
			foreach ($event->getResults() as $eventResult)
			{
				if ($eventResult->getType() === EventResult::SUCCESS)
				{
					$params = $eventResult->getParameters();
					if (empty($params) || !isset($params['ADDITIONAL_DATA']) || empty($params['ADDITIONAL_DATA']))
					{
						continue;
					}

					$moduleId = $eventResult->getModuleId();
					$additionalData[$moduleId] = $params;
				}
			}
		}

		return $additionalData;
	}

	public static function getAvailableModules()
	{
		static $list = null;

		if (!$list)
		{
			$event = new Event("recyclebin", "OnModuleSurvey");
			$event->send();
			if ($event->getResults())
			{
				foreach ($event->getResults() as $eventResult)
				{
					if ($eventResult->getType() == EventResult::SUCCESS)
					{
						$params = $eventResult->getParameters();
						if (empty($params) || !isset($params['LIST']) || empty($params['LIST']))
						{
							continue;
						}

						$moduleId = $eventResult->getModuleId();

						$list[$moduleId] = $params;
					}
				}
			}
		}

		return $list;
	}

	private static function removeRecyclebinInternal($recyclebinId)
	{
		try
		{
			if (RecyclebinTable::delete($recyclebinId))
			{
				RecyclebinDataTable::deleteByRecyclebinId($recyclebinId);
				RecyclebinFileTable::deleteByRecyclebinId($recyclebinId);
			}

			return true;
		}
		catch (\Exception $e)
		{
			return false;
		}
	}

	public static function remove($recyclebinId, array $params = [])
	{
		$entity = self::getEntityData($recyclebinId);
		if (!$entity)
		{
			return false;
		}
		if (
			!User::isSuper()
			&& !User::isAdmin()
			&& empty($params['skipAdminRightsCheck'])
		)
		{
			throw new AccessDeniedException('Access Denied');
		}

		$handler = self::getHandler($entity);

		if (!class_exists($handler))
		{
			return null;
		}

		$result = call_user_func([$handler, 'removeFromRecyclebin'], $entity);

		if ($result)
		{
			self::removeRecyclebinInternal($recyclebinId);
		}

		return $result;
	}

	public static function preview($recyclebinId, array $params = [])
	{
		return false;
	}

	public static function findId($moduleId, $entityType, $entityId)
	{
		$fields = RecyclebinTable::getRow(
			[
				'filter' => [ '=MODULE_ID' => $moduleId, '=ENTITY_TYPE' => $entityType, '=ENTITY_ID' => $entityId ],
				'select' => [ 'ID' ]
			]
		);
		return is_array($fields) ? (int)$fields['ID'] : 0;
	}
}
