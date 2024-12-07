<?php

namespace Bitrix\Tasks\Flow\Internal\Event\Project;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Internal\FlowTable;
use Bitrix\Tasks\Internals\Registry\GroupRegistry;
use CMain;

class FlowProjectEventHandler
{
	private CMain $application;
	private Connection $connection;

	private int $projectId = 0;

	public function __construct()
	{
		$this->init();
	}

	public function withProjectId(int $projectId): static
	{
		$this->projectId = $projectId;
		return $this;
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws LoaderException
	 */
	public function onProjectDelete(): bool
	{
		$query = FlowTable::query()
			->addSelect(new ExpressionField('1', '1'))
			->where('GROUP_ID', $this->projectId)
			->getQuery();

		if ($this->connection->query($query)->fetch() !== false)
		{
			$group = GroupRegistry::getInstance()->get($this->projectId);

			$message = $group['PROJECT']
				? Loc::getMessage('TASKS_FLOW_EVENT_PROJECT_WITH_FLOWS_TYPE_PROJECT')
				: Loc::getMessage('TASKS_FLOW_EVENT_PROJECT_WITH_FLOWS_TYPE_GROUP');

			$this->application->throwException($message);
			return false;
		}

		return true;
	}

	private function init(): void
	{
		global $APPLICATION;
		$this->application = $APPLICATION;
		$this->connection = Application::getConnection();
	}
}