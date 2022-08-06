<?php

namespace Bitrix\Crm\Filter\Preset;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Localization\Loc;
use CCrmQuote;

final class Quote extends Base
{
	private array $processStatusIds = [];
	private array $closedStatusIds = [];

	public function getDefaultPresets(): array
	{
		$this->initStatuses();

		return [
			self::ID_IN_WORK => [
				'name' => Loc::getMessage('CRM_PRESET_IN_WORK_QUOTES'),
				'default' => true,
				'fields' => array_merge($this->defaultValues, [
					'STATUS_ID' => $this->processStatusIds,
				])
			],
			self::ID_MY => [
				'name' => Loc::getMessage('CRM_PRESET_MY_QUOTES'),
				'fields' => array_merge($this->defaultValues, [
					'ASSIGNED_BY_ID_name' => $this->userName,
					'ASSIGNED_BY_ID' => $this->userId,
					'STATUS_ID' => $this->processStatusIds,
				])
			],
			self::ID_IN_CLOSED => [
				'name' => Loc::getMessage('CRM_PRESET_CLOSED_QUOTES'),
				'fields' => array_merge($this->defaultValues, [
					'STATUS_ID' => $this->closedStatusIds,
				])
			],
		];
	}

	private function initStatuses(): void
	{
		foreach(array_keys(CCrmQuote::GetStatuses()) as $statusId)
		{
			$semanticId = CCrmQuote::GetSemanticID($statusId);
			if($semanticId === PhaseSemantics::PROCESS)
			{
				$this->processStatusIds[] = $statusId;
			}
			elseif(in_array($semanticId, [PhaseSemantics::SUCCESS, PhaseSemantics::FAILURE]))
			{
				$this->closedStatusIds[] = $statusId;
			}
		}
	}
}
