<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Tasks\Manager\Task;
use \Bitrix\Tasks\Util;

// todo: refactor this class

final class TasksTaskFormState
{
	const O_CHOSEN = 'C';
	const O_OPENED = 'O';

	const OPT_NAME = 'task_edit_form_state';

	public static function get()
	{
		$value = Util\Type::unSerializeArray(Util\User::getOption(self::OPT_NAME));

		if(!is_array($value) || empty($value))
		{
			return self::getDefault();
		}
		else
		{
			return self::merge(static::getDefault(), $value); // merging helps to introduce new blocks
		}
	}

	public static function set(array $newState)
	{
		$newState = self::filter($newState);
		$toSave = self::merge(self::get(), $newState);

		Util\User::setOption(self::OPT_NAME, serialize($toSave));
	}

	private static function merge(array $currentState, array $newState)
	{
		foreach($currentState as $typeId => &$type)
		{
			if(array_key_exists($typeId, $newState))
			{
				foreach($type as $i => &$value)
				{
					if(array_key_exists($i, $newState[$typeId]))
					{
						if(is_array($value))
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

	public static function reset()
	{
		CUserOptions::SetOption(
			'tasks',
			self::OPT_NAME,
			''
		);
	}

	private static function filter(array $state)
	{
		$default = self::getDefault();

		foreach($state as $section => $vars)
		{
			if($section != 'BLOCKS' && $section != 'FLAGS')
			{
				unset($state[$section]);
				continue;
			}
		}

		if(is_array($state['BLOCKS']))
		{
			foreach($state['BLOCKS'] as $blockName => &$blockOpts)
			{
				if(!array_key_exists($blockName, $default['BLOCKS'])) // no such block
				{
					unset($state['BLOCKS'][$blockName]);
					continue;
				}

				if(!is_array($blockOpts)) // value is not an array
				{
					unset($state['BLOCKS'][$blockName]);
					continue;
				}

				foreach($blockOpts as $type => &$value)
				{
					if(!array_key_exists($type, $default['BLOCKS'][$blockName])) // no such type
					{
						unset($blockOpts[$type]);
						continue;
					}

					$value = static::convertToBoolean($value);
				}
				unset($value);
			}
			unset($blockOpts);
		}
		else
		{
			unset($state['BLOCKS']);
		}

		if(is_array($state['FLAGS']))
		{
			foreach($state['FLAGS'] as $flag => &$value)
			{
				if(!array_key_exists($flag, $default['FLAGS'])) // no such flag
				{
					unset($state['FLAGS'][$flag]);
					continue;
				}

				$value = static::convertToBoolean($value);
			}
			unset($value);
		}
		else
		{
			unset($state['FLAGS']);
		}

		return $state;
	}

	private static function convertToBoolean($value)
	{
		return $value === true || $value === 'true' || $value === '1' || $value == 'Y';
	}

	private static function getDefault()
	{
		$popupOpts = CTasksTools::getPopupOptions();

		return array(
			'BLOCKS' => array(
				// match task fields
				Task::SE_PREFIX.'CHECKLIST' => 	array(
					self::O_CHOSEN => false,
				),
				Task::SE_PREFIX.'ORIGINATOR' => array(
					self::O_CHOSEN => false,
				),
				Task::SE_PREFIX.'AUDITOR' => 	array(
					self::O_CHOSEN => false,
				),
				Task::SE_PREFIX.'ACCOMPLICE' => 	array(
					self::O_CHOSEN => false,
				),
				'DATE_PLAN' =>  	array(
					self::O_CHOSEN => false,
				),
				'OPTIONS' => array(
					self::O_CHOSEN => false,
				),
				Task::SE_PREFIX.'PROJECT' => 	array(
					self::O_CHOSEN => false,
				),
				'TIMEMAN' =>  	array(
					self::O_CHOSEN => false,
				),
				Task::SE_PREFIX.'REMINDER' => array(
					self::O_CHOSEN => false,
				),
				Task::SE_PREFIX.'TEMPLATE' =>  	array(
					self::O_CHOSEN => false,
				),
				Task::SE_PREFIX.'PROJECTDEPENDENCE' => array(
					self::O_CHOSEN => false,
				),
				'UF_CRM_TASK' =>  	array(
					self::O_CHOSEN => false,
				),
				Task\ParentTask::getCode(true) => array(
					self::O_CHOSEN => false,
				),
				Task::SE_PREFIX.'TAG' => array(
					self::O_CHOSEN => false,
				),
				'USER_FIELDS' => array(
					self::O_CHOSEN => false,
				),
				Task::SE_PREFIX.'RELATEDTASK' => array(
					self::O_CHOSEN => false,
				),
			),
			'FLAGS' => array(
				'ALLOW_TIME_TRACKING' => 	$popupOpts['time_tracking'] == 'Y',
				'TASK_CONTROL' => 			$popupOpts['task_control'] == 'Y',
				'ALLOW_CHANGE_DEADLINE' =>  true,
				'MATCH_WORK_TIME' => 		false,
				'FORM_FOOTER_PIN' => 		false
			)
		);
	}
}