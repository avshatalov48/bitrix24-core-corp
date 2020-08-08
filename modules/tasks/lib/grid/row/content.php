<?php
namespace Bitrix\Tasks\Grid\Row;

use Bitrix\Main;

/**
 * Class Content
 *
 * @package Bitrix\Tasks\Grid\Row
 */
class Content
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 */
	public static function prepare(array $row, array $parameters)
	{
		$resultRow = [];

		$row['REAL_STATUS'] = (isset($row['REAL_STATUS']) ? (int)$row['REAL_STATUS'] : (int)$row['STATUS']);

		$prepareMap = [
			'ID' => Content\TaskId::class,
			'PARENT_ID' => Content\ParentId::class,
			'STATUS' => Content\Status::class,
			'PRIORITY' => Content\Priority::class,
			'TAG' => Content\Tag::class,
			'MARK' => Content\Mark::class,
			'TITLE' => Content\Title::class,
			'GROUP_NAME' => Content\Project::class,
			'FLAG_COMPLETE' => Content\CompleteFlag::class,
			'TIME_ESTIMATE' => Content\TimeEstimate::class,
			'TIME_SPENT_IN_LOGS' => Content\TimeSpentInLogs::class,
			'ALLOW_TIME_TRACKING' => Content\TimeTrackingPermission::class,
			'ALLOW_CHANGE_DEADLINE' => Content\ChangeDeadlinePermission::class,

			'ORIGINATOR_NAME' => Content\UserName\Originator::class,
			'RESPONSIBLE_NAME' => Content\UserName\Responsible::class,

			'CREATED_DATE' => Content\Date\CreatedDate::class,
			'CHANGED_DATE' => Content\Date\ChangedDate::class,
			'CLOSED_DATE' => Content\Date\ClosedDate::class,
			'ACTIVITY_DATE' => Content\Date\ActivityDate::class,
			'DEADLINE' => Content\Date\Deadline::class,
		];
		foreach ($prepareMap as $key => $class)
		{
			/** @var Content $class */
			$resultRow[$key] = $class::prepare($row, $parameters);
		}

		if (isset($parameters['UF']) && is_array($parameters['UF']))
		{
			foreach ($parameters['UF'] as $ufName => $ufItem)
			{
				$parameters['USER_FIELD_NAME'] = $ufName;
				$resultRow[$ufName] = Content\UserField::prepare($row, $parameters);
				unset($parameters['USER_FIELD_NAME']);
			}
		}

		if (Main\Loader::includeModule('crm'))
		{
			$crmFieldsMap = [
				'UF_CRM_TASK_LEAD' => 'L',
				'UF_CRM_TASK_CONTACT' => 'C',
				'UF_CRM_TASK_COMPANY' => 'CO',
				'UF_CRM_TASK_DEAL' => 'D',
			];
			foreach ($crmFieldsMap as $key => $fieldId)
			{
				$parameters['CRM_FIELD_ID'] = $fieldId;
				$resultRow[$key] = Content\CrmField::prepare($row, $parameters);
			}
		}

		return $resultRow;
	}
}