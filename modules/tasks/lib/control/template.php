<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Main\Application;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\Control\Exception\TemplateAddException;
use Bitrix\Tasks\Control\Exception\TemplateUpdateException;
use Bitrix\Tasks\Control\Handler\Exception\TemplateFieldValidateException;
use Bitrix\Tasks\Control\Handler\TemplateFieldHandler;
use Bitrix\Tasks\Internals\DataBase\Tree\TargetNodeNotFoundException;
use Bitrix\Tasks\Internals\Task\Template\ScenarioTable;
use Bitrix\Tasks\Internals\Task\Template\TemplateDependenceTable;
use Bitrix\Tasks\Internals\Task\Template\TemplateMemberTable;
use Bitrix\Tasks\Internals\Task\Template\TemplateObject;
use Bitrix\Tasks\Internals\Task\Template\DependenceTable;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Internals\Task\Template\TemplateTagTable;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use Bitrix\Tasks\Item\SystemLog;

class Template
{
	private const FIELD_SCENARIO = 'SCENARIO_NAME';

	private $userId;

	private $db;
	private $ufManager;
	private $application;

	private $templateId;
	private $template;

	private $checkFileRights = false;
	private $deleteSubTemplates = false;
	private $unsafeDelete = false;
	private $skipAgent = false;


	public function __construct(int $userId)
	{
		global $DB;
		global $USER_FIELD_MANAGER;
		global $APPLICATION;

		$this->userId = $userId;
		$this->db = $DB;
		$this->ufManager = $USER_FIELD_MANAGER;
		$this->application = $APPLICATION;
	}

	/**
	 * @return $this
	 */
	public function withCheckFileRights(): self
	{
		$this->checkFileRights = true;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function withDeleteSubTemplates(): self
	{
		$this->deleteSubTemplates = true;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function withUnsafeDelete(): self
	{
		$this->unsafeDelete = true;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function withSkipAgent(): self
	{
		$this->skipAgent = true;
		return $this;
	}

	/**
	 * @param array $fields
	 * @return TemplateObject
	 * @throws TemplateAddException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function add(array $fields): TemplateObject
	{
		try
		{
			$fields = $this->prepareFields($fields);
		}
		catch (TemplateFieldValidateException $e)
		{
			$message = $e->getMessage();
			$this->application->ThrowException(new \CAdminException([
				['text' => $message]
			]));

			throw new TemplateAddException($e->getMessage());
		}

		if (empty($fields))
		{
			throw new TemplateAddException();
		}

		if (!$this->ufManager->CheckFields(\Bitrix\Tasks\Util\UserField\Task\Template::getEntityCode(), 0, $fields, $this->userId))
		{
			$msg = $this->getApplicationError();
			throw new TemplateAddException($msg);
		}

		try
		{
			$template = $this->insert($fields);
			$this->templateId = $template->getId();
		}
		catch (\Exception $e)
		{
			throw new TemplateAddException();
		}

		$this->setMembers($fields);
		$this->setTags($fields);
		$this->setDependTasks($fields);
		$this->setScenario($fields);

		$this->ufManager->Update(\Bitrix\Tasks\Util\UserField\Task\Template::getEntityCode(), $this->templateId, $fields, $this->userId);

		$this->enableReplication($fields);
		$this->setParent($fields);
		$this->setRights($fields);

		return $template;
	}

	/**
	 * @param int $id
	 * @return bool
	 */
	public function delete(int $id): bool
	{
		if ($id < 1)
		{
			return false;
		}

		$this->templateId = $id;

		try
		{
			$template = $this->getTemplateData();
			if (!$template)
			{
				return false;
			}

			if ($this->unsafeDelete)
			{
				return $this->fullDelete();
			}

			$safeDelete = false;
			if (Loader::includeModule('recyclebin'))
			{
				$result = \Bitrix\Tasks\Integration\Recyclebin\Template::OnBeforeDelete($this->templateId, $template);
				if (!$result->isSuccess())
				{
					return false;
				}

				$safeDelete = true;
			}

			if(!$this->onBeforeDelete())
			{
				return false;
			}

			$this->safeDelete();

			if (!$safeDelete)
			{
				$this->fullDelete();
			}

			$this->onDelete();
		}
		catch (\Exception $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * @param int $id
	 * @param array $fields
	 * @return TemplateObject
	 * @throws TemplateUpdateException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function update(int $id, array $fields): TemplateObject
	{
		if ($id < 1)
		{
			throw new TemplateUpdateException();
		}

		$this->templateId = $id;
		$template = $this->getTemplateData();

		if ((int)($template['BASE_TEMPLATE_ID'] ?? null) > 0)
		{
			unset($fields['REPLICATE'], $fields['PARENT_ID']);
			$isReplicateParamsChanged = false;
		}
		else
		{
			$isReplicateParamsChanged =
				(
					isset($fields['REPLICATE'])
					&& $template['REPLICATE'] !== $fields['REPLICATE']
				)
				||
				(
					isset($fields['REPLICATE_PARAMS'])
					&& $template['REPLICATE_PARAMS'] !== $fields['REPLICATE_PARAMS']
				);
		}

		try
		{
			$fields = $this->prepareFields($fields);
		}
		catch (TemplateFieldValidateException $e)
		{
			$message = $e->getMessage();
			$this->application->ThrowException(new \CAdminException([
				['text' => $message]
			]));

			throw new TemplateUpdateException($e->getMessage());
		}

		if (!$this->ufManager->CheckFields(\Bitrix\Tasks\Util\UserField\Task\Template::getEntityCode(), $this->templateId, $fields))
		{
			$msg = $this->getApplicationError();
			throw new TemplateUpdateException($msg);
		}

		$templateObject = $this->save($fields);

		$this->setMembers($fields);
		$this->setTags($fields);
		$this->setDependTasks($fields);

		$this->ufManager->Update(\Bitrix\Tasks\Util\UserField\Task\Template::getEntityCode(), $this->templateId, $fields, $this->userId);
		$this->updateParent($fields);

		if ($isReplicateParamsChanged && !$this->skipAgent)
		{
			$this->enableReplication($fields);
		}

		return $templateObject;
	}

	/**
	 * @param array $fields
	 * @return void
	 * @throws Exception\TemplateNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function setMembers(array $fields)
	{
		$handler = new TemplateMember($this->userId, $this->templateId);
		$handler->set($fields);
	}

	/**
	 * @param array $fields
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function setScenario(array $fields): void
	{
		if (empty($fields[self::FIELD_SCENARIO]))
		{
			// set default scenario if none specified
			$fields[self::FIELD_SCENARIO] = ScenarioTable::SCENARIO_DEFAULT;
		}
		ScenarioTable::insertIgnore($this->templateId, $fields[self::FIELD_SCENARIO]);
	}

	/**
	 * @param array $fields
	 * @return void
	 * @throws Exception\TemplateNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function setTags(array $fields)
	{
		$handler = new TemplateTag($this->userId, $this->templateId);
		$handler->set($fields);
	}

	/**
	 * @param array $fields
	 * @return void
	 * @throws Exception\TemplateNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function setDependTasks(array $fields)
	{
		$handler = new TemplateDependence($this->userId, $this->templateId);
		$handler->set($fields);
	}

	/**
	 * @return string
	 */
	private function getApplicationError(string $default = ''): string
	{
		$e = $this->application->GetException();

		if (is_a($e, \CApplicationException::class))
		{
			$message = $e->GetString();
			$message = explode('<br>', $message);
			return $message[0];
		}

		if (
			!is_object($e)
			|| !isset($e->messages)
			|| !is_array($e->messages)
		)
		{
			return $default;
		}

		$message = array_shift($e->messages);

		if (
			is_array($message)
			&& isset($message['text'])
		)
		{
			$message = $message['text'];
		}
		elseif (!is_string($message))
		{
			$message = $default;
		}

		return $message;
	}

	/**
	 * @param array $fields
	 * @return void
	 */
	private function updateParent(array $fields)
	{
		if (!isset($fields['BASE_TEMPLATE_ID']))
		{
			return;
		}

		try
		{
			DependenceTable::link($this->templateId, intval($fields['BASE_TEMPLATE_ID']));
		}
		catch(\Bitrix\Tasks\DB\Tree\LinkExistsException $e)
		{
		}
	}

	/**
	 * @param array $data
	 * @return TemplateObject
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function save(array $data): TemplateObject
	{
		$handler = new TemplateFieldHandler($this->userId, $data);
		$data = $handler->getFieldsToDb();

		$result = TemplateTable::update($this->templateId, $data);

		if (!$result->isSuccess())
		{
			throw new TemplateUpdateException();
		}

		$template = TemplateTable::getByPrimary($this->templateId)->fetchObject();

		return $template;
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function fullDelete(): bool
	{
		$template = $this->getTemplateData();

		// delete syslog records
		SystemLog::deleteByEntity($this->templateId, 1);

		// delete files
		if ($template["FILES"])
		{
			$files = unserialize($template["FILES"], ['allowed_classes' => false]);
			if (is_array($files))
			{
				$filesToDelete = array();
				foreach ($files as $file)
				{
					$rsFile = \CTaskFiles::GetList([], ["FILE_ID" => $file]);
					if (!$arFile = $rsFile->Fetch())
					{
						$filesToDelete[] = $file;
					}
				}
				foreach ($filesToDelete as $file)
				{
					\CFile::Delete($file);
				}
			}
		}

		// delete checklist
		TemplateCheckListFacade::deleteByEntityIdOnLowLevel($this->templateId);

		// delete access rights
		\Bitrix\Tasks\Item\Access\Task\Template::revokeAll($this->templateId, ['CHECK_RIGHTS' => false]);

		TemplateMemberTable::deleteList([
			'=TEMPLATE_ID' => $this->templateId,
		]);

		TemplateTagTable::deleteList([
			'=TEMPLATE_ID' => $this->templateId,
		]);

		TemplateDependenceTable::deleteList([
			'=TEMPLATE_ID' => $this->templateId,
		]);

		ScenarioTable::delete($this->templateId);

		// delete sub templates
		if ($this->deleteSubTemplates)
		{
			$subTemplatesBdResult = DependenceTable::getSubTree($this->templateId, ['select' => ['ID' => 'TEMPLATE_ID']], ['INCLUDE_SELF' => false]);
			while ($subTemplateItem = $subTemplatesBdResult->fetch())
			{
				$manager = new self($this->userId);
				if ($this->unsafeDelete)
				{
					$manager->withUnsafeDelete();
				}
				if ($this->deleteSubTemplates)
				{
					$manager->withDeleteSubTemplates();
				}
				$manager->delete((int)$subTemplateItem['ID']);
			}

			try
			{
				DependenceTable::deleteSubtree($this->templateId);
			}
			catch (TargetNodeNotFoundException $e)
			{
				// had no children, actually don't care
			}
		}

		// delete user fields
		$this->ufManager->Delete("TASKS_TASK_TEMPLATE", $this->templateId);

		$res = TemplateTable::delete($this->templateId);

		return $res->isSuccess();
	}

	/**
	 * @return bool
	 */
	private function onDelete(): bool
	{
		$template = $this->getTemplateData();

		foreach (GetModuleEvents('tasks', 'OnTaskTemplateDelete', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($this->templateId, $template)) === false)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Tasks\Internals\DataBase\Tree\Exception
	 * @throws \Bitrix\Tasks\Internals\DataBase\Tree\LinkExistsException
	 * @throws \Bitrix\Tasks\Internals\DataBase\Tree\ParentNodeNotFoundException
	 * @throws \Bitrix\Tasks\Internals\DataBase\Tree\TargetNodeNotFoundException
	 */
	private function safeDelete()
	{
		$connection = Application::getConnection();
		$connection->queryExecute('UPDATE b_tasks_template SET ZOMBIE = \'Y\' WHERE ID = ' . $this->templateId);

		$this->removeAgents();

		$parent = DependenceTable::getParentId($this->templateId)->fetch();
		$parentId = $parent['PARENT_TEMPLATE_ID'];
		$subTree = DependenceTable::getSubTree($this->templateId, [], ['INCLUDE_SELF' => false])->fetchAll();

		// delete link to parent
		DependenceTable::unlink($this->templateId);

		// reattach sub templates
		if ($parentId)
		{
			foreach ($subTree as $element)
			{
				if ($element['DIRECT'] == 1)
				{
					DependenceTable::moveLink($element['TEMPLATE_ID'], $parentId);
				}
			}
		}
		else
		{
			foreach ($subTree as $element)
			{
				if ($element['DIRECT'] == 1)
				{
					DependenceTable::unlink($element['TEMPLATE_ID']);
				}
			}
		}

		// delete item itself
		$select = ['TEMPLATE_ID', 'PARENT_TEMPLATE_ID'];
		$filter = [
			'=TEMPLATE_ID' => $this->templateId,
			'=PARENT_TEMPLATE_ID' => $this->templateId
		];

		$item = DependenceTable::getList(['select' => $select, 'filter' => $filter])->fetch();
		DependenceTable::delete($item);
	}

	/**
	 * @return bool
	 */
	private function onBeforeDelete(): bool
	{
		$template = $this->getTemplateData();
		foreach (GetModuleEvents('tasks', 'OnBeforeTaskTemplateDelete', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($this->templateId, $template)) === false)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array $fields
	 * @return void
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	private function setRights(array $fields)
	{
		\Bitrix\Tasks\Item\Access\Task\Template::grantAccessLevel(
			$this->templateId,
			'U'.$this->userId,
			'full',
			[
				'CHECK_RIGHTS' => false,
			]
		);

		if ($this->userId !== (int) $fields['CREATED_BY'])
		{
			\Bitrix\Tasks\Item\Access\Task\Template::grantAccessLevel(
				$this->templateId,
				'U'.$fields['CREATED_BY'],
				'full',
				[
					'CHECK_RIGHTS' => false,
				]
			);
		}
	}

	/**
	 * @param array $fields
	 * @return void
	 */
	private function setParent(array $fields)
	{
		if (!(int)($fields['BASE_TEMPLATE_ID'] ?? null))
		{
			return;
		}

		try
		{
			DependenceTable::createLink($this->templateId, intval($fields['BASE_TEMPLATE_ID']));
		}
		catch(\Bitrix\Tasks\DB\Tree\LinkExistsException $e)
		{
		}
	}

	/**
	 * @param array $fields
	 * @return void
	 */
	private function enableReplication(array $fields)
	{
		$name = 'CTasks::RepeatTaskByTemplateId('.$this->templateId.');';

		// First, remove all agents for this template
		$this->removeAgents();

		if (
			!array_key_exists('REPLICATE', $fields)
			|| $fields['REPLICATE'] !== 'Y'
		)
		{
			return;
		}

		$nextTime = \CTasks::getNextTime($fields['REPLICATE_PARAMS'], $this->templateId); // localtime
		if ($nextTime)
		{

			\CAgent::AddAgent(
				$name,
				'tasks',
				'N', 		// is periodic?
				86400, 		// interval (24 hours)
				$nextTime, 	// datecheck
				'Y', 		// is active?
				$nextTime	// next_exec
			);
		}
	}

	/**
	 * @param int $templateId
	 * @return void
	 */
	private function removeAgents()
	{
		\CAgent::RemoveAgent('CTasks::RepeatTaskByTemplateId('.$this->templateId.');', 'tasks');
		\CAgent::RemoveAgent('CTasks::RepeatTaskByTemplateId('.$this->templateId.', 0);', 'tasks');
		\CAgent::RemoveAgent('CTasks::RepeatTaskByTemplateId('.$this->templateId.', 1);', 'tasks');
	}

	/**
	 * @param array $data
	 * @return TemplateObject
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function insert(array $data): TemplateObject
	{
		$handler = new TemplateFieldHandler($this->userId, $data);
		$data = $handler->getFieldsToDb();

		$template = new TemplateObject($data);
		$result = $template->save();

		if (!$result->isSuccess())
		{
			throw new TemplateAddException();
		}

		return $template;
	}

	/**
	 * @param array $fields
	 * @return array
	 * @throws TemplateFieldValidateException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function prepareFields(array $fields): array
	{
		$templateData = $this->getTemplateData() ?? [];
		$handler = new TemplateFieldHandler($this->userId, $fields, $templateData);

		$handler
			->prepareResponsible()
			->prepareMultitask()
			->prepareBBCodes()
			->prepareType()
			->prepareMembers()
			->prepareTitle()
			->prepareReplication()
			->prepareBaseTemplate()
			->prepareParentId()
			->preparePriority()
			->prepareSiteId()
			->prepareTags()
			->prepareDependencies()
			->prepareDescription();

		return $handler->getFields();
	}

	/**
	 * @return array|null
	 */
	private function getTemplateData(): ?array
	{
		if (!$this->templateId)
		{
			return null;
		}

		$templateDbResult = \CTaskTemplates::GetByID($this->templateId);
		$template = $templateDbResult->Fetch();

		if (!$template)
		{
			return null;
		}

		$this->template = $template;
		return $this->template;
	}
}