<?php

namespace Bitrix\Tasks\Flow\Demo;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Control\Command\AddDemoCommand;

class DataList
{
	/**
	 * @return AddDemoCommand[]
	 */
	public static function get(): array
	{
		$list = [];

		$list[] = [
			'flow' => (new AddDemoCommand())
				->setName(Loc::getMessage('TASKS_FLOW_DEMO_FLOW_NAME_1'))
				->setDescription(Loc::getMessage('TASKS_FLOW_DEMO_FLOW_DESCRIPTION_1')),
			'template' => [
				'TITLE' => Loc::getMessage('TASKS_FLOW_DEMO_TEMPLATE_NAME_1'),
				'DESCRIPTION' => Loc::getMessage('TASKS_FLOW_DEMO_TEMPLATE_DESCRIPTION_1'),
			],
		];

		$list[] = [
			'flow' => (new AddDemoCommand())
				->setName(Loc::getMessage('TASKS_FLOW_DEMO_FLOW_NAME_2'))
				->setDescription(Loc::getMessage('TASKS_FLOW_DEMO_FLOW_DESCRIPTION_2')),
			'template' => [
				'TITLE' => Loc::getMessage('TASKS_FLOW_DEMO_TEMPLATE_NAME_2'),
				'DESCRIPTION' => Loc::getMessage('TASKS_FLOW_DEMO_TEMPLATE_DESCRIPTION_2'),
			],
		];

		$list[] = [
			'flow' => (new AddDemoCommand())
				->setName(Loc::getMessage('TASKS_FLOW_DEMO_FLOW_NAME_3'))
				->setDescription(Loc::getMessage('TASKS_FLOW_DEMO_FLOW_DESCRIPTION_3')),
			'template' => [
				'TITLE' => Loc::getMessage('TASKS_FLOW_DEMO_TEMPLATE_NAME_3'),
				'DESCRIPTION' => Loc::getMessage('TASKS_FLOW_DEMO_TEMPLATE_DESCRIPTION_3'),
			],
		];

		$list[] = [
			'flow' => (new AddDemoCommand())
				->setName(Loc::getMessage('TASKS_FLOW_DEMO_FLOW_NAME_4'))
				->setDescription(Loc::getMessage('TASKS_FLOW_DEMO_FLOW_DESCRIPTION_4')),
			'template' => [
				'TITLE' => Loc::getMessage('TASKS_FLOW_DEMO_TEMPLATE_NAME_4'),
				'DESCRIPTION' => Loc::getMessage('TASKS_FLOW_DEMO_TEMPLATE_DESCRIPTION_4'),
			],
		];

		$list[] = [
			'flow' => (new AddDemoCommand())
				->setName(Loc::getMessage('TASKS_FLOW_DEMO_FLOW_NAME_5'))
				->setDescription(Loc::getMessage('TASKS_FLOW_DEMO_FLOW_DESCRIPTION_5')),
			'template' => [
				'TITLE' => Loc::getMessage('TASKS_FLOW_DEMO_TEMPLATE_NAME_5'),
				'DESCRIPTION' => Loc::getMessage('TASKS_FLOW_DEMO_TEMPLATE_DESCRIPTION_5'),
			],
		];

		$list[] = [
			'flow' => (new AddDemoCommand())
				->setName(Loc::getMessage('TASKS_FLOW_DEMO_FLOW_NAME_6'))
				->setDescription(Loc::getMessage('TASKS_FLOW_DEMO_FLOW_DESCRIPTION_6')),
			'template' => [
				'TITLE' => Loc::getMessage('TASKS_FLOW_DEMO_TEMPLATE_NAME_6'),
				'DESCRIPTION' => Loc::getMessage('TASKS_FLOW_DEMO_TEMPLATE_DESCRIPTION_6'),
			],
		];

		$list[] = [
			'flow' => (new AddDemoCommand())
				->setName(Loc::getMessage('TASKS_FLOW_DEMO_FLOW_NAME_7'))
				->setDescription(Loc::getMessage('TASKS_FLOW_DEMO_FLOW_DESCRIPTION_7')),
			'template' => [
				'TITLE' => Loc::getMessage('TASKS_FLOW_DEMO_TEMPLATE_NAME_7'),
				'DESCRIPTION' => Loc::getMessage('TASKS_FLOW_DEMO_TEMPLATE_DESCRIPTION_7'),
			],
		];

		return $list;
	}
}
