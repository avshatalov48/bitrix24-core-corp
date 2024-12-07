<?php

namespace Bitrix\Tasks\Flow\Template\Access\Permission;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Tasks\Access\Permission\TasksTemplatePermission;
use Bitrix\Tasks\Access\Permission\TasksTemplatePermissionTable;
use Bitrix\Tasks\Flow\Control\Exception\InvalidCommandException;
use Bitrix\Tasks\Internals\Log\Logger;
use Throwable;

class TemplatePermissionService
{
	protected const LOCK_TIMEOUT = 15;

	protected TemplatePermissionCommand $command;
	protected Connection $connection;

	public function __construct()
	{
		$this->init();
	}

	/**
	 * @throws InvalidCommandException
	 */
	public function merge(TemplatePermissionCommand $command): void
	{
		$this->command = $command;
		$this->command->validateAdd();

		$lockName = 'tasks_flow_template_update_lock_' . $this->command->templateId;

		if ($this->connection->lock($lockName, static::LOCK_TIMEOUT))
		{
			try
			{
				$this->deleteAffected();
				$this->insert();
			}
			catch (Throwable $t)
			{
				Logger::logThrowable($t, 'TASKS_FLOW_TEMPLATE_ACCESS_UPDATE');
			}
			finally
			{
				$this->connection->unlock($lockName);
			}
		}
	}

	protected function deleteAffected(): void
	{
		TasksTemplatePermissionTable::deleteList([
			'=TEMPLATE_ID' => $this->command->templateId,
			'@ACCESS_CODE' => $this->command->accessCodes,
		]);
	}

	protected function insert(): void
	{
		foreach ($this->command->accessCodes as $accessCode)
		{
			$permission = (new TasksTemplatePermission())
				->setTemplateId($this->command->templateId)
				->setAccessCode($accessCode)
				->setPermissionId($this->command->permissionId)
				->setValue($this->command->value);

			$permission->save();
		}
	}

	protected function init(): void
	{
		$this->connection = Application::getConnection();
	}
}