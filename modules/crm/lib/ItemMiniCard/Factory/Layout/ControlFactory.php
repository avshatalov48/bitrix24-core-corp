<?php

namespace Bitrix\Crm\ItemMiniCard\Factory\Layout;

use Bitrix\Crm\ItemMiniCard\Layout\Control\Button;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Router;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\UI\Buttons\AirButtonStyle;
use Bitrix\UI\Buttons\Size;

final class ControlFactory
{
	private readonly Router $router;
	private readonly UserPermissions\EntityPermissions\Item $permissions;

	public function __construct(
		private readonly int $entityTypeId,
		private readonly int $entityId,
	)
	{
		$this->router = Container::getInstance()->getRouter();
		$this->permissions = Container::getInstance()->getUserPermissions()->item();
	}

	public function getOpenButton(): Button
	{
		$title = Loc::getMessage('CRM_COMMON_ACTION_OPEN');
		$openUrl =  $this->getDetailUrl();

		return new Button($this->createButton($title, $openUrl));
	}

	public function getEditButton(): Button
	{
		$title = Loc::getMessage('CRM_COMMON_ACTION_EDIT');
		if (!$this->canUpdate())
		{
			return new Button(
				$this
					->createButton($title, null)
					->setDisabled(),
			);
		}

		$editUrl = $this
			->getDetailUrl()
			?->addParams([
				'init_mode' => 'edit',
			]);

		return new Button($this->createButton($title, $editUrl));
	}

	private function canUpdate(): bool
	{
		return $this->permissions->canUpdate($this->entityTypeId, $this->entityId);
	}

	private function getDetailUrl(): ?Uri
	{
		return $this->router->getItemDetailUrl(
			$this->entityTypeId,
			$this->entityId,
		);
	}

	private function createButton(string $title, ?Uri $uri): \Bitrix\UI\Buttons\Button
	{
		return (new \Bitrix\UI\Buttons\Button())
			->setText($title)
			->setStyle(AirButtonStyle::OUTLINE_ACCENT_2)
			->setSize(Size::SMALL)
			->setLink($uri?->getUri() ?? '');
	}
}
