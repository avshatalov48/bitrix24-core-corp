<?php

namespace Bitrix\Crm\Service\Scenario;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Scenario;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class DefaultStages extends Scenario
{
	protected $entityId;
	protected $statusPrefix;
	protected $categoryId;
	protected $stagesData;
	protected $statusManager;

	public function __construct(string $entityId, string $statusPrefix, int $categoryId)
	{
		$this->entityId = $entityId;
		$this->statusPrefix = $statusPrefix;
		$this->categoryId = $categoryId;
		$this->statusManager = new \CCrmStatus($entityId);
	}

	public function setStagesData(array $stagesData): DefaultStages
	{
		$this->stagesData = $stagesData;

		return $this;
	}

	public function getStagesData(): array
	{
		if(empty($this->stagesData))
		{
			return $this->getDefaultStagesData();
		}

		return $this->stagesData;
	}

	protected function getDefaultStagesData(): array
	{
		return [
			[
				'NAME' => Loc::getMessage('CRM_SCENARIO_DEFAULT_STAGES_NEW'),
				'COLOR' => '#22B9FF',
				'STATUS_ID' => 'NEW',
				'SYSTEM' => 'Y',
			],
			[
				'NAME' => Loc::getMessage('CRM_SCENARIO_DEFAULT_STAGES_PREPARATION'),
				'COLOR' => '#88B9FF',
				'STATUS_ID' => 'PREPARATION',
			],
			[
				'NAME' => Loc::getMessage('CRM_SCENARIO_DEFAULT_STAGES_CLIENT'),
				'COLOR' => '#10e5fc',
				'STATUS_ID' => 'CLIENT',
			],
			[
				'NAME' => Loc::getMessage('CRM_SCENARIO_DEFAULT_STAGES_SUCCESS'),
				'COLOR' => '#00ff00',
				'SEMANTICS' => PhaseSemantics::SUCCESS,
				'STATUS_ID' => 'SUCCESS',
				'SYSTEM' => 'Y',
			],
			[
				'NAME' => Loc::getMessage('CRM_SCENARIO_DEFAULT_STAGES_FAIL'),
				'COLOR' => '#ff0000',
				'SEMANTICS' => PhaseSemantics::FAILURE,
				'STATUS_ID' => 'FAIL',
				'SYSTEM' => 'Y',
			],
		];
	}

	public function play(): Result
	{
		$result = new Result();
		$data = [];

		$stagesData = $this->getStagesData();
		if(empty($stagesData))
		{
			return $result->addError(new Error(Loc::getMessage('CRM_SCENARIO_ERROR_NO_DATA')));
		}

		$sort = 10;

		foreach($stagesData as $data)
		{
			$data = $this->prepareStageFields($data);
			$data['SORT'] = $sort;

			$addResult = StatusTable::add($data);
			if(!$addResult->isSuccess())
			{
				$result->addErrors($addResult->getErrors());
			}
			else
			{
				$data['stages'][] = $addResult->getObject();
			}

			$sort += 10;
		}

		return $result->setData($data);
	}

	protected function prepareStageFields(array $data): array
	{
		$data['ENTITY_ID'] = $this->entityId;
		if($this->categoryId > 0)
		{
			$data['CATEGORY_ID'] = $this->categoryId;
		}

		$data['STATUS_ID'] = $this->statusManager::addKnownPrefixToStatusId($data['STATUS_ID'], $this->statusPrefix);
		$data['NAME_INIT'] = $data['NAME'];

		return $data;
	}
}