<?php

namespace Bitrix\Tasks\Component\Task;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\Manager\Task;
use Bitrix\Tasks\Replication\Replicator\RegularTaskReplicator;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util\User;
use CTasksTools;
use CUserOptions;

if (!Loader::includeModule('tasks'))
{
	die();
}

// todo: refactor this class

final class TasksTaskFormState
{
	public const O_CHOSEN = 'C';
	public const O_OPENED = 'O';
	public const O_HIDDEN = 'H';

	public const OPT_NAME = 'task_edit_form_state';

	public static function get(): array
	{
		$value = Type::unSerializeArray(User::getOption(self::OPT_NAME));

		if (!is_array($value) || empty($value))
		{
			return self::getDefault();
		}
		else
		{
			return self::merge(self::getDefault(), $value); // merging helps to introduce new blocks
		}
	}

	public static function set(array $newState): void
	{
		$newState = self::filter($newState);
		$toSave = self::merge(self::get(), $newState);

		User::setOption(self::OPT_NAME, serialize($toSave));
	}

	private static function merge(array $currentState, array $newState): array
	{
		foreach ($currentState as $typeId => &$type)
		{
			if (array_key_exists($typeId, $newState))
			{
				foreach ($type as $i => &$value)
				{
					if (array_key_exists($i, $newState[$typeId]))
					{
						if (is_array($value))
						{
							$value = array_merge($value, $newState[$typeId][$i]);
						}
						else
						{
							$value = $newState[$typeId][$i];
						}
					}
				}
				unset($value);
			}
		}
		unset($type);

		return $currentState;
	}

	public static function reset(): void
	{
		CUserOptions::SetOption(
			'tasks',
			self::OPT_NAME,
			''
		);
	}

	private static function filter(array $state): array
	{
		$default = self::getDefault();

		foreach ($state as $section => $vars)
		{
			if ($section != 'BLOCKS' && $section != 'FLAGS')
			{
				unset($state[$section]);
			}
		}

		if (is_array($state['BLOCKS'] ?? null))
		{
			foreach ($state['BLOCKS'] as $blockName => &$blockOpts)
			{
				if (!array_key_exists($blockName, $default['BLOCKS'])) // no such block
				{
					unset($state['BLOCKS'][$blockName]);
					continue;
				}

				if (!is_array($blockOpts)) // value is not an array
				{
					unset($state['BLOCKS'][$blockName]);
					continue;
				}

				foreach ($blockOpts as $type => &$value)
				{
					if (!array_key_exists($type, $default['BLOCKS'][$blockName])) // no such type
					{
						unset($blockOpts[$type]);
						continue;
					}

					$value = self::convertToBoolean($value);
				}
				unset($value);
			}
			unset($blockOpts);
		}
		else
		{
			unset($state['BLOCKS']);
		}

		if (is_array($state['FLAGS']))
		{
			foreach ($state['FLAGS'] as $flag => &$value)
			{
				if (!array_key_exists($flag, $default['FLAGS'])) // no such flag
				{
					unset($state['FLAGS'][$flag]);
					continue;
				}

				$value = self::convertToBoolean($value);
			}
			unset($value);
		}
		else
		{
			unset($state['FLAGS']);
		}

		return $state;
	}

	private static function convertToBoolean($value): bool
	{
		return $value === true || $value === 'true' || $value === '1' || $value == 'Y';
	}

	private static function getDefault(): array
	{

		return [
			'BLOCKS' => self::getBlocks(),
			'FLAGS' => self::getFlags(),
		];
	}

	private static function getBlocks(): array
	{
		$blocks = [
			// match task fields
			Manager::SE_PREFIX . 'CHECKLIST' => [
				self::O_CHOSEN => false,
			],
			Manager::SE_PREFIX . 'ORIGINATOR' => [
				self::O_CHOSEN => false,
			],
			Manager::SE_PREFIX . 'AUDITOR' => [
				self::O_CHOSEN => false,
			],
			Manager::SE_PREFIX . 'ACCOMPLICE' => [
				self::O_CHOSEN => false,
			],
			'DATE_PLAN' => [
				self::O_CHOSEN => false,
			],
			'OPTIONS' => [
				self::O_CHOSEN => false,
			],
			Manager::SE_PREFIX . 'PROJECT' => [
				self::O_CHOSEN => false,
			],
			'TIMEMAN' => [
				self::O_CHOSEN => false,
			],
			Manager::SE_PREFIX . 'REMINDER' => [
				self::O_CHOSEN => false,
			],
			Manager::SE_PREFIX . 'TEMPLATE' => [
				self::O_CHOSEN => false,
			],
			Manager::SE_PREFIX . 'PROJECTDEPENDENCE' => [
				self::O_CHOSEN => false,
			],
			'UF_CRM_TASK' => [
				self::O_CHOSEN => false,
			],
			Task\ParentTask::getCode(true) => [
				self::O_CHOSEN => false,
			],
			Manager::SE_PREFIX . 'TAG' => [
				self::O_CHOSEN => false,
			],
			'EPIC' => [
				self::O_CHOSEN => false,
				self::O_HIDDEN => true,
			],
			'USER_FIELDS' => [
				self::O_CHOSEN => false,
			],
			Manager::SE_PREFIX . 'RELATEDTASK' => [
				self::O_CHOSEN => false,
			],
			Manager::SE_PREFIX . 'REQUIRE_RESULT' => [
				self::O_CHOSEN => true,
			],
		];

		if (RegularTaskReplicator::isEnabled())
		{
			$blocks['REGULAR'] = [
				self::O_CHOSEN => false
			];
		}

		return $blocks;
	}

	private static function getFlags(): array
	{
		$popupOpts = CTasksTools::getPopupOptions();

		return [
			'ALLOW_TIME_TRACKING' => $popupOpts['time_tracking'] == 'Y',
			'TASK_CONTROL' => $popupOpts['task_control'] == 'Y',
			'ALLOW_CHANGE_DEADLINE' => true,
			'MATCH_WORK_TIME' => false,
			'FORM_FOOTER_PIN' => false,
			'REQUIRE_RESULT' => false,
			'TASK_PARAM_3' => false,
		];
	}
}