<?php
namespace Bitrix\Tasks\Grid\Task\Row;

use Bitrix\Main;
use Bitrix\Tasks\Integration\CRM\Fields\Mapper;

/**
 * Class Content
 *
 * @package Bitrix\Tasks\Grid\Task\Row
 */
class Content
{
	protected $rowData = [];
	protected $parameters = [];
	protected $fieldKey;

	public function __construct(array $rowData = [], array $parameters = [], string $fieldKey = null)
	{
		$this->rowData = $rowData;
		$this->parameters = $parameters;
		$this->fieldKey = $fieldKey;
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 */
	public function prepare()
	{
		$resultRow = [];

		if (array_key_exists('REAL_STATUS', $this->rowData))
		{
			$this->rowData['REAL_STATUS'] = (int)$this->rowData['REAL_STATUS'];
		}
		elseif (array_key_exists('STATUS', $this->rowData))
		{
			$this->rowData['REAL_STATUS'] = (int)$this->rowData['STATUS'];
		}

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

			'CREATED_DATE' => Content\Date\FormattedDate::class,
			'CHANGED_DATE' => Content\Date\FormattedDate::class,
			'CLOSED_DATE' => Content\Date\FormattedDate::class,
			'ACTIVITY_DATE' => Content\Date\ActivityDate::class,
			'DEADLINE' => Content\Date\Deadline::class,
			'DATE' => Content\Date\FormattedDate::class,
		];

		foreach ($this->rowData as $key => $value)
		{
			if (array_key_exists($key, $prepareMap))
			{
				/** @var Content $class */
				$class = $prepareMap[$key];
				$resultRow[$key] = (new $class($this->rowData, $this->parameters, $key))->prepare();
			}
			else
			{
				$resultRow[$key] = $value;
			}
		}

		foreach ($prepareMap as $key => $value)
		{
			if (array_key_exists($key, $resultRow))
			{
				continue;
			}
			$resultRow[$key] = (new $value($this->rowData, $this->parameters, $key))->prepare();
		}

		if (isset($this->parameters['UF']) && is_array($this->parameters['UF']))
		{
			foreach ($this->parameters['UF'] as $ufName => $ufItem)
			{
				$this->parameters['USER_FIELD_NAME'] = $ufName;
				$resultRow[$ufName] = (new Content\UserField($this->rowData, $this->parameters, $ufName))->prepare();
				unset($this->parameters['USER_FIELD_NAME']);
			}
		}

		if (Main\Loader::includeModule('crm'))
		{
			foreach (Mapper::CRM_FIELDS as $key => $fieldId)
			{
				$this->parameters['CRM_FIELD_ID'] = $fieldId;
				$resultRow[$key] = (new Content\CrmField($this->rowData, $this->parameters, $key))->prepare();
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