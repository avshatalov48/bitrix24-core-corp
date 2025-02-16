<?php

namespace Bitrix\Crm\Activity\Provider\Bizproc;

use Bitrix\Crm\Activity\TaskPingSettingsProvider;
use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\Badge\SourceIdentifier;
use Bitrix\Crm\Badge\Type\BizprocWorkflowStatus;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Config\Option;

final class Task extends Base
{
	private const PROVIDER_ID = 'CRM_BIZPROC_TASK';
	private const PROVIDER_TYPE_ID = 'BIZPROC_TASK';

	public static function getId()
	{
		return self::PROVIDER_ID;
	}

	public static function getProviderTypeId(): string
	{
		return self::PROVIDER_TYPE_ID;
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_BIZPROC_TASK_NAME') ?? '';
	}

	public static function getTypes()
	{
		return [
			[
				'NAME' => self::getName(),
				'PROVIDER_ID' => self::PROVIDER_ID,
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID,
			],
		];
	}

	public static function getDefaultPingOffsets(array $params = []): array
	{
		return TaskPingSettingsProvider::DEFAULT_OFFSETS;
	}

	public function find(int $taskId, int $responsibleId = 0): array
	{
		if ($taskId <= 0)
		{
			return [];
		}

		$query =
			(\Bitrix\Crm\ActivityTable::query())
				->setSelect(['ID', 'SETTINGS'])
				->where('ASSOCIATED_ENTITY_ID', $taskId)
				->where('TYPE_ID', \CCrmActivityType::Provider)
				->where('PROVIDER_ID', self::PROVIDER_ID)
				->where('COMPLETED', 'N')
		;
		if ($responsibleId > 0)
		{
			$query->where('RESPONSIBLE_ID', $responsibleId);
		}

		return $query->exec()->fetchAll();
	}

	public function findByWorkflowId(string $workflowId): array
	{
		if (!$workflowId)
		{
			return [];
		}

		$query =
			(\Bitrix\Crm\ActivityTable::query())
				->setSelect(['ID', 'SETTINGS'])
				->where('ORIGIN_ID', $workflowId)
				->where('TYPE_ID', \CCrmActivityType::Provider)
				->where('PROVIDER_ID', self::PROVIDER_ID)
				->where('COMPLETED', 'N')
		;

		return $query->exec()->fetchAll();
	}

	public static function checkCompletePermission($entityId, array $activity, $userId)
	{
		$settings = $activity['SETTINGS'] ?? [];
		$status = $settings['STATUS'] ?? \Bitrix\Crm\Service\Timeline\Item\Activity\Bizproc\Task::TASK_STATUS_DONE;
		if ($status === \Bitrix\Crm\Service\Timeline\Item\Activity\Bizproc\Task::TASK_STATUS_RUNNING)
		{
			$taskId = (int)($settings['TASK_ID'] ?? 0);
			$responsibleId = (int)($activity['RESPONSIBLE_ID'] ?? 0);
			if ($taskId > 0 && $responsibleId > 0 && Loader::includeModule('bizproc'))
			{
				$users = \CBPTaskService::getTaskUsers($taskId)[$taskId] ?? [];
				foreach ($users as $user)
				{
					if (
						(int)$user['USER_ID'] === $responsibleId
						&& \CBPTaskUserStatus::Waiting === (int)$user['STATUS']
					)
					{
						self::setCompletionDeniedError(
							Loc::getMessage('CRM_ACTIVITY_PROVIDER_BIZPROC_TASK_COMPLETE_ACCESS_DENIED') ?? ''
						);

						return false;
					}
				}
			}
		}

		return parent::checkCompletePermission($entityId,$activity, $userId);
	}

	public static function syncBadges(int $activityId, array $activityFields, array $bindings): void
	{
		$settings = $activityFields['SETTINGS'] ?? [];
		if (!is_array($settings))
		{
			return;
		}

		$badge =
			Container::getInstance()
				->getBadge(Badge::BIZPROC_WORKFLOW_STATUS_TYPE, BizprocWorkflowStatus::RUNNING_TASK_VALUE)
		;
		$sourceIdentifier = new SourceIdentifier(
			SourceIdentifier::CRM_OWNER_TYPE_PROVIDER,
			\CCrmOwnerType::Activity,
			$activityId
		);

		$status = $settings['STATUS'] ?? \Bitrix\Crm\Service\Timeline\Item\Activity\Bizproc\Task::TASK_STATUS_DONE;
		foreach ($bindings as $singleBinding)
		{
			$itemIdentifier = new ItemIdentifier((int)$singleBinding['OWNER_TYPE_ID'], (int)$singleBinding['OWNER_ID']);
			if ($status === \Bitrix\Crm\Service\Timeline\Item\Activity\Bizproc\Task::TASK_STATUS_RUNNING)
			{
				$badge->bind($itemIdentifier, $sourceIdentifier);
			}
			else
			{
				$badge->unbind($itemIdentifier, $sourceIdentifier);
			}
		}
	}

	public static function checkFields($action, &$fields, $id, $params = null)
	{
		if (isset($fields['COMPLETED']) && $fields['COMPLETED'] === 'Y') // fix faces
		{
			$prevFields = $params && isset($params['PREVIOUS_FIELDS']) ? $params['PREVIOUS_FIELDS'] : [];
			$settings = $fields['SETTINGS'] ?? ($prevFields['SETTINGS'] ?? null);
			$workflowId = $settings['WORKFLOW_ID'] ?? ($prevFields['ORIGIN_ID'] ?? null);
			if ($settings && $workflowId)
			{
				$taskId = $settings['TASK_ID'] ?? 0;
				$workflow = new \Bitrix\Crm\Timeline\Bizproc\Data\Workflow($workflowId);
				$settings['FACES'] = $workflow->getFaces($taskId);
				$fields['SETTINGS'] = $settings;
			}
		}

		return new Result();
	}

	public static function getCustomViewLink(array $activityFields): ?string
	{
		if ((int)$activityFields['ASSOCIATED_ENTITY_ID'] > 0)
		{
			return \CComponentEngine::MakePathFromTemplate(
				'/company/personal/bizproc/#TASK_ID#/?USER_ID=#USER_ID#',
				[
					'TASK_ID' => $activityFields['ASSOCIATED_ENTITY_ID'],
					'USER_ID' => $activityFields['RESPONSIBLE_ID']
				]
			);
		}

		return parent::getCustomViewLink($activityFields);
	}
}
