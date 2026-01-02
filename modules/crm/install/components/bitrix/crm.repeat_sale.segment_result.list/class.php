<?php

use Bitrix\Crm\Component\Base;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\RepeatSale\Log\Entity\RepeatSaleLogTable;
use Bitrix\Main\Application;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CrmRepeatSaleSegmentResultList extends Base
{
	public function executeComponent(): void
	{
		$this->init();

		$this->arResult = $this->arParams;

		$this->initFilter();
		$this->initTitle();

		$this->includeComponentTemplate();
	}

	private function initFilter(): void
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();
		$tableName = $sqlHelper->forSql(RepeatSaleLogTable::getTableName());
		$entityTypeId = \CCrmOwnerType::Deal; // @todo will be support other entityTypeId later
		$segmentId = (int) ($this->arResult['SEGMENT_ID'] ?? 0);

		$filter = [
			'__CONDITIONS' => [
				[
					'SQL' => 'EXISTS (SELECT 1 FROM ' . $tableName . ' rsl WHERE rsl.SEGMENT_ID = ' . $segmentId . ' AND rsl.ENTITY_TYPE_ID = ' . $entityTypeId . ' AND rsl.ENTITY_ID = L.ID)',
				],
			],
		];

		$phaseSemanticId = $this->getPhaseSemanticId();
		if ($phaseSemanticId)
		{
			$filter['STAGE_SEMANTIC_ID'] = [$phaseSemanticId];
		}

		$this->arResult['FILTER'] = $filter;
	}

	private function initTitle(): void
	{
		$code = match ($this->getPhaseSemanticId())
		{
			PhaseSemantics::SUCCESS => 'CRM_REPEAT_SALE_SEGMENT_RESULT_LIST_WIN_DEALS_TITLE',
			PhaseSemantics::FAILURE => 'CRM_REPEAT_SALE_SEGMENT_RESULT_LIST_LOSE_DEALS_TITLE',
			default => 'CRM_REPEAT_SALE_SEGMENT_RESULT_LIST_ALL_DEALS_TITLE',
		};

		$this->arResult['TITLE'] = \Bitrix\Main\Localization\Loc::getMessage($code);
	}

	private function getPhaseSemanticId(): ?string
	{
		$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
		$phaseSemantic = $request->getQuery('PHASE_SEMANTIC');

		return PhaseSemantics::isFinal($phaseSemantic) ? $phaseSemantic : null;
	}
}
