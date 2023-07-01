<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !==true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\Import\PersonNameFormatter;
use Bitrix\Tasks\Manager\Task;
use Bitrix\Tasks\Util\Error\Collection;

Loc::loadMessages(__FILE__);

/**
 * Class TasksImportAjaxController
 */
class TasksImportAjaxController extends Main\Engine\Controller
{
	private $usersById;
	private $usersByName;
	private $projectsByName;
	private $importParameters;
	private static $nameFormats;
	private static $keysMap = [
		'TITLE' => [
			'RESULT_KEY' => 'TITLE',
			'CHECK_TYPE' => 'EQUAL',
		],
		'DESCRIPTION' => [
			'RESULT_KEY' => 'DESCRIPTION',
			'CHECK_TYPE' => 'EQUAL',
		],
		'PRIORITY' => [
			'RESULT_KEY' => 'PRIORITY',
			'CHECK_TYPE' => 'BOOL',
			'TRUE_RESULT' => 2,
			'FALSE_RESULT' => 1,
		],
		'RESPONSIBLE' => [
			'RESULT_KEY' => 'RESPONSIBLE_ID',
			'CHECK_TYPE' => 'FUNCTION',
			'FUNCTION_NAME' => 'getUserIdByName',
		],
		'ORIGINATOR' => [
			'RESULT_KEY' => 'CREATED_BY',
			'CHECK_TYPE' => 'FUNCTION',
			'FUNCTION_NAME' => 'getUserIdByName',
		],
		'ACCOMPLICES' => [
			'RESULT_KEY' => 'ACCOMPLICES',
			'CHECK_TYPE' => 'FUNCTION',
			'FUNCTION_NAME' => 'getUserIdsFromStringData',
		],
		'AUDITORS' => [
			'RESULT_KEY' => 'AUDITORS',
			'CHECK_TYPE' => 'FUNCTION',
			'FUNCTION_NAME' => 'getUserIdsFromStringData',
		],
		'DEADLINE' => [
			'RESULT_KEY' => 'DEADLINE',
			'CHECK_TYPE' => 'EQUAL',
		],
		'START_DATE_PLAN' => [
			'RESULT_KEY' => 'START_DATE_PLAN',
			'CHECK_TYPE' => 'EQUAL',
		],
		'END_DATE_PLAN' => [
			'RESULT_KEY' => 'END_DATE_PLAN',
			'CHECK_TYPE' => 'EQUAL',
		],
		'ALLOW_CHANGE_DEADLINE' => [
			'RESULT_KEY' => 'ALLOW_CHANGE_DEADLINE',
			'CHECK_TYPE' => 'BOOL',
			'TRUE_RESULT' => 'Y',
			'FALSE_RESULT' => 'N',
		],
		'MATCH_WORK_TIME' => [
			'RESULT_KEY' => 'MATCH_WORK_TIME',
			'CHECK_TYPE' => 'BOOL',
			'TRUE_RESULT' => 'Y',
			'FALSE_RESULT' => 'N',
		],
		'TASK_CONTROL' => [
			'RESULT_KEY' => 'TASK_CONTROL',
			'CHECK_TYPE' => 'BOOL',
			'TRUE_RESULT' => 'Y',
			'FALSE_RESULT' => 'N',
		],
		'PARAM_1' => [
			'RESULT_KEY' => 'PARAM_1',
			'CHECK_TYPE' => 'BOOL',
			'TRUE_RESULT' => 'Y',
			'FALSE_RESULT' => 'N',
		],
		'PARAM_2' => [
			'RESULT_KEY' => 'PARAM_2',
			'CHECK_TYPE' => 'BOOL',
			'TRUE_RESULT' => 'Y',
			'FALSE_RESULT' => 'N',
		],
		'PROJECT' => [
			'RESULT_KEY' => 'GROUP_ID',
			'CHECK_TYPE' => 'FUNCTION',
			'FUNCTION_NAME' => 'getProjectIdByName',
		],
		'ALLOW_TIME_TRACKING' => [
			'RESULT_KEY' => 'ALLOW_TIME_TRACKING',
			'CHECK_TYPE' => 'BOOL',
			'TRUE_RESULT' => 'Y',
			'FALSE_RESULT' => 'N',
		],
		'TIME_ESTIMATE' => [
			'RESULT_KEY' => 'TIME_ESTIMATE',
			'CHECK_TYPE' => 'INT',
		],
		'CHECKLIST' => [
			'RESULT_KEY' => 'SE_CHECKLIST',
			'CHECK_TYPE' => 'FUNCTION',
			'FUNCTION_NAME' => 'getCheckListItemsFromStringData',
		],
		'TAGS' => [
			'RESULT_KEY' => 'TAGS',
			'CHECK_TYPE' => 'FUNCTION',
			'FUNCTION_NAME' => 'getListFromStringWithDelimiter',
		],
	];

	/**
	 * Returns parameters for the next import or error's collection
	 *
	 * @param $importParameters - Import parameters
	 * @return AjaxJson
	 */
	public function startImportAction($importParameters): AjaxJson
	{
		$tmpFilePath = CTempFile::GetDirectoryName(2, 'tasks');

		if ($this->checkPrimaryErrors($importParameters['FILE_HASH'], $tmpFilePath))
		{
			return AjaxJson::createError($this->errorCollection);
		}

		$this->initParameters($importParameters);
		$headersInFirstRow = $this->importParameters['HEADERS_IN_FIRST_ROW'];

		$fileHash = preg_replace('/[^a-f0-9]/i', '', $this->importParameters['FILE_HASH']);
		$filePath = "{$tmpFilePath}{$fileHash}.tmp";

		$csvFile = new CCSVData();
		$csvFile->LoadFile($filePath);
		$csvFile->SetFieldsType('R');
		$csvFile->SetFirstHeader($headersInFirstRow);
		$csvFile->SetPos($this->importParameters['FILE_POS']);
		$csvFile->SetDelimiter($this->importParameters['SEPARATOR']);

		if ((int)$this->importParameters['FILE_POS'] === 0)
		{
			$this->importParameters['IMPORTS_TOTAL_COUNT'] = $this->getLinesCount($csvFile);
			$csvFile->SetPos(0);
			$csvFile->SetFirstHeader($headersInFirstRow);
		}

		if ($this->parseCsvFile($csvFile))
		{
			return AjaxJson::createSuccess($this->importParameters);
		}

		return AjaxJson::createError($this->errorCollection);
	}

	/**
	 * Checks errors and returns true if they exist otherwise returns false
	 *
	 * @param $fileHash - File's hash to check
	 * @param $tmpFilePath - File's path to check
	 * @return bool - True if errors exist otherwise false
	 */
	private function checkPrimaryErrors($fileHash, $tmpFilePath): bool
	{
		if (!CModule::IncludeModule('tasks'))
		{
			$this->errorCollection[] = new Main\Error(
				Loc::getMessage('TASKS_IMPORT_ERRORS_MODULE_TASKS_NOT_INCLUDED')
			);
		}
		if (!CModule::IncludeModule('socialnetwork'))
		{
			$this->errorCollection[] = new Main\Error(
				Loc::getMessage('TASKS_IMPORT_ERRORS_MODULE_SOCIAL_NETWORK_NOT_INCLUDED')
			);
		}
		if (!preg_match('/[0-9a-f]{32}\.tmp/i', $fileHash))
		{
			$this->errorCollection[] = new Main\Error(
				Loc::getMessage('TASKS_IMPORT_ERRORS_WRONG_FILE_HASH')
			);
		}
		if (!CheckDirPath($tmpFilePath))
		{
			$this->errorCollection[] = new Main\Error(
				Loc::getMessage('TASKS_IMPORT_ERRORS_WRONG_FILE_PATH')
			);
		}

		return count($this->errorCollection) > 0;
	}

	/**
	 * Creates classes properties based on import parameters
	 *
	 * @param $importParameters - Input import parameters
	 */
	private function initParameters($importParameters): void
	{
		$fileEncoding = $importParameters['FILE_ENCODING'];
		$fileEncoding = ($fileEncoding === 'auto' ? $importParameters['FOUND_FILE_ENCODING'] : $fileEncoding);

		$importParameters['FILE_ENCODING'] = $fileEncoding;
		$importParameters['MAX_EXECUTION_TIME'] = (int)$importParameters['MAX_EXECUTION_TIME'];
		$importParameters['HEADERS_IN_FIRST_ROW'] = $importParameters['HEADERS_IN_FIRST_ROW'] === "true";
		$importParameters['IMPORTS_TOTAL_COUNT'] = ($importParameters['IMPORTS_TOTAL_COUNT'] ?? 0);
		$importParameters['CURRENT_LINE'] = ($importParameters['CURRENT_LINE'] ?? 0);
		$importParameters['SUCCESSFUL_IMPORTS'] = 0;
		$importParameters['ERROR_IMPORTS'] = 0;
		$importParameters['ERROR_IMPORTS_MESSAGES'] = [];

		$this->usersById = ($importParameters['USERS_BY_ID'] ?? []);
		$this->usersByName = ($importParameters['USERS_BY_NAME'] ?? []);
		$this->projectsByName = ($importParameters['PROJECTS_BY_NAME'] ?? []);
		$this->importParameters = $importParameters;

		self::$nameFormats = $this->getNameFormats();
	}

	/**
	 * Returns the amount of lines in csv file to import
	 *
	 * @param CCSVData $csvFile - CSV file
	 * @return int - The amount of lines
	 */
	private function getLinesCount(CCSVData $csvFile): int
	{
		$linesCount = 0;
		while ($taskData = $csvFile->Fetch())
		{
			$linesCount++;
		}

		return $linesCount;
	}

	/**
	 * Parses CSV file and returns true if parsing is OK otherwise returns false
	 *
	 * @param CCSVData $csvFile - CSV file
	 * @return bool
	 */
	private function parseCsvFile(CCSVData $csvFile): bool
	{
		$allLinesLoaded = true;
		while ($taskData = $csvFile->Fetch())
		{
			$this->importParameters['CURRENT_LINE']++;

			$taskData = $this->encodeDataToSiteCharset($taskData, $this->importParameters['FILE_ENCODING']);
			$taskProperties = $this->getTaskPropertiesFromTaskData(
				$taskData,
				$this->importParameters['SELECTED_FIELDS'],
				$this->importParameters['SKIPPED_COLUMNS']
			);
			$taskProperties = $this->checkTaskPropertiesBeforeCreatingTask(
				$taskProperties,
				$this->importParameters['DEFAULT_ORIGINATOR'],
				$this->importParameters['DEFAULT_RESPONSIBLE']
			);

			// try
			// {
				$userId = $this->getCurrentUser()->getId();
				$newTask = Task::add($userId, $taskProperties, ['PUBLIC_MODE' => true, 'RETURN_ENTITY' => false]);

				/** @var Collection $errors */
				$errors = $newTask['ERRORS'];
				if ($errors->checkNoFatals())
				{
					$this->importParameters['SUCCESSFUL_IMPORTS']++;
				}
				else
				{
					$this->addImportError(implode('; ', $errors->getMessages()));
				}
			// }
			// catch (\TasksException $e)
			// {
			// 	$message = unserialize($e->getMessage(), ['allowed_classes' => false]);
			// 	$this->addImportError($message[0]['text']);
			// }
			// catch (Exception $e)
			// {
			// 	$this->addImportError($e->getMessage());
			// }

			$maxExecutionTime = $this->importParameters['MAX_EXECUTION_TIME'];
			if (($maxExecutionTime > 0) && ((getmicrotime() - START_EXEC_TIME) > $maxExecutionTime))
			{
				$allLinesLoaded = false;
				break;
			}
		}

		$this->importParameters['ALL_LINES_LOADED'] = $allLinesLoaded;
		$this->importParameters['FILE_POS'] = $csvFile->GetPos();
		$this->importParameters['HEADERS_IN_FIRST_ROW'] = false;
		$this->importParameters['USERS_BY_ID'] = $this->usersById;
		$this->importParameters['USERS_BY_NAME'] = $this->usersByName;
		$this->importParameters['PROJECTS_BY_NAME'] = $this->projectsByName;

		return true;
	}

	/**
	 * Encodes data to site's encoding
	 *
	 * @param $data - Data to encode
	 * @param $dataEncoding - Source encoding
	 * @return mixed - Encoded data
	 */
	private function encodeDataToSiteCharset($data, $dataEncoding)
	{
		return Encoding::convertEncoding($data, $dataEncoding, SITE_CHARSET);
	}

	/**
	 * Returns result key and data of field
	 *
	 * @param $key - Field's key
	 * @param $data - Field's data
	 * @return array - Result property
	 */
	private function getResultPropertyData($key, $data): array
	{
		$currentKeyMap = self::$keysMap[$key];

		$result = [
			'KEY' => $currentKeyMap['RESULT_KEY'],
			'DATA' => null,
		];

		switch ($currentKeyMap['CHECK_TYPE'])
		{
			case 'INT':
				$result['DATA'] = (int)$data;
				break;

			case 'BOOL':
				$result['DATA'] = ($data === '1' ? $currentKeyMap['TRUE_RESULT'] : $currentKeyMap['FALSE_RESULT']);
				break;

			case 'FUNCTION':
				$functionName = $currentKeyMap['FUNCTION_NAME'];
				$result['DATA'] = $this->$functionName($data);
				break;

			case 'EQUAL':
			default:
				$result['DATA'] = $data;
				break;
		}

		return $result;
	}

	/**
	 * Returns array without empty values
	 *
	 * @param $taskData - Input array
	 * @param $skippedColumns - Indexes of skipped columns
	 * @return array - Array without empty values
	 */
	private function removeSkippedColumns($taskData, $skippedColumns): array
	{
		if (isset($skippedColumns))
		{
			foreach (array_keys($skippedColumns) as $column)
			{
				unset($taskData[$column]);
			}
		}

		return array_values($taskData);
	}

	/**
	 * Returns task's properties from data
	 *
	 * @param $taskData - Parsed data (line of CSV file)
	 * @param $fields - Fields
	 * @param $skippedColumns - Indexes of skipped columns
	 * @return array - Task's properties
	 */
	private function getTaskPropertiesFromTaskData($taskData, $fields, $skippedColumns): array
	{
		$taskProperties = [];

		$taskData = $this->removeSkippedColumns($taskData, $skippedColumns);
		foreach ($taskData as $key => $data)
		{
			if (isset($fields[$key]) && !empty($fields[$key]))
			{
				$currentKey = mb_strtoupper($fields[$key]);
				$data = trim(htmlspecialcharsback($data));

				if ($data !== '')
				{
					$resultProperty = $this->getResultPropertyData($currentKey, $data);
					$taskProperties[$resultProperty['KEY']] = $resultProperty['DATA'];
				}
			}
		}

		return $taskProperties;
	}

	/**
	 * Checks some task's properties and transform them if needed
	 *
	 * @param $taskProperties - Task's properties
	 * @param $defaultOriginatorUserId - Id of default originator
	 * @param $defaultResponsibleUserId - Id of default responsible
	 * @return array - Checked task's properties
	 */
	private function checkTaskPropertiesBeforeCreatingTask(
		$taskProperties,
		$defaultOriginatorUserId,
		$defaultResponsibleUserId
	): array
	{
		$booleanValuesToCheck = [
			'ALLOW_CHANGE_DEADLINE',
			'MATCH_WORK_TIME',
			'TASK_CONTROL',
			'PARAM_1',
			'PARAM_2',
			'PARAM_3',
			'ALLOW_TIME_TRACKING',
		];
		foreach ($booleanValuesToCheck as $key)
		{
			if (($taskProperties[$key] ?? null) !== 'Y')
			{
				$taskProperties[$key] = 'N';
			}
		}

		if (!isset($taskProperties['CREATED_BY']) || $taskProperties['CREATED_BY'] <= 0)
		{
			$taskProperties['CREATED_BY'] = $defaultOriginatorUserId;
		}
		if ((int)$taskProperties['RESPONSIBLE_ID'] === 0)
		{
			$taskProperties['RESPONSIBLE_ID'] = $defaultResponsibleUserId;
		}

		if ($taskProperties['PRIORITY'] !== 2)
		{
			$taskProperties['PRIORITY'] = 1;
		}
		if ($taskProperties['TIME_ESTIMATE'] < 0)
		{
			$taskProperties['TIME_ESTIMATE'] = 0;
		}

		$taskProperties['SE_PARAMETER'] = [
			[
				'CODE' => \Bitrix\Tasks\Internals\Task\ParameterTable::PARAM_SUBTASKS_TIME,
				'VALUE' => $taskProperties['PARAM_1'],
			],
			[
				'CODE' => \Bitrix\Tasks\Internals\Task\ParameterTable::PARAM_SUBTASKS_AUTOCOMPLETE,
				'VALUE' => $taskProperties['PARAM_2'],
			],
			[
				'CODE' => \Bitrix\Tasks\Internals\Task\ParameterTable::PARAM_RESULT_REQUIRED,
				'VALUE' => $taskProperties['PARAM_3'],
			],
		];
		unset($taskProperties['PARAM_1'], $taskProperties['PARAM_2'], $taskProperties['PARAM_3']);

		return $taskProperties;
	}

	/**
	 * Adds error to import errors
	 *
	 * @param $message
	 */
	private function addImportError($message): void
	{
		$this->importParameters['ERROR_IMPORTS']++;
		$this->importParameters['ERROR_IMPORTS_MESSAGES'][] = $this->importParameters['CURRENT_LINE'].": ".$message;
	}

	/**
	 * Returns checklist from string
	 *
	 * @param string $data - String data
	 * @return array - Result checklist
	 */
	private function getCheckListItemsFromStringData(string $data): array
	{
		if (!$data)
		{
			return [];
		}

		if (!mb_strstr($data, '[**]'))
		{
			if (!mb_strstr($data, '[*]'))
			{
				return [];
			}
			$data = '[**]'.Loc::getMessage('TASKS_IMPORT_DEFAULT_CHECKLIST_NAME').$data;
		}

		$checkListItems = [];
		$sortIndex = -1;
		$roots = explode('[**]', $data);
		foreach ($roots as $nodeId => $checkListData)
		{
			if (empty($checkListData))
			{
				continue;
			}

			$isName = true;
			$itemSortIndex = -1;
			$checkListItemsData = explode('[*]', $checkListData);
			foreach ($checkListItemsData as $index => $itemData)
			{
				if (empty(trim($itemData)))
				{
					if ($isName)
					{
						continue 2;
					}
					continue;
				}
				if ($isName)
				{
					$checkListItems[$nodeId] = [
						'NODE_ID' => $nodeId,
						'PARENT_NODE_ID' => '',
						'SORT_INDEX' => ++$sortIndex,
						'TITLE' => trim($itemData),
					];
					$isName = false;
					continue;
				}

				$checkListItems[$nodeId.$index] = [
					'NODE_ID' => $nodeId.$index,
					'PARENT_NODE_ID' => $nodeId,
					'SORT_INDEX' => ++$itemSortIndex,
					'TITLE' => trim($itemData),
				];
			}
		}

		return $checkListItems;
	}

	/**
	 * Returns user's ids from string
	 *
	 * @param string $data - String data
	 * @return array - Result user's ids
	 * @throws Main\NotSupportedException
	 */
	private function getUserIdsFromStringData(string $data): array
	{
		$userIds = [];

		$names = explode(',', $data);
		foreach ($names as $name)
		{
			$userId = $this->getUserIdByName($name);
			if ($userId !== 0 && !in_array($userId, $userIds, true))
			{
				$userIds[] = $userId;
			}
		}

		return $userIds;
	}

	/**
	 * Transforms string with delimiter in array
	 *
	 * @param $string - String
	 * @return array - Result array
	 */
	private function getListFromStringWithDelimiter($string): array
	{
		$result = explode('[*]', trim($string));

		foreach ($result as $index => $item)
		{
			if ($item === '')
			{
				unset($result[$index]);
			}
		}

		return $result;
	}

	/**
	 * Returns name's formats
	 *
	 * @return array - Name's formats
	 */
	private function getNameFormats(): array
	{
		$nameFormats = [];

		$nameFormatsDescriptions = PersonNameFormatter::getAllDescriptions();
		foreach ($nameFormatsDescriptions as $formatId => $description)
		{
			$nameFormats[$formatId] = PersonNameFormatter::getFormatByID($formatId);
		}

		return $nameFormats;
	}

	/**
	 * Returns project's id by project's name
	 *
	 * @param $projectName - Project's name
	 * @return int - Project's id
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getProjectIdByName($projectName): int
	{
		if (isset($this->projectsByName[$projectName]))
		{
			return $this->projectsByName[$projectName]['ID'];
		}

		$projectId = 0;

		$dbGroups = WorkgroupTable::getList([
			'select' => ['ID'],
			'filter' => ['NAME' => $projectName],
		]);
		$group = (is_object($dbGroups) ? $dbGroups->fetch() : null);
		if (is_array($group))
		{
			$projectId = (int)$group['ID'];
			$this->projectsByName[$projectName]['ID'] = $projectId;
		}

		return $projectId;
	}

	/**
	 * Returns user's id if he exists in base based on user's id otherwise returns 0
	 *
	 * @param $userId - User's id
	 * @return int - User's id
	 */
	private function checkUserExistence($userId): int
	{
		$userId = ($userId < 0 ? 0 : $userId);
		if ($userId > 0)
		{
			if (isset($this->usersById[$userId]))
			{
				return $userId;
			}

			$dbUsers = CUser::GetList('ID',	'ASC', ['ID'=> $userId], ['FIELDS' => ['ID']]);
			$user = (is_object($dbUsers) ? $dbUsers->Fetch() : null);
			if (is_array($user))
			{
				$this->usersById[$userId] = $user;
			}
			else
			{
				$userId = 0;
			}
		}

		return $userId;
	}

	/**
	 * Returns user's id if he exists in base based on user's name otherwise returns 0
	 *
	 * @param $userName - User's name
	 * @param $formatId - User name's format
	 * @return int - User's id
	 * @throws Main\NotSupportedException
	 */
	private function getUserIdByNameAndFormat($userName, $formatId): int
	{
		$userId = 0;

		$nameParts = [];
		if (PersonNameFormatter::tryParseName($userName, $formatId, $nameParts))
		{
			$userFilter = [];
			$parts = ['NAME', 'SECOND_NAME', 'LAST_NAME', 'TITLE'];
			foreach ($parts as $part)
			{
				if (isset($nameParts[$part]))
				{
					$userFilter[$part] = $nameParts[$part];
				}
			}

			$dbUsers = CUser::GetList('ID',	'ASC', $userFilter, ['FIELDS' => ['ID']]);
			$user = (is_object($dbUsers) ? $dbUsers->Fetch() : null);
			if (is_array($user))
			{
				$userId = $user['ID'] = (int)$user['ID'];
				$this->usersByName[$userName] = $user;
			}
		}

		return $userId;
	}

	/**
	 * Returns user's id by user's name
	 *
	 * @param $userName - User's name
	 * @return int - User's id
	 * @throws Main\NotSupportedException
	 */
	private function getUserIdByName($userName): int
	{
		if (is_numeric($userName))
		{
			return $this->checkUserExistence((int)$userName);
		}

		if (preg_match('/^.+\[\s*(\d+)\s*]$/', $userName, $m) === 1)
		{
			return $this->checkUserExistence((int)$m[1]);
		}

		if (isset($this->usersByName[$userName]))
		{
			return (int)$this->usersByName[$userName]['ID'];
		}

		$userId = $this->getUserIdByNameAndFormat($userName, $this->importParameters['NAME_FORMAT']);

		foreach (array_keys(self::$nameFormats) as $formatId)
		{
			if ($userId > 0)
			{
				break;
			}
			if ($formatId === 1 || $formatId === $this->importParameters['NAME_FORMAT'])
			{
				continue;
			}

			$userId = $this->getUserIdByNameAndFormat($userName, $formatId);
		}

		return $userId;
	}
}
