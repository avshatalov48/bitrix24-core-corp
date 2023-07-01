<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Application;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Access;
use Bitrix\Tasks\Import\PersonNameFormatter;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Error;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

/**
 * Class TasksImportComponent
 */
class TasksImportComponent extends TasksBaseComponent
{
	public static $map = [
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
		'SKIP_EMPTY_COLUMNS' => 'skip_empty_columns',
	];

	protected function getData()
	{
		$encodings = [
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
			'UTF-16' => 'UTF-16',
		];

		foreach (array_keys($encodings) as $key)
		{
			if ($key !== 'auto')
			{
				$this->arResult['CHARSETS'][] = $key;
			}
		}

		$currentUserId = $this->arParams['VARIABLES']['user_id'];
		$currentUser = $this->getUsersDataById($currentUserId);

		$this->arResult['IFRAME'] = isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y';
		$this->arResult['STEP'] = 1;
		$this->arResult['FORM_ID'] = 'TASKS_IMPORT_FORM';
		$this->arResult['ERRORS'] = [];
		$this->arResult['CURRENT_USER_ID'] = $currentUserId;
		$this->arResult['DEFAULT_ORIGINATOR'] = $currentUser;
		$this->arResult['DEFAULT_RESPONSIBLE'] = $currentUser;
		$this->arResult['NAME_FORMATS'] = PersonNameFormatter::getAllDescriptions();
		$this->arResult['ENCODINGS'] = $encodings;
		$this->arResult['HEADERS'] = [
			['id' => 'TITLE', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_TITLE'), 'mandatory' => 'Y'],
			['id' => 'DESCRIPTION', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_DESCRIPTION')],
			['id' => 'PRIORITY', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_PRIORITY')],
			['id' => 'RESPONSIBLE', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_RESPONSIBLE')],
			['id' => 'ORIGINATOR', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_ORIGINATOR')],
			['id' => 'ACCOMPLICES', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_ACCOMPLICES')],
			['id' => 'AUDITORS', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_AUDITORS')],
			['id' => 'DEADLINE', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_DEADLINE')],
			['id' => 'START_DATE_PLAN', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_START_DATE_PLAN')],
			['id' => 'END_DATE_PLAN', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_END_DATE_PLAN_V2')],
			['id' => 'ALLOW_CHANGE_DEADLINE', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_ALLOW_CHANGE_DEADLINE')],
			['id' => 'MATCH_WORK_TIME', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_MATCH_WORK_TIME')],
			['id' => 'TASK_CONTROL', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_TASK_CONTROL_V2')],
			['id' => 'PARAM_1', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_PARAM_1')],
			['id' => 'PARAM_2', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_PARAM_2')],
			['id' => 'PROJECT', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_PROJECT')],
			['id' => 'ALLOW_TIME_TRACKING', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_ALLOW_TIME_TRACKING')],
			['id' => 'TIME_ESTIMATE', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_TIME_ESTIMATE')],
			['id' => 'CHECKLIST', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_CHECKLIST')],
			['id' => 'TAGS', 'name' => Loc::getMessage('TASKS_IMPORT_HEADERS_TAGS')],
		];
		$this->arResult['SEPARATORS'] = [
			'semicolon' => Loc::getMessage('TASKS_IMPORT_FIELDS_FILE_SEPARATOR_SEMICOLON'),
			'comma' => Loc::getMessage('TASKS_IMPORT_FIELDS_FILE_SEPARATOR_COMMA'),
			'tab' => Loc::getMessage('TASKS_IMPORT_FIELDS_FILE_SEPARATOR_TAB'),
			'space' => Loc::getMessage('TASKS_IMPORT_FIELDS_FILE_SEPARATOR_SPACE'),
		];

		$headers = [];
		$requiredFields = [];
		$fields = ['' => ''];
		$upperFields = [];
		foreach($this->arResult['HEADERS'] as $header)
		{
			$headerId = $header['id'];
			$headerName = $header['name'];

			$headers[] = $headerName;
			$fields[$headerId] = $headerName;
			$upperFields[$headerId] = ToUpper($headerName);
			if (($header['mandatory'] ?? null) === 'Y')
			{
				$requiredFields[$headerId] = $headerName;
			}
		}
		$this->arResult['IMPORT_FILE_PARAMETERS']['HEADERS'] = $headers;
		$this->arResult['IMPORT_FILE_PARAMETERS']['REQUIRED_FIELDS'] = $requiredFields;
		$this->arResult['IMPORT_FILE_PARAMETERS']['FIELDS'] = $fields;
		$this->arResult['IMPORT_FILE_PARAMETERS']['UPPER_FIELDS'] = $upperFields;

		$this->arResult['IMPORT_FILE_PARAMETERS']['NAME_FORMAT'] = PersonNameFormatter::getFormatID();
		$this->arResult['IMPORT_FILE_PARAMETERS']['FILE_NAME'] = Loc::getMessage('TASKS_IMPORT_FIELDS_FILE_NAME');
	}

	/**
	 * @return bool
	 */
	protected function doPreAction()
	{
		if (!Access\TaskAccessController::can($this->userId, Access\ActionDictionary::ACTION_TASK_IMPORT))
		{
			$this->errors->add(
				'ACCESS_DENIED',
				Loc::getMessage('TASKS_COMMON_ACCESS_DENIED'),
				Error::TYPE_FATAL
			);
		}
		return parent::doPreAction();
	}

	/**
	 * @return bool|void
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	protected function doPostAction()
	{
		$application = Application::getInstance();
		$context = $application->getContext();
		$request = $context->getRequest();

		if ($request->get('download') === 'csv')
		{
			$this->downloadSampleCsvFile();
		}

		if ($request->isPost() && check_bitrix_sessid())
		{
			$step = (int)$request->get('step');
			$this->arResult['STEP'] = $step;

			if ($request->get('next'))
			{
				if ($step === 1)
				{
					$this->handleStep1($request);
				}
				elseif ($step === 2)
				{
					$this->handleStep2Next($request);
				}
				elseif ($step === 3)
				{
					if (!$this->arResult['IFRAME'])
					{
						$this->moveToCurrentUserTasks();
					}
				}
			}
			elseif ($request->get('back'))
			{
				if ($step === 2)
				{
					$this->handleStep2Back($request);
				}
				elseif ($step === 3)
				{
					$this->arResult['STEP'] = 1;
				}
			}
			elseif ($request->get('cancel'))
			{
				if (!$this->arResult['IFRAME'])
				{
					$this->moveToCurrentUserTasks();
				}
			}
		}
	}

	/**
	 * Does some actions on step 1
	 *
	 * @param HttpRequest $request
	 */
	private function handleStep1(HttpRequest $request): void
	{
		$this->loadRequestValues($request);
		if (!$this->checkFileErrors())
		{
			$this->fillStep2DataFromFile();
		}
		else
		{
			$this->unselectFile();
		}
	}

	/**
	 * Fills the array with import parameters
	 *
	 * @param HttpRequest $request
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	private function handleStep2Next(HttpRequest $request): void
	{
		$headers = $this->parseStringToArray($request->get('hidden_headers'));
		$requiredFields = $this->parseStringToArray($request->get('hidden_required_fields'));
		$fields = $this->parseStringToArray($request->get('hidden_fields'));
		$rows = $this->parseStringToArray($request->get('hidden_rows'), true);
		$skippedColumns = $this->parseStringToArray($request->get('hidden_skipped_columns'));

		$upperFields = $fields;
		$upperFields = array_map('mb_strtoupper', $upperFields);

		$selectedFields = [];
		$requiredFieldsWithFlag = $requiredFields;
		$headersCount = count($headers);

		foreach ($requiredFieldsWithFlag as $key => $value)
		{
			$requiredFieldsWithFlag[$key] = 0;
		}
		for ($i = 0; $i < $headersCount; $i++)
		{
			$fieldValue = $request->get('field_'.$i);
			foreach ($requiredFieldsWithFlag as $key => $value)
			{
				if ($fieldValue === $key)
				{
					$requiredFieldsWithFlag[$key] = 1;
				}
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
		$this->arResult['IMPORT_FILE_PARAMETERS']['MAX_EXECUTION_TIME'] =
			Option::get('tasks', 'import_step', 15);

		$this->loadRequestValues($request);

		foreach ($requiredFieldsWithFlag as $key => $value)
		{
			if ($value === 0)
			{
				$this->arResult['ERRORS']['REQUIRED_FIELDS'] =
					Loc::getMessage('TASKS_IMPORT_ERRORS_REQUIRED_FIELDS');
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
	private function handleStep2Back(HttpRequest $request): void
	{
		$this->loadRequestValues($request);
		$this->arResult['IMPORT_FILE_PARAMETERS']['FROM_TMP_DIR'] = true;

		if (($filePath = $this->getFilePath()) === null)
		{
			return;
		}

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
	private function detectUtf8Encoding(CCSVData $csvFile): bool
	{
		$stringLineOfFile = '';
		$arrayLineOfFile = $csvFile->Fetch();
		foreach ($arrayLineOfFile as $value)
		{
			$stringLineOfFile .= $value.$this->arResult['IMPORT_FILE_PARAMETERS']['SEPARATOR'];
		}

		return Encoding::detectUtf8($stringLineOfFile);
	}

	/**
	 * Configures step 2 data from loaded file's parameters
	 */
	private function fillStep2DataFromFile(): void
	{
		$this->fillFieldsForMatching();

		if (($filePath = $this->getFilePath()) === null)
		{
			return;
		}

		$csvFile = $this->getCsvFileByPath($filePath);
		$this->fillRowsForExampleTableFromCsv($csvFile);

		if (count($this->arResult['IMPORT_FILE_PARAMETERS']['ROWS']) === 0)
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
	private function getEncodedResults(CCSVData $csvFile): array
	{
		$csvFile->SetPos();

		$resultLine = '';
		while ($fetchedArray = $csvFile->Fetch())
		{
			foreach ($fetchedArray as $value)
			{
				$resultLine .= $value.$this->arResult['IMPORT_FILE_PARAMETERS']['SEPARATOR'];
				if (mb_strlen($resultLine) > 50)
				{
					break;
				}
			}
			if (mb_strlen($resultLine) > 50)
			{
				break;
			}
		}
		$resultLine = mb_substr($resultLine, 0, 50);

		$encodedResult = [];
		foreach ($this->arResult['CHARSETS'] as $charset)
		{
			$encodedResult[$charset] = (
				$charset === SITE_CHARSET
					? mb_convert_encoding($resultLine, $charset, $charset)
					: Encoding::convertEncoding($resultLine, $charset, SITE_CHARSET)
			);

			$questionsCount = substr_count($encodedResult[$charset], '?');
			$maxQuestionsCount = mb_strlen($encodedResult[$charset]) * 1 / 3;

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
	private function checkFileErrors(): bool
	{
		if (!is_uploaded_file($_FILES['file']['tmp_name']))
		{
			if ($this->arResult['IMPORT_FILE_PARAMETERS']['FROM_TMP_DIR'])
			{
				return false;
			}
			$this->arResult['ERRORS']['FILE_LABEL'] = Loc::getMessage('TASKS_IMPORT_ERRORS_FILE_NOT_FOUND');
		}
		else
		{
			if ($_FILES['file']['error'] > 0)
			{
				$this->arResult['ERRORS']['FILE_LABEL'] = Loc::getMessage((
					$_FILES['file']['error'] === 4
						? 'TASKS_IMPORT_ERRORS_FILE_NOT_FOUND'
						: 'TASKS_IMPORT_ERRORS_FILE_ERRORS'
				));
			}
			if (($error = CFile::CheckFile($_FILES['file'], 0, false, 'csv')) !== '')
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
	 * @return mixed - Encoded data
	 */
	private function encodeDataToSiteCharset($data)
	{
		$fileEncoding = $this->arResult['IMPORT_FILE_PARAMETERS']['FILE_ENCODING'];

		if ($fileEncoding === 'auto' && isset($this->arResult['IMPORT_FILE_PARAMETERS']['FOUND_FILE_ENCODING']))
		{
			$fileEncoding = $this->arResult['IMPORT_FILE_PARAMETERS']['FOUND_FILE_ENCODING'];
		}

		if ($fileEncoding !== 'auto' && $fileEncoding !== mb_strtolower(SITE_CHARSET))
		{
			//HACK: Remove UTF-8 BOM
			if ($fileEncoding === 'UTF-8')
			{
				if (is_string($data) && mb_strpos($data, "\xEF\xBB\xBF") === 0)
				{
					$data = mb_substr($data, 3);
				}
				elseif (is_array($data) && is_string($data[0]) && mb_strpos($data[0], "\xEF\xBB\xBF") === 0)
				{
					$data[0] = mb_substr($data[0], 3);
				}
			}

			$data = Encoding::convertEncoding($data, $fileEncoding, SITE_CHARSET);
			if ($fileEncoding === SITE_CHARSET)
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
	private function fillRowsForExampleTableFromCsv(CCSVData $csvFile): void
	{
		$row = 1;
		$headers = [];
		$rows = [];
		$skippedColumns = [];
		$skipEmptyColumns = $this->arResult['IMPORT_FILE_PARAMETERS']['SKIP_EMPTY_COLUMNS'];
		$headersInFirstRow = $this->arResult['IMPORT_FILE_PARAMETERS']['HEADERS_IN_FIRST_ROW'];
		$maxExampleRows = ($headersInFirstRow ? 4 : 3);

		while ($rowData = $csvFile->Fetch())
		{
			$rowData = $this->encodeDataToSiteCharset($rowData);

			if ($row === 1)
			{
				$columnIndex = 0;
				foreach ($rowData as $key => $value)
				{
					if ($skipEmptyColumns && $value === '')
					{
						$skippedColumns[$columnIndex] = $columnIndex;
						$columnIndex++;
						continue;
					}
					if ($headersInFirstRow)
					{
						$headers[$key] = (
							empty($value)
								? Loc::getMessage('TASKS_IMPORT_CUSTOM_HEADER').' '.($key + 1)
								: trim($value)
						);
					}
					else
					{
						$headers[$key] = Loc::getMessage('TASKS_IMPORT_CUSTOM_HEADER').' '.($key + 1);
					}
					$columnIndex++;
				}
				if (!$headersInFirstRow)
				{
					foreach ($headers as $key => $value)
					{
						$rows[$row][$key] = $rowData[$key];
					}
				}
			}
			else
			{
				foreach ($headers as $key => $value)
				{
					$rows[$row][$key] = $rowData[$key];
				}
			}

			if ($row >= $maxExampleRows)
			{
				break;
			}

			$row++;
		}
		$this->arResult['IMPORT_FILE_PARAMETERS']['HEADERS'] = $headers;
		$this->arResult['IMPORT_FILE_PARAMETERS']['ROWS'] = $rows;
		$this->arResult['IMPORT_FILE_PARAMETERS']['SKIPPED_COLUMNS'] = $skippedColumns;
	}

	/**
	 * Fills the arrays for step 2 tuning field's match
	 */
	private function fillFieldsForMatching(): void
	{
		$requiredFields = [];
		$fields = ['' => ''];
		$upperFields = [];

		foreach ($this->arResult['HEADERS'] as $header)
		{
			$headerId = $header['id'];
			$headerName = $header['name'];

			$fields[$headerId] = $headerName;
			$upperFields[$headerId] = ToUpper($headerName);
			if (($header['mandatory'] ?? null) === 'Y')
			{
				$requiredFields[$headerId] = $headerName;
			}
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
	private function loadRequestValues(HttpRequest $request): void
	{
		foreach (self::$map as $key => $value)
		{
			if ($request->get($value))
			{
				$this->arResult['IMPORT_FILE_PARAMETERS'][$key] = $request->get($value);
			}
		}

		if ($this->arResult['STEP'] === 2)
		{
			if ($request->get('hidden_default_originator'))
			{
				$this->arResult['IMPORT_FILE_PARAMETERS']['DEFAULT_ORIGINATOR'] =
					$request->get('hidden_default_originator');
			}
			if ($request->get('hidden_default_responsible'))
			{
				$this->arResult['IMPORT_FILE_PARAMETERS']['DEFAULT_RESPONSIBLE'] =
					$request->get('hidden_default_responsible');
			}
		}

		$headersInFirstRow = $this->arResult['IMPORT_FILE_PARAMETERS']['HEADERS_IN_FIRST_ROW'];
		$skipEmptyColumns = $this->arResult['IMPORT_FILE_PARAMETERS']['SKIP_EMPTY_COLUMNS'];
		$fromTmpDir = $this->arResult['IMPORT_FILE_PARAMETERS']['FROM_TMP_DIR'];

		$this->arResult['DEFAULT_ORIGINATOR'] = $this->getUsersDataById(
			$this->arResult['IMPORT_FILE_PARAMETERS']['DEFAULT_ORIGINATOR']
		);
		$this->arResult['DEFAULT_RESPONSIBLE'] = $this->getUsersDataById(
			$this->arResult['IMPORT_FILE_PARAMETERS']['DEFAULT_RESPONSIBLE']
		);
		$this->arResult['IMPORT_FILE_PARAMETERS']['SEPARATOR'] = $this->getSeparatorByText(
			$this->arResult['IMPORT_FILE_PARAMETERS']['SEPARATOR_TEXT']
		);
		$this->arResult['IMPORT_FILE_PARAMETERS']['HEADERS_IN_FIRST_ROW'] = isset($headersInFirstRow);
		$this->arResult['IMPORT_FILE_PARAMETERS']['SKIP_EMPTY_COLUMNS'] = isset($skipEmptyColumns);
		$this->arResult['IMPORT_FILE_PARAMETERS']['FROM_TMP_DIR'] = $fromTmpDir === 'Y';
	}

	/**
	 * Redirects to current user's tasks page
	 */
	private function moveToCurrentUserTasks(): void
	{
		LocalRedirect(
			CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_USER_TASKS'],
				['user_id' => $this->arResult['CURRENT_USER_ID']]
			)
		);
	}

	/**
	 * Unselect the file
	 */
	private function unselectFile(): void
	{
		$this->arResult['IMPORT_FILE_PARAMETERS']['FILE_NAME'] = Loc::getMessage('TASKS_IMPORT_FIELDS_FILE_NAME');
	}

	/**
	 * Returns CSV file by path
	 *
	 * @param string $filePath - Path to file
	 * @return CCSVData - CSV file
	 */
	private function getCsvFileByPath(string $filePath): CCSVData
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
	 * @param string $fileHashName - Hash name of file
	 * @return bool
	 */
	private function checkFileHashName(string $fileHashName): bool
	{
		if (!preg_match('/^[0-9a-f]{32}\.tmp$/i', $fileHashName))
		{
			$this->arResult['ERRORS']['FILE_LABEL'] = Loc::getMessage('TASKS_IMPORT_ERRORS_FILE_PATH');
			return false;
		}

		return true;
	}

	/**
	 * Returns true if file exists by path
	 *
	 * @param string $filePath - Path to file
	 * @return bool
	 */
	private function checkFileExistenceByPath(string $filePath): bool
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
	private function getFilePath(): ?string
	{
		if (($tmpDirPath = $this->getTmpDirPath()) === null)
		{
			return null;
		}

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
		{
			return $filePath;
		}

		return null;
	}

	/**
	 * Moves file to new location
	 *
	 * @param string $file - File
	 * @param string $location - New location
	 */
	private function moveFileToLocation(string $file, string $location): void
	{
		move_uploaded_file($file, $location);
	}

	/**
	 * Changes file mode
	 *
	 * @param string $filePath - Path to file
	 * @param int $mode - Target mode
	 */
	private function changeFileMode(string $filePath, int $mode): void
	{
		chmod($filePath, $mode);
	}

	/**
	 * Returns user's data by user's id
	 *
	 * @param int $userId
	 * @return mixed
	 */
	private function getUsersDataById(int $userId)
	{
		$result = [];
		$fields = ['ID', 'TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME'];

		$dbUser = CUser::GetByID($userId);
		$user = (is_object($dbUser) ? $dbUser->Fetch() : null);
		if (is_array($user))
		{
			foreach ($fields as $field)
			{
				$result[$field] = $user[$field];
			}
		}

		return $result;
	}

	/**
	 * Generates demo values for file
	 *
	 * @param int $numberOfDemoLines - The amount of line in sample CSV file with demo values
	 * @return array
	 */
	private function generateDemoValues(int $numberOfDemoLines = 1): array
	{
		$demoValues = [];
		$time = time();
		$deadline = UI::formatDateTime($time + 86400);
		$startDatePlan = UI::formatDateTime($time);
		$endDatePlan = $deadline;

		for ($i = 0; $i < $numberOfDemoLines; $i++)
		{
			$demoValues[$i] = [
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
				'CHECKLIST' => Loc::getMessage('TASKS_IMPORT_DEMO_VALUES_CHECKLIST_NEW'),
				'TAGS' => Loc::getMessage('TASKS_IMPORT_DEMO_VALUES_TAGS'),
			];
		}

		return $demoValues;
	}

	/**
	 * Creates file with demo values to download
	 *
	 * @param array $demoValues - Demo values
	 */
	private function createDemoFile(array $demoValues): void
	{
		Header("Content-Type: application/force-download");
		Header("Content-Type: application/octet-stream");
		Header("Content-Type: application/download");
		Header("Content-Disposition: attachment;filename=tasks.csv");
		Header("Content-Transfer-Encoding: binary");

		// add UTF-8 BOM marker
		if (defined('BX_UTF') && BX_UTF)
		{
			echo chr(239).chr(187).chr(191);
		}

		foreach ($this->arResult['HEADERS'] as $header)
		{
			echo '"'.str_replace('"', '""', $header['name']).'";';
		}
		echo "\n";

		foreach ($demoValues as $line => $values)
		{
			foreach ($this->arResult['HEADERS'] as $header)
			{
				echo (
					isset($values[$header['id']])
						? '"'.str_replace('"', '""', $values[$header['id']]).'";'
						: '"";'
				);
			}
			echo "\n";
		}
	}

	/**
	 * Downloads sample CSV file
	 */
	private function downloadSampleCsvFile(): void
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();

		$demoValues = $this->generateDemoValues();
		$this->createDemoFile($demoValues);

		CMain::FinalActions();
	}

	/**
	 * Returns separator's sign by separator's name
	 *
	 * @param string $separatorText - Separator's name
	 * @return string - Separator's sign
	 */
	private function getSeparatorByText(string $separatorText): string
	{
		switch ($separatorText)
		{
			case 'comma':
				$separator = ',';
				break;

			case 'tab':
				$separator = "\t";
				break;

			case 'space':
				$separator = ' ';
				break;

			case 'semicolon':
			default:
				$separator = ';';
				break;
		}

		return $separator;
	}

	/**
	 * Transforms string with delimiters [/] and [//] ([///] if multipleArray) in array
	 *
	 * @param string $string - String
	 * @param bool $multipleArray - True if more that 2 divisions
	 * @return array
	 */
	private function parseStringToArray(string $string, bool $multipleArray = false): array
	{
		$result = [];

		if ($multipleArray)
		{
			$explodedRows = explode('[///]', $string);
			foreach ($explodedRows as $index => $explodedRow)
			{
				if ($explodedRow === '')
				{
					continue;
				}
				$explodedResults = explode('[//]', $explodedRow);
				foreach ($explodedResults as $explodedResult)
				{
					if ($explodedResult === '')
					{
						continue;
					}
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
				if ($explodedResult === '')
				{
					continue;
				}
				$tmp = explode('[/]', $explodedResult);
				if ($multipleArray)
				{
					$result[0][$tmp[0]] = $tmp[1];
				}
				else
				{
					$result[$tmp[0]] = $tmp[1];
				}
			}
		}

		return $result;
	}
}