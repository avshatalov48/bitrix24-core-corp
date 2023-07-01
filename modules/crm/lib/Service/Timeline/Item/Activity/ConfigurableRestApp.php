<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ActionDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlockDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\FooterButtonDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\LayoutDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\MenuItemDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\TagDto;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlock\DeadlineDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlock\LineOfBlocksDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlock\LinkDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlock\TextDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlock\WithTitleDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\EventHandler;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Crm\Service\Timeline\Layout\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItem;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItemDelimiter;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Rest\AppTable;

class ConfigurableRestApp extends Activity
{
	private ?LayoutDto $layoutDto = null;
	private ?int $restAppId = null;

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

		return Layout\Common\Logo::getInstance($logoCode)
			->createLogo()
			->setAction($this->createAction($body->logo->action))
		;
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
			$layout = Json::decode($this->getAssociatedEntityModel()->get('PROVIDER_DATA'));
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
		if (!$this->isValidDto($contentBlockDto))
		{
			return null;
		}

		$properties = $contentBlockDto->properties ?? new \stdClass();

		switch ($contentBlockDto->type)
		{
			case ContentBlockDto::TYPE_TEXT:
				/** @var $properties TextDto */
				return (new ContentBlock\Text())
					->setValue($properties->value)
					->setIsMultiline($properties->multiline)
					->setTitle($properties->title)
					->setIsBold($properties->bold)
					->setFontSize($properties->size)
					->setColor($properties->color)
					->setScope($properties->scope)
				;

			case  ContentBlockDto::TYPE_LARGE_TEXT:
				return (new ContentBlock\EditableDescription())
					->setText($properties->value)
					->setEditable(false)
					->setHeight(ContentBlock\EditableDescription::HEIGHT_LONG)
				;

			case ContentBlockDto::TYPE_LINK:
				/** @var $properties LinkDto */
				return (new ContentBlock\Link())
					->setValue($properties->text)
					->setIsBold($properties->bold)
					->setAction($this->createAction($properties->action))
					->setScope($properties->scope)
				;

			case ContentBlockDto::TYPE_DEADLINE:
				/** @var $properties DeadlineDto */
				if ($this->getDeadline())
				{
					$readonly = !$this->isScheduled() || ($properties->readonly ?? false);

					return (new ContentBlock\EditableDate())
						->setStyle(ContentBlock\EditableDate::STYLE_PILL)
						->setDate($this->getDeadline())
						->setAction($readonly ? null : $this->getChangeDeadlineAction())
						->setBackgroundColor($readonly ? null : ContentBlock\EditableDate::BACKGROUND_COLOR_WARNING)
						->setScope($properties->scope)
					;
				}

				return null;

			case ContentBlockDto::TYPE_WITH_TITLE:
				/** @var $properties WithTitleDto */
				if (!$properties->block || !$this->isValidChildContentBlock($properties->block))
				{
					return null;
				}

				$childBlock = $this->createContentBlock($properties->block);
				if (!$childBlock)
				{
					return null;
				}

				return (new ContentBlock\ContentBlockWithTitle())
					->setTitle($properties->title)
					->setWordWrap(true)
					->setInline($properties->inline)
					->setContentBlock($childBlock)
					->setScope($properties->scope)
				;

			case ContentBlockDto::TYPE_LINE_OF_BLOCKS:
				/** @var $properties LineOfBlocksDto */
				if (!is_array($properties->blocks))
				{
					return null;
				}
				$blocks = [];
				foreach ($properties->blocks as $blockId => $blockDto)
				{
					if (!$this->isValidChildContentBlock($blockDto))
					{
						continue;
					}

					$block = $this->createContentBlock($blockDto);
					if ($block)
					{
						$blocks[(string)$blockId] = $block;
					}
				}
				if (empty($blocks))
				{
					return null;
				}

				return (new ContentBlock\LineOfTextBlocks())
					->setScope($properties->scope)
					->setContentBlocks($blocks)
				;
		}

		return null;
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
		if ($actionDto->type === ActionDto::TYPE_REDIRECT)
		{
			$uri = new Uri($actionDto->uri);
			$action = new Action\Redirect($uri);
			if ($uri->getHost()) // open external links in new window
			{
				$action->addActionParamString('target', '_blank');
			}

			return $action;
		}
		if (
			$actionDto->type === ActionDto::TYPE_REST_EVENT
			|| $actionDto->type === ActionDto::TYPE_OPEN_REST_APP
		)
		{
			$actionParams = $actionDto->actionParams ?? [];
			if ($actionDto->type === ActionDto::TYPE_REST_EVENT)
			{
				$actionParams['entityTypeId'] = $this->getContext()->getEntityTypeId();
				$actionParams['entityId'] = $this->getContext()->getEntityId();
				$actionParams['activityId'] = $this->getActivityId();
				$actionParams['id'] = $actionDto->id;

				$actionParams['APP_ID'] = $this->getRestAppClientId();
				$signedParams = EventHandler::signParams($actionParams);
				$animation = null;
				switch ($actionDto->animationType)
				{
					case ActionDto::ANIMATION_TYPE_DISABLE:
						$animation = Action\Animation::disableBlock()->setForever(true);
						break;
					case ActionDto::ANIMATION_TYPE_LOADER:
						$animation = Action\Animation::showLoaderForItem()->setForever(true);
						break;
				}

				return (new Action\RunAjaxAction('crm.activity.configurable.emitRestEvent'))
					->addActionParamString('signedParams', $signedParams)
					->setAnimation($animation)
				;

			}
			else
			{
				$action = $this->createOpenAppAction();
				foreach ($actionParams as $actionParamName => $actionParamValue)
				{
					$action->addActionParamString((string)$actionParamName, (string)$actionParamValue);
				}

				return $action;
			}
		}

		return null;
	}

	private function createOpenAppAction(): Action\JsEvent
	{
		$action = (new Action\JsEvent('Activity:ConfigurableRestApp:OpenApp'));
		$action->addActionParamInt('restAppId',$this->getRestAppId());
		$this->appendContextActionParams($action);

		return $action;
	}

	private function appendContextActionParams(Action $action): void
	{
		$action->addActionParamInt('entityTypeId', $this->getContext()->getEntityTypeId());
		$action->addActionParamInt('entityId', $this->getContext()->getEntityId());
		$action->addActionParamInt('activityId', $this->getActivityId());
	}

	private function isValidDto(?Dto $dto): bool
	{
		if (!$dto)
		{
			return false;
		}

		return !$dto->hasValidationErrors();
	}

	private function isValidChildContentBlock(?ContentBlockDto $contentBlock): bool
	{
		if (!$this->isValidDto($contentBlock))
		{
			return false;
		}

		return in_array($contentBlock->type, [ContentBlockDto::TYPE_TEXT, ContentBlockDto::TYPE_LINK, ContentBlockDto::TYPE_DEADLINE], true);
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
