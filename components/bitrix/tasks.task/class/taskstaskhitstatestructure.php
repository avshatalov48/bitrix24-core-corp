<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2023 Bitrix
 */

namespace Bitrix\Tasks\Component\Task;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if(!\Bitrix\Main\Loader::includeModule('tasks'))
{
	die();
}

use Bitrix\Tasks\Util\Type\Structure;
use Bitrix\Tasks\Util\Type\StructureChecker;
use Bitrix\Tasks\Integration;

final class TasksTaskHitStateStructure extends Structure
{
	public function __construct($request)
	{
		$hitState = array();
		if(array_key_exists('HIT_STATE', $request))
		{
			$hitState = $request['HIT_STATE'];
		}

		// todo: also add BACKURL, CANCELURL, DATA_SOURCE here for compatibility, to keep this data inside hit state

		parent::__construct($hitState);
	}

	public function getRules()
	{
		return [
			'INITIAL_TASK_DATA' => [
				'VALUE' => [
					'PARENT_ID' => ['VALUE' => StructureChecker::TYPE_INT_POSITIVE],
					'RESPONSIBLE_ID' => ['VALUE' => StructureChecker::TYPE_INT_POSITIVE],
					'AUDITORS' => [
						'VALUE' => StructureChecker::TYPE_ARRAY_OF_STRING,
						'CAST' => function($value) {
							return (
							is_array($value)
								? $value
								: array_map('trim', explode(',', $value))
							);
						},
					],
					'GROUP_ID' => ['VALUE' => StructureChecker::TYPE_INT_POSITIVE],
					'FLOW_ID' => ['VALUE' => StructureChecker::TYPE_INT_POSITIVE],
					'TITLE' => ['VALUE' => StructureChecker::TYPE_STRING],
					'DESCRIPTION' => ['VALUE' => StructureChecker::TYPE_STRING],
					Integration\CRM\UserField::getMainSysUFCode() => ['VALUE' => StructureChecker::TYPE_STRING],
					Integration\Mail\UserField::getMainSysUFCode() => ['VALUE' => StructureChecker::TYPE_INT_POSITIVE],
					Integration\Disk\UserField::getMainSysUFCode() => ['VALUE' => StructureChecker::TYPE_ARRAY_OF_STRING],
					'TAGS' => [
						'VALUE' => StructureChecker::TYPE_ARRAY_OF_STRING,
						'CAST' => function($value) {
							return (
							is_array($value)
								? $value
								: array_map('trim', explode(',', $value))
							);
						},
					],
					'DEADLINE' => ['VALUE' => StructureChecker::TYPE_STRING],
					'START_DATE_PLAN' => ['VALUE' => StructureChecker::TYPE_STRING],
					'END_DATE_PLAN' => ['VALUE' => StructureChecker::TYPE_STRING],
				],
				'DEFAULT' => [],
			],
			'BACKURL' => ['VALUE' => StructureChecker::TYPE_STRING],
			'CANCELURL' => ['VALUE' => StructureChecker::TYPE_STRING],
			'DATA_SOURCE' => [
				'VALUE' => [
					'TYPE' => ['VALUE' => StructureChecker::TYPE_STRING],
					'ID' => ['VALUE' => StructureChecker::TYPE_INT_POSITIVE],
				],
				'DEFAULT' => [],
			],
		];
	}
}