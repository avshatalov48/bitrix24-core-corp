<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\Display;

use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\Display\AbstractDisplayService;
use Bitrix\Main\Text\Emoji;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\CrmService;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Internals\Registry\GroupRegistry;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Internals\Routes\RouteDictionary;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\CheckListService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\FileService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\MemberService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\TagService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\UserSettings\KanbanUserSettings;

class KanbanDisplayService extends AbstractDisplayService
{
	public function __construct(
		private readonly GroupRegistry $groupRegistry,
		private readonly FlowRegistry $flowRegistry,
		KanbanUserSettings $userSettings,
		MemberService $memberService,
		CheckListService $checkListService,
		FileService $fileService,
		TagService $tagService,
		CrmService $crmService,
	)
	{
		parent::__construct(
			userSettings: $userSettings,
			memberService: $memberService,
			checkListService: $checkListService,
			fileService: $fileService,
			tagService: $tagService,
			crmService: $crmService,
		);
	}

	public function fill(array $item, int $userId): array
	{
		return array_merge_recursive(
			parent::fill($item, $userId),
			[
				'deadline_visibility' => $this->fillDeadLineVisibility(),
				'item_fields' => [
					$this->fillProject((int)($item['GROUP_ID'] ?? 0), $userId),
					$this->fillFlow((int)($item['FLOW_ID'] ?? 0), $userId),
					$this->fillDateStart((string)($item['DATE_START'] ?? '')),
					$this->fillDateFinishPlan((string)($item['END_DATE_PLAN'] ?? '')),
				],
			]
		);
	}

	private function fillProject(int $projectId, int $userId): ?array
	{
		$projectField = $this->userSettings->getProject($userId);

		if (!$this->userSettings->required($projectField))
		{
			return null;
		}

		$project = $this->groupRegistry->get($projectId);
		$collection = [];

		if (isset($project['ID'], $project['NAME']))
		{
			$path = \COption::GetOptionString(
				'tasks',
				'paths_task_group',
				'/workgroups/group/#group_id#/tasks/',
			);
			$path = str_replace('#group_id#', $project['ID'], $path);
			$collection[] = [
				'name' => $project['NAME'],
				'url' => $path,
			];
		}

		return [
			'collection' => $collection,
			'label' => $projectField->getTitle(),
		];
	}

	private function fillFlow(int $flowId, int $userId): ?array
	{
		$flowField = $this->userSettings->getFlow();

		if (!$this->userSettings->required($flowField) || $flowId <= 0 || !FlowFeature::isOn())
		{
			return null;
		}

		$flow = $this->flowRegistry->get($flowId);
		$collection = [];

		if (isset($flow['ID'], $flow['NAME']))
		{
			$tasksPath = str_replace('#user_id#', (string)$userId, RouteDictionary::PATH_TO_USER_TASKS_LIST);

			$flowUri = new Uri($tasksPath . 'flow/');

			$demoSuffix = \Bitrix\Tasks\Flow\FlowFeature::isFeatureEnabledByTrial() ? 'Y' : 'N';

			$flowUri->addParams([
				'apply_filter' => 'Y',
				'ID_numsel' => 'exact',
				'ID_from' => $flowId,
				'ta_cat' => 'flows',
				'ta_sec' => 'tasks',
				'ta_sub' => \Bitrix\Tasks\Helper\Analytics::SUB_SECTION['kanban'],
				'ta_el' => \Bitrix\Tasks\Helper\Analytics::ELEMENT['title_click'],
				'p1' => 'isDemo_' . $demoSuffix,
			]);

			$collection[] = [
				'name' => Emoji::decode($flow['NAME']),
				'url' => $flowUri->getUri(),
			];
		}

		return [
			'collection' => $collection,
			'label' => $flowField->getTitle(),
		];
	}

	private function fillDateStart(string $date): ?array
	{
		if ($date !== '')
		{
			$date = (new DateTime($date))->format(\Bitrix\Tasks\UI::getDateTimeFormat());
		}
		else
		{
			$date = null;
		}

		$dateStartField = $this->userSettings->getDateStarted();

		if ($this->userSettings->required($dateStartField) && $date)
		{
			return [
				'value' => $date,
				'label' => $dateStartField->getTitle(),
			];
		}

		return null;
	}

	private function fillDateFinishPlan(string $date): ?array
	{
		if ($date !== '')
		{
			$date = (new DateTime($date))->format(\Bitrix\Tasks\UI::getDateTimeFormat());
		}
		else
		{
			$date = null;
		}

		$dateFinishedField = $this->userSettings->getDateFinished();

		if ($this->userSettings->required($dateFinishedField) && $date)
		{
			return [
				'value' => $date,
				'label' => $dateFinishedField->getTitle(),
			];
		}

		return null;
	}

	private function fillDeadLineVisibility(): string
	{
		$deadlineField = $this->userSettings->getDeadLine();

		if ($this->userSettings->required($deadlineField))
		{
			return '';
		}

		return 'hidden';
	}
}