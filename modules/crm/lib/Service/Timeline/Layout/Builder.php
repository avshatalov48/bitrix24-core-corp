<?php

namespace Bitrix\Crm\Service\Timeline\Layout;

use Bitrix\Crm\Integration\Intranet\BindingMenu\CodeBuilder;
use Bitrix\Crm\Integration\Intranet\BindingMenu\SectionCode;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\Configurable;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Main\Localization\Loc;

class Builder
{
	private Configurable $item;

	public function __construct(Configurable $item)
	{
		$this->item = $item;
	}

	public function build(): Layout
	{
		$layout = (new Layout())
			->setIcon($this->buildIcon())
			->setHeader($this->buildHeader())
			->setBody($this->buildBody())
			->setFooter($this->buildFooter())
			->setMarketPanel($this->item->getMarketPanel())
			->setIsLogMessage($this->item->isLogMessage())
		;
		$this->applyLayoutPartsDependencies($layout);

		return $layout;
	}

	protected function buildIcon(): ?Layout\Icon
	{
		return $this->item->getIcon();
	}

	protected function buildHeader(): ?Layout\Header
	{
		$title = $this->item->getTitle();
		$date = $this->item->getDate();
		$tags = $this->item->getTags();
		$userId = $this->item->getAuthorId();

		$changeStreamButton = $this->item->getChangeStreamButton();
		$infoHelper = $this->item->getInfoHelper();

		return ($title || $date || $tags || $userId || $changeStreamButton || $infoHelper)
			? (new Layout\Header())
				->setChangeStreamButton($changeStreamButton)
				->setTitle($title)
				->setTitleAction($this->item->getTitleAction())
				->setDate($date)
				->setDatePlaceholder($this->item->getDatePlaceholder())
				->setTags($tags ?? [])
				->setUser($this->createLayoutUser($userId))
				->setInfoHelper($infoHelper)
			: null
		;
	}

	protected function buildBody(): Layout\Body
	{
		$body = new Layout\Body();
		$body->setLogo($this->item->getLogo());
		$contentBlocks = array_merge(
			$this->item->getCommonContentBlocksBlocks(),
			$this->item->getContentBlocks() ?? [],
		);
		$currentSort = 0;
		foreach ($contentBlocks as $contentBlock)
		{
			if (is_null($contentBlock->getSort()))
			{
				$contentBlock->setSort($currentSort++);
			}
		}
		$body->setContentBlocks($contentBlocks);

		return $body;
	}

	protected function buildFooter(): ?Layout\Footer
	{
		$footer = new Layout\Footer();

		$buttons = $this->item->getButtons();
		if (!empty($buttons))
		{
			$currentSort = 0;
			foreach ($buttons as $button)
			{
				if (is_null($button->getSort()))
				{
					$button->setSort($currentSort++);
				}
			}
			$footer->setButtons($buttons);
		}
		$additionalButtons = [];
		if ($this->item->getAdditionalIconButton())
		{
			$additionalButtons['extra'] = $this->item->getAdditionalIconButton();
		}

		if ($this->item->needShowNotes())
		{
			$additionalButtons['notes'] = (new Layout\Footer\IconButton(
				'note', Loc::getMessage('CRM_TIMELINE_NOTES_TITLE')
			))
				->setAction((new Layout\Action\JsEvent('Note:StartEdit')))
				->setHideIfReadonly()
			;
		}
		if (!empty($additionalButtons))
		{
			$footer->setAdditionalButtons($additionalButtons);
		}

		$menuItems = $this->item->getMenuItems();
		if (!empty($menuItems))
		{
			$extensionsMenu = $this->getExtensionsMenu();
			if ($extensionsMenu)
			{
				$extensionsMenu
					->setSort(8000)
					->setScopeWeb()
				;

				$menuItems[] = $extensionsMenu;
				$menuItems[] = (new Menu\MenuItemDelimiter())
					->setSort(8001)
					->setScopeWeb()
				;
			}

			$footer->setMenu(
				(new Layout\Menu())
					->setItems($menuItems)
			);
		}

		return $footer;
	}

	private function createLayoutUser(?int $userId): ?Layout\User
	{
		if (!$userId)
		{
			return null;
		}

		$userData = Container::getInstance()->getUserBroker()->getById($userId);
		if (!$userData)
		{
			return null;
		}

		return new Layout\User(
			$userData['FORMATTED_NAME'],
			$userData['SHOW_URL'],
			$userData['PHOTO_URL'] ?? null
		);
	}

	private function getExtensionsMenu(): ?Menu\MenuItem
	{
		$menu = \Bitrix\Intranet\Binding\Menu::getMenuItems(
			SectionCode::TIMELINE,
			CodeBuilder::getMenuCode($this->item->getContext()->getEntityTypeId()),
			[
				'inline' => true,
				'context' => [
					'ENTITY_ID' => $this->item->getContext()->getEntityId(),
				]
			]
		);
		if (empty($menu) || empty($menu['items']))
		{
			return null;
		}

		return Menu\MenuItemFactory::createFromArray($menu);
	}

	private function applyLayoutPartsDependencies(Layout $layout): void
	{
		$noteBlock = $layout->getBody()->getContentBlocks()['note'] ?? null;
		if ($noteBlock && !is_null($noteBlock->getId()))
		{
			$footerNoteAdditionalButton = $layout->getFooter()->getAdditionalButtons()['notes'] ?? null;
			if ($footerNoteAdditionalButton)
			{
				$footerNoteAdditionalButton->setColor(Layout\Footer\IconButton::COLOR_PRIMARY);
			}
		}
	}
}
