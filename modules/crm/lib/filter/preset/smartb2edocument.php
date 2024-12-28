<?php

namespace Bitrix\Crm\Filter\Preset;

use Bitrix\Crm\Filter\ItemDataProvider;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Sign\B2e\TypeService;
use Bitrix\Main\Localization\Loc;

final class SmartB2EDocument extends Base
{
	private const EMPLOYEE_COORDINATION_STAGE = 'EMPLOYEE_COORDINATION';
	private const EMPLOYEE_SIGNING_STAGE = 'EMPLOYEE_SIGNING';
	private const EMPLOYEE_COMPLETED_STAGE = 'EMPLOYEE_COMPLETED';

	public function getDefaultPresets(): array
	{
		$presets = [];

		if($this->isStagesEnabled)
		{
			$typeService = Container::getInstance()->getSignB2eTypeService();
			$statusService = Container::getInstance()->getSignB2eStatusService();

			$filterFields = array_merge($this->defaultValues, [
				ItemDataProvider::FIELD_STAGE_SEMANTIC => PhaseSemantics::getProcessSemantis(),
			]);

			$category = $typeService->getCategoryById((int)$this->categoryId);
			if (($category['CODE'] ?? null) === TypeService::SIGN_B2E_EMPLOYEE_ITEM_CATEGORY_CODE)
			{
				$categoryId = (int)$this->categoryId;
				$filterFields['STAGE_ID'] = [
					$statusService->makeName($categoryId, self::EMPLOYEE_COORDINATION_STAGE),
					$statusService->makeName($categoryId, self::EMPLOYEE_SIGNING_STAGE),
					$statusService->makeName($categoryId, self::EMPLOYEE_COMPLETED_STAGE),
				];
			}

			$presets[self::ID_IN_WORK] = [
				'name' => Loc::getMessage('CRM_TYPE_FILTER_PRESET_IN_WORK'),
				'default' => true,
				'disallow_for_all' => false,
				'fields' => $filterFields,
			];
		}

		$presets[self::ID_MY] = [
			'name' => Loc::getMessage('CRM_TYPE_FILTER_PRESET_MY'),
			'default' => false,
			'disallow_for_all' => true,
			'fields' => array_merge($this->defaultValues, [
				'ASSIGNED_BY_ID_name' => $this->userName,
				'ASSIGNED_BY_ID' => $this->userId,
				ItemDataProvider::FIELD_STAGE_SEMANTIC => PhaseSemantics::getProcessSemantis(),
			]),
		];

		if($this->isStagesEnabled)
		{
			$presets[self::ID_IN_CLOSED] = [
				'name' => Loc::getMessage('CRM_TYPE_FILTER_PRESET_SUCCESS'),
				'default' => false,
				'disallow_for_all' => false,
				'fields' => array_merge($this->defaultValues, [
					ItemDataProvider::FIELD_STAGE_SEMANTIC => PhaseSemantics::getFinalSemantis(),
				]),
			];
		}

		return $presets;
	}
}
