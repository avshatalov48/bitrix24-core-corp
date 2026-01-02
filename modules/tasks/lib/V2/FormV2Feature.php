<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2;

use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Config\Option;

class FormV2Feature
{
	public static function isOn(string $feature = '', int $groupId = null): bool
	{
		if ($feature === 'miniform')
		{
			return true;
		}

		if (in_array($groupId, self::getAllowedGroups(), true))
		{
			return true;
		}

		if (Option::get('tasks', 'tasks_form_v2', 'N') === 'Y')
		{
			return true;
		}

		if ($feature !== '' && static::isDevMode())
		{
			return true;
		}

		$feature = match ($feature)
		{
			'miniform' => 'tasks_form_v2_miniform',
			'create' => 'tasks_form_v2_create',
			'update' => 'tasks_form_v2_update',
			'delete' => 'tasks_form_v2_delete',
			'favorite' => 'tasks_form_v2_favorite',
			'watch' => 'tasks_form_v2_watch',
			'option' => 'tasks_form_v2_option',
			'priority' => 'tasks_form_v2_priority',
			'timer' => 'tasks_form_v2_timer',
			'status' => 'tasks_form_v2_status',
			'planner' => 'tasks_form_v2_planner',
			'elapsed' => 'tasks_form_v2_elapsed',
			'view' => 'tasks_form_v2_view',
			'move' => 'tasks_form_v2_move',
			'reminder' => 'tasks_form_v2_reminder',
			'gantt' => 'tasks_form_v2_gantt',
			default => '',
		};

		if ($feature === '')
		{
			return false;
		}

		return Option::get('tasks', $feature, 'N') === 'Y';
	}

	public static function turnOn(string $feature = ''): bool
	{
		$option = $feature === '' ? 'tasks_form_v2' : self::getOptionByFeature($feature);

		if ($option === '')
		{
			return false;
		}

		Option::set('tasks', $option, 'Y');

		return true;
	}

	public static function turnOff(string $feature = ''): bool
	{
		$option = $feature === '' ? 'tasks_form_v2' : self::getOptionByFeature($feature);

		if ($option === '')
		{
			return false;
		}

		Option::set('tasks', $option, 'N');

		return true;
	}

	public static function getAllowedGroups(): array
	{
		$groups = Option::get('tasks', 'tasks_form_v2_groups', '');

		return array_map('intval', array_filter(explode(',', $groups)));
	}

	public static function isDevMode(): bool
	{
		$exceptionHandling = Configuration::getValue('exception_handling');

		return !empty($exceptionHandling['debug']) && Option::get('tasks', 'tasks_api_v2', 'N') === 'Y';
	}

	public static function getOptionByFeature(string $feature): string
	{
		return match ($feature)
		{
			'miniform' => 'tasks_form_v2_miniform',
			'create' => 'tasks_form_v2_create',
			'update' => 'tasks_form_v2_update',
			'delete' => 'tasks_form_v2_delete',
			'favorite' => 'tasks_form_v2_favorite',
			'watch' => 'tasks_form_v2_watch',
			'option' => 'tasks_form_v2_option',
			'priority' => 'tasks_form_v2_priority',
			'timer' => 'tasks_form_v2_timer',
			'status' => 'tasks_form_v2_status',
			'planner' => 'tasks_form_v2_planner',
			'elapsed' => 'tasks_form_v2_elapsed',
			'view' => 'tasks_form_v2_view',
			'move' => 'tasks_form_v2_move',
			'reminder' => 'tasks_form_v2_reminder',
			default => '',
		};
	}
}
