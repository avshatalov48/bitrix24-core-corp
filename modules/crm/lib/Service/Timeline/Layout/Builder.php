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

		return $layout;
	}

	protected function buildIcon(): ?Layout\Icon
	{
		$iconCode = $this->item->getIconCode();

		return $iconCode
			? (new Layout\Icon())
				->setCode($iconCode)
				->setCounterType($this->item->getCounterType())
				->setBackgroundColorToken($this->item->getBackgroundColorToken())
			: null
		;
	}

	protected function buildHeader(): ?Layout\Header
	{
		$title = $this->item->getTitle();
		$date = $this->item->getDate();
		$tags = $this->item->getTags();
		$userId = $this->item->getAuthorId();

		$changeStreamButton = $this->item->getChangeStreamButton();

		return ($title || $date || $tags || $userId || $changeStreamButton)
			? (new Layout\Header())
				->setChangeStreamButton($changeStreamButton)
				->setTitle($title)
				->setTitleAction($this->item->getTitleAction())
				->setDate($date)
				->setDatePlaceholder($this->item->getDatePlaceholder())
				->setTags($tags ?? [])
				->setUser($this->createLayoutUser($userId))
			: null
		;
	}

	protected function buildBody(): Layout\Body
	{
		$body = new Layout\Body();
		$body->setLogo($this->item->getLogo());
		$body->setContentBlocks($this->item->getContentBlocks() ?? []);

		return $body;
	}

	protected function buildFooter(): ?Layout\Footer
	{
		$footer = new Layout\Footer();

		$buttons = $this->item->getButtons();
		if (!empty($buttons))
		{
			$footer->setButtons($buttons);
		}
		$additionalButtons = [];
		if ($this->item->getAdditionalIconButton())
		{
			$additionalButtons['extra'] = $this->item->getAdditionalIconButton();
		}
		if ($this->item->needShowNotes())
		{
			$additionalButtons['notes'] = new Layout\Footer\IconButton('page', Loc::getMessage('CRM_TIMELINE_NOTES_TITLE'));
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

	private function createLayoutUser(?int $userId): ?Layout\Header\User
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

		return new Layout\Header\User(
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
}
