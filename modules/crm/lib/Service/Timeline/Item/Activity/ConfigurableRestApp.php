<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ActionDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlockDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\FooterButtonDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\LayoutDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\MenuItemDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\TagDto;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Crm\Service\Timeline\Layout\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItem;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItemDelimiter;
use Bitrix\Crm\Service\Timeline\Layout\Factory\RestAppConfigurable\ActionFactory;
use Bitrix\Crm\Service\Timeline\Layout\Factory\RestAppConfigurable\ContentBlockFactory;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Rest\AppTable;
use Bitrix\Main\ArgumentException;

class ConfigurableRestApp extends Activity
{
	private ?LayoutDto $layoutDto = null;
	private ?int $restAppId = null;
	protected ActionFactory $actionFactory;
	protected ContentBlockFactory $contentBlockFactory;

	protected function getActionFactory(): ActionFactory
	{
		if (!isset($this->actionFactory))
		{
			$this->actionFactory = new ActionFactory($this, $this->getRestAppClientId(), $this->getRestAppId());
		}

		return $this->actionFactory;
	}

	protected function getContentBlocksFactory(): ContentBlockFactory
	{
		if (!isset($this->contentBlockFactory))
		{
			$this->contentBlockFactory = new ContentBlockFactory($this, $this->getActionFactory());
		}

		return $this->contentBlockFactory;
	}

	public function needShowRestAppLayoutBlocks(): bool
	{
		return false;
	}

	public static function isModelValid(\Bitrix\Crm\Service\Timeline\Item\Model $model): bool
	{
		try
		{
			$layout = Json::decode($model->getAssociatedEntityModel()?->get('PROVIDER_DATA'));

			return !empty($layout);
		}
		catch (ArgumentException)
		{
			return false;
		}
	}

	protected function getActivityTypeId(): string
	{
		return 'ConfigurableRestApp';
	}

	public function getIconCode(): ?string
	{
		$icon = $this->getLayoutDto()->icon;
		if ($this->isValidDto($icon))
		{
			return $icon->code ?? '';
		}

		return '';
	}

	/**
	 * Icon of timeline record
	 *
	 */
	public function getIcon(): ?Icon
	{
		$icon = parent::getIcon();
		if (!$icon)
		{
			return null;
		}
		$iconData = \Bitrix\Crm\Service\Timeline\Layout\Common\Icon::initFromCode($icon->getCode())->getData();
		if (!$iconData) // wrong icon code was provided
		{
			return null;
		}
		if (!$iconData['isSystem'])
		{
			$icon->setBackgroundUri($iconData['fileUri']);
		}

		return $icon;
	}

	public function getTitle(): string
	{
		$header = $this->getLayoutDto()->header;
		if ($header)
		{
			return $header->title ?? '';
		}

		return '';
	}

	public function getTitleAction(): ?Action
	{
		$header = $this->getLayoutDto()->header;
		if ($header && $this->isValidDto($header->titleAction))
		{
			return $this->createAction($header->titleAction);
		}

		return null;
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		$body = $this->getLayoutDto()->body;
		if (!$body || !$body->logo)
		{
			return null;
		}

		$logoCode = $body->logo->code;

		if (!$logoCode)
		{
			return null;
		}

		$logo = Layout\Common\Logo::getInstance($logoCode)
			->createLogo()
		;
		if (!$logo)
		{
			return null;
		}

		return $logo->setAction($this->createAction($body->logo->action));
	}

	public function getContentBlocks(): array
	{
		if (!$this->getLayoutDto()->body)
		{
			return [];
		}
		$blocks = $this->getLayoutDto()->body->blocks;
		if (empty($blocks))
		{
			return [];
		}

		$result = [];
		foreach ($blocks as $blockId => $blockDto)
		{
			$block = $this->createContentBlock($blockDto);
			if ($block)
			{
				$result[(string)$blockId] = $block;
			}
		}

		return $result;
	}

	public function getButtons(): array
	{
		if (!$this->getLayoutDto()->footer)
		{
			return [];
		}
		$buttons = $this->getLayoutDto()->footer->buttons;
		if (empty($buttons))
		{
			return [];
		}

		$result = [];
		foreach ($buttons as $buttonId => $buttonDto)
		{
			$button = $this->createFooterButton($buttonDto);
			if ($button)
			{
				$result[(string)$buttonId] = $button;
			}
		}

		return $result;
	}

	public function getMenuItems(): array
	{
		$needAddPinMenuItem = true;
		$needAddPostponeMenuItem = true;
		$needAddDeleteMenuItem = true;
		$extraMenuItems = [];
		if ($this->getLayoutDto()->footer && $this->getLayoutDto()->footer->menu)
		{
			$menuDto = $this->getLayoutDto()->footer->menu;
			$needAddPinMenuItem = $menuDto->showPinItem ?? true;
			$needAddPostponeMenuItem = $menuDto->showPostponeItem ?? true;
			$needAddDeleteMenuItem = $menuDto->showDeleteItem ?? true;
			$extraMenuItems = $menuDto->items ?? [];
		}
		if (!$this->getDeadline() || !$this->isScheduled())
		{
			$needAddPostponeMenuItem = false;
		}
		if ($this->isScheduled())
		{
			$needAddPinMenuItem = false;
		}

		$result = [];

		$extraMenuItemsAdded = false;
		foreach ($extraMenuItems as $menuId => $menuItemDto)
		{
			$menuItem = $this->createMenuItem($menuItemDto);
			if ($menuItem)
			{
				$result[(string)$menuId] = $menuItem;
				$extraMenuItemsAdded = true;
			}
		}
		if ($extraMenuItemsAdded)
		{
			$result['delim1'] = (new MenuItemDelimiter())->setSort(9000);
		}
		$stdMenuItemsAdded = false;
		if ($needAddPinMenuItem)
		{
			$this->addPinMenuItems($result);
			$stdMenuItemsAdded = true;
		}
		if ($needAddPostponeMenuItem)
		{
			$postponeMenuItem = $this->createPostponeMenuItem($this->getActivityId());
			if ($postponeMenuItem)
			{
				$result['postpone'] = $postponeMenuItem;
				$stdMenuItemsAdded = true;
			}
		}
		if ($needAddDeleteMenuItem)
		{
			$deleteMenuItem = $this->createDeleteMenuItem($this->getActivityId());
			if ($deleteMenuItem)
			{
				$result['delete'] = $deleteMenuItem;
				$stdMenuItemsAdded = true;
			}
		}
		if ($stdMenuItemsAdded)
		{
			$result['delim2'] = (new MenuItemDelimiter())->setSort(10000);
		}
		$aboutAction = $this->createOpenAppAction();
		$aboutAction->addActionParamString('context', 'aboutMenuItem');

		$result['aboutApp'] = (new MenuItem(Loc::getMessage('CRM_TIMELINE_CONFIGURABLE_APP_MENU_ITEM_ABOUT')))
			->setAction($aboutAction)
			->setSort(10001)
			->setScopeWeb()
		;

		return $result;
	}

	public function getTags(): ?array
	{
		$header = $this->getLayoutDto()->header;
		if (!$header || empty($header->tags))
		{
			return [];
		}

		$result = [];
		foreach ($header->tags as $tagId => $tagDto)
		{
			$tag = $this->createTag($tagDto);
			if ($tag)
			{
				$result[(string)$tagId] = $tag;
			}
		}
		return $result;
	}

	public function needShowNotes(): bool
	{
		return (!$this->getLayoutDto()->footer || $this->getLayoutDto()->footer->showNote !== false);
	}

	private function getLayoutDto(): LayoutDto
	{
		if (!$this->layoutDto)
		{
			try
			{
				$layout = Json::decode($this->getAssociatedEntityModel()->get('PROVIDER_DATA'));
			}
			catch (ArgumentException)
			{
				$layout = [];
			}

			$this->layoutDto = new LayoutDto((array)$layout);
		}

		return $this->layoutDto;
	}

	private function createTag(?TagDto $tagDto): ?Tag
	{
		if (!$this->isValidDto($tagDto))
		{
			return null;
		}

		return (new Tag($tagDto->title, $tagDto->type))
			->setAction($this->createAction($tagDto->action))
		;
	}

	private function createContentBlock(?ContentBlockDto $contentBlockDto): ?ContentBlock
	{
		return $this->getContentBlocksFactory()->createByDto($contentBlockDto);
	}

	private function createFooterButton(?FooterButtonDto $buttonDto): ?Button
	{
		if (!$this->isValidDto($buttonDto))
		{
			return null;
		}

		return (new Button($buttonDto->title, $buttonDto->type))
			->setScope($buttonDto->scope)
			->setHideIfReadonly($buttonDto->hideIfReadonly)
			->setAction($this->createAction($buttonDto->action))
		;
	}

	private function createMenuItem(?MenuItemDto $menuItemDto): ?MenuItem
	{
		if (!$this->isValidDto($menuItemDto))
		{
			return null;
		}

		return (new MenuItem($menuItemDto->title))
			->setScope($menuItemDto->scope)
			->setHideIfReadonly($menuItemDto->hideIfReadonly)
			->setAction($this->createAction($menuItemDto->action))
		;
	}

	private function createAction(?ActionDto $actionDto): ?Action
	{
		if (!$this->isValidDto($actionDto))
		{
			return null;
		}

		return $this->getActionFactory()->createByDto($actionDto);
	}

	private function createOpenAppAction(): Action\JsEvent
	{
		return $this->getActionFactory()->createOpenAppAction();
	}

	private function isValidDto(?Dto $dto): bool
	{
		if (!$dto)
		{
			return false;
		}

		return !$dto->hasValidationErrors();
	}

	private function getProviderParams(): array
	{
		return $this->getAssociatedEntityModel()->get('PROVIDER_PARAMS');
	}

	private function getRestAppClientId(): ?string
	{
		$clientId = $this->getProviderParams()['clientId'];

		return $clientId ? (string)$clientId : null;
	}

	private function getRestAppId(): int
	{
		if ($this->restAppId === null)
		{
			$clientId = $this->getRestAppClientId();
			if ($clientId && Loader::includeModule('rest'))
			{
				$app = AppTable::getByClientId($clientId);
				$this->restAppId = (int)($app['ID'] ?? 0);
			}
		}

		return $this->restAppId;
	}
}
