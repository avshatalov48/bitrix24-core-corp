<?php

namespace Bitrix\Tasks\Components\Kanban;

use Bitrix\Tasks\Components\Kanban\Services\CheckList;
use Bitrix\Tasks\Components\Kanban\Services\Crm;
use Bitrix\Tasks\Components\Kanban\Services\Files;
use Bitrix\Tasks\Components\Kanban\Services\Members;
use Bitrix\Tasks\Components\Kanban\Services\Tags;
use Bitrix\Tasks\Internals\Registry\GroupRegistry;
use Bitrix\Main\Type\DateTime;

class DisplayService
{
	private bool $isScrum;
	private UserSettings $kanbanUserSettings;
	private Files $files;
	private Tags $tags;
	private Members $members;
	private CheckList $checkList;

	public function __construct(
		bool $isScrum,
		UserSettings $customSettings,
		Files $files,
		Tags $tags,
		Members $members,
		CheckList $checkList
	)
	{
		$this->isScrum = $isScrum;
		$this->kanbanUserSettings = $customSettings;
		$this->files = $files;
		$this->tags = $tags;
		$this->members = $members;
		$this->checkList = $checkList;
	}

	public function fillCheckList(array $items): array
	{
		return $this->required($this->kanbanUserSettings->getCheckList()->getCode())
			? $this->checkList->getCheckList($items)
			: $items;
	}

	public function fillFiles(array $items): array
	{
		return $this->required($this->kanbanUserSettings->getFiles()->getCode())
			? $this->files->getFiles($items)
			: $items;
	}

	public function fillTags(array $items): array
	{
		return $this->required($this->kanbanUserSettings->getTags()->getCode())
			? $this->tags->getTags($items)
			: $items;
	}

	public function fillId(int $id): ?array
	{
		$idField = $this->kanbanUserSettings->getId();
		return $this->required($idField->getCode())
			? ['value' => $id, 'label' => $idField->getTitle()]
			: null;
	}

	public function fillProject(int $projectId): ?array
	{
		$projectField = $this->kanbanUserSettings->getProject();
		if (!$this->required($projectField->getCode()))
		{
			return null;
		}
		$project = GroupRegistry::getInstance()->get($projectId);
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

	public function fillTimeSpent(int $seconds): ?array
	{
		$timeSpentField = $this->kanbanUserSettings->getTimeSpent();
		return $this->required($timeSpentField->getCode()) && $seconds
			? ['value' => \Bitrix\Tasks\UI::formatTimeAmount($seconds), 'label' => $timeSpentField->getTitle()]
			: null;
	}

	public function fillDateStart(string $date): ?array
	{
		$date = $date !== '' ? (new DateTime($date))->format(\Bitrix\Tasks\UI::getDateTimeFormat()) : null;
		$dateStartField = $this->kanbanUserSettings->getDateStarted();
		return $this->required($dateStartField->getCode()) && $date
			? ['value' => $date, 'label' => $dateStartField->getTitle()]
			: null;
	}

	public function fillDateFinishPlan(string $date): ?array
	{
		$date = $date !== '' ? (new DateTime($date))->format(\Bitrix\Tasks\UI::getDateTimeFormat()) : null;
		$dateFinishedField = $this->kanbanUserSettings->getDateFinished();
		return $this->required($dateFinishedField->getCode()) && $date
			? ['value' => $date, 'label' => $dateFinishedField->getTitle()]
			: null;
	}

	public function fillDeadLineVisibility(): string
	{
		return $this->required($this->kanbanUserSettings->getDeadLine()->getCode())
			? ''
			: 'hidden';
	}

	public function fillTitle(string $title): string
	{
		return $this->required($this->kanbanUserSettings->getTitle()->getCode())
			? $title
			: '';
	}

	public function fillAuditors(array $ids): ?array
	{
		$auditorsField = $this->kanbanUserSettings->getAuditors();
		return $this->required($auditorsField->getCode())
			? ['collection' => array_values($this->members->getByIds($ids)), 'label' => $auditorsField->getTitle()]
			: null;
	}

	public function fillAccomplices(array $ids): ?array
	{
		$accomplicesField = $this->kanbanUserSettings->getAccomplices();
		return $this->required($accomplicesField->getCode())
			? ['collection' => array_values($this->members->getByIds($ids)), 'label' => $accomplicesField->getTitle()]
			: null;
	}

	public function fillMark(string $value): ?array
	{
		$markField = $this->kanbanUserSettings->getMark();
		return $this->required($markField->getCode())
			? ['value' => $value, 'label' => $markField->getTitle()]
			: null;
	}

	public function fillCrmData(array $item): ?array
	{
		$crmField = $this->kanbanUserSettings->getCrm();
		if (!$this->required($crmField->getCode()))
		{
			return null;
		}

		return [
			'collection' => (new Crm())->getData($item),
			'label' => $crmField->getTitle()
		];
	}

	public function required(string $field): bool
	{
		if ($this->isScrum && $this->kanbanUserSettings->isFieldDefault($field))
		{
			return true;
		}
		if (!$this->kanbanUserSettings->hasSelectedCustomFields() && $this->kanbanUserSettings->isFieldDefault($field))
		{
			return true;
		}
		if (!$this->isScrum && $this->kanbanUserSettings->isFieldSelected($field))
		{
			return true;
		}

		return false;
	}
}