<?php

namespace Bitrix\Crm\ItemMiniCard\Provider;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemMiniCard\Contract\Provider;
use Bitrix\Crm\ItemMiniCard\Factory\Layout\ControlFactory;
use Bitrix\Crm\ItemMiniCard\Factory\Layout\Entity\FieldFactory;
use Bitrix\Crm\ItemMiniCard\Layout\FooterNote\DateUpdatedFooterNote;
use CCrmOwnerType;

abstract class AbstractEntityProvider implements Provider
{
	protected ControlFactory $controlFactory;
	protected FieldFactory $fieldFactory;

	public function __construct(
		protected readonly Item $item,
	)
	{
		$this->controlFactory = new ControlFactory($this->item->getEntityTypeId(), $this->item->getId());
		$this->fieldFactory = new FieldFactory($this->item);
	}

	public function provideId(): string
	{
		$entityTypeName = CCrmOwnerType::ResolveName($this->item->getEntityTypeId());
		$entityId = $this->item->getId();

		return "{$entityTypeName}_{$entityId}";
	}

	public function provideTitle(): string
	{
		return $this->item->getHeading();
	}

	public function provideControls(): array
	{
		return [
			$this->controlFactory->getOpenButton(),
			$this->controlFactory->getEditButton(),
		];
	}

	public function provideFooterNotes(): array
	{
		return [
			new DateUpdatedFooterNote($this->item->getUpdatedTime()),
		];
	}
}
