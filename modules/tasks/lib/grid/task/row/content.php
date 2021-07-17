<?php
namespace Bitrix\Tasks\Grid\Task\Row;

use Bitrix\Main;

/**
 * Class Content
 *
 * @package Bitrix\Tasks\Grid\Task\Row
 */
class Content
{
	protected $rowData = [];
	protected $parameters = [];

	public function __construct(array $rowData = [], array $parameters = [])
	{
		$this->rowData = $rowData;
		$this->parameters = $parameters;
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 */
	public function prepare()
	{
		$resultRow = [];

		$this->rowData['REAL_STATUS'] = (
			isset($this->rowData['REAL_STATUS'])
				? (int)$this->rowData['REAL_STATUS']
				: (int)$this->rowData['STATUS']
		);

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
			$resultRow[$key] = (new $class($this->rowData, $this->parameters))->prepare();
		}

		if (isset($this->parameters['UF']) && is_array($this->parameters['UF']))
		{
			foreach ($this->parameters['UF'] as $ufName => $ufItem)
			{
				$this->parameters['USER_FIELD_NAME'] = $ufName;
				$resultRow[$ufName] = (new Content\UserField($this->rowData, $this->parameters))->prepare();
				unset($this->parameters['USER_FIELD_NAME']);
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
				$this->parameters['CRM_FIELD_ID'] = $fieldId;
				$resultRow[$key] = (new Content\CrmField($this->rowData, $this->parameters))->prepare();
			}
		}

		return $resultRow;
	}

	public function getRowData(): array
	{
		return $this->rowData;
	}

	public function setRowData(array $rowData): void
	{
		$this->rowData = $rowData;
	}

	public function getParameters(): array
	{
		return $this->parameters;
	}

	public function setParameters(array $parameters): void
	{
		$this->parameters = $parameters;
	}
}