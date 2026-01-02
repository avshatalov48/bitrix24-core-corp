<?php

namespace Bitrix\Mobile\Menu;

final class Analytics
{
	public static function menuOpen(
		string $tool,
		string $category,
		string $event = 'open_section',
		string $cSection = 'ava_menu',
	): array
	{
		return [
			'tool' => $tool,
			'category' => $category,
			'event' => $event,
			'c_section' => $cSection,
		];
	}

	public static function calendar(): array
	{
		return self::menuOpen('calendar', 'calendar');
	}

	public static function disk(): array
	{
		return self::menuOpen('files', 'files_operations');
	}

	public static function stream(): array
	{
		return self::menuOpen('feed', 'posts_operations');
	}

	public static function tasks(): array
	{
		return self::menuOpen('tasks', 'tasks', 'tasks_projects_view');
	}

	public static function crmActivity(): array
	{
		return self::menuOpen('crm', 'activity_operations');
	}

	public static function crmCustomSection(): array
	{
		return self::menuOpen('crm', 'automation_operations');
	}

	public static function hrEmployees(): array
	{
		return self::menuOpen('hr', 'employees');
	}

	public static function groups(): array
	{
		return self::menuOpen('groups', 'groups');
	}

	public static function signDocuments(): array
	{
		return self::menuOpen('sign', 'documents');
	}

	public static function projects(): array
	{
		return self::menuOpen('tasks', 'project', 'tasks_projects_view');
	}

	public static function flows(): array
	{
		return self::menuOpen('tasks', 'flows', 'flows_view');
	}

	public static function telephony(): array
	{
		return self::menuOpen('telephony', 'telephony', 'dial_number');
	}

	public static function whatsNew(): array
	{
		return self::menuOpen('intranet', 'whats_new', 'drawer_open');
	}

	public static function profileView(): array
	{
		return self::menuOpen('intranet', 'user_profile', 'profile_view');
	}

	public static function knowledge(): array
	{
		return self::menuOpen('landing', 'kb', 'open_start_page');
	}
}
