<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !==true) die();

use Bitrix\Main\Error;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Response\AjaxJson;

use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\Import\PersonNameFormatter;
use Bitrix\Tasks\Manager\Task;

Loc::loadMessages(__FILE__);

/**
 * Class TasksImportAjaxController
 */
class TasksImportAjaxController extends \Bitrix\Main\Engine\Controller
{
	private $usersById;
	private $usersByName;
	private $projectsByName;
	private $importParameters;
	private static $nameFormats;
	private static $keysMap = array(
		'TITLE' => array(
			'RESULT_KEY' => 'TITLE',
			'CHECK_TYPE' => 'EQUAL'
		),
		'DESCRIPTION' => array(
			'RESULT_KEY' => 'DESCRIPTION',
			'CHECK_TYPE' => 'EQUAL'
		),
		'PRIORITY' => array(
			'RESULT_KEY' => 'PRIORITY',
			'CHECK_TYPE' => 'BOOL',
			'TRUE_RESULT' => 2,
			'FALSE_RESULT' => 1
		),
		'RESPONSIBLE' => array(
			'RESULT_KEY' => 'RESPONSIBLE_ID',
			'CHECK_TYPE' => 'FUNCTION',
			'FUNCTION_NAME' => 'getUserIdByName'
		),
		'ORIGINATOR' => array(
			'RESULT_KEY' => 'CREATED_BY',
			'CHECK_TYPE' => 'FUNCTION',
			'FUNCTION_NAME' => 'getUserIdByName'
		),
		'ACCOMPLICES' => array(
			'RESULT_KEY' => 'ACCOMPLICES',
			'CHECK_TYPE' => 'FUNCTION',
			'FUNCTION_NAME' => 'getUserIdsFromStringData'
		),
		'AUDITORS' => array(
			'RESULT_KEY' => 'AUDITORS',
			'CHECK_TYPE' => 'FUNCTION',
			'FUNCTION_NAME' => 'getUserIdsFromStringData'
		),
		'DEADLINE' => array(
			'RESULT_KEY' => 'DEADLINE',
			'CHECK_TYPE' => 'EQUAL'
		),
		'START_DATE_PLAN' => array(
			'RESULT_KEY' => 'START_DATE_PLAN',
			'CHECK_TYPE' => 'EQUAL'
		),
		'END_DATE_PLAN' => array(
			'RESULT_KEY' => 'END_DATE_PLAN',
			'CHECK_TYPE' => 'EQUAL'
		),
		'ALLOW_CHANGE_DEADLINE' => array(
			'RESULT_KEY' => 'ALLOW_CHANGE_DEADLINE',
			'CHECK_TYPE' => 'BOOL',
			'TRUE_RESULT' => 'Y',
			'FALSE_RESULT' => 'N'
		),
		'MATCH_WORK_TIME' => array(
			'RESULT_KEY' => 'MATCH_WORK_TIME',
			'CHECK_TYPE' => 'BOOL',
			'TRUE_RESULT' => 'Y',
			'FALSE_RESULT' => 'N'
		),
		'TASK_CONTROL' => array(
			'RESULT_KEY' => 'TASK_CONTROL',
			'CHECK_TYPE' => 'BOOL',
			'TRUE_RESULT' => 'Y',
			'FALSE_RESULT' => 'N'
		),
		'PARAM_1' => array(
			'RESULT_KEY' => 'PARAM_1',
			'CHECK_TYPE' => 'BOOL',
			'TRUE_RESULT' => 'Y',
			'FALSE_RESULT' => 'N'
		),
		'PARAM_2' => array(
			'RESULT_KEY' => 'PARAM_2',
			'CHECK_TYPE' => 'BOOL',
			'TRUE_RESULT' => 'Y',
			'FALSE_RESULT' => 'N'
		),
		'PROJECT' => array(
			'RESULT_KEY' => 'GROUP_ID',
			'CHECK_TYPE' => 'FUNCTION',
			'FUNCTION_NAME' => 'getProjectIdByName'
		),
		'ALLOW_TIME_TRACKING' => array(
			'RESULT_KEY' => 'ALLOW_TIME_TRACKING',
			'CHECK_TYPE' => 'BOOL',
			'TRUE_RESULT' => 'Y',
			'FALSE_RESULT' => 'N'
		),
		'TIME_ESTIMATE' => array(
			'RESULT_KEY' => 'TIME_ESTIMATE',
			'CHECK_TYPE' => 'INT'
		),
		'CHECKLIST' => array(
			'RESULT_KEY' => 'SE_CHECKLIST',
			'CHECK_TYPE' => 'FUNCTION',
			'FUNCTION_NAME' => 'getCheckListFromStringData'
		),
		'TAGS' => array(
			'RESULT_KEY' => 'TAGS',
			'CHECK_TYPE' => 'FUNCTION',
			'FUNCTION_NAME' => 'getListFromStringWithDelimiter'
		)
	);

	/**
	 * Returns parameters for the next import or error's collection
	 *
	 * @param $importParameters - Import parameters
	 * @return static
	 */
	public function startImportAction($importParameters)
	{
		$tmpFilePath = CTempFile::GetDirectoryName(2, 'tasks');

		if ($this->checkPrimaryErrors($importParameters['FILE_HASH'], $tmpFilePath))
			return AjaxJson::createError($this->errorCollection);

		$this->initParameters($importParameters);
		$headersInFirstRow = $this->importParameters['HEADERS_IN_FIRST_ROW'];
		$filePath = $tmpFilePath.preg_replace('/[^a-f0-9]/i', '', $this->importParameters['FILE_HASH']).'.tmp';

		$csvFile = new CCSVData();
		$csvFile->LoadFile($filePath);
		$csvFile->SetFieldsType('R');
		$csvFile->SetFirstHeader($headersInFirstRow);
		$csvFile->SetPos($this->importParameters['FILE_POS']);
		$csvFile->SetDelimiter($this->importParameters['SEPARATOR']);

		if (intval($this->importParameters['FILE_POS']) == 0)
		{
			$this->importParameters['IMPORTS_TOTAL_COUNT'] = $this->getLinesCount($csvFile);
			$csvFile->SetPos(0);
			$csvFile->SetFirstHeader($headersInFirstRow);
		}

		if ($this->parseCsvFile($csvFile))
			return $this->importParameters;
		else
			return AjaxJson::createError($this->errorCollection);
	}

	/**
	 * Checks errors and returns true if they exist otherwise returns false
	 *
	 * @param $fileHash - File's hash to check
	 * @param $tmpFilePath - File's path to check
	 * @return bool - True if errors exist otherwise false
	 */
	private function checkPrimaryErrors($fileHash, $tmpFilePath)
	{
		if (!CModule::IncludeModule('tasks'))
			$this->errorCollection[] = new Error(Loc::getMessage('TASKS_IMPORT_ERRORS_MODULE_TASKS_NOT_INCLUDED'));

		if (!CModule::IncludeModule('socialnetwork'))
			$this->errorCollection[] = new Error(Loc::getMessage('TASKS_IMPORT_ERRORS_MODULE_SOCIAL_NETWORK_NOT_INCLUDED'));

		if (!preg_match('/[0-9a-f]{32}\.tmp/i', $fileHash))
			$this->errorCollection[] = new Error(Loc::getMessage('TASKS_IMPORT_ERRORS_WRONG_FILE_HASH'));

		if (!CheckDirPath($tmpFilePath))
			$this->errorCollection[] = new Error(Loc::getMessage('TASKS_IMPORT_ERRORS_WRONG_FILE_PATH'));

		return count($this->errorCollection) > 0;
	}

	/**
	 * Creates classes properties based on import parameters
	 *
	 * @param $importParameters - Input import parameters
	 */
	private function initParameters($importParameters)
	{
		$importParameters['FILE_ENCODING'] = ($importParameters['FILE_ENCODING'] == 'auto') ? $importParameters['FOUND_FILE_ENCODING'] : $importParameters['FILE_ENCODING'];
		$importParameters['MAX_EXECUTION_TIME'] = intval($importParameters['MAX_EXECUTION_TIME']);
		$importParameters['HEADERS_IN_FIRST_ROW'] = ($importParameters['HEADERS_IN_FIRST_ROW'] == "true") ? true : false;
		$importParameters['IMPORTS_TOTAL_COUNT'] = isset($importParameters['IMPORTS_TOTAL_COUNT']) ? $importParameters['IMPORTS_TOTAL_COUNT'] : 0;
		$importParameters['CURRENT_LINE'] = isset($importParameters['CURRENT_LINE']) ? $importParameters['CURRENT_LINE'] : 0;
		$importParameters['SUCCESSFUL_IMPORTS'] = 0;
		$importParameters['ERROR_IMPORTS'] = 0;
		$importParameters['ERROR_IMPORTS_MESSAGES'] = array();

		$this->usersById = isset($importParameters['USERS_BY_ID']) ? $importParameters['USERS_BY_ID'] : array();
		$this->usersByName = isset($importParameters['USERS_BY_NAME']) ? $importParameters['USERS_BY_NAME'] : array();
		$this->projectsByName = isset($importParameters['PROJECTS_BY_NAME']) ? $importParameters['PROJECTS_BY_NAME'] : array();
		$this->importParameters = $importParameters;
		self::$nameFormats = $this->getNameFormats();
	}

	/**
	 * Returns the amount of lines in csv file to import
	 *
	 * @param CCSVData $csvFile - CSV file
	 * @return int - The amount of lines
	 */
	private function getLinesCount(CCSVData $csvFile)
	{
		$linesCount = 0;

		while ($taskData = $csvFile->Fetch())
			$linesCount++;

		return $linesCount;
	}

	/**
	 * Parses CSV file and returns true if parsing is OK otherwise returns false
	 *
	 * @param CCSVData $csvFile - CSV file
	 * @return bool
	 */
	private function parseCsvFile(CCSVData $csvFile)
	{
		$allLinesLoaded = true;
		while ($taskData = $csvFile->Fetch())
		{
			$this->importParameters['CURRENT_LINE']++;

			$taskData = $this->encodeDataToSiteCharset($taskData, $this->importParameters['FILE_ENCODING']);
			$taskProperties = $this->getTaskPropertiesFromTaskData($taskData, $this->importParameters['SELECTED_FIELDS'], $this->importParameters['SKIPPED_COLUMNS']);
			$taskProperties = $this->checkTaskPropertiesBeforeCreatingTask($taskProperties, $this->importParameters['DEFAULT_ORIGINATOR'], $this->importParameters['DEFAULT_RESPONSIBLE']);

			try
			{
				$userId = $this->getCurrentUser()->getId();
				$newTask = Task::add($userId, $taskProperties, ['PUBLIC_MODE' => true, 'RETURN_ENTITY' => false]);

				/** @var \Bitrix\Tasks\Util\Error\Collection $errors */
				$errors = $newTask['ERRORS'];
				if ($errors->checkNoFatals())
				{
					$this->importParameters['SUCCESSFUL_IMPORTS']++;
				}
				else
				{
					self::addImportError(implode('; ', $errors->getMessages()));
				}
			}
			/** @noinspection PhpDeprecationInspection */
			catch (TasksException $e)
			{
				$message = unserialize($e->getMessage());
				self::addImportError($message[0]['text']);
			}
			catch (Exception $e)
			{
				self::addImportError($e->getMessage());
			}

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
	 * @return array|bool|SplFixedArray|string - Encoded data
	 */
	private function encodeDataToSiteCharset($data, $dataEncoding)
	{
		$encodedData = Encoding::convertEncoding($data, $dataEncoding, SITE_CHARSET);
		return $encodedData;
	}

	/**
	 * Returns result key and data of field
	 *
	 * @param $key - Field's key
	 * @param $data - Field's data
	 * @return array - Result property
	 */
	private function getResultPropertyData($key, $data)
	{
		$currentKeyMap = self::$keysMap[$key];

		$result = array(
			'KEY' => $currentKeyMap['RESULT_KEY'],
			'DATA' => null
		);

		switch ($currentKeyMap['CHECK_TYPE'])
		{
			case 'EQUAL':
				$result['DATA'] = $data;
				break;

			case 'INT':
				$result['DATA'] = intval($data);
				break;

			case 'BOOL':
				$result['DATA'] = ($data == '1'? $currentKeyMap['TRUE_RESULT'] : $currentKeyMap['FALSE_RESULT']);
				break;

			case 'FUNCTION':
				$functionName = $currentKeyMap['FUNCTION_NAME'];
				$result['DATA'] = $this->$functionName($data);
				break;

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
	private function removeSkippedColumns($taskData, $skippedColumns)
	{
		if (isset($skippedColumns))
			foreach ($skippedColumns as $key => $value)
				unset($taskData[$key]);

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
	private function getTaskPropertiesFromTaskData($taskData, $fields, $skippedColumns)
	{
		$taskProperties = array();

		$taskData = $this->removeSkippedColumns($taskData, $skippedColumns);
		foreach ($taskData as $key => $data)
		{
			if (isset($fields[$key]) && !empty($fields[$key]))
			{
				$currentKey = strtoupper($fields[$key]);
				$data = trim(htmlspecialcharsback($data));

				if ($data == '')
					continue;

				$resultProperty = $this->getResultPropertyData($currentKey, $data);
				$taskProperties[$resultProperty['KEY']] = $resultProperty['DATA'];
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
	 * @return mixed - Checked task's properties
	 */
	private function checkTaskPropertiesBeforeCreatingTask($taskProperties, $defaultOriginatorUserId, $defaultResponsibleUserId)
	{
		$booleanValuesToCheck = array(
			'ALLOW_CHANGE_DEADLINE',
			'MATCH_WORK_TIME',
			'TASK_CONTROL',
			'PARAM_1',
			'PARAM_2',
			'ALLOW_TIME_TRACKING'
		);

		foreach ($booleanValuesToCheck as $key)
		{
			if ($taskProperties[$key] !== 'Y')
			{
				$taskProperties[$key] = 'N';
			}
		}

		if (!isset($taskProperties['CREATED_BY']) || $taskProperties['CREATED_BY'] <= 0)
		{
			$taskProperties['CREATED_BY'] = $defaultOriginatorUserId;
		}

		if ($taskProperties['RESPONSIBLE_ID'] == 0)
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

		$taskProperties['SE_PARAMETER'] = array(
			0 => array(
				'CODE' => 1,
				'VALUE' => $taskProperties['PARAM_1']
			),
			1 => array(
				'CODE' => 2,
				'VALUE' => $taskProperties['PARAM_2']
			)
		);

		unset($taskProperties['PARAM_1']);
		unset($taskProperties['PARAM_2']);

		return $taskProperties;
	}

	/**
	 * Adds error to import errors
	 *
	 * @param $message
	 */
	private function addImportError($message)
	{
		$this->importParameters['ERROR_IMPORTS']++;
		$this->importParameters['ERROR_IMPORTS_MESSAGES'][] = $this->importParameters['CURRENT_LINE'] . ": " . $message;
	}

	/**
	 * Returns checklist from string
	 *
	 * @param $data - String data
	 * @return array - Result checklist
	 */
	private function getCheckListFromStringData($data)
	{
		$checklist = array();
		$checklistTitles = $this->getListFromStringWithDelimiter($data);

		foreach ($checklistTitles as $title)
		{
			$checklist[] = array(
				'TITLE' => $title
			);
		}

		return $checklist;
	}

	/**
	 * Returns user's ids from string
	 *
	 * @param $data - String data
	 * @throws \Bitrix\Main\NotSupportedException
	 * @return array - Result user's ids
	 */
	private function getUserIdsFromStringData($data)
	{
		$userIds = array();

		$names = explode(',', $data);
		foreach ($names as $name)
		{
			$userId = $this->getUserIdByName($name);
			if ($userId !== 0 && !in_array($userId, $userIds))
				$userIds[] = $userId;
		}

		return $userIds;
	}

	/**
	 * Transforms string with delimiter in array
	 *
	 * @param $string - String
	 * @return array - Result array
	 */
	private function getListFromStringWithDelimiter($string)
	{
		$string = ltrim($string);
		$result = explode('[*]', $string);

		foreach ($result as $index => $item)
		{
			if ($item == '')
				unset($result[$index]);
		}

		return $result;
	}

	/**
	 * Returns name's formats
	 *
	 * @return array - Name's formats
	 */
	private function getNameFormats()
	{
		$nameFormatsDescriptions = PersonNameFormatter::getAllDescriptions();

		$nameFormats = array();
		foreach ($nameFormatsDescriptions as $formatId => $description)
			$nameFormats[$formatId] = PersonNameFormatter::getFormatByID($formatId);

		return $nameFormats;
	}

	/**
	 * Returns project's id by project's name
	 *
	 * @param $projectName - Project's name
	 * @throws Exception
	 * @return int - Project's id
	 */
	private function getProjectIdByName($projectName)
	{
		if (isset($this->projectsByName[$projectName]))
			return $this->projectsByName[$projectName]['ID'];

		$projectId = 0;

		$dbGroups = WorkgroupTable::getList(array(
			'select' => array('ID'),
			'filter' => array('NAME' => $projectName)
		));
		$group = is_object($dbGroups) ? $dbGroups->fetch() : null;
		if (is_array($group))
		{
			$projectId = intval($group['ID']);
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
	private function checkUserExistence($userId)
	{
		$userId = ($userId < 0) ? 0 : $userId;
		if ($userId > 0)
		{
			if (isset($this->usersById[$userId]))
				return $userId;

			$dbUsers = CUser::GetList($by = 'ID', $order = 'ASC', array('ID'=> $userId), array('FIELDS' => array('ID')));
			$user = is_object($dbUsers) ? $dbUsers->Fetch() : null;
			if (is_array($user))
				$this->usersById[$userId] = $user;
			else
				$userId = 0;
		}

		return $userId;
	}

	/**
	 * Returns user's id if he exists in base based on user's name otherwise returns 0
	 *
	 * @param $userName - User's name
	 * @param $formatId - User name's format
	 * @throws Bitrix\Main\NotSupportedException
	 * @return int - User's id
	 */
	private function getUserIdByNameAndFormat($userName, $formatId)
	{
		$userId = 0;

		$nameParts = array();
		if (PersonNameFormatter::tryParseName($userName, $formatId, $nameParts))
		{
			$userFilter = array();
			if (isset($nameParts['NAME']))
				$userFilter['NAME'] = $nameParts['NAME'];
			if (isset($nameParts['SECOND_NAME']))
				$userFilter['SECOND_NAME'] = $nameParts['SECOND_NAME'];
			if (isset($nameParts['LAST_NAME']))
				$userFilter['LAST_NAME'] = $nameParts['LAST_NAME'];
			if (isset($nameParts['TITLE']))
				$userFilter['TITLE'] = $nameParts['TITLE'];

			$dbUsers = CUser::GetList($by = 'ID', $order = 'ASC', $userFilter, array('FIELDS' => array('ID')));
			$user = is_object($dbUsers) ? $dbUsers->Fetch() : null;
			if (is_array($user))
			{
				$userId = $user['ID'] = intval($user['ID']);
				$this->usersByName[$userName] = $user;
			}
		}

		return $userId;
	}

	/**
	 * Returns user's id by user's name
	 *
	 * @param $userName - User's name
	 * @throws Bitrix\Main\NotSupportedException
	 * @return int - User's id
	 */
	private function getUserIdByName($userName)
	{
		$userNameFormat = $this->importParameters['NAME_FORMAT'];

		if (is_numeric($userName))
		{
			$userId = $this->checkUserExistence(is_int($userName) ? $userName : intval($userName));
		}
		else
		{
			if (preg_match('/^.+\[\s*(\d+)\s*]$/', $userName, $m) === 1)
			{
				$userId = $this->checkUserExistence(intval($m[1]));
			}
			else
			{
				if (isset($this->usersByName[$userName]))
				{
					$userId = intval($this->usersByName[$userName]['ID']);
				}
				else
				{
					$userId = $this->getUserIdByNameAndFormat($userName, $userNameFormat);

					foreach (self::$nameFormats as $formatId => $formatString)
					{
						if ($userId > 0)
							break;

						if (($formatId == 1) || ($formatId == $userNameFormat))
							continue;

						$userId = $this->getUserIdByNameAndFormat($userName, $formatId);
					}
				}
			}
		}

		return $userId;
	}
}