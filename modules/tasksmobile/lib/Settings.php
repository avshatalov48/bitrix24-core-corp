<?php

namespace Bitrix\TasksMobile;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\MobileApp\Mobile;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Ui\Filter\Task;

final class Settings
{
	public const IS_BETA_AVAILABLE = 'isBetaAvailable';
	public const IS_BETA_ACTIVE = 'isBetaActive';

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

	public function clientHasApiVersion(int $apiVersion): bool
	{
		return Mobile::getInstance()::getApiVersion() >= $apiVersion;
	}

	public function isBetaAvailable(): bool
	{
		return (
			Mobile::getInstance()::$isDev
			|| (
				$this->clientHasApiVersion(54)
				&& Option::get('tasksmobile', Settings::IS_BETA_AVAILABLE, 'Y', '-') === 'Y'
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

	public function isTaskFlowAvailable(): bool
	{
		return FlowFeature::isOn() && $this->clientHasApiVersion(54);
	}

	public function getDashboardSelectedView(int $projectId = 0): string
	{
		Task::setUserId($this->userId);
		Task::setGroupId($projectId);

		$listState = Task::getListStateInstance()->getState();
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
