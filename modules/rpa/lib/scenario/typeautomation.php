<?php

namespace Bitrix\Rpa\Scenario;

use Bitrix\Main\Result;
use Bitrix\Rpa\Integration;
use Bitrix\Rpa\Model\Type;
use Bitrix\Rpa\Scenario;
use Bitrix\Bizproc;

class TypeAutomation extends Scenario
{
	protected $type;

	public function __construct(Type $type)
	{
		$this->type = $type;
	}

	protected function getRobotsDescription(): array
	{
		return [
			'moveItemOnCreate' => [
				[
					'Type'       => 'RpaChangeStageActivity',
					'Properties' =>
						[
							'TargetStageId'     => ':next:',
							'ModifiedBy'   => '{=Document:CREATED_BY}',
						],
					'Name'       => 'A64539_77252_84355_68246',
				],
			],
		];
	}

	public function play(): Result
	{
		$result = new Result();

		$settings = $this->type->getSettings();
		if(!is_array($settings) || !isset($settings['scenarios']) || !is_array($settings['scenarios']) || empty($settings['scenarios']))
		{
			return $result;
		}

		if (!Integration\Bizproc\Automation\Factory::canUseAutomation())
		{
			return $result;
		}

		$robots = $this->getRobotsDescription();
		foreach($settings['scenarios'] as $scenario)
		{
			if(isset($robots[$scenario]))
			{
				$robotResult = $this->installRobot($robots[$scenario]);
				if(!$robotResult->isSuccess())
				{
					$result->addErrors($robotResult->getErrors());
				}
			}
		}

		return $result;
	}

	protected function installRobot(array $description): Result
	{
		$result = new Result();

		$firstStage = $this->type->getFirstStage()->getId();
		$documentType = Integration\Bizproc\Document\Item::makeComplexType($this->type->getId());

		$template = new Bizproc\Automation\Engine\Template($documentType, $firstStage);
		return $template->save($description, 1); // USER_ID = 1, there is no other way to identify system import
	}
}