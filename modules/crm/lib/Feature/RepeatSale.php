<?php

namespace Bitrix\Crm\Feature;

use Bitrix\Crm\Feature\Category\BaseCategory;
use Bitrix\Crm\Feature\Category\Common;
use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Crm\RepeatSale\Segment\SystemSegmentCode;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use CCrmStatus;

class RepeatSale extends BaseFeature
{
	public function getName(): string
	{
		return Loc::getMessage('CRM_FEATURE_REPEAT_SALE_NAME');
	}

	public function getCategory(): BaseCategory
	{
		return Common::getInstance();
	}

	protected function getOptionName(): string
	{
		return 'CRM_REPEAT_SALE';
	}

	protected function getEnabledValue(): bool
	{
		return true;
	}

	public function enable(): void
	{
		if ($this->isEnabled())
		{
			return;
		}

		parent::enable();

		$this->checkAndAppendStatus();
		$this->deleteNotPeriodicalSegments();

		/**
		 * @see \Bitrix\Crm\Agent\RepeatSale\PrefillAgent
		 */
		\CAgent::AddAgent(
			'Bitrix\Crm\Agent\RepeatSale\PrefillAgent::run();',
			'crm',
			'N',
			60,
			'',
			'Y',
			\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 50, 'FULL')
		);

		$jobExecutorAgentName = 'Bitrix\Crm\Agent\RepeatSale\JobExecutorAgent::run();';
		if (\CAgent::GetList([], ['NAME' => $jobExecutorAgentName, 'MODULE_ID' => 'crm'])->Fetch())
		{
			return;
		}

		Option::set('crm', 'repeat-sale-wait-only-calc-scheduler', 'Y');

		/**
		 * @see \Bitrix\Crm\Agent\RepeatSale\OnlyCalcSchedulerAgent
		 */
		\CAgent::AddAgent(
			'Bitrix\Crm\Agent\RepeatSale\OnlyCalcSchedulerAgent::run();',
			'crm',
			'N',
			3600,
			'',
			'Y',
			\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 100, 'FULL'),
		);

		/**
		 * @see \Bitrix\Crm\Agent\RepeatSale\JobExecutorAgent
		 */
		\CAgent::AddAgent(
			$jobExecutorAgentName,
			'crm',
			'N',
			60,
			'',
			'Y',
			\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 200, 'FULL'),
		);

		$aiQueueBufferAgentName = 'Bitrix\Crm\Agent\Copilot\AiQueueBufferAgent::run();';
		if (\CAgent::GetList([], ['NAME' => $aiQueueBufferAgentName, 'MODULE_ID' => 'crm'])->Fetch())
		{
			return;
		}
		/**
		 * @see \Bitrix\Crm\Agent\Copilot\AiQueueBufferAgent
		 */
		\CAgent::AddAgent(
			$aiQueueBufferAgentName,
			'crm',
			'N',
			600,
			'',
			'Y',
			\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 900, 'FULL'),
		);
	}

	private function checkAndAppendStatus(): void
	{
		$list = StatusTable::getList([
			'filter' => ['=ENTITY_ID' => StatusTable::ENTITY_ID_SOURCE],
			'order' => ['SORT' => 'DESC'],
		]);

		$statuses = [];
		while ($status = $list->fetch())
		{
			$statuses[$status['STATUS_ID']] = $status;
		}

		if (!empty($statuses['REPEAT_SALE']['NAME_INIT']))
		{
			return;
		}

		$sources = CCrmStatus::GetDefaultSources();
		$repeatSaleItem = current(
			array_filter(
				$sources,
				static fn($sourceItem) => $sourceItem['STATUS_ID'] === 'REPEAT_SALE'
			)
		);

		if (!$repeatSaleItem)
		{
			return;
		}

		if (isset($statuses['REPEAT_SALE']))
		{
			StatusTable::update(
				$statuses['REPEAT_SALE']['ID'],
				[
					'NAME' => $repeatSaleItem['NAME'],
					'NAME_INIT' => $repeatSaleItem['NAME'],
				]
			);
		}
		else
		{
			$maxSort = (int)(current($statuses)['SORT'] ?? $repeatSaleItem['SORT']);
			StatusTable::add([
				'ENTITY_ID' => StatusTable::ENTITY_ID_SOURCE,
				'STATUS_ID' => $repeatSaleItem['STATUS_ID'],
				'NAME' => $repeatSaleItem['NAME'],
				'NAME_INIT' => $repeatSaleItem['NAME'],
				'SORT' => $maxSort + 10,
				'SYSTEM' => 'Y',
				'COLOR' => '#',
				'CATEGORY_ID' => 0,
			]);
		}

		StatusTable::cleanCache();
	}

	private function deleteNotPeriodicalSegments(): void
	{
		$controller = RepeatSaleSegmentController::getInstance();
		$notPeriodicalSegments = $controller->getList([
			'select' => ['ID'],
			'filter' => [
				'@CODE' => [
					SystemSegmentCode::SLEEPING_CLIENT->value,
					SystemSegmentCode::LOST_CLIENT->value
				],
			],
		]);

		foreach ($notPeriodicalSegments as $notPeriodicalSegment)
		{
			$controller->delete($notPeriodicalSegment->getId());
		}
	}
}
