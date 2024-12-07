<?php

namespace Bitrix\Crm\Service\Display\Field\Sign\B2e;

use Bitrix\Crm\Service\Display\Field\StatusField;
use Bitrix\Crm\Service\Display\Options;
use Bitrix\Main\Localization\Loc;

final class ResultStatusField extends StatusField
{
	public const TYPE = 'sing_b2e_result_status';
	private const STATUS_DONE = 'done';
	private const STATUS_STOPPED = 'stopped';

	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		$valueConfig = $this->getValueConfig((string)$fieldValue);
		if ($valueConfig === null)
		{
			return [];
		}

		$params = match ((string)$fieldValue)
		{
			self::STATUS_DONE => [
				'color' => '#589309',
				'backgroundColor' => '#86a13900',
				'name' => self::STATUS_DONE,
			],
			self::STATUS_STOPPED => [
				'color' => '#1097c2',
				'backgroundColor' => '#c2c5cb00',
				'name' => self::STATUS_STOPPED,
			]
		};

		return [
			'value' => [
				$params
			]
		];
	}

	protected function getValueConfig(string $value): ?array
	{
		if (in_array($value, [self::STATUS_DONE, self::STATUS_STOPPED], true) === false)
		{
			return null;
		}

		$text = match ($value)
		{
			self::STATUS_DONE => Loc::getMessage(
				'CRM_FIELD_SIGN_B2E_RESULT_STATUS_DONE'
			),
			self::STATUS_STOPPED => Loc::getMessage(
				'CRM_FIELD_SIGN_B2E_RESULT_STATUS_STOPPED'
			),
		};

		return [
			'cssPostfix' => 'sign-b2e-'.$value,
			'text' => $text,
		];
	}

	public function prepareField(): void
	{
		parent::prepareField();
		if ($this->isKanbanContext())
		{
			$this->displayParams['cssPrefix'] = 'crm-kanban-item-status';
		}
		elseif ($this->isGridContext())
		{
			$this->displayParams['cssPrefix'] = 'crm-list-item-status';
		}
	}
}
