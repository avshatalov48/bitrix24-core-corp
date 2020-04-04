<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 *
 * @global $USER_FIELD_MANAGER CUserTypeManager
 * @global $APPLICATION CMain
 *
 * @deprecated
 */
global $USER_FIELD_MANAGER;

use Bitrix\Main\Application;
use Bitrix\Main\DB\MssqlConnection;
use Bitrix\Main\DB\MysqlCommonConnection;
use Bitrix\Main\DB\OracleConnection;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\CheckList\Internals\CheckList;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Helper\Task\Dependence;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\Task\FavoriteTable;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;
use Bitrix\Tasks\Internals\Task\SortingTable;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Kanban\TaskStageTable;
use Bitrix\Tasks\Util\Calendar;
use Bitrix\Tasks\Util\Replicator;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Util\UserField;
use \Bitrix\Main\Data\Cache;

class CTasks
{
	//Task statuses: 1 - New, 2 - Pending, 3 - In Progress, 4 - Supposedly completed, 5 - Completed, 6 - Deferred, 7 - Declined
	// todo: using statuses in the way "-2, -1" is a bad idea. its better to have separate (probably runtime) fields called "viewed" and "expired"
	// todo: and then, if you want to know if the task is "virgin new", just apply filter array('=VIEWED' => false, '=STATUS' => 2/*or 1*/)
	const METASTATE_VIRGIN_NEW = -2; // unseen
	const METASTATE_EXPIRED = -1;
	const METASTATE_EXPIRED_SOON = -3;
	const STATE_NEW = 1;
	const STATE_PENDING = 2;    // Pending === Accepted
	const STATE_IN_PROGRESS = 3;
	const STATE_SUPPOSEDLY_COMPLETED = 4;
	const STATE_COMPLETED = 5;
	const STATE_DEFERRED = 6;
	const STATE_DECLINED = 7;

	const PRIORITY_LOW = 0;
	const PRIORITY_AVERAGE = 1;
	const PRIORITY_HIGH = 2;

	const MARK_POSITIVE = 'P';
	const MARK_NEGATIVE = 'N';

	const TIME_UNIT_TYPE_SECOND = 'secs';
	const TIME_UNIT_TYPE_MINUTE = 'mins';
	const TIME_UNIT_TYPE_HOUR = 'hours';
	const TIME_UNIT_TYPE_DAY = 'days';
	const TIME_UNIT_TYPE_WEEK = 'weeks';
	const TIME_UNIT_TYPE_MONTH = 'monts'; // 5 chars max :)
	const TIME_UNIT_TYPE_YEAR = 'years';

	const PARAMETER_PROJECT_PLAN_FROM_SUBTASKS = 0x01;
	const PARAMETER_COMPLETE_TASK_FROM_SUBTASKS = 0x02;

	const MAX_INT = 2147483647;

	const FILTER_LIMIT_CACHE_KEY = 'FILTER_LIMIT_CACHE_KEY';
	const CACHE_TASKS_COUNT_DIR_NAME = '/bx_tasks_count';

	private $_errors = array();
	private $lastOperationResultData = array();
	private $previousData = array();

	private static $cacheIds = array();
	private static $cacheClearEnabled = true;

	function GetErrors()
	{
		return $this->_errors;
	}

	public function getLastOperationResultData()
	{
		return $this->lastOperationResultData;
	}

	public function getPreviousData()
	{
		return $this->previousData;
	}

	function CheckFields(&$arFields, $ID = false, $effectiveUserId = null)
	{
		global $APPLICATION;

		if ($effectiveUserId === null)
		{
			$effectiveUserId = User::getId();
			if (!$effectiveUserId)
			{
				$effectiveUserId = User::getAdminId();
			}
		}

		if ((is_set($arFields, "TITLE") || $ID === false))
		{
			$arFields["TITLE"] = trim((string)$arFields["TITLE"]);

			if (strlen($arFields["TITLE"]) <= 0)
			{
				$this->_errors[] = array("text" => GetMessage("TASKS_BAD_TITLE"), "id" => "ERROR_BAD_TASKS_TITLE");
			}
			elseif (strlen($arFields['TITLE']) > 250)
			{
				$arFields['TITLE'] = substr($arFields['TITLE'], 0, 250);
			}
		}

		if (is_set($arFields, 'STATUS') && $arFields['STATUS'] == 1)
		{
			$arFields['STATUS'] = 2; // status CTasks::STATE_NEW (=1) deprecated
		}

		// you are not allowed to clear up END_DATE_PLAN while the task is linked
		if ($ID && ((isset($arFields['END_DATE_PLAN']) && (string)$arFields['END_DATE_PLAN'] == '')))
		{
			if (ProjectDependenceTable::checkItemLinked($ID))
			{
				$this->_errors[] = array(
					"text" => GetMessage("TASKS_IS_LINKED_END_DATE_PLAN_REMOVE"),
					"id"   => "ERROR_TASKS_IS_LINKED"
				);
			}
		}

		if (array_key_exists('GROUP_ID', $arFields) && (int)$arFields['GROUP_ID'] > 0)
		{
			if (\Bitrix\Main\Loader::IncludeModule('socialnetwork'))
			{
				$group = \CSocNetGroup::getById($arFields['GROUP_ID']);

				if ($group && $group['PROJECT'] == 'Y' && ($group['PROJECT_DATE_START'] || $group['PROJECT_DATE_FINISH']))
				{
					$projectStartDate = DateTime::createFrom($group['PROJECT_DATE_START']);
					$projectFinishDate = DateTime::createFrom($group['PROJECT_DATE_FINISH']);

					if ($projectFinishDate)
					{
						$projectFinishDate->addSecond(86399); // + 23:59:59
					}

					$deadline = null;
					$endDatePlan = null;
					$startDatePlan = null;

					if (isset($arFields['DEADLINE']) && $arFields['DEADLINE'])
					{
						$deadline = DateTime::createFrom($arFields['DEADLINE']);
					}
					if (isset($arFields['END_DATE_PLAN']) && $arFields['END_DATE_PLAN'])
					{
						$endDatePlan = DateTime::createFrom($arFields['END_DATE_PLAN']);
					}
					if (isset($arFields['START_DATE_PLAN']) && $arFields['START_DATE_PLAN'])
					{
						$startDatePlan = DateTime::createFrom($arFields['START_DATE_PLAN']);
					}

					if ($deadline && !$deadline->checkInRange($projectStartDate, $projectFinishDate))
					{
						$this->_errors[] = ["text" => GetMessage("TASKS_DEADLINE_OUT_OF_PROJECT_RANGE"), "id" => "ERROR_TASKS_OUT_OF_PROJECT_DATE"];
					}

					if ($endDatePlan && !$endDatePlan->checkInRange($projectStartDate, $projectFinishDate))
					{
						$this->_errors[] = ["text" => GetMessage("TASKS_PLAN_DATE_END_OUT_OF_PROJECT_RANGE"), "id" => "ERROR_TASKS_OUT_OF_PROJECT_DATE"];
					}

					if ($startDatePlan && !$startDatePlan->checkInRange($projectStartDate, $projectFinishDate))
					{
						$this->_errors[] = ["text" => GetMessage("TASKS_PLAN_DATE_START_OUT_OF_PROJECT_RANGE"), "id" => "ERROR_TASKS_OUT_OF_PROJECT_DATE"];
					}
				}
			}
		}

		if ($ID && (isset($arFields['PARENT_ID']) && intval($arFields['PARENT_ID']) > 0))
		{
			if (ProjectDependenceTable::checkLinkExists($ID, $arFields['PARENT_ID'], array('BIDIRECTIONAL' => true)))
			{
				$this->_errors[] = array(
					"text" => GetMessage("TASKS_IS_LINKED_SET_PARENT"),
					"id"   => "ERROR_TASKS_IS_LINKED"
				);
			}
		}

		// If plan dates were set
		if (isset($arFields['START_DATE_PLAN']) &&
			($arFields['START_DATE_PLAN'] != '') &&
			isset($arFields['END_DATE_PLAN']) &&
			($arFields['END_DATE_PLAN'] != ''))
		{
			$startDate = MakeTimeStamp($arFields['START_DATE_PLAN']);
			$endDate = MakeTimeStamp($arFields['END_DATE_PLAN']);

			// and they were really set
			if ($startDate > 0 && $endDate > 0)
			{
				// and end date is before start date => then emit error
				if ($endDate < $startDate)
				{
					$this->_errors[] = array(
						'text' => GetMessage('TASKS_BAD_PLAN_DATES'),
						'id'   => 'ERROR_BAD_TASKS_PLAN_DATES'
					);
				}

				$duration = $endDate - $startDate;
				if ($duration > self::MAX_INT)
				{
					$this->_errors[] = array(
						'text' => GetMessage('TASKS_BAD_DURATION'),
						'id'   => 'ERROR_TASKS_BAD_DURATION'
					);
				}
			}
		}

		if ($ID === false && !is_set($arFields, "RESPONSIBLE_ID"))
		{
			$this->_errors[] = array(
				"text" => GetMessage("TASKS_BAD_RESPONSIBLE_ID"),
				"id"   => "ERROR_TASKS_BAD_RESPONSIBLE_ID"
			);
		}

		if ($ID === false && !is_set($arFields, "CREATED_BY"))
			$this->_errors[] = array(
				"text" => GetMessage("TASKS_BAD_CREATED_BY"),
				"id"   => "ERROR_TASKS_BAD_CREATED_BY"
			);

		if (is_set($arFields, "CREATED_BY"))
		{
			if (!($arFields['CREATED_BY'] >= 1))
				$this->_errors[] = array(
					"text" => GetMessage("TASKS_BAD_CREATED_BY"),
					"id"   => "ERROR_TASKS_BAD_CREATED_BY"
				);
		}

		if (is_set($arFields, "RESPONSIBLE_ID"))
		{
			$r = CUser::GetByID($arFields["RESPONSIBLE_ID"]);
			if ($arUser = $r->Fetch())
			{
				if ($ID)
				{
					$rsTask = CTasks::GetList(
						array(),
						array("ID" => $ID),
						array("RESPONSIBLE_ID"),
						array('USER_ID' => $effectiveUserId)
					);
					if ($arTask = $rsTask->Fetch())
					{
						$currentResponsible = $arTask["RESPONSIBLE_ID"];
					}
				}

				// new task or responsible changed
				if (!$ID || (isset($currentResponsible) && $currentResponsible != $arFields["RESPONSIBLE_ID"]))
				{
					// check if $createdBy is director for responsible
					$createdBy = $arFields["CREATED_BY"];

					$arSubDeps = CTasks::GetSubordinateDeps($createdBy);

					if (!is_array($arUser["UF_DEPARTMENT"]))
						$bSubordinate = (sizeof(array_intersect($arSubDeps, array($arUser["UF_DEPARTMENT"]))) > 0);
					else
						$bSubordinate = (sizeof(array_intersect($arSubDeps, $arUser["UF_DEPARTMENT"])) > 0);

					if (!$arFields["STATUS"])
					{
						$arFields["STATUS"] = self::STATE_PENDING;
					}
					if (!$bSubordinate)
					{
						$arFields["ADD_IN_REPORT"] = "N";
					}

					$arFields["DECLINE_REASON"] = false;
				}
			}
			else
			{
				$this->_errors[] = array(
					"text" => GetMessage("TASKS_BAD_RESPONSIBLE_ID_EX"),
					"id"   => "ERROR_TASKS_BAD_RESPONSIBLE_ID_EX"
				);
			}
		}

		// move 0 to null in PARENT_ID to avoid constraint and query problems
		// todo: move PARENT_ID, GROUP_ID and other "foreign keys" to the unique way of keeping absense of relation: null, 0 or ''
		if (array_key_exists('PARENT_ID', $arFields))
		{
			$parentId = intval($arFields['PARENT_ID']);
			if (!intval($parentId))
			{
				$arFields['PARENT_ID'] = false;
			}
		}

		if (is_set($arFields, "PARENT_ID") && intval($arFields["PARENT_ID"]) > 0)
		{
			$r = CTasks::GetByID($arFields["PARENT_ID"], true, array('USER_ID' => $effectiveUserId));
			if (!$r->Fetch())
			{
				$this->_errors[] = array(
					"text" => GetMessage("TASKS_BAD_PARENT_ID"),
					"id"   => "ERROR_TASKS_BAD_PARENT_ID"
				);
			}
		}

		if ($ID !== false && intval($arFields["PARENT_ID"]))
		{
			$result = \Bitrix\Tasks\Internals\Helper\Task\Dependence::canAttach($ID, $arFields["PARENT_ID"]);

			if (!$result->isSuccess())
			{
				foreach ($result->getErrors()->getMessages() as $message)
				{
					$this->_errors[] = array("text" => $message, "id" => "ERROR_TASKS_PARENT_SELF");
				}
			}
		}

		if ($ID !== false && is_array($arFields["DEPENDS_ON"]) && in_array($ID, $arFields["DEPENDS_ON"]))
		{
			$this->_errors[] = array(
				"text" => GetMessage("TASKS_DEPENDS_ON_SELF"),
				"id"   => "ERROR_TASKS_DEPENDS_ON_SELF"
			);
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

		// accomplices & auditors
		Type::checkArrayOfUPIntegerKey($arFields, 'ACCOMPLICES');
		Type::checkArrayOfUPIntegerKey($arFields, 'AUDITORS');

		if (!Type::checkEnumKey(
			$arFields,
			'STATUS',
			array(
				CTasks::STATE_NEW,
				CTasks::STATE_PENDING,
				CTasks::STATE_IN_PROGRESS,
				CTasks::STATE_SUPPOSEDLY_COMPLETED,
				CTasks::STATE_COMPLETED,
				CTasks::STATE_DEFERRED,
				CTasks::STATE_DECLINED,
			)
		))
		{
			$this->_errors[] = array(
				"text" => GetMessage("TASKS_INCORRECT_STATUS"),
				"id"   => "ERROR_TASKS_INCORRECT_STATUS"
			);
		}

		Type::checkEnumKey(
			$arFields,
			'PRIORITY',
			array(self::PRIORITY_LOW, self::PRIORITY_AVERAGE, self::PRIORITY_HIGH),
			self::PRIORITY_AVERAGE
		);
		Type::checkEnumKey($arFields, 'MARK', array(self::MARK_NEGATIVE, self::MARK_POSITIVE, ''));

		// flags
		Type::checkYNKey($arFields, 'ALLOW_CHANGE_DEADLINE');
		Type::checkYNKey($arFields, 'TASK_CONTROL');
		Type::checkYNKey($arFields, 'ADD_IN_REPORT');
		Type::checkYNKey($arFields, 'MATCH_WORK_TIME');
		Type::checkYNKey($arFields, 'REPLICATE');

        if (!$ID && array_key_exists('GUID', $arFields) && trim($arFields['GUID'])) // !$ID for check only add function
        {
            global $DB;
            $res = $DB->Query("SELECT COUNT(ID) as cnt FROM b_tasks WHERE GUID='".$DB->ForSql($arFields['GUID'])."'");
            if($res && ($result = $res->Fetch()) && $result['cnt'] > 0)
            {
                $this->_errors[] = array(
                    "text" => GetMessage("ERROR_TASKS_GUID_NON_UNIQUE"),
                    "id"   => "ERROR_TASKS_GUID_NON_UNIQUE"
                );
            }
        }

		if (!empty($this->_errors))
		{
			$e = new CAdminException($this->_errors);
			$APPLICATION->ThrowException($e);

			return false;
		}

		return true;
	}

	/**
	 * This method is deprecated. Use CTaskItem::add() instead.
	 * @deprecated
	 */
	public function Add($arFields, $arParams = array())
	{
		global $DB, $USER_FIELD_MANAGER, $CACHE_MANAGER, $APPLICATION;

		if (isset($arFields['META::EVENT_GUID']))
		{
			$eventGUID = $arFields['META::EVENT_GUID'];
			unset($arFields['META::EVENT_GUID']);
		}
		else
			$eventGUID = sha1(uniqid('AUTOGUID', true));

		if (!array_key_exists('GUID', $arFields))
			$arFields['GUID'] = CTasksTools::genUuid();

		if (!isset($arFields['SITE_ID']))
			$arFields['SITE_ID'] = SITE_ID;

		if (!isset($arParams['CORRECT_DATE_PLAN']))
		{
			$arParams['CORRECT_DATE_PLAN'] = true;
		}

		if (isset($arFields['ALLOW_CHANGE_DEADLINE_COUNT']))
		{
			$availableValues = array_column(\Bitrix\Tasks\UI\Controls\Fields\Deadline::getCountTimesItems(), 'VALUE');
			if(!in_array($arFields['ALLOW_CHANGE_DEADLINE_COUNT'], $availableValues))
			{
				$arFields['ALLOW_CHANGE_DEADLINE_COUNT'] = '*;';
			}
			$arFields['ALLOW_CHANGE_DEADLINE_COUNT'] = $arFields['ALLOW_CHANGE_DEADLINE_COUNT']=='*' ? null: (int)$arFields['ALLOW_CHANGE_DEADLINE_COUNT'];
		}

		if (isset($arFields['ALLOW_CHANGE_DEADLINE_MAXTIME']))
		{
			$availableValues = array_column(\Bitrix\Tasks\UI\Controls\Fields\Deadline::getTimesItems(), 'VALUE');
			if(!in_array($arFields['ALLOW_CHANGE_DEADLINE_MAXTIME'], $availableValues))
			{
				$arFields['ALLOW_CHANGE_DEADLINE_MAXTIME'] = '*;';
			}

			if($arFields['ALLOW_CHANGE_DEADLINE_MAXTIME'] != '*')
			{
				$maxDate = Datetime::createFromTimestamp(strtotime('+'.$arFields['ALLOW_CHANGE_DEADLINE_MAXTIME']));
			}

			$arFields['ALLOW_CHANGE_DEADLINE_MAXTIME_VALUE'] = $arFields['ALLOW_CHANGE_DEADLINE_MAXTIME']=='*' ? null: $arFields['ALLOW_CHANGE_DEADLINE_MAXTIME'];
			$arFields['ALLOW_CHANGE_DEADLINE_MAXTIME'] = $arFields['ALLOW_CHANGE_DEADLINE_MAXTIME']=='*' ? null: $maxDate;
		}

		// force GROUP_ID to 0 if not set (prevent occur as NULL in database)
		$arFields['GROUP_ID'] = intval($arFields['GROUP_ID']);

		$bWasFatalError = false;
		$spawnedByAgent = false;

		$effectiveUserId = null;

		$bCheckRightsOnFiles = false;    // for backward compatibility

		if (is_array($arParams))
		{
			if (isset($arParams['SPAWNED_BY_AGENT']) &&
				(($arParams['SPAWNED_BY_AGENT'] === 'Y') || ($arParams['SPAWNED_BY_AGENT'] === true)))
			{
				$spawnedByAgent = true;
			}

			if (isset($arParams['USER_ID']) && ($arParams['USER_ID'] > 0))
				$effectiveUserId = (int)$arParams['USER_ID'];

			if (isset($arParams['CHECK_RIGHTS_ON_FILES']))
			{
				if (($arParams['CHECK_RIGHTS_ON_FILES'] === 'Y') || ($arParams['CHECK_RIGHTS_ON_FILES'] === true))
				{
					$bCheckRightsOnFiles = true;
				}
				else
					$bCheckRightsOnFiles = false;
			}
		}

		self::processDurationPlanFields($arFields, $arFields['DURATION_TYPE']);

		if ($effectiveUserId === null)
		{
			$effectiveUserId = User::getId();
			if (!$effectiveUserId)
			{
				$effectiveUserId = 1; // nasty, but for compatibility :(
			}
		}

		if ((!isset($arFields['CREATED_BY'])) || (!$arFields['CREATED_BY']))
		{
			$arFields['CREATED_BY'] = $effectiveUserId;
		}

		if ($this->CheckFields($arFields, false, $effectiveUserId))
		{
			// never, never step on this option. hot lava!
			if ($arParams['CLONE_DISK_FILE_ATTACHMENT'] === true || $arParams['CLONE_DISK_FILE_ATTACHMENT'] === 'Y')
			{
				// when you pass existing file attachments to add(), you must copy all the files and make new attachments
				// currently only for one field: UF_TASK_WEBDAV_FILES
				if (array_key_exists('UF_TASK_WEBDAV_FILES', $arFields) && is_array($arFields['UF_TASK_WEBDAV_FILES']))
				{
					$arFields['UF_TASK_WEBDAV_FILES'] = Integration\Disk::cloneFileAttachment($arFields['UF_TASK_WEBDAV_FILES'], $effectiveUserId);
				}
			}

			if ($USER_FIELD_MANAGER->CheckFields("TASKS_TASK", 0, $arFields, $effectiveUserId))
			{
				$nowDateTimeString = \Bitrix\Tasks\UI::formatDateTime(User::getTime());

				if (!isset($arFields["CREATED_DATE"])) // created date was not set manually
				{
					$arFields["CREATED_DATE"] = $nowDateTimeString;
				}

				if (!isset($arFields["CHANGED_BY"]))
				{
					$arFields["STATUS_CHANGED_BY"] = $arFields["CHANGED_BY"] = $arFields["CREATED_BY"];
					$arFields["STATUS_CHANGED_DATE"] = $arFields["CHANGED_DATE"] = $arFields["CREATED_DATE"] = $nowDateTimeString;
				}

				if (isset($arFields['DEADLINE']) &&
					(string)$arFields['DEADLINE'] != '' &&
					isset($arFields['MATCH_WORK_TIME']) &&
					$arFields['MATCH_WORK_TIME'] == 'Y')
				{
					$arFields['DEADLINE'] = static::getDeadlineMatchWorkTime($arFields['DEADLINE']);
				}

				$shiftResult = null;
				if ($arParams['CORRECT_DATE_PLAN'] &&
					((string)$arFields['START_DATE_PLAN'] != '' || (string)$arFields['END_DATE_PLAN'] != ''))
				{
					$scheduler = \Bitrix\Tasks\Processor\Task\Scheduler::getInstance($effectiveUserId);
					$shiftResult = $scheduler->processEntity(
						0,
						$arFields,
						array(
							'MODE' => 'BEFORE_ATTACH',
						)
					);
					if ($shiftResult->isSuccess())
					{
						$shiftData = $shiftResult->getImpactById(0);
						if ($shiftData)
						{
							// will be saved...
							$arFields['START_DATE_PLAN'] = $shiftData['START_DATE_PLAN'];
							$arFields['END_DATE_PLAN'] = $shiftData['END_DATE_PLAN'];
							$arFields['DURATION_PLAN_SECONDS'] = $shiftData['DURATION_PLAN_SECONDS'];
						}
					}
				}
				self::processDurationPlanFields($arFields, $arFields['DURATION_TYPE']);

				$arFields["OUTLOOK_VERSION"] = 1;

				foreach (GetModuleEvents('tasks', 'OnBeforeTaskAdd', true) as $arEvent)
				{
					if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
					{
						$e = $APPLICATION->GetException();

						if ($e)
						{
							if ($e instanceof CAdminException)
							{
								if (is_array($e->messages))
								{
									foreach ($e->messages as $msg)
									{
										$this->_errors[] = $msg;
									}
								}
							}
							else
							{
								$this->_errors[] = array('text' => $e->getString(), 'id' => 'unknown');
							}
						}

						if (empty($this->_errors))
							$this->_errors[] = array(
								"text" => GetMessage("TASKS_UNKNOWN_ADD_ERROR"),
								"id"   => "ERROR_UNKNOWN_ADD_TASK_ERROR"
							);

						return false;
					}
				}

				// Timezone hack http://jabber.bx/view.php?id=105626
				$disabled = !\CTimeZone::enabled();

				if ($disabled)
				{
					\CTimeZone::enable();
				}

				/** @var $DB \CDatabaseMysql */
				$ID = $DB->Add("b_tasks", $arFields, array("DESCRIPTION"), "tasks");

				if ($disabled)
				{
					\CTimeZone::disable();
				}

				$arFields["ACCOMPLICES"] = (array)$arFields["ACCOMPLICES"];
				$arFields["AUDITORS"] = (array)$arFields["AUDITORS"];

				if ($ID)
				{
					$rsTask = CTasks::GetByID($ID, false);
					if ($arTask = $rsTask->Fetch())
					{
						// add to favorite, if needed
						if (intval($arFields['PARENT_ID']) && FavoriteTable::check(
								array('TASK_ID' => $arFields['PARENT_ID'], 'USER_ID' => $effectiveUserId)
							))
						{
							FavoriteTable::add(
								array('TASK_ID' => $ID, 'USER_ID' => $effectiveUserId),
								array('CHECK_EXISTENCE' => false)
							);
						}

						// drop, then re-add
						$res = MemberTable::getList(array('filter' => array('=TASK_ID' => $ID)));
						while ($item = $res->fetch())
						{
							MemberTable::delete($item);
						}

						// add responsible and creator to the member "cache" table
						MemberTable::add(
							array(
								'TASK_ID' => $ID,
								'USER_ID' => $arTask['CREATED_BY'],
								'TYPE'    => 'O',
							)
						);
						MemberTable::add(
							array(
								'TASK_ID' => $ID,
								'USER_ID' => $arTask['RESPONSIBLE_ID'],
								'TYPE'    => 'R',
							)
						);

						CTasks::AddAccomplices($ID, $arFields["ACCOMPLICES"]);
						CTasks::AddAuditors($ID, $arFields["AUDITORS"]);

						CTasks::AddFiles(
							$ID,
							$arFields["FILES"],
							array(
								'USER_ID'               => $effectiveUserId,
								'CHECK_RIGHTS_ON_FILES' => $bCheckRightsOnFiles
							)
						);

						$newTags = static::detectTags($arFields);

						if (!empty($newTags))
						{
							if (!isset($arFields['TAGS']))
							{
								$arFields['TAGS'] = array();
							}
							if (!is_array($arFields['TAGS']))
							{
								$arFields['TAGS'] = array($arFields['TAGS']);
							}
							$arFields['TAGS'] = array_unique(array_merge($arFields['TAGS'], $newTags));
						}

						CTasks::AddTags($ID, $arTask["CREATED_BY"], $arFields["TAGS"], $effectiveUserId);
						CTasks::AddPrevious($ID, $arFields["DEPENDS_ON"]);

						$arFields = CTasks::processUserFields($arFields, $ID, $effectiveUserId);
						$USER_FIELD_MANAGER->Update("TASKS_TASK", $ID, $arFields, $effectiveUserId);

						// backward compatibility with PARENT_ID
						$parentId = intval($arFields["PARENT_ID"]);
						if ($parentId)
						{
							\Bitrix\Tasks\Internals\Helper\Task\Dependence::attachNew($ID, $parentId);
						}

						$arFields["ID"] = $ID;

						CTasks::__updateViewed($ID, $effectiveUserId, $onTaskAdd = true);
						//						CTaskCountersProcessor::onAfterTaskAdd($arFields);
						Counter::onAfterTaskAdd($arFields);

						CTaskComments::onAfterTaskAdd($ID, $arFields);

						$occurAsUserId = CTasksTools::getOccurAsUserId();
						if (!$occurAsUserId)
						{
							$occurAsUserId = ($effectiveUserId ? $effectiveUserId : 1);
						}

						CTaskNotifications::SendAddMessage(
							array_merge($arFields, array('CHANGED_BY' => $occurAsUserId)),
							array('SPAWNED_BY_AGENT' => $spawnedByAgent)
						);

						CTaskSync::AddItem($arFields); // MS Exchange

						// changes log
						$arLogFields = array(
							"TASK_ID"      => $ID,
							"USER_ID"      => $occurAsUserId,
							"CREATED_DATE" => $nowDateTimeString,
							"FIELD"        => "NEW"
						);
						$log = new CTaskLog();
						$log->Add($arLogFields);

						try
						{
							$lastEventName = '';
							foreach (GetModuleEvents('tasks', 'OnTaskAdd', true) as $arEvent)
							{
								$lastEventName = $arEvent['TO_CLASS'].'::'.$arEvent['TO_METHOD'].'()';
								ExecuteModuleEventEx($arEvent, array($ID, &$arFields));
							}
						}
						catch (Exception $e)
						{
							CTaskAssert::logWarning(
								'[0x37eb64ae] exception in module event: '.$lastEventName
							);
							\Bitrix\Tasks\Util::log($e);
						}

						$mergedFields = array_merge($arTask, $arFields);

						CTasks::Index($mergedFields, $arFields["TAGS"]); // search index
						SearchIndex::setTaskSearchIndex($ID, $mergedFields);

						// clear cache
						if ($arFields["GROUP_ID"])
						{
							$CACHE_MANAGER->ClearByTag("tasks_group_".$arFields["GROUP_ID"]);
						}
						$arParticipants = array_unique(
							array_merge(
								array($arFields["CREATED_BY"], $arFields["RESPONSIBLE_ID"]),
								$arFields["ACCOMPLICES"],
								$arFields["AUDITORS"]
							)
						);
						foreach ($arParticipants as $userId)
						{
							$CACHE_MANAGER->ClearByTag("tasks_user_".$userId);
						}

						// Emit pull event
						try
						{
							$arPullRecipients = array();

							foreach ($arParticipants as $userId)
							{
								$arPullRecipients[] = (int)$userId;
							}

							$taskGroupId = 0;    // no group

							if (isset($arFields['GROUP_ID']) && ($arFields['GROUP_ID'] > 0))
							{
								$taskGroupId = (int)$arFields['GROUP_ID'];
							}

							$arPullData = [
								'TASK_ID'    => (int)$ID,
								'AFTER'      => [
									'GROUP_ID' => $taskGroupId
								],
								'TS'         => time(),
								'event_GUID' => $eventGUID
							];

//							self::EmitPullWithTagPrefix(
//								$arPullRecipients,
//								'TASKS_GENERAL_',
//								'task_add',
//								$arPullData
//							);

							self::EmitPullWithTag(
								$arPullRecipients,
								'TASKS_TASK_'.(int)$ID,
								'task_add',
								$arPullData
							);

							\Bitrix\Tasks\Internals\Counter::sendPushCounters($arPullRecipients);
						}
						catch (Exception $e)
						{
							$bWasFatalError = true;
							$this->_errors[] = 'at line '.$e->GetLine().', '.$e->GetMessage();
						}

						// tasks dependence

						if ($shiftResult !== null)
						{
							if ($parentId)
							{
								$childrenCountDbResult = self::getChildrenCount(array(), $parentId);
								$fetchedChildrenCount = $childrenCountDbResult->Fetch();
								$childrenCount = $fetchedChildrenCount['CNT'];

								if ($childrenCount == 1)
								{
									$scheduler = \Bitrix\Tasks\Processor\Task\Scheduler::getInstance($effectiveUserId);
									$shiftResult = $scheduler->processEntity(
										0,
										$arFields,
										array('MODE' => 'BEFORE_ATTACH')
									);
								}
							}

							$shiftResult->save(array('!ID' => 0));
						}

						if ($arFields['GROUP_ID'] && CModule::IncludeModule("socialnetwork"))
						{
							CSocNetGroup::SetLastActivity($arFields['GROUP_ID']);
						}
					}
				}

				if ($bWasFatalError)
					soundex('push&pull: bWasFatalError === true');
				return $ID;
			}
			else
			{
				$e = $APPLICATION->GetException();
				foreach ($e->messages as $msg)
				{
					$this->_errors[] = $msg;
				}
			}
		}

		if (empty($this->_errors))
			$this->_errors[] = array(
				"text" => GetMessage("TASKS_UNKNOWN_ADD_ERROR"),
				"id"   => "ERROR_UNKNOWN_ADD_TASK_ERROR"
			);

		return false;
	}

	private static function processDurationPlanFields(&$arFields, $type)
	{
		$durationPlan = false;
		if (isset($arFields['DURATION_PLAN_SECONDS']))
		{
			$durationPlan = $arFields['DURATION_PLAN_SECONDS'];
		}
		elseif (isset($arFields['DURATION_PLAN']))
		{
			$durationPlan = self::convertDurationToSeconds($arFields['DURATION_PLAN'], $type);
		}

		if ($durationPlan !== false) // smth were done
		{
			$arFields['DURATION_PLAN'] = $durationPlan;
			unset($arFields['DURATION_PLAN_SECONDS']);
		}
	}

	/**
	 * Changes user fields if needed
	 *
	 * @param $fields
	 * @param $taskId
	 * @param $userId
	 *
	 * @return mixed
	 */
	private static function processUserFields($fields, $taskId, $userId)
	{
		global $USER_FIELD_MANAGER;

		$systemUserFields = array('UF_CRM_TASK', 'UF_TASK_WEBDAV_FILES');
		$userFields = $USER_FIELD_MANAGER->GetUserFields('TASKS_TASK', $taskId, false, $userId);

		foreach ($fields as $key => $field)
		{
			if (array_key_exists($key, $userFields) &&
				!array_key_exists($key, $systemUserFields) &&
				$userFields[$key]['USER_TYPE_ID'] == 'boolean')
			{
				$fields[$key] = Type::convertBooleanUserFieldValue($field);
			}
		}

		return $fields;
	}

	/**
	 * This method is deprecated. Use CTaskItem::update() instead.
	 * @deprecated
	 */
	public function Update($ID, $arFields, $arParams = array(
		'CORRECT_DATE_PLAN_DEPENDENT_TASKS' => true,
		'CORRECT_DATE_PLAN'                 => true,
		'THROTTLE_MESSAGES'                 => false
	))
	{
		//$GLOBALS['LS'] = true;

		global $DB, $USER_FIELD_MANAGER, $APPLICATION;

		$updatePins = false;

		if (!isset($arParams['CORRECT_DATE_PLAN']))
		{
			$arParams['CORRECT_DATE_PLAN'] = true;
		}
		if (!isset($arParams['CORRECT_DATE_PLAN_DEPENDENT_TASKS']))
		{
			$arParams['CORRECT_DATE_PLAN_DEPENDENT_TASKS'] = true;
		}
		if (!isset($arParams['THROTTLE_MESSAGES']))
		{
			$arParams['THROTTLE_MESSAGES'] = false;
		}

		$this->lastOperationResultData = array();

		if (isset($arFields['META::EVENT_GUID']))
		{
			$eventGUID = $arFields['META::EVENT_GUID'];
			unset($arFields['META::EVENT_GUID']);
		}
		else
			$eventGUID = sha1(uniqid('AUTOGUID', true));

		$bWasFatalError = false;

		$ID = intval($ID);
		if ($ID < 1)
			return false;

		$userID = null;

		$bCheckRightsOnFiles = false;    // for backward compatibility

		if (!is_array($arParams))
		{
			$arParams = array();
		}

		if (isset($arParams['USER_ID']) && ($arParams['USER_ID'] > 0))
		{
			$userID = (int)$arParams['USER_ID'];
		}

		if (isset($arParams['CHECK_RIGHTS_ON_FILES']))
		{
			if (($arParams['CHECK_RIGHTS_ON_FILES'] === 'Y') || ($arParams['CHECK_RIGHTS_ON_FILES'] === true))
			{
				$bCheckRightsOnFiles = true;
			}
			else
				$bCheckRightsOnFiles = false;
		}

		if (!isset($arParams['CORRECT_DATE_PLAN_DEPENDENT_TASKS']))
		{
			$arParams['CORRECT_DATE_PLAN_DEPENDENT_TASKS'] = true;
		}

		if (!isset($arParams['CORRECT_DATE_PLAN']))
		{
			$arParams['CORRECT_DATE_PLAN'] = true;
		}

		if ($userID === null)
		{
			$userID = User::getId();
			if (!$userID)
			{
				$userID = 1; // nasty, but for compatibility :(
			}
		}

		$rsTask = CTasks::GetByID($ID, false, array('USER_ID' => $userID));
		if ($arTask = $rsTask->Fetch())
		{
			if ($this->CheckFields($arFields, $ID, $userID))
			{
				$ufCheck = true;
				$hasUfs = UserField::checkContainsUFKeys($arFields);
				if ($hasUfs)
				{
					$ufCheck = $USER_FIELD_MANAGER->CheckFields("TASKS_TASK", $ID, $arFields, $userID);
				}

				/*
				//region allow change deadline count
				if(!array_key_exists('ALLOW_CHANGE_DEADLINE_COUNT_VALUE', $arFields)) // if change deadline only
				{
					if (array_key_exists('DEADLINE', $arFields))
					{
						$canChangeDeadline = $arFields['CREATED_BY'] === User::getId() ||
											 User::isAdmin() ||
											 User::isSuper();

						if ($canChangeDeadline)
						{
							if ($arFields['DEADLINE'] == '')
							{
								if (!is_null($arTask['ALLOW_CHANGE_DEADLINE_COUNT']) ||
									!is_null($arTask['ALLOW_CHANGE_DEADLINE_MAXTIME']))
								{
									$this->_errors[] = array(
										"text" => GetMessage("ERROR_TASKS_CHANGE_DEADLINE_NULL"),
										"id"   => "ERROR_TASKS_CHANGE_DEADLINE_NULL"
									);
								}
							}
							else
							{
								if($arTask['ALLOW_CHANGE_DEADLINE_COUNT_VALUE'] != '*')
								{
									if ($arTask['ALLOW_CHANGE_DEADLINE_COUNT'] <= 0)
									{
										$this->_errors[] = array(
											"text" => GetMessage("ERROR_TASKS_CHANGE_DEADLINE_COUNT_OVER"),
											"id"   => "ERROR_TASKS_CHANGE_DEADLINE_COUNT_OVER"
										);
									}
									else
									{
										$arFields['ALLOW_CHANGE_DEADLINE_COUNT'] = $arTask['ALLOW_CHANGE_DEADLINE_COUNT'] - 1;
									}
								}
								else
								{
									$arFields['ALLOW_CHANGE_DEADLINE_COUNT'] = null;
								}



								if($arTask['ALLOW_CHANGE_DEADLINE_MAXTIME_VALUE'] != '*')
								{
									if (!is_null($arTask['ALLOW_CHANGE_DEADLINE_MAXTIME']))
									{
										$maxDatetime = DateTime::createFromTimestamp(
											strtotime($arTask['ALLOW_CHANGE_DEADLINE_MAXTIME'])
										);
										$deadline = DateTime::createFromTimestamp(strtotime($arFields['DEADLINE']));

										if ($deadline->checkGT($maxDatetime))
										{
											$this->_errors[] = array(
												"text" => GetMessage(
													"ERROR_TASKS_CHANGE_DEADLINE_MAXTIME_OVER",
													['#DEADLINE#' => $maxDatetime->toString()]
												),
												"id"   => "ERROR_TASKS_CHANGE_DEADLINE_MAXTIME_OVER"
											);
										}
									}
								}
								else
								{
									$arFields['ALLOW_CHANGE_DEADLINE_MAXTIME'] = null;
								}
							}

							if (!empty($this->_errors))
							{
								$e = new CAdminException($this->_errors);
								$APPLICATION->ThrowException($e);

								return false;
							}
						}

						if ($canChangeDeadline)
						{
							if (isset($arFields['ALLOW_CHANGE_DEADLINE_COUNT']))
							{
								$availableValues = array_column(
									\Bitrix\Tasks\UI\Controls\Fields\Deadline::getCountTimesItems(),
									'VALUE'
								);
								if (!in_array($arFields['ALLOW_CHANGE_DEADLINE_COUNT'], $availableValues))
								{
									$arFields['ALLOW_CHANGE_DEADLINE_COUNT'] = '*';
								}
								$arFields['ALLOW_CHANGE_DEADLINE_COUNT'] = $arFields['ALLOW_CHANGE_DEADLINE_COUNT'] ==
																		   '*' ? null
									: (int)$arFields['ALLOW_CHANGE_DEADLINE_COUNT'];
							}

							if (isset($arFields['ALLOW_CHANGE_DEADLINE_MAXTIME']))
							{
								$availableValues = array_column(
									\Bitrix\Tasks\UI\Controls\Fields\Deadline::getTimesItems(),
									'VALUE'
								);
								if (!in_array($arFields['ALLOW_CHANGE_DEADLINE_MAXTIME'], $availableValues))
								{
									$arFields['ALLOW_CHANGE_DEADLINE_MAXTIME'] = '*';
								}

								if ($arFields['ALLOW_CHANGE_DEADLINE_MAXTIME'] != '*')
								{
									$maxDate = Datetime::createFromTimestamp(
										strtotime('+'.$arFields['ALLOW_CHANGE_DEADLINE_MAXTIME'])
									);
								}

								$arFields['ALLOW_CHANGE_DEADLINE_MAXTIME_VALUE'] = $arFields['ALLOW_CHANGE_DEADLINE_MAXTIME'] ==
																				   '*' ? null
									: $arFields['ALLOW_CHANGE_DEADLINE_MAXTIME'];
								$arFields['ALLOW_CHANGE_DEADLINE_MAXTIME'] = $arFields['ALLOW_CHANGE_DEADLINE_MAXTIME'] ==
																			 '*' ? null : $maxDate;
							}
						}
					}
				}
				else
				{ // if update

					if(array_key_exists('DEADLINE', $arFields) && $arFields['DEADLINE']=='')
					{
						$arFields['ALLOW_CHANGE_DEADLINE_MAXTIME']='';
						$arFields['ALLOW_CHANGE_DEADLINE_MAXTIME_VALUE']='*';
						$arFields['ALLOW_CHANGE_DEADLINE_COUNT']='';
						$arFields['ALLOW_CHANGE_DEADLINE_COUNT_VALUE']='*';
					}
					else
					{
						if($arFields['ALLOW_CHANGE_DEADLINE_COUNT_VALUE'] != '*')
						{
							// check valid value!!! SEC
							$arFields['ALLOW_CHANGE_DEADLINE_COUNT'] = $arFields['ALLOW_CHANGE_DEADLINE_COUNT_VALUE'];
						}
					}
				}
				//endregion
*/

				// detect hashtags in body
				if (isset($arFields['TITLE']) || isset($arFields['DESCRIPTION']))
				{
					$oldTags = static::detectTags($arTask);
					$newTags = static::detectTags($arFields);

					if ($oldTags != $newTags)
					{
						$deleteTags = array_diff($oldTags, $newTags);
						$newNewTags = array_diff($newTags, $oldTags);
						// def vals
						if (!isset($arTask['TAGS']))
						{
							$arTask['TAGS'] = array();
						}
						if (!isset($arFields['TAGS']))
						{
							$arFields['TAGS'] = $arTask['TAGS'];
						}
						if (!is_array($arFields['TAGS']))
						{
							$arFields['TAGS'] = array($arFields['TAGS']);
						}
						// new tags deteced in body
						if (!empty($newNewTags))
						{
							$arFields['TAGS'] = array_merge($arFields['TAGS'], $newNewTags);
						}
						// some tags was removed from body
						if (!empty($deleteTags))
						{
							for ($t = 0, $c = count($arFields['TAGS']); $t < $c; $t++)
							{
								if (in_array($arFields['TAGS'][$t], $deleteTags))
								{
									unset($arFields['TAGS'][$t]);
								}
							}
						}

						$arFields['TAGS'] = array_unique($arFields['TAGS']);
					}
				}

				if ($ufCheck)
				{
					unset($arFields["ID"]);

					$arBinds = array(
						"DESCRIPTION"    => $arFields["DESCRIPTION"],
						"DECLINE_REASON" => $arFields["DECLINE_REASON"]
					);

					$time = \Bitrix\Tasks\UI::formatDateTime(User::getTime());

					$arFields["CHANGED_BY"] = $userID;
					$arFields["CHANGED_DATE"] = $time;

					$occurAsUserId = CTasksTools::getOccurAsUserId();
					if (!$occurAsUserId)
					{
						$occurAsUserId = ($arFields["CHANGED_BY"] ? $arFields["CHANGED_BY"] : 1);
					}

					if (!$arFields["OUTLOOK_VERSION"])
					{
						$arFields["OUTLOOK_VERSION"] = ($arTask["OUTLOOK_VERSION"] ? $arTask["OUTLOOK_VERSION"] : 1) +
													   1;
					}

					// If new status code given AND new status code != current status => than update
					if (isset($arFields["STATUS"]) && (int)$arTask['STATUS'] !== (int)$arFields['STATUS'])
					{
						$arFields["STATUS_CHANGED_BY"] = $userID;
						$arFields["STATUS_CHANGED_DATE"] = $time;

						if ($arFields["STATUS"] == 5 || $arFields["STATUS"] == 4)
						{
							$arFields["CLOSED_BY"] = $userID;
							$arFields["CLOSED_DATE"] = $time;
						}
						else
						{
							$arFields["CLOSED_BY"] = false;
							$arFields["CLOSED_DATE"] = false;

							if ($arFields["STATUS"] == 3 && !$arTask["DATE_START"])
							{
								$arFields["DATE_START"] = $time;
							}
						}
					}

					if (isset($arFields['DEADLINE']) &&
						(string)$arFields['DEADLINE'] != '' &&
						$arTask['MATCH_WORK_TIME'] == 'Y')
					{
						$arFields['DEADLINE'] = static::getDeadlineMatchWorkTime($arFields['DEADLINE']);
					}

					$shiftResult = null;
					if ($arParams['CORRECT_DATE_PLAN'])
					{
						$parentChanged = static::parentChanged($arTask, $arFields, $userID);
						$datesChanged = static::datesChanged($arTask, $arFields);
						$followDatesChanged = static::followDatesSetTrue($arFields);

						if ($parentChanged)
						{
							// task was attached previously, and now it is being unattached or reattached to smth else
							// then we need to recalculate its previous parent...
							$scheduler = \Bitrix\Tasks\Processor\Task\Scheduler::getInstance($userID);
							$shiftResultPrev = $scheduler->processEntity(
								$ID,
								$arTask,
								array(
									'MODE' => 'BEFORE_DETACH',
								)
							);
							if ($shiftResultPrev->isSuccess())
							{
								$shiftResultPrev->save(array('!ID' => $ID));
							}
						}
						else
						{
							if (array_key_exists('PARENT_ID', $arFields))
							{
								unset($arFields['PARENT_ID']);
							}
						}

						// when updating end or start date plan, we need to be sure the time is correct
						if ($parentChanged || $datesChanged || $followDatesChanged)
						{
							$scheduler = \Bitrix\Tasks\Processor\Task\Scheduler::getInstance($userID);
							$shiftResult = $scheduler->processEntity(
								$ID,
								$arFields,
								array(
									'MODE' => $parentChanged ? 'BEFORE_ATTACH' : '',
								)
							);
							if ($shiftResult->isSuccess())
							{
								$shiftData = $shiftResult->getImpactById($ID);
								if ($shiftData)
								{
									// will be saved...
									$arFields['START_DATE_PLAN'] = ((isset($arFields['START_DATE_PLAN']) &&
																	 $shiftData['START_DATE_PLAN'] == null) ? false
										: $shiftData['START_DATE_PLAN']);
									$arFields['END_DATE_PLAN'] = ((isset($arFields['END_DATE_PLAN']) &&
																   $shiftData['END_DATE_PLAN'] == null) ? false
										: $shiftData['END_DATE_PLAN']);
									$arFields['DURATION_PLAN_SECONDS'] = $shiftData['DURATION_PLAN_SECONDS'];

									$this->lastOperationResultData['SHIFT_RESULT'][$ID] = $shiftData;
								}
							}
						}
					}

					// END_DATE_PLAN will be dropped
					if (isset($arFields['END_DATE_PLAN']) && (string)$arFields['END_DATE_PLAN'] == '')
					{
						// duration is no longer adequate
						$arFields['DURATION_PLAN'] = 0;
					}

					self::processDurationPlanFields(
						$arFields,
						(string)$arFields['DURATION_TYPE'] != ''
							? $arFields['DURATION_TYPE'] : $arTask['DURATION_TYPE']
					);

					$arTaskCopy = $arTask;    // this will allow transfer data by pointer for speed-up
					foreach (GetModuleEvents('tasks', 'OnBeforeTaskUpdate', true) as $arEvent)
					{
						if (ExecuteModuleEventEx($arEvent, array($ID, &$arFields, &$arTaskCopy)) === false)
						{
							$errmsg = GetMessage("TASKS_UNKNOWN_UPDATE_ERROR");
							$errno = 'ERROR_UNKNOWN_UPDATE_TASK_ERROR';

							if ($ex = $APPLICATION->getException())
							{
								$errmsg = $ex->getString();
								$errno = $ex->getId();
							}

							$this->_errors[] = array('text' => $errmsg, 'id' => $errno);

							return false;
						}
					}

					if ($arFields['GROUP_ID'] && $arFields['GROUP_ID'] != $arTask['GROUP_ID'])
					{
						$updatePins = true;
						$arFields['STAGE_ID'] = 0;
					}

					$strUpdate = $DB->PrepareUpdate("b_tasks", $arFields, "tasks");
					$strSql = "UPDATE b_tasks SET ".$strUpdate." WHERE ID=".$ID;
					$result = $DB->QueryBind($strSql, $arBinds, false, "File: ".__FILE__."<br>Line: ".__LINE__);

					if ($result)
					{

						$arParticipants = array_merge(
							array(
								$arTask['CREATED_BY'],
								$arTask['RESPONSIBLE_ID']
							),
							(array)$arTask['ACCOMPLICES'],
							(array)$arTask['AUDITORS']
						);

						if (isset($arFields['CREATED_BY']))
							$arParticipants[] = $arFields['CREATED_BY'];

						if (isset($arFields['RESPONSIBLE_ID']))
							$arParticipants[] = $arFields['RESPONSIBLE_ID'];

						if (isset($arFields['ACCOMPLICES']))
						{
							$arParticipants = array_merge(
								$arParticipants,
								(array)$arFields['ACCOMPLICES']
							);
						}

						if (isset($arFields['AUDITORS']))
						{
							$arParticipants = array_merge(
								$arParticipants,
								(array)$arFields['AUDITORS']
							);
						}

						$arParticipants = array_unique($arParticipants);

						if (array_key_exists('STATUS', $arFields) &&
							($arFields['STATUS'] == static::STATE_SUPPOSEDLY_COMPLETED ||
							 $arFields['STATUS'] == static::STATE_COMPLETED))
						{
							// stop timer for responsible and accomplices, if exists
							$responsibleTimer = CTaskTimerManager::getInstance($arTask['RESPONSIBLE_ID']);
							$responsibleTimer->stop($ID);

							$accomplices = $arTask['ACCOMPLICES'];
							if (isset($accomplices) && !empty($accomplices))
							{
								foreach ($accomplices as $accompliceId)
								{
									$accompliceTimer = CTaskTimerManager::getInstance($accompliceId);
									$accompliceTimer->stop($ID);
								}
							}
						}

						// Emit pull event
						try
						{
							$arPullRecipients = array();

							foreach ($arParticipants as $userId)
							{
								$arPullRecipients[] = (int)$userId;
							}

							$taskGroupId = 0;    // no group
							$taskGroupIdBeforeUpdate = 0;    // no group

							if (isset($arTask['GROUP_ID']) && ($arTask['GROUP_ID'] > 0))
								$taskGroupId = (int)$arTask['GROUP_ID'];

							// if $arFields['GROUP_ID'] not given, than it means,
							// that group not changed during this update, so
							// we must take existing group_id (from $arTask)
							if (!array_key_exists('GROUP_ID', $arFields))
							{
								if (isset($arTask['GROUP_ID']) && ($arTask['GROUP_ID'] > 0))
									$taskGroupIdBeforeUpdate = (int)$arTask['GROUP_ID'];
								else
									$taskGroupIdBeforeUpdate = 0;    // no group
							}
							else    // Group given, use it
							{
								if ($arFields['GROUP_ID'] > 0)
									$taskGroupIdBeforeUpdate = (int)$arFields['GROUP_ID'];
								else
									$taskGroupIdBeforeUpdate = 0;    // no group
							}

							$arPullData = array(
								'TASK_ID'    => (int)$ID,
								'BEFORE'     => array(
									'GROUP_ID' => $taskGroupId
								),
								'AFTER'      => array(
									'GROUP_ID' => $taskGroupIdBeforeUpdate
								),
								'TS'         => time(),
								'event_GUID' => $eventGUID,
								'params'=>[
									'HIDE'=>array_key_exists('HIDE', $arParams)?(bool)$arParams['HIDE']:true
								]
							);

//							self::EmitPullWithTagPrefix(
//								$arPullRecipients,
//								'TASKS_GENERAL_',
//								'task_update',
//								$arPullData
//							);

							self::EmitPullWithTag(
								$arPullRecipients,
								'TASKS_TASK_'.(int)$ID,
								'task_update',
								$arPullData
							);
						}
						catch (Exception $e)
						{
							$bWasFatalError = true;
							$this->_errors[] = 'at line '.$e->GetLine().', '.$e->GetMessage();
						}

						// changes log
						$arTmp = array('arTask' => $arTask, 'arFields' => $arFields);

						if (isset($arTask['DURATION_PLAN']))// && isset($arTask['DURATION_TYPE']))
						{
							$arTmp['arTask']['DURATION_PLAN_SECONDS'] = $arTask['DURATION_PLAN_SECONDS'];
							unset($arTmp['arTask']['DURATION_PLAN']);
						}

						if (isset($arFields['DURATION_PLAN']))// && isset($arFields['DURATION_TYPE']))
						{
							// at this point, $arFields['DURATION_PLAN'] in seconds
							$arTmp['arFields']['DURATION_PLAN_SECONDS'] = $arFields['DURATION_PLAN'];
							unset($arTmp['arFields']['DURATION_PLAN']);
						}

						$arChanges = CTaskLog::GetChanges($arTmp['arTask'], $arTmp['arFields']);

						unset($arTmp);

						foreach ($arChanges as $key => $value)
						{
							$arLogFields = array(
								"TASK_ID"      => $ID,
								"USER_ID"      => $occurAsUserId,
								"CREATED_DATE" => $arFields["CHANGED_DATE"],
								"FIELD"        => $key,
								"FROM_VALUE"   => $value["FROM_VALUE"],
								"TO_VALUE"     => $value["TO_VALUE"]
							);

							$log = new CTaskLog();
							$log->Add($arLogFields);
						}

						if (isset($arFields["RESPONSIBLE_ID"]) && isset($arChanges["RESPONSIBLE_ID"]))
						{
							CTaskMembers::updateForTask($ID, array($arFields['RESPONSIBLE_ID']), 'R');
						}
						if (isset($arFields["CREATED_BY"]) && isset($arChanges["CREATED_BY"]))
						{
							CTaskMembers::updateForTask($ID, array($arFields['CREATED_BY']), 'O');
						}

						if (isset($arFields["ACCOMPLICES"]) && isset($arChanges["ACCOMPLICES"]))
						{
							CTaskMembers::updateForTask($ID, $arFields["ACCOMPLICES"], 'A');
						}

						if (isset($arFields["AUDITORS"]) && isset($arChanges["AUDITORS"]))
						{
							CTaskMembers::updateForTask($ID, $arFields["AUDITORS"], 'U');
						}

						if (isset($arFields["FILES"]) &&
							(isset($arChanges["NEW_FILES"]) || isset($arChanges["DELETED_FILES"])))
						{
							$arNotDeleteFiles = $arFields["FILES"];
							CTaskFiles::DeleteByTaskID($ID, $arNotDeleteFiles);
							CTasks::AddFiles(
								$ID,
								$arFields["FILES"],
								array(
									'USER_ID'               => $userID,
									'CHECK_RIGHTS_ON_FILES' => $bCheckRightsOnFiles
								)
							);
						}

						if (isset($arFields["TAGS"]) && isset($arChanges["TAGS"]))
						{
							CTaskTags::DeleteByTaskID($ID);
							CTasks::AddTags($ID, $arTask["CREATED_BY"], $arFields["TAGS"], $userID);
						}

						if (isset($arFields["DEPENDS_ON"]) && isset($arChanges["DEPENDS_ON"]))
						{
							CTaskDependence::DeleteByTaskID($ID);
							CTasks::AddPrevious($ID, $arFields["DEPENDS_ON"]);
						}

						if ($hasUfs)
						{
							$USER_FIELD_MANAGER->Update("TASKS_TASK", $ID, $arFields, $userID);
						}

						// backward compatibility with PARENT_ID
						if (array_key_exists('PARENT_ID', $arFields))
						{
							// PARENT_ID changed, reattach subtree from previous location to new one
							\Bitrix\Tasks\Internals\Helper\Task\Dependence::attach($ID, intval($arFields['PARENT_ID']));
						}

						// tasks dependence

						if ($shiftResult !== null && $arParams['CORRECT_DATE_PLAN_DEPENDENT_TASKS'])
						{
							$saveResult = $shiftResult->save(array('!ID' => $ID));
							if ($saveResult->isSuccess())
							{
								$this->lastOperationResultData['SHIFT_RESULT'] = $shiftResult->exportData();
							}
						}

						if (array_key_exists('STATUS', $arFields) && $arFields['STATUS'] == 5)
						{
							if ($arParams['AUTO_CLOSE'] !== false)
							{
								$closer = \Bitrix\Tasks\Processor\Task\AutoCloser::getInstance($userID);
								$closeResult = $closer->processEntity($ID, $arFields);
								if ($closeResult->isSuccess())
								{
									$closeResult->save(array('!ID' => $ID));
								}
							}
						}

						$bSkipNotification = (isset($arParams['SKIP_NOTIFICATION']) && $arParams['SKIP_NOTIFICATION']);
						$notifArFields = array_merge($arFields, array('CHANGED_BY' => $occurAsUserId));

						if (($status = intval($arFields["STATUS"])) &&
							$status > 0 &&
							$status < 8 &&
							((int)$arTask['STATUS'] !== (int)$arFields['STATUS'])    // only if status changed
						)
						{
							if ($status == 7)
							{
								$arTask["DECLINE_REASON"] = $arFields["DECLINE_REASON"];
							}

							if (!$bSkipNotification)
							{
								CTaskNotifications::SendStatusMessage($arTask, $status, $notifArFields);
							}
						}

						if (!$bSkipNotification)
						{
							CTaskNotifications::SendUpdateMessage($notifArFields, $arTask, false, $arParams);
						}

						CTaskComments::onAfterTaskUpdate($ID, $arTask, $arFields);

						$arFields["ID"] = $ID;

						$arMergedFields = array_merge($arTask, $arFields);

						CTaskSync::UpdateItem($arFields, $arTask); // MS Exchange

						$arFields['META:PREV_FIELDS'] = $arTask;

						try
						{
							$lastEventName = '';
							foreach (GetModuleEvents('tasks', 'OnTaskUpdate', true) as $arEvent)
							{
								$lastEventName = $arEvent['TO_CLASS'].'::'.$arEvent['TO_METHOD'].'()';
								ExecuteModuleEventEx($arEvent, array($ID, &$arFields, &$arTaskCopy));
							}
						}
						catch (Exception $e)
						{
							CTaskAssert::logWarning(
								'[0xee8999a8] exception in module event: '.
								$lastEventName.
								'; at file: '.
								$e->getFile().
								':'.
								$e->getLine().
								";\n"
							);
							\Bitrix\Tasks\Util::log($e);
						}

						unset($arFields['META:PREV_FIELDS']);

						CTasks::Index($arMergedFields, $arFields["TAGS"]); // search index
						SearchIndex::setTaskSearchIndex($ID, $arMergedFields);

						// clear cache
						static::addCacheIdToClear("tasks_".$ID);

						if ($arTask["GROUP_ID"])
						{
							static::addCacheIdToClear("tasks_group_".$arTask["GROUP_ID"]);
						}

						if ($arFields['GROUP_ID'] && ($arFields['GROUP_ID'] != $arTask['GROUP_ID']))
						{
							static::addCacheIdToClear("tasks_group_".$arFields["GROUP_ID"]);
						}

						foreach ($arParticipants as $userId)
						{
							static::addCacheIdToClear("tasks_user_".$userId);
						}

						//						CTaskCountersProcessor::onAfterTaskUpdate($arTask, $arFields);
						Counter::onAfterTaskUpdate($arTask, $arFields, $arParams);

						static::clearCache();

						if ($bWasFatalError)
						{
							soundex('push&pull: bWasFatalError === true');
						}

						//_dump_r($this->lastOperationResultData['SHIFT_RESULT']);

						$this->previousData = $arTask;

						if ($updatePins)
						{
							\Bitrix\Tasks\Kanban\StagesTable::pinInStage(
								$ID,
								array(
									'CREATED_BY' => $arTask['CREATED_BY']// because is not new task
								),
								true
							);
						}

						if ($arTask['FORUM_TOPIC_ID'] && $arTask['TITLE'] !== $arFields['TITLE'])
						{
							Integration\Forum\Task\Topic::updateTopicTitle($arTask['FORUM_TOPIC_ID'], $arFields['TITLE']);
						}

						Integration\Bizproc\Listener::onTaskUpdate($ID, $arFields, $arTaskCopy);

						return true;
					}
				}
				else
				{
					$e = $APPLICATION->GetException();
					foreach ($e->messages as $msg)
					{
						$this->_errors[] = $msg;
					}
				}
			}
		}

		if (sizeof($this->_errors) == 0)
			$this->_errors[] = array(
				"text" => GetMessage("TASKS_UNKNOWN_UPDATE_ERROR"),
				"id"   => "ERROR_UNKNOWN_UPDATE_TASK_ERROR"
			);

		return false;
	}

	/**
	 * Check if deadline is matching work time.
	 * Returns closest work time if not.
	 *
	 * @param $deadline
	 *
	 * @return DateTime|int|static
	 */
	private static function getDeadlineMatchWorkTime($deadline)
	{
		$resultDeadline = DateTime::createFromUserTimeGmt($deadline);

		$calendar = new Calendar();
		if (!$calendar->isWorkTime($resultDeadline))
		{
			$resultDeadline = $calendar->getClosestWorkTime($resultDeadline);
		}

		$resultDeadline = $resultDeadline->convertToLocalTime()->getTimestamp();
		$resultDeadline = DateTime::createFromTimestamp($resultDeadline - User::getTimeZoneOffsetCurrentUser());

		return $resultDeadline;
	}

	/**
	 * Occurs when user does not know anything about main task but is trying to change its sub task.
	 * This method returns true in that case, so we should not change parent.
	 *
	 * @param $oldParentId
	 * @param $newParentId
	 * @param $userId
	 *
	 * @return bool
	 */
	private static function checkFakeParentChange($oldParentId, $newParentId, $userId)
	{
		try
		{
			if (User::isSuper($userId))
			{
				return false;
			}

			if ($newParentId == false && $oldParentId)
			{
				try
				{
					$parentTask = new \CTaskItem($oldParentId, $userId);
					$parentTask->getData(false, ['select' => ['ID'], 'bSkipExtraData' => true]);
				}
				/** @noinspection PhpDeprecationInspection */
				catch (\TasksException $e)
				{
					/** @noinspection PhpDeprecationInspection */
					if ($e->getCode() == \TasksException::TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE)
					{
						return true;
					}
				}
			}

			return false;
		}
		catch (\Exception $exception)
		{
			return false;
		}
	}

	private static function parentChanged($oldData, $newData, $userId)
	{
		if (array_key_exists('PARENT_ID', $newData))
		{
			$fakeParentChange = static::checkFakeParentChange($oldData['PARENT_ID'], $newData['PARENT_ID'], $userId);

			return !$fakeParentChange && ($newData['PARENT_ID'] != $oldData['PARENT_ID']);
		}

		return false;
	}

	private static function datesChanged($oldData, $newData)
	{
		if (!array_key_exists('START_DATE_PLAN', $newData) && !array_key_exists('END_DATE_PLAN', $newData))
		{
			return false;
		}

		return ((string)$oldData['START_DATE_PLAN'] != (string)$newData['START_DATE_PLAN']) ||
			   ((string)$oldData['END_DATE_PLAN'] != (string)$newData['END_DATE_PLAN']);
	}

	private static function followDatesSetTrue($fields)
	{
		if (array_key_exists('SE_PARAMETER', $fields) && is_array($fields['SE_PARAMETER']))
		{
			foreach ($fields['SE_PARAMETER'] as $parameter)
			{
				if ($parameter['CODE'] == 1 && $parameter['VALUE'] == 'Y')
				{
					return true;
				}
			}
		}

		return false;
	}

	public static function checkCacheAutoClearEnabled()
	{
		return static::$cacheClearEnabled;
	}

	public static function disableCacheAutoClear()
	{
		if (!static::$cacheClearEnabled)
		{
			return false;
		}

		static::$cacheClearEnabled = false;

		return true;
	}

	public static function enableCacheAutoClear($clearNow = true)
	{
		static::$cacheClearEnabled = true;

		if ($clearNow)
		{
			static::clearCache();
		}
	}

	private static function addCacheIdToClear($cacheId)
	{
		if ((string)$cacheId === '')
		{
			return;
		}

		static::$cacheIds[$cacheId] = true;
	}

	private static function clearCache()
	{
		if (!static::$cacheClearEnabled)
		{
			return;
		}

		global $CACHE_MANAGER;

		if (!empty(static::$cacheIds))
		{
			foreach (static::$cacheIds as $id => $void)
			{
				$CACHE_MANAGER->ClearByTag($id);
			}

			static::$cacheIds = array();
		}
	}

	/**
	 * This method is deprecated. Use CTaskItem::delete() instead.
	 *
	 * @param $taskId
	 * @param array $parameters
	 * @return bool
	 *
	 * @deprecated
	 */
	public static function Delete($taskId, $parameters = [])
	{
		global $DB, $CACHE_MANAGER, $USER_FIELD_MANAGER;

		$taskId = intval($taskId);
		if ($taskId < 1)
		{
			return false;
		}

		$actorUserId = User::getId();
		if (!$actorUserId)
		{
			$actorUserId = User::getAdminId();
		}

		if (isset($parameters['META::EVENT_GUID']))
		{
			$eventGuid = $parameters['META::EVENT_GUID'];
			unset($parameters['META::EVENT_GUID']);
		}
		else
		{
			$eventGuid = sha1(uniqid('AUTOGUID', true));
		}

		if (isset($parameters['skipExchangeSync']) && ($parameters['skipExchangeSync'] === 'Y' || $parameters['skipExchangeSync'] === true))
		{
			$skipExchangeSync = true;
		}
		else
		{
			$skipExchangeSync = false;
		}

		/** @noinspection PhpDeprecationInspection */
		$taskDbResult = CTasks::GetByID($taskId, false);
		if ($taskData = $taskDbResult->Fetch())
		{
			$safeDelete = false;
			try
			{
				if (\Bitrix\Main\Loader::includeModule('recyclebin'))
				{
					$result = Integration\Recyclebin\Task::OnBeforeTaskDelete($taskId, $taskData);
					$safeDelete = $result;
				}
			}
			catch (\Exception $e)
			{

			}

			foreach (GetModuleEvents('tasks', 'OnBeforeTaskDelete', true) as $arEvent)
			{
				if (ExecuteModuleEventEx($arEvent, [$taskId, $taskData]) === false)
				{
					return false;
				}
			}

			// stop timer, if exists
			$timer = CTaskTimerManager::getInstance($taskData['RESPONSIBLE_ID']);
			$timer->stop($taskId);

			CTaskMembers::DeleteAllByTaskID($taskId);
			CTaskDependence::DeleteByTaskID($taskId);
			CTaskDependence::DeleteByDependsOnID($taskId);
			CTaskReminders::DeleteByTaskID($taskId);

			$tableResult = ProjectDependenceTable::getList([
				"select" => ['TASK_ID'],
				"filter" => [
					"=TASK_ID" => $taskId,
					"DEPENDS_ON_ID" => $taskId
				]
			]);

			if (ProjectDependenceTable::checkItemLinked($taskId) || $tableResult->fetch())
			{
				ProjectDependenceTable::deleteLink($taskId, $taskId);
			}

			if (!$safeDelete)
			{
				CTaskFiles::DeleteByTaskID($taskId);
				CTaskTags::DeleteByTaskID($taskId);
				FavoriteTable::deleteByTaskId($taskId, ['LOW_LEVEL' => true]);
				SortingTable::deleteByTaskId($taskId);
				TaskStageTable::clearTask($taskId);
				TaskCheckListFacade::deleteByEntityIdOnLowLevel($taskId);

				$tablesToClear = [
					ViewedTable::class => ['TASK_ID', 'USER_ID'],
					ParameterTable::class => ['ID'],
					SearchIndexTable::class => ['ID'],
				];

				foreach ($tablesToClear as $table => $select)
				{
					/** @var \Bitrix\Main\ORM\Query\Result $tableResult */
					$tableResult = $table::getList([
						"select" => $select,
						"filter" => [
							"=TASK_ID" => $taskId,
						],
					]);

					while ($item = $tableResult->fetch())
					{
						$table::delete($item);
					}
				}
			}

			SortingTable::fixSiblingsEx($taskId);

			// by default, CTasks::Delete() should not delete the entire sub-tree, so we need to delete only node itself
			$children = Dependence::getSubTree($taskId)->find(['__PARENT_ID' => $taskId])->getData();
			Dependence::delete($taskId);

			if ($taskData['PARENT_ID'] && !empty($children))
			{
				foreach ($children as $child)
				{
					Dependence::attach($child['__ID'], $taskData['PARENT_ID']);
				}
			}

			if ($taskData['PARENT_ID'] && $taskData['START_DATE_PLAN'] && $taskData['END_DATE_PLAN'])
			{
				// we need to scan for parent bracket tasks change...
				$scheduler = \Bitrix\Tasks\Processor\Task\Scheduler::getInstance($actorUserId);
				// we could use MODE => DETACH here, but there we can act in more effective way by
				// re-calculating tree of PARENT_ID after removing link between ID and PARENT_ID
				// we also do not need to calculate detached tree
				// it is like DETACH_AFTER
				$shiftResult = $scheduler->processEntity($taskData['PARENT_ID']);
				if ($shiftResult->isSuccess())
				{
					$shiftResult->save();
				}
			}

			// todo: see \CTaskPlannerMaintance::reviseTaskLists(), move task list from option to a table, and then just do cleaning
			// todo: dayplan by TASK_ID here for each user, regardless to the role; the following solution works only for current user, creator and responsible
			//\CTaskPlannerMaintance::plannerActions(array('remove' => array($taskId)));
			//\CTaskPlannerMaintance::plannerActions(array('remove' => array($taskId)), SITE_ID, $taskData['CREATED_BY']);
			//\CTaskPlannerMaintance::plannerActions(array('remove' => array($taskId)), SITE_ID, $taskData['RESPONSIBLE_ID']);

			$CACHE_MANAGER->ClearByTag("tasks_" . $taskId);

			// clear cache
			if ($taskData["GROUP_ID"])
			{
				$CACHE_MANAGER->ClearByTag("tasks_group_" . $taskData["GROUP_ID"]);
			}
			$arParticipants = array_unique(
				array_merge(
					[
						$taskData["CREATED_BY"],
						$taskData["RESPONSIBLE_ID"]
					],
					$taskData["ACCOMPLICES"],
					$taskData["AUDITORS"]
				)
			);
			foreach ($arParticipants as $userId)
			{
				$CACHE_MANAGER->ClearByTag("tasks_user_" . $userId);
			}

			$strSql = "UPDATE b_tasks_template SET TASK_ID = NULL WHERE TASK_ID = " . $taskId;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$strSql = "UPDATE b_tasks_template SET PARENT_ID = " .
					  ($taskData["PARENT_ID"]? $taskData["PARENT_ID"] : "NULL") .
					  " WHERE PARENT_ID = " .
					  $taskId;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$strSql = "UPDATE b_tasks SET PARENT_ID = " .
					  ($taskData["PARENT_ID"] ? $taskData["PARENT_ID"] : "NULL") .
					  " WHERE PARENT_ID = " .
					  $taskId;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$strUpdate = $DB->PrepareUpdate(
				"b_tasks",
				[
					'ZOMBIE' => 'Y',
					'CHANGED_BY' => $actorUserId,
					'CHANGED_DATE' => \Bitrix\Tasks\UI::formatDateTime(User::getTime())
				],
				"tasks"
			);
			$strSql = "UPDATE b_tasks SET " . $strUpdate . " WHERE ID = " . $taskId;

			if ($DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			{
				CTaskNotifications::SendDeleteMessage($taskData);

				if (!$safeDelete)
				{
					Integration\Forum\Task\Topic::delete($taskData["FORUM_TOPIC_ID"]);
					$USER_FIELD_MANAGER->Delete('TASKS_TASK', $taskId);
				}

				if (!$skipExchangeSync)
				{
					CTaskSync::DeleteItem($taskData); // MS Exchange
				}

				// Emit pull event
				try
				{
					$arPullRecipients = [];

					foreach ($arParticipants as $userId)
					{
						$arPullRecipients[] = (int)$userId;
					}

					$taskGroupId = 0; // no group

					if (isset($taskData['GROUP_ID']) && $taskData['GROUP_ID'] > 0)
					{
						$taskGroupId = (int)$taskData['GROUP_ID'];
					}

					$arPullData = [
						'TASK_ID' => $taskId,
						'TS' => time(),
						'event_GUID' => $eventGuid,
						'BEFORE' => [
							'GROUP_ID' => $taskGroupId
						],
					];

//					self::EmitPullWithTagPrefix(
//						$arPullRecipients,
//						'TASKS_GENERAL_',
//						'task_remove',
//						$arPullData
//					);

					self::EmitPullWithTag(
						$arPullRecipients,
						'TASKS_TASK_' . $taskId,
						'task_remove',
						$arPullData
					);
				}
				catch (Exception $e)
				{

				}

				foreach (GetModuleEvents('tasks', 'OnTaskDelete', true) as $arEvent)
				{
					ExecuteModuleEventEx($arEvent, [$taskId]);
				}

				if (CModule::IncludeModule("search"))
				{
					CSearch::DeleteIndex("tasks", $taskId);
				}

				Counter::onAfterTaskDelete($taskData);
				Integration\Bizproc\Listener::onTaskDelete($taskId);
			}

			return true;
		}

		return false;
	}

	/**
	 * @param $ID
	 *
	 * This method MUST be called after sync with all Outlook clients.
	 * We can't determine such moment, so we should terminate zombies
	 * for some time after task been deleted.
	 */
	private static function terminateZombie($ID)
	{
		global $DB, $USER_FIELD_MANAGER;

		$res = CTasks::GetList(
			array(),
			array('ID' => (int)$ID, 'ZOMBIE' => 'Y'),
			array('ID'),
			array('bGetZombie' => true)
		);

		if ($res && ($task = $res->fetch()))
		{
			foreach (GetModuleEvents('tasks', 'OnBeforeTaskZombieDelete', true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($ID));
			}

			CTaskCheckListItem::deleteByTaskId($ID);
			CTaskLog::DeleteByTaskId($ID);

			$USER_FIELD_MANAGER->Delete("TASKS_TASK", $ID);

			$strSql = "DELETE FROM b_tasks WHERE ID = ".(int)$ID;

			$DB->query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			foreach (GetModuleEvents('tasks', 'OnTaskZombieDelete', true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($ID));
			}
		}
	}

	protected static function GetSqlByFilter($arFilter, $userID, $sAliasPrefix, $bGetZombie, $bMembersTableJoined = false, $params = [])
	{
		global $DB;

		$bFullJoin = null;

		if (!is_array($arFilter))
			throw new TasksException(
				'GetSqlByFilter: expected array, but something other given: '.var_export($arFilter, true)
			);

		$logicStr = ' AND ';

		if (isset($arFilter['::LOGIC']))
		{
			switch ($arFilter['::LOGIC'])
			{
				case 'AND':
					$logicStr = ' AND ';
					break;

				case 'OR':
					$logicStr = ' OR ';
					break;

				default:
					throw new TasksException('Unknown logic in filter');
					break;
			}
		}

		$arSqlSearch = array();

		foreach ($arFilter as $key => $val)
		{
			// Skip meta-key
			if ($key === '::LOGIC')
				continue;

			// Skip markers
			if ($key === '::MARKERS')
				continue;

			// Subfilter?
			if (static::isSubFilterKey($key))
			{
				$arSqlSearch[] = self::GetSqlByFilter($val, $userID, $sAliasPrefix, $bGetZombie, $bMembersTableJoined, $params);
				continue;
			}

			$key = ltrim($key);

			// This type of operations should be processed in special way
			// Fields like "META:DEADLINE_TS" will be replaced to "DEADLINE"
			if (substr($key, -3) === '_TS')
			{
				$arSqlSearch = array_merge(
					$arSqlSearch,
					self::getSqlForTimestamps($key, $val, $userID, $sAliasPrefix, $bGetZombie)
				);

				continue;
			}

			$res = CTasks::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = strtoupper($key);

			switch ($key)
			{
				case 'META::ID_OR_NAME':
					if (strtoupper($DB->type) == "ORACLE")
						$arSqlSearch[] = " (".
										 $sAliasPrefix.
										 "T.ID = '".
										 intval($val).
										 "' OR (UPPER(".
										 $sAliasPrefix.
										 "T.TITLE) UPPER('%".
										 $DB->ForSqlLike($val).
										 "%')) ) ";
					else
						$arSqlSearch[] = " (".
										 $sAliasPrefix.
										 "T.ID = '".
										 intval($val).
										 "' OR (UPPER(".
										 $sAliasPrefix.
										 "T.TITLE) LIKE UPPER('%".
										 $DB->ForSqlLike($val).
										 "%')) ) ";
					break;

				//case "DURATION_PLAN": // temporal
				case "PARENT_ID":
				case "GROUP_ID":
				case "STATUS_CHANGED_BY":
				case "FORUM_TOPIC_ID":
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix."T.".$key,
						$val,
						"number",
						$bFullJoin,
						$cOperationType
					);
					break;

				case "ID":
				case "PRIORITY":
				case "CREATED_BY":
				case "RESPONSIBLE_ID":
				case "STAGE_ID":
				case 'TIME_ESTIMATE':
				case 'FORKED_BY_TEMPLATE_ID':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix."T.".$key,
						$val,
						"number_wo_nulls",
						$bFullJoin,
						$cOperationType
					);
					break;

				case "REFERENCE:RESPONSIBLE_ID":
					$key = 'RESPONSIBLE_ID';
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix."T.".$key,
						$val,
						'reference',
						$bFullJoin,
						$cOperationType
					);
					break;

				case "REFERENCE:START_DATE_PLAN":
					$key = 'START_DATE_PLAN';
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix."T.".$key,
						$val,
						'reference',
						$bFullJoin,
						$cOperationType
					);
					break;

				case 'META:GROUP_ID_IS_NULL_OR_ZERO':
					$key = 'GROUP_ID';
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix."T.".$key,
						$val,
						"null_or_zero",
						$bFullJoin,
						$cOperationType,
						false
					);
					break;

				case 'META:PARENT_ID_OR_NULL':
					if ((array)$val)
					{
						$arSqlSearch[] = '(T.PARENT_ID IN ('.join(', ', array_map('intval', (array)$val)).') OR T.PARENT_ID IS NULL)';
					}
					break;

				case "CHANGED_BY":
					$arSqlSearch[] = CTasks::FilterCreate(
						"CASE WHEN ".
						$sAliasPrefix.
						"T.".
						$key.
						" IS NULL THEN ".
						$sAliasPrefix.
						"T.CREATED_BY ELSE ".
						$sAliasPrefix.
						"T.".
						$key.
						" END",
						$val,
						"number",
						$bFullJoin,
						$cOperationType
					);
					break;

				case 'GUID':
				case 'TITLE':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix."T.".$key,
						$val,
						"string",
						$bFullJoin,
						$cOperationType
					);
					break;

				case 'FULL_SEARCH_INDEX':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix."TSIF.SEARCH_INDEX",
						$val,
						"fulltext",
						$bFullJoin,
						$cOperationType
					);
					break;

				case 'COMMENT_SEARCH_INDEX':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix."TSIC.SEARCH_INDEX",
						$val,
						"fulltext",
						$bFullJoin,
						$cOperationType
					);
					break;

				case "TAG":
					if (!is_array($val))
					{
						$val = array($val);
					}
					$arConds = array();
					foreach ($val as $tag)
					{
						if ($tag)
						{
							$arConds[] = "(".$sAliasPrefix."TT.NAME = '".$DB->ForSql($tag)."')";
						}
					}
					if (count($arConds))
					{
						$arSqlSearch[] = trim($sAliasPrefix."T.ID IN(
							SELECT
								".$sAliasPrefix."TT.TASK_ID
							FROM
								b_tasks_tag ".$sAliasPrefix."TT
							WHERE
								(".implode(" OR ", $arConds).")
							AND
								".$sAliasPrefix."TT.TASK_ID = ".$sAliasPrefix."T.ID
						)");
					}
					break;

				case 'REAL_STATUS':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix."T.STATUS",
						$val,
						"number",
						$bFullJoin,
						$cOperationType
					);
					break;

				case 'DEADLINE_COUNTED':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix."T.DEADLINE_COUNTED",
						$val,
						"number_wo_nulls",
						$bFullJoin,
						$cOperationType
					);
					break;

				case 'VIEWED':
					$arSqlSearch[] = CTasks::FilterCreate(
						"
						CASE
							WHEN
								".$sAliasPrefix."TV.USER_ID IS NULL
								AND
								(".$sAliasPrefix."T.STATUS = 1 OR ".$sAliasPrefix."T.STATUS = 2)
							THEN
								'0'
							ELSE
								'1'
						END
					",
						$val,
						"number",
						$bFullJoin,
						$cOperationType
					);
					break;

				case "STATUS_EXPIRED": // expired: deadline in past and

					$arSqlSearch[] = ($cOperationType == 'N' ? 'not' : '').
									 "(".
									 $sAliasPrefix.
									 "T.DEADLINE < ".
									 $DB->CurrentTimeFunction().
									 " AND ".
									 $sAliasPrefix.
									 "T.STATUS != '4' AND ".
									 $sAliasPrefix.
									 "T.STATUS != '5' AND (".
									 $sAliasPrefix.
									 "T.STATUS != '7' OR ".
									 $sAliasPrefix.
									 "T.RESPONSIBLE_ID != ".
									 $userID.
									 "))";

					break;

				case "STATUS_NEW": // viewed by a specified user + status is either new or pending

					$arSqlSearch[] = ($cOperationType == 'N' ? 'not' : '')."(

						".$sAliasPrefix."TV.USER_ID IS NULL
						AND
						".$sAliasPrefix."T.CREATED_BY != ".$userID."
						AND
						(".$sAliasPrefix."T.STATUS = 1 OR ".$sAliasPrefix."T.STATUS = 2)

					)";
					$bFullJoin = true; // join TV

					break;

				case "STATUS":
					$arSqlSearch[] = CTasks::FilterCreate(
						"
						CASE
							WHEN
								".
						$sAliasPrefix.
						"T.DEADLINE < DATE_ADD(".
						$DB->CurrentTimeFunction().
						", INTERVAL ".
						Counter::getDeadlineTimeLimit().
						" SECOND)
								AND ".
						$sAliasPrefix.
						"T.DEADLINE >= ".
						$DB->CurrentTimeFunction().
						"
								AND ".
						$sAliasPrefix.
						"T.STATUS != '4'
								AND ".
						$sAliasPrefix.
						"T.STATUS != '5'
								AND (
									".
						$sAliasPrefix.
						"T.STATUS != '7'
									OR ".
						$sAliasPrefix.
						"T.RESPONSIBLE_ID != ".
						intval($userID).
						"
								)
							THEN
								'-3'
							WHEN
								".
						$sAliasPrefix.
						"T.DEADLINE < ".
						$DB->CurrentTimeFunction().
						" AND ".
						$sAliasPrefix.
						"T.STATUS != '4' AND ".
						$sAliasPrefix.
						"T.STATUS != '5' AND (".
						$sAliasPrefix.
						"T.STATUS != '7' OR ".
						$sAliasPrefix.
						"T.RESPONSIBLE_ID != ".
						$userID.
						")
							THEN
								'-1'
							WHEN
								".
						$sAliasPrefix.
						"TV.USER_ID IS NULL
								AND
								".
						$sAliasPrefix.
						"T.CREATED_BY != ".
						$userID.
						"
								AND
								(".
						$sAliasPrefix.
						"T.STATUS = 1 OR ".
						$sAliasPrefix.
						"T.STATUS = 2)
							THEN
								'-2'
							ELSE
								".
						$sAliasPrefix.
						"T.STATUS
						END
					",
						$val,
						"number",
						$bFullJoin,
						$cOperationType
					);

					break;

				case 'MARK':
				case 'XML_ID':
				case 'SITE_ID':
				case 'ZOMBIE':
				case 'ADD_IN_REPORT':
				case 'ALLOW_TIME_TRACKING':
				case 'ALLOW_CHANGE_DEADLINE':
				case 'MATCH_WORK_TIME':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix."T.".$key,
						$val,
						"string_equal",
						$bFullJoin,
						$cOperationType
					);
					break;

				case "END_DATE_PLAN":
				case "START_DATE_PLAN":
				case "DATE_START":
				case "DEADLINE":
				case "CREATED_DATE":
				case "CLOSED_DATE":
					if (($val === false) || ($val === ''))
						$arSqlSearch[] = CTasks::FilterCreate(
							$sAliasPrefix."T.".$key,
							$val,
							"date",
							$bFullJoin,
							$cOperationType,
							$bSkipEmpty = false
						);
					else
						$arSqlSearch[] = CTasks::FilterCreate(
							$sAliasPrefix."T.".$key,
							$DB->CharToDateFunction($val),
							"date",
							$bFullJoin,
							$cOperationType
						);
					break;

				case "CHANGED_DATE":
					$arSqlSearch[] = CTasks::FilterCreate(
						"CASE WHEN ".
						$sAliasPrefix.
						"T.".
						$key.
						" IS NULL THEN ".
						$sAliasPrefix.
						"T.CREATED_DATE ELSE ".
						$sAliasPrefix.
						"T.".
						$key.
						" END",
						$DB->CharToDateFunction($val),
						"date",
						$bFullJoin,
						$cOperationType
					);
					break;

				case "ACCOMPLICE":
					if (!is_array($val))
						$val = array($val);

					$val = array_filter($val);

					$arConds = array();

					if ($bMembersTableJoined)
					{
						if ($cOperationType !== 'N')
						{
							foreach ($val as $id)
							{
								$arConds[] = "(".$sAliasPrefix."TM.USER_ID = '".intval($id)."')";
							}

							if (!empty($arConds))
								$arSqlSearch[] = '('.$sAliasPrefix."TM.TYPE = 'A' AND (".implode(" OR ", $arConds).'))';
						}
						else
						{
							foreach ($val as $id)
							{
								$arConds[] = "(".$sAliasPrefix."TM.USER_ID != '".intval($id)."')";
							}

							if (!empty($arConds))
								$arSqlSearch[] = '('.
												 $sAliasPrefix.
												 "TM.TYPE = 'A' AND (".
												 implode(" AND ", $arConds).
												 '))';
						}
					}
					else
					{
						foreach ($val as $id)
						{
							$arConds[] = "(".$sAliasPrefix."TM.USER_ID = '".intval($id)."')";
						}

						if (!empty($arConds))
						{
							$arSqlSearch[] = ($cOperationType !== 'N' ? 'EXISTS' : 'NOT EXISTS')."(
								SELECT
									'x'
								FROM
									b_tasks_member ".$sAliasPrefix."TM
								WHERE
									(".implode(" OR ", $arConds).")
								AND
									".$sAliasPrefix."TM.TASK_ID = ".$sAliasPrefix."T.ID
								AND
									".$sAliasPrefix."TM.TYPE = 'A'
							)";
						}
					}
					break;

				case "PERIOD":
				case "ACTIVE":
					if ($val["START"] || $val["END"])
					{
						$strDateStart = $strDateEnd = false;

						if (MakeTimeStamp($val['START']) > 0)
						{
							$strDateStart = $DB->CharToDateFunction(
								$DB->ForSql(
									CDatabase::FormatDate(
										$val['START'],
										FORMAT_DATETIME
									)
								)
							);
						}

						if (MakeTimeStamp($val['END']))
						{
							$strDateEnd = $DB->CharToDateFunction(
								$DB->ForSql(
									CDatabase::FormatDate(
										$val['END'],
										FORMAT_DATETIME
									)
								)
							);
						}

						if (($strDateStart !== false) && ($strDateEnd !== false))
						{
							$arSqlSearch[] = "(
									(T.CREATED_DATE >= $strDateStart AND T.CLOSED_DATE <= $strDateEnd)
								OR
									(T.CHANGED_DATE >= $strDateStart AND T.CHANGED_DATE <= $strDateEnd)
								OR
									(T.CREATED_DATE <= $strDateStart AND T.CLOSED_DATE IS NULL)
								)";
						}
						elseif (($strDateStart !== false) && ($strDateEnd === false))
						{
							$arSqlSearch[] = "(
									(T.CREATED_DATE >= $strDateStart)
								OR
									(T.CHANGED_DATE >= $strDateStart)
								)";
						}
						elseif (($strDateStart === false) && ($strDateEnd !== false))
						{
							$arSqlSearch[] = "(
									(T.CLOSED_DATE <= $strDateEnd)
									(T.CHANGED_DATE <= $strDateEnd)
								)";
						}
					}
					break;

				case "AUDITOR":
					if (!is_array($val))
						$val = array($val);

					$val = array_filter($val);

					$arConds = array();

					if ($bMembersTableJoined)
					{
						if ($cOperationType !== 'N')
						{
							foreach ($val as $id)
							{
								$arConds[] = "(".$sAliasPrefix."TM.USER_ID = '".intval($id)."')";
							}

							if (!empty($arConds))
								$arSqlSearch[] = '('.$sAliasPrefix."TM.TYPE = 'U' AND (".implode(" OR ", $arConds).'))';
						}
						else
						{
							foreach ($val as $id)
							{
								$arConds[] = "(".$sAliasPrefix."TM.USER_ID != '".intval($id)."')";
							}

							if (!empty($arConds))
								$arSqlSearch[] = '('.
												 $sAliasPrefix.
												 "TM.TYPE = 'U' AND (".
												 implode(" AND ", $arConds).
												 '))';
						}
					}
					else
					{
						foreach ($val as $id)
						{
							$arConds[] = "(".$sAliasPrefix."TM.USER_ID = '".intval($id)."')";
						}

						if (!empty($arConds))
						{
							$arSqlSearch[] = ($cOperationType !== 'N' ? 'EXISTS' : 'NOT EXISTS')."(
								SELECT
									'x'
								FROM
									b_tasks_member ".$sAliasPrefix."TM
								WHERE
									(".implode(" OR ", $arConds).")
								AND
									".$sAliasPrefix."TM.TASK_ID = ".$sAliasPrefix."T.ID
								AND
									".$sAliasPrefix."TM.TYPE = 'U'
							)";
						}
					}

					break;

				case "DOER":
					$val = intval($val);
					$arSqlSearch[] = "(
						".$sAliasPrefix."T.RESPONSIBLE_ID = ".$val."
						OR
						EXISTS(
							SELECT 'x'
							FROM
								b_tasks_member ".$sAliasPrefix."TM
							WHERE
								".$sAliasPrefix."TM.TASK_ID = ".$sAliasPrefix."T.ID
								AND
								".$sAliasPrefix."TM.USER_ID = '".$val."'
								AND
								".$sAliasPrefix."TM.TYPE = 'A'
							)
						)";
					break;

				case "MEMBER":
					$val = intval($val);
					$arSqlSearch[] = "(
						".$sAliasPrefix."T.CREATED_BY = ".intval($val)."
						OR
						".$sAliasPrefix."T.RESPONSIBLE_ID = ".intval($val)."
						OR
						EXISTS(
							SELECT 'x' FROM b_tasks_member ".$sAliasPrefix."TM
							WHERE
								".$sAliasPrefix."TM.TASK_ID = ".$sAliasPrefix."T.ID
								AND
								".$sAliasPrefix."TM.USER_ID = '".$val."'
						)
					)";
					break;

				case "DEPENDS_ON":
					if (!is_array($val))
					{
						$val = array($val);
					}
					$arConds = array();
					foreach ($val as $id)
					{
						if ($id)
						{
							$arConds[] = "(".$sAliasPrefix."TD.TASK_ID = '".intval($id)."')";
						}
					}
					if (sizeof($arConds))
					{
						$arSqlSearch[] = "EXISTS(
							SELECT
								'x'
							FROM
								b_tasks_dependence ".$sAliasPrefix."TD
							WHERE
								(".implode(" OR ", $arConds).")
							AND
								".$sAliasPrefix."TD.DEPENDS_ON_ID = ".$sAliasPrefix."T.ID
						)";
					}
					break;

				case "ONLY_ROOT_TASKS":
					if ($val == "Y")
					{
						$arSqlSearch[] = "(".
										 $sAliasPrefix.
										 "T.PARENT_ID IS NULL OR ".
										 $sAliasPrefix.
										 "T.PARENT_ID = '0' OR NOT EXISTS (".
										 CTasks::GetRootSubQuery($arFilter, $bGetZombie, $sAliasPrefix, $params).
										 "))";
					}
					break;

				case "SUBORDINATE_TASKS":
					if ($val == "Y")
					{
						$arSubSqlSearch = array(
							$sAliasPrefix."T.CREATED_BY = ".$userID,
							$sAliasPrefix."T.RESPONSIBLE_ID = ".$userID,
							"EXISTS(
								SELECT 'x'
								FROM
									b_tasks_member ".$sAliasPrefix."TM
								WHERE
									".$sAliasPrefix."TM.TASK_ID = ".$sAliasPrefix."T.ID
									AND
									".$sAliasPrefix."TM.USER_ID = ".$userID."
							)"
						);
						// subordinate check
						if ($strSql = CTasks::GetSubordinateSql($sAliasPrefix, array('USER_ID' => $userID)))
						{
							$arSubSqlSearch[] = "EXISTS(".$strSql.")";
						}

						$arSqlSearch[] = "(".implode(" OR ", $arSubSqlSearch).")";
					}
					break;

				case "OVERDUED":
					if ($val == "Y")
					{
						$arSqlSearch[] = $sAliasPrefix.
										 "T.CLOSED_DATE IS NOT NULL AND ".
										 $sAliasPrefix.
										 "T.DEADLINE IS NOT NULL AND ".
										 $sAliasPrefix.
										 "T.DEADLINE < CLOSED_DATE";
					}
					break;

				case "SAME_GROUP_PARENT":
					if ($val == "Y" && !array_key_exists("ONLY_ROOT_TASKS", $arFilter))
					{
						$arSqlSearch[] = "EXISTS(
							SELECT
								'x'
							FROM
								b_tasks ".$sAliasPrefix."PT
							WHERE
								".$sAliasPrefix."T.PARENT_ID = ".$sAliasPrefix."PT.ID
							AND
								(".$sAliasPrefix."PT.GROUP_ID = ".$sAliasPrefix."T.GROUP_ID
								OR (".$sAliasPrefix."PT.GROUP_ID IS NULL AND ".$sAliasPrefix."T.GROUP_ID IS NULL)
								OR (".$sAliasPrefix."PT.GROUP_ID = 0 AND ".$sAliasPrefix."T.GROUP_ID IS NULL)
								OR (".$sAliasPrefix."PT.GROUP_ID IS NULL AND ".$sAliasPrefix."T.GROUP_ID = 0)
								)
							".($bGetZombie ? "" : " AND ".$sAliasPrefix."PT.ZOMBIE = 'N' ")."
						)";
					}
					break;

				case "DEPARTMENT_ID":
					if ($strSql = CTasks::GetDeparmentSql($val, $sAliasPrefix))
					{
						$arSqlSearch[] = "EXISTS(".$strSql.")";
					}
					break;

				case 'CHECK_PERMISSIONS':
					break;

				case 'FAVORITE':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix."FVT.TASK_ID",
						$val,
						"left_existence",
						$bFullJoin,
						$cOperationType,
						false
					);
					break;

				case 'SORTING':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix."SRT.TASK_ID",
						$val,
						"left_existence",
						$bFullJoin,
						$cOperationType,
						false
					);
					break;

				case 'STAGES_ID':
					$arSqlSearch[] = CTasks::FilterCreate(
						$sAliasPrefix."STG.STAGE_ID",
						$val,
						"number",
						$bFullJoin,
						$cOperationType,
						false
					);
					break;

				default:
					if ((strlen($key) >= 3) && (substr($key, 0, 3) === 'UF_'))
					{
						;    // It's OK, this fields will be processed by UserFieldManager
					}
					else
					{
						$extraData = '';

						if (isset($_POST['action']) && ($_POST['action'] === 'group_action'))
						{
							$extraData = '; Extra data: <data0>'.
										 serialize(array($_POST['arFilter'], $_POST['action'], $arFilter)).
										 '</data0>';
						}
						else
						{
							$extraData = '; Extra data: <data1>'.serialize($arFilter).'</data1>';
						}

						//CTaskAssert::logError('[0x6024749e] unexpected field in filter: ' . $key . $extraData);

						//throw new TasksException('Bad filter argument: '.$key, TasksException::TE_WRONG_ARGUMENTS);
					}
					break;
			}
		}

		$sql = implode(
			$logicStr,
			array_filter(
				$arSqlSearch
			)
		);

		if ($sql == '')
			$sql = '1=1';

		return ('('.$sql.')');
	}

	private static function getSqlForTimestamps($key, $val, $userID, $sAliasPrefix, $bGetZombie)
	{
		static $ts = null;        // some fixed timestamp of "now" (for consistency)

		if ($ts === null)
			$ts = CTasksPerHitOption::getHitTimestamp();

		$bTzWasDisabled = !CTimeZone::enabled();

		if ($bTzWasDisabled)
			CTimeZone::enable();

		// Adjust UNIX TS to "Bitrix timestamp"
		$tzOffset = CTimeZone::getOffset();
		$ts += $tzOffset;

		if ($bTzWasDisabled)
			CTimeZone::disable();

		$arSqlSearch = array();

		$arFilter = array(
			'::LOGIC' => 'AND'
		);

		$key = ltrim($key);

		$res = CTasks::MkOperationFilter($key);
		$fieldName = substr($res["FIELD"], 5, -3);    // Cutoff prefix "META:" and suffix "_TS"
		$cOperationType = $res["OPERATION"];

		$operationSymbol = substr($key, 0, -1 * strlen($res["FIELD"]));

		if (substr($cOperationType, 0, 1) !== '#')
		{
			switch ($operationSymbol)
			{
				case '<':
					$operationCode = CTaskFilterCtrl::OP_STRICTLY_LESS;
					break;

				case '>':
					$operationCode = CTaskFilterCtrl::OP_STRICTLY_GREATER;
					break;

				case '<=':
					$operationCode = CTaskFilterCtrl::OP_LESS_OR_EQUAL;
					break;

				case '>=':
					$operationCode = CTaskFilterCtrl::OP_GREATER_OR_EQUAL;
					break;

				case '!=':
					$operationCode = CTaskFilterCtrl::OP_NOT_EQUAL;
					break;

				case '':
				case '=':
					$operationCode = CTaskFilterCtrl::OP_EQUAL;
					break;

				default:
					CTaskAssert::log(
						'Unknown operation code: '.
						$operationSymbol.
						'; $key = '.
						$key.
						'; it will be silently ignored, incorrect results expected',
						CTaskAssert::ELL_ERROR    // errors, incorrect results expected
					);

					return ($arSqlSearch);
					break;
			}
		}
		else
			$operationCode = (int)substr($cOperationType, 1);

		$date1 = $date2 = $cOperationType1 = $cOperationType2 = null;

		// sometimes we can have DAYS in $val, not TIMESTAMP
		if ($operationCode != CTaskFilterCtrl::OP_DATE_NEXT_DAYS &&
			$operationCode != CTaskFilterCtrl::OP_DATE_LAST_DAYS)
		{
			$val += $tzOffset;
		}

		// Convert cOperationType to format accepted by self::FilterCreate
		switch ($operationCode)
		{
			case CTaskFilterCtrl::OP_EQUAL:
			case CTaskFilterCtrl::OP_DATE_TODAY:
			case CTaskFilterCtrl::OP_DATE_YESTERDAY:
			case CTaskFilterCtrl::OP_DATE_TOMORROW:
			case CTaskFilterCtrl::OP_DATE_CUR_WEEK:
			case CTaskFilterCtrl::OP_DATE_PREV_WEEK:
			case CTaskFilterCtrl::OP_DATE_NEXT_WEEK:
			case CTaskFilterCtrl::OP_DATE_CUR_MONTH:
			case CTaskFilterCtrl::OP_DATE_PREV_MONTH:
			case CTaskFilterCtrl::OP_DATE_NEXT_MONTH:
			case CTaskFilterCtrl::OP_DATE_NEXT_DAYS:
			case CTaskFilterCtrl::OP_DATE_LAST_DAYS:
				$cOperationType1 = '>=';
				$cOperationType2 = '<=';
				break;

			case CTaskFilterCtrl::OP_LESS_OR_EQUAL:
				$cOperationType1 = '<=';
				break;

			case CTaskFilterCtrl::OP_GREATER_OR_EQUAL:
				$cOperationType1 = '>=';
				break;

			case CTaskFilterCtrl::OP_NOT_EQUAL:
				$cOperationType1 = '<';
				$cOperationType2 = '>';
				break;

			case CTaskFilterCtrl::OP_STRICTLY_LESS:
				$cOperationType1 = '<';
				break;

			case CTaskFilterCtrl::OP_STRICTLY_GREATER:
				$cOperationType1 = '>';
				break;

			default:
				CTaskAssert::log(
					'Unknown operation code: '.
					$operationCode.
					'; $key = '.
					$key.
					'; it will be silently ignored, incorrect results expected',
					CTaskAssert::ELL_ERROR    // errors, incorrect results expected
				);

				return ($arSqlSearch);
				break;
		}

		// Convert/generate dates
		$ts1 = $ts2 = null;
		switch ($operationCode)
		{
			case CTaskFilterCtrl::OP_DATE_TODAY:
				$ts1 = $ts2 = $ts;
				break;

			case CTaskFilterCtrl::OP_DATE_YESTERDAY:
				$ts1 = $ts2 = $ts - 86400;
				break;

			case CTaskFilterCtrl::OP_DATE_TOMORROW:
				$ts1 = $ts2 = $ts + 86400;
				break;

			case CTaskFilterCtrl::OP_DATE_CUR_WEEK:
				$weekDay = date('N');    // numeric representation of the day of the week (1 to 7)
				$ts1 = $ts - ($weekDay - 1) * 86400;
				$ts2 = $ts + (7 - $weekDay) * 86400;
				break;

			case CTaskFilterCtrl::OP_DATE_PREV_WEEK:
				$weekDay = date('N');    // numeric representation of the day of the week (1 to 7)
				$ts1 = $ts - ($weekDay - 1 + 7) * 86400;
				$ts2 = $ts - $weekDay * 86400;
				break;

			case CTaskFilterCtrl::OP_DATE_NEXT_WEEK:
				$weekDay = date('N');    // numeric representation of the day of the week (1 to 7)
				$ts1 = $ts + (7 - $weekDay + 1) * 86400;
				$ts2 = $ts + (7 - $weekDay + 7) * 86400;
				break;

			case CTaskFilterCtrl::OP_DATE_CUR_MONTH:
				$ts1 = mktime(0, 0, 0, date('n', $ts), 1, date('Y', $ts));
				$ts2 = mktime(23, 59, 59, date('n', $ts) + 1, 0, date('Y', $ts));
				break;

			case CTaskFilterCtrl::OP_DATE_PREV_MONTH:
				$ts1 = mktime(0, 0, 0, date('n', $ts) - 1, 1, date('Y', $ts));
				$ts2 = mktime(23, 59, 59, date('n', $ts), 0, date('Y', $ts));
				break;

			case CTaskFilterCtrl::OP_DATE_NEXT_MONTH:
				$ts1 = mktime(0, 0, 0, date('n', $ts) + 1, 1, date('Y', $ts));
				$ts2 = mktime(23, 59, 59, date('n', $ts) + 2, 0, date('Y', $ts));
				break;

			case CTaskFilterCtrl::OP_DATE_LAST_DAYS:
				$ts1 = $ts - ((int)$val) * 86400; // val in days
				$ts2 = $ts;
				break;

			case CTaskFilterCtrl::OP_DATE_NEXT_DAYS:
				$ts1 = $ts;
				$ts2 = $ts + ((int)$val) * 86400; // val in days
				break;

			case CTaskFilterCtrl::OP_GREATER_OR_EQUAL:
			case CTaskFilterCtrl::OP_LESS_OR_EQUAL:
			case CTaskFilterCtrl::OP_STRICTLY_LESS:
			case CTaskFilterCtrl::OP_STRICTLY_GREATER:
				$ts1 = $val;
				break;

			case CTaskFilterCtrl::OP_EQUAL:
				$ts1 = mktime(0, 0, 0, date('n', $val), date('j', $val), date('Y', $val));
				$ts2 = mktime(23, 59, 59, date('n', $val), date('j', $val), date('Y', $val));
				break;

			case CTaskFilterCtrl::OP_NOT_EQUAL:
				$ts1 = mktime(0, 0, 0, date('n', $val), date('j', $val), date('Y', $val));
				$ts2 = mktime(23, 59, 59, date('n', $val), date('j', $val), date('Y', $val));
				break;

			default:
				CTaskAssert::log(
					'Unknown operation code: '.
					$operationCode.
					'; $key = '.
					$key.
					'; it will be silently ignored, incorrect results expected',
					CTaskAssert::ELL_ERROR    // errors, incorrect results expected
				);

				return ($arSqlSearch);
				break;
		}

		if ($ts1)
			$date1 = ConvertTimeStamp(mktime(0, 0, 0, date('n', $ts1), date('j', $ts1), date('Y', $ts1)), 'FULL');

		if ($ts2)
			$date2 = ConvertTimeStamp(mktime(23, 59, 59, date('n', $ts2), date('j', $ts2), date('Y', $ts2)), 'FULL');

		if (($cOperationType1 !== null) && ($date1 !== null))
		{
			$arrayKey = $cOperationType1.$fieldName;
			while (isset($arFilter[$arrayKey]))
			{
				$arrayKey = ' '.$arrayKey;
			}

			$arFilter[$arrayKey] = $date1;
		}

		if (($cOperationType2 !== null) && ($date2 !== null))
		{
			$arrayKey = $cOperationType2.$fieldName;
			while (isset($arFilter[$arrayKey]))
			{
				$arrayKey = ' '.$arrayKey;
			}

			$arFilter[$arrayKey] = $date2;
		}

		$arSqlSearch[] = self::GetSqlByFilter($arFilter, $userID, $sAliasPrefix, $bGetZombie);

		return ($arSqlSearch);
	}

	public static function GetFilteredKeys($arFilter)
	{
		$result = array();

		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $v)
			{
				// Skip meta-key
				if ($key === '::LOGIC')
					continue;

				// Skip markers
				if ($key === '::MARKERS')
					continue;

				// Subfilter?
				if (static::isSubFilterKey($key))
				{
					$result = array_merge($result, self::GetFilteredKeys($v));
					continue;
				}

				$res = CTasks::MkOperationFilter($key);

				if ((string)$res['FIELD'] != '')
				{
					$result[] = $res['FIELD'];
				}
			}
		}

		return array_unique($result);
	}

	public static function isSubFilterKey($key)
	{
		return is_numeric($key) || (substr((string)$key, 0, 12) === '::SUBFILTER-');
	}

	public static function GetFilter($arFilter, $sAliasPrefix = "", $arParams = false)
	{
		if (!is_array($arFilter))
		{
			$arFilter = array();
		}

		$arSqlSearch = array();

		if (is_array($arParams) && array_key_exists('USER_ID', $arParams) && ($arParams['USER_ID'] > 0))
		{
			$userID = (int)$arParams['USER_ID'];
		}
		else
		{
			$userID = User::getId();
		}

		$bGetZombie = false;
		if (isset($arParams['bGetZombie']))
		{
			$bGetZombie = (bool)$arParams['bGetZombie'];
		}

		// if TRUE will be generated constraint for members
		$bMembersTableJoined = false;
		if (isset($arParams['bMembersTableJoined']))
		{
			$bMembersTableJoined = (bool)$arParams['bMembersTableJoined'];
		}

		$sql = self::GetSqlByFilter($arFilter, $userID, $sAliasPrefix, $bGetZombie, $bMembersTableJoined, $arParams);
		if (strlen($sql))
		{
			$arSqlSearch[] = $sql;
		}

		// enable legacy access if no option passed (by default)
		// disable legacy access when ENABLE_LEGACY_ACCESS === true
		// we can not switch legacy access off by default, because getFilter() can be used separately
		$enableLegacyAccess = !is_array($arParams) || $arParams['ENABLE_LEGACY_ACCESS'] !== false;
		if ($enableLegacyAccess && static::needAccessRestriction($arFilter, $arParams))
		{
			list($arSubSqlSearch, $fields) = static::getPermissionFilterConditions(
				$arParams,
				array('ALIAS' => $sAliasPrefix)
			);

			if (!empty($arSubSqlSearch))
			{
				$arSqlSearch[] = " \n/*access LEGACY BEGIN*/\n (".
								 implode(" OR ", $arSubSqlSearch).
								 ") \n/*access LEGACY END*/\n";
			}
		}

		return $arSqlSearch;
	}

	private static function placeFieldSql($field, $behaviour, &$fields)
	{
		if ($behaviour['USE_PLACEHOLDERS'])
		{
			$fields[] = $field;

			return '%s';
		}

		return $behaviour['ALIAS'].'T.'.$field;
	}

	/**
	 * @param $arParams
	 * @param array $behaviour
	 *
	 * @return array
	 * @deprecated
	 */
	public static function getPermissionFilterConditions($arParams,
														 $behaviour = array('ALIAS' => '', 'USE_PLACEHOLDERS' => false))
	{
		if (!is_array($behaviour))
		{
			$behaviour = array();
		}
		if (!isset($behaviour['ALIAS']))
		{
			$behaviour['ALIAS'] = '';
		}
		if (!isset($behaviour['USE_PLACEHOLDERS']))
		{
			$behaviour['USE_PLACEHOLDERS'] = false;
		}

		$arSubSqlSearch = array();
		$fields = array();

		$a = $behaviour['ALIAS'];
		$b = $behaviour;
		$f =& $fields;

		if (!is_array($arParams))
		{
			$arParams = [];
		}

		if (array_key_exists('USER_ID', $arParams) && ($arParams['USER_ID'] > 0))
		{
			$userID = (int)$arParams['USER_ID'];
		}
		else
		{
			$userID = User::getId();
		}

		if (array_key_exists('TASK_MEMBER_JOINED', $arParams) && $arParams['TASK_MEMBER_JOINED'])
		{
			$taskMemberJoined = true;
		}
		else
		{
			$taskMemberJoined = false;
		}

		if (!User::isSuper($userID))
		{
			// subordinate check
			$arParams['FIELDS'] =& $fields;
			if ($strSql = CTasks::GetSubordinateSql($a, $arParams, $behaviour))
			{
				$arSubSqlSearch[] = "EXISTS(" . $strSql . ")";
			}

			// group permission check
			if ($arAllowedGroups = CTasks::GetAllowedGroups($arParams))
			{
				$arSubSqlSearch[] = "(" . static::placeFieldSql('GROUP_ID', $b, $f) . " IN (" . implode(",", $arAllowedGroups) . "))";
			}

			if (!$taskMemberJoined || ($taskMemberJoined && !empty($arSubSqlSearch)))
			{
				$arSubSqlSearch[] = static::placeFieldSql('CREATED_BY', $b, $f) . " = '" . $userID . "'";
				$arSubSqlSearch[] = static::placeFieldSql('RESPONSIBLE_ID', $b, $f) . " = '" . $userID . "'";
				$arSubSqlSearch[] =
					"EXISTS(
					SELECT 'x'
					FROM b_tasks_member " . $a . "TM
					WHERE
						" . $a . "TM.TASK_ID = " . static::placeFieldSql('ID', $b, $f) . " AND " . $a . "TM.USER_ID = '" . $userID . "'
					)";
			}
		}

		return array($arSubSqlSearch, $fields);
	}

	public static function MkOperationFilter($key)
	{
		static $arOperationsMap = null;    // will be loaded on demand

		$key = ltrim($key);

		$firstSymbol = substr($key, 0, 1);
		$twoSymbols = substr($key, 0, 2);

		if ($firstSymbol == "=") //Identical
		{
			$key = substr($key, 1);
			$cOperationType = "I";
		}
		elseif ($twoSymbols == "!=") //not Identical
		{
			$key = substr($key, 2);
			$cOperationType = "NI";
		}
		elseif ($firstSymbol == "%") //substring
		{
			$key = substr($key, 1);
			$cOperationType = "S";
		}
		elseif ($twoSymbols == "!%") //not substring
		{
			$key = substr($key, 2);
			$cOperationType = "NS";
		}
		elseif ($firstSymbol == "?") //logical
		{
			$key = substr($key, 1);
			$cOperationType = "?";
		}
		elseif ($twoSymbols == "><") //between
		{
			$key = substr($key, 2);
			$cOperationType = "B";
		}
		elseif ($twoSymbols == "*=") // identical full text match
		{
			$key = substr($key, 2);
			$cOperationType = "FTI";
		}
		elseif ($twoSymbols == "*%") // partial full text match based on LIKE
		{
			$key = substr($key, 2);
			$cOperationType = "FTL";
		}
		elseif ($firstSymbol == "*") // partial full text match
		{
			$key = substr($key, 1);
			$cOperationType = "FT";
		}
		elseif (substr($key, 0, 3) == "!><") //not between
		{
			$key = substr($key, 3);
			$cOperationType = "NB";
		}
		elseif ($twoSymbols == ">=") //greater or equal
		{
			$key = substr($key, 2);
			$cOperationType = "GE";
		}
		elseif ($firstSymbol == ">")  //greater
		{
			$key = substr($key, 1);
			$cOperationType = "G";
		}
		elseif ($twoSymbols == "<=")  //less or equal
		{
			$key = substr($key, 2);
			$cOperationType = "LE";
		}
		elseif ($firstSymbol == "<")  //less
		{
			$key = substr($key, 1);
			$cOperationType = "L";
		}
		elseif ($firstSymbol == "!") // not field LIKE val
		{
			$key = substr($key, 1);
			$cOperationType = "N";
		}
		elseif ($firstSymbol === '#')
		{
			// Preload and cache in static variable
			if ($arOperationsMap === null)
			{
				$arManifest = CTaskFilterCtrl::getManifest();
				$arOperationsMap = $arManifest['Operations map'];
			}

			// Resolve operation code and cutoff operation prefix from item name
			$operation = null;
			foreach ($arOperationsMap as $operationCode => $operationPrefix)
			{
				$pattern = '/^'.preg_quote($operationPrefix).'[A-Za-z]/';
				if (preg_match($pattern, $key))
				{
					$operation = $operationCode;
					$key = substr($key, strlen($operationPrefix));
					break;
				}
			}

			CTaskAssert::assert($operation !== null);

			$cOperationType = "#".$operation;
		}
		else
			$cOperationType = "E"; // field LIKE val

		return array("FIELD" => $key, "OPERATION" => $cOperationType);
	}

	public static function FilterCreate($fname, $vals, $type, &$bFullJoin, $cOperationType = false, $bSkipEmpty = true)
	{
		global $DB;
		if (!is_array($vals))
		{
			$vals = array($vals);
		}
		else
		{
			$vals = array_unique(array_values($vals));
		}

		if (count($vals) < 1)
			return "";

		if (is_bool($cOperationType))
		{
			if ($cOperationType === true)
				$cOperationType = "N";
			else
				$cOperationType = "E";
		}

		if ($cOperationType == "G")
			$strOperation = ">";
		elseif ($cOperationType == "GE")
			$strOperation = ">=";
		elseif ($cOperationType == "LE")
			$strOperation = "<=";
		elseif ($cOperationType == "L")
			$strOperation = "<";
		elseif ($cOperationType === "NI")
			$strOperation = "!=";
		else
			$strOperation = "=";

		$bFullJoin = false;
		$bWasLeftJoin = false;

		// special case for array of number
		if ($type === 'number' && is_array($vals) && count($vals) > 1 && count($vals) < 80)
		{
			$vals = implode(', ', array_unique(array_map('intval', $vals)));

			$res = $fname.' '.($cOperationType == 'N' ? 'not' : '').' in ('.$vals.')';

			// INNER JOIN in this case
			if ($cOperationType != "N")
				$bFullJoin = true;

			return $res;
		}

		$res = array();

		foreach ($vals as $key => $val)
		{
			if (($type === 'number') && !$val)
				$val = 0;

			if (!$bSkipEmpty || strlen($val) > 0 || (is_bool($val) && $val === false))
			{
				switch ($type)
				{
					case "string_equal":
						if (strlen($val) <= 0)
							$res[] = ($cOperationType == "N" ? "NOT" : "").
									 "(".
									 $fname.
									 " IS NULL OR ".
									 $DB->Length($fname).
									 "<=0)";
						else
							$res[] = "(".
									 ($cOperationType == "N" ? " ".$fname." IS NULL OR NOT (" : "").
									 $fname.
									 $strOperation.
									 "'".
									 $DB->ForSql($val).
									 "'".
									 ($cOperationType == "N" ? ")" : "").
									 ")";
						break;

					case "string":
						if ($cOperationType == "?")
						{
							if (strlen($val) > 0)
								$res[] = GetFilterQuery($fname, $val, "Y", array(), "N");
						}
						elseif ($cOperationType == "S")
						{
							$res[] = "(UPPER(".$fname.") LIKE UPPER('%".$DB->ForSqlLike($val)."%'))";
						}
						elseif ($cOperationType == "NS")
						{
							$res[] = "(UPPER(".$fname.") NOT LIKE UPPER('%".$DB->ForSqlLike($val)."%'))";
						}
						elseif ($cOperationType == "FTL")
						{
							$sqlWhere = new CSQLWhere();
							$res[] = $sqlWhere->matchLike($fname, $val);
						}
						elseif (strlen($val) <= 0)
						{
							$res[] = ($cOperationType == "N" ? "NOT" : "").
									 "(".
									 $fname.
									 " IS NULL OR ".
									 $DB->Length($fname).
									 "<=0)";
						}
						else
						{
							if ($strOperation == "=")
								$res[] = "(".
										 ($cOperationType == "N" ? " ".$fname." IS NULL OR NOT (" : "").
										 (strtoupper($DB->type) == "ORACLE"
											 ? $fname." LIKE "."'".$DB->ForSqlLike($val)."'"." ESCAPE '\\'" : $fname.
																											  " ".
																											  ($strOperation ==
																											   "="
																												  ? "LIKE"
																												  : $strOperation).
																											  " '".
																											  $DB->ForSqlLike(
																												  $val
																											  ).
																											  "'").
										 ($cOperationType == "N" ? ")" : "").
										 ")";
							else
								$res[] = "(".
										 ($cOperationType == "N" ? " ".$fname." IS NULL OR NOT (" : "").
										 (strtoupper($DB->type) == "ORACLE"
											 ? $fname.
											   " ".
											   $strOperation.
											   " ".
											   "'".
											   $DB->ForSql($val).
											   "'".
											   " "
											 : $fname.
											   " ".
											   $strOperation.
											   " '".
											   $DB->ForSql($val).
											   "'").
										 ($cOperationType == "N" ? ")" : "").
										 ")";
						}
						break;
					case "fulltext":
						echo '';
						if ($cOperationType == "FT" || $cOperationType == "FTI")
						{
							$sqlWhere = new CSQLWhere();
							$res[] = $sqlWhere->match($fname, $val, $cOperationType == "FT");
						}
						elseif ($cOperationType == "FTL")
						{
							$sqlWhere = new CSQLWhere();
							$res[] = $sqlWhere->matchLike($fname, $val);
						}
						elseif ($cOperationType == "?")
						{
							if (strlen($val) > 0)
							{
								$sr = GetFilterQuery(
									$fname,
									$val,
									"Y",
									array(),
									($fname == "BE.SEARCHABLE_CONTENT" || $fname == "BE.DETAIL_TEXT" ? "Y" : "N")
								);
								if ($sr != "0")
									$res[] = $sr;
							}
						}
						elseif (($cOperationType == "B" || $cOperationType == "NB") &&
								is_array($val) &&
								count($val) == 2)
						{
							$res[] = ($cOperationType == "NB" ? " ".$fname." IS NULL OR NOT " : "").
									 "(".
									 CIBlock::_Upper($fname).
									 " ".
									 $strOperation[0].
									 " '".
									 CIBlock::_Upper($DB->ForSql($val[0])).
									 "' ".
									 $strOperation[1].
									 " '".
									 CIBlock::_Upper($DB->ForSql($val[1])).
									 "')";
						}
						elseif ($cOperationType == "S" || $cOperationType == "NS")
							$res[] = ($cOperationType == "NS" ? " ".$fname." IS NULL OR NOT " : "").
									 "(".
									 CIBlock::_Upper($fname).
									 " LIKE ".
									 CIBlock::_Upper("'%".CIBlock::ForLIKE($val)."%'").
									 ")";
						else
						{
							if (strlen($val) <= 0)
								$res[] = ($bNegative ? "NOT" : "")."(".$fname." IS NULL OR ".$DB->Length($fname)."<=0)";
							else if ($strOperation == "=" && $cOperationType != "I" && $cOperationType != "NI")
								$res[] = ($cOperationType == "N" ? " ".$fname." IS NULL OR NOT " : "").
										 "(".
										 ($DB->type == "ORACLE"
											 ? CIBlock::_Upper($fname).
											   " LIKE ".
											   CIBlock::_Upper("'".$DB->ForSqlLike($val)."'").
											   " ESCAPE '\\'"
											 : $fname.
											   " LIKE '".
											   $DB->ForSqlLike($val).
											   "'").
										 ")";
							else
								$res[] = ($bNegative ? " ".$fname." IS NULL OR NOT " : "").
										 "(".
										 ($DB->type == "ORACLE"
											 ? CIBlock::_Upper($fname).
											   " ".
											   $strOperation.
											   " ".
											   CIBlock::_Upper("'".$DB->ForSql($val)."'").
											   " "
											 : $fname.
											   " ".
											   $strOperation.
											   " '".
											   $DB->ForSql($val).
											   "'").
										 ")";
						}
						break;
					case "date":
						if (strlen($val) <= 0)
							$res[] = ($cOperationType == "N" ? "NOT" : "")."(".$fname." IS NULL)";
						else
							$res[] = "(".
									 ($cOperationType == "N" ? " ".$fname." IS NULL OR NOT (" : "").
									 $fname.
									 " ".
									 $strOperation.
									 " ".
									 $val.
									 "".
									 ($cOperationType == "N" ? ")" : "").
									 ")";
						break;

					case "number":

						if (($vals[$key] === false) || (strlen($val) <= 0))
							$res[] = ($cOperationType == "N" ? "NOT" : "")."(".$fname." IS NULL)";
						else
							$res[] = "(".
									 ($cOperationType == "N" ? " ".$fname." IS NULL OR NOT (" : "").
									 $fname.
									 " ".
									 $strOperation.
									 " '".
									 DoubleVal($val).
									 ($cOperationType == "N" ? "')" : "'").
									 ")";
						break;

					case "number_wo_nulls":
						$res[] = "(".
								 ($cOperationType == "N" ? "NOT (" : "").
								 $fname.
								 " ".
								 $strOperation.
								 " ".
								 DoubleVal($val).
								 ($cOperationType == "N" ? ")" : "").
								 ")";
						break;

					case "null_or_zero":
						if ($cOperationType == "N")
							$res[] = "((".$fname." IS NOT NULL) AND (".$fname." != 0))";
						else
							$res[] = "((".$fname." IS NULL) OR (".$fname." = 0))";

						break;

					case "left_existence":

						if ($strOperation != '=')
						{
							CTaskAssert::logError('Operation type not supported for '.$fname.': '.$strOperation);
						}
						elseif ($val != 'Y' && $val != 'N' && 0)
						{
							CTaskAssert::logError('Filter value not supported for '.$fname.': '.$val);
						}
						else
						{
							$otNot = $cOperationType == "N";

							if (($val == 'Y' && !$otNot) || ($val == 'N' && $otNot))
								$res[] = "(".$fname." IS NOT NULL)";
							else
								$res[] = "(".$fname." IS NULL)";
						}

						break;

					case 'reference':

						$val = trim($val);

						if (preg_match('#^[a-z0-9_]+(\.{1}[a-z0-9_]+)*$#i', $val))
						{
							if ($cOperationType === 'E')
								$res[] = '('.$fname.' = '.$DB->ForSql($val).')';
							elseif ($cOperationType === 'N')
								$res[] = '('.$fname.' != '.$DB->ForSql($val).')';
							elseif ($cOperationType === 'L')
								$res[] = '('.$fname.' < '.$DB->ForSql($val).')';
							elseif ($cOperationType === 'G')
								$res[] = '('.$fname.' > '.$DB->ForSql($val).')';
							else
								CTaskAssert::logError('[0xcf017223] Operation type not supported: '.$cOperationType);
						}
						else
						{
							CTaskAssert::logError("Bad reference: ".$fname." => '".$val."'");
						}

						break;
				}

				// INNER JOIN in this case
				if (strlen($val) > 0 && $cOperationType != "N")
					$bFullJoin = true;
				else
					$bWasLeftJoin = true;
			}
		}

		$strResult = "";
		for ($i = 0, $resCnt = count($res); $i < $resCnt; $i++)
		{
			if ($i > 0)
				$strResult .= ($cOperationType == "N" ? " AND " : " OR ");
			$strResult .= $res[$i];
		}

		if (count($res) > 1)
			$strResult = "(".$strResult.")";

		if ($bFullJoin && $bWasLeftJoin && $cOperationType != "N")
			$bFullJoin = false;

		return $strResult;
	}

	/**
	 * This method is deprecated. Use CTaskItem class instead.
	 * @deprecated
	 */
	public static function GetByID($ID, $bCheckPermissions = true, $arParams = array())
	{
		$bReturnAsArray = false;
		$bSkipExtraData = false;
		$arGetListParams = array();

		if (isset($arParams['returnAsArray']))
			$bReturnAsArray = ($arParams['returnAsArray'] === true);

		if (isset($arParams['bSkipExtraData']))
			$bSkipExtraData = ($arParams['bSkipExtraData'] === true);

		if (isset($arParams['USER_ID']))
			$arGetListParams['USER_ID'] = $arParams['USER_ID'];

		$arFilter = array("ID" => (int)$ID);

		if (!$bCheckPermissions)
			$arFilter["CHECK_PERMISSIONS"] = "N";

		$select = ['*', 'UF_*'];
		if (array_key_exists('select', $arParams))
		{
			$select = $arParams['select'];
		}

		$select = array_unique(array_merge(['ID'], $select));

		$res = CTasks::GetList(array(), $arFilter, $select, $arGetListParams);
		if ($res && ($task = $res->Fetch()))
		{
			if (in_array('AUDITORS', $select) || in_array('ACCOMPLICES', $select) || in_array('*', $select))
			{
				$task["ACCOMPLICES"] = $task["AUDITORS"] = [];
				$rsMembers = CTaskMembers::GetList(array(), array("TASK_ID" => $ID));
				while ($arMember = $rsMembers->Fetch())
				{
					if ($arMember["TYPE"] == "A" && (in_array('ACCOMPLICES', $select) || in_array('*', $select)))
					{
						$task["ACCOMPLICES"][] = $arMember["USER_ID"];
					}
					elseif ($arMember["TYPE"] == "U" && (in_array('AUDITORS', $select) || in_array('*', $select)))
					{
						$task["AUDITORS"][] = $arMember["USER_ID"];
					}
				}
			}

			if (!$bSkipExtraData)
			{
				if (in_array('TAGS', $select) || in_array('*', $select))
				{
					$arTagsFilter = array("TASK_ID" => $ID);
					$arTagsOrder = array("NAME" => "ASC");
					$rsTags = CTaskTags::GetList($arTagsOrder, $arTagsFilter);
					$task["TAGS"] = array();
					while ($arTag = $rsTags->Fetch())
					{
						$task["TAGS"][] = $arTag["NAME"];
					}
				}

				if (in_array('CHECKLIST', $select) || in_array('*', $select))
				{
					$task["CHECKLIST"] = TaskCheckListFacade::getByEntityId($ID);
				}

				if (in_array('FILES', $select) || in_array('*', $select))
				{
					$rsFiles = CTaskFiles::GetList(array(), array("TASK_ID" => $ID));
					$task["FILES"] = array();
					while ($arFile = $rsFiles->Fetch())
					{
						$task["FILES"][] = $arFile["FILE_ID"];
					}
				}

				if (in_array('DEPENDS_ON', $select) || in_array('*', $select))
				{
					$rsDependsOn = CTaskDependence::GetList(array(), array("TASK_ID" => $ID));
					$task["DEPENDS_ON"] = array();
					while ($arDependsOn = $rsDependsOn->Fetch())
					{
						$task["DEPENDS_ON"][] = $arDependsOn["DEPENDS_ON_ID"];
					}
				}
			}

			if ($bReturnAsArray)
				return ($task);
			else
			{
				$rsTask = new CDBResult;
				$rsTask->InitFromarray(array($task));

				return $rsTask;
			}
		}
		else
		{
			if ($bReturnAsArray)
				return (false);
			else
				return $res;
		}
	}

	/**
	 * @param null $userID
	 *
	 * @return array
	 * @deprecated
	 */
	public static function GetSubordinateDeps($userID = null)
	{
		return Integration\Intranet\Department::getSubordinateIds($userID, true);
	}

	/**
	 * @param array $arParams
	 *
	 * @return mixed
	 * @deprecated
	 * @see Integration\SocialNetwork\Group::getIdsByAllowedAction
	 */
	public static function GetAllowedGroups($arParams = array())
	{
		global $DB;
		static $ALLOWED_GROUPS = array();

		$userId = null;

		if (is_array($arParams) && isset($arParams['USER_ID']))
			$userId = $arParams['USER_ID'];
		else
		{
			$userId = User::getId();
		}

		if (!($userId >= 1))
			$userId = 0;

		$bGetZombie = false;
		if (isset($arParams['bGetZombie']))
			$bGetZombie = (bool)$arParams['bGetZombie'];

		if (!isset($ALLOWED_GROUPS[$userId]) && CModule::IncludeModule("socialnetwork"))
		{
			// bottleneck
			$strSql = "SELECT DISTINCT(T.GROUP_ID) FROM b_tasks T WHERE T.GROUP_ID IS NOT NULL";
			if (!$bGetZombie)
				$strSql .= " AND T.ZOMBIE = 'N'";

			$rsGroups = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$ALLOWED_GROUPS[$userId] = $arGroupsWithTasks = array();
			while ($arGroup = $rsGroups->Fetch())
			{
				$arGroupsWithTasks[] = $arGroup["GROUP_ID"];
			}
			if (is_array($arGroupsWithTasks) && sizeof($arGroupsWithTasks))
			{
				if ($userId === 0)
					$featurePerms = CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
						SONET_ENTITY_GROUP,
						$arGroupsWithTasks,
						"tasks",
						"view_all"
					);
				else
					$featurePerms = CSocNetFeaturesPerms::CanPerformOperation(
						$userId,
						SONET_ENTITY_GROUP,
						$arGroupsWithTasks,
						"tasks",
						"view_all"
					);

				if (is_array($featurePerms))
				{
					$ALLOWED_GROUPS[$userId] = array_keys(array_filter($featurePerms));
				}
			}
		}

		return $ALLOWED_GROUPS[$userId];
	}

	public static function GetDepartmentManagers($arDepartments, $skipUserId = false, $arSelectFields = array('ID'))
	{
		global $CACHE_MANAGER;

		if ((!is_array($arDepartments)) || empty($arDepartments) || (!is_array($arSelectFields)))
		{
			return false;
		}

		// We need ID in any case
		if (!in_array('ID', $arSelectFields))
			$arSelectFields[] = 'ID';

		$arManagers = array();
		$obCache = new CPHPCache();
		$lifeTime = CTasksTools::CACHE_TTL_UNLIM;
		$cacheDir = "/tasks/subordinatedeps";
		$cacheFPrint = sha1(
			serialize($arDepartments).'|'.serialize($arSelectFields)
		);
		if ($obCache->InitCache($lifeTime, $cacheFPrint, $cacheDir))
		{
			$arManagers = $obCache->GetVars();
		}
		elseif ($obCache->StartDataCache())
		{
			$IBlockID = COption::GetOptionInt('intranet', 'iblock_structure', 0);

			$CACHE_MANAGER->StartTagCache($cacheDir);
			$CACHE_MANAGER->RegisterTag("iblock_id_".$IBlockID);

			$arUserIDs = self::GetDepartmentManagersIDs($arDepartments, $IBlockID);

			if (count($arUserIDs) > 0)
			{
				$arFilter = array(
					'ID' => implode('|', $arUserIDs)
				);

				// Prevent using users, that doesn't activate it's account
				// http://jabber.bx/view.php?id=29118
				if (IsModuleInstalled('bitrix24'))
					$arFilter['!LAST_LOGIN'] = false;

				$dbUser = CUser::GetList(
					$by = 'ID',
					$order = 'ASC',
					$arFilter,
					array('FIELDS' => $arSelectFields)    // selects only $arSelectFields fields
				);
				while ($arUser = $dbUser->GetNext())
				{
					$arManagers[(int)$arUser["ID"]] = $arUser;
				}
			}

			$CACHE_MANAGER->EndTagCache();
			$obCache->EndDataCache($arManagers);
		}

		// remove user to be skipped
		if (($skipUserId !== false) && (isset($arManagers[(int)$skipUserId])))
		{
			unset ($arManagers[(int)$skipUserId]);
		}

		return $arManagers;
	}

	protected static function GetDepartmentManagersIDs($arDepartments, $IBlockID)
	{
		if (!CModule::IncludeModule('iblock'))
		{
			return array();
		}

		$dbSections = CIBlockSection::GetList(
			array('SORT' => 'ASC'),
			array(
				'ID'                => $arDepartments,
				'IBLOCK_ID'         => $IBlockID,
				'CHECK_PERMISSIONS' => 'N'
			),
			false,                                // don't count
			array(
				'ID',
				'UF_HEAD',
				'IBLOCK_SECTION_ID'
			)
		);

		$arUserIDs = array();
		while ($arSection = $dbSections->Fetch())
		{
			if ($arSection['UF_HEAD'] > 0)
				$arUserIDs[] = $arSection['UF_HEAD'];

			if ($arSection['IBLOCK_SECTION_ID'] > 0)
			{
				$arUserIDs = array_merge(
					$arUserIDs,
					self::GetDepartmentManagersIDs(array($arSection['IBLOCK_SECTION_ID']), $IBlockID)
				);
			}
		}

		return $arUserIDs;
	}

	/**
	 * @param $employeeID1
	 * @param $employeeID2
	 *
	 * @return bool true if $employeeID2 is manager of $employeeID1
	 */
	public static function IsSubordinate($employeeID1, $employeeID2)
	{
		if ($employeeID1 == $employeeID2)
		{
			return false;
		}

		$dbRes = CUser::GetList(
			$by = 'ID',
			$order = 'ASC',
			array('ID' => $employeeID1),
			array('SELECT' => array('UF_DEPARTMENT'))
		);

		if (($arRes = $dbRes->Fetch()) && is_array($arRes['UF_DEPARTMENT']) && (count($arRes['UF_DEPARTMENT']) > 0))
		{
			$arManagers = array_keys(CTasks::GetDepartmentManagers($arRes['UF_DEPARTMENT'], $employeeID1));

			if (in_array($employeeID2, $arManagers))
				return true;
		}

		return false;
	}

	public static function getSelectSqlByFilter(array $filter = array(), $alias = '', array $filterParams = array())
	{
		$userId = intval($filterParams['USER_ID']);

		$obUserFieldsSql = new CUserTypeSQL();
		$obUserFieldsSql->SetEntity("TASKS_TASK", $alias . "T.ID");
		$obUserFieldsSql->SetFilter($filter);

		if (isset($filter['::LOGIC']))
		{
			CTaskAssert::assert($filter['::LOGIC'] === 'AND');
		}

		$optimized = static::tryOptimizeFilter($filter, $alias . "T", $alias . "TM_SPEC");
		$sqlSearch = CTasks::GetFilter($optimized['FILTER'], $alias, $filterParams);

		$r = $obUserFieldsSql->GetFilter();
		if (strlen($r) > 0)
		{
			$sqlSearch[] = "(" . $r . ")";
		}

		$params = [
			'USER_ID' => $userId,
			'JOIN_ALIAS' => $alias,
			'SOURCE_ALIAS' => $alias . "T"
		];
		$relatedJoins = static::getRelatedJoins([], $filter, [], $params);
		$relatedJoins = array_merge($relatedJoins, $optimized['JOINS']);

		$needGroup = isset($relatedJoins['FULL_SEARCH']) || isset($relatedJoins['COMMENT_SEARCH']);

		$sql = "
			SELECT {$alias}T.ID
			FROM b_tasks {$alias}T
			INNER JOIN b_user {$alias}CU ON {$alias}CU.ID = {$alias}T.CREATED_BY
			INNER JOIN b_user {$alias}RU ON {$alias}RU.ID = {$alias}T.RESPONSIBLE_ID
			" . implode("\n", $relatedJoins) . "
			" . $obUserFieldsSql->GetJoin($alias."T.ID") . "
			" . (sizeof($sqlSearch)? " WHERE " . implode(" AND ", $sqlSearch) : "") . "
			" . ($needGroup? "GROUP BY {$alias}T.ID" : ""). "
		";

		return $sql;
	}

	/**
	 * Get tasks fields info (for rest, etc)
	 *
	 * @return array
	 */
	public static function getFieldsInfo()
	{
		global $USER_FIELD_MANAGER;

		$fields = [
			"ID"          => [
				'type'    => 'integer',
				'primary' => true
			],
			"PARENT_ID"   => [
				'type'    => 'integer',
				'default' => 0
			],
			"TITLE"       => [
				'type'     => 'string',
				'required' => true
			],
			"DESCRIPTION" => [
				'type' => 'string',
			],
			"MARK"        => [
				'type'    => 'enum',
				'values'  => [
					self::MARK_NEGATIVE => Loc::getMessage('TASKS_FIELDS_MARK_NEGATIVE'),
					self::MARK_POSITIVE => Loc::getMessage('TASKS_FIELDS_MARK_POSITIVE')
				],
				'default' => null
			],
			"PRIORITY"    => [
				'type'    => 'enum',
				'values'  => [
					self::PRIORITY_HIGH    => Loc::getMessage('TASKS_FIELDS_PRIORITY_HIGH'),
					self::PRIORITY_AVERAGE => Loc::getMessage('TASKS_FIELDS_PRIORITY_AVERAGE'),
					self::PRIORITY_LOW     => Loc::getMessage('TASKS_FIELDS_PRIORITY_LOW')
				],
				'default' => self::PRIORITY_AVERAGE
			],
			"STATUS"      => [
				'type'    => 'enum',
				'values'  => [
					self::STATE_PENDING              => Loc::getMessage('TASKS_FIELDS_STATUS_PENDING'),
					self::STATE_IN_PROGRESS          => Loc::getMessage('TASKS_FIELDS_STATUS_IN_PROGRESS'),
					self::STATE_SUPPOSEDLY_COMPLETED => Loc::getMessage('TASKS_FIELDS_STATUS_SUPPOSEDLY_COMPLETED'),
					self::STATE_COMPLETED            => Loc::getMessage('TASKS_FIELDS_STATUS_COMPLETED'),
					self::STATE_DEFERRED             => Loc::getMessage('TASKS_FIELDS_STATUS_DEFERRED')
				],
				'default' => self::STATE_PENDING
			],

			"MULTITASK" => [
				'type'    => 'enum',
				'values'  => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N'
			],
			"NOT_VIEWED" => [
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
			"CREATED_DATE"        => [
				'type' => 'datetime'
			],
			"RESPONSIBLE_ID" => [
				'type'     => 'integer',
				'required' => true
			],
			"ACCOMPLICES" => [
				'type'     => 'array'
			],
			"AUDITORS" => [
				'type'     => 'array'
			],

			"CHANGED_BY"          => [
				'type' => 'integer',
			],
			"CHANGED_DATE"        => [
				'type' => 'datetime'
			],
			"STATUS_CHANGED_BY"   => [
				'type' => 'integer',
			],
			"STATUS_CHANGED_DATE" => [
				'type' => 'datetime'
			],

			"CLOSED_BY"   => [
				'type'    => 'integer',
				'default' => null
			],
			"CLOSED_DATE" => [
				'type'    => 'datetime',
				'default' => null
			],

			"DATE_START" => [
				'type'    => 'datetime',
				'default' => null
			],
			"DEADLINE"   => [
				'type'    => 'datetime',
				'default' => null
			],

			"START_DATE_PLAN" => [
				'type'    => 'datetime',
				'default' => null
			],
			"END_DATE_PLAN"   => [
				'type'    => 'datetime',
				'default' => null
			],

			'GUID'   => [
				'type'    => 'string',
				'default' => null
			],
			"XML_ID" => [
				'type'    => 'string',
				'default' => null
			],

			"COMMENTS_COUNT" => [
				'type'    => 'integer',
				'default' => 0
			],
			"NEW_COMMENTS_COUNT" => [
				'type'    => 'integer',
				'default' => 0
			],

			"ALLOW_CHANGE_DEADLINE" => [
				'type'    => 'enum',
				'values'  => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N',
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
			"FORKED_BY_TEMPLATE_ID" => [
				'type'    => 'enum',
				'values'  => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => 'N'
			],

			"TIME_ESTIMATE" => [
				'type' => 'integer',
			],

			"TIME_SPENT_IN_LOGS" => [
				'type' => 'integer',
			],
			"MATCH_WORK_TIME"    => [
				'type' => 'integer',
			],

			"FORUM_TOPIC_ID" => [
				'type' => 'integer',
			],
			"FORUM_ID"       => [
				'type' => 'integer',
			],
			"SITE_ID"        => [
				'type' => 'string',
			],
			"SUBORDINATE"    => [
				'type'    => 'enum',
				'values'  => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => null
			],
			"FAVORITE"    => [
				'type'    => 'enum',
				'values'  => [
					'Y' => Loc::getMessage('TASKS_FIELDS_Y'),
					'N' => Loc::getMessage('TASKS_FIELDS_N'),
				],
				'default' => null
			],

			"EXCHANGE_MODIFIED" => [
				'type'    => 'datetime',
				'default' => null
			],
			"EXCHANGE_ID"       => [
				'type'    => 'integer',
				'default' => null
			],
			"OUTLOOK_VERSION"   => [
				'type'    => 'integer',
				'default' => null
			],

			"VIEWED_DATE" => [
				'type' => 'datetime'
			],

			"SORTING" => [
				'type' => 'double',
			],

			"DURATION_PLAN" => [
				'type' => 'integer',
			],
			"DURATION_FACT" => [
				'type' => 'integer',
			],
			"CHECKLIST" => [
				'type' => 'array',
			],
			"DURATION_TYPE" => [
				'type'    => 'enum',
				'values'  => [
					'secs',
					'mins',
					'hours',
					'days',
					'weeks',
					'monts',
					'years'
				],
				'default' => 'days'
			]
		];

		foreach ($fields as $fieldId => &$fieldData)
		{
			$fieldData = array_merge(['title' => Loc::getMessage('TASKS_FIELDS_'.$fieldId)], $fieldData);
		}
		unset($fieldData);

		$uf = $USER_FIELD_MANAGER->GetUserFields("TASKS_TASK");
		foreach ($uf as $key=>$item)
		{
			$fields[$key]=[
				'title'=>$item['USER_TYPE']['DESCRIPTION'],
				'type'=>$item['USER_TYPE_ID']
			];
		}

		return $fields;
	}

	/**
	 * @param array $arOrder
	 * @param array $arFilter
	 * @param array $arSelect
	 * @param array $arParams
	 * @param array $arGroup
	 * @return bool|CDBResult
	 * @throws TasksException
	 */
	public static function GetList($arOrder=array(), $arFilter=array(), $arSelect = array(), $arParams = array(), array $arGroup = array())
	{
		global $DB, $USER_FIELD_MANAGER;

		$bIgnoreErrors = false;
		$nPageTop = false;
		$bGetZombie = false;
		$deleteMessageId = false;

		if ( ! is_array($arParams) )
		{
			$nPageTop = $arParams;
			$arParams = false;
		}
		else
		{
			if (isset($arParams['nPageTop']))
				$nPageTop = $arParams['nPageTop'];

			if (isset($arParams['bIgnoreErrors']))
				$bIgnoreErrors = (bool) $arParams['bIgnoreErrors'];

			if (isset($arParams['bGetZombie']))
				$bGetZombie = (bool) $arParams['bGetZombie'];
		}

		$obUserFieldsSql = new CUserTypeSQL();
		$obUserFieldsSql->SetEntity("TASKS_TASK", "T.ID");
		$obUserFieldsSql->SetSelect($arSelect);
		$obUserFieldsSql->SetFilter($arFilter);
		$obUserFieldsSql->SetOrder($arOrder);

		if (is_array($arParams) && array_key_exists('USER_ID', $arParams) && ($arParams['USER_ID'] > 0))
		{
			$userID = (int) $arParams['USER_ID'];
		}
		else
		{
			$userID = User::getId();
		}

		$arFields = array(
			"ID" => "T.ID",
			"TITLE" => "T.TITLE",
			"DESCRIPTION" => "T.DESCRIPTION",
			"DESCRIPTION_IN_BBCODE" => "T.DESCRIPTION_IN_BBCODE",
			"DECLINE_REASON" => "T.DECLINE_REASON",
			"PRIORITY" => "T.PRIORITY",
			// 1) deadline in past, real status is not STATE_SUPPOSEDLY_COMPLETED and not STATE_COMPLETED and (not STATE_DECLINED or responsible is not me (user))
			// 2) viewed by noone(?) and created not by me (user) and (STATE_NEW or STATE_PENDING)
			"STATUS" => "
				CASE
					WHEN
						T.DEADLINE < DATE_ADD(".$DB->CurrentTimeFunction().", INTERVAL ".
						Counter::getDeadlineTimeLimit()." SECOND)
						AND T.DEADLINE >= ".$DB->CurrentTimeFunction()."
						AND T.STATUS != '4'
						AND T.STATUS != '5'
						AND (
							T.STATUS != '7'
							OR T.RESPONSIBLE_ID != ".intval($userID)."
						)
					THEN
						'-3'
					WHEN
						T.DEADLINE < ".$DB->CurrentTimeFunction()." AND T.STATUS != '4' AND T.STATUS != '5' AND (T.STATUS != '7' OR T.RESPONSIBLE_ID != ".intval($userID).")
					THEN
						'-1'
					WHEN
						TV.USER_ID IS NULL
						AND
						T.CREATED_BY != ".intval($userID)."
						AND
						(T.STATUS = 1 OR T.STATUS = 2)
					THEN
						'-2'
					ELSE
						T.STATUS
				END
			",
			"NOT_VIEWED" => "
				CASE
					WHEN
						TV.USER_ID IS NULL
						AND
						T.CREATED_BY != ".intval($userID)."
						AND
						(T.STATUS = 1 OR T.STATUS = 2)
					THEN
						'Y'
					ELSE
						'N'
				END
			",
			// used in ORDER BY to make completed tasks go after (or before) all other tasks
			"STATUS_COMPLETE" => "
				CASE
					WHEN
						T.STATUS = '5'
					THEN
						'2'
					ELSE
						'1'
					END
			",
			"REAL_STATUS" => "T.STATUS",
			"MULTITASK" => "T.MULTITASK",
			"STAGE_ID" => "T.STAGE_ID",
			"RESPONSIBLE_ID" => "T.RESPONSIBLE_ID",
			"RESPONSIBLE_NAME" => "RU.NAME",
			"RESPONSIBLE_LAST_NAME" => "RU.LAST_NAME",
			"RESPONSIBLE_SECOND_NAME" => "RU.SECOND_NAME",
			"RESPONSIBLE_LOGIN" => "RU.LOGIN",
			"RESPONSIBLE_WORK_POSITION" => "RU.WORK_POSITION",
			"RESPONSIBLE_PHOTO" => "RU.PERSONAL_PHOTO",
			"DATE_START" => $DB->DateToCharFunction("T.DATE_START", "FULL"),
			"DURATION_FACT" => "(SELECT SUM(TE.MINUTES) FROM b_tasks_elapsed_time TE WHERE TE.TASK_ID = T.ID GROUP BY TE.TASK_ID)",
			"TIME_ESTIMATE" => "T.TIME_ESTIMATE",
			"TIME_SPENT_IN_LOGS" => "(SELECT SUM(TE.SECONDS) FROM b_tasks_elapsed_time TE WHERE TE.TASK_ID = T.ID GROUP BY TE.TASK_ID)",
			"REPLICATE" => "T.REPLICATE",
			"DEADLINE" => $DB->DateToCharFunction("T.DEADLINE", "FULL"),
			"DEADLINE_ORIG" => "T.DEADLINE",
			"START_DATE_PLAN" => $DB->DateToCharFunction("T.START_DATE_PLAN", "FULL"),
			"END_DATE_PLAN" => $DB->DateToCharFunction("T.END_DATE_PLAN", "FULL"),
			"CREATED_BY" => "T.CREATED_BY",
			"CREATED_BY_NAME" => "CU.NAME",
			"CREATED_BY_LAST_NAME" => "CU.LAST_NAME",
			"CREATED_BY_SECOND_NAME" => "CU.SECOND_NAME",
			"CREATED_BY_LOGIN" => "CU.LOGIN",
			"CREATED_BY_WORK_POSITION" => "CU.WORK_POSITION",
			"CREATED_BY_PHOTO" => "CU.PERSONAL_PHOTO",
			"CREATED_DATE" => $DB->DateToCharFunction("T.CREATED_DATE", "FULL"),
			"CHANGED_BY" => "T.CHANGED_BY",
			"CHANGED_DATE" => $DB->DateToCharFunction("T.CHANGED_DATE", "FULL"),
			"STATUS_CHANGED_BY" => "T.CHANGED_BY",
			"STATUS_CHANGED_DATE" =>
				'CASE WHEN T.STATUS_CHANGED_DATE IS NULL THEN '
				. $DB->DateToCharFunction("T.CHANGED_DATE", "FULL")
				. ' ELSE '
				. $DB->DateToCharFunction("T.STATUS_CHANGED_DATE", "FULL")
				. ' END ',
			"CLOSED_BY" => "T.CLOSED_BY",
			"CLOSED_DATE" => $DB->DateToCharFunction("T.CLOSED_DATE", "FULL"),
			'GUID' => 'T.GUID',
			"XML_ID" => "T.XML_ID",
			"MARK" => "T.MARK",
			"ALLOW_CHANGE_DEADLINE" => "T.ALLOW_CHANGE_DEADLINE",
			"ALLOW_CHANGE_DEADLINE_COUNT" => "T.ALLOW_CHANGE_DEADLINE_COUNT",
			"ALLOW_CHANGE_DEADLINE_COUNT_VALUE" => "T.ALLOW_CHANGE_DEADLINE_COUNT_VALUE",
			"ALLOW_CHANGE_DEADLINE_MAXTIME" => "T.ALLOW_CHANGE_DEADLINE_MAXTIME",
			"ALLOW_CHANGE_DEADLINE_MAXTIME_VALUE" => "T.ALLOW_CHANGE_DEADLINE_MAXTIME_VALUE",
			"ALLOW_TIME_TRACKING" => 'T.ALLOW_TIME_TRACKING',
			"MATCH_WORK_TIME" => "T.MATCH_WORK_TIME",
			"TASK_CONTROL" => "T.TASK_CONTROL",
			"ADD_IN_REPORT" => "T.ADD_IN_REPORT",
			"GROUP_ID" => "CASE WHEN T.GROUP_ID IS NULL THEN 0 ELSE T.GROUP_ID END",
			"FORUM_TOPIC_ID" => "T.FORUM_TOPIC_ID",
			"PARENT_ID" => "T.PARENT_ID",
			"COMMENTS_COUNT" => "FT.POSTS",
			"FORUM_ID" => "FT.FORUM_ID",
			"MESSAGE_ID" => "MIN(TSIF.MESSAGE_ID)",
			"SITE_ID" => "T.SITE_ID",
			"SUBORDINATE" => ($strSql = CTasks::GetSubordinateSql('', $arParams)) ? "CASE WHEN EXISTS(".$strSql.") THEN 'Y' ELSE 'N' END" : "'N'",
			"EXCHANGE_MODIFIED" => "T.EXCHANGE_MODIFIED",
			"EXCHANGE_ID" => "T.EXCHANGE_ID",
			"OUTLOOK_VERSION" => "T.OUTLOOK_VERSION",
			"VIEWED_DATE" => $DB->DateToCharFunction("TV.VIEWED_DATE", "FULL"),
			"DEADLINE_COUNTED" => "T.DEADLINE_COUNTED",
			"FORKED_BY_TEMPLATE_ID" => "T.FORKED_BY_TEMPLATE_ID",

			"FAVORITE" => "CASE WHEN FVT.TASK_ID IS NULL THEN 'N' ELSE 'Y' END",
			"SORTING" => "SRT.SORT",

			"DURATION_PLAN_SECONDS" => "T.DURATION_PLAN",
			"DURATION_TYPE_ALL" => "T.DURATION_TYPE",

			"DURATION_PLAN" => "
				case
					when
						T.DURATION_TYPE = '".self::TIME_UNIT_TYPE_MINUTE."' or T.DURATION_TYPE = '".self::TIME_UNIT_TYPE_HOUR."'
					then
						ROUND(T.DURATION_PLAN / 3600, 0)
					when
						T.DURATION_TYPE = '".self::TIME_UNIT_TYPE_DAY."' or T.DURATION_TYPE = '' or T.DURATION_TYPE is null
					then
						ROUND(T.DURATION_PLAN / 86400, 0)
					else
						T.DURATION_PLAN
				end
			",
			"DURATION_TYPE" => "
				case
					when
						T.DURATION_TYPE = '".self::TIME_UNIT_TYPE_MINUTE."'
					then
						'".self::TIME_UNIT_TYPE_HOUR."'
					else
						T.DURATION_TYPE
				end
			"
		);

		if ($bGetZombie)
		{
			$arFields['ZOMBIE'] = 'T.ZOMBIE';
		}
		if (!in_array('MESSAGE_ID', $arSelect))
		{
			$deleteMessageId = true;
		}

		if (count($arSelect) <= 0 || in_array("*", $arSelect))
		{
			$arSelect = array_keys($arFields);
		}
		elseif (!in_array("ID", $arSelect))
		{
			$arSelect[] = "ID";
		}

		// add fields that are NOT selected by default
		//$arFields["FAVORITE"] = "CASE WHEN FVT.TASK_ID IS NULL THEN 'N' ELSE 'Y' END";

		// If DESCRIPTION selected, than BBCODE flag must be selected too
		if (
			in_array('DESCRIPTION', $arSelect)
			&& ( ! in_array('DESCRIPTION_IN_BBCODE', $arSelect) )
		)
		{
			$arSelect[] = 'DESCRIPTION_IN_BBCODE';
		}

		if (!Integration\Forum::isInstalled())
		{
			$arSelect = array_diff($arSelect, array('COMMENTS_COUNT', 'FORUM_ID'));
		}

		if ($deleteMessageId)
		{
			$arSelect = array_diff($arSelect, ['MESSAGE_ID']);
		}

		if (!is_array($arOrder))
		{
			$arOrder = array();
		}

		$arSqlOrder = array();
		foreach ($arOrder as $by => $order)
		{
			$needle = null;
			$by = strtolower($by);
			$order = strtolower($order);

			if ($by === 'deadline')
			{
				if ( ! in_array($order, array('asc', 'desc', 'asc,nulls', 'desc,nulls'), true) )
					$order = 'asc,nulls';
			}
			else
			{
				if ($order !== 'asc')
					$order = 'desc';
			}

			switch ($by)
			{
				case 'id':
					$arSqlOrder[] = " ID ".$order." ";
				break;

				case 'title':
					$arSqlOrder[] = " TITLE ".$order." ";
					$needle = 'TITLE';
				break;

				case 'time_spent_in_logs':
					$arSqlOrder[] = " TIME_SPENT_IN_LOGS ".$order." ";
					$needle = 'TIME_SPENT_IN_LOGS';
				break;

				case 'date_start':
					$arSqlOrder[] = " T.DATE_START ".$order." ";
					$needle = 'DATE_START';
				break;

				case 'created_date':
					$arSqlOrder[] = " T.CREATED_DATE ".$order." ";
					$needle = 'CREATED_DATE';
				break;

				case 'changed_date':
					$arSqlOrder[] = " T.CHANGED_DATE ".$order." ";
					$needle = 'CHANGED_DATE';
				break;

				case 'closed_date':
					$arSqlOrder[] = " T.CLOSED_DATE ".$order." ";
					$needle = 'CLOSED_DATE';
				break;

				case 'start_date_plan':
					$arSqlOrder[] = " T.START_DATE_PLAN ".$order." ";
					$needle = 'START_DATE_PLAN';
				break;

				case 'end_date_plan':
					$arSqlOrder[] = " T.END_DATE_PLAN ".$order." ";
					$needle = 'END_DATE_PLAN';
				break;

				case 'deadline':
					$orderClause = self::getOrderSql(
						'T.DEADLINE',
						$order,
						$default_order = 'asc,nulls',
						$nullable = true
					);
					$needle = 'DEADLINE_ORIG';

					if ( ! is_array($orderClause) )
						$arSqlOrder[] = $orderClause;
					else   // we have to add select field in order to correctly sort
					{
						//         COLUMN ALIAS      COLUMN EXPRESSION
						$arFields[$orderClause[1]] = $orderClause[0];

						if ( ! in_array($orderClause[1], $arSelect) )
							$arSelect[] = $orderClause[1];

						$arSqlOrder[] = $orderClause[2];	// order expression
					}
				break;

				case 'status':
//					$arSqlOrder[] = " STATUS ".$order." ";
//					$needle = 'STATUS';
				break;
				case 'real_status':
					$arSqlOrder[] = " REAL_STATUS ".$order." ";
					$needle = 'REAL_STATUS';
				break;

				case 'status_complete':
					$arSqlOrder[] = " STATUS_COMPLETE ".$order." ";
					$needle = 'STATUS_COMPLETE';
				break;

				case 'priority':
					$arSqlOrder[] = " PRIORITY ".$order." ";
					$needle = 'PRIORITY';
				break;

				case 'mark':
					$arSqlOrder[] = " MARK ".$order." ";
					$needle = 'MARK';
				break;

				case 'originator_name':
				case 'created_by':
					$arSqlOrder[] = " CREATED_BY_LAST_NAME ".$order." ";
					$needle = 'CREATED_BY_LAST_NAME';
				break;

				case 'responsible_name':
				case 'responsible_id':
					$arSqlOrder[] = " RESPONSIBLE_LAST_NAME ".$order." ";
					$needle = 'RESPONSIBLE_LAST_NAME';
				break;

				case 'group_id':
					$arSqlOrder[] = " GROUP_ID ".$order." ";
					$needle = 'GROUP_ID';
				break;

				case 'time_estimate':
					$arSqlOrder[] = " TIME_ESTIMATE ".$order." ";
					$needle = 'TIME_ESTIMATE';
				break;

				case 'allow_change_deadline':
					$arSqlOrder[] = " ALLOW_CHANGE_DEADLINE ".$order." ";
					$needle = 'ALLOW_CHANGE_DEADLINE';
				break;

				case 'allow_time_tracking':
					$arSqlOrder[] = " ALLOW_TIME_TRACKING ".$order." ";
					$needle = 'ALLOW_TIME_TRACKING';
				break;

				case 'match_work_time':
					$arSqlOrder[] = " MATCH_WORK_TIME ".$order." ";
					$needle = 'MATCH_WORK_TIME';
				break;

				case 'favorite':
					$arSqlOrder[] = " FAVORITE ".$order." ";
					$needle = 'FAVORITE';
				break;

				case 'sorting':
					$asc = stripos($order, "desc") === false;
					$arSqlOrder = array_merge($arSqlOrder, self::getSortingOrderBy($asc));
					$needle = "SORTING";
				break;

				case 'message_id':
					$arSqlOrder[] = " MESSAGE_ID " . $order . " ";
					$needle = 'MESSAGE_ID';
					break;

				default:
					if (substr($by, 0, 3) === 'uf_')
					{
						if ($s = $obUserFieldsSql->GetOrder($by))
							$arSqlOrder[$by] = " ".$s." ".$order." ";
					}
					else
						CTaskAssert::logWarning('[0x9a92cf7d] invalid sort by field requested: ' . $by);
				break;
			}

			if (
				($needle !== null)
				&& ( ! in_array($needle, $arSelect) )
			)
			{
				$arSelect[] = $needle;
			}
		}

		$arSqlSelect = array();
		foreach ($arSelect as $field)
		{
			$field = strtoupper($field);
			if (array_key_exists($field, $arFields))
				$arSqlSelect[$field] = $arFields[$field]." AS ".$field;
		}

		if (!sizeof($arSqlSelect))
		{
			$arSqlSelect = "T.ID AS ID";
		}

		$disableAccessOptimization = (is_array($arParams) && $arParams['DISABLE_ACCESS_OPTIMIZATION'] === true);
		$useAccessAsWhere = !$disableAccessOptimization;

		// First level logic MUST be 'AND', because of backward compatibility
		// and some requests for checking rights, attached at first level of filter.
		// Situtation when there is OR-logic at first level cannot be resolved
		// in general case.
		// So if there is OR-logic, it is FATAL error caused by programmer.
		// But, if you want to use OR-logic at the first level of filter, you
		// can do this by putting all your filter conditions to the ::SUBFILTER-xxx,
		// except CHECK_PERMISSIONS, SUBORDINATE_TASKS (if you don't know exactly,
		// what are consequences of this fields in OR-logic of subfilters).
		if (isset($arFilter['::LOGIC']))
		{
			CTaskAssert::assert($arFilter['::LOGIC'] === 'AND');
		}

		// GET OPTIMIZED JOINS
		$sourceFilter = $arFilter;
		$optimized = static::tryOptimizeFilter($arFilter);

		$arFilter = $optimized['FILTER'];
		$optimizedJoins = implode("\n", $optimized['JOINS']);
		$distinct = '';

		if (!empty($optimized['JOINS']))
		{
			$distinct = 'DISTINCT';
			$arParams['SOURCE_FILTER'] = $sourceFilter;
		}

		// GET RELATED JOINS
		$params = [
			'USER_ID' => $userID,
			'VIEWED_BY' => static::getViewedBy($sourceFilter, $userID),
			'SORTING_GROUP_ID' => (isset($arParams['SORTING_GROUP_ID']) && $arParams['SORTING_GROUP_ID'] > 0? $arParams['SORTING_GROUP_ID'] : false)
		];
		$relatedJoins = static::getRelatedJoins($arSelect, $arFilter, $arOrder, $params);
		$relatedJoinsForCount = array_merge(
			[
				'CREATOR' => "INNER JOIN " . UserTable::getTableName() . " CU ON CU.ID = T.CREATED_BY",
				'RESPONSIBLE' => "INNER JOIN " . UserTable::getTableName() . " RU ON RU.ID = T.RESPONSIBLE_ID"
			],
			static::getRelatedJoins([], $arFilter, [], $params)
		);

		// GET ACCESS SQL
		$accessSql = '';
		if ($useAccessAsWhere && static::needAccessRestriction($arFilter, $arParams))
		{
			$buildAccessSql = true;
			$arParams['APPLY_FILTER'] = static::makePossibleForwardedFilter($arFilter);
			$arParams['PUT_SELECT_INTO_WHERE'] = true;

			if ($arParams['MAKE_ACCESS_FILTER'])
			{
				$viewedUserId = static::getViewedUserId($sourceFilter, $userID);

				$runtimeOptions = static::makeAccessFilterRuntimeOptions($sourceFilter, [
					'USER_ID' => $userID,
					'VIEWED_USER_ID' => $viewedUserId
				]);

				if (!is_array($arParams['ACCESS_FILTER_RUNTIME_OPTIONS']))
				{
					$arParams['ACCESS_FILTER_RUNTIME_OPTIONS'] = $runtimeOptions;
				}
				else
				{
					foreach ($runtimeOptions as $key => $value)
					{
						$arParams['ACCESS_FILTER_RUNTIME_OPTIONS'][$key] += $value;
					}
				}

				if ($viewedUserId == $userID)
				{
					$buildAccessSql = static::checkAccessSqlBuilding($runtimeOptions);
				}
			}

			if ($buildAccessSql)
			{
				try
				{
					$accessSql = static::appendJoinRights($accessSql, $arParams);
				}
				catch (\Exception $exception)
				{
					$hash = hash('md5', 'ACCESS_FILTER_BUILDING_ERROR'); //934afb467df6f2ed2c5ff62fc358d6fe
					throw new TasksException('[' . $hash . '] ' . $exception->getMessage(), TasksException::TE_SQL_ERROR);
				}
			}
		}

		// GET FILTER
		$arParams['ENABLE_LEGACY_ACCESS'] = $disableAccessOptimization; // manual legacy access switch
		$arSqlSearch = CTasks::GetFilter($arFilter, '', $arParams);

		if (!$bGetZombie)
		{
			$arSqlSearch[] = " T.ZOMBIE = 'N' ";
		}

		if ($accessSql !== '')
		{
			$arSqlSearch[] = $accessSql;
		}

		$userFieldsJoin = false;
		$r = $obUserFieldsSql->GetFilter();
		if (strlen($r) > 0)
		{
			$userFieldsJoin = true;
			$arSqlSearch[] = "(".$r.")";
		}

		// GET GROUP BY
		if (isset($relatedJoins['FULL_SEARCH']) || isset($relatedJoins['COMMENT_SEARCH']))
		{
			$arGroup[] = "T.ID";
		}
		$strGroupBy = (!empty($arGroup)? 'GROUP BY ' . implode(',', $arGroup) : "");

		// GET ORDER BY
		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		for ($i = 0, $arSqlOrderCnt = count($arSqlOrder); $i < $arSqlOrderCnt; $i++)
		{
			if ($i == 0)
			{
				$strSqlOrder = " ORDER BY ";
			}
			else
			{
				$strSqlOrder .= ",";
			}

			$strSqlOrder .= $arSqlOrder[$i];
		}

		// BUILD QUERY
		$strSql = "
			SELECT " . $distinct . "
			" . implode(",\n", $arSqlSelect) . "
			" . $obUserFieldsSql->GetSelect();

		$strFrom = "
			FROM b_tasks T
			" . $optimizedJoins . "
			" . implode("\n", $relatedJoins) . "
			" . $obUserFieldsSql->GetJoin("T.ID") . "
			" . (count($arSqlSearch)? "WHERE " . implode(" AND ", $arSqlSearch) : "");

		$strSql .= "
			" . $strFrom . "
			" . $strGroupBy . "
			" . $strSqlOrder;

		$strFromForCount = "
			FROM b_tasks T
			" . $optimizedJoins . "
			" . implode("\n", $relatedJoinsForCount) . "
			" . ($userFieldsJoin? $obUserFieldsSql->GetJoin("T.ID") : "") . "
			" . (count($arSqlSearch)? "WHERE " . implode(" AND ", $arSqlSearch) : "");

		if (($nPageTop !== false) && is_numeric($nPageTop))
		{
			$strSql = $DB->TopSql($strSql, intval($nPageTop));
		}

		if (is_array($arParams) && array_key_exists("NAV_PARAMS", $arParams) && is_array($arParams["NAV_PARAMS"]))
		{
			$nTopCount = intval($arParams['NAV_PARAMS']['nTopCount']);
			if($nTopCount > 0)
			{
				$strSql = $DB->TopSql($strSql, $nTopCount);
				$res = $DB->Query($strSql, $bIgnoreErrors, "File: " . __FILE__ . "<br>Line: " . __LINE__);

				if ($res === false)
					throw new TasksException('', TasksException::TE_SQL_ERROR);

				$res->SetUserFields($USER_FIELD_MANAGER->GetUserFields("TASKS_TASK"));
			}
			else
			{
				$res_cnt = $DB->Query("SELECT COUNT(DISTINCT T.ID) as C " . $strFromForCount);
				$res_cnt = $res_cnt->Fetch();
				$totalTasksCount = (int) $res_cnt["C"];	// unknown by default

				// Sync counters in case of mistiming
//				CTaskCountersProcessorHomeostasis::onTaskGetList($arFilter, $totalTasksCount);

				$res = new CDBResult();
				$res->SetUserFields($USER_FIELD_MANAGER->GetUserFields("TASKS_TASK"));
				$rc = $res->NavQuery($strSql, $totalTasksCount, $arParams["NAV_PARAMS"], $bIgnoreErrors);

				if ($bIgnoreErrors && ($rc === false))
					throw new TasksException('', TasksException::TE_SQL_ERROR);
			}
		}
		else
		{
			$res = $DB->Query($strSql, $bIgnoreErrors, "File: " . __FILE__ . "<br>Line: " . __LINE__);

			if ($res === false)
				throw new TasksException('', TasksException::TE_SQL_ERROR);

			$res->SetUserFields($USER_FIELD_MANAGER->GetUserFields("TASKS_TASK"));
		}

		return $res;
	}

	public static function getAvailableOrderFields()
    {
        return [
            'ID',
            'TITLE',
            'TIME_SPENT_IN_LOGS',
            'DATE_START',
            'CREATED_DATE',
            'CHANGED_DATE',
            'CLOSED_DATE',
            'START_DATE_PLAN',
            'END_DATE_PLAN',
            'DEADLINE',
            'REAL_STATUS',
            'STATUS_COMPLETE',
            'PRIORITY',
            'MARK',
            'CREATED_BY_LAST_NAME',
            'RESPONSIBLE_LAST_NAME',
            'GROUP_ID',
            'TIME_ESTIMATE',
            'ALLOW_CHANGE_DEADLINE',
            'ALLOW_TIME_TRACKING',
            'MATCH_WORK_TIME',
            'FAVORITE',
            'SORTING',
            'MESSAGE_ID'
        ];
    }

	/**
	 * Checks if we need to build access sql
	 *
	 * @param $runtimeOptions
	 * @return bool
	 */
	private static function checkAccessSqlBuilding($runtimeOptions)
	{
		$fields = $runtimeOptions['FIELDS'];

		foreach (array_keys($fields) as $key)
		{
			if (preg_match('/^ROLE_/', $key))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns related joins
	 *
	 * @param $select
	 * @param $filter
	 * @param $order
	 * @param $params
	 * @return array
	 */
	public static function getRelatedJoins($select, $filter, $order, $params)
	{
		$relatedJoins = [];

		$userId = ($params['USER_ID']?: User::getId());
		$viewedBy = ($params['VIEWED_BY']?: $userId);
		$sortingGroupId = $params['SORTING_GROUP_ID'];
		$joinAlias = ($params['JOIN_ALIAS']?: "");
		$sourceAlias = ($params['SOURCE_ALIAS']?: "T");

		$filterKeys = static::GetFilteredKeys($filter);
		$possibleJoins = ['CREATOR', 'RESPONSIBLE', 'VIEWED', 'SORTING', 'FAVORITE', 'STAGES', 'FORUM', 'FULL_SEARCH', 'COMMENT_SEARCH'];

		foreach ($possibleJoins as $join)
		{
			switch ($join)
			{
				case 'CREATOR':
					if (
						in_array('CREATED_BY', $select, true) ||
						in_array('CREATED_BY_NAME', $select, true) ||
						in_array('CREATED_BY_LAST_NAME', $select, true) ||
						in_array('CREATED_BY_SECOND_NAME', $select, true) ||
						in_array('CREATED_BY_LOGIN', $select, true) ||
						in_array('CREATED_BY_WORK_POSITION', $select, true) ||
						in_array('CREATED_BY_PHOTO', $select, true) ||
						array_key_exists('ORIGINATOR_NAME', $order) ||
						array_key_exists('CREATED_BY', $order)
					)
					{
						$relatedJoins[$join] = "INNER JOIN " . UserTable::getTableName() . " {$joinAlias}CU ON {$joinAlias}CU.ID = {$sourceAlias}.CREATED_BY";
					}
					break;

				case 'RESPONSIBLE':
					if (
						in_array('RESPONSIBLE_ID', $select, true) ||
						in_array('RESPONSIBLE_NAME', $select, true) ||
						in_array('RESPONSIBLE_LAST_NAME', $select, true) ||
						in_array('RESPONSIBLE_SECOND_NAME', $select, true) ||
						in_array('RESPONSIBLE_LOGIN', $select, true) ||
						in_array('RESPONSIBLE_WORK_POSITION', $select, true) ||
						in_array('RESPONSIBLE_PHOTO', $select, true) ||
						array_key_exists('RESPONSIBLE_NAME', $order) ||
						array_key_exists('RESPONSIBLE_ID', $order)
					)
					{
						$relatedJoins[$join] = "INNER JOIN " . UserTable::getTableName() . " {$joinAlias}RU ON {$joinAlias}RU.ID = {$sourceAlias}.RESPONSIBLE_ID";
					}
					break;

				case 'VIEWED':
					if (
						in_array('STATUS', $select, true) ||
						in_array('NOT_VIEWED', $select, true) ||
						in_array('VIEWED_DATE', $select, true) ||
						in_array('STATUS', $filterKeys, true) ||
						in_array('VIEWED_BY', $filterKeys, true)
					)
					{
						$relatedJoins[$join] = "LEFT JOIN " . ViewedTable::getTableName() . " {$joinAlias}TV ON {$joinAlias}TV.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}TV.USER_ID = " . $viewedBy;
					}
					break;

				case 'SORTING':
					if (
						in_array('SORTING', $select, true) ||
						in_array('SORTING', $filterKeys, true) ||
						array_key_exists('SORTING', $order)
					)
					{
						$relatedJoins[$join] = "LEFT JOIN " . SortingTable::getTableName() . " {$joinAlias}SRT ON {$joinAlias}SRT.TASK_ID = {$sourceAlias}.ID AND " .
							($sortingGroupId > 0? "{$joinAlias}SRT.GROUP_ID = " . $sortingGroupId : "{$joinAlias}SRT.USER_ID = " . $userId);
					}
					break;

				case 'FAVORITE':
					if (
						in_array('FAVORITE', $select, true) ||
						in_array('FAVORITE', $filterKeys, true) ||
						array_key_exists('FAVORITE', $order)
					)
					{
						$relatedJoins[$join] = "LEFT JOIN " . FavoriteTable::getTableName() . " {$joinAlias}FVT ON {$joinAlias}FVT.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}FVT.USER_ID = " . $userId;
					}
					break;

				case 'STAGES':
					if (in_array('STAGES_ID', $filterKeys, true))
					{
						$relatedJoins[$join] = "INNER JOIN " . TaskStageTable::getTableName() . " {$joinAlias}STG ON STG.TASK_ID = {$sourceAlias}.ID";
					}
					break;

				case 'FORUM':
					if (
						in_array('COMMENTS_COUNT', $select, true) ||
						in_array('FORUM_ID', $select, true)
					)
					{
						$relatedJoins[$join] = "LEFT JOIN b_forum_topic {$joinAlias}FT ON {$joinAlias}FT.ID = {$sourceAlias}.FORUM_TOPIC_ID";
					}
					break;

				case 'FULL_SEARCH':
					if (
						in_array('MESSAGE_ID', $select, true) ||
						in_array('FULL_SEARCH_INDEX', $filterKeys, true)
					)
					{
						$relatedJoins[$join] = "INNER JOIN " . SearchIndexTable::getTableName() . " {$joinAlias}TSIF ON {$joinAlias}TSIF.TASK_ID = {$sourceAlias}.ID";
					}
					break;

				case 'COMMENT_SEARCH':
					if (in_array('COMMENT_SEARCH_INDEX', $filterKeys, true))
					{
						$relatedJoins[$join] = "INNER JOIN " . SearchIndexTable::getTableName() . " {$joinAlias}TSIC ON {$joinAlias}TSIC.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}TSIC.MESSAGE_ID <> 0";
					}
					break;
			}
		}

		return $relatedJoins;
	}

	/**
	 * Creates filter runtime options from given sub filter
	 *
	 * @param $filter
	 * @param $parameters
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function makeAccessFilterRuntimeOptions($filter, $parameters)
	{
		$runtimeOptions = [
			'FIELDS' => [],
			'FILTERS' => []
		];

		$fields = [
			// ROLES
			'CREATED_BY' => true,
			'RESPONSIBLE_ID' => true,
			'ACCOMPLICE' => true,
			'AUDITOR' => true,
			'ROLEID' => true,

			// TASK FIELDS
			'ID' => true,
			'TITLE' => true,
			'PRIORITY' => true,
			'STATUS' => true,
			'GROUP_ID' => true,
			'TAG' => true,
			'MARK' => true,
			'ALLOW_TIME_TRACKING' => true,

			// RELATED FIELDS
			'FULL_SEARCH_INDEX' => true,
			'COMMENT_SEARCH_INDEX' => true,

			// DATES
			'DEADLINE' => true,
			'CREATED_DATE' => true,
			'CLOSED_DATE' => true,
			'DATE_START' => true,
			'START_DATE_PLAN' => true,
			'END_DATE_PLAN' => true,

			// DIFFICULT PARAMS
			'ACTIVE' => true,
			'PARAMS' => true,
			'PROBLEM' => true,
		];

		if (is_array($filter) && !empty($filter))
		{
			foreach ($filter as $key => $value)
			{
				$newKey = substr((string)$key, 12);

				if ($newKey && $fields[$newKey])
				{
					$fieldRuntimeOptions = static::getFieldRuntimeOptions($newKey, $value, $parameters);

					$runtimeOptions['FIELDS'] = array_merge($runtimeOptions['FIELDS'], $fieldRuntimeOptions['FIELDS']);
					$runtimeOptions['FILTERS'] = array_merge($runtimeOptions['FILTERS'], $fieldRuntimeOptions['FILTERS']);
				}
			}
		}

		return $runtimeOptions;
	}

	/**
	 * Returns field's runtime options
	 *
	 * @param $key
	 * @param $value
	 * @param $parameters
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function getFieldRuntimeOptions($key, $value, $parameters)
	{
		$runtimeOptions = [
			'FIELDS' => [],
			'FILTERS' => []
		];
		$dates = ['DEADLINE', 'CREATED_DATE', 'CLOSED_DATE', 'DATE_START', 'START_DATE_PLAN', 'END_DATE_PLAN'];

		switch ($key)
		{
			case 'ID':
			case 'PRIORITY':
			case 'MARK':
			case 'ALLOW_TIME_TRACKING':
			case 'DEADLINE':
			case 'CREATED_DATE':
			case 'CLOSED_DATE':
			case 'DATE_START':
			case 'START_DATE_PLAN':
			case 'END_DATE_PLAN':
				foreach ($value as $name => $val)
				{
					if (in_array($key, $dates))
					{
						$val = new Type\DateTime($val);
					}

					$fieldKeyData = static::parseFieldKey($name, $key);
					$runtimeOptions['FILTERS'][$key] = Query::filter()->where($key, $fieldKeyData['OPERATOR'], $val);
				}
				break;

			case 'TITLE':
				foreach ($value as $name => $val)
				{
					$fieldKeyData = static::parseFieldKey($name, $key);

					$field = Query::expr()->upper($key);
					$val = '%' . ToUpper($val) . '%';

					$runtimeOptions['FILTERS'][$key] = Query::filter()->where($field, $fieldKeyData['OPERATOR'], $val);
				}
				break;

			case 'FULL_SEARCH_INDEX':
			case 'COMMENT_SEARCH_INDEX':
				$map = [
					'FULL_SEARCH_INDEX' => [],
					'COMMENT_SEARCH_INDEX' => Query::filter()->where('ref.MESSAGE_ID', '!=', 0)
				];

				$runtimeOptions['FIELDS'][$key] = new Entity\ReferenceField(
					'TSI' . $key[0],
					SearchIndexTable::class,
					Join::on('ref.TASK_ID', 'this.ID')
						->where($map[$key]),
					['join_type' => 'inner']
				);

				$column = "TSI{$key[0]}.SEARCH_INDEX";
				$value = current($value);

				if (SearchIndexTable::isFullTextIndexEnabled())
				{
					$filter = Query::filter()->whereMatch($column, $value);
				}
				else
				{
					$filter = Query::filter()->whereLike(Query::expr()->upper($column), '%' . ToUpper($value) . '%');
				}

				$runtimeOptions['FILTERS'][$key] = $filter;
				break;

			case 'CREATED_BY':
			case 'RESPONSIBLE_ID':
			case 'GROUP_ID':
				$fieldKeyData = static::parseFieldKey(key($value), $key, 'in');
				$runtimeOptions['FILTERS'][$key] = Query::filter()->where($key, $fieldKeyData['OPERATOR'], current($value));
				break;

			case 'STATUS':
				if (!empty($value['REAL_STATUS']))
				{
					$runtimeOptions['FILTERS'][$key] = Query::filter()->where($key, 'in', $value['REAL_STATUS']);
				}
				break;

			case 'ACCOMPLICE':
			case 'AUDITOR':
			case 'TAG':
				$parameters['USER_ID'] = $parameters['NAME'] = current($value);
				$parameters['TYPE_CONDITION'] = true;

				$runtimeOptions['FILTERS'][$key] = Query::filter()->whereExists(static::getSelectionExpressionByType($key, $parameters));
				break;

			case 'ROLEID':
			case 'PROBLEM':
				if ($key == 'ROLEID')
				{
					$filterOptions = static::getFilterOptionsFromRoleField($value);
				}
				else
				{
					$filterOptions = static::getFilterOptionsFromProblemField($value, $parameters);
				}

				$runtimeOptions['FIELDS'] = $filterOptions['FIELDS'];
				$runtimeOptions['FILTERS'] = $filterOptions['FILTERS'];
				break;

			case 'ACTIVE':
				$date = $value[$key];
				$dateStart = $dateEnd = false;

				if (MakeTimeStamp($date['START']) > 0)
				{
					$dateStart = new Type\DateTime($date['START']);
				}
				if (MakeTimeStamp($date['END']))
				{
					$dateEnd = new Type\DateTime($date['END']);
				}

				if ($dateStart !== false && $dateEnd !== false)
				{
					$runtimeOptions['FILTERS'][$key] = Query::filter()->where(
						Query::filter()
							->logic('or')
							->where(
								Query::filter()
									->where('CREATED_DATE', '>=', $dateStart)
									->where('CLOSED_DATE', '<=', $dateEnd)
							)
							->where(
								Query::filter()
									->where('CHANGED_DATE', '>=', $dateStart)
									->where('CHANGED_DATE', '<=', $dateEnd)
							)
							->where(
								Query::filter()
									->where('CREATED_DATE', '<=', $dateStart)
									->where('CLOSED_DATE', '=', null)
							)
					);
				}
				break;

			case 'PARAMS':
				foreach ($value as $name => $val)
				{
					$fieldKeyData = static::parseFieldKey($name);
					$fieldName = $fieldKeyData['FIELD_NAME'];

					if ($fieldName == 'MARK' || $fieldName == 'ADD_IN_REPORT')
					{
						$operator = $fieldKeyData['OPERATOR'];
						$runtimeOptions['FILTERS'][$fieldName] = Query::filter()->where($fieldName, $operator, $val);
					}
					else if ($fieldName == 'FAVORITE')
					{
						$runtimeOptions['FIELDS'][$fieldName] = new Entity\ReferenceField(
							'FVT',
							FavoriteTable::class,
							Join::on('ref.TASK_ID', 'this.ID')
								->where('ref.USER_ID', $parameters['USER_ID'])
						);
						$runtimeOptions['FILTERS'][$fieldName] = Query::filter()->where('FVT.TASK_ID', '!=', null);
					}
					else if ($fieldName == 'OVERDUED')
					{
						$runtimeOptions['FILTERS'][$fieldName] = Query::filter()
							->where('DEADLINE', '!=', null)
							->where('CLOSED_DATE', '!=', null)
							->whereColumn('DEADLINE', '<', 'CLOSED_DATE');
					}
				}
				break;
		}

		return $runtimeOptions;
	}

	/**
	 * Tries to parse string like '>=DEADLINE' to separate operator '>=' suitable for orm and pure name 'DEADLINE'
	 *
	 * @param $key
	 * @param string $fieldName
	 * @param string $defaultOperator
	 * @return array
	 */
	private static function parseFieldKey($key, $fieldName = '', $defaultOperator = '=')
	{
		$operators = [
			'>=' => '>=',
			'<=' => '<=',
			'!=' => '!=',
			'%' => 'like',
			'=%' => 'like',
			'%=' => 'like',
			'=' => '=',
			'>' => '>',
			'<' => '<',
			'!' => '!='
		];

		if ($fieldName)
		{
			$operator = str_replace($fieldName, '', $key);
			$operator = ($operator && isset($operators[$operator])? $operators[$operator] : $defaultOperator);
		}
		else
		{
			$pattern = '/^(' . implode('|', array_keys($operators)) . ')/';
			$matches = [];

			preg_match($pattern, $key, $matches);

			if (!empty($matches))
			{
				$operator = $operators[$matches[0]];
				$fieldName = str_replace($matches[0], '', $key);
			}
			else
			{
				$operator = $defaultOperator;
				$fieldName = $key;
			}
		}

		return [
			'OPERATOR' => $operator,
			'FIELD_NAME' => $fieldName
		];
	}

	/**
	 * Returns role field type based on its conditions
	 *
	 * @param $role
	 * @return string
	 */
	private static function getRoleFieldType($role)
	{
		if (array_key_exists('MEMBER', $role))
		{
			return 'MEMBER';
		}

		if (array_key_exists('=CREATED_BY', $role))
		{
			return 'CREATED_BY';
		}

		if (array_key_exists('=RESPONSIBLE_ID', $role))
		{
			return 'RESPONSIBLE_ID';
		}

		if (array_key_exists('=ACCOMPLICE', $role))
		{
			return 'ACCOMPLICE';
		}

		if (array_key_exists('=AUDITOR', $role))
		{
			return 'AUDITOR';
		}

		return '';
	}

	/**
	 * Returns filter options of role filter field
	 *
	 * @param $role
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function getFilterOptionsFromRoleField($role)
	{
		$fields = [];
		$filters = [];

		$key = 'ROLE_';
		$roleType = static::getRoleFieldType($role);
		$userId = $role[($roleType == 'MEMBER'? '' : '=') . $roleType];

		$referenceFilter = Query::filter()
			->whereColumn('ref.TASK_ID', 'this.ID')
			->where('ref.USER_ID', $userId);

		switch ($roleType)
		{
			case 'MEMBER':
				$fields[$key . $roleType] = static::getMemberTableReferenceField($referenceFilter);
				break;

			case 'CREATED_BY':
			case 'RESPONSIBLE_ID':
			case 'ACCOMPLICE':
			case 'AUDITOR':
				$map = [
					'CREATED_BY' => 'O',
					'RESPONSIBLE_ID' => 'R',
					'ACCOMPLICE' => 'A',
					'AUDITOR' => 'U'
				];
				$referenceFilter->where('ref.TYPE', $map[$roleType]);

				$fields[$key . $roleType] = static::getMemberTableReferenceField($referenceFilter);

				if ($roleType == 'CREATED_BY')
				{
					$filters[$key . $roleType] = Query::filter()->whereColumn('CREATED_BY', '!=', 'RESPONSIBLE_ID');
				}
				break;
		}

		return [
			'FIELDS' => $fields,
			'FILTERS' => $filters
		];
	}

	/**
	 * Returns reference field for joining member table
	 *
	 * @param $referenceFilter
	 * @return Entity\ReferenceField
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function getMemberTableReferenceField($referenceFilter)
	{
		$joinOn = $referenceFilter;
		$joinType = ['join_type' => 'inner'];

		return new Entity\ReferenceField('TM', MemberTable::class, $joinOn, $joinType);
	}

	/**
	 * Returns filter options of problem filter field
	 *
	 * @param $problem
	 * @param $parameters
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function getFilterOptionsFromProblemField($problem, $parameters)
	{
		$fields = [];
		$filters = [];

		if (array_key_exists('VIEWED', $problem))
		{
			$userId = ($problem['VIEWED_BY']?: $parameters['USER_ID']);
			$filterKey = 'PROBLEM_NOT_VIEWED';

			$fields[$filterKey] = new Entity\ReferenceField(
				'TV',
				ViewedTable::class,
				Join::on('ref.TASK_ID', 'this.ID')
					->where('ref.USER_ID', $userId)
			);
			$filters[$filterKey] = Query::filter()
				->where('TV.USER_ID', null)
				->where('STATUS', 'in', [1, 2]);
		}
		else
		{
			$filters['PROBLEM'] = static::parseLogicProblemFilter($problem);
		}

		return [
			'FIELDS' => $fields,
			'FILTERS' => $filters
		];
	}

	/**
	 * Parse logic filter
	 *
	 * @param $problem
	 * @return \Bitrix\Main\ORM\Query\Filter\ConditionTree
	 * @throws \Bitrix\Main\ArgumentException
	 */
	private static function parseLogicProblemFilter($problem)
	{
		$filter = Query::filter();

		foreach ($problem as $key => $condition)
		{
			if (static::isSubFilterKey($key))
			{
				$filter->where(static::parseLogicProblemFilter($condition));
				continue;
			}

			if ($key == '::LOGIC')
			{
				$filter->logic($condition);
				continue;
			}

			$fieldKeyData = static::parseFieldKey($key);
			$fieldKeyName = ($fieldKeyData['FIELD_NAME'] == 'REAL_STATUS'? 'STATUS' : $fieldKeyData['FIELD_NAME']);
			$fieldKeyOperator = $fieldKeyData['OPERATOR'];

			if (substr($fieldKeyName, 0, 10) == 'REFERENCE:')
			{
				$filter->whereColumn(substr($fieldKeyName, 10), $fieldKeyOperator, $condition);
			}
			else if ($condition == null && $fieldKeyOperator == '=')
			{
				$filter->whereNull($fieldKeyName);
			}
			else
			{
				$filter->where($fieldKeyName, $fieldKeyOperator, $condition);
			}
		}

		return $filter;
	}

	/**
	 * Gets selection sql expression by expression type
	 *
	 * @param $type
	 * @param $parameters
	 * @return SqlExpression|string
	 */
	private static function getSelectionExpressionByType($type, $parameters)
	{
		try
		{
			switch ($type)
			{
				case 'MEMBER':
				case 'ACCOMPLICE':
				case 'AUDITOR':
					$userIdsConditions = [];
					foreach ($parameters['USER_ID'] as $userId)
					{
						$userIdsConditions[] = "(TM.USER_ID = '" . intval($userId) . "')";
					}
					$typeCondition = ($parameters['TYPE_CONDITION'] ? ' AND TM.TYPE = ?s' : '');

					$sql = new SqlExpression(
						'SELECT TM.?# FROM ?# TM WHERE TM.?# = ?#.ID AND (' . implode(" OR ", $userIdsConditions) . ')' . $typeCondition,
						'TASK_ID',
						'b_tasks_member',
						'TASK_ID',
						'tasks_internals_task',
						($type == 'ACCOMPLICE' ? 'A' : 'U')
					);
					break;

				case 'TAG':
					$sql = new SqlExpression(
						'SELECT TT.?# FROM ?# TT WHERE TT.?# = ?#.ID AND TT.NAME = ?s',
						'TASK_ID',
						'b_tasks_tag',
						'TASK_ID',
						'tasks_internals_task',
						$parameters['NAME']
					);
					break;

				default:
					$sql = '';
					break;
			}
		}
		catch (Exception $ex)
		{
			$sql = '';
		}

		return $sql;
	}

	/**
	 * @param $filter
	 * @return array
	 */
	private static function makePossibleForwardedFilter($filter)
	{
		$result = array();

		$allowedFields = array(
			'ID' => true, // number_wo_nulls
			'TITLE' => true, // string
			'STATUS_CHANGED_BY' => true, // number
			'SITE_ID' => true, // string_equal

			'PRIORITY' => true, // number_wo_nulls
			'STAGE_ID' => true, // number_wo_nulls
			'RESPONSIBLE_ID' => true, // number_wo_nulls
			'TIME_ESTIMATE' => true, // number_wo_nulls
			'CREATED_BY' => true, // number_wo_nulls
			'GUID' => true, // string
			'XML_ID' => true, // string_equal
			'MARK' => true, // string_equal
			'ALLOW_CHANGE_DEADLINE' => true, // string_equal
			'ALLOW_TIME_TRACKING' => true, // string_equal
			'ADD_IN_REPORT' => true, // string_equal
			'GROUP_ID' => true, // number
			'PARENT_ID' => true, // number
			'FORUM_TOPIC_ID' => true, // number
			'ZOMBIE' => true, // string_equal
			'MATCH_WORK_TIME' => true, // string_equal

			//dates
			/*
			'DATE_START' => true,
			'DEADLINE' => true,
			'START_DATE_PLAN' => true,
			'END_DATE_PLAN' => true,
			'CREATED_DATE' => true,
			'STATUS_CHANGED_DATE' => true,
			 */
		);

		$stringEqual = array(
			'SITE_ID' => true, // string_equal
			'XML_ID' => true, // string_equal
			'MARK' => true, // string_equal
			'ALLOW_CHANGE_DEADLINE' => true, // string_equal
			'ALLOW_TIME_TRACKING' => true, // string_equal
			'ADD_IN_REPORT' => true, // string_equal
			'ZOMBIE' => true, // string_equal
			'MATCH_WORK_TIME' => true, // string_equal
		);

		if(is_array($filter) && !empty($filter))
		{
			// cannot forward filer with LOGIC OR or LOGIC NOT
			if(array_key_exists('LOGIC', $filter) && $filter['LOGIC'] != 'AND')
			{
				return $result;
			}
			if(array_key_exists('::LOGIC', $filter) && $filter['::LOGIC'] != 'AND')
			{
				return $result;
			}

			$filter = \Bitrix\Tasks\Internals\DataBase\Helper\Common::parseFilter($filter);
			foreach($filter as $k => $condition)
			{
				$field = $condition['FIELD'];

				if(!array_key_exists($field, $allowedFields))
				{
					continue;
				}

				// convert like into strict check
				if(array_key_exists($field, $stringEqual))
				{
					// '' => '='
					if($condition['OPERATION'] == 'E')
					{
						$condition['OPERATION'] = 'I';
						unset($condition['ORIG_KEY']);
					}
					// '!' => '!='
					if($condition['OPERATION'] == 'N')
					{
						$condition['OPERATION'] = 'NI';
						unset($condition['ORIG_KEY']);
					}
				}

				// actually, allow only "equal" and "not equal"
				$op = $condition['OPERATION'];
				if($op != 'E' && $op != 'I' && $op != 'N' && $op != 'NI')
				{
					continue;
				}

				$result[] = $condition;
			}

			$result = \Bitrix\Tasks\Internals\DataBase\Helper\Common::makeFilter($result);
		}

		return $result;
	}

	private static function needAccessRestriction(array $arFilter, $arParams)
	{
		if (is_array($arParams) && array_key_exists('USER_ID', $arParams) && ($arParams['USER_ID'] > 0))
			$userID = (int) $arParams['USER_ID'];
		else
			$userID = User::getId();

		return
			!User::isSuper($userID)
			&&
			$arFilter["CHECK_PERMISSIONS"] != "N" // and not setted flag "skip permissions check"
			&&
			$arFilter["SUBORDINATE_TASKS"] != "Y"; // and not rights via subordination
	}

	/**
	 * @param array $filter
	 * @param bool $getZombie
	 * @param string $aliasPrefix
	 * @param array $params
	 * @return string
	 */
	private static function GetRootSubQuery($filter = [], $getZombie = false, $aliasPrefix = '', $params = [])
	{
		$filter = (isset($params['SOURCE_FILTER'])? $params['SOURCE_FILTER'] : $filter);
		$userId = (isset($params['USER_ID'])? $params['USER_ID'] : User::getId());

		$sqlSearch = ["(PT.ID = " . $aliasPrefix . "T.PARENT_ID)"];

		if (!$getZombie)
		{
			$sqlSearch[] = " (PT.ZOMBIE = 'N') ";
		}

		if ($filter["SAME_GROUP_PARENT"] == "Y")
		{
			$sqlSearch[] = "(PT.GROUP_ID = " . $aliasPrefix . "T.GROUP_ID
				OR (PT.GROUP_ID IS NULL AND " . $aliasPrefix . "T.GROUP_ID IS NULL)
				OR (PT.GROUP_ID IS NULL AND " . $aliasPrefix . "T.GROUP_ID = 0)
				OR (PT.GROUP_ID = 0 AND " . $aliasPrefix . "T.GROUP_ID IS NULL)
				)";
		}

		unset($filter["ONLY_ROOT_TASKS"], $filter["SAME_GROUP_PARENT"]);

		$params = [
			'USER_ID' => $userId,
			'JOIN_ALIAS' => 'P',
			'SOURCE_ALIAS' => 'PT'
		];

		$optimized = static::tryOptimizeFilter($filter, 'PT', 'PTM_SPEC');
		$sqlSearch = array_merge($sqlSearch, CTasks::GetFilter($optimized['FILTER'], "P"));

		$relatedJoins = static::getRelatedJoins([], $filter, [], $params);

		if (isset($relatedJoins['FULL_SEARCH']))
		{
			$relatedJoins['FULL_SEARCH'] = str_replace('INNER', 'LEFT', $relatedJoins['FULL_SEARCH']);
		}
		if (isset($relatedJoins['COMMENT_SEARCH']))
		{
			$relatedJoins['COMMENT_SEARCH'] = str_replace('INNER', 'LEFT', $relatedJoins['COMMENT_SEARCH']);
		}

		$relatedJoins = array_merge($relatedJoins, $optimized['JOINS']);

		$strSql = "
			SELECT 'x'
			FROM b_tasks PT
			" . implode("\n", $relatedJoins) . "
			WHERE " . implode(" AND ", $sqlSearch) . "
		";

		return $strSql;
	}


	/**
	 * @param array $arFilter
	 * @param array $arParams
	 * @param array $arGroupBy
	 * @return bool|CDBResult
	 */
	public static function GetCount($arFilter=array(), $arParams = array(), $arGroupBy = array())
	{
		/**
		 * @global CDatabase $DB
		 */
		global $DB;

		$bIgnoreDbErrors = false;
		$bSkipUserFields = false;
		$bSkipExtraTables = false;
		$bSkipJoinTblViewed = false;
		$bNeedJoinMembersTable = false;

		if (isset($arParams['bIgnoreDbErrors']))
			$bIgnoreDbErrors = (bool) $arParams['bIgnoreDbErrors'];

		if (isset($arParams['bSkipUserFields']))
			$bSkipUserFields = (bool) $arParams['bSkipUserFields'];

		if (isset($arParams['bSkipExtraTables']))
			$bSkipExtraTables = (bool) $arParams['bSkipExtraTables'];

		if (isset($arParams['bSkipJoinTblViewed']))
			$bSkipJoinTblViewed = (bool) $arParams['bSkipJoinTblViewed'];

		if (isset($arParams['bNeedJoinMembersTable']))
			$bNeedJoinMembersTable = (bool) $arParams['bNeedJoinMembersTable'];

		$disableOptimization = (is_array($arParams) && $arParams['DISABLE_OPTIMIZATION'] === true);
		$disableAccessOptimization = (is_array($arParams) && $arParams['DISABLE_ACCESS_OPTIMIZATION'] === true);

		// in some cases, we can replace filter conditions
		$canUseOptimization = !$disableOptimization && !$bNeedJoinMembersTable;

		if ( ! $bSkipUserFields )
		{
			$obUserFieldsSql = new CUserTypeSQL;
			$obUserFieldsSql->SetEntity("TASKS_TASK", "T.ID");
			$obUserFieldsSql->SetFilter($arFilter);
		}

		if (!is_array($arFilter))
		{
			CTaskAssert::logError('[0x053f6639] expected array in $arFilter');
			$arFilter = array();
		}

		if (isset($arParams['USER_ID']))
			$userID = (int) $arParams['USER_ID'];
		else
			$userID = User::getId();

		static $arFields = array(
			'GROUP_ID'       => 'T.GROUP_ID',
			'CREATED_BY'     => 'T.CREATED_BY',
			'RESPONSIBLE_ID' => 'T.RESPONSIBLE_ID',
			'ACCOMPLICE'     => 'TM.USER_ID',
			'AUDITOR'        => 'TM.USER_ID'
		);

		$strGroupBy = ' ';
		$strSelect  = ' ';

		// ignore unknown fields
		$arGroupBy = array_intersect($arGroupBy, array_keys($arFields));

		if (is_array($arGroupBy) && ! empty($arGroupBy))
		{
			$arGroupByFields = array();
			foreach ($arGroupBy as $fieldName)
			{
				$strSelect = ', ' . $arFields[$fieldName] . ' AS ' . $fieldName;

				if (($fieldName === 'ACCOMPLICE') || ($fieldName === 'AUDITOR'))
					$bNeedJoinMembersTable = true;

				$arGroupByFields[] = $arFields[$fieldName];
			}

			$strGroupBy = ' GROUP BY ' . implode(', ', $arGroupByFields);
		}

		$sourceFilter = $arFilter;

		// try to make some recyclebiny optimizations
		// (later you can remove the following block without getting any logic broken)
		$additionalJoins = '';
		if($canUseOptimization)
		{
			$optimized = static::tryOptimizeFilter($arFilter);
			$arFilter = $optimized['FILTER'];
			$additionalJoins = implode("\n\n", $optimized['JOINS']);
		}

		if (isset($arParams['bUseRightsCheck']))
		{
			$arFilter['CHECK_PERMISSIONS'] = ((bool) $arParams['bUseRightsCheck']) ? 'Y' : 'N';
		}

		$fParams = array(
			'bMembersTableJoined' => $bNeedJoinMembersTable,
			'USER_ID' => $userID,
		);
		$fParams['ENABLE_LEGACY_ACCESS'] = $disableAccessOptimization; // manual legacy access switch

		$arSqlSearch = CTasks::GetFilter(
			$arFilter,
			'',			// $sAliasPrefix
			$fParams
		);
		$arSqlSearch[] = " T.ZOMBIE = 'N' ";

		$ufJoin = ' ';
		if ( ! $bSkipUserFields )
		{
			$r = $obUserFieldsSql->GetFilter();
			if (strlen($r) > 0)
			{
				$arSqlSearch[] = "(".$r.")";
			}

			$ufJoin .= $obUserFieldsSql->GetJoin("T.ID");
		}

		$strSql = "
			SELECT
				COUNT(".($canUseOptimization ? 'distinct ' : '')."T.ID) AS CNT " . $strSelect . "
			FROM ";

		if ($bNeedJoinMembersTable)
			$strSql .= "b_tasks_member TM \n INNER JOIN b_tasks T ON T.ID = TM.TASK_ID ";
		else
			$strSql .= "b_tasks T ";

		if ( ! $bSkipExtraTables )
		{
			$strSql .= " INNER JOIN b_user CU ON CU.ID = T.CREATED_BY
				INNER JOIN b_user RU ON RU.ID = T.RESPONSIBLE_ID ";
		}

		if (!$bSkipJoinTblViewed)
		{
			$viewedBy = static::getViewedBy($arFilter, $userID);

			$strSql .= "\n LEFT JOIN
				b_tasks_viewed TV ON TV.TASK_ID = T.ID AND TV.USER_ID = " . $viewedBy;
		}

		$useAccessAsWhere = !$disableAccessOptimization;
		if ($useAccessAsWhere && static::needAccessRestriction($arFilter, $fParams))
		{
			$fParams['APPLY_MEMBER_FILTER'] = static::makePossibleForwardedMemberFilter($sourceFilter);
			$fParams['APPLY_FILTER'] = static::makePossibleForwardedFilter($sourceFilter);
			$fParams['PUT_SELECT_INTO_WHERE'] = true;

			$accessSql = '';
			$accessSql = static::appendJoinRights($accessSql, $fParams);

			if ($accessSql !== '')
			{
				$arSqlSearch[] = $accessSql;
			}
		}

		$strSql .= "
			" . $additionalJoins . "
			" . $ufJoin . "
			" . "WHERE " . implode(" AND ", $arSqlSearch) . "
			" . $strGroupBy;

		$res = $DB->Query($strSql, $bIgnoreDbErrors, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $res;
	}

	private static function makePossibleForwardedMemberFilter($filter)
	{
		$result = array();

		if (is_array($filter) && !empty($filter))
		{
			// cannot forward filer with LOGIC OR or LOGIC NOT
			if (array_key_exists('LOGIC', $filter) && $filter['LOGIC'] != 'AND')
			{
				return $result;
			}
			if (array_key_exists('::LOGIC', $filter) && $filter['::LOGIC'] != 'AND')
			{
				return $result;
			}

			/** @see \CTasks::GetSqlByFilter() */
			if (array_key_exists('AUDITOR', $filter)) // we have equality to AUDITOR, not negation
			{
				$result[] = [
					'=TYPE' => 'U',
					'=USER_ID' => $filter['AUDITOR'],
				];
			}
			else if (array_key_exists('ACCOMPLICE', $filter)) // we have equality to ACCOMPLICE, not negation
			{
				$result[] = [
					'=TYPE' => 'A',
					'=USER_ID' => $filter['ACCOMPLICE'],
				];
			}
		}

		return $result;
	}

	private static function appendJoinRights($sql, $arParams)
	{
		$arParams['THIS_TABLE_ALIAS'] = 'T';

		$access = \Bitrix\Tasks\Internals\RunTime\Task::getAccessCheckSql($arParams);
		$accessSql = $access['sql'];

		if ($accessSql != '')
		{
			if (isset($arParams['PUT_SELECT_INTO_WHERE']) && $arParams['PUT_SELECT_INTO_WHERE'])
			{
				$sql .= "T.ID IN ($accessSql)";
			}
			else
			{
				$sql .= "\n\n/*access BEGIN*/\n\n inner join ($accessSql) TASKS_ACCESS on T.ID = TASKS_ACCESS.TASK_ID\n\n/*access END*/\n\n";
			}
		}

		return $sql;
	}

	/**
	 * Optimizes filter
	 *
	 * @param array $filter
	 * @param $sourceTableAlias
	 * @param $joinTableAlias
	 * @return array
	 */
	private static function tryOptimizeFilter(array $filter, $sourceTableAlias = 'T', $joinTableAlias = 'TM')
	{
		$additionalJoins = [];
		$roleKey = '::SUBFILTER-ROLEID';

		$joinAlias = $joinTableAlias;
		$sourceAlias = $sourceTableAlias;

		// get rid of ::SUBFILTER-ROOT if can
		if (array_key_exists('::SUBFILTER-ROOT', $filter) && count($filter) == 1)
		{
			if ($filter['::LOGIC'] != 'OR')
			{
				// we have only one element in the root, and logic is not "OR". then we could remove subfilter-root
				$filter = $filter['::SUBFILTER-ROOT'];
			}
		}

		// we can optimize only if there is no "or-logic"
		if ($filter['::LOGIC'] != 'OR' && $filter['LOGIC'] != 'OR')
		{
			// MEMBER
			if (array_key_exists('MEMBER', $filter) || isset($filter[$roleKey]) && array_key_exists('MEMBER', $filter[$roleKey]))
			{
				if (array_key_exists('MEMBER', $filter))
				{
					$member = intval($filter['MEMBER']);
					unset($filter['MEMBER']);
				}
				else
				{
					$member = intval($filter[$roleKey]['MEMBER']);
					unset($filter[$roleKey]);
				}

				$additionalJoins[] = "INNER JOIN b_tasks_member {$joinAlias} ON {$joinAlias}.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}.USER_ID = {$member}";
			}
			// DOER
			else if (array_key_exists('DOER', $filter))
			{
				$doer = intval($filter['DOER']);
				unset($filter['DOER']);

				$additionalJoins[] = "INNER JOIN b_tasks_member {$joinAlias} ON {$joinAlias}.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}.USER_ID = {$doer} AND {$joinAlias}.TYPE in ('R', 'A')";
			}
			// RESPONSIBLE
			else if (isset($filter[$roleKey]) && array_key_exists('=RESPONSIBLE_ID', $filter[$roleKey]))
			{
				$responsible = $filter[$roleKey]['=RESPONSIBLE_ID'];
				unset($filter[$roleKey]);

				$additionalJoins[] = "INNER JOIN b_tasks_member {$joinAlias} ON {$joinAlias}.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}.USER_ID = {$responsible} AND {$joinAlias}.TYPE = 'R'";
			}
			// CREATOR
			else if (isset($filter[$roleKey]) && array_key_exists('=CREATED_BY', $filter[$roleKey]))
			{
				$creator = $filter[$roleKey]['=CREATED_BY'];
				unset($filter[$roleKey]['=CREATED_BY']);

				if (!empty($filter[$roleKey]))
				{
					$filter += $filter[$roleKey];
				}
				unset($filter[$roleKey]);

				$additionalJoins[] = "INNER JOIN b_tasks_member {$joinAlias} ON {$joinAlias}.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}.USER_ID = {$creator} AND {$joinAlias}.TYPE = 'O'";
			}
			// ACCOMPLICE
			else if (array_key_exists('ACCOMPLICE', $filter) || isset($filter[$roleKey]) && array_key_exists('=ACCOMPLICE', $filter[$roleKey]))
			{
				if (array_key_exists('ACCOMPLICE', $filter))
				{
					if (!is_array($filter['ACCOMPLICE'])) // we have single value, not array which will cause "in ()" instead of =
					{
						$accomplice = intval($filter['ACCOMPLICE']);
						unset($filter['ACCOMPLICE']);

						$additionalJoins[] = "INNER JOIN b_tasks_member {$joinAlias} ON {$joinAlias}.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}.USER_ID = {$accomplice} AND {$joinAlias}.TYPE = 'A'";
					}
				}
				else
				{
					if (!is_array($filter[$roleKey]['=ACCOMPLICE']))
					{
						$accomplice = intval($filter[$roleKey]['=ACCOMPLICE']);
						unset($filter[$roleKey]);

						$additionalJoins[] = "INNER JOIN b_tasks_member {$joinAlias} ON {$joinAlias}.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}.USER_ID = {$accomplice} AND {$joinAlias}.TYPE = 'A'";
					}
				}
			}
			// AUDITOR
			else if (array_key_exists('AUDITOR', $filter) || isset($filter[$roleKey]) && array_key_exists('=AUDITOR', $filter[$roleKey]))
			{
				if (array_key_exists('AUDITOR', $filter))
				{
					if (!is_array($filter['AUDITOR'])) // we have single value, not array which will cause "in ()" instead of =
					{
						$auditor = intval($filter['AUDITOR']);
						unset($filter['AUDITOR']);

						$additionalJoins[] = "INNER JOIN b_tasks_member {$joinAlias} ON {$joinAlias}.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}.USER_ID = {$auditor} AND {$joinAlias}.TYPE = 'U'";
					}
				}
				else
				{
					if (!is_array($filter[$roleKey]['=AUDITOR']))
					{
						$auditor = intval($filter[$roleKey]['=AUDITOR']);
						unset($filter[$roleKey]);

						$additionalJoins[] = "INNER JOIN b_tasks_member {$joinAlias} ON {$joinAlias}.TASK_ID = {$sourceAlias}.ID AND {$joinAlias}.USER_ID = {$auditor} AND {$joinAlias}.TYPE = 'U'";
					}
				}
			}
		}

		return [
			'FILTER' => $filter,
			'JOINS' => $additionalJoins,
		];
	}

	/**
	 * Gets user's id task list we are looking at
	 *
	 * @param $filter
	 * @param $currentUserId
	 * @return mixed
	 */
	private static function getViewedUserId($filter, $currentUserId)
	{
		$viewedBy = static::getViewedBy($filter, $currentUserId);

		if ($viewedBy !== $currentUserId)
		{
			$viewedUserId = $viewedBy;
		}
		else
		{
			if (array_key_exists('::SUBFILTER-ROLEID', $filter) && !empty($filter['::SUBFILTER-ROLEID']))
			{
				$viewedUserId = current($filter['::SUBFILTER-ROLEID']);
			}
			else
			{
				$viewedUserId = $currentUserId;
			}
		}

		return $viewedUserId;
	}

	/**
	 * Get user id b_tasks_viewed table joined on by filter or default value if filter haven't VIEWED_BY option
	 *
	 * @param $filter
	 * @param $defaultValue
	 * @return int
	 */
	private static function getViewedBy($filter, $defaultValue)
	{
		$viewedBy = $defaultValue;

		if (
			array_key_exists('::SUBFILTER-PROBLEM', $filter) &&
			array_key_exists('VIEWED_BY', $filter['::SUBFILTER-PROBLEM']) &&
			intval($filter['::SUBFILTER-PROBLEM']['VIEWED_BY'])
		)
		{
			$viewedBy = intval($filter['::SUBFILTER-PROBLEM']['VIEWED_BY']);
		}
		else if (array_key_exists('VIEWED_BY', $filter) && intval($filter['VIEWED_BY']))
		{
			$viewedBy = intval($filter['VIEWED_BY']);
		}

		return $viewedBy;
	}

	public static function getUsersViewedTask($taskId)
	{
		global $DB;

		$taskId = (int) $taskId;

		$res = $DB->query(
			"SELECT USER_ID
			FROM b_tasks_viewed
			WHERE TASK_ID = " . $taskId,
			true	// ignore DB errors
		);

		if ($res === false)
			throw new TasksException ('', TasksException::TE_SQL_ERROR);

		$arUsers = array();

		while ($ar = $res->fetch())
			$arUsers[] = (int) $ar['USER_ID'];

		return ($arUsers);
	}


	public static function GetCountInt($arFilter=array(), $arParams = array())
	{
		$count = 0;

		$rsCount = CTasks::GetCount($arFilter, $arParams);
		if ($arCount = $rsCount->Fetch())
		{
			$count = intval($arCount["CNT"]);
		}

		return $count;
	}


	public static function GetChildrenCount($filter, $parentIds)
	{
		if (!$parentIds)
		{
			return false;
		}

		global $DB;

		$obUserFieldsSql = new CUserTypeSQL;
		$obUserFieldsSql->SetEntity("TASKS_TASK", "T.ID");
		$obUserFieldsSql->SetFilter($filter);

		if (!is_array($filter))
		{
			$filter = [];
		}

		$userId = User::getId();

		$filter["PARENT_ID"] = $parentIds;
		unset($filter["ONLY_ROOT_TASKS"]);

		$sqlSearch = CTasks::GetFilter($filter);
		$sqlSearch[] = " T.ZOMBIE = 'N' ";

		$r = $obUserFieldsSql->GetFilter();
		if (strlen($r) > 0)
		{
			$sqlSearch[] = "(".$r.")";
		}

		$relatedJoins = static::getRelatedJoins([], $filter, [], ['USER_ID' => $userId]);
		$relatedJoins = implode("\n", $relatedJoins);

		$strSql = "
			SELECT T.PARENT_ID, COUNT(T.ID) AS CNT
			FROM (";

		$strSql .= "
			SELECT T.PARENT_ID AS PARENT_ID, T.ID
			FROM b_tasks T
			INNER JOIN b_user CU ON CU.ID = T.CREATED_BY
			INNER JOIN b_user RU ON RU.ID = T.RESPONSIBLE_ID
			" . $relatedJoins . "
			" . $obUserFieldsSql->GetJoin("T.ID") . "
			" . (sizeof($sqlSearch)? "WHERE " . implode(" AND ", $sqlSearch) : "") . "
			GROUP BY T.ID
		";

		$strSql .= ") T
			GROUP BY T.PARENT_ID
		";

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $res;
	}

	/**
	 *
	 * @access private
	 */
	public static function GetOriginatorsByFilter($arFilter, $loggedInUserId)
	{
		return static::GetFieldGrouppedByFilter('CREATED_BY', $arFilter, $loggedInUserId);
	}

	/**
	 *
	 * @access private
	 */
	public static function GetResponsiblesByFilter($arFilter, $loggedInUserId)
	{
		return static::GetFieldGrouppedByFilter('RESPONSIBLE_ID', $arFilter, $loggedInUserId);
	}

	private static function GetFieldGrouppedByFilter($column, $arFilter, $loggedInUserId)
	{
		CTaskAssert::assert($loggedInUserId && is_array($arFilter));

		$arSqlSearch = CTasks::GetFilter($arFilter, '', array('USER_ID' => $loggedInUserId));
		$arSqlSearch[] = " T.ZOMBIE = 'N' ";

		$keysFiltered = CTasks::GetFilteredKeys($arFilter);

		$bNeedJoinFavoritesTable = in_array('FAVORITE', $keysFiltered, true);

		$sql = "SELECT T.".$column." AS USER_ID, COUNT(T.ID) AS TASKS_CNT
			FROM b_tasks T
			LEFT JOIN b_tasks_viewed TV ON TV.TASK_ID = T.ID AND TV.USER_ID = " . $loggedInUserId . "

			". ($bNeedJoinFavoritesTable ? "
				LEFT JOIN ".FavoriteTable::getTableName()." FVT ON FVT.TASK_ID = T.ID and FVT.USER_ID = '".$loggedInUserId/*always int, no sqli*/."'
				" : "")."

			WHERE " . implode('AND', $arSqlSearch)
			. " GROUP BY T.".$column;

		return $GLOBALS['DB']->query($sql);
	}

	public static function GetSubordinateSql($sAliasPrefix="", $arParams = array(), $behaviour = array())
	{
		$arDepsIDs = Integration\Intranet\Department::getSubordinateIds($arParams['USER_ID'], true);

		if (sizeof($arDepsIDs))
		{
			$rsDepartmentField = CUserTypeEntity::GetList(array(), array("ENTITY_ID" => "USER", "FIELD_NAME" => "UF_DEPARTMENT"));
			if ($arDepartmentField = $rsDepartmentField->Fetch())
			{
				return CTasks::GetDeparmentSql($arDepsIDs, $sAliasPrefix, $arParams, $behaviour);
			}
		}

		return false;
	}


	function GetDeparmentSql($arDepsIDs, $sAliasPrefix="", $arParams = array(), $behaviour = array())
	{
		global $DBType;

		if (!is_array($arDepsIDs))
		{
			$arDepsIDs = array(intval($arDepsIDs));
		}
		else
		{
			$arDepsIDs = array_map('intval', $arDepsIDs);
		}

		if(!is_array($behaviour))
		{
			$behaviour = array();
		}
		if(!isset($behaviour['ALIAS']))
		{
			$behaviour['ALIAS'] = $sAliasPrefix;
		}
		if(!isset($arParams['FIELDS']))
		{
			$arParams['FIELDS'] = array();
		}

		$a = $sAliasPrefix;
		$b = $behaviour;
		$f =& $arParams['FIELDS'];

		//static::placeFieldSql('CREATED_BY', 	$b, $f)

		$rsDepartmentField = CUserTypeEntity::GetList(array(), array("ENTITY_ID" => "USER", "FIELD_NAME" => "UF_DEPARTMENT"));
		$cntOfDepartments = count($arDepsIDs);
		if ($cntOfDepartments && $arDepartmentField = $rsDepartmentField->Fetch())
		{
			if (
				($DBType === 'oracle')
				&& ($valuesLimit = 1000)
				&& ($cntOfDepartments > $valuesLimit)
			)
			{
				$arConstraints = array();
				$sliceIndex = 0;
				while ($sliceIndex < $cntOfDepartments)
				{
					$arConstraints[] = $sAliasPrefix . 'BUF1.VALUE_INT IN ('
						. implode(',', array_slice($arDepsIDs, $sliceIndex, $valuesLimit))
						. ')';

					$sliceIndex += $valuesLimit;
				}

				$strConstraint = '(' . implode(' OR ', $arConstraints) . ')';
			}
			else
				$strConstraint = $sAliasPrefix . "BUF1.VALUE_INT IN (" . implode(",", $arDepsIDs) . ")";

			// EXISTS!
			$strSql = "
				SELECT
					'x'
				FROM
					b_utm_user ".$sAliasPrefix."BUF1
				WHERE
					".$sAliasPrefix."BUF1.FIELD_ID = ".$arDepartmentField["ID"]."
				AND
					(" . $sAliasPrefix . "BUF1.VALUE_ID = " . static::placeFieldSql('RESPONSIBLE_ID', $b, $f)."
						OR " . $sAliasPrefix . "BUF1.VALUE_ID = " . static::placeFieldSql('CREATED_BY', $b, $f) . "
						OR EXISTS(
							SELECT 'x'
							FROM b_tasks_member ".$sAliasPrefix."DSTM
							WHERE ".$sAliasPrefix."DSTM.TASK_ID = ".static::placeFieldSql('ID', $b, $f)."
								AND ".$sAliasPrefix."DSTM.USER_ID = " . $sAliasPrefix . "BUF1.VALUE_ID
						)
					)
				AND
					" . $strConstraint . "
			";

			return $strSql;
		}

		return false;
	}


	/**
	 * Use CTaskItem->update() instead (with key 'ACCOMPLICES')
	 *
	 * @deprecated
	 */
	function AddAccomplices($ID, $arAccompleces = array())
	{
		if ($arAccompleces)
		{
			$arAccompleces = array_unique($arAccompleces);
			foreach ($arAccompleces as $accomplice)
			{
				$arMember = array(
					"TASK_ID" => $ID,
					"USER_ID" => $accomplice,
					"TYPE" => "A"
				);
				$member = new CTaskMembers();
				$member->Add($arMember);
			}
		}
	}


	/**
	 * Use CTaskItem->update() instead (with key 'AUDITORS')
	 *
	 * @deprecated
	 */
	function AddAuditors($ID, $arAuditors = array())
	{
		if ($arAuditors)
		{
			$arAuditors = array_unique($arAuditors);
			foreach ($arAuditors as $auditor)
			{
				$arMember = array(
					"TASK_ID" => $ID,
					"USER_ID" => $auditor,
					"TYPE" => "U"
				);
				$member = new CTaskMembers();
				$member->Add($arMember);
			}
		}
	}


	function AddFiles($ID, $arFiles = array(), $arParams = array())
	{
		$arFilesIds = array();

		$userId = null;

		$bCheckRightsOnFiles = false;

		if (is_array($arParams))
		{
			if (isset($arParams['USER_ID']) && ($arParams['USER_ID'] > 0))
				$userId = (int) $arParams['USER_ID'];

			if (isset($arParams['CHECK_RIGHTS_ON_FILES']))
				$bCheckRightsOnFiles = $arParams['CHECK_RIGHTS_ON_FILES'];
		}

		if ($userId === null)
		{
			$userId = User::getId();
			if(!$userId)
			{
				$userId = User::getAdminId();
			}
		}

		if ($arFiles)
		{
			foreach ($arFiles as $file)
				$arFilesIds[] = (int) $file;

			if (count($arFilesIds))
			{
				CTaskFiles::AddMultiple(
					$ID,
					$arFilesIds,
					array(
						'USER_ID'               => $userId,
						'CHECK_RIGHTS_ON_FILES' => $bCheckRightsOnFiles
					)
				);
			}
		}
	}

	/**
	 * Detect tags in data array.
	 * @param array $fields Data array.
	 * @return array
	 */
	private function detectTags(array &$fields)
	{
		$newTags = array();
		$searchFields = array('TITLE', 'DESCRIPTION');

		foreach ($searchFields as $code)
		{
			if (
				isset($fields[$code]) &&
				preg_match_all('/\s#([^\s,\[\]<>]+)/is', ' ' . $fields[$code], $tags)
			)
			{
				$newTags = array_merge($newTags, $tags[1]);
			}
		}

		return $newTags;
	}

	function AddTags($ID, $USER_ID, $arTags = array(), $effectiveUserId = null)
	{
		// delete previous
		$oTag = new CTaskTags();
		$oTag->DeleteByTaskID($ID);

		if ($arTags)
		{
			if (!is_array($arTags))
			{
				$arTags = explode(",", $arTags);
			}
			$arTags = array_unique(array_map("trim", $arTags));

			foreach ($arTags as $tag)
			{
				$arTag = array(
					"TASK_ID" => $ID,
					"USER_ID" => $USER_ID,
					"NAME" => $tag
				);
				$oTag = new CTaskTags();
				$oTag->Add($arTag, $effectiveUserId);
			}
		}
	}


	function AddPrevious($ID, $arPrevious = array())
	{
		$oDependsOn = new CTaskDependence();
		$oDependsOn->DeleteByTaskID($ID);

		if ($arPrevious)
		{
			$arPrevious = array_unique(array_map('intval', $arPrevious));

			foreach ($arPrevious as $dependsOn)
			{
				$arDependsOn = array(
					"TASK_ID" => $ID,
					"DEPENDS_ON_ID" => $dependsOn
				);
				$oDependsOn = new CTaskDependence();
				$oDependsOn->Add($arDependsOn);
			}
		}
	}


	function Index($arTask, $tags)
	{
		$arTask['SE_TAG'] = $tags;
		Integration\Search\Task::index($arTask);
	}


	function OnSearchReindex($NS=array(), $oCallback=NULL, $callback_method="")
	{
		$arResult = array();
		$arOrder  = array('ID' => 'ASC');
		$arFilter = array();

		if (isset($NS['MODULE']) && ($NS['MODULE'] === 'tasks')
			&& isset($NS['ID']) && ($NS['ID'] > 0)
		)
		{
			$arFilter['>ID'] = (int) $NS['ID'];
		}
		else
			$arFilter['>ID'] = 0;


		$rsTasks = CTasks::GetList($arOrder, $arFilter);
		while ($arTask = $rsTasks->Fetch())
		{
			$rsTags = CTaskTags::GetList(array(), array("TASK_ID" => $arTask["ID"]));
			$arTags = array();
			while ($arTag = $rsTags->Fetch())
			{
				$arTags[] = $arTag["NAME"];
			}

			$arTask["ACCOMPLICES"] = $arTask["AUDITORS"] = array();
			$rsMembers = CTaskMembers::GetList(array(), array("TASK_ID" => $arTask["ID"]));
			while ($arMember = $rsMembers->Fetch())
			{
				if ($arMember["TYPE"] == "A")
				{
					$arTask["ACCOMPLICES"][] = $arMember["USER_ID"];
				}
				elseif ($arMember["TYPE"] == "U")
				{
					$arTask["AUDITORS"][] = $arMember["USER_ID"];
				}
			}

			// todo: get path form socnet
			if ($arTask["GROUP_ID"] > 0)
			{
				$path = str_replace("#group_id#", $arTask["GROUP_ID"], COption::GetOptionString("tasks", "paths_task_group_entry", "/workgroups/group/#group_id#/tasks/task/view/#task_id#/", $arTask["SITE_ID"]));
			}
			else
			{
				$path = str_replace("#user_id#", $arTask["RESPONSIBLE_ID"], COption::GetOptionString("tasks", "paths_task_user_entry", "/company/personal/user/#user_id#/tasks/task/view/#task_id#/", $arTask["SITE_ID"]));
			}
			$path = str_replace("#task_id#", $arTask["ID"], $path);

			$arPermissions = CTasks::__GetSearchPermissions($arTask);
			$Result = array(
				"ID" => $arTask["ID"],
				"LAST_MODIFIED" => $arTask["CHANGED_DATE"] ? $arTask["CHANGED_DATE"] : $arTask["CREATED_DATE"],
				"TITLE" => $arTask["TITLE"],
				"BODY" => strip_tags($arTask["DESCRIPTION"]) ? strip_tags($arTask["DESCRIPTION"]) : $arTask["TITLE"],
				"TAGS" => implode(",", $arTags),
				"URL" => $path,
				"SITE_ID" => $arTask["SITE_ID"],
				"PERMISSIONS" => $arPermissions,
			);

			if ($oCallback)
			{
				$index_res = call_user_func(array($oCallback, $callback_method), $Result);
				if(!$index_res)
					return $Result["ID"];
			}
			else
				$arResult[] = $Result;

			CTasks::UpdateForumTopicIndex($arTask["FORUM_TOPIC_ID"], "U", $arTask["RESPONSIBLE_ID"], "tasks", "view_all", $path, $arPermissions, $arTask["SITE_ID"]);
		}

		if ($oCallback)
			return false;

		return $arResult;
	}


	function UpdateForumTopicIndex($topic_id, $entity_type, $entity_id, $feature, $operation, $path, $arPermissions, $siteID)
	{
		global $DB;

		if(!CModule::IncludeModule("forum"))
			return;

		$topic_id = intval($topic_id);

		$rsForumTopic = $DB->Query("SELECT FORUM_ID FROM b_forum_topic WHERE ID = ".$topic_id);
		$arForumTopic = $rsForumTopic->Fetch();
		if(!$arForumTopic)
			return;

		CSearch::ChangePermission("forum", $arPermissions, false, $arForumTopic["FORUM_ID"], $topic_id);

		$rsForumMessages = $DB->Query("
			SELECT ID
			FROM b_forum_message
			WHERE TOPIC_ID = ".$topic_id."
		");
		while($arMessage = $rsForumMessages->Fetch())
		{
			CSearch::ChangeSite("forum", array($siteID => $path), $arMessage["ID"]);
		}

		$arParams = array(
			"feature_id" => "S".$entity_type."_".$entity_id."_".$feature."_".$operation,
			"socnet_user" => $entity_id,
		);

		CSearch::ChangeIndex("forum", array("PARAMS" => $arParams), false, $arForumTopic["FORUM_ID"], $topic_id);
	}


	public static function __GetSearchPermissions($arTask)
	{
		$arPermissions = array();

		// check task members
		if (!isset($arTask['ACCOMPLICES']) || !isset($arTask['AUDITORS']))
		{
			if (!isset($arTask['ACCOMPLICES']))
				$arTask['ACCOMPLICES'] = array();
			if (!isset($arTask['AUDITORS']))
				$arTask['AUDITORS'] = array();
			$rsMembers = CTaskMembers::GetList(array(), array("TASK_ID" => $arTask["ID"]));
			while ($arMember = $rsMembers->Fetch())
			{
				if ($arMember["TYPE"] == "A")
					$arTask["ACCOMPLICES"][] = $arMember["USER_ID"];
				elseif ($arMember["TYPE"] == "U")
					$arTask["AUDITORS"][] = $arMember["USER_ID"];
			}
		}

		// group id is set, then take permissions from socialnetwork settings
		if ($arTask["GROUP_ID"] > 0 && CModule::IncludeModule("socialnetwork"))
		{
			$prefix = "SG".$arTask["GROUP_ID"]."_";
			$letter = CSocNetFeaturesPerms::GetOperationPerm(SONET_ENTITY_GROUP, $arTask["GROUP_ID"], "tasks", "view_all");
			switch($letter)
			{
				case "N"://All
					$arPermissions[] = 'G2';
					break;
				case "L"://Authorized
					$arPermissions[] = 'AU';
					break;
				case "K"://Group members includes moderators and admins
					$arPermissions[] = $prefix.'K';
				case "E"://Moderators includes admins
					$arPermissions[] = $prefix.'E';
				case "A"://Admins
					$arPermissions[] = $prefix.'A';
					break;
			}
		}

		// if neither "all users" nor "authorized user" enabled, turn permissions on at least for task members
		if (!in_array("G2", $arPermissions) && !in_array("AU", $arPermissions))
		{
			if (!$arTask["ACCOMPLICES"])
				$arTask["ACCOMPLICES"] = array();

			if (!$arTask["AUDITORS"])
				$arTask["AUDITORS"] = array();

			$arParticipants = array_unique(array_merge(array($arTask["CREATED_BY"], $arTask["RESPONSIBLE_ID"]), $arTask["ACCOMPLICES"], $arTask["AUDITORS"]));
			foreach($arParticipants as $userId)
				$arPermissions[] = "U".$userId;

			$arDepartments = array();

			$arSubUsers = array_unique(array($arTask['RESPONSIBLE_ID'], $arTask['CREATED_BY']));

			foreach ($arSubUsers as $subUserId)
			{
				$arUserDepartments = CTasks::GetUserDepartments($subUserId);

				if (is_array($arUserDepartments) && count($arUserDepartments))
					$arDepartments = array_merge($arDepartments, $arUserDepartments);
			}

			$arDepartments = array_unique($arDepartments);
			$arManagersTmp = CTasks::GetDepartmentManagers($arDepartments);

			if (is_array($arManagersTmp))
			{
				$arManagers = array_keys($arManagersTmp);

				// Remove $arSubUsers from $arManagers
				$arManagers = array_diff($arManagers, $arSubUsers);

				foreach($arManagers as $userId)
				{
					if (!in_array("U".$userId, $arPermissions))
						$arPermissions[] = "U".$userId;
				}
			}
		}

		// adimins always allowed to view search result
		$arPermissions[] = 'G1';

		return $arPermissions;
	}

	/**
	 * Agent handler for repeating tasks.
	 * Create new task based on given template.
	 *
	 * @param integer $templateId - id of task template
	 * @param integer $flipFlop unused
	 * @param mixed[] $debugHere
	 *
	 * @return string empty string.
	 * @deprecated
	 */
	public static function RepeatTaskByTemplateId ($templateId, $flipFlop = 1, array &$debugHere = array())
	{
		return Replicator\Task\FromTemplate::repeatTask(
			$templateId,
			array(
				// todo: get rid of use of CTasks one day...
				'AGENT_NAME_TEMPLATE' => 'CTasks::RepeatTaskByTemplateId(#ID#);',
				'RESULT' => &$debugHere,
			)
		);
	}


	/**
	 * @deprecated
	 *
	 * This function is deprecated and strongly discouraged to be used.
	 * But it will not be removed, because some agents can be still active for
	 * using this function in future for at least one year.
	 * Current date is: 06 Oct 2012, Sat. Code written, but updater not built.
	 *
	 * @param $TASK_ID
	 * @param string $time
	 * @return string originally always returns an empty string
	 */
	function RepeatTask($TASK_ID, /** @noinspection PhpUnusedParameterInspection */ $time="")
	{
		$rsTemplate = CTaskTemplates::GetList(
			array(),
			array('TASK_ID' => (int) $TASK_ID)
		);

		if ( ! ($arTemplate = $rsTemplate->Fetch()) )
			return ('');

		// Redirect call to new function
		if (isset($arTemplate['ID']) && ($arTemplate['ID'] > 0))
			self::RepeatTaskByTemplateId( (int) $arTemplate['ID'] );

		return ('');
	}

	/**
	 * @param $arParams
	 * @param bool $template
	 * @param integer $agentTime Time in server timezone
	 * @return bool|string
	 */
	public static function getNextTime($arParams, $template = false, $agentTime = false)
	{
		if(!is_array($arParams))
		{
			return false;
		}

		$templateData = false;
		if(is_array($template))
		{
			$templateData = $template;
		}
		elseif($template = intval($template))
		{
			$item = \CTaskTemplates::getList(array(), array('ID' => $template), array(), array(), array('CREATED_BY', 'REPLICATE_PARAMS', 'TPARAM_REPLICATION_COUNT'))->fetch();
			if($item)
			{
				$templateData = $item;
			}
		}

		if(!$templateData)
		{
			$templateData = array();
		}
		$templateData['REPLICATE_PARAMS'] = $arParams;

		$result = Replicator\Task\FromTemplate::getNextTime($templateData, $agentTime);
		$rData = $result->getData();

		return $rData['TIME'] == '' ? false : $rData['TIME'];
	}

	public static function CanGivenUserDelete($userId, $taskCreatedBy, $taskGroupId, /** @noinspection PhpUnusedParameterInspection */ $site_id = SITE_ID)
	{
		$userId = (int) $userId;
		$taskGroupId = (int) $taskGroupId;

		$site_id = null;	// not used, left in function declaration for backward compatibility

		if ($userId <= 0)
			throw new TasksException();

		if (
			CTasksTools::IsAdmin($userId)
			|| CTasksTools::IsPortalB24Admin($userId)
			|| ($userId == $taskCreatedBy)
		)
		{
			return (true);
		}
		elseif (($taskGroupId > 0) && CModule::IncludeModule('socialnetwork'))
		{
			return (boolean) CSocNetFeaturesPerms::CanPerformOperation($userId, SONET_ENTITY_GROUP, $taskGroupId, "tasks", "delete_tasks");
		}

		return false;
	}


	public static function CanCurrentUserDelete($task, $site_id = SITE_ID)
	{
		if (!$userID = User::getId()) // wtf?
		{
			return false;
		}

		return (self::CanGivenUserDelete($userID, $task['CREATED_BY'], $task['GROUP_ID'], $site_id));
	}


	public static function CanGivenUserEdit($userId, $taskCreatedBy, $taskGroupId, /** @noinspection PhpUnusedParameterInspection */ $site_id = SITE_ID)
	{
		$userId = (int) $userId;
		$taskGroupId = (int) $taskGroupId;

		$site_id = null;	// not used, left in function declaration for backward compatibility    /** @noinspection PhpUnusedParameterInspection */

		if ($userId <= 0)
			throw new TasksException();

		if (
			CTasksTools::IsAdmin($userId)
			|| CTasksTools::IsPortalB24Admin($userId)
			|| ($userId == $taskCreatedBy)
		)
		{
			return (true);
		}
		elseif (($taskGroupId > 0) && CModule::IncludeModule('socialnetwork'))
		{
			return (boolean) CSocNetFeaturesPerms::CanPerformOperation($userId, SONET_ENTITY_GROUP, $taskGroupId, "tasks", "edit_tasks");
		}

		return false;
	}


	public static function CanCurrentUserEdit($task, $site_id = SITE_ID)
	{
		if (!$userID = User::getId())
		{
			return false;
		}

		return (self::CanGivenUserEdit($userID, $task['CREATED_BY'], $task['GROUP_ID'], $site_id));
	}


	public static function UpdateViewed($TASK_ID, $USER_ID)
	{
		self::__updateViewed($TASK_ID, $USER_ID);
	}

	public static function __updateViewed($TASK_ID, $USER_ID, $onTaskAdd = false)
	{
		$USER_ID = (int) $USER_ID;
		$TASK_ID = (int) $TASK_ID;

		$list = \Bitrix\Tasks\Internals\Task\ViewedTable::getList(array(
			"select" => array("TASK_ID", "USER_ID"),
			"filter" => array(
				"=TASK_ID" => $TASK_ID,
				"=USER_ID" => $USER_ID,
			),
		));
		if ($item = $list->fetch())
		{
			\Bitrix\Tasks\Internals\Task\ViewedTable::update($item, array(
				"VIEWED_DATE" => new \Bitrix\Main\Type\DateTime(),
			));
		}
		else
		{
			\Bitrix\Tasks\Internals\Task\ViewedTable::add(array(
				"TASK_ID" => $TASK_ID,
				"USER_ID" => $USER_ID,
				"VIEWED_DATE" => new \Bitrix\Main\Type\DateTime(),
			));
		}

		//CTaskCountersProcessor::onAfterTaskViewedFirstTime($TASK_ID, $USER_ID, $onTaskAdd);
		\Bitrix\Tasks\Internals\Counter::onAfterTaskViewedFirstTime($TASK_ID, $USER_ID, $onTaskAdd);

		$event = new \Bitrix\Main\Event(
			'tasks',
			'onTaskUpdateViewed',
			array(
				'taskId' => $TASK_ID,
				'userId' => $USER_ID
			)
		);
		$event->send();
	}

	function GetUpdatesCount($arViewed)
	{
		global $DB;
		if ($userID = User::getId())
		{
			$arSqlSearch = array();
			$arUpdatesCount = array();
			foreach($arViewed as $key=>$val)
			{
				$arSqlSearch[] = "(CREATED_DATE > " . $DB->CharToDateFunction($val) . " AND TASK_ID = " . (int) $key . ")";
				$arUpdatesCount[$key] = 0;
			}

			if ( ! empty($arSqlSearch) )
			{
				$strSql = "
					SELECT
						TL.TASK_ID AS TASK_ID,
						COUNT(TL.TASK_ID) AS CNT
					FROM
						b_tasks_log TL
					WHERE
						USER_ID != " . $userID . "
						AND (
						".implode(" OR ", $arSqlSearch)."
						)
					GROUP BY
						TL.TASK_ID
				";

				$rsUpdatesCount = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				while($arUpdate = $rsUpdatesCount->Fetch())
				{
					$arUpdatesCount[$arUpdate["TASK_ID"]] = $arUpdate["CNT"];
				}

				return $arUpdatesCount;
			}
		}

		return false;
	}


	function GetFilesCount($arTasksIDs)
	{
		global $DB;

		$arFilesCount = array();

		$arTasksIDs = array_filter($arTasksIDs);

		if (sizeof($arTasksIDs))
		{
			$strSql = "
				SELECT
					TF.TASK_ID,
					COUNT(TF.FILE_ID) AS CNT
				FROM
					b_tasks_file TF
				WHERE
					TF.TASK_ID IN (".implode(",", $arTasksIDs).")
			";
			$rsFilesCount = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while($arFile = $rsFilesCount->Fetch())
			{
				$arFilesCount[$arFile["TASK_ID"]] = $arFile["CNT"];
			}
		}

		return $arFilesCount;
	}


	function CanCurrentUserViewTopic($topicID)
	{
		$isSocNetModuleIncluded = CModule::IncludeModule("socialnetwork");

		if (($topicID = intval($topicID)) && User::getId())
		{
			if (User::isSuper())
			{
				return true;
			}

			$rsTask = $res = CTasks::GetList(array(), array("FORUM_TOPIC_ID" => $topicID));
			if ($arTask = $rsTask->Fetch())
			{
				if ( ((int)$arTask['GROUP_ID']) > 0 )
				{
					if (in_array(CSocNetFeaturesPerms::GetOperationPerm(SONET_ENTITY_GROUP, $arTask["GROUP_ID"], "tasks", "view_all"), array("G2", "AU")))
						return true;
					elseif (
						$isSocNetModuleIncluded
						&& (false !== CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, $arTask['GROUP_ID'], 'tasks', 'view_all'))
					)
					{
						return (true);
					}
				}

				$arTask["ACCOMPLICES"] = $arTask["AUDITORS"] = array();
				$rsMembers = CTaskMembers::GetList(array(), array("TASK_ID" => $arTask["ID"]));
				while ($arMember = $rsMembers->Fetch())
				{
					if ($arMember["TYPE"] == "A")
					{
						$arTask["ACCOMPLICES"][] = $arMember["USER_ID"];
					}
					elseif ($arMember["TYPE"] == "U")
					{
						$arTask["AUDITORS"][] = $arMember["USER_ID"];
					}
				}

				if (in_array(User::getId(), array_unique(array_merge(array($arTask["CREATED_BY"], $arTask["RESPONSIBLE_ID"]), $arTask["ACCOMPLICES"], $arTask["AUDITORS"]))))
					return true;


				$dbRes = CUser::GetList($by='ID', $order='ASC', array('ID' => $arTask["RESPONSIBLE_ID"]), array('SELECT' => array('UF_DEPARTMENT')));

				if (($arRes = $dbRes->Fetch()) && is_array($arRes['UF_DEPARTMENT']) && count($arRes['UF_DEPARTMENT']) > 0)
					if (in_array(User::getId(), array_keys(CTasks::GetDepartmentManagers($arRes['UF_DEPARTMENT'], $arTask["RESPONSIBLE_ID"]))))
						return true;
			}
		}

		return false;
	}

	public static function getParentOfTask($taskId)
	{
		$taskId = intval($taskId);
		if(!$taskId)
		{
			return false;
		}

		global $DB;

		$item = $DB->query("select PARENT_ID from b_tasks where ID = '".$taskId."'")->fetch();

		return intval($item['PARENT_ID']) ? intval($item['PARENT_ID']) : false;
	}

	public static function GetUserDepartments($USER_ID)
	{
		static $cache = array();
		$USER_ID = (int) $USER_ID;

		if (!isset($cache[$USER_ID]))
		{
			$dbRes = CUser::GetList($by='ID', $order='ASC', array('ID' => $USER_ID), array('SELECT' => array('UF_DEPARTMENT')));

			if ($arRes = $dbRes->Fetch())
				$cache[$USER_ID] = $arRes['UF_DEPARTMENT'];
			else
				$cache[$USER_ID] = false;
		}

		return $cache[$USER_ID];
	}


	public static function onBeforeSocNetGroupDelete($inGroupId)
	{
		global $DB, $APPLICATION;

		$bCanDelete = false;	// prohibit group removing by default

		$groupId = (int) $inGroupId;

		$strSql =
			"SELECT ID AS TASK_ID
			FROM b_tasks
			WHERE GROUP_ID = $groupId
				AND ZOMBIE = 'N'
			";

		$result = $DB->Query($strSql, false, 'File: ' . __FILE__ . '<br>Line: ' . __LINE__);
		if ($result === false)
		{
			$APPLICATION->ThrowException('EA_SQL_ERROR_OCCURED');
			return (false);
		}

		$arResult = $result->Fetch();

		// permit group deletion only when there is no tasks
		if ($arResult === false)
			$bCanDelete = true;
		else
			$APPLICATION->ThrowException(GetMessage('TASKS_ERR_GROUP_IN_USE'));

		return ($bCanDelete);
	}


	public static function OnBeforeUserDelete($inUserID)
	{
		global $DB, $APPLICATION;

		$userID = (int) $inUserID;
		if ( ! ($userID > 0) )
		{
			$APPLICATION->ThrowException(GetMessage('TASKS_BAD_USER_ID'));
			return (false);
		}

		// check for tasks
		$strSql =
			"SELECT ID AS TASK_ID
			FROM b_tasks
			WHERE
				(
					CREATED_BY = $userID
					OR RESPONSIBLE_ID = $userID
				)
				AND ZOMBIE = 'N'

			UNION

			SELECT TASK_ID
			FROM b_tasks_member
			WHERE USER_ID = $userID";

		$result = $DB->Query($strSql, false, 'File: ' . __FILE__ . '<br>Line: ' . __LINE__);
		if ($result === false)
		{
			$APPLICATION->ThrowException('EA_SQL_ERROR_OCCURED');
			return (false);
		}

		$tasks = array();
		while($item = $result->fetch())
		{
			$tasks[$item['TASK_ID']] = true;
		}

		$templates = array();
		$res = \Bitrix\Tasks\TemplateTable::getList(array('filter' => array(
			'LOGIC' => 'OR',
			array('=CREATED_BY' => $userID),
			array('=RESPONSIBLE_ID' => $userID)
		), 'select' => array('ID')));
		while($item = $res->fetch())
		{
			$templates[$item['ID']] = true;
		}

		$errorMessages = array();

		if(!empty($tasks))
		{
			$tasks = array_keys($tasks);
			$tail = '';
			$count = count($tasks);
			if($count > 10)
			{
				$tasks = array_slice($tasks, 0, 10);
				$tail = GetMessage('TASKS_ERR_USER_IN_USE_TAIL', array('#N#' => $count - 10));
			}

			$errorMessages[] = GetMessage('TASKS_ERR_USER_IN_USE_TASKS', array('#IDS#' => implode(', ', $tasks))).$tail;
		}

		if(!empty($templates))
		{
			$templates = array_keys($templates);
			$tail = '';
			$count = count($templates);
			if($count > 10)
			{
				$templates = array_slice($templates, 0, 10);
				$tail = GetMessage('TASKS_ERR_USER_IN_USE_TAIL', array('#N#' => $count - 10));
			}

			$errorMessages[] = GetMessage('TASKS_ERR_USER_IN_USE_TEMPLATES', array('#IDS#' => implode(', ', $templates))).$tail;
		}

		$errorMessages = implode(', ', $errorMessages);

		if((string) $errorMessages != '')
			$APPLICATION->ThrowException(GetMessage('TASKS_ERR_USER_IN_USE_TASKS_PREFIX', array('#ENTITIES#' => $errorMessages)));

		return (empty($tasks) && empty($templates));
	}

	// $value comes in units of $type, we must translate to seconds
	private static function convertDurationToSeconds($value, $type)
	{
		if($type == self::TIME_UNIT_TYPE_HOUR)
		{
			// hours to seconds
			return intval($value) * 3600;
		}
		elseif($type == self::TIME_UNIT_TYPE_DAY || (string) $type == ''/*days by default, see install/db*/)
		{
			// days to seconds
			return intval($value) * 86400;
		}

		return $value;
	}

	// $value comes in seconds, we must translate to units of $type
	public static function convertDurationFromSeconds($value, $type)
	{
		if($type == self::TIME_UNIT_TYPE_HOUR)
		{
			// hours to seconds
			return round(intval($value) / 3600, 0);
		}
		elseif($type == self::TIME_UNIT_TYPE_DAY || (string) $type == ''/*days by default, see install/db*/)
		{
			// days to seconds
			return round(intval($value) / 86400, 0);
		}

		return $value;
	}

	public static function OnUserDelete($USER_ID)
	{
		global $CACHE_MANAGER, $DB;
		$USER_ID = intval($USER_ID);
		$strSql = "
			SELECT RESPONSIBLE_ID AS USER_ID FROM b_tasks WHERE CREATED_BY = ".$USER_ID." AND CREATED_BY != RESPONSIBLE_ID
			UNION
			SELECT CREATED_BY AS USER_ID FROM b_tasks WHERE RESPONSIBLE_ID = ".$USER_ID." AND CREATED_BY != RESPONSIBLE_ID
			UNION
			SELECT USER_ID FROM b_tasks_member WHERE TASK_ID IN (SELECT TASK_ID FROM b_tasks_member WHERE USER_ID = ".$USER_ID.")
		";
		$result = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($arResult = $result->Fetch())
		{
			$CACHE_MANAGER->ClearByTag("tasks_user_".$arResult["USER_ID"]);
		}
	}


	public static function EmitPullWithTagPrefix($arRecipients, $tagPrefix, $cmd, $arParams)
	{
		if ( ! is_array($arRecipients) )
			throw new TasksException('EA_PARAMS', TasksException::TE_WRONG_ARGUMENTS);

		$arRecipients = array_unique($arRecipients);

		if ( ! CModule::IncludeModule('pull') )
			return;

		/*
		$arEventData = array(
			'module_id' => 'tasks',
			'command'   => 'notify',
			'params'    => CIMNotify::GetFormatNotify(
				array(
					'ID' => -3
				)
			),
		);
		*/

		$bWasFatalError = false;

		foreach ($arRecipients as $userId)
		{
			$userId = (int) $userId;

			if ($userId < 1)
			{
				$bWasFatalError = true;
				continue;	// skip invalid items
			}

			//\Bitrix\Pull\Event::add($userId, $arEventData);
			CPullWatch::AddToStack(
				$tagPrefix . $userId,
				array(
					'module_id'  => 'tasks',
					'command'    => $cmd,
					'params'     => $arParams
				)
			);
		}

		if ($bWasFatalError)
			throw new TasksException('EA_PARAMS', TasksException::TE_WRONG_ARGUMENTS);
	}


	public static function EmitPullWithTag($arRecipients, $tag, $cmd, $arParams)
	{
		if ( ! is_array($arRecipients) )
			throw new TasksException('EA_PARAMS', TasksException::TE_WRONG_ARGUMENTS);

		$arRecipients = array_unique($arRecipients);

		if ( ! CModule::IncludeModule('pull') )
			return;

		$bWasFatalError = false;

		foreach ($arRecipients as $userId)
		{
			$userId = (int) $userId;

			if ($userId < 1)
			{
				$bWasFatalError = true;
				continue;	// skip invalid items
			}

			CPullWatch::Add($userId, $tag);

			//\Bitrix\Pull\Event::add($userId, $arEventData);
			CPullWatch::AddToStack(
				$tag,
				array(
					'module_id'  => 'tasks',
					'command'    => $cmd,
					'params'     => $arParams
				)
			);


		}

		if ($bWasFatalError)
			throw new TasksException('EA_PARAMS', TasksException::TE_WRONG_ARGUMENTS);
	}


	/**
	 * Get list of IDs groups, which contains tasks where given user is member
	 *
	 * @param integer $userId
	 * @throws TasksException
	 * @return array
	 */
	public static function GetGroupsWithTasksForUser($userId)
	{
		global $DB;

		$userId = (int) $userId;

		// EXISTS!
		$rc = $DB->Query(
			"SELECT GROUP_ID
			FROM b_tasks T
			WHERE (
				T.CREATED_BY = $userId
				OR T.RESPONSIBLE_ID = $userId
				OR EXISTS(
					SELECT 'x'
					FROM b_tasks_member TM
					WHERE TM.TASK_ID = T.ID
						AND TM.USER_ID = $userId
					)
				)
				AND T.ZOMBIE = 'N'
				AND GROUP_ID IS NOT NULL
				AND GROUP_ID != 0
			GROUP BY GROUP_ID
			"
		);

		if ( ! $rc )
			throw new TasksException();

		$arGroups = array();

		while ($ar = $rc->Fetch())
			$arGroups[] = (int) $ar['GROUP_ID'];

		return (array_unique($arGroups));
	}


	/**
	 * This is experimental code, don't rely on it.
	 * It can be removed or changed in future without any notifications.
	 *
	 * Use CTaskItem::getAllowedTaskActions() and CTaskItem::getAllowedTaskActionsAsStrings() instead.
	 *
	 * @deprecated
	 */
	public static function GetAllowedActions($arTask, $userId = null)
	{
		$arAllowedActions = array();

		if ($userId === null)
		{
			$curUserId = (int) User::getId();
		}
		else
			$curUserId = (int) $userId;

		// we cannot use cached object here (CTaskItem::getInstanceFromPool($arTask['ID'], $curUserId);)
		// because of backward compatibility (CTasks::Update() don't mark cache as dirty in pooled CTaskItem objects)
		$oTask = new CTaskItem($arTask['ID'], $curUserId);

		if ($oTask->isUserRole(CTaskItem::ROLE_RESPONSIBLE))
		{
			if ($arTask['REAL_STATUS'] == CTasks::STATE_NEW)
			{
				$arAllowedActions[] = array(
					'public_name' => 'accept',
					'system_name' => 'accept',
					'id'          => CTaskItem::ACTION_ACCEPT
				);

				$arAllowedActions[] = array(
					'public_name' => 'decline',
					'system_name' => 'decline',
					'id'          => CTaskItem::ACTION_DECLINE
				);
			}
		}

		if ($oTask->isActionAllowed(CTaskItem::ACTION_COMPLETE))
		{
			$arAllowedActions[] = array(
				'public_name' => 'close',
				'system_name' => 'close',
				'id'          => CTaskItem::ACTION_COMPLETE
			);
		}

		if ($oTask->isActionAllowed(CTaskItem::ACTION_START))
		{
			$arAllowedActions[] = array(
				'public_name' => 'start',
				'system_name' => 'start',
				'id'          => CTaskItem::ACTION_START
			);
		}

		if ($oTask->isActionAllowed(CTaskItem::ACTION_DELEGATE))
		{
			$arAllowedActions[] = array(
				'public_name' => 'delegate',
				'system_name' => 'delegate',
				'id'          => CTaskItem::ACTION_DELEGATE
			);
		}

		if ($oTask->isActionAllowed(CTaskItem::ACTION_APPROVE))
		{
			$arAllowedActions[] = array(
				'public_name' => 'approve',
				'system_name' => 'close',
				'id'          => CTaskItem::ACTION_APPROVE
			);
		}

		if ($oTask->isActionAllowed(CTaskItem::ACTION_DISAPPROVE))
		{
			$arAllowedActions[] = array(
				'public_name' => 'redo',
				'system_name' => 'accept',
				'id'          => CTaskItem::ACTION_DISAPPROVE
			);
		}

		if ($oTask->isActionAllowed(CTaskItem::ACTION_REMOVE))
		{
			$arAllowedActions[] = array(
				'public_name' => 'remove',
				'system_name' => 'remove',
				'id'          => CTaskItem::ACTION_REMOVE
			);
		}

		if ($oTask->isActionAllowed(CTaskItem::ACTION_EDIT))
		{
			$arAllowedActions[] = array(
				'public_name' => 'edit',
				'system_name' => 'edit',
				'id'          => CTaskItem::ACTION_EDIT
			);
		}

		if ($oTask->isActionAllowed(CTaskItem::ACTION_DEFER))
		{
			$arAllowedActions[] = array(
				'public_name' => 'pause',
				'system_name' => 'defer',
				'id'          => CTaskItem::ACTION_DEFER
			);
		}

		if ($oTask->isActionAllowed(CTaskItem::ACTION_START))
		{
			$arAllowedActions[] = array(
				'public_name' => 'renew',
				'system_name' => 'start',
				'id'          => CTaskItem::ACTION_START
			);
		}
		elseif ($oTask->isActionAllowed(CTaskItem::ACTION_RENEW))
		{
			$arAllowedActions[] = array(
				'public_name' => 'renew',
				'system_name' => 'accept',
				'id'          => CTaskItem::ACTION_RENEW
			);
		}

		if ($oTask->isActionAllowed(CTaskItem::ACTION_ADD_FAVORITE))
		{
			$arAllowedActions[] = array(
				'public_name' => 'add_favorite',
				'system_name' => 'add_favorite',
				'id'          => CTaskItem::ACTION_ADD_FAVORITE
			);
		}
		if ($oTask->isActionAllowed(CTaskItem::ACTION_DELETE_FAVORITE))
		{
			$arAllowedActions[] = array(
				'public_name' => 'delete_favorite',
				'system_name' => 'delete_favorite',
				'id'          => CTaskItem::ACTION_DELETE_FAVORITE
			);
		}

		return ($arAllowedActions);
	}


	/**
	 * Convert every given string in array from BB-code to HTML
	 *
	 * @param array $arStringsInBbcode
	 *
	 * @throws TasksException
	 * @return array of strings converted to HTML, keys maintaned
	 */
	public static function convertBbcode2Html($arStringsInBbcode)
	{
		if ( ! is_array($arStringsInBbcode) )
			throw new TasksException();

		static $delimiter = '--------This is unique BB-code strings delimiter at high confidence level (CL)--------';

		$stringsCount = count($arStringsInBbcode);
		$arStringsKeys = array_keys($arStringsInBbcode);

		$concatenatedStrings = implode($delimiter, $arStringsInBbcode);

		// While not unique identifier, try to
		$i = -150;
		while (count(explode($delimiter, $concatenatedStrings)) !== $stringsCount)
		{
			// prevent an infinite loop
			if ( ! ($i++) )
				throw new TasksException();

			$delimiter = '--------' . sha1(uniqid()) . '--------';
			$concatenatedStrings = implode($delimiter, $arStringsInBbcode);
		}

		$oParser = new CTextParser();

		$arHtmlStringsWoKeys = explode(
			$delimiter,
			str_replace(
				"\t",
				' &nbsp; &nbsp;',
				$oParser->convertText($concatenatedStrings)
			)
		);

		$arHtmlStrings = array();

		// Do job in compatibility mode, if count of resulted strings not match source
		if (count($arHtmlStringsWoKeys) !== $stringsCount)
		{
			foreach ($arStringsInBbcode as $key => $str)
			{
				$oParser = new CTextParser();
				$arHtmlStrings[$key] = str_replace(
					"\t",
					' &nbsp; &nbsp;',
					$oParser->convertText($str)
				);
				unset($oParser);
			}
		}
		else
		{
			// Maintain original array keys
			$i = 0;
			foreach ($arStringsKeys as $key)
				$arHtmlStrings[$key] = $arHtmlStringsWoKeys[$i++];
		}

		return ($arHtmlStrings);
	}

	public static function getTaskSubTree($taskId)
	{
		$taskId = intval($taskId);
		if(!$taskId)
		{
			return array();
		}

		$queue = array($taskId);
		$met = array();
		$limit = 1000;
		$result = array();

		$i = 0;
		while(true)
		{
			if($i > $limit)
			{
				break;
			}

			$next = array_shift($queue);
			if(isset($met[$next]))
			{
				break;
			}
			if(!intval($next))
			{
				break;
			}

			$subTasks = self::getSubTaskIdsForTask($next);
			foreach($subTasks as $sTId)
			{
				$result[] = $sTId;
				$queue[] = $sTId;
			}

			$met[$next] = true;
			$i++;
		}

		return $result;
	}

	private static function getSubTaskIdsForTask($taskId)
	{
		global $DB;

		$taskId = intval($taskId);

		$result = array();
		$res = $DB->query("select ID from b_tasks where ZOMBIE != 'Y' and ".($taskId ? "PARENT_ID = '".$taskId."'" : "PARENT_ID is null or PARENT_ID = '0'"));
		while($item = $res->fetch())
		{
			if(intval($item['ID']))
			{
				$result[] = $item['ID'];
			}
		}

		return array_unique($result);
	}

	public static function runRestMethod($executiveUserId, $methodName, $args, $navigation)
	{
		CTaskAssert::assert($methodName === 'getlist');

		// Force & limit NAV_PARAMS (in 4th argument)
		while (count($args) < 4)
			$args[] = array();		// All params in CTasks::GetList() by default are empty arrays

		$arParams = & $args[3];

		if ($navigation['iNumPage'] > 1)
		{
			$arParams['NAV_PARAMS'] = array(
				'nPageSize' => CTaskRestService::TASKS_LIMIT_PAGE_SIZE,
				'iNumPage'  => (int) $navigation['iNumPage']
			);
		}
		else if (isset($arParams['NAV_PARAMS']))
		{
			if (isset($arParams['NAV_PARAMS']['nPageTop']))
				$arParams['NAV_PARAMS']['nPageTop'] = min(CTaskRestService::TASKS_LIMIT_TOP_COUNT, (int) $arParams['NAV_PARAMS']['nPageTop']);

			if (isset($arParams['NAV_PARAMS']['nPageSize']))
				$arParams['NAV_PARAMS']['nPageSize'] = min(CTaskRestService::TASKS_LIMIT_PAGE_SIZE, (int) $arParams['NAV_PARAMS']['nPageSize']);

			if (
				( ! isset($arParams['NAV_PARAMS']['nPageTop']) )
				&& ( ! isset($arParams['NAV_PARAMS']['nPageSize']) )
			)
			{
				$arParams['NAV_PARAMS'] = array(
					'nPageSize' => CTaskRestService::TASKS_LIMIT_PAGE_SIZE,
					'iNumPage'  => 1
				);
			}
		}
		else
		{
			$arParams['NAV_PARAMS'] = array(
				'nPageSize' => CTaskRestService::TASKS_LIMIT_PAGE_SIZE,
				'iNumPage'  => 1
			);
		}

		// Check and parse params
		$argsParsed = CTaskRestService::_parseRestParams('ctasks', $methodName, $args);

		$arParams['USER_ID'] = $executiveUserId;

		// TODO: remove this hack (needs for select tasks with GROUP_ID === NULL or 0)
		if (isset($argsParsed[1]))
		{
			$arFilter = $argsParsed[1];
			foreach ($arFilter as $key => $value)
			{
				if (($key === 'GROUP_ID') && ($value == 0))
				{
					$argsParsed[1]['META:GROUP_ID_IS_NULL_OR_ZERO'] = 1;
					unset($argsParsed[1][$key]);
					break;
				}
			}

			if (
				isset($argsParsed[1]['ID'])
				&& is_array($argsParsed[1]['ID'])
				&& empty($argsParsed[1]['ID'])
			)
			{
				$argsParsed[1]['ID'] = -1;
			}
		}

		$rsTasks = call_user_func_array(array('self', 'getlist'), $argsParsed);

		$arTasks = array();
		while ($arTask = $rsTasks->fetch())
			$arTasks[] = $arTask;

		return (array($arTasks, $rsTasks));
	}

	public static function getPublicFieldMap()
	{
		// READ, WRITE, SORT, FILTER, DATE
		return array(
			'TITLE' => 						array(1, 1, 1, 1, 0),
			'STAGE_ID' => 					array(1, 1, 0, 1, 0),
			'STAGES_ID' => 					array(0, 0, 0, 1, 0),
			'DESCRIPTION' => 				array(1, 1, 0, 0, 0),
			'DEADLINE' => 					array(1, 1, 1, 1, 1),
			'START_DATE_PLAN' => 			array(1, 1, 1, 1, 1),
			'END_DATE_PLAN' => 				array(1, 1, 1, 1, 1),
			'PRIORITY' => 					array(1, 1, 1, 1, 0),
			'ACCOMPLICES' => 				array(1, 1, 0, 0, 0),
			'AUDITORS' => 					array(1, 1, 0, 0, 0),
			'TAGS' => 						array(1, 1, 0, 0, 0),
			'ALLOW_CHANGE_DEADLINE' => 		array(1, 1, 1, 0, 0),
			'ALLOW_CHANGE_DEADLINE_COUNT' => 		array(1, 1, 1, 1, 0),
			'ALLOW_CHANGE_DEADLINE_COUNT_VALUE' => 		array(1, 1, 1, 1, 0),
			'ALLOW_CHANGE_DEADLINE_MAXTIME' => 		array(1, 1, 1, 1, 1),
			'ALLOW_CHANGE_DEADLINE_MAXTIME_VALUE' => 		array(1, 1, 1, 1, 1),
			'TASK_CONTROL' => 				array(1, 1, 0, 0, 0),
			'PARENT_ID' => 					array(1, 1, 0, 1, 0),
			'DEPENDS_ON' => 				array(1, 1, 0, 1, 0),
			'GROUP_ID' => 					array(1, 1, 1, 1, 0),
			'RESPONSIBLE_ID' => 			array(1, 1, 1, 1, 0),
			'TIME_ESTIMATE' => 				array(1, 1, 1, 1, 0),
			'ID' => 						array(1, 0, 1, 1, 0),
			'CREATED_BY' => 				array(1, 1, 1, 1, 0),
			'DESCRIPTION_IN_BBCODE' => 		array(1, 0, 0, 0, 0),
			'DECLINE_REASON' => 			array(1, 1, 0, 0, 0),
			'REAL_STATUS' => 				array(1, 0, 0, 1, 0),
			'STATUS' => 					array(1, 1, 1, 1, 0),
			'RESPONSIBLE_NAME' => 			array(1, 0, 0, 0, 0),
			'RESPONSIBLE_LAST_NAME' => 		array(1, 0, 0, 0, 0),
			'RESPONSIBLE_SECOND_NAME' => 	array(1, 0, 0, 0, 0),
			'DATE_START' => 				array(1, 0, 1, 1, 1),
			'DURATION_FACT' => 				array(1, 0, 0, 0, 0),
			'DURATION_PLAN' => 				array(1, 1, 0, 0, 0),
			'DURATION_TYPE' => 				array(1, 1, 0, 0, 0),
			'CREATED_BY_NAME' => 			array(1, 0, 0, 0, 0),
			'CREATED_BY_LAST_NAME' => 		array(1, 0, 0, 0, 0),
			'CREATED_BY_SECOND_NAME' => 	array(1, 0, 0, 0, 0),
			'CREATED_DATE' => 				array(1, 0, 1, 1, 1),
			'CHANGED_BY' => 				array(1, 1, 0, 1, 0),
			'CHANGED_DATE' => 				array(1, 1, 1, 1, 1),
			'STATUS_CHANGED_BY' => 			array(1, 0, 0, 1, 0),
			'STATUS_CHANGED_DATE' => 		array(1, 0, 0, 0, 1),
			'CLOSED_BY' =>					array(1, 0, 0, 0, 0),
			'CLOSED_DATE' => 				array(1, 0, 1, 1, 1),
			'GUID' => 						array(1, 0, 0, 1, 0),
			'MARK' => 						array(1, 1, 1, 1, 0),
			'VIEWED_DATE' => 				array(1, 0, 0, 0, 1),
			'TIME_SPENT_IN_LOGS' => 		array(1, 0, 0, 0, 0),
			'FAVORITE' => 					array(1, 0, 1, 1, 0),
			'ALLOW_TIME_TRACKING' => 		array(1, 1, 1, 1, 0),
			'MATCH_WORK_TIME' => 			array(1, 1, 1, 1, 0),
			'ADD_IN_REPORT' => 				array(1, 1, 0, 1, 0),
			'FORUM_ID' => 					array(1, 0, 0, 0, 0),
			'FORUM_TOPIC_ID' => 			array(1, 0, 0, 1, 0),
			'COMMENTS_COUNT' => 			array(1, 0, 0, 0, 0),
			'SITE_ID' => 					array(1, 1, 0, 1, 0),
			'SUBORDINATE' => 				array(1, 0, 0, 0, 0),
			'FORKED_BY_TEMPLATE_ID' => 		array(1, 0, 0, 0, 0),
			'MULTITASK' => 					array(1, 0, 0, 0, 0),
			'ACCOMPLICE' => 				array(0, 0, 0, 1, 0),
			'AUDITOR' => 					array(0, 0, 0, 1, 0),
			'DOER' => 						array(0, 0, 0, 1, 0),
			'MEMBER' => 					array(0, 0, 0, 1, 0),
			'TAG' => 						array(0, 0, 0, 1, 0),
			'ONLY_ROOT_TASKS' => 			array(0, 0, 0, 1, 0),
		);
	}

	public static function getManifest()
	{
		static $fieldMap;

		if($fieldMap == null)
		{
			$fieldMap = static::getPublicFieldMap();
		}

		static $fieldManifest;

		if($fieldManifest === null)
		{
			foreach($fieldMap as $field => $permissions)
			{
				if($permissions[0]) // read
				{
					$fieldManifest['READ'][] = $field;
				}

				if($permissions[1]) // write
				{
					$fieldManifest['WRITE'][] = $field;
				}

				if($permissions[2]) // sort
				{
					$fieldManifest['SORT'][] = $field;
				}

				if($permissions[3]) // filter
				{
					$fieldManifest['FILTER'][] = $field;
				}

				if($permissions[4]) // filter
				{
					$fieldManifest['DATE'][] = $field;
				}
			}
		}

		return(array(
			'Manifest version' => '2.1',
			'Warning' => 'don\'t rely on format of this manifest, it can be changed without any notification',
			'REST: shortname alias to class'    => 'items',
			'REST: writable task data fields'   =>  $fieldManifest['WRITE'],
			'REST: readable task data fields'   =>  $fieldManifest['READ'],
			'REST: sortable task data fields'   =>  $fieldManifest['SORT'],
			'REST: filterable task data fields' =>  $fieldManifest['FILTER'],
			'REST: date fields' =>  $fieldManifest['DATE'],
			'REST: available methods' => array(
				'getlist' => array(
					'mandatoryParamsCount' => 0,
					'params' => array(
						array(
							'description' => 'arOrder',
							'type'        => 'array',
							'allowedKeys' => $fieldManifest['SORT']
						),
						array(
							'description' => 'arFilter',
							'type'        => 'array',
							'allowedKeys' =>  $fieldManifest['FILTER'],
							'allowedKeyPrefixes' => array(
								'=', '!=', '%', '!%', '?', '><',
								'!><', '>=', '>', '<', '<=', '!'
							)
						),
						array(
							'description'   => 'arSelect',
							'type'          => 'array',
							'allowedValues' => $fieldManifest['READ']
						),
						array(
							'description' => 'arParams',
							'type'        => 'array',
							'allowedKeys' =>  array('NAV_PARAMS', 'bGetZombie')
						)
					),
					'allowedKeysInReturnValue' => $fieldManifest['READ'],
					'collectionInReturnValue'  => true
				)
			)
		));
	}

	private static function getSortingOrderBy($asc = true)
	{
		$order = array();
		$direction = $asc ? "ASC" : "DESC";

		$connection = Application::getConnection();
		if ($connection instanceof MysqlCommonConnection)
		{
			$order[] = " ISNULL(SORTING) ".$direction." ";
			$order[] = " SORTING ".$direction." ";
		}
		elseif ($connection instanceof MssqlConnection)
		{
			$order[] = "CASE WHEN SRT.SORT IS".($asc ? "" : " NOT")." NULL THEN 1 ELSE 0 END";
			$order[] = " SRT.SORT ".$direction." ";
		}
		elseif ($connection instanceof OracleConnection)
		{
			$order[] = " SORTING ".$direction." ".($asc ? "NULLS LAST " : "NULLS FIRST " );
		}

		return $order;
	}

	private static function getOrderSql($by, $order, $default_order, $nullable = true)
	{
		global $DBType;

		static $dbtype = null;

		if ($dbtype === null)
			$dbtype = strtolower($DBType);

		switch ($dbtype)
		{
			case 'mysql':
				return (self::getOrderSql_mysql($by, $order, $default_order, $nullable = true));
			break;

			case 'mssql':
				return (self::getOrderSql_mssql($by, $order, $default_order, $nullable = true));
			break;

			case 'oracle':
				return (self::getOrderSql_oracle($by, $order, $default_order, $nullable = true));
			break;

			default:
				CTaskAssert::log('unknown DB type: ' . $dbtype, CTaskAssert::ELL_ERROR);
				return ' ';
			break;
		}
	}


	private static function getOrderSql_mysql($by, $order, $default_order, $nullable = true)
	{
		$o = self::parseOrder($by, $order, $default_order, $nullable);
		//$o[0] - bNullsFirst
		//$o[1] - asc|desc
		if($o[0])
		{
			if($o[1] == "asc")
				return $by." asc";
			else
				return "length(".$by.")>0 asc, ".$by." desc";
		}
		else
		{
			if($o[1] == "asc")
				return "length(".$by.")>0 desc, ".$by." asc";
			else
				return $by." desc";
		}
	}


	private static function getOrderSql_mssql($by, $order, $default_order, $nullable = true)
	{
		static $temp_by = 0;
		$o = self::parseOrder($by, $order, $default_order, $nullable);
		//$o[0] - bNullsFirst
		//$o[1] - asc|desc
		if($o[0])
		{
			if($o[1] == "asc")
				return $by." asc";//
			else
				return array(
					"case when len(".$by.") > 0 then 1 else 0 end",
					"_IS_NULL_".$temp_by,
					"_IS_NULL_".($temp_by++)." asc, ".$by." desc",
				);
		}
		else
		{
			if($o[1] == "asc")
				return array(
					"case when len(".$by.") > 0 then 1 else 0 end",
					"_IS_NULL_".$temp_by,
					"_IS_NULL_".($temp_by++)." desc, ".$by." asc",
				);
			else
				return $by." desc";//
		}
	}


	private static function getOrderSql_oracle($by, $order, $default_order, $nullable = true)
	{
		$o = self::parseOrder($by, $order, $default_order, $nullable);
		//$o[0] - bNullsFirst
		//$o[1] - asc|desc
		if($o[0])
		{
			if($o[1] == "asc")
			{
				if($nullable)
					return $by." asc nulls first";
				else
					return $by." asc";
			}
			else
			{
				return $by." desc";
			}
		}
		else
		{
			if($o[1] == "asc")
			{
				return $by." asc";
			}
			else
			{
				if($nullable)
					return $by." desc nulls last";
				else
				return $by." desc";
			}
		}
	}


	private static function parseOrder($by, $order, $default_order, $nullable = true)
	{
		static $arOrder = array(
			"nulls,asc"  => array(true,  "asc" ),
			"asc,nulls"  => array(false, "asc" ),
			"nulls,desc" => array(true,  "desc"),
			"desc,nulls" => array(false, "desc"),
			"asc"        => array(true,  "asc" ),
			"desc"       => array(false, "desc"),
		);
		$order = strtolower(trim($order));
		if(array_key_exists($order, $arOrder))
			$o = $arOrder[$order];
		elseif(array_key_exists($default_order, $arOrder))
			$o = $arOrder[$default_order];
		else
			$o = $arOrder["desc,nulls"];

		//There is no need to "reverse" nulls order when
		//column can not contain nulls
		if(!$nullable)
		{
			if($o[1] == "asc")
				$o[0] = true;
			else
				$o[0] = false;
		}

		return $o;
	}
}
