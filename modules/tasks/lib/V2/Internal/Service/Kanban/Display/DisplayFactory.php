<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Kanban\Display;

use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\UserSettings\KanbanUserSettings;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Internals\Registry\GroupRegistry;
use Bitrix\Tasks\Internals\Trait\SingletonTrait;
use Bitrix\Tasks\Scrum\Service\EpicService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\CrmService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\Display\AbstractDisplayService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\UserSettings\AbstractUserSettings;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\CheckListService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\Display\ScrumDisplayService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\Display\KanbanDisplayService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\FileService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\MemberService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\Service\TagService;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\UserSettings\ScrumUserSettings;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\ViewModeType;

class DisplayFactory
{
	use SingletonTrait;

	public function createDisplayService(ViewModeType $viewModeType, int $userId): AbstractDisplayService
	{
		return match ($viewModeType)
		{
			ViewModeType::Scrum => $this->createScrumDisplayService($userId),
			default => $this->createKanbanDisplayService($viewModeType),
		};
	}

	public function createUserSettings(ViewModeType $viewModeType): AbstractUserSettings
	{
		return match ($viewModeType)
		{
			ViewModeType::Scrum => new ScrumUserSettings(),
			default => new KanbanUserSettings($viewModeType),
		};
	}

	private function createScrumDisplayService(int $userId): ScrumDisplayService
	{
		$userSettings = new ScrumUserSettings();
		$memberService = new MemberService();
		$checkListService = new CheckListService();
		$fileService = new FileService();
		$tagService = new TagService();
		$crmService = new CrmService();
		$itemService = new ItemService();
		$epicService = new EpicService($userId);

		return new ScrumDisplayService(
			epicService: $epicService,
			itemService: $itemService,
			userSettings: $userSettings,
			memberService: $memberService,
			checkListService: $checkListService,
			fileService: $fileService,
			tagService: $tagService,
			crmService: $crmService,
		);
	}

	private function createKanbanDisplayService(ViewModeType $viewModeType): KanbanDisplayService
	{
		$userSettings = new KanbanUserSettings($viewModeType);
		$memberService = new MemberService();
		$checkListService = new CheckListService();
		$fileService = new FileService();
		$tagService = new TagService();
		$crmService = new CrmService();
		$flowRegistry = FlowRegistry::getInstance();
		$groupRegistry = GroupRegistry::getInstance();

		return new KanbanDisplayService(
			groupRegistry: $groupRegistry,
			flowRegistry: $flowRegistry,
			userSettings: $userSettings,
			memberService: $memberService,
			checkListService: $checkListService,
			fileService: $fileService,
			tagService: $tagService,
			crmService: $crmService,
		);
	}
}