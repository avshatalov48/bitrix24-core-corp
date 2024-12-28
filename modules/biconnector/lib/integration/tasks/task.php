<?php

namespace Bitrix\BIConnector\Integration\Tasks;

use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\BIConnector\DataSource\Dataset;
use Bitrix\BIConnector\DataSource\Field\DateTimeField;
use Bitrix\BIConnector\DataSource\Field\IntegerField;
use Bitrix\BIConnector\DataSource\Field\StringField;
use Bitrix\Tasks\Internals\Task\TimeUnitType;

class Task extends Dataset
{
	protected const FIELD_NAME_PREFIX = 'TASK_FIELD_';

	protected function getResultTableName(): string
	{
		return 'task';
	}

	public function getSqlTableAlias(): string
	{
		return 'T';
	}

	protected function getConnectionTableName(): string
	{
		return 'b_tasks';
	}

	protected function getTableDescription(): string
	{
		return $this->getMessage('TASK_TABLE');
	}

	/**
	 * @return Result
	 */
	protected function onBeforeEvent(): Result
	{
		$result = parent::onBeforeEvent();

		if (!Loader::includeModule('tasks'))
		{
			$result->addError(new Error('Module is not installed'));
		}

		return $result;
	}

	protected function getFields(): array
	{
		$groupJoin = $this->createJoin(
			"SGROUP",
			"INNER JOIN b_sonet_group SGROUP ON SGROUP.ID = {$this->getAliasFieldName('GROUP_ID')}",
			"LEFT JOIN b_sonet_group SGROUP ON SGROUP.ID = {$this->getAliasFieldName('GROUP_ID')}",
		);

		$stageJoin = $this->createJoin(
			"STAGES",
			"INNER JOIN b_tasks_stages STAGES ON STAGES.ID = {$this->getAliasFieldName('STAGE_ID')}",
			"LEFT JOIN b_tasks_stages STAGES ON STAGES.ID = {$this->getAliasFieldName('STAGE_ID')}",
		);

		$createdJoin = $this->createJoin(
			"CREATED",
			"INNER JOIN b_user CREATED ON CREATED.ID = {$this->getAliasFieldName('CREATED_BY')}",
			"LEFT JOIN b_user CREATED ON CREATED.ID = {$this->getAliasFieldName('CREATED_BY')}",
		);

		$responsibleJoin = $this->createJoin(
			"RESPONSIBLE",
			"INNER JOIN b_user RESPONSIBLE ON RESPONSIBLE.ID = {$this->getAliasFieldName('RESPONSIBLE_ID')}",
			"LEFT JOIN b_user RESPONSIBLE ON RESPONSIBLE.ID = {$this->getAliasFieldName('RESPONSIBLE_ID')}",
		);

		$accomplicesJoin = $this->createJoin(
			"TMA",
			"INNER JOIN b_tasks_member TMA ON TMA.TASK_ID = {$this->getAliasFieldName('ID')} AND TMA.TYPE = 'A' ",
			"LEFT JOIN b_tasks_member TMA ON TMA.TASK_ID = {$this->getAliasFieldName('ID')} AND TMA.TYPE = 'A' ",
		);

		$accomplicesUserJoin = $this->createJoin(
			"TMAS",
			"INNER JOIN (
						SELECT U.NAME AS NAME, U.LAST_NAME AS LAST_NAME, U.ID as USER_ID, TMS.TASK_ID as TASK
						FROM b_tasks_member TMS
						LEFT JOIN b_user U on U.ID = TMS.USER_ID
						WHERE TMS.TYPE = 'A'
					) TMAS ON TMAS.TASK = {$this->getAliasFieldName('ID')}",
			"LEFT JOIN (
						SELECT U.NAME AS NAME, U.LAST_NAME AS LAST_NAME, U.ID as USER_ID, TMS.TASK_ID as TASK
						FROM b_tasks_member TMS
						LEFT JOIN b_user U on U.ID = TMS.USER_ID
						WHERE TMS.TYPE = 'A'
					) TMAS ON TMAS.TASK = {$this->getAliasFieldName('ID')}",

		);

		$auditorsJoin = $this->createJoin(
			"TMU",
			"INNER JOIN b_tasks_member TMU ON TMU.TASK_ID = {$this->getAliasFieldName('ID')} AND TMU.TYPE = 'U' ",
			"LEFT JOIN b_tasks_member TMU ON TMU.TASK_ID = {$this->getAliasFieldName('ID')} AND TMU.TYPE = 'U' ",
		);

		$auditorsUserJoin = $this->createJoin(
			"TMUS",
			"INNER JOIN (
						SELECT U.NAME AS NAME, U.LAST_NAME AS LAST_NAME, U.ID as USER_ID, TMS.TASK_ID as TASK
						FROM b_tasks_member TMS
						LEFT JOIN b_user U on U.ID = TMS.USER_ID
						WHERE TMS.TYPE = 'U'
					) TMUS ON TMUS.TASK = {$this->getAliasFieldName('ID')}",
			"LEFT JOIN (
						SELECT U.NAME AS NAME, U.LAST_NAME AS LAST_NAME, U.ID as USER_ID, TMS.TASK_ID as TASK
						FROM b_tasks_member TMS
						LEFT JOIN b_user U on U.ID = TMS.USER_ID
						WHERE TMS.TYPE = 'U'
					) TMUS ON TMUS.TASK = {$this->getAliasFieldName('ID')}",

		);

		$changedJoin = $this->createJoin(
			"CHANGED",
			"INNER JOIN b_user CHANGED ON CHANGED.ID = {$this->getAliasFieldName('CHANGED_BY')}",
			"LEFT JOIN b_user CHANGED ON CHANGED.ID = {$this->getAliasFieldName('CHANGED_BY')}",
		);

		$statusChangedJoin = $this->createJoin(
			"STCHANGED",
			"INNER JOIN b_user STCHANGED ON STCHANGED.ID = {$this->getAliasFieldName('STATUS_CHANGED_BY')}",
			"LEFT JOIN b_user STCHANGED ON STCHANGED.ID = {$this->getAliasFieldName('STATUS_CHANGED_BY')}",
		);

		$closedJoin = $this->createJoin(
			"CLOSED",
			"INNER JOIN b_user CLOSED ON CLOSED.ID = {$this->getAliasFieldName('CLOSED_BY')}",
			"LEFT JOIN b_user CLOSED ON CLOSED.ID = {$this->getAliasFieldName('CLOSED_BY')}",
		);

		$utsJoin = $this->createJoin(
			"UTS_TASK",
			"INNER JOIN b_uts_tasks_task UTS_TASK ON UTS_TASK.VALUE_ID = {$this->getAliasFieldName('ID')}",
			"LEFT JOIN b_uts_tasks_task UTS_TASK ON UTS_TASK.VALUE_ID = {$this->getAliasFieldName('ID')}",
		);

		$tagsJoin = $this->createJoin(
			"TAGS",
			"INNER JOIN (
						SELECT T.TASK_ID AS TASK, L.NAME as NAME
						FROM b_tasks_task_tag T
						LEFT JOIN b_tasks_label L on L.ID = T.TAG_ID
					) TAGS ON TAGS.TASK = {$this->getAliasFieldName('ID')}",
			"LEFT JOIN (
						SELECT T.TASK_ID AS TASK, L.NAME as NAME
						FROM b_tasks_task_tag T
						LEFT JOIN b_tasks_label L on L.ID = T.TAG_ID
					) TAGS ON TAGS.TASK = {$this->getAliasFieldName('ID')}",
		);

		$dependenceJoin = $this->createJoin(
			"DEPENDENCE",
			"INNER JOIN b_tasks_dependence DEPENDENCE ON DEPENDENCE.TASK_ID = {$this->getAliasFieldName('ID')}",
			"LEFT JOIN b_tasks_dependence DEPENDENCE ON DEPENDENCE.TASK_ID = {$this->getAliasFieldName('ID')}",
		);

		$elapsedTimeJoin = $this->createJoin(
			"ELAPSED",
			"INNER JOIN (
						SELECT SUM(SECONDS) AS TIME_SPENT_IN_LOGS, TASK_ID
						FROM b_tasks_elapsed_time
						GROUP BY TASK_ID
					) ELAPSED ON ELAPSED.TASK_ID = {$this->getAliasFieldName('ID')}",
			"LEFT JOIN (
						SELECT SUM(SECONDS) AS TIME_SPENT_IN_LOGS, TASK_ID
						FROM b_tasks_elapsed_time
						GROUP BY TASK_ID
					) ELAPSED ON ELAPSED.TASK_ID = {$this->getAliasFieldName('ID')}",
		);

		$commentsCountJoin = $this->createJoin(
			"UFMC",
			"INNER JOIN (
				SELECT XML_ID, COUNT(1) AS COMMENTS_COUNT
				FROM b_forum_message
				WHERE SERVICE_TYPE is NULL AND PARAM1 is NULL
				GROUP BY XML_ID
			) UFMC on UFMC.XML_ID = CONCAT('TASK_', {$this->getAliasFieldName('ID')})",
			"LEFT JOIN (
				SELECT XML_ID, COUNT(1) AS COMMENTS_COUNT
				FROM b_forum_message
				WHERE SERVICE_TYPE is NULL AND PARAM1 is NULL
				GROUP BY XML_ID
			) UFMC on UFMC.XML_ID = CONCAT('TASK_', {$this->getAliasFieldName('ID')})"
		);

		$flowJoin = $this->createJoin(
			"TFT",
			"INNER JOIN b_tasks_flow_task TFT ON TFT.TASK_ID = {$this->getAliasFieldName('ID')}",
			"LEFT JOIN b_tasks_flow_task TFT ON TFT.TASK_ID = {$this->getAliasFieldName('ID')}",
		);

		$fieldsInfo = \CTasks::getFieldsInfo(false);

		return [
			(new IntegerField('ID'))
				->setPrimary()
			,
			(new IntegerField('PARENT_ID')),
			(new StringField('TITLE')),
			(new StringField('DESCRIPTION'))
				->setCallback(
					static function($value) {
						if (empty($value))
						{
							return '';
						}

						return strlen($value) > 128 ? mb_substr($value, 0, 128) . '...' : $value;
					}
				)
			,
			(new StringField('MARK'))
				->setDictionary(
					$fieldsInfo['MARK']['values'] ?? [],
					$this->getMessage('TASK_FIELD_MARK_NULL_VALUE')
				)
				->setDescription($this->getMessage('TASK_FIELD_MARK_MSGVER_1'))
				->setDescriptionFull($this->getMessage('TASK_FIELD_MARK_FULL_MSGVER_1'))
			,
			(new StringField('PRIORITY'))
				->setDictionary($fieldsInfo['PRIORITY']['values'] ?? [])
			,
			(new StringField('STATUS'))
				->setDictionary($fieldsInfo['STATUS']['values'] ?? [])
			,
			(new IntegerField('GROUP_ID')),
			(new StringField('GROUP_NAME'))
				->setName($groupJoin->getJoinFieldName('NAME'))
				->setJoin($groupJoin)
			,
			(new StringField('GROUP_INFO'))
				->setName("
					if(
						{$this->getAliasFieldName('GROUP_ID')} > 0,
						concat_ws(
							' ', 
							concat('[', {$this->getAliasFieldName('GROUP_ID')}, ']'), 
							nullif({$groupJoin->getJoinFieldName('NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($groupJoin)
			,
			(new StringField('MULTITASK')),
			(new IntegerField('STAGE_ID')),
			(new StringField('STAGE_NAME'))
				->setName($stageJoin->getJoinFieldName('TITLE'))
				->setJoin($stageJoin)
			,
			(new StringField('STAGE'))
				->setName("
					if(
						{$this->getAliasFieldName('STAGE_ID')} > 0,
						concat_ws(
							' ', 
							concat('[', {$this->getAliasFieldName('STAGE_ID')}, ']'), 
							nullif({$stageJoin->getJoinFieldName('TITLE')}, '')
						),
						NULL
					)"
				)
				->setJoin($stageJoin)
			,
			(new IntegerField('CREATED_BY_ID'))
				->setName($this->getAliasFieldName('CREATED_BY'))
			,
			(new StringField('CREATED_BY_NAME'))
				->setName("
					if(
						{$this->getAliasFieldName('CREATED_BY')} > 0,
						concat_ws(
							' ', 
							nullif({$createdJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$createdJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($createdJoin)
			,
			(new StringField('CREATED_BY'))
				->setName("
					if(
						{$this->getAliasFieldName('CREATED_BY')} > 0,
						concat_ws(
							' ', 
							concat('[', {$this->getAliasFieldName('CREATED_BY')}, ']'), 
							nullif({$createdJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$createdJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($createdJoin)
			,
			(new DateTimeField('CREATED_DATE')),
			(new IntegerField('RESPONSIBLE_ID')),
			(new StringField('RESPONSIBLE_NAME'))
				->setName("
					if(
						{$this->getAliasFieldName('RESPONSIBLE_ID')} > 0,
						concat_ws(
							' ', 
							nullif({$responsibleJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$responsibleJoin->getJoinFieldName('LAST_NAME')} , '')
						),
						NULL
					)"
				)
				->setJoin($responsibleJoin)
			,
			(new StringField('RESPONSIBLE'))
				->setName("
					if(
						{$this->getAliasFieldName('RESPONSIBLE_ID')} > 0,
						concat_ws(
							' ', 
							concat('[', {$this->getAliasFieldName('RESPONSIBLE_ID')}, ']'), 
							nullif({$responsibleJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$responsibleJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($responsibleJoin)
			,
			(new StringField('ACCOMPLICES_IDS'))
				->setName($accomplicesJoin->getJoinFieldName('USER_ID'))
				->setJoin($accomplicesJoin)
				->setMultiple()
			,
			(new StringField('ACCOMPLICES_NAMES'))
				->setName("
					if(
						{$accomplicesUserJoin->getJoinFieldName('USER_ID')} > 0,
						concat_ws(
							' ', 
							nullif({$accomplicesUserJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$accomplicesUserJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($accomplicesUserJoin)
				->setMultiple()
			,
			(new StringField('ACCOMPLICES'))
				->setName("
					if(
						{$accomplicesUserJoin->getJoinFieldName('USER_ID')} > 0,
						concat_ws(
							' ',
							concat('[', {$accomplicesUserJoin->getJoinFieldName('USER_ID')}, ']'), 
							nullif({$accomplicesUserJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$accomplicesUserJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($accomplicesUserJoin)
				->setMultiple()
			,

			(new StringField('AUDITORS_IDS'))
				->setName($auditorsJoin->getJoinFieldName('USER_ID'))
				->setJoin($auditorsJoin)
				->setMultiple()
			,
			(new StringField('AUDITORS_NAMES'))
				->setName("
					if(
						{$auditorsUserJoin->getJoinFieldName('USER_ID')} > 0,
						concat_ws(
							' ', 
							nullif({$auditorsUserJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$auditorsUserJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($auditorsUserJoin)
				->setMultiple()
			,
			(new StringField('AUDITORS'))
				->setName("
					if(
						{$auditorsUserJoin->getJoinFieldName('USER_ID')} > 0,
						concat_ws(
							' ',
							concat('[', {$auditorsUserJoin->getJoinFieldName('USER_ID')}, ']'), 
							nullif({$auditorsUserJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$auditorsUserJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($auditorsUserJoin)
				->setMultiple()
			,
			(new IntegerField('CHANGED_BY_ID'))
				->setName($this->getAliasFieldName('CHANGED_BY'))
			,
			(new StringField('CHANGED_BY_NAME'))
				->setName("
					if(
						{$this->getAliasFieldName('CHANGED_BY')} > 0,
						concat_ws(
							' ', 
							nullif({$changedJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$changedJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($changedJoin)
			,
			(new StringField('CHANGED_BY'))
				->setName("
					if(
						{$this->getAliasFieldName('CHANGED_BY')} > 0,
						concat_ws(
							' ', 
							concat('[', {$this->getAliasFieldName('CHANGED_BY')}, ']'), 
							nullif({$changedJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$changedJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($changedJoin)
			,
			(new DateTimeField('CHANGED_DATE')),
			(new IntegerField('STATUS_CHANGED_BY_ID'))
				->setName($this->getAliasFieldName('STATUS_CHANGED_BY'))
			,
			(new StringField('STATUS_CHANGED_BY_NAME'))
				->setName("
					if(
						{$this->getAliasFieldName('STATUS_CHANGED_BY')} > 0,
						concat_ws(
							' ', 
							nullif({$statusChangedJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$statusChangedJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($statusChangedJoin)
			,
			(new StringField('STATUS_CHANGED_BY'))
				->setName("
					if(
						{$this->getAliasFieldName('STATUS_CHANGED_BY')} > 0,
						concat_ws(
							' ', 
							concat('[', {$this->getAliasFieldName('STATUS_CHANGED_BY')}, ']'), 
							nullif({$statusChangedJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$statusChangedJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($statusChangedJoin)
			,
			(new DateTimeField('STATUS_CHANGED_DATE')),
			(new IntegerField('CLOSED_BY_ID'))
				->setName($this->getAliasFieldName('CLOSED_BY'))
			,
			(new StringField('CLOSED_BY_NAME'))
				->setName("
					if(
						{$this->getAliasFieldName('CLOSED_BY')} > 0,
						concat_ws(
							' ', 
							nullif({$closedJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$closedJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($closedJoin)
			,
			(new StringField('CLOSED_BY'))
				->setName("
					if(
						{$this->getAliasFieldName('CLOSED_BY')} > 0,
						concat_ws(
							' ', 
							concat('[', {$this->getAliasFieldName('CLOSED_BY')}, ']'), 
							nullif({$closedJoin->getJoinFieldName('NAME')}, ''), 
							nullif({$closedJoin->getJoinFieldName('LAST_NAME')}, '')
						),
						NULL
					)"
				)
				->setJoin($closedJoin)
			,
			(new DateTimeField('CLOSED_DATE')),
			(new DateTimeField('ACTIVITY_DATE')),
			(new DateTimeField('DATE_START')),
			(new DateTimeField('DEADLINE')),
			(new DateTimeField('START_DATE_PLAN')),
			(new DateTimeField('END_DATE_PLAN')),
			(new StringField('ALLOW_CHANGE_DEADLINE')),
			(new StringField('ALLOW_TIME_TRACKING')),
			(new StringField('TASK_CONTROL'))
				->setDescription($this->getMessage('TASK_FIELD_TASK_CONTROL_MSGVER_1'))
				->setDescriptionFull($this->getMessage('TASK_FIELD_TASK_CONTROL_FULL_MSGVER_1'))
			,
			(new StringField('ADD_IN_REPORT')),
			(new IntegerField('TIME_ESTIMATE')),
			(new StringField('MATCH_WORK_TIME')),
			(new IntegerField('DURATION_PLAN')),
			(new StringField('DURATION_TYPE'))
				->setDictionary([
					TimeUnitType::SECOND => $this->getMessage('TASK_FIELD_DURATION_TYPE_VALUE_TYPE_SECOND'),
					TimeUnitType::MINUTE => $this->getMessage('TASK_FIELD_DURATION_TYPE_VALUE_TYPE_MINUTE'),
					TimeUnitType::HOUR => $this->getMessage('TASK_FIELD_DURATION_TYPE_VALUE_TYPE_HOUR'),
					TimeUnitType::DAY => $this->getMessage('TASK_FIELD_DURATION_TYPE_VALUE_TYPE_DAY'),
					TimeUnitType::WEEK => $this->getMessage('TASK_FIELD_DURATION_TYPE_VALUE_TYPE_WEEK'),
					TimeUnitType::MONTH => $this->getMessage('TASK_FIELD_DURATION_TYPE_VALUE_TYPE_MONTH'),
					TimeUnitType::YEAR => $this->getMessage('TASK_FIELD_DURATION_TYPE_VALUE_TYPE_YEAR'),
				])
			,
			(new StringField('CRM_TASK'))
				->setName($utsJoin->getJoinFieldName('UF_CRM_TASK'))
				->setJoin($utsJoin)
				->setMultiple()
				->setCallback(
					static function($value) {
						if (!empty($value))
						{
							$value = unserialize($value, ['allowed_classes' => false]);
						}

						return is_array($value) ? implode(', ', $value) : null;
					}
				)
			,
			(new StringField('TAGS'))
				->setName($tagsJoin->getJoinFieldName('NAME'))
				->setJoin($tagsJoin)
				->setMultiple()
			,
			(new IntegerField('DEPENDS_ON'))
				->setName($dependenceJoin->getJoinFieldName('DEPENDS_ON_ID'))
				->setJoin($dependenceJoin)
				->setMultiple()
			,
			(new IntegerField('TIME_SPENT_IN_LOGS'))
				->setName($elapsedTimeJoin->getJoinFieldName('TIME_SPENT_IN_LOGS'))
				->setJoin($elapsedTimeJoin)
			,
			(new IntegerField('COMMENTS_COUNT'))
				->setName($commentsCountJoin->getJoinFieldName('COMMENTS_COUNT'))
				->setJoin($commentsCountJoin)
			,
			(new IntegerField('FLOW_ID'))
				->setName($flowJoin->getJoinFieldName('FLOW_ID'))
				->setJoin($flowJoin)
			,
		];
	}
}
