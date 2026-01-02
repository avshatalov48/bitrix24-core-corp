<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\Display;

use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\CrmService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\UserSettings\AbstractUserSettings;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\MemberService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\CheckListService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\FileService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\TagService;

abstract class AbstractDisplayService
{
	public function __construct(
		protected readonly AbstractUserSettings $userSettings,
		protected readonly MemberService $memberService,
		protected readonly CheckListService $checkListService,
		protected readonly FileService $fileService,
		protected readonly TagService $tagService,
		protected readonly CrmService $crmService,
	)
	{
	}

	public function fill(array $item, int $userId): array
	{
		return [
			'name' => $this->fillTitle($item['TITLE']),
			'item_fields' => [
				$this->fillId((int)($item['ID'] ?? 0)),
				$this->fillAuditors((array)($item['AUDITORS'] ?? [])),
				$this->fillAccomplices((array)($item['ACCOMPLICES'] ?? [])),
				$this->fillMark((string)($item['MARK_PROP_MESSAGE'] ?? '')),
				$this->fillTimeSpent((int)($item['TIME_SPENT_IN_LOGS'] ?? 0)),
				$this->fillCrmData($item),
			],
		];
	}

	public function fillBatch(array $items): array
	{
		$items = $this->fillTags($items);
		$items = $this->fillFiles($items);

		return $this->fillCheckList($items);
	}

	public function getPopupSections(int $userId): array
	{
		return $this->userSettings->getPopupSections($userId);
	}

	public function saveUserSelectedFields(array $selectedFields): bool
	{
		return $this->userSettings->saveUserSelectedFields($selectedFields);
	}

	private function fillId(int $id): ?array
	{
		$idField = $this->userSettings->getId();

		if ($this->userSettings->required($idField))
		{
			return [
				'value' => $id,
				'label' => $idField->getTitle(),
			];
		}

		return null;
	}

	private function fillTitle(string $title): string
	{
		$titleField = $this->userSettings->getTitle();

		if ($this->userSettings->required($titleField))
		{
			return $title;
		}

		return '';
	}

	private function fillAuditors(array $ids): ?array
	{
		$auditorsField = $this->userSettings->getAuditors();

		if ($this->userSettings->required($auditorsField))
		{
			return [
				'collection' => array_values($this->memberService->getByIds($ids)),
				'label' => $auditorsField->getTitle(),
			];
		}

		return null;
	}

	private function fillAccomplices(array $ids): ?array
	{
		$accomplicesField = $this->userSettings->getAccomplices();

		if ($this->userSettings->required($accomplicesField))
		{
			return [
				'collection' => array_values($this->memberService->getByIds($ids)),
				'label' => $accomplicesField->getTitle(),
			];
		}

		return null;
	}

	private function fillCheckList(array $items): array
	{
		$checkListField = $this->userSettings->getCheckList();

		if ($this->userSettings->required($checkListField))
		{
			return $this->checkListService->getCheckList($items);
		}

		return $items;
	}

	private function fillFiles(array $items): array
	{
		$filesField = $this->userSettings->getFiles();

		if ($this->userSettings->required($filesField))
		{
			return $this->fileService->getFiles($items);
		}

		return $items;
	}

	private function fillTags(array $items): array
	{
		$tagsField = $this->userSettings->getTags();

		if ($this->userSettings->required($tagsField))
		{
			return $this->tagService->getTags($items);
		}

		return $items;
	}

	private function fillMark(string $value): ?array
	{
		$markField = $this->userSettings->getMark();

		if ($this->userSettings->required($markField))
		{
			return [
				'value' => $value,
				'label' => $markField->getTitle(),
			];
		}

		return null;
	}

	private function fillTimeSpent(int $seconds): ?array
	{
		$timeSpentField = $this->userSettings->getTimeSpent();

		if ($this->userSettings->required($timeSpentField) && $seconds)
		{
			return [
				'value' => \Bitrix\Tasks\UI::formatTimeAmount($seconds),
				'label' => $timeSpentField->getTitle(),
			];
		}

		return null;
	}

	private function fillCrmData(array $item): ?array
	{
		$crmField = $this->userSettings->getCrm();

		if ($this->userSettings->required($crmField))
		{
			return [
				'collection' => $this->crmService->getData($item),
				'label' => $crmField->getTitle(),
			];
		}

		return null;
	}
}