<?php

namespace Bitrix\Crm\ItemMiniCard\Provider;

use Bitrix\Crm\ItemMiniCard\Contract\Provider;
use Bitrix\Crm\ItemMiniCard\Factory\Layout\ControlFactory;
use Bitrix\Crm\ItemMiniCard\Factory\Layout\Order\FieldFactory;
use Bitrix\Crm\ItemMiniCard\Layout\Avatar\AbstractAvatar;
use Bitrix\Crm\ItemMiniCard\Layout\Avatar\IconAvatar;
use Bitrix\Crm\ItemMiniCard\Layout\FooterNote\DateUpdatedFooterNote;
use Bitrix\Crm\Order\Order;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

final class OrderProvider implements Provider
{
	private readonly FieldFactory $fieldFactory;
	private readonly ControlFactory $controlFactory;

	public function __construct(
		private readonly Order $order,
	)
	{
		$this->fieldFactory = new FieldFactory($this->order);
		$this->controlFactory = new ControlFactory(CCrmOwnerType::Order, $this->order->getId());
	}

	public function provideId(): string
	{
		$entityTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::Order);
		$entityId = $this->order->getId();

		return "{$entityTypeName}_{$entityId}";
	}

	public function provideTitle(): string
	{
		$topic = Loc::getMessage(
			'CRM_ITEM_MINI_CARD_ORDER_PROVIDER_ORDER_NUMBER_TITLE',
			[
				'#ORDER_NUMBER#' => $this->order->getField('ACCOUNT_NUMBER'),
			],
		);

		if (!empty($this->order->getField('ORDER_TOPIC')))
		{
			$topic = Loc::getMessage(
				'CRM_ITEM_MINI_CARD_ORDER_PROVIDER_ORDER_TOPIC_TITLE',
				[
					'#ORDER_TOPIC#' => $this->order->getField('ORDER_TOPIC'),
					'#ORDER_NUMBER#' => $this->order->getField('ACCOUNT_NUMBER'),
				],
			);
		}

		return $topic;
	}

	public function provideAvatar(): AbstractAvatar
	{
		return new IconAvatar('o-shopping-cart');
	}

	public function provideControls(): array
	{
		return [
			$this->controlFactory->getOpenButton(),
			$this->controlFactory->getEditButton(),
		];
	}

	public function provideFields(): array
	{
		return [
			$this->fieldFactory->getStatus(),
			$this->fieldFactory->getProducts(),
			$this->fieldFactory->getPrice(),
			$this->fieldFactory->getCompany(),
			$this->fieldFactory->getContact(),
		];
	}

	public function provideFooterNotes(): array
	{
		return [
			new DateUpdatedFooterNote($this->order->getField('DATE_UPDATE')),
		];
	}
}
