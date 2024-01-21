<?php

namespace Bitrix\TasksMobile;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\MobileApp\Mobile;

final class Settings
{
	public const BETA_API_VERSION = 52;
	public const NEW_DASHBOARD_API_VERSION = 52;

	public const IS_BETA_AVAILABLE = 'isBetaAvailable';
	public const IS_BETA_ACTIVE = 'isBetaActive';
	public const IS_NEW_CHECKLIST_ACTIVE = 'isNewChecklistActive';
	public const IS_NEW_DASHBOARD_ACTIVE = 'isNewDashboardActive';

	protected int $userId;

	private static ?Settings $instance = null;

	public static function getInstance(): Settings
	{
		if (!Settings::$instance)
		{
			Settings::$instance = new Settings();
		}

		return Settings::$instance;
	}

	private function __construct(?int $userId = null)
	{
		if (!$userId)
		{
			$userId = CurrentUser::get()->getId();
		}
		$this->userId = $userId;
	}

	public function isNewDashboardActive(): bool
	{
		return
			Settings::getInstance()->isBetaActive()
			|| (
				Mobile::getInstance()::getApiVersion() >= Settings::NEW_DASHBOARD_API_VERSION
				&& (Option::get('tasksmobile', Settings::IS_NEW_DASHBOARD_ACTIVE, 'Y', '-') === 'Y')
			)
		;
	}

	public function isBetaAvailable(): bool
	{
		return (
			Mobile::getInstance()::getApiVersion() >= Settings::BETA_API_VERSION
			&& (
				Mobile::getInstance()::$isDev
				|| Option::get('tasksmobile', Settings::IS_BETA_AVAILABLE, 'N', '-') === 'Y'
			)
		);
	}

	public function isBetaActive(): bool
	{
		if ($this->isBetaAvailable())
		{
			return \CUserOptions::GetOption('tasksmobile', Settings::IS_BETA_ACTIVE, true, $this->userId);
		}

		return false;
	}

	public function activateBeta(): void
	{
		\CUserOptions::SetOption('tasksmobile', Settings::IS_BETA_ACTIVE, true, false, $this->userId);
	}

	public function deactivateBeta(): void
	{
		\CUserOptions::SetOption('tasksmobile', Settings::IS_BETA_ACTIVE, false, false, $this->userId);
	}

	public function isNewChecklistActive(): bool
	{
		return \CUserOptions::GetOption('tasksmobile', Settings::IS_NEW_CHECKLIST_ACTIVE, false, $this->userId);
	}

	public function activateNewChecklist(): void
	{
		\CUserOptions::SetOption('tasksmobile', Settings::IS_NEW_CHECKLIST_ACTIVE, true, false, $this->userId);
	}

	public function deactivateNewChecklist(): void
	{
		\CUserOptions::SetOption('tasksmobile', Settings::IS_NEW_CHECKLIST_ACTIVE, false, false, $this->userId);
	}

	public function getDashboardSelectedView(int $projectId = 0): string
	{
		\Bitrix\Tasks\Ui\Filter\Task::setUserId($this->userId);
		\Bitrix\Tasks\Ui\Filter\Task::setGroupId($projectId);

		$listState = \Bitrix\Tasks\Ui\Filter\Task::getListStateInstance()->getState();
		$selectedView = $listState['VIEW_SELECTED']['CODENAME'];
		$map = [
			'VIEW_MODE_LIST' => 'LIST',
			'VIEW_MODE_KANBAN' => 'KANBAN',
			'VIEW_MODE_TIMELINE' => 'DEADLINE',
			'VIEW_MODE_PLAN' => 'PLANNER',
		];

		if (!isset($map[$selectedView]) || ($map[$selectedView] === 'KANBAN' && $projectId === 0))
		{
			return 'LIST';
		}

		return $map[$selectedView];
	}
}
