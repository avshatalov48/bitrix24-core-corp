<?php


namespace Bitrix\Crm\Service\Display\Field;


use Bitrix\Crm\Service\Display\Options;
use Bitrix\Crm\Workflow\PaymentStage;

final class PaymentStatusField extends StatusField
{
	public const TYPE = 'payment_status';

	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		$valueConfig = $this->getValueConfig($fieldValue);
		if (!$valueConfig)
		{
			return [];
		}

		$params = [
			'color' => '#79818b',
			'backgroundColor' => '#e0e2e4',
		];
		if ($fieldValue === PaymentStage::PAID)
		{
			$params = [
				'color' => '#589309',
				'backgroundColor' => '#e0f5c2',
			];
		}
		elseif ($fieldValue === PaymentStage::VIEWED_NO_PAID)
		{
			$params = [
				'color' => '#1097c2',
				'backgroundColor' => '#dcf6fe',
			];
		}
		$params['name'] = mb_strtoupper($valueConfig['text']);

		return [
			'value' => [
				$params,
			],
		];
	}

	protected function getValueConfig(string $stage): ?array
	{
		if (!PaymentStage::isValid($stage))
		{
			return null;
		}

		$classMap = [
			PaymentStage::NOT_PAID => 'not-paid',
			PaymentStage::PAID => 'paid',
			PaymentStage::SENT_NO_VIEWED => 'send',
			PaymentStage::VIEWED_NO_PAID => 'seen',
			PaymentStage::CANCEL => 'cancel',
			PaymentStage::REFUND => 'refund',
		];

		return [
			'cssPostfix' => ($classMap[$stage] ?? 'default'),
			'text' => PaymentStage::getMessage($stage),
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
