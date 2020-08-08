<?php

namespace Bitrix\Rpa\Scenario;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Security\Random;
use Bitrix\Rpa\Controller\Stage;
use Bitrix\Rpa\Model\StageToStageTable;
use Bitrix\Rpa\Model\Type;
use Bitrix\Rpa\Scenario;

class DefaultStages extends Scenario
{
	protected $type;

	public function __construct(Type $type)
	{
		$this->type = $type;
	}

	protected function getDefaultStagesData(): array
	{
		return [
			[
				'name' => Loc::getMessage('RPA_SCENARIO_DEFAULT_STAGES_FIRST'),
				'color' => '22B9FF',
			],
			[
				'name' => Loc::getMessage('RPA_SCENARIO_DEFAULT_STAGES_CHIEF_APPROVAL'),
				'color' => '88B9FF',
			],
			[
				'name' => Loc::getMessage('RPA_SCENARIO_DEFAULT_STAGES_ACCOUNTANT_APPROVAL'),
				'color' => '10e5fc',
			],
			[
				'name' => Loc::getMessage('RPA_SCENARIO_DEFAULT_STAGES_SUCCESS'),
				'color' => '00ff00',
				'semantic' => \Bitrix\Rpa\Model\Stage::SEMANTIC_SUCCESS,
			],
			[
				'name' => Loc::getMessage('RPA_SCENARIO_DEFAULT_STAGES_FAIL'),
				'color' => 'ff0000',
				'semantic' => \Bitrix\Rpa\Model\Stage::SEMANTIC_FAIL,
			],
		];
	}

	public function play(): Result
	{
		$result = new Result();

		$stages = $this->type->getStages();
		if($stages->count() > 0)
		{
			return $result;
		}

		$defaultStagesData = $this->getDefaultStagesData();
		$resultData = [
			'eventIds' => [],
		];

		$sort = Stage::SORT_STEP;
		foreach($defaultStagesData as $fields)
		{
			$controller = new Stage();
			$stage = $this->type->createStage();
			$fields['sort'] = $sort;
			$eventId = Random::getString(6);
			$controller->updateAction($stage, $fields, $eventId);
			$resultData['eventIds'][] = $eventId;
			if($controller->getErrors())
			{
				$result->addErrors($controller->getErrors());
			}
			else
			{
				$stages->add($stage);
				$resultData['defaultStages'][] = $stage;
			}

			$sort += Stage::SORT_STEP;
		}

		if($result->isSuccess())
		{
			$allowResult = $this->allowMovingToFinalStages();
			if(!$allowResult->isSuccess())
			{
				$result->addErrors($allowResult->getErrors());
			}
		}

		$result->setData($resultData);

		return $result;
	}

	protected function allowMovingToFinalStages(): Result
	{
		$result = new Result();

		$firstStage = $this->type->getFirstStage();
		$successStage = $this->type->getSuccessStage();
		$failStages = $this->type->getFailStages();
		if($firstStage && $successStage)
		{
			$addResult = StageToStageTable::add([
				'STAGE_ID' => $firstStage->getId(),
				'STAGE_TO_ID' => $successStage->getId()
			]);
			if(!$addResult->isSuccess())
			{
				$result->addErrors($addResult->getErrors());
			}
		}
		if($firstStage)
		{
			foreach($failStages as $failStage)
			{
				$addResult = StageToStageTable::add([
					'STAGE_ID' => $firstStage->getId(),
					'STAGE_TO_ID' => $failStage->getId()
				]);
				if(!$addResult->isSuccess())
				{
					$result->addErrors($addResult->getErrors());
				}
			}
		}

		return $result;
	}
}