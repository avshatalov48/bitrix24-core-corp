<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 *
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 */
global $APPLICATION, $DB;

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\Item\Access;
use Bitrix\Tasks\Item\SystemLog;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Internals\DataBase\Tree\TargetNodeNotFoundException;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use Bitrix\Tasks\Internals\Task\Template\DependenceTable;
use Bitrix\Tasks\Template;

Loc::loadMessages(__FILE__);

class CTaskTemplates
{
	const TYPE_FOR_NEW_USER =		0x01;

	const CURRENT_SITE_ID =			'*';

	private $_errors = array();

	/**
	 * Get tasks fields info (for rest, etc)
	 *
	 * @return array
	 */
	public static function getFieldsInfo()
	{
		global $USER_FIELD_MANAGER;

		$fields = [
			"ID" => [
				'type'    => 'integer',
				'primary' => true
			],
			"PARENT_ID"   => [
				'type'    => 'integer',
				'default' => 0
			],
			"TITLE" => [
				'type'    => 'string',
				'required' => true
			],
			"DESCRIPTION" => [
				'type'    => 'string'
			],

			"PRIORITY"    => [
				'type'    => 'enum',
				'values'  => [
					\CTasks::PRIORITY_HIGH    => Loc::getMessage('TASKS_FIELDS_PRIORITY_HIGH'),
					\CTasks::PRIORITY_AVERAGE => Loc::getMessage('TASKS_FIELDS_PRIORITY_AVERAGE'),
					\CTasks::PRIORITY_LOW     => Loc::getMessage('TASKS_FIELDS_PRIORITY_LOW')
				],
				'default' => \CTasks::PRIORITY_AVERAGE
			],


			"GROUP_ID" => [
				'type'    => 'integer',
				'default' => 0
			],
			"STAGE_ID" => [
				'type'    => 'integer',
				'default' => 0
			],

			"CREATED_BY"     => [
				'type'     => 'integer',
				'required' => true
			],
			"RESPONSIBLE_ID" => [
				'type'     => 'integer',
				'required' => true
			],
			"DEPENDS_ON" => [
				'type'     => 'integer',
				'required' => true
			],
			"RESPONSIBLES" => [
				'type'     => 'array'
			],
			"ACCOMPLICES" => [
				'type'     => 'array'
			],
			"AUDITORS" => [
				'type'     => 'array'
			],
			"STATUS"      => [
				'type'    => 'enum',
				'values'  => [
					\CTasks::STATE_PENDING              => Loc::getMessage('TASKS_FIELDS_STATUS_PENDING'),
					\CTasks::STATE_IN_PROGRESS          => Loc::getMessage('TASKS_FIELDS_STATUS_IN_PROGRESS'),
					\CTasks::STATE_SUPPOSEDLY_COMPLETED => Loc::getMessage('TASKS_FIELDS_STATUS_SUPPOSEDLY_COMPLETED'),
					\CTasks::STATE_COMPLETED            => Loc::getMessage('TASKS_FIELDS_STATUS_COMPLETED'),
					\CTasks::STATE_DEFERRED             => Loc::getMessage('TASKS_FIELDS_STATUS_DEFERRED')
				],
				'default' => \CTasks::STATE_PENDING
			],

			"MULTITASK" => [
				'type'    => 'enum',
				'values'  => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N'
			],
			"REPLICATE" => [
				'type'    => 'enum',
				'values'  => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N'
			],
			"SITE_ID"        => [
				'type' => 'string',
			],
			"TASK_CONTROL"          => [
				'type'    => 'enum',
				'values'  => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N'
			],
			"ADD_IN_REPORT"         => [
				'type'    => 'enum',
				'values'  => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N'
			],

			"XML_ID" => [
				'type'    => 'string',
				'default' => null
			],


			'DEADLINE_AFTER' => [
				'type'    => 'datetime',
				'default' => null
			],
			'START_DATE_PLAN_AFTER'=> [
				'type'    => 'datetime',
				'default' => null
			],
			'END_DATE_PLAN_AFTER' => [
				'type'    => 'datetime',
				'default' => null
			],

			'TPARAM_TYPE' => [
				'type'    => 'enum',
				'values'=>[
					1 => Loc::getMessage('TASKS_FIELDS_Y'),
					0 => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 0
			],
			'TPARAM_REPLICATION_COUNT' => [
				'type'    => 'integer',
				'default' => 0
			],
			'REPLICATE_PARAMS' => [
				'type'    => 'string'
			],

			'BASE_TEMPLATE_ID' => [
				'type'    => 'integer',
				'default' => null
			],
			'TEMPLATE_CHILDREN_COUNT' => [
				'type'    => 'integer',
				'default' => null
			],

		];

		foreach ($fields as $fieldId => &$fieldData)
		{
			$fieldData = array_merge(['title' => Loc::getMessage('TASKS_TEMPLATE_FIELDS_'.$fieldId)], $fieldData);
		}
		unset($fieldData);


		$uf = $USER_FIELD_MANAGER->GetUserFields("TASKS_TASK_TEMPLATE");
		foreach ($uf as $key => $item)
		{
			$fields[$key] = [
				'title' => $item['USER_TYPE']['DESCRIPTION'],
				'type'  => $item['USER_TYPE_ID']
			];
		}

		return $fields;
	}

	function GetErrors()
	{
		return $this->_errors;
	}


	function CheckFields(&$arFields, $ID = false, $arParams = array())
	{
		global $APPLICATION;

		if(intval($ID))
		{
			if(!is_array($this->currentData))
				$this->currentData = self::GetById($ID)->fetch();
		}

		////////////////////////////////////
		// deal with TYPE

		if(intval($this->currentData['ID']) && isset($arFields['TPARAM_TYPE']) && $this->currentData['TPARAM_TYPE'] != $arFields['TPARAM_TYPE'])
		{
			$this->_errors[] = array("text" => 'You can not change TYPE of an existing template', "id" => "ERROR_TASKS_BAD_TEMPLATE_PARAMETER_TYPE");
			unset($arFields['TPARAM_TYPE']); // You may not change template type of an existing template
		}

		// check allowed value
		if(isset($arFields['TPARAM_TYPE']))
		{
			if($arFields['TPARAM_TYPE'] != static::TYPE_FOR_NEW_USER) // white list here later
				$this->_errors[] = array("text" => 'Unknown template type id passed', "id" => "ERROR_TASKS_BAD_TEMPLATE_PARAMETER_TYPE");
		}

		// unset some data, if type is TYPE_FOR_NEW_USER
		if((!intval($ID) && $arFields['TPARAM_TYPE'] == CTaskTemplates::TYPE_FOR_NEW_USER) || (intval($ID) && $this->currentData['TPARAM_TYPE'] == CTaskTemplates::TYPE_FOR_NEW_USER))
		{
			$arFields['BASE_TEMPLATE_ID'] = ''; // such kind of template can not have parent template ...
			$arFields['REPLICATE_PARAMS'] = serialize(array()); // ... and replication params
			$arFields['RESPONSIBLE_ID'] = '0'; // ... and responsible_id
			$arFields['RESPONSIBLES'] = serialize(array(0)); // ... and other responsibles
			$arFields['MULTITASK'] = 'N'; // ... and be a multitask
		}

		////////////////////////////////////
		// deal with RESPONSIBLES

		if(($ID === false && $arFields['TPARAM_TYPE'] != CTaskTemplates::TYPE_FOR_NEW_USER) || ($ID !== false && $this->currentData['TPARAM_TYPE'] != CTaskTemplates::TYPE_FOR_NEW_USER))
		{
			if(isset($arFields["RESPONSIBLE_ID"]))
			{
				/** @noinspection PhpDynamicAsStaticMethodCallInspection */
				$r = CUser::GetByID($arFields["RESPONSIBLE_ID"]);
				if (!$r->Fetch())
				{
					$this->_errors[] = array("text" => GetMessage("TASKS_BAD_RESPONSIBLE_ID_EX"), "id" => "ERROR_TASKS_BAD_RESPONSIBLE_ID_EX");
				}
			}
			else
			{
				if($ID === false)
					$this->_errors[] = array("text" => GetMessage("TASKS_BAD_RESPONSIBLE_ID"), "id" => "ERROR_TASKS_BAD_RESPONSIBLE_ID");
			}
		}

		////////////////////////////////////
		// deal with other data

		if ((is_set($arFields, "TITLE") || $ID === false) && $arFields["TITLE"] == '')
		{
			$this->_errors[] = array("text" => GetMessage("TASKS_BAD_TITLE"), "id" => "ERROR_BAD_TASKS_TITLE");
		}

		if(array_key_exists('TPARAM_REPLICATION_COUNT', $arFields))
		{
			$arFields['TPARAM_REPLICATION_COUNT'] = intval($arFields['TPARAM_REPLICATION_COUNT']);
		}
		elseif(!$ID)
		{
			$arFields['TPARAM_REPLICATION_COUNT'] = 1;
		}

		/*
		if(!$ID)
		{
			// since this time we dont allow to create tasks with a non-bbcode description
			if($arFields['DESCRIPTION_IN_BBCODE'] == 'N')
			{
				$this->_errors[] = array("text" => GetMessage("TASKS_DESCRIPTION_IN_BBCODE_NO_NOT_ALLOWED"), "id" => "ERROR_TASKS_DESCRIPTION_IN_BBCODE_NO_NOT_ALLOWED");
			}
			else
			{
				$arFields['DESCRIPTION_IN_BBCODE'] = 'Y';
			}
		}
		*/

		if (isset($arFields['BASE_TEMPLATE_ID']))
		{
			try
			{
				if(intval($arFields['BASE_TEMPLATE_ID']))
				{
					$template = static::GetList(array(), array('ID' => $arFields['BASE_TEMPLATE_ID']), false, false, array('ID'))->fetch();
					if(!is_array($template))
						$this->_errors[] = array("text" => Loc::getMessage("TASKS_TEMPLATE_BASE_TEMPLATE_ID_NOT_EXISTS"), "id" => "ERROR_TASKS_BASE_TEMPLATE_ID_NOT_EXISTS");

					// you cannot add a template with both PARENT_ID and BASE_TEMPLATE_ID set. BASE_TEMPLATE_ID has greather priority
					if(isset($arFields['PARENT_ID']))
						$arFields['PARENT_ID'] = '';

					// you cannot add REPLICATE parameters here in case of BASE_TEMPLATE_ID is set
					if(isset($arFields['REPLICATE']))
						$arFields['REPLICATE'] = serialize(array());

					$arFields['REPLICATE_PARAMS'] = serialize(array());
				}
			}
			catch(\Bitrix\Main\ArgumentException $e)
			{
				$this->_errors[] = array("text" => Loc::getMessage("TASKS_TEMPLATE_BAD_BASE_TEMPLATE_ID"), "id" => "ERROR_TASKS_BAD_BASE_TEMPLATE_ID");
			}
		}

		// move 0 to null in PARENT_ID to avoid constraint and query problems
		// todo: move PARENT_ID, GROUP_ID and other "foreign keys" to the unique way of keeping absense of relation: null, 0 or ''
		if(array_key_exists('PARENT_ID', $arFields))
		{
			$parentId = intval($arFields['PARENT_ID']);
			if(!intval($parentId))
			{
				$arFields['PARENT_ID'] = false;
			}
		}

		if (is_set($arFields, "PARENT_ID") && intval($arFields["PARENT_ID"]) > 0)
		{
			$r = CTasks::GetList(array(), array("ID" => $arFields["PARENT_ID"]));
			if (!$r->Fetch())
			{
				$this->_errors[] = array("text" => GetMessage("TASKS_BAD_PARENT_ID"), "id" => "ERROR_TASKS_BAD_PARENT_ID");
			}
		}

		if (
			isset($arFields['FILES'])
			&& isset($arParams['CHECK_RIGHTS_ON_FILES'])
			&& (
				($arParams['CHECK_RIGHTS_ON_FILES'] === true)
				|| ($arParams['CHECK_RIGHTS_ON_FILES'] === 'Y')
			)
		)
		{
			CTaskAssert::assert(
				isset($arParams['USER_ID'])
				&& CTaskAssert::isLaxIntegers($arParams['USER_ID'])
				&& ($arParams['USER_ID'] > 0)
			);

			// Are there any files?
			if ($arFields['FILES'] !== false)
			{
				// There is must be serialized array
				$arFilesIds = unserialize($arFields['FILES'], ['allowed_classes' => false]);

				if (is_array($arFilesIds))
				{
					$ar = CTaskFiles::checkFilesAccessibilityByUser($arFilesIds, (int) $arParams['USER_ID']);

					// If we have one file, that is not accessible, than emit error
					foreach ($arFilesIds as $fileId)
					{
						if (
							( ! isset($ar['f' . $fileId]) )
							|| ($ar['f' . $fileId] === false)
						)
						{
							$this->_errors[] = array('text' => GetMessage('TASKS_BAD_FILE_ID_EX'), 'id' => 'ERROR_TASKS_BAD_FILE_ID_EX');
						}
					}
				}
				else
				{
					$this->_errors[] = array('text' => GetMessage('TASKS_BAD_FILE_ID_EX'), 'id' => 'ERROR_TASKS_BAD_FILE_ID_EX');
				}
			}
		}

		// REPLICATE_PARAMS comes serialized 0_o
		if((string) $arFields['REPLICATE_PARAMS'] != '')
		{
			$params = \Bitrix\Tasks\Util\Type::unSerializeArray($arFields['REPLICATE_PARAMS']);

			$arFields['REPLICATE_PARAMS'] = serialize(self::parseReplicationParams($params));
		}

		// ensure RESPONSIBLES filled anyway
		if(!$ID)
		{
			// set RESPONSIBLES from RESPONSIBLE_ID if not set on add()
			if((string) $arFields['RESPONSIBLES'] == '')
			{
				$arFields['RESPONSIBLES'] = serialize(array($arFields['RESPONSIBLE_ID']));
			}
		}
		else
		{
			if((string) $arFields['RESPONSIBLES'] == '' && (string) $this->currentData['RESPONSIBLES'] == '')
			{
				$arFields['RESPONSIBLES'] = serialize(array($arFields['RESPONSIBLE_ID']));
			}
		}

		if (!empty($this->_errors))
		{
			$e = new CAdminException($this->_errors);
			$APPLICATION->ThrowException($e);
			return false;
		}

		//Defaults
		if (is_set($arFields, "PRIORITY") && !in_array($arFields["PRIORITY"], Array(0, 1, 2)))
			$arFields["PRIORITY"] = 1;

		return true;
	}

	private static function removeAgents($templateId)
	{
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		CAgent::RemoveAgent('CTasks::RepeatTaskByTemplateId('.$templateId.');', 'tasks');

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		CAgent::RemoveAgent('CTasks::RepeatTaskByTemplateId('.$templateId.', 0);', 'tasks');

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		CAgent::RemoveAgent('CTasks::RepeatTaskByTemplateId('.$templateId.', 1);', 'tasks');
	}

	function Add($arFields, $arParams = array())
	{
		global $DB, $USER_FIELD_MANAGER;

		$bCheckFilesPermissions = false;
		if (
			isset($arParams['CHECK_RIGHTS_ON_FILES'])
			&& (
				($arParams['CHECK_RIGHTS_ON_FILES'] === true)
				|| ($arParams['CHECK_RIGHTS_ON_FILES'] === 'Y')
			)
		)
		{
			CTaskAssert::assert(
				isset($arParams['USER_ID'])
				&& CTaskAssert::isLaxIntegers($arParams['USER_ID'])
				&& ($arParams['USER_ID'] > 0)
			);

			$bCheckFilesPermissions = true;
		}

		$arParamsForCheckFields = array(
			'CHECK_RIGHTS_ON_FILES' => $bCheckFilesPermissions
		);

		if (isset($arParams['USER_ID']))
			$arParamsForCheckFields['USER_ID'] = $arParams['USER_ID'];

		// Use of BB code by default, HTML is deprecated
		if (!isset($arFields['DESCRIPTION_IN_BBCODE']))
		{
			$arFields['DESCRIPTION_IN_BBCODE'] = 'Y';
		}

		if ($this->CheckFields($arFields, false, $arParamsForCheckFields))
		{
			if ($USER_FIELD_MANAGER->CheckFields("TASKS_TASK_TEMPLATE", 0, $arFields, $arParamsForCheckFields['USER_ID']))
			{
				$arBinds = Array(
					"DESCRIPTION",
					"REPLICATE_PARAMS",
					"ACCOMPLICES",
					"AUDITORS",
					"FILES",
					"TAGS",
					"DEPENDS_ON",
					"RESPONSIBLES"
				);

				// fix for absent SITE_ID
				if((string) $arFields['SITE_ID'] == '' || $arFields['SITE_ID'] == static::CURRENT_SITE_ID)
				{
					$arFields['SITE_ID'] = SITE_ID;
				}

				$ID = $DB->Add("b_tasks_template", $arFields, $arBinds, "tasks");
				if (isset($arFields['FILES']))
					CTaskFiles::removeTemporaryStatusForFiles(unserialize($arFields['FILES'], ['allowed_classes' => false]), $arParams['USER_ID']);

				$USER_FIELD_MANAGER->Update("TASKS_TASK_TEMPLATE", $ID, $arFields, $arParamsForCheckFields['USER_ID']);

				// periodic tasks
				// todo: use \Bitrix\Tasks\Util\Replicator\Task\FromTemplate::reInstallAgent() here
				if ($arFields["REPLICATE"] == "Y")
				{
					$name = 'CTasks::RepeatTaskByTemplateId('.$ID.');';

					// First, remove all agents for this template
					self::removeAgents($ID);

					// Set up new agent
					if ($arFields['REPLICATE'] === 'Y')
					{
						$nextTime = CTasks::getNextTime(unserialize($arFields['REPLICATE_PARAMS'], ['allowed_classes' => false]), $ID); // localtime
						if ($nextTime)
						{
							/** @noinspection PhpDynamicAsStaticMethodCallInspection */
							CAgent::AddAgent(
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
				}

				// template tree
				if (intval($arFields['BASE_TEMPLATE_ID']))
				{
					try
					{
						Template\DependencyTable::createLink($ID, intval($arFields['BASE_TEMPLATE_ID']));
					}
					catch(\Bitrix\Tasks\DB\Tree\LinkExistsException $e)
					{
					}
				}

				// need to create rights...
				\Bitrix\Tasks\Item\Access\Task\Template::grantAccessLevel($ID, 'U'.$arFields['CREATED_BY'], 'full', array(
					'CHECK_RIGHTS' => false,
				));

				return $ID;
			}
			else
			{
				$e = $GLOBALS['APPLICATION']->GetException();
				foreach($e->messages as $msg)
				{
					$this->_errors[] = $msg;
				}
			}
		}

		return false;
	}

	function Update($ID, $arFields, $arParams = array())
	{
		global $DB, $USER_FIELD_MANAGER;

		$ID = intval($ID);
		if ($ID < 1)
			return false;

		$bCheckFilesPermissions = false;
		if (
			isset($arParams['CHECK_RIGHTS_ON_FILES'])
			&& (
				($arParams['CHECK_RIGHTS_ON_FILES'] === true)
				|| ($arParams['CHECK_RIGHTS_ON_FILES'] === 'Y')
			)
		)
		{
			CTaskAssert::assert(
				isset($arParams['USER_ID'])
				&& CTaskAssert::isLaxIntegers($arParams['USER_ID'])
				&& ($arParams['USER_ID'] > 0)
			);

			$bCheckFilesPermissions = true;
		}

		$arParamsForCheckFields = array(
			'CHECK_RIGHTS_ON_FILES' => $bCheckFilesPermissions
		);

		if (isset($arParams['USER_ID']))
        {
            $userID = $arParams['USER_ID'];
        }
        else
        {
            $userID = \Bitrix\Tasks\Util\User::getId();
	        if(!$userID)
	        {
		        $userID = \Bitrix\Tasks\Util\User::getAdminId(); // compatibility
	        }
        }

        $arParamsForCheckFields['USER_ID'] = $userID;

        // We need understand, does REPLICATE_PARAMS changed
		$rsCurData = self::GetByID($ID);
		$arCurData = $rsCurData->Fetch();
		$this->currentData = $arCurData;

		if(intval($arCurData['BASE_TEMPLATE_ID']) > 0) // for sub-template ...
		{
			unset($arFields['REPLICATE']); // ... you cannot set replicate params
			unset($arFields['PARENT_ID']); // ... and base task
			$isReplicateParamsChanged = false;
		}
		else
		{
			$isReplicateParamsChanged =
				(
					isset($arFields['REPLICATE'])
					&&
					($arCurData['REPLICATE'] !== $arFields['REPLICATE'])
				)
				||
				(
					isset($arFields['REPLICATE_PARAMS'])
					&&
					($arCurData['REPLICATE_PARAMS'] !== $arFields['REPLICATE_PARAMS'])
				);
		}

		if ($this->CheckFields($arFields, $ID, $arParamsForCheckFields))
		{
			if ($USER_FIELD_MANAGER->CheckFields("TASKS_TASK_TEMPLATE", $ID, $arFields))
			{
				unset($arFields['ID']);

				$arBinds = Array(
					'DESCRIPTION'      => $arFields['DESCRIPTION'],
					'REPLICATE_PARAMS' => $arFields['REPLICATE_PARAMS'],
					'ACCOMPLICES'      => $arFields['ACCOMPLICES'],
					'AUDITORS'         => $arFields['AUDITORS'],
					'FILES'            => $arFields['FILES'],
					'TAGS'             => $arFields['TAGS'],
					'DEPENDS_ON'       => $arFields['DEPENDS_ON']
				);

				$strUpdate = $DB->PrepareUpdate('b_tasks_template', $arFields, 'tasks');
				if((string) $strUpdate !== '')
				{
					$strSql = "UPDATE b_tasks_template SET " . $strUpdate . " WHERE ID=" . $ID;
					$DB->QueryBind($strSql, $arBinds, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				}

				if (isset($arFields['FILES']))
					CTaskFiles::removeTemporaryStatusForFiles(unserialize($arFields['FILES'], ['allowed_classes' => false]), $arParams['USER_ID']);

				$USER_FIELD_MANAGER->Update("TASKS_TASK_TEMPLATE", $ID, $arFields, $userID);

				// update template tree, if required
				if (isset($arFields['BASE_TEMPLATE_ID']))
				{
					try
					{
						Template\DependencyTable::link($ID, intval($arFields['BASE_TEMPLATE_ID']), array('CREATE_PARENT_NODE_ON_NOTFOUND' => true));
					}
					catch(\Bitrix\Tasks\DB\Tree\LinkExistsException $e)
					{
					}
				}

				$skipAgentProcessing = (isset($arParams['SKIP_AGENT_PROCESSING'])? $arParams['SKIP_AGENT_PROCESSING'] : false);

				if ($isReplicateParamsChanged && !$skipAgentProcessing)
				{
					$name = 'CTasks::RepeatTaskByTemplateId('.$ID.');';

					// First, remove all agents for this template
					/** @noinspection PhpDynamicAsStaticMethodCallInspection */
					self::removeAgents($ID);

					// Set up new agent
					if ($arFields['REPLICATE'] === 'Y')
					{
						$nextTime = CTasks::getNextTime(unserialize($arFields['REPLICATE_PARAMS'], ['allowed_classes' => false]), $ID);
						if ($nextTime)
						{
							/** @noinspection PhpDynamicAsStaticMethodCallInspection */
							$ares = CAgent::AddAgent(
								$name,
								'tasks',
								'N', 		// is periodic?
								86400, 		// interval
								$nextTime, 	// datecheck
								'Y', 		// is active?
								$nextTime	// next_exec
							);
						}
					}
				}

				return true;
			}
			else
			{
				$e = $GLOBALS['APPLICATION']->GetException();
				foreach($e->messages as $msg)
				{
					$this->_errors[] = $msg;
				}
			}
		}

		return false;
	}

	public static function Delete($id, array $params = [])
	{
		$id = (int)$id;

		if ($id < 1)
		{
			return false;
		}

		try
		{
			$connection = Application::getConnection();
			$template = $connection->query('SELECT * FROM b_tasks_template WHERE ID = ' . $id)->fetch();

			if (!$template)
			{
				return false;
			}

			// remove from recycle bin
			$unsafeDeleteOnly = $params['UNSAFE_DELETE_ONLY'];
			if (isset($unsafeDeleteOnly) && ($unsafeDeleteOnly !== false && $unsafeDeleteOnly !== 'N'))
			{
				static::doUnsafeDelete($template, $params);
				return true;
			}

			$safeDelete = false;
			if (Loader::includeModule('recyclebin'))
			{
				$result = Integration\Recyclebin\Template::OnBeforeDelete($id, $template);
				if (!$result->isSuccess())
				{
					return false;
				}

				$safeDelete = true;
			}

			foreach (GetModuleEvents('tasks', 'OnBeforeTaskTemplateDelete', true) as $arEvent)
			{
				if (ExecuteModuleEventEx($arEvent, array($id, $template)) === false)
				{
					return false;
				}
			}

			static::doSafeDelete($id);

			if (!$safeDelete)
			{
				static::doUnsafeDelete($template, $params);
			}

			foreach (GetModuleEvents('tasks', 'OnTaskTemplateDelete', true) as $arEvent)
			{
				if (ExecuteModuleEventEx($arEvent, array($id, $template)) === false)
				{
					return false;
				}
			}
		}
		catch (\Exception $e)
		{
			return false;
		}

		return true;
	}

	// may be in a future.... may be...
	public static function DeleteBatch(array $ids, array $params = [])
	{
		$result = [];
		$ids = array_filter(array_unique(array_map('intval', $ids)));

		if (empty($ids))
		{
			return false;
		}

		foreach($ids as $id)
		{
			$result[$id] = self::Delete($id, $params);
		}

		return $result;
	}

	/**
	 * Set zombie field to 'Y' and deletes first part of template data.
	 * This data shouldn't exists while the template is in recycle bin.
	 *
	 * @param $templateId
	 * @throws Exception
	 * @throws TargetNodeNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Tasks\Internals\DataBase\Tree\Exception
	 * @throws \Bitrix\Tasks\Internals\DataBase\Tree\LinkExistsException
	 * @throws \Bitrix\Tasks\Internals\DataBase\Tree\ParentNodeNotFoundException
	 */
	private static function doSafeDelete($templateId)
	{
		// set ZOMBIE = 'Y'
		$connection = Application::getConnection();
		$connection->queryExecute('UPDATE b_tasks_template SET ZOMBIE = \'Y\' WHERE ID = ' . $templateId);

		// delete replication agents
		self::removeAgents($templateId);

		$parent = DependenceTable::getParentId($templateId)->fetch();
		$parentId = $parent['PARENT_TEMPLATE_ID'];
		$subTree = DependenceTable::getSubTree($templateId, [], ['INCLUDE_SELF' => false])->fetchAll();

		// delete link to parent
		DependenceTable::unlink($templateId);

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
			'=TEMPLATE_ID' => $templateId,
			'=PARENT_TEMPLATE_ID' => $templateId
		];

		$item = DependenceTable::getList(['select' => $select, 'filter' => $filter])->fetch();
		DependenceTable::delete($item);
	}

	/**
	 * Deletes other template data.
	 * Occurs when recycle bin module is not included and we have to delete the entire template or
	 * when we delete template from recycle bin.
	 *
	 * @param $template
	 * @param $params
	 * @throws NotImplementedException
	 * @throws Exception
	 */
	private static function doUnsafeDelete($template, $params)
	{
		global $USER_FIELD_MANAGER;

		$id = $template['ID'];

		// delete syslog records
		SystemLog::deleteByEntity($id, 1);

		// delete files
		if ($template["FILES"])
		{
			$files = unserialize($template["FILES"], ['allowed_classes' => false]);
			if (is_array($files))
			{
				$filesToDelete = array();
				foreach ($files as $file)
				{
					$rsFile = CTaskFiles::GetList(array(), array("FILE_ID" => $file));
					if (!$arFile = $rsFile->Fetch())
					{
						$filesToDelete[] = $file;
					}
				}
				foreach ($filesToDelete as $file)
				{
					CFile::Delete($file);
				}
			}
		}

		// delete checklist
		TemplateCheckListFacade::deleteByEntityIdOnLowLevel($id);

		// delete access rights
		Access\Task\Template::revokeAll($id, array('CHECK_RIGHTS' => false));

		// delete sub templates
		if (isset($params['DELETE_SUB_TEMPLATES']) && $params['DELETE_SUB_TEMPLATES'] !== false)
		{
			$subTemplatesBdResult = DependenceTable::getSubTree($id, ['select' => ['ID' => 'TEMPLATE_ID']], ['INCLUDE_SELF' => false]);
			while ($subTemplateItem = $subTemplatesBdResult->fetch())
			{
				static::Delete($subTemplateItem['ID'], $params);
			}

			try
			{
				DependenceTable::deleteSubtree($id);
			}
			catch (TargetNodeNotFoundException $e)
			{
				// had no children, actually don't care
			}
		}

		// delete user fields
		$USER_FIELD_MANAGER->Delete("TASKS_TASK_TEMPLATE", $id);

		TemplateTable::delete($id);
	}

	/**
	 * @param $arOrder
	 * @param $arFilter
	 * @param array $arNavParams
	 * @param array $arParams
	 * @param array $arSelect
	 * @return bool|CDBResult
	 *
	 * @global $DB CDatabase
	 */
	public static function GetList($arOrder, $arFilter = array(), $arNavParams = array(), $arParams = array(), $arSelect = array())
	{
		global $DB, $USER_FIELD_MANAGER;

		$arOrder = is_array($arOrder) ? $arOrder : [];
		$arFilter = is_array($arFilter) ? $arFilter : [];
		$arNavParams = is_array($arNavParams) ? $arNavParams : [];
		$arParams = is_array($arParams) ? $arParams : [];
		$arSelect = is_array($arSelect) ? $arSelect : [];

		$provider = new \Bitrix\Tasks\Provider\TemplateProvider($DB, $USER_FIELD_MANAGER);
		return $provider->getList($arOrder, $arFilter, $arSelect, $arParams, $arNavParams);
	}

	public static function GetCount($includeSubTemplates = false, array $params = [])
	{
		global $DB, $USER_FIELD_MANAGER;

		if (!array_key_exists('USER_ID', $params))
		{
			$params['USER_ID'] = (int) \Bitrix\Tasks\Util\User::getId();
		}

		$provider = new \Bitrix\Tasks\Provider\TemplateProvider($DB, $USER_FIELD_MANAGER);
		return $provider->getCount($includeSubTemplates, $params);
	}

	function GetFilter($arFilter, $arParams)
	{
		if (!is_array($arFilter))
			$arFilter = Array();

		$arSqlSearch = Array();

		foreach ($arFilter as $key => $val)
		{
			$res = CTasks::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = mb_strtoupper($key);

			switch ($key)
			{
				case "CREATED_BY":
				case "TASK_ID":
				case "GROUP_ID":
				case "TPARAM_TYPE":
				case "ID":
					$arSqlSearch[] = CTasks::FilterCreate("TT.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "RESPONSIBLE":
					$arSqlSearch[] = CTasks::FilterCreate("TT.RESPONSIBLE_ID", $val, "number", $bFullJoin, $cOperationType);
					break;

				case "TAGS":
					$val = '%i:%;s:%:"'.$val.'";%';
					$arSqlSearch[] = CTasks::FilterCreate("TT.".$key, $val, "string", $bFullJoin, $cOperationType);
					break;

				case "TITLE":
				case "ZOMBIE":
				case "XML_ID":
					$arSqlSearch[] = CTasks::FilterCreate("TT.".$key, $val, "string", $bFullJoin, $cOperationType);
					break;

				case "REPLICATE":
				case "PRIORITY":
					$arSqlSearch[] = CTasks::FilterCreate("TT.".$key, $val, "string_equal", $bFullJoin, $cOperationType);
					break;

				case 'SEARCH_INDEX':
					$fieldsToLike = array(
						"TT.TITLE",
						"TT.DESCRIPTION",
						"CONCAT_WS(' ', CU.NAME, CU.LAST_NAME, CU.SECOND_NAME, CU.LOGIN, RU.NAME, RU.LAST_NAME, RU.SECOND_NAME, RU.LOGIN)"
					);

					$filter = '(';
					$filter .= CTasks::FilterCreate("TT.ID", (int)$val, "number", $bFullJoin, $cOperationType);

					foreach ($fieldsToLike as $field)
					{
						$filter .= " OR ".CTasks::FilterCreate($field, $val, "string", $bFullJoin, "S");
					}
					$filter .= ')';

					$arSqlSearch[] = $filter;

					break;

				/*
				case "TEMPLATE_CHILDREN_COUNT":
					$arSqlSearch[] = CTasks::FilterCreate($key, $val, "number", $bFullJoin, $cOperationType);
					break;
				*/

				case "BASE_TEMPLATE_ID":

					$parentColumnName = 	Template\DependencyTable::getPARENTIDColumnName();
					$columnName = 			Template\DependencyTable::getIDColumnName();
					$cOperationType = 		'I'; // force to "identical" for this field, in any case

					$val = (string) $val;
					if($val === '' || $val === '0')
						$val = false;

					//$includeSubtree = 		$arParams['INCLUDE_TEMPLATE_SUBTREE'] === true || $arParams['INCLUDE_TEMPLATE_SUBTREE'] === 'Y';
					$excludeSubtree = 	$arParams['EXCLUDE_TEMPLATE_SUBTREE'] === true || $arParams['EXCLUDE_TEMPLATE_SUBTREE'] === 'Y';

					if($excludeSubtree)
					{
						$arSqlSearch[] = "TT.ID NOT IN (SELECT ".$columnName." FROM ".Template\DependencyTable::getTableName()." WHERE ".$parentColumnName." = '".intval($val)."')";
					}
					else
					{
						$arSqlSearch[] = '('.($val ? "TD.".$parentColumnName." = '".intval($val)."'" : "TD.".$parentColumnName." = '0' OR TD.".$parentColumnName." IS NULL").')';
						//$arSqlSearch[] = CTasks::FilterCreate("TD.".Template\DependencyTable::getPARENTIDColumnName(), $val, "number", $bFullJoin, $cOperationType);
					}

					break;
			}
		}

		return $arSqlSearch;
	}

	function GetByID($ID, $arParams = array())
	{
		if(!intval($ID))
		{
			$dbResult = new CDBResult();
			$dbResult->InitFromArray(array());
			return $dbResult;
		}

		$select = array_key_exists('select', $arParams) ? $arParams['select'] : ['*', 'UF_*'];
		$order = array_key_exists('order', $arParams) ? $arParams['order'] : [];

		return CTaskTemplates::GetList($order, ["ID" => $ID], [], $arParams, $select);
	}

	/**
	 * @deprecated
	 */
	public static function parseReplicationParams(array $params = array())
	{
		// todo: use \Bitrix\Tasks\Item\Task\Template\Field\ReplicateParams::createValueStructure() here

		$allowed = array(
			"PERIOD" => true,
			"EVERY_DAY" => true,
			"WORKDAY_ONLY" => true,
			"EVERY_WEEK" => true,
			"WEEK_DAYS" => true,
			"MONTHLY_TYPE" => true,
			"MONTHLY_DAY_NUM" => true,
			"MONTHLY_MONTH_NUM_1" => true,
			"MONTHLY_WEEK_DAY_NUM" => true,
			"MONTHLY_WEEK_DAY" => true,
			"MONTHLY_MONTH_NUM_2" => true,
			"YEARLY_TYPE" => true,
			"YEARLY_DAY_NUM" => true,
			"YEARLY_MONTH_1" => true,
			"YEARLY_WEEK_DAY_NUM" => true,
			"YEARLY_WEEK_DAY" => true,
			"YEARLY_MONTH_2" => true,
			"START_DATE" => true,
			"END_DATE" => true,
			"TIME" => true,
			"TIMEZONE_OFFSET" => true,
			"DAILY_MONTH_INTERVAL" => true,
			"REPEAT_TILL" => true,
			"TIMES" => true,
			"NEXT_EXECUTION_TIME" => true
		);

		foreach($params as $fld => $value)
		{
			if(!$allowed[$fld])
			{
				unset($params[$fld]);
			}
		}

        if(!intval($params['EVERY_DAY']))
        {
            $params['EVERY_DAY'] = 1;
        }
        if(!intval($params['EVERY_WEEK']))
        {
            $params['EVERY_WEEK'] = 1;
        }
        if(!intval($params['MONTHLY_DAY_NUM']))
        {
            $params['MONTHLY_DAY_NUM'] = 1;
        }
        if(!intval($params['MONTHLY_MONTH_NUM_1']))
        {
            $params['MONTHLY_MONTH_NUM_1'] = 1;
        }
        if(!intval($params['MONTHLY_MONTH_NUM_2']))
        {
            $params['MONTHLY_MONTH_NUM_2'] = 1;
        }
        if(!intval($params['YEARLY_DAY_NUM']))
        {
            $params['YEARLY_DAY_NUM'] = 1;
        }

        $params['MONTHLY_TYPE'] = static::parseTypeSelector($params['MONTHLY_TYPE']);
        $params['YEARLY_TYPE'] = static::parseTypeSelector($params['YEARLY_TYPE']);

        if((string) $params['PERIOD'] == '')
        {
            $params['PERIOD'] = 'daily';
        }
        if(!is_array($params['WEEK_DAYS']))
        {
            $params['WEEK_DAYS'] = array();
        }

		$time = 18000; // 3600 * 5 (five hours)
		if(trim($params['TIME']) != '')
		{
			$time = \Bitrix\Tasks\UI::parseTimeAmount($params['TIME'], 'HH:MI');
		}
		$params['TIME'] = \Bitrix\Tasks\UI::formatTimeAmount($time, 'HH:MI');

		if(array_key_exists('TIMEZONE_OFFSET', $params))
		{
			$params['TIMEZONE_OFFSET'] = intval($params['TIMEZONE_OFFSET']);
		}

        $params['WORKDAY_ONLY'] = $params['WORKDAY_ONLY'] == 'Y' ? 'Y' : 'N';

		// for old templates
		if((string) $params['END_DATE'] != '' && !array_key_exists('REPEAT_TILL', $params))
		{
			$params['REPEAT_TILL'] = 'date';
		}

		return $params;
	}

    private static function parseTypeSelector($type)
    {
        $type = intval($type);
        if($type < 1 || $type > 2)
        {
            $type = 1;
        }

        return $type;
    }
}