<?
namespace Bitrix\Timeman\Update;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Update\Stepper;
use Bitrix\Timeman\Form\Schedule\ScheduleForm;
use Bitrix\Timeman\Form\Schedule\ViolationForm;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesTable;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable;
use Bitrix\Timeman\Model\Worktime\Report\WorktimeReportTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Service\DependencyManager;
use CIntranetUtils;
use COption;

Loc::loadMessages(__FILE__);

class TimemanVersion19Converter extends Stepper
{
	private $maxExecuteSeconds = 12;
	protected static $moduleId = 'timeman';

	protected $recordsQueryLimit = 2000;
	private $entriesOffsetOptionName = 'converterEntriesFieldsToConvertOffset';
	private $userIdsOffsetOptionName = 'converterUserIdsOffsetOptionName';
	private $isEntriesTimestampMigrationDoneName = 'converterIsEntriesMigrationEndedName';
	private $isEntriesOffsetMigrationDoneName = 'converterIsEntriesOffsetMigrationEndedName';
	private $scheduleForms = [];
	private $userToScheduleMap = [];
	/*** @var int */
	private $timeExecutionStart;

	private $processedEntitiesData = [];
	private $savedSchedulesMap = [];
	private $dropLogAfterExecution = false;
	/** @var ViolationForm[] */
	private $violationForms = [];
	private $violationRulesSaved = false;
	/** @var array|null */
	private $departmentsTree;

	public function execute(array &$option)
	{
		$this->timeExecutionStart = time();

		if (!$this->isRecordsTimestampMigrationDone())
		{
			$this->migrateTimestampRecordsData();
			$this->logMessage('execute - migrate Timestamp Records Data', __LINE__);
		}
		elseif (!$this->isRecordsUserOffsetMigrationDone())
		{
			$this->migrateRecordsOffsetData();
			$this->logMessage('execute - migrate Records Offset Data', __LINE__);
		}
		else
		{
			$done = $this->migrateSchedulesSettings();
			if (!$done)
			{
				return true;
			}
			$this->logMessage('version 19.0 data migration complete', __LINE__);
			Option::delete(self::$moduleId, ['name' => 'converter19isOldSchedulesDeleted']);
			Option::delete(self::$moduleId, ['name' => $this->isEntriesOffsetMigrationDoneName]);
			Option::delete(self::$moduleId, ['name' => $this->userIdsOffsetOptionName]);
			Option::delete(self::$moduleId, ['name' => $this->isEntriesTimestampMigrationDoneName]);
			Option::delete(self::$moduleId, ["name" => $this->entriesOffsetOptionName]);
			return false;
		}

		return true;
	}

	private function createTemporaryTables()
	{
		if (!Application::getConnection()->isTableExists('b_timeman_converter_collected_schedules'))
		{
			Application::getConnection()->query("
				CREATE TABLE IF NOT EXISTS `b_timeman_converter_collected_schedules` (
					`ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					`SCHEDULE_KEY` VARCHAR(50) NOT NULL DEFAULT '',
					`SCHEDULE_FORM_DATA` TEXT NOT NULL,
					`ASSIGNMENTS` MEDIUMTEXT NOT NULL,
					`ASSIGNMENTS_EXCLUDED` MEDIUMTEXT NOT NULL,
					`SCHEDULE_ID` INT(10) UNSIGNED NOT NULL DEFAULT '0',
					`SHIFT_ID` INT(10) UNSIGNED NOT NULL DEFAULT '0',
					`USERS_RECORDS_UPDATED` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (`ID`),
					UNIQUE INDEX `SCHEDULE_KEY` (`SCHEDULE_KEY`)
				);
			");
		}
		if (!Application::getConnection()->isTableExists('b_timeman_converter_violation_rules'))
		{
			Application::getConnection()->query("
				CREATE TABLE IF NOT EXISTS `b_timeman_converter_violation_rules` (
					`ENTITY_CODE` VARCHAR(50) NOT NULL,
					`FORM_DATA` TEXT NOT NULL,
					`VIOLATION_RULES_SAVED` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
					UNIQUE INDEX `ENTITY_CODE` (`ENTITY_CODE`)
				);
			");
		}
		if (!Application::getConnection()->isTableExists('b_timeman_converter_log'))
		{
			Application::getConnection()->query("
				CREATE TABLE IF NOT EXISTS `b_timeman_converter_log` (
					`ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					`TIMESTAMP_X` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
					`LOG_MESSAGE` MEDIUMTEXT NOT NULL,
					`FILE_LINE` INT(11) NOT NULL,
					PRIMARY KEY (`ID`)
				);
			");
		}
		if (!Application::getConnection()->isTableExists('b_timeman_converter_processed_entities'))
		{
			Application::getConnection()->query("
				CREATE TABLE IF NOT EXISTS `b_timeman_converter_processed_entities` (
					`ENTITY_CODE` VARCHAR(50) NULL DEFAULT NULL,
					UNIQUE INDEX `ENTITY_CODE` (`ENTITY_CODE`)
				)
			");
		}
	}

	private function isRecordsUserOffsetMigrationDone()
	{
		return Option::get(self::$moduleId, $this->isEntriesOffsetMigrationDoneName, 0);
	}

	private function isRecordsTimestampMigrationDone()
	{
		return Option::get(self::$moduleId, $this->isEntriesTimestampMigrationDoneName, 0);
	}

	private function logMessage($text, $line)
	{
		$connection = Application::getConnection();

		try
		{
			$connection->query("INSERT INTO `b_timeman_converter_log` (LOG_MESSAGE, FILE_LINE) VALUES " .
							   "('" . $connection->getSqlHelper()->forSql($text) . "'" .
							   ", '" . $connection->getSqlHelper()->forSql($line) . "');");
		}
		catch (\Exception $exc)
		{
		}
	}

	private function migrateRecordsOffsetData()
	{
		$this->logMessage('migrate Records Offset Data started', __LINE__);

		$recordsOffset = (int)Option::get(self::$moduleId, $this->userIdsOffsetOptionName, 0);
		$userLimit = 350;
		while (!$this->isMaxExecutionSecondsExceeded())
		{
			$userIds = array_column(
				WorktimeRecordTable::query()
					->addSelect('USER_ID_D')
					->registerRuntimeField(new ExpressionField('USER_ID_D', 'DISTINCT(%s)', 'USER_ID'))
					->addOrder('USER_ID_D')
					->setLimit($userLimit)
					->setOffset($recordsOffset)
					->exec()
					->fetchAll(),
				'USER_ID_D');

			$offsets = [];
			$timeHelper = TimeHelper::getInstance();
			$dateTimeServer = new \DateTime('now', new \DateTimeZone(date_default_timezone_get()));
			foreach ($userIds as $userId)
			{
				$userOffset = $dateTimeServer->getOffset() + $timeHelper->getUserToServerOffset($userId);
				if ($userOffset !== 0)
				{
					$offsets[$userOffset][] = (int)$userId;
				}
			}
			foreach ($offsets as $offset => $offsetUserIds)
			{
				Application::getConnection()->query("
					UPDATE 
					b_timeman_entries 
					SET
					TIMESTAMP_X = TIMESTAMP_X,
					START_OFFSET = IF(START_OFFSET != 0, START_OFFSET, " . (int)$offset . "),
					STOP_OFFSET = IF(STOP_OFFSET != 0, STOP_OFFSET, IF(CURRENT_STATUS = \"CLOSED\", " . (int)$offset . ", 0))
					WHERE
					USER_ID IN (" . implode(', ', $offsetUserIds) . ")"
				);
			}

			if (count($userIds) < $userLimit)
			{
				Option::set(self::$moduleId, $this->isEntriesOffsetMigrationDoneName, 1);
				break;
			}
			else
			{
				$recordsOffset = $recordsOffset + $userLimit;
				Option::set(self::$moduleId, $this->userIdsOffsetOptionName, $recordsOffset);
				$this->logMessage('migrate Records Offset Data offset changed to ' . $recordsOffset, __LINE__);
			}
		}

	}

	private function migrateTimestampRecordsData()
	{
		$offset = (int)Option::get(self::$moduleId, $this->entriesOffsetOptionName, 0);
		if ($offset === 0)
		{
			$this->createTemporaryTables();
		}
		$this->logMessage('migrate Timestamp Records Data started', __LINE__);
		while (!$this->isMaxExecutionSecondsExceeded())
		{
			$records = WorktimeRecordTable::query()
				->addSelect('ID')
				->addSelect('DATE_START')
				->setLimit($this->recordsQueryLimit)
				->setOffset($offset)
				->addOrder('ID')
				->exec()
				->fetchAll();
			if ($records)
			{
				$this->updateRecords($records);
			}

			if (count($records) < $this->recordsQueryLimit)
			{
				// entries migration is over
				Option::set(self::$moduleId, $this->isEntriesTimestampMigrationDoneName, 1);

				Application::getConnection()->query("
					UPDATE 
					b_timeman_entries 
					SET
					TIMESTAMP_X = TIMESTAMP_X,
					RECORDED_DURATION = IF(
						RECORDED_DURATION != 0,
						RECORDED_DURATION,
						IF(
							CURRENT_STATUS = 'PAUSED',
							RECORDED_DURATION,
							IF(
								CURRENT_STATUS != 'CLOSED',
								0,
								IF(
									(CAST(RECORDED_STOP_TIMESTAMP AS SIGNED) - CAST(RECORDED_START_TIMESTAMP AS SIGNED) - CAST(TIME_LEAKS AS SIGNED)) > 0, 
									CAST(RECORDED_STOP_TIMESTAMP AS SIGNED) - CAST(RECORDED_START_TIMESTAMP AS SIGNED) - CAST(TIME_LEAKS AS SIGNED), 
									IF(
										CAST(RECORDED_STOP_TIMESTAMP AS SIGNED) - CAST(RECORDED_START_TIMESTAMP AS SIGNED) > 0, 
										CAST(RECORDED_STOP_TIMESTAMP AS SIGNED) - CAST(RECORDED_START_TIMESTAMP AS SIGNED), 
										0
									)
								)
							)
						)
					)"
				);
				break;
			}
			else
			{
				$offset = $offset + $this->recordsQueryLimit;
				Option::set(self::$moduleId, $this->entriesOffsetOptionName, $offset);
				$this->logMessage('migrate Timestamp Records Data offset changed to ' . $offset, __LINE__);
			}
		}
	}

	private function updateRecords(array $records)
	{
		$utcOffsets = [];
		foreach ($records as $record)
		{
			$utcOffsets[date('Z', strtotime($record['DATE_START']))][] = (int)$record['ID'];
		}

		foreach ($utcOffsets as $offsetSeconds => $entriesIds)
		{
			$reportIds = array_column(
				WorktimeReportTable::query()
					->registerRuntimeField(new ExpressionField('RID', 'MIN(ID)'))
					->addSelect('RID')
					->whereIn('REPORT_TYPE', ['ERR_OPEN', 'ERR_CLOSE'])
					->whereIn('ENTRY_ID', $entriesIds)
					->addGroup('REPORT_TYPE')
					->addGroup('ENTRY_ID')
					->exec()
					->fetchAll(),
				'RID'
			);
			$connection = Application::getConnection();
			$offsetWithLeadZero = TimeHelper::getInstance()->getFormattedOffset($offsetSeconds);
			$dateFormat = '"%Y-%m-%dT%H:%i:%s' . $offsetWithLeadZero . '"';
			$reportLike = '"%;%' . $offsetWithLeadZero . ';%"';
			$connection->query('SET time_zone = "' . TimeHelper::getInstance()->getFormattedOffset($offsetSeconds, false) . '";');
			if (empty($reportIds))
			{
				$connection->query('
					UPDATE `b_timeman_entries` e
					SET 
						e.TIMESTAMP_X = e.TIMESTAMP_X,
	
						e.RECORDED_START_TIMESTAMP = IF(
							e.RECORDED_START_TIMESTAMP != 0, 
							e.RECORDED_START_TIMESTAMP, 
							UNIX_TIMESTAMP(e.DATE_START)
						),
						e.ACTUAL_START_TIMESTAMP = IF(
							e.ACTUAL_START_TIMESTAMP != 0, 
							e.ACTUAL_START_TIMESTAMP, 
							UNIX_TIMESTAMP(e.DATE_START)
						),
		
						e.RECORDED_STOP_TIMESTAMP = IF(
							e.RECORDED_STOP_TIMESTAMP != 0, 
							e.RECORDED_STOP_TIMESTAMP, 
							IF(e.CURRENT_STATUS = "CLOSED", UNIX_TIMESTAMP(e.DATE_FINISH), 0)
						),
						e.ACTUAL_STOP_TIMESTAMP = IF(
							e.ACTUAL_STOP_TIMESTAMP != 0, 
							e.ACTUAL_STOP_TIMESTAMP, 
							IF(e.CURRENT_STATUS = "CLOSED", UNIX_TIMESTAMP(e.DATE_FINISH), 0)
						),
						e.RECORDED_DURATION = IF(
							e.RECORDED_DURATION != 0, 
							e.RECORDED_DURATION, 
							IF(e.CURRENT_STATUS = "PAUSED", UNIX_TIMESTAMP(e.DATE_FINISH) - UNIX_TIMESTAMP(e.DATE_START), 0)
						)
					WHERE 
						e.ID IN (' . implode(', ', $entriesIds) . ');
				');
			}
			else
			{
				$connection->query('
					UPDATE `b_timeman_entries` e
					LEFT JOIN `b_timeman_reports` rstart ON rstart.ENTRY_ID = e.id AND rstart.REPORT_TYPE = "ERR_OPEN" 
					LEFT JOIN `b_timeman_reports` rend ON rend.ENTRY_ID = e.id AND rend.REPORT_TYPE ="ERR_CLOSE"
					SET
						e.TIMESTAMP_X = e.TIMESTAMP_X,
						e.RECORDED_START_TIMESTAMP = IF(
							e.RECORDED_START_TIMESTAMP != 0, 
							e.RECORDED_START_TIMESTAMP, 
							UNIX_TIMESTAMP(e.DATE_START)
						),
						e.ACTUAL_START_TIMESTAMP = IF(
							e.ACTUAL_START_TIMESTAMP != 0, 
							e.ACTUAL_START_TIMESTAMP, 
							IF(
								rstart.REPORT IS NOT NULL AND rstart.REPORT LIKE ' . $reportLike . ', 
								UNIX_TIMESTAMP(
									STR_TO_DATE(
										SUBSTRING_INDEX(SUBSTRING_INDEX(rstart.REPORT, \';\', 2), \';\', -1)
										, ' . $dateFormat . '
									)
								), 
								UNIX_TIMESTAMP(e.DATE_START) 
							)
						),
		
						e.RECORDED_STOP_TIMESTAMP = IF(
							e.RECORDED_STOP_TIMESTAMP != 0, 
							e.RECORDED_STOP_TIMESTAMP, 
							IF(e.CURRENT_STATUS = "CLOSED", UNIX_TIMESTAMP(e.DATE_FINISH), 0)
						),
						e.ACTUAL_STOP_TIMESTAMP = IF(
							e.ACTUAL_STOP_TIMESTAMP != 0, 
							e.ACTUAL_STOP_TIMESTAMP, 
							IF(e.CURRENT_STATUS = "CLOSED", IF(
								rend.REPORT IS NOT NULL AND rend.REPORT LIKE ' . $reportLike . ', 
								UNIX_TIMESTAMP(
									STR_TO_DATE(
										SUBSTRING_INDEX(SUBSTRING_INDEX(rend.REPORT, \';\', 2), \';\', -1)
										, ' . $dateFormat . '
									)
								), 
								UNIX_TIMESTAMP(e.DATE_FINISH) 
							), 0)
						),
						e.RECORDED_DURATION = IF(
							e.RECORDED_DURATION != 0, 
							e.RECORDED_DURATION, 
							IF(e.CURRENT_STATUS = "PAUSED", UNIX_TIMESTAMP(e.DATE_FINISH) - UNIX_TIMESTAMP(e.DATE_START), 0)
						)
	
					WHERE 
						e.ID IN (' . implode(', ', $entriesIds) . ')
						AND (rstart.ID IS NULL OR rstart.ID IN (' . implode(', ', $reportIds) . '))
						AND (rend.ID  IS NULL OR rend.ID IN (' . implode(', ', $reportIds) . '));
				');
			}
		}
	}

	private function issetPersonalSetting($value)
	{
		return $value !== '' && $value !== null;
	}

	private function excludeEntityFromScheduleForms($code, $scheduleForms)
	{
		foreach ($scheduleForms as $scheduleForm)
		{
			/** @var ScheduleForm $scheduleForm */
			$scheduleForm->assignmentsExcluded[$code] = $code;
			unset($scheduleForm->assignments[$code]);
		}
	}

	private function processDepartmentScheduleMigration($departmentInfo, $parentParams, $parentDepartmentIds)
	{
		if ($this->isMaxExecutionSecondsExceeded())
		{
			throw new MaximumExecutionSecondsExceededException();
		}
		$depId = $departmentInfo['data']['ID'];
		$entityCode = 'DR' . $depId;
		$selfSettings = TimemanVersion18User::getSectionPersonalSettings($depId, true, $this->getTimemanSettingsNames());
		$filledSettings = $this->fillDefaultSettingsParams($selfSettings, $parentParams);
		if (!isset($this->processedEntitiesData[$entityCode]))
		{
			$this->processedEntitiesData[$entityCode] = true;
			if (!$this->isTimemanTurnedOff($selfSettings))
			{
				if ($this->hasPersonalViolationRules($selfSettings))
				{
					$this->buildViolationForm($filledSettings, $entityCode);
				}

				$this->assignEntityToSchedule($entityCode, $filledSettings, $parentDepartmentIds);
			}
		}
		$parentDepartmentIds = array_unique(array_merge($parentDepartmentIds, [(int)$departmentInfo['data']['ID']]));
		foreach ($departmentInfo['subDepartments'] as $subDepartmentData)
		{
			$this->processDepartmentScheduleMigration($subDepartmentData, $filledSettings, $parentDepartmentIds);
		}
		foreach ($departmentInfo['data']['EMPLOYEES'] as $userId)
		{
			$this->processUserScheduleMigration($userId, $filledSettings, $parentDepartmentIds);
		}
	}

	private function processUserScheduleMigration($userId, $filledSettings, $parentDepartmentIds = [])
	{
		if ($this->isMaxExecutionSecondsExceeded())
		{
			throw new MaximumExecutionSecondsExceededException();
		}
		$entityCode = 'U' . $userId;

		$tmUser = new TimemanVersion18User($userId);
		$selfSettings = $tmUser->getPersonalSettings($this->getTimemanSettingsNames());

		if (!$this->isTimemanTurnedOff($selfSettings))
		{
			$params = $tmUser->getSettings();
			if (preg_match(TimeHelper::getInstance()->getTimeRegExp(), $params['UF_TM_ALLOWED_DELTA']) === 1)
			{
				$params['UF_TM_ALLOWED_DELTA'] = TimeHelper::getInstance()->convertHoursMinutesToSeconds($params['UF_TM_ALLOWED_DELTA']);
			}
			if ($this->hasPersonalViolationRules($selfSettings))
			{
				$this->buildViolationForm($params, $entityCode);
			}

			$this->assignEntityToSchedule($entityCode, $params, $parentDepartmentIds);
		}
	}

	private function buildViolationForm($params, $entityCode)
	{
		$form = $this->createViolationForm(
			$params['UF_TM_MAX_START'],
			$params['UF_TM_MIN_FINISH'],
			$params['UF_TM_MIN_DURATION'],
			$params['UF_TM_ALLOWED_DELTA']
		);
		$form->entityCode = $entityCode;
		$this->violationForms[$entityCode] = $form;

		return $form;
	}

	/**
	 * @param $params
	 * @return ScheduleForm
	 */
	private function getScheduleForm($params, $key = null)
	{
		$key = $key === null ? $this->createScheduleSettingsKey($params) : $key;
		if (!$this->scheduleForms[$key])
		{
			$this->scheduleForms[$key] = $this->createScheduleForm($key);
			$this->savedSchedulesMap[$key] = [
				'scheduleId' => 0,
				'shiftId' => 0,
				'usersUpdated' => false,
			];
		}
		return $this->scheduleForms[$key];
	}

	private function createOrRestoreSchedulesData($baseDepartmentId)
	{
		$this->processedEntitiesData = Application::getConnection()->query("SELECT * FROM `b_timeman_converter_processed_entities`")->fetchAll();
		$this->processedEntitiesData = array_fill_keys(array_values(array_column($this->processedEntitiesData, 'ENTITY_CODE')), true);
		$this->restoreViolationsData();

		$tempData = Application::getConnection()->query("SELECT * FROM b_timeman_converter_collected_schedules")->fetchAll();
		if (empty($tempData))
		{
			$defaultSettings = $this->getDefaultTimemanSettings();

			$this->buildViolationForm($defaultSettings, 'DR' . $baseDepartmentId);

			$commonScheduleForm = $this->getScheduleForm($defaultSettings);
			$commonScheduleForm->assignments = [ScheduleForm::ALL_USERS => ScheduleForm::ALL_USERS];

			return $commonScheduleForm;
		}
		else
		{
			$commonScheduleForm = null;
			foreach ($tempData as $tempValues)
			{
				$key = $tempValues['SCHEDULE_KEY'];
				$this->savedSchedulesMap[$key] = [
					'scheduleId' => $tempValues['SCHEDULE_ID'],
					'shiftId' => $tempValues['SHIFT_ID'],
					'usersUpdated' => (int)$tempValues['USERS_RECORDS_UPDATED'] === 1,
				];
				if (!$this->scheduleForms[$key])
				{
					$this->scheduleForms[$key] = $this->createScheduleForm($key);
					$this->scheduleForms[$key]->assignments = json_decode($tempValues['ASSIGNMENTS'], true);
					$this->scheduleForms[$key]->assignmentsExcluded = json_decode($tempValues['ASSIGNMENTS_EXCLUDED'], true);
					if (!$commonScheduleForm && isset($this->scheduleForms[$key]->assignments[ScheduleForm::ALL_USERS]))
					{
						$commonScheduleForm = $this->scheduleForms[$key];
					}
				}
			}
			return $commonScheduleForm;
		}
	}

	private function restoreViolationsData()
	{
		$tempData = Application::getConnection()->query("SELECT * FROM b_timeman_converter_violation_rules")->fetchAll();
		if (empty($tempData))
		{
			return;
		}
		foreach ($tempData as $tempValues)
		{
			$params = json_decode($tempValues['FORM_DATA'], true);
			$this->buildViolationForm($params, $tempValues['ENTITY_CODE']);
			$this->violationRulesSaved = (int)$tempValues['VIOLATION_RULES_SAVED'] === 1;
		}
	}

	private function saveTempDataForRestore()
	{
		$connection = Application::getConnection();

		$sqlEntities = 'INSERT IGNORE INTO `b_timeman_converter_processed_entities` (ENTITY_CODE) VALUES ';
		$valuesEntities = [];
		foreach ($this->processedEntitiesData as $code => $value)
		{
			$valuesEntities[] = '("' . $code . '")';
		}
		if (!empty($valuesEntities))
		{
			$sqlEntities .= implode(",\n", $valuesEntities);
			$connection->query($sqlEntities);
		}


		$sql = 'REPLACE INTO `b_timeman_converter_collected_schedules` 
		(SCHEDULE_KEY, SCHEDULE_FORM_DATA, ASSIGNMENTS, ASSIGNMENTS_EXCLUDED, SCHEDULE_ID, SHIFT_ID, USERS_RECORDS_UPDATED) VALUES ';
		$values = [];
		foreach ($this->scheduleForms as $scheduleKey => $scheduleForm)
		{
			$scheduleForm->validate();
			/** @var ScheduleForm $scheduleForm */
			$formData = [
				'maxExactStart' => $scheduleForm->violationForm->maxExactStart,
				'minExactEnd' => $scheduleForm->violationForm->minExactEnd,
				'minDayDuration' => $scheduleForm->violationForm->minDayDuration,
				'maxAllowedToEditWorkTime' => $scheduleForm->violationForm->maxAllowedToEditWorkTime,
			];

			$values[] = '("' . $connection->getSqlHelper()->forSql($scheduleKey) . '", '
						. '"' . $connection->getSqlHelper()->forSql(json_encode($formData)) . '", '
						. '"' . $connection->getSqlHelper()->forSql(json_encode($scheduleForm->assignments)) . '", '
						. '"' . $connection->getSqlHelper()->forSql(json_encode($scheduleForm->assignmentsExcluded)) . '", '
						. ($this->savedSchedulesMap[$scheduleKey]['scheduleId']) . ', '
						. ($this->savedSchedulesMap[$scheduleKey]['shiftId']) . ', '
						. ($this->savedSchedulesMap[$scheduleKey]['usersUpdated'] === true ? '1' : '0')
						. ')';
		}
		if (!empty($values))
		{
			$sql .= implode(",\n", $values);
			$connection->query($sql);
		}

		$this->saveTempViolations($connection);
	}

	/**
	 * @param Connection $connection
	 */
	private function saveTempViolations($connection)
	{
		$violationSql = 'REPLACE INTO `b_timeman_converter_violation_rules` (FORM_DATA, ENTITY_CODE, VIOLATION_RULES_SAVED) VALUES ';
		$violationValues = [];
		foreach ($this->violationForms as $entityCode => $violationForm)
		{
			$formData = [
				'UF_TM_MAX_START' => $violationForm->maxExactStart,
				'UF_TM_MIN_FINISH' => $violationForm->minExactEnd,
				'UF_TM_MIN_DURATION' => $violationForm->minDayDuration,
				'UF_TM_ALLOWED_DELTA' => $violationForm->maxAllowedToEditWorkTime,
			];

			$violationValues[] = '("' . $connection->getSqlHelper()->forSql(json_encode($formData)) . '",'
								 . '"' . $connection->getSqlHelper()->forSql($violationForm->entityCode) . '",'
								 . ($this->violationRulesSaved ? '1' : '0')
								 . ')';
		}
		if (!empty($violationValues))
		{
			$violationSql .= implode(",\n", $violationValues);
			$connection->query($violationSql);
		}
	}

	private function migrateSchedulesSettings()
	{
		$this->createTemporaryTables();
		$this->logMessage('migrate Schedules Settings started', __LINE__);

		try
		{
			$this->deleteOldSchedules();
		}
		catch (MaximumExecutionSecondsExceededException $exc)
		{
			$this->logMessage('Maximum Execution Seconds Exceeded Exception - deleteOldSchedules', __LINE__);
			return false;
		}

		$defaultSettings = $this->getDefaultTimemanSettings();
		$departmentsTree = $this->buildDepartmentsTree();

		$commonScheduleForm = $this->createOrRestoreSchedulesData(reset(array_keys($departmentsTree)));

		# fetch all users/departments personal timeman settings
		# and group them
		try
		{
			$this->logMessage(json_encode($departmentsTree), __LINE__);
		}
		catch (\Exception $exc)
		{
		}

		try
		{
			foreach ($departmentsTree as $departmentId => $departmentData)
			{
				$this->processDepartmentScheduleMigration(
					$departmentData,
					$defaultSettings,
					[(int)$departmentId]
				);
			}
		}
		catch (MaximumExecutionSecondsExceededException $exc)
		{
			$this->logMessage('Maximum Execution Seconds Exceeded Exception - saveTempDataForRestore', __LINE__);

			# save results in a temp table
			# that on the next run we can start from this point and continue grouping
			$this->saveTempDataForRestore();
			return false;
		}


		foreach ($this->scheduleForms as $key => $scheduleForm)
		{
			$scheduleForm->validate();
			if ($this->isMaxExecutionSecondsExceeded())
			{
				$this->saveTempDataForRestore();
				return false;
			}
			if ($this->savedSchedulesMap[$key]['scheduleId'] > 0)
			{
				// schedule already saved to db
				continue;
			}
			$result = DependencyManager::getInstance()
				->getScheduleService()
				->add($scheduleForm);
			if ($result->isSuccess())
			{
				$this->savedSchedulesMap[$key] = [
					'scheduleId' => $result->getSchedule()->getId(),
					'shiftId' => $result->getSchedule()->getShifts() ? reset($result->getSchedule()->getShifts()->getIdList()) : 0,
					'usersUpdated' => false,
				];
			}
		}
		$this->logMessage('schedules added', __LINE__);
		$commonScheduleId = 0;
		foreach ($this->scheduleForms as $scheduleFormKey => $scheduleForm)
		{
			if ($scheduleForm->isForAllUsers)
			{
				$commonScheduleId = (int)$this->savedSchedulesMap[$scheduleFormKey]['scheduleId'];
				break;
			}
		}
		$violationRows = [];
		foreach ($this->violationForms as $entityCode => $violationForm)
		{
			$violationForm->validate();
			$violationRules = ViolationRules::create($commonScheduleId, $violationForm, $entityCode);
			$violationRows[] = [
				'SCHEDULE_ID' => $violationRules->getScheduleId(),
				'ENTITY_CODE' => Application::getConnection()->getSqlHelper()->forSql($violationRules->getEntityCode()),
				'MAX_EXACT_START' => (int)$violationRules->getMaxExactStart(),
				'MIN_EXACT_END' => (int)$violationRules->getMinExactEnd(),
				'MIN_DAY_DURATION' => (int)$violationRules->getMinDayDuration(),
				'MAX_ALLOWED_TO_EDIT_WORK_TIME' => (int)$violationRules->getMaxAllowedToEditWorkTime(),
				'USERS_TO_NOTIFY' => $violationRules->getUsersToNotify(),
			];
		}
		if (!empty($violationRows) && $commonScheduleId > 0 && !$this->violationRulesSaved)
		{
			if (!empty(array_column($violationRows, 'ENTITY_CODE')))
			{
				Application::getConnection()->query("DELETE FROM `" . ViolationRulesTable::getTableName() . "` 
				WHERE SCHEDULE_ID = " . (int)$commonScheduleId
													. ' AND ENTITY_CODE IN ("' . implode('", "', array_column($violationRows, 'ENTITY_CODE')) . '")'
				);
			}
			$resultMulti = ViolationRulesTable::addMulti($violationRows, true);
			if ($resultMulti->isSuccess())
			{
				Application::getConnection()->query("UPDATE b_timeman_converter_violation_rules SET VIOLATION_RULES_SAVED = 1");
				$this->violationRulesSaved = true;
			}
		}
		if ($this->isMaxExecutionSecondsExceeded())
		{
			$this->saveTempDataForRestore();
			return false;
		}

		# build mapping - for every userId find scheduleId by user's settings
		foreach ($departmentsTree as $departmentId => $departmentData)
		{
			$this->saveUserToScheduleMap($departmentData, 'department');
		}

		# build mapping - scheduleId to array of userIds
		$scheduleKeyUserIdsMap = [];
		foreach ($this->userToScheduleMap as $userId => $userScheduleKey)
		{
			$scheduleKeyUserIdsMap[$userScheduleKey][] = (int)$userId;
		}


		foreach ($scheduleKeyUserIdsMap as $scheduleKey => $userIds)
		{
			if ($this->isMaxExecutionSecondsExceeded())
			{
				$this->saveTempDataForRestore();
				return false;
			}
			if ($this->savedSchedulesMap[$scheduleKey]['usersUpdated'] === true)
			{
				# columns SCHEDULE_ID and SHIFT_ID are already updated
				continue;
			}
			$chunks = array_chunk($userIds, 500);
			foreach ($chunks as $chunkUserIds)
			{
				Application::getConnection()->query("
					UPDATE b_timeman_entries
					SET 
					TIMESTAMP_X = TIMESTAMP_X,
					SCHEDULE_ID = " . (int)$this->savedSchedulesMap[$scheduleKey]['scheduleId'] . ",
					SHIFT_ID =  " . (int)$this->savedSchedulesMap[$scheduleKey]['shiftId'] . "
					WHERE USER_ID IN (" . implode(', ', $chunkUserIds) . ');'
				);
				$this->savedSchedulesMap[$scheduleKey]['usersUpdated'] = true;
			}
		}
		$this->logMessage('update SCHEDULE_ID for records - done', __LINE__);

		# just to prevent any record data with no scheduleId mapping
		foreach ($this->scheduleForms as $scheduleFormKey => $scheduleForm)
		{
			if ($scheduleForm->isForAllUsers)
			{
				Application::getConnection()->query("
					UPDATE b_timeman_entries
					SET 
					TIMESTAMP_X = TIMESTAMP_X,
					SCHEDULE_ID = " . (int)$this->savedSchedulesMap[$scheduleFormKey]['scheduleId'] . ",
					SHIFT_ID =  " . (int)$this->savedSchedulesMap[$scheduleFormKey]['shiftId'] . "
					WHERE SCHEDULE_ID = 0;"
				);
				break;
			}
		}

		Application::getConnection()->query("DROP TABLE `b_timeman_converter_collected_schedules`;");
		Application::getConnection()->query("DROP TABLE `b_timeman_converter_violation_rules`;");
		Application::getConnection()->query("DROP TABLE `b_timeman_converter_processed_entities`;");
		if ($this->dropLogAfterExecution)
		{
			Application::getConnection()->query("DROP TABLE `b_timeman_converter_log`;");
		}
		$this->logMessage('DROP helpers tables - done', __LINE__);

		return true;
	}

	private function saveUserToScheduleMap($data, $type)
	{
		if ($type === 'user')
		{
			$userId = $data;
			$tmUser = new TimemanVersion18User($userId);
			$selfSettings = $tmUser->getSettings();

			if ($selfSettings['UF_TIMEMAN'] === false)
			{
				return;
			}
			if (!isset($this->userToScheduleMap[$userId]))
			{
				$key = $this->createScheduleSettingsKey($selfSettings);
				if ($this->scheduleForms[$key])
				{
					$this->userToScheduleMap[$userId] = $key;
				}
			}
		}
		else
		{
			foreach ($data['data']['EMPLOYEES'] as $userId)
			{
				$this->saveUserToScheduleMap($userId, 'user');
			}
			foreach ($data['subDepartments'] as $subDepartmentData)
			{
				$this->saveUserToScheduleMap($subDepartmentData, 'department');
			}
		}
	}

	private function createScheduleSettingsKey($settings)
	{
		if ($settings['UF_TM_FREE'] === 'Y' || $settings['UF_TM_FREE'] === true)
		{
			return 'FREE';
		}
		return 'COMMON';
	}

	private function buildDepartmentsTree()
	{
		if ($this->departmentsTree === null)
		{
			$results = [];
			$allDepartments = \CIntranetUtils::getStructure()['DATA'];
			foreach ($allDepartments as $departmentId => $depData)
			{
				if (isset($allDepartments[$departmentId]))
				{
					$results[$departmentId]['data'] = $allDepartments[$departmentId];
					$results[$departmentId]['subDepartments'] = $this->getChildrenData($departmentId, $allDepartments);
				}
			}
			$this->departmentsTree = $results;
		}
		return $this->departmentsTree;
	}

	private function getChildrenData($departmentId, &$allDepartments)
	{
		$firstLevelChildren = (array)CIntranetUtils::getSubDepartments($departmentId);
		$res = [];
		foreach ($firstLevelChildren as $firstLevelDepId)
		{
			if (isset($allDepartments[$firstLevelDepId]))
			{
				$res[$firstLevelDepId]['data'] = $allDepartments[$firstLevelDepId];
				$res[$firstLevelDepId]['subDepartments'] = $this->getChildrenData($firstLevelDepId, $allDepartments);
				unset($allDepartments[$firstLevelDepId]);
			}
		}
		return $res;
	}

	private function getDefaultTimemanSettings()
	{
		return TimemanVersion18User::getModuleSettings($this->getTimemanSettingsNames());
	}

	private function getTimemanSettingsNames()
	{
		return [
			'UF_TIMEMAN',
			'UF_TM_FREE',
			'UF_TM_MAX_START',
			'UF_TM_MIN_FINISH',
			'UF_TM_MIN_DURATION',
			'UF_TM_ALLOWED_DELTA',
		];
	}

	private function createViolationForm($maxExactStart, $minExactEnd, $minDayDuration, $maxAllowedToEditWorkTime)
	{
		$form = new ViolationForm();
		$form->maxExactStart = $maxExactStart;
		$form->minExactEnd = $minExactEnd;
		$form->minDayDuration = $minDayDuration;
		$form->maxAllowedToEditWorkTime = $maxAllowedToEditWorkTime;
		$form->editWorktimeNotifyUsers = [ViolationRulesTable::USERS_TO_NOTIFY_USER_MANAGER,];

		return $form;
	}

	private function createScheduleForm($key = null)
	{
		$restrictions = [
			ScheduleTable::WORKTIME_RESTRICTION_ALLOWED_TO_REOPEN_RECORD => COption::getOptionString('timeman', 'workday_close_undo', 'Y') === 'Y',
			ScheduleTable::WORKTIME_RESTRICTION_ALLOWED_TO_EDIT_RECORD => COption::getOptionString('timeman', 'workday_can_edit_current', 'Y') === 'Y',
		];

		$shiftStart = COption::getOptionInt('timeman', 'workday_start', 32400);
		$shiftEnd = COption::getOptionInt('timeman', 'workday_finish', 64800);
		$scheduleForm = new \Bitrix\Timeman\Form\Schedule\ScheduleForm();

		if ($key === 'FREE')
		{
			$scheduleForm->load([
				$scheduleForm->getFormName() => [
					'type' => ScheduleTable::SCHEDULE_TYPE_FLEXTIME,
					'name' => Loc::getMessage('TIMEMAN_CONVERTER_SCHEDULE_FLEXTIME_NAME'),
					'reportPeriod' => ScheduleTable::REPORT_PERIOD_MONTH,
					'reportPeriodStartWeekDay' => ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_MONDAY,
					'worktimeRestrictions' => [
						ScheduleTable::WORKTIME_RESTRICTION_ALLOWED_TO_REOPEN_RECORD => true,
						ScheduleTable::WORKTIME_RESTRICTION_ALLOWED_TO_EDIT_RECORD => true,
					],
					'assignments' => [],
					'ShiftForm' => [],
					'CalendarForm' => [
						'calendarId' => '',
						'parentId' => '',
						'datesJson' => '{}',
					],
					'ViolationForm' => [
						'scheduleId' => '',
						'maxExactStartFormatted' => '--:--',
						'minExactEndFormatted' => '--:--',
						'relativeStartFromFormatted' => '--:--',
						'relativeStartToFormatted' => '--:--',
						'relativeEndFromFormatted' => '--:--',
						'relativeEndToFormatted' => '--:--',
						'minDayDurationFormatted' => '--:--',
						'maxAllowedToEditWorkTimeFormatted' => '--:--',
						'maxShiftStartDelayFormatted' => '--:--',
						'maxWorkTimeLackForPeriod' => '',
					],
					'controlledActions' => ScheduleTable::CONTROLLED_ACTION_START_AND_END,
					'allowedDevices' => [
						'browser' => 'on',
						'b24time' => 'on',
						'mobile' => 'on',
						'mobileRecordLocation' => '',
					],
				],
			]);
			return $scheduleForm;

		}

		$scheduleForm->load([
			$scheduleForm->getFormName() => [
				'type' => ScheduleTable::SCHEDULE_TYPE_FIXED,
				'name' => Loc::getMessage('TIMEMAN_CONVERTER_SCHEDULE_FOR_ALL_USERS_NAME'),
				'reportPeriod' => ScheduleTable::REPORT_PERIOD_MONTH,
				'reportPeriodStartWeekDay' => ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_MONDAY,
				'worktimeRestrictions' => $restrictions,
				'assignments' => [],
				'ShiftForm' => [
					[
						'shiftId' => '',
						'workDays' => '12345',
						'name' => '',
						'startTimeFormatted' => TimeHelper::getInstance()->convertSecondsToHoursMinutes($shiftStart),
						'endTimeFormatted' => TimeHelper::getInstance()->convertSecondsToHoursMinutes($shiftEnd),
						'breakDurationFormatted' => '00:00',
					],
				],
				'CalendarForm' => [
					'calendarId' => '',
					'parentId' => '',
					'datesJson' => '{}',
				],
				'ViolationForm' => [
					'scheduleId' => '',
					'maxExactStartFormatted' => '--:--',
					'minExactEndFormatted' => '--:--',
					'relativeStartFromFormatted' => '--:--',
					'relativeStartToFormatted' => '--:--',
					'relativeEndFromFormatted' => '--:--',
					'relativeEndToFormatted' => '--:--',
					'minDayDurationFormatted' => '--:--',
					'maxAllowedToEditWorkTimeFormatted' => '--:--',
					'maxShiftStartDelayFormatted' => '--:--',
					'maxWorkTimeLackForPeriod' => '',
				],
				'controlledActions' => ScheduleTable::CONTROLLED_ACTION_START_AND_END,
				'allowedDevices' => [
					'browser' => 'on',
					'b24time' => 'on',
					'mobile' => 'on',
					'mobileRecordLocation' => '',
				],
			],
		]);

		return $scheduleForm;
	}

	private function fillDefaultSettingsParams($selfSettings, $parentParams)
	{
		$res = [];
		foreach (['UF_TM_MAX_START', 'UF_TM_MIN_FINISH', 'UF_TM_MIN_DURATION', 'UF_TM_ALLOWED_DELTA', 'UF_TM_FREE'] as $name)
		{
			if (!$this->issetPersonalSetting($selfSettings[$name]))
			{
				$res[$name] = $parentParams[$name];
			}
			else
			{
				$res[$name] = $selfSettings[$name];
				if (is_bool($selfSettings[$name]))
				{
					$res[$name] = $selfSettings[$name] === true ? 'Y' : 'F';
				}
			}
		}
		return $res;
	}

	private function isMaxExecutionSecondsExceeded()
	{
		return time() - $this->timeExecutionStart > $this->maxExecuteSeconds;
	}

	private function deleteOldSchedules()
	{
		if ((int)Option::get(self::$moduleId, 'converter19isOldSchedulesDeleted', 0) === 1)
		{
			return;
		}
		$idsToDelete = array_column(
			DependencyManager::getInstance()
				->getScheduleRepository()
				->getActiveSchedulesQuery()
				->addSelect('ID')
				->exec()
				->fetchAll(),
			'ID'
		);
		foreach ($idsToDelete as $id)
		{
			$res = DependencyManager::getInstance()
				->getScheduleService()
				->delete($id);
			Application::getConnection()
				->query("UPDATE
					b_timeman_entries
					SET TIMESTAMP_X = TIMESTAMP_X,
					SCHEDULE_ID = 0,
					SHIFT_ID = 0
					WHERE SCHEDULE_ID = $id"
				);
			if ($this->isMaxExecutionSecondsExceeded())
			{
				throw new MaximumExecutionSecondsExceededException();
			}
		}
		Option::set(self::$moduleId, 'converter19isOldSchedulesDeleted', 1);
	}

	private function isTimemanTurnedOff($selfSettings)
	{
		return $selfSettings['UF_TIMEMAN'] === 'N' || $selfSettings['UF_TIMEMAN'] === false;
	}

	/**
	 * @param $departmentId
	 * @param ScheduleForm $scheduleForm
	 * @return mixed
	 */
	private function isDepartmentInsideSchedule($departmentId, $scheduleForm)
	{
		$statement = false;
		if ((int)$departmentId === (int)reset(array_keys($this->buildDepartmentsTree())))
		{
			$statement = in_array('UA', $scheduleForm->assignments, true);
		}
		return $statement || in_array('DR' . $departmentId, $scheduleForm->assignments, true);
	}

	/**
	 * @param $departmentId
	 * @param ScheduleForm $scheduleForm
	 * @return bool
	 */
	private function isDepartmentExcludedFromSchedule($departmentId, $scheduleForm)
	{
		return in_array('DR' . $departmentId, $scheduleForm->assignmentsExcluded, true);
	}

	private function isUserInsideSchedule($entityCode, $parentSchedule)
	{
		return in_array($entityCode, $parentSchedule->assignments, true);
	}

	private function hasPersonalViolationRules($selfSettings)
	{
		return $this->issetPersonalSetting($selfSettings['UF_TM_MAX_START']) ||
			   $this->issetPersonalSetting($selfSettings['UF_TM_MIN_FINISH']) ||
			   $this->issetPersonalSetting($selfSettings['UF_TM_MIN_DURATION']) ||
			   $this->issetPersonalSetting($selfSettings['UF_TM_ALLOWED_DELTA']);
	}

	private function assignEntityToSchedule($entityCode, $selfSettings, array $parentDepartmentIds)
	{
		$myScheduleForm = $this->getScheduleForm($selfSettings);
		$oppositeScheduleForm = null;
		if (count($this->scheduleForms) > 1)
		{
			$oppositeScheduleForm = $this->getScheduleForm([], $this->createScheduleSettingsKey($selfSettings) === 'COMMON' ? 'FREE' : 'COMMON');
		}

		$includedInMySchedule = false;
		foreach (array_reverse($parentDepartmentIds) as $parentDepartmentId)
		{
			if ($this->isDepartmentInsideSchedule($parentDepartmentId, $myScheduleForm))
			{
				$includedInMySchedule = true;
				break;
			}
			elseif ($this->isDepartmentExcludedFromSchedule($parentDepartmentId, $myScheduleForm))
			{
				$myScheduleForm->assignments[$entityCode] = $entityCode;
				unset($myScheduleForm->assignmentsExcluded[$entityCode]);
				$includedInMySchedule = true;
				break;
			}
		}
		if (!$includedInMySchedule)
		{
			$myScheduleForm->assignments[$entityCode] = $entityCode;
			unset($myScheduleForm->assignmentsExcluded[$entityCode]);
		}
		if ($oppositeScheduleForm)
		{
			foreach (array_reverse($parentDepartmentIds) as $parentDepartmentId)
			{
				if ($this->isDepartmentExcludedFromSchedule($parentDepartmentId, $oppositeScheduleForm))
				{
					break;
				}
				elseif ($this->isDepartmentInsideSchedule($parentDepartmentId, $oppositeScheduleForm))
				{
					$this->excludeEntityFromScheduleForms($entityCode, [$oppositeScheduleForm]);
					break;
				}
			}
		}
	}
}

class MaximumExecutionSecondsExceededException extends \Exception
{
}