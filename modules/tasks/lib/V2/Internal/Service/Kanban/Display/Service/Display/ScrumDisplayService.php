<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\Display;

use Bitrix\Tasks\Scrum\Form\EpicForm;
use Bitrix\Tasks\Scrum\Service\EpicService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\CheckListService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\CrmService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\Display\AbstractDisplayService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\FileService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\MemberService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\TagService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\UserSettings\ScrumUserSettings;

class ScrumDisplayService extends AbstractDisplayService
{
	private array $scrumItems;

	public function __construct(
		private readonly EpicService $epicService,
		private readonly ItemService $itemService,
		ScrumUserSettings $userSettings,
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

	public function fillBatch(array $items): array
	{
		$this->scrumItems = $this->itemService->getItemsBySourceIds(array_keys($items));

		$items = parent::fillBatch($items);
		$items = $this->fillEpic($items);
		$items = $this->fillVisibilitySubtasks($items);

		return $this->fillStoryPoints($items);
	}

	private function fillEpic(array $items): array
	{
		$epicsField = $this->userSettings->getEpic();

		if (!$this->userSettings->required($epicsField))
		{
			return $items;
		}

		$epicIds = [];
		foreach ($this->scrumItems as $item)
		{
			$epicIds[] = $item->getEpicId();
		}

		$epicIds = array_filter(array_unique($epicIds));

		$epicsList = [];
		if ($epicIds)
		{
			$queryResult = $this->epicService->getList(
				[],
				['ID' => $epicIds],
				['ID' => 'ASC'],
			);

			while ($data = $queryResult->fetch())
			{
				$epic = new EpicForm();

				$epic->fillFromDatabase($data);

				$epicsList[$epic->getId()] = $epic->toArray();
			}
		}

		foreach ($this->scrumItems as $item)
		{
			$items[$item->getSourceId()]['data']['epic'] = $epicsList[$item->getEpicId()] ?? [];
		}

		return $items;
	}

	private function fillStoryPoints(array $items): array
	{
		$storyPointsField = $this->userSettings->getStoryPoints();

		if (!$this->userSettings->required($storyPointsField))
		{
			return $items;
		}

		foreach ($this->scrumItems as $item)
		{
			$items[$item->getSourceId()]['data']['storyPoints'] = $item->getStoryPoints();
		}

		return $items;
	}

	private function fillVisibilitySubtasks(array $items): array
	{
		foreach ($this->scrumItems as $item)
		{
			$items[$item->getSourceId()]['isVisibilitySubtasks'] = $item->getInfo()->isVisibilitySubtasks() ? 'Y' : 'N';
		}

		return $items;
	}
}