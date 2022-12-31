<?php


namespace Bitrix\Crm\Service\Display\Field;


use Bitrix\Crm\Order\DeliveryStage;
use Bitrix\Crm\Service\Display\Options;

final class DeliveryStatusField extends StatusField
{
	public const TYPE = 'delivery_status';
	private array $stages;

	protected function __construct(string $id)
	{
		parent::__construct($id);

		$this->stages = DeliveryStage::getList();
	}

	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		$valueConfig = $this->getValueConfig($fieldValue);
		if (!$valueConfig)
		{
			return [];
		}

		return [
			'value' => [
				[
					'name' => mb_strtoupper($valueConfig['text']),
					'color' => ($fieldValue === DeliveryStage::SHIPPED ? '#589309' : '#79818b'),
					'backgroundColor' => ($fieldValue === DeliveryStage::SHIPPED ? '#e0f5c2' : '#e0e2e4'),
				],
			],
		];
	}

	protected function getValueConfig(string $stage): ?array
	{
		if (!isset($this->stages[$stage]))
		{
			return null;
		}

		return [
			'cssPostfix' => ($stage === DeliveryStage::SHIPPED ? 'shipped' : 'no-shipped'),
			'text' => $this->stages[$stage],
		];
	}

	public function prepareField(): void
	{
		parent::prepareField();

		if ($this->isKanbanContext())
		{
			$this->displayParams['cssPrefix'] = 'crm-kanban-item-status';
		}
		if ($this->isGridContext())
		{
			$this->displayParams['cssPrefix'] = 'crm-list-item-status';
		}
	}
}
