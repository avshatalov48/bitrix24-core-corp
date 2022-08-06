<?php
namespace Bitrix\Tasks\Integration\Recyclebin;

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\Result;
use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\Internals\Task\TemplateTable;

use Bitrix\Recyclebin\Internals\Entity;
use Bitrix\Recyclebin\Internals\Contracts\Recyclebinable;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;

if (Loader::includeModule('recyclebin'))
{
	/**
	 * Class Template
	 * @package Bitrix\Tasks\Integration\Recyclebin
	 */
	class Template implements Recyclebinable
	{
		/**
		 * @param $templateId
		 * @param array $template
		 * @return Result
		 */
		public static function OnBeforeDelete($templateId, array $template = [])
		{
			$recyclebin = new Entity($templateId, Manager::TASKS_TEMPLATE_RECYCLEBIN_ENTITY, Manager::MODULE_ID);
			$recyclebin->setTitle($template['TITLE']);

			$additionalData = self::collectAdditionalData($templateId);
			if ($additionalData)
			{
				foreach ($additionalData as $action => $data)
				{
					$recyclebin->add($action, $data);
				}
			}

			$result = $recyclebin->save();

			return $result;
		}

		/**
		 * @param $templateId
		 * @return array
		 */
		private static function collectAdditionalData($templateId)
		{
			$data = [];

			return $data;
		}

		/**
		 * @param Entity $entity
		 *
		 * @return Result
		 */
		public static function moveFromRecyclebin(Entity $entity)
		{
			$result = new Result();

			$templateId = $entity->getEntityId();
			$connection = Application::getConnection();

			try
			{
				$connection->queryExecute('UPDATE ' . TemplateTable::getTableName() . ' SET ZOMBIE = \'N\' WHERE ID = ' . $templateId);
			}
			catch (\Exception $e)
			{
				$result->addError(new Error($e->getMessage(), $e->getCode()));
			}

			try
			{
				$select = ['ID', 'CREATED_BY', 'REPLICATE', 'REPLICATE_PARAMS', 'TPARAM_REPLICATION_COUNT'];
				$template = \CTaskTemplates::getList([], ['ID' => $templateId], [], [], $select)->fetch();

				if ($template && $template["REPLICATE"] == "Y")
				{
					$name = 'CTasks::RepeatTaskByTemplateId(' . $templateId . ');';

					$nextTime = \CTasks::getNextTime(unserialize($template['REPLICATE_PARAMS'], ['allowed_classes' => false]), $template); // localtime
					if ($nextTime)
					{

						\CAgent::AddAgent($name,'tasks','N',86400, $nextTime,'Y', $nextTime);
					}
				}
			}
			catch (\Exception $e)
			{
				$result->addError(new Error($e->getMessage(), $e->getCode()));
			}

			return $result;
		}

		private static function restoreAdditionalData($templateId, $action, array $data = [])
		{
			$result = new Result();

			try
			{

			}
			catch (\Exception $e)
			{
				$result->addError(new Error($e->getMessage(), $e->getCode()));
			}

			return $result;
		}

		/**
		 * Removes entity from recycle bin
		 *
		 * @param Entity $entity
		 * @param array $params
		 *
		 * @return Result
		 */
		public static function removeFromRecyclebin(Entity $entity, array $params = [])
		{
			$result = new Result;

			try
			{
				$templateId = $entity->getEntityId();
				$params = ['UNSAFE_DELETE_ONLY' => true];

				\CTaskTemplates::Delete($templateId, $params);
			}
			catch (\Exception $e)
			{
				$result->addError(new Error($e->getMessage(), $e->getCode()));
			}

			return $result;
		}

		/**
		 * @param Entity $entity
		 * @return bool|void
		 * @throws NotImplementedException
		 */
		public static function previewFromRecyclebin(Entity $entity)
		{
			throw new NotImplementedException("Coming soon...");
		}

		/**
		 * @return array
		 */
		public static function getNotifyMessages()
		{
			return [
				'NOTIFY' => [
					'RESTORE' => Loc::getMessage('TASKS_TEMPLATE_RECYCLEBIN_RESTORE_MESSAGE'),
					'REMOVE' => Loc::getMessage('TASKS_TEMPLATE_RECYCLEBIN_REMOVE_MESSAGE'),
				],
				'CONFIRM' => [
					'RESTORE' => Loc::getMessage('TASKS_TEMPLATE_RECYCLEBIN_RESTORE_CONFIRM'),
					'REMOVE' => Loc::getMessage('TASKS_TEMPLATE_RECYCLEBIN_REMOVE_CONFIRM')
				]
			];
		}

		/**
		 * @return array
		 * @throws \Bitrix\Main\ObjectPropertyException
		 * @throws \Bitrix\Main\SystemException
		 */
		public static function getAdditionalData(): array
		{
			return [
				'LIMIT_DATA' => [
					'RESTORE' => [
						'DISABLE' => TaskLimit::isLimitExceeded() || !\Bitrix\Tasks\Integration\Bitrix24::checkFeatureEnabled(\Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary::TASKS_RECYCLEBIN),
						'SLIDER_CODE' => 'limit_tasks_recycle_bin_restore',
					],
				],
			];
		}
	}
}
