<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\HttpRequest;
use Bitrix\Main\Application;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Import\PersonNameFormatter;
use Bitrix\Tasks\UI;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

/**
 * Class TasksImportComponent
 */
class TasksImportComponent extends TasksBaseComponent
{
	static $map = array(
		'FILE_NAME' => 'file_name',
		'FILE_HASH' => 'hidden_file_hash',
		'FILE_ENCODING' => 'file_encoding',
		'FOUND_FILE_ENCODING' => 'hidden_found_file_encoding',
		'DEFAULT_ORIGINATOR' => 'default_originator',
		'DEFAULT_RESPONSIBLE' => 'default_responsible',
		'FROM_TMP_DIR' => 'hidden_from_tmp_dir',
		'NAME_FORMAT' => 'name_format',
		'SEPARATOR_TEXT' => 'separator',
		'HEADERS_IN_FIRST_ROW' => 'headers_in_first_row',
		'SKIP_EMPTY_COLUMNS' => 'skip_empty_columns'
	);

	protected function getData()
	{
		$encodings = array(
			'auto' => Loc::getMessage('TASKS_IMPORT_FIELDS_FILE_ENCODING_AUTO_DETECT'),
			'ascii' => 'ASCII',
			'windows-1251' => 'Windows-1251',
			'Windows-1252' => 'Windows-1252',
			'iso-8859-1' => 'ISO-8859-1',
			'iso-8859-2' => 'ISO-8859-2',
			'iso-8859-3' => 'ISO-8859-3',
			'iso-8859-4' => 'ISO-8859-4',
			'iso-8859-5' => 'ISO-8859-5',
			'iso-8859-6' => 'ISO-8859-6',
			'iso-8859-7' => 'ISO-8859-7',
			'iso-8859-8' => 'ISO-8859-8',
			'iso-8859-9' => 'ISO-8859-9',
			'iso-8859-10' => 'ISO-8859-10',
			'iso-8859-13' => 'ISO-8859-13',
			'iso-8859-14' => 'ISO-8859-14',
			'iso-8859-15' => 'ISO-8859-15',
			'koi8-r' => 'KOI8-R',
			'UTF-8' => 'UTF-8',
			'UTF-16' => 'UTF-16'
		);

		foreach (array_keys($encodings) as $key)
		{
			if ($key !== 'auto')
				$this->arResult['CHARSETS'][] = $key;
		}

		$currentUserId = $this->arParams['VARIABLES']['user_id'];
		$currentUser = $this->getUsersDataById($currentUserId);
		$this->arResult['IFRAME'] = (isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] == 'Y') ? true : false;
		$this->arResult['STEP'] = 1;
		$this->arResult['FORM_ID'] = 'TASKS_IMPORT_FORM';
		$this->arResult['ERRORS'] = array();
		$this->arResult['CURRENT_USER_ID'] = $currentUserId;
		$this->arResult['DEFAULT_ORIGINATOR'] = $currentUser;
		$this->arResult['DEFAULT_RESPONSIBLE'] = $currentUser;
		$this->arResult['NAME_FORMATS'] = PersonNameFormatter::getAllDescriptions();
		$this->arResult['ENCODINGS'] = $encodings;
		$this->arResult['HEADERS'] = array(
			array('id' => 'TITLE', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_TITLE'), 'mandatory' => 'Y'),
			array('id' => 'DESCRIPTION', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_DESCRIPTION')),
			array('id' => 'PRIORITY', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_PRIORITY')),
			array('id' => 'RESPONSIBLE', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_RESPONSIBLE')),
			array('id' => 'ORIGINATOR', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_ORIGINATOR')),
			array('id' => 'ACCOMPLICES', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_ACCOMPLICES')),
			array('id' => 'AUDITORS', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_AUDITORS')),
			array('id' => 'DEADLINE', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_DEADLINE')),
			array('id' => 'START_DATE_PLAN', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_START_DATE_PLAN')),
			array('id' => 'END_DATE_PLAN', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_END_DATE_PLAN')),
			array('id' => 'ALLOW_CHANGE_DEADLINE', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_ALLOW_CHANGE_DEADLINE')),
			array('id' => 'MATCH_WORK_TIME', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_MATCH_WORK_TIME')),
			array('id' => 'TASK_CONTROL', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_TASK_CONTROL')),
			array('id' => 'PARAM_1', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_PARAM_1')),
			array('id' => 'PARAM_2', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_PARAM_2')),
			array('id' => 'PROJECT', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_PROJECT')),
			array('id' => 'ALLOW_TIME_TRACKING', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_ALLOW_TIME_TRACKING')),
			array('id' => 'TIME_ESTIMATE', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_TIME_ESTIMATE')),
			array('id' => 'CHECKLIST', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_CHECKLIST')),
			array('id' => 'TAGS', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_TAGS'))
		);
		$this->arResult['SEPARATORS'] = array(
			'semicolon' => Loc::getMessage('TASKS_IMPORT_FIELDS_FILE_SEPARATOR_SEMICOLON'),
			'comma' => Loc::getMessage('TASKS_IMPORT_FIELDS_FILE_SEPARATOR_COMMA'),
			'tab' => Loc::getMessage('TASKS_IMPORT_FIELDS_FILE_SEPARATOR_TAB'),
			'space' => Loc::getMessage('TASKS_IMPORT_FIELDS_FILE_SEPARATOR_SPACE')
		);

		$headers = array();
		$requiredFields = array();
		$fields = array('' => '');
		$upperFields = array();
		foreach($this->arResult['HEADERS'] as $header)
		{
			$headers[] = $header['name'];
			$fields[$header['id']] = $header['name'];
			$upperFields[$header['id']] = ToUpper($header['name']);
			if ($header['mandatory'] == 'Y')
				$requiredFields[$header['id']] = $header['name'];
		}
		$this->arResult['IMPORT_FILE_PARAMETERS']['HEADERS'] = $headers;
		$this->arResult['IMPORT_FILE_PARAMETERS']['REQUIRED_FIELDS'] = $requiredFields;
		$this->arResult['IMPORT_FILE_PARAMETERS']['FIELDS'] = $fields;
		$this->arResult['IMPORT_FILE_PARAMETERS']['UPPER_FIELDS'] = $upperFields;

		$this->arResult['IMPORT_FILE_PARAMETERS']['NAME_FORMAT'] = PersonNameFormatter::getFormatID();
		$this->arResult['IMPORT_FILE_PARAMETERS']['FILE_NAME'] = Loc::getMessage('TASKS_IMPORT_FIELDS_FILE_NAME');
	}

	protected function doPostAction()
	{
		$application = Application::getInstance();
		$context = $application->getContext();
		$request = $context->getRequest();

		if ($request->get('download') == 'csv')
			$this->downloadSampleCsvFile();

		if ($request->isPost() && check_bitrix_sessid())
		{
			$step = $request->get('step');
			$this->arResult['STEP'] = intval($step);
			if ($request->get('next'))
			{
				if ($step == 1)
				{
					$this->handleStep1($request);
				}
				elseif ($step == 2)
				{
					$this->handleStep2Next($request);
				}
				elseif ($step == 3)
				{
					if (!$this->arResult['IFRAME'])
						$this->moveToCurrentUserTasks();
				}
			}
			elseif ($request->get('back'))
			{
				if ($step == 2)
				{
					$this->handleStep2Back($request);
				}
				elseif ($step == 3)
				{
					$this->arResult['STEP'] = 1;
				}
			}
			elseif ($request->get('cancel'))
			{
				if (!$this->arResult['IFRAME'])
					$this->moveToCurrentUserTasks();
			}
		}
	}

	/**
	 * Does some actions on step 1
	 *
	 * @param HttpRequest $request
	 */
	private function handleStep1(HttpRequest $request)
	{
		$this->loadRequestValues($request);
		if (!$this->checkFileErrors())
			$this->fillStep2DataFromFile();
		else
			$this->unselectFile();
	}

	/**
	 * Fills the array with import parameters
	 *
	 * @param HttpRequest $request
	 */
	private function handleStep2Next(HttpRequest $request)
	{
		$headers = $this->parseStringToArray($request->get('hidden_headers'));
		$requiredFields = $this->parseStringToArray($request->get('hidden_required_fields'));
		$fields = $this->parseStringToArray($request->get('hidden_fields'));
		$rows = $this->parseStringToArray($request->get('hidden_rows'), true);
		$skippedColumns = $this->parseStringToArray($request->get('hidden_skipped_columns'));

		$upperFields = array();
		foreach ($fields as $key => $value)
			$upperFields[$key] = ToUpper($value);

		$selectedFields = array();
		$requiredFieldsWithFlag = $requiredFields;
		$headersCount = count($headers);
		foreach ($requiredFieldsWithFlag as $key => $value)
			$requiredFieldsWithFlag[$key] = 0;
		for ($i = 0; $i < $headersCount; $i++)
		{
			$fieldValue = $request->get('field_'.$i);
			foreach ($requiredFieldsWithFlag as $key => $value)
			{
				if ($fieldValue == $key)
					$requiredFieldsWithFlag[$key] = 1;
			}
			$selectedFields[$i] = $fieldValue;
		}

		$this->arResult['IMPORT_FILE_PARAMETERS']['HEADERS'] = $headers;
		$this->arResult['IMPORT_FILE_PARAMETERS']['REQUIRED_FIELDS'] = $requiredFields;
		$this->arResult['IMPORT_FILE_PARAMETERS']['FIELDS'] = $fields;
		$this->arResult['IMPORT_FILE_PARAMETERS']['UPPER_FIELDS'] = $upperFields;
		$this->arResult['IMPORT_FILE_PARAMETERS']['SELECTED_FIELDS'] = $selectedFields;
		$this->arResult['IMPORT_FILE_PARAMETERS']['ROWS'] = $rows;
		$this->arResult['IMPORT_FILE_PARAMETERS']['SKIPPED_COLUMNS'] = $skippedColumns;
		$this->arResult['IMPORT_FILE_PARAMETERS']['FILE_POS'] = 0;
		$this->arResult['IMPORT_FILE_PARAMETERS']['MAX_EXECUTION_TIME'] = Option::get('tasks', 'import_step', 15);
		$this->loadRequestValues($request);

		foreach ($requiredFieldsWithFlag as $key => $value)
		{
			if ($value == 0)
			{
				$this->arResult['ERRORS']['REQUIRED_FIELDS'] = Loc::getMessage('TASKS_IMPORT_ERRORS_REQUIRED_FIELDS');
				return;
			}
		}

		$this->arResult['STEP'] = 3;
	}

	/**
	 * Returns form on step 1 with remembered field's values
	 *
	 * @param HttpRequest $request
	 */
	private function handleStep2Back(HttpRequest $request)
	{
		$this->loadRequestValues($request);
		$this->arResult['IMPORT_FILE_PARAMETERS']['FROM_TMP_DIR'] = true;

		if (($filePath = $this->getFilePath()) == null)
			return;

		$csvFile = $this->getCsvFileByPath($filePath);

		if ($this->detectUtf8Encoding($csvFile))
		{
			$this->arResult['IMPORT_FILE_PARAMETERS']['SHOW_ENCODING_CHOICE'] = 'N';
			$this->arResult['IMPORT_FILE_PARAMETERS']['FOUND_FILE_ENCODING'] = 'UTF-8';
		}
		else
		{
			$this->arResult['IMPORT_FILE_PARAMETERS']['SHOW_ENCODING_CHOICE'] = 'Y';
			$this->arResult['ENCODED_RESULTS'] = $this->getEncodedResults($csvFile);
		}

		$this->arResult['STEP'] = 1;
	}

	/**
	 * Returns true if file is in UTF-8 encoding otherwise returns false
	 *
	 * @param CCSVData $csvFile - File
	 * @return bool
	 */
	private function detectUtf8Encoding(CCSVData $csvFile)
	{
		$stringLineOfFile = '';
		$arrayLineOfFile = $csvFile->Fetch();
		foreach ($arrayLineOfFile as $value)
			$stringLineOfFile .= $value.$this->arResult['IMPORT_FILE_PARAMETERS']['SEPARATOR'];

		return Encoding::detectUtf8($stringLineOfFile);
	}

	/**
	 * Configures step 2 data from loaded file's parameters
	 */
	private function fillStep2DataFromFile()
	{
		$this->fillFieldsForMatching();

		if (($filePath = $this->getFilePath()) == null)
			return;

		$csvFile = $this->getCsvFileByPath($filePath);

		$this->fillRowsForExampleTableFromCsv($csvFile);
		if (count($this->arResult['IMPORT_FILE_PARAMETERS']['ROWS']) == 0)
		{
			$this->arResult['ERRORS']['FILE_LABEL'] = Loc::getMessage('TASKS_IMPORT_ERRORS_FILE_EMPTY');
			return;
		}
		$this->arResult['STEP'] = 2;
	}

	/**
	 * Returns array of encoded file lines
	 *
	 * @param CCSVData $csvFile - File
	 * @return array
	 */
	private function getEncodedResults(CCSVData $csvFile)
	{
		$csvFile->SetPos();

		$resultLine = '';
		while ($fetchedArray = $csvFile->Fetch())
		{
			foreach ($fetchedArray as $value)
			{
				$resultLine .= $value.$this->arResult['IMPORT_FILE_PARAMETERS']['SEPARATOR'];
				if (strlen($resultLine) > 50)
				{
					break;
				}
			}

			if (strlen($resultLine) > 50)
			{
				break;
			}
		}
		$resultLine = substr($resultLine, 0, 50);

		$encodedResult = array();
		foreach ($this->arResult['CHARSETS'] as $charset)
		{
			$encodedResult[$charset] = (
				$charset == SITE_CHARSET?
				mb_convert_encoding($resultLine, $charset, $charset) :
				Encoding::convertEncoding($resultLine, $charset, SITE_CHARSET)
			);

			$questionsCount = substr_count($encodedResult[$charset], '?');
			$maxQuestionsCount = strlen($encodedResult[$charset]) * 1 / 3;

			if ($questionsCount > $maxQuestionsCount)
			{
				unset($encodedResult[$charset]);
			}
		}

		return $encodedResult;
	}

	/**
	 * Returns true if there are some errors with loaded file otherwise returns false
	 *
	 * @return bool
	 */
	private function checkFileErrors()
	{
		if (!is_uploaded_file($_FILES['file']['tmp_name']))
		{
			if ($this->arResult['IMPORT_FILE_PARAMETERS']['FROM_TMP_DIR'])
				return false;
			else
				$this->arResult['ERRORS']['FILE_LABEL'] = Loc::getMessage('TASKS_IMPORT_ERRORS_FILE_NOT_FOUND');
		}
		else
		{
			if ($_FILES['file']['error'] > 0)
			{
				if ($_FILES['file']['error'] == 4)
					$this->arResult['ERRORS']['FILE_LABEL'] = Loc::getMessage('TASKS_IMPORT_ERRORS_FILE_NOT_FOUND');
				else
					$this->arResult['ERRORS']['FILE_LABEL'] = Loc::getMessage('TASKS_IMPORT_ERRORS_FILE_ERRORS');
			}
			if (($error = CFile::CheckFile($_FILES['file'], 0, 0, 'csv')) !== '')
			{
				$this->arResult['ERRORS']['FILE_LABEL'] = $error;
			}
		}

		return count($this->arResult['ERRORS']) > 0;
	}

	/**
	 * Encodes data to site encoding
	 *
	 * @param $data - Data to encode
	 * @return array|bool|SplFixedArray|string - Encoded data
	 */
	private function encodeDataToSiteCharset($data)
	{
		$fileEncoding = $this->arResult['IMPORT_FILE_PARAMETERS']['FILE_ENCODING'];

		if ($fileEncoding == 'auto' && isset($this->arResult['IMPORT_FILE_PARAMETERS']['FOUND_FILE_ENCODING']))
		{
			$fileEncoding = $this->arResult['IMPORT_FILE_PARAMETERS']['FOUND_FILE_ENCODING'];
		}

		if ($fileEncoding !== 'auto' && $fileEncoding !== strtolower(SITE_CHARSET))
		{
			$convertCharsetErrorMsg = '';

			//HACK: Remove UTF-8 BOM
			if ($fileEncoding === 'UTF-8' && substr($data, 0, 3) === "\xEF\xBB\xBF")
			{
				$data = substr($data, 3);
			}

			$data = Encoding::convertEncoding($data, $fileEncoding, SITE_CHARSET, $convertCharsetErrorMsg);
			if ($fileEncoding == SITE_CHARSET)
			{
				if (is_array($data))
				{
					foreach ($data as $key => $value)
					{
						$data[$key] = mb_convert_encoding($value, $fileEncoding, $fileEncoding);
					}
				}
				elseif (is_string($data))
				{
					$data = mb_convert_encoding($data, $fileEncoding, $fileEncoding);
				}
			}
		}

		return $data;
	}

	/**
	 * Fills the arrays for step 2 table with example import data
	 *
	 * @param CCSVData $csvFile - CSV file
	 */
	private function fillRowsForExampleTableFromCsv(CCSVData $csvFile)
	{
		$row = 1;
		$headers = array();
		$rows = array();
		$skippedColumns = array();
		$skipEmptyColumns = $this->arResult['IMPORT_FILE_PARAMETERS']['SKIP_EMPTY_COLUMNS'];
		$headersInFirstRow = $this->arResult['IMPORT_FILE_PARAMETERS']['HEADERS_IN_FIRST_ROW'];
		$maxExampleRows = ($headersInFirstRow) ? 4 : 3;

		while ($rowData = $csvFile->Fetch())
		{
			$rowData = $this->encodeDataToSiteCharset($rowData);

			if ($row == 1)
			{
				$columnIndex = 0;
				foreach ($rowData as $key => $value)
				{
					if ($skipEmptyColumns && ($value == ''))
					{
						$skippedColumns[$columnIndex] = $columnIndex;
						$columnIndex++;
						continue;
					}
					if ($headersInFirstRow)
						$headers[$key] = empty($value) ? Loc::getMessage('TASKS_IMPORT_CUSTOM_HEADER').' '.($key + 1) : trim($value);
					else
						$headers[$key] = Loc::getMessage('TASKS_IMPORT_CUSTOM_HEADER').' '.($key + 1);

					$columnIndex++;
				}
				if (!$headersInFirstRow)
					foreach ($headers as $key => $value)
						$rows[$row][$key] = $rowData[$key];
			}
			else
				foreach ($headers as $key => $value)
					$rows[$row][$key] = $rowData[$key];

			if ($row >= $maxExampleRows)
				break;

			$row++;
		}
		$this->arResult['IMPORT_FILE_PARAMETERS']['HEADERS'] = $headers;
		$this->arResult['IMPORT_FILE_PARAMETERS']['ROWS'] = $rows;
		$this->arResult['IMPORT_FILE_PARAMETERS']['SKIPPED_COLUMNS'] = $skippedColumns;
	}

	/**
	 * Fills the arrays for step 2 tuning field's match
	 */
	private function fillFieldsForMatching()
	{
		$requiredFields = array();
		$fields = array('' => '');
		$upperFields = array();
		foreach ($this->arResult['HEADERS'] as $header)
		{
			$fields[$header['id']] = $header['name'];
			$upperFields[$header['id']] = ToUpper($header['name']);
			if ($header['mandatory'] == 'Y')
				$requiredFields[$header['id']] = $header['name'];
		}
		$this->arResult['IMPORT_FILE_PARAMETERS']['REQUIRED_FIELDS'] = $requiredFields;
		$this->arResult['IMPORT_FILE_PARAMETERS']['FIELDS'] = $fields;
		$this->arResult['IMPORT_FILE_PARAMETERS']['UPPER_FIELDS'] = $upperFields;
	}

	/**
	 * Fill array with form field's values
	 *
	 * @param HttpRequest $request
	 */
	private function loadRequestValues(HttpRequest $request)
	{
		foreach (self::$map as $key => $value)
			if ($request->get($value))
				$this->arResult['IMPORT_FILE_PARAMETERS'][$key] = $request->get($value);

		if ($this->arResult['STEP'] == 2)
		{
			if ($request->get('hidden_default_originator'))
				$this->arResult['IMPORT_FILE_PARAMETERS']['DEFAULT_ORIGINATOR'] = $request->get('hidden_default_originator');
			if ($request->get('hidden_default_responsible'))
				$this->arResult['IMPORT_FILE_PARAMETERS']['DEFAULT_RESPONSIBLE'] = $request->get('hidden_default_responsible');
		}

		$headersInFirstRow = $this->arResult['IMPORT_FILE_PARAMETERS']['HEADERS_IN_FIRST_ROW'];
		$skipEmptyColumns = $this->arResult['IMPORT_FILE_PARAMETERS']['SKIP_EMPTY_COLUMNS'];
		$fromTmpDir = $this->arResult['IMPORT_FILE_PARAMETERS']['FROM_TMP_DIR'];
		$this->arResult['IMPORT_FILE_PARAMETERS']['SEPARATOR'] = $this->getSeparatorByText($this->arResult['IMPORT_FILE_PARAMETERS']['SEPARATOR_TEXT']);
		$this->arResult['IMPORT_FILE_PARAMETERS']['HEADERS_IN_FIRST_ROW'] = isset($headersInFirstRow) ? true : false;
		$this->arResult['IMPORT_FILE_PARAMETERS']['SKIP_EMPTY_COLUMNS'] = isset($skipEmptyColumns) ? true : false;
		$this->arResult['IMPORT_FILE_PARAMETERS']['FROM_TMP_DIR'] = ($fromTmpDir == 'Y') ? true : false;
		$this->arResult['DEFAULT_ORIGINATOR'] = $this->getUsersDataById($this->arResult['IMPORT_FILE_PARAMETERS']['DEFAULT_ORIGINATOR']);
		$this->arResult['DEFAULT_RESPONSIBLE'] = $this->getUsersDataById($this->arResult['IMPORT_FILE_PARAMETERS']['DEFAULT_RESPONSIBLE']);
	}

	/**
	 * Redirects to current user's tasks page
	 */
	private function moveToCurrentUserTasks()
	{
		LocalRedirect(
			CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_USER_TASKS'],
				array('user_id' => $this->arResult['CURRENT_USER_ID'])
			)
		);
	}

	/**
	 * Unselect the file
	 */
	private function unselectFile()
	{
		$this->arResult['IMPORT_FILE_PARAMETERS']['FILE_NAME'] = Loc::getMessage('TASKS_IMPORT_FIELDS_FILE_NAME');
	}

	/**
	 * Returns CSV file by path
	 *
	 * @param $filePath - Path to file
	 * @return CCSVData - CSV file
	 */
	private function getCsvFileByPath($filePath)
	{
		$csvFile = new CCSVData();
		$csvFile->LoadFile($filePath);
		$csvFile->SetFieldsType('R');
		$csvFile->SetFirstHeader(false);
		$csvFile->SetDelimiter($this->arResult['IMPORT_FILE_PARAMETERS']['SEPARATOR']);

		return $csvFile;
	}

	/**
	 * Returns path to temporary dir or null if path did not pass check
	 */
	private function getTmpDirPath()
	{
		$tmpDirPath = CTempFile::GetDirectoryName(2, 'tasks');
		if (!CheckDirPath($tmpDirPath))
		{
			$this->arResult['ERRORS']['FILE_LABEL'] = Loc::getMessage('TASKS_IMPORT_ERRORS_FILE_PATH');
			return null;
		}

		return $tmpDirPath;
	}

	/**
	 * Returns true if file's hash name is valid otherwise returns false
	 *
	 * @param $fileHashName - Hash name of file
	 * @return bool
	 */
	private function checkFileHashName($fileHashName)
	{
		if (!preg_match('/[0-9a-f]{32}\.tmp/i', $fileHashName))
		{
			$this->arResult['ERRORS']['FILE_LABEL'] = Loc::getMessage('TASKS_IMPORT_ERRORS_FILE_PATH');
			return false;
		}

		return true;
	}

	/**
	 * Returns true if file exists by path
	 *
	 * @param $filePath - Path to file
	 * @return bool
	 */
	private function checkFileExistenceByPath($filePath)
	{
		if (!file_exists($filePath))
		{
			$this->arResult['ERRORS']['FILE_LABEL'] = Loc::getMessage('TASKS_IMPORT_ERRORS_FILE_PATH');
			return false;
		}

		return true;
	}

	/**
	 * Returns file's path or null if path to tmp dir or file hash name are invalid
	 *
	 * @return null|string
	 */
	private function getFilePath()
	{
		if (($tmpDirPath = $this->getTmpDirPath()) == null)
			return null;

		if ($this->arResult['IMPORT_FILE_PARAMETERS']['FROM_TMP_DIR'])
		{
			$fileHashName = $this->arResult['IMPORT_FILE_PARAMETERS']['FILE_HASH'];
			$filePath = $tmpDirPath.$fileHashName;
		}
		else
		{
			$fileHashName = md5($_FILES['file']['tmp_name']).'.tmp';
			$filePath = $tmpDirPath.$fileHashName;
			$this->moveFileToLocation($_FILES['file']['tmp_name'], $filePath);
			$this->changeFileMode($filePath, BX_FILE_PERMISSIONS);

			$this->arResult['IMPORT_FILE_PARAMETERS']['FILE_NAME'] = $_FILES['file']['name'];
			$this->arResult['IMPORT_FILE_PARAMETERS']['FILE_HASH'] = $fileHashName;
		}

		if ($this->checkFileHashName($fileHashName) && $this->checkFileExistenceByPath($filePath))
			return $filePath;
		else
			return null;
	}

	/**
	 * Moves file to new location
	 *
	 * @param $file - File
	 * @param $location - New location
	 */
	private function moveFileToLocation($file, $location)
	{
		move_uploaded_file($file, $location);
	}

	/**
	 * Changes file mode
	 *
	 * @param $filePath - Path to file
	 * @param $mode - Target mode
	 */
	private function changeFileMode($filePath, $mode)
	{
		@chmod($filePath, $mode);
	}

	/**
	 * Returns user's data by user's id
	 *
	 * @param $userId
	 * @return array|bool|false|mixed|null
	 */
	private function getUsersDataById($userId)
	{
		$result = array();
		$fields = array('ID', 'TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME');

		$dbUser = CUser::GetByID($userId);
		$user = is_object($dbUser) ? $dbUser->Fetch() : null;

		foreach ($fields as $field)
			$result[$field] = $user[$field];

		return $result;
	}

	/**
	 * Generates demo values for file
	 *
	 * @param int $numberOfDemoLines - The amount of line in sample CSV file with demo values
	 * @return array
	 */
	private function generateDemoValues($numberOfDemoLines = 1)
	{
		$demoValues = array();
		$time = time();
		$deadline = UI::formatDateTime($time + 86400);
		$startDatePlan = UI::formatDateTime($time);
		$endDatePlan = $deadline;

		for ($i = 0; $i < $numberOfDemoLines; $i++)
		{
			$demoValues[$i] = array(
				'TITLE' => Loc::getMessage('TASKS_IMPORT_DEMO_VALUES_TITLE'),
				'DESCRIPTION' => Loc::getMessage('TASKS_IMPORT_DEMO_VALUES_DESCRIPTION'),
				'PRIORITY' => '1',
				'RESPONSIBLE' => Loc::getMessage('TASKS_IMPORT_DEMO_VALUES_RESPONSIBLE'),
				'ORIGINATOR' => Loc::getMessage('TASKS_IMPORT_DEMO_VALUES_ORIGINATOR'),
				'ACCOMPLICES' => Loc::getMessage('TASKS_IMPORT_DEMO_VALUES_ACCOMPLICES'),
				'AUDITORS' => Loc::getMessage('TASKS_IMPORT_DEMO_VALUES_AUDITORS'),
				'DEADLINE' => $deadline,
				'START_DATE_PLAN' => $startDatePlan,
				'END_DATE_PLAN' => $endDatePlan,
				'ALLOW_CHANGE_DEADLINE' => '0',
				'MATCH_WORK_TIME' => '0',
				'TASK_CONTROL' => '0',
				'PARAM_1' => '0',
				'PARAM_2' => '0',
				'PROJECT' => Loc::getMessage('TASKS_IMPORT_DEMO_VALUES_PROJECT'),
				'ALLOW_TIME_TRACKING' => '1',
				'TIME_ESTIMATE' => 43200,
				'CHECKLIST' => Loc::getMessage('TASKS_IMPORT_DEMO_VALUES_CHECKLIST'),
				'TAGS' => Loc::getMessage('TASKS_IMPORT_DEMO_VALUES_TAGS')
			);
		}

		return $demoValues;
	}

	/**
	 * Creates file with demo values to download
	 *
	 * @param $demoValues - Demo values
	 */
	private function createDemoFile($demoValues)
	{
		Header("Content-Type: application/force-download");
		Header("Content-Type: application/octet-stream");
		Header("Content-Type: application/download");
		Header("Content-Disposition: attachment;filename=tasks.csv");
		Header("Content-Transfer-Encoding: binary");

		// add UTF-8 BOM marker
		if (defined('BX_UTF') && BX_UTF)
			echo chr(239).chr(187).chr(191);

		foreach ($this->arResult['HEADERS'] as $header)
			echo '"'.str_replace('"', '""', $header['name']).'";';
		echo "\n";

		foreach ($demoValues as $line => $values)
		{
			foreach ($this->arResult['HEADERS'] as $header)
				echo isset($values[$header['id']]) ? '"'.str_replace('"', '""', $values[$header['id']]).'";' : '"";';
			echo "\n";
		}
	}

	/**
	 * Downloads sample CSV file
	 */
	private function downloadSampleCsvFile()
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();

		$demoValues = $this->generateDemoValues();
		$this->createDemoFile($demoValues);

		CMain::FinalActions();
		die();
	}

	/**
	 * Returns separator's sign by separator's name
	 *
	 * @param $separatorText - Separator's name
	 * @return string - Separator's sign
	 */
	private function getSeparatorByText($separatorText)
	{
		switch ($separatorText)
		{
			case 'semicolon':
				$separator = ';';
				break;
			case 'comma':
				$separator = ',';
				break;
			case 'tab':
				$separator = "\t";
				break;
			case 'space':
				$separator = ' ';
				break;
			default:
				$separator = ';';
				break;
		}
		return $separator;
	}

	/**
	 * Transforms string with delimiters [*] and [**] ([***] if multipleArray) in array
	 *
	 * @param $string - String
	 * @param bool $multipleArray - True if more that 2 divisions
	 * @return array
	 */
	private function parseStringToArray($string, $multipleArray = false)
	{
		$result = array();

		if ($multipleArray)
		{
			$explodedRows = explode('[///]', $string);
			foreach ($explodedRows as $index => $explodedRow)
			{
				if ($explodedRow == '')
					continue;
				$explodedResults = explode('[//]', $explodedRow);
				foreach ($explodedResults as $explodedResult)
				{
					if ($explodedResult == '')
						continue;
					$tmp = explode('[/]', $explodedResult);
					$result[$index][$tmp[0]] = $tmp[1];
				}
			}
		}
		else
		{
			$explodedResults = explode('[//]', $string);
			foreach ($explodedResults as $explodedResult)
			{
				if ($explodedResult == '')
					continue;
				$tmp = explode('[/]', $explodedResult);
				if ($multipleArray)
					$result[0][$tmp[0]] = $tmp[1];
				else
					$result[$tmp[0]] = $tmp[1];
			}
		}

		return $result;
	}
}