<?php

namespace Bitrix\BIConnector\Controller\ExternalSource;

use Bitrix\BIConnector\ExternalSource\Type;
use Bitrix\BIConnector\ExternalSource\FieldType;
use Bitrix\BIConnector\ExternalSource\Validation\ImportDataValidator;
use Bitrix\BIConnector\ExternalSource\Validation\Rules\RulesProvider;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\IO;
use Bitrix\BIConnector\ExternalSource;
use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Integration\UI\FileUploader;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\UI\FileUploaderController\DatasetUploaderController;

class Dataset extends Controller
{
	private const FILE_ROWS_LIMIT = 300000;
	private const LOG_FILE_SIZE_LIMIT_MB = 10;
	private const MAX_ERRORS_COUNT = 200;

	private static array $fileMap = [
		'encoding' => 'encoding',
		'separator' => 'delimiter',
		'firstLineHeader' => 'hasHeaders',
	];

	private static array $datasetMap = [
		'id' => 'ID',
		'name' => 'NAME',
		'description' => 'DESCRIPTION',
		'externalCode' => 'EXTERNAL_CODE',
		'externalName' => 'EXTERNAL_NAME',
	];

	private static array $fieldsMap = [
		'id' => 'ID',
		'type' => 'TYPE',
		'name' => 'NAME',
		'externalCode' => 'EXTERNAL_CODE',
		'visible' => 'VISIBLE',
	];

	protected function processBeforeAction(Action $action): bool
	{
		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_EXTERNAL_DASHBOARD_CONFIG))
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_EXTERNAL_SOURCE_DATASET_ACCESS_ERROR')));

			return false;
		}

		return parent::processBeforeAction($action);
	}

	public function getPrimaryAutoWiredParameter(): ExactParameter
	{
		return new ExactParameter(
			ExternalSource\Internal\ExternalDataset::class,
			'dataset',
			function($className, $id)
			{
				$datasetId = (int)$id;
				if ($datasetId <= 0)
				{
					$this->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_NOT_FOUND_ERROR')));

					return null;
				}

				$dataset = ExternalSource\DatasetManager::getById($datasetId);
				if (!$dataset)
				{
					$this->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_NOT_FOUND_ERROR')));

					return null;
				}

				return $dataset;
			}
		);
	}

	public function logErrorsIntoFileAction(string $type, array $fields): ?string
	{
		$checkBeforeFileCheckResult = $this->checkAndPrepareBeforeFileCheck($type, $fields);
		if (!$checkBeforeFileCheckResult->isSuccess())
		{
			$this->addErrors($checkBeforeFileCheckResult->getErrors());

			return null;
		}

		$checkBeforeViewData = $checkBeforeFileCheckResult->getData();
		$file = $checkBeforeViewData['file'];

		if (!$file)
		{
			$this->addError(new Error('Empty file'));

			return null;
		}

		$datasetFields = $checkBeforeViewData['fields'];
		$datasetSettings = $checkBeforeViewData['settings'];
		$datasetSettings = array_column($datasetSettings, 'FORMAT', 'TYPE');

		$reader = ExternalSource\FileReader\Factory::getReader(Type::Csv, $file);
		$rulesMap = RulesProvider::getRules(Type::Csv, $datasetSettings);
		$validator = new ImportDataValidator($rulesMap, $datasetFields);
		$rowsCount = 0;

		$filePath = \CTempFile::GetFileName('errors.html');
		$logFile = new IO\File($filePath);

		global $APPLICATION;
		ob_start();

		$APPLICATION->IncludeComponent('bitrix:biconnector.dataset.import.errors', '', ['part' => 'header', 'datasetTitle' => $fields['datasetProperties']['name'] ?? '']);

		$logFile->putContents(ob_get_clean());

		ob_start();

		$APPLICATION->IncludeComponent('bitrix:biconnector.dataset.import.errors', '', ['part' => 'row']);

		$rowTemplate = ob_get_clean();

		$batchCount = 0;
		$batchContent = '';
		$errorsCount = 0;

		foreach ($reader->readAllRowsByOne() as $row)
		{
			$rowsCount++;
			if ($rowsCount > self::FILE_ROWS_LIMIT)
			{
				$this->addError(
					new Error(
						Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_MAX_ROWS')
					)
				);

				return null;
			}

			$rowValidationResult = $validator->validateRow($row);
			if (!$rowValidationResult->isSuccess())
			{
				$rowNumber = $file['hasHeaders'] ? $rowsCount + 1 : $rowsCount;
				foreach ($rowValidationResult->getErrors() as $error)
				{
					$errorsCount++;
					$batchCount++;
					$rowContent = str_replace(
						['#ERROR_NUMBER#', '#MESSAGE#', '#ROW_NUMBER#', '#FIELD_NAME#'],
						[(string)$errorsCount, htmlspecialcharsbx($error->getMessage()), (string)$rowNumber, htmlspecialcharsbx($datasetFields[$error->getCustomData()['field']]['NAME'])],
						$rowTemplate,
					);
					$batchContent .= $rowContent . PHP_EOL;

					if ($batchCount > 1000)
					{
						$logFile->putContents($batchContent, IO\File::APPEND);
						$batchCount = 0;
						$batchContent = '';
					}
				}
			}

			if ($batchCount === 0 && ($logFile->getSize() / 1024 / 1024) > self::LOG_FILE_SIZE_LIMIT_MB)
			{
				ob_start();

				$APPLICATION->IncludeComponent('bitrix:biconnector.dataset.import.errors', '', ['part' => 'footer_overflow']);

				$logFile->putContents(ob_get_clean(), IO\File::APPEND);

				return $logFile->getContents();
			}
		}

		if (!empty($batchContent))
		{
			$logFile->putContents($batchContent, IO\File::APPEND);
		}

		ob_start();

		$APPLICATION->IncludeComponent('bitrix:biconnector.dataset.import.errors', '', ['part' => 'footer']);

		$logFile->putContents(ob_get_clean(), IO\File::APPEND);

		return $logFile->getContents();
	}

	/**
	 * Adds new external dataset
	 *
	 * @param string $type
	 * @param array $fields
	 * @param int|null $sourceId
	 * @return array|null
	 */
	public function addAction(string $type, array $fields, ?int $sourceId = null): ?array
	{
		if (!$sourceId)
		{
			$sourceId = (int)($fields['connectionSettings']['connectionId'] ?? 0);
		}
		$checkBeforeAddResult = $this->checkAndPrepareBeforeAdd($type, $fields, $sourceId);
		if (!$checkBeforeAddResult->isSuccess())
		{
			$this->addErrors($checkBeforeAddResult->getErrors());

			return null;
		}

		$checkBeforeAddData = $checkBeforeAddResult->getData();
		$dataset = $checkBeforeAddData['dataset'];
		$datasetFields = $checkBeforeAddData['fields'];
		$datasetSettings = $checkBeforeAddData['settings'];

		$addResult = ExternalSource\DatasetManager::add($dataset, $datasetFields, $datasetSettings, $sourceId);
		if (!$addResult->isSuccess())
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_ADD_ERROR'), 'ADD_ERROR'));

			return null;
		}

		$addResultData = $addResult->getData();

		$file = $checkBeforeAddData['file'];
		if ($file)
		{
			try
			{
				$importer = new FileImporter($addResultData['id'], $file);
				$importResult = $importer->import();
				if (!$importResult->isSuccess())
				{
					$this->addError(
						new Error(
							Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_IMPORT_ERROR'),
							'IMPORT_ERROR'
						)
					);

					return null;
				}
			}
			catch (\Exception)
			{
				$this->addError(
					new Error(
						Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_IMPORT_ERROR'),
						'IMPORT_ERROR_EXCEPTION'
					)
				);

				return null;
			}
		}

		return [
			'id' => $addResultData['id'],
			'name' => $addResultData['dataset']['NAME'],
		];
	}

	private function checkAndPrepareBeforeAdd(string $type, array $fields, ?int $sourceId = null): Result
	{
		$result = new Result();

		$enumType = ExternalSource\Type::tryFrom($type);
		if (!$enumType)
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_UNKNOWN_DATASET_TYPE'), 'UNKNOWN_TYPE'));

			return $result;
		}

		$prepareResult = $this->prepareFields($fields);
		if (!$prepareResult->isSuccess())
		{
			$result->addErrors($prepareResult->getErrors());

			return $result;
		}

		$preparedFields = $prepareResult->getData();

		$file = $preparedFields['file'];
		$dataset = $preparedFields['dataset'];
		$datasetFields = $preparedFields['fields'];
		$datasetSettings = $preparedFields['settings'];

		if ($file)
		{
			$checkFileResult = $this->checkFile($enumType, $file);
			if (!$checkFileResult->isSuccess())
			{
				$result->addErrors($checkFileResult->getErrors());

				return $result;
			}
		}

		if (empty($dataset['NAME']))
		{
			$dataset['NAME'] = $this->getDefaultDatasetName($enumType);
		}

		if (in_array($dataset['NAME'], ExternalSource\SupersetServiceIntegration::getTableList(), true))
		{
			$result->addError(
				new Error(
					Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_ALREADY_EXIST_ERROR')
				)
			);
		}

		$dataset['TYPE'] = $enumType->value;
		$dataset['DATE_CREATE'] = new DateTime();
		$dataset['CREATED_BY_ID'] = $this->getCurrentUser()?->getId();

		if (empty($datasetFields))
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_EMPTY_FIELDS'), 'EMPTY_FIELDS'));
		}

		if (empty($datasetSettings))
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_EMPTY_SETTINGS'), 'EMPTY_SETTINGS'));
		}

		$result->setData([
			'file' => $file,
			'dataset' => $dataset,
			'fields' => $datasetFields,
			'settings' => $datasetSettings,
		]);

		return $result;
	}

	/**
	 * Updates external dataset
	 *
	 * @param int $id
	 * @param string $type
	 * @param array $fields
	 * @param int|null $sourceId
	 * @return array|null
	 */
	public function updateAction(int $id, string $type, array $fields, ?int $sourceId = null): ?array
	{
		$checkBeforeUpdateResult = $this->checkAndPrepareBeforeUpdate($type, $fields, $sourceId);
		if (!$checkBeforeUpdateResult->isSuccess())
		{
			$this->addErrors($checkBeforeUpdateResult->getErrors());

			return null;
		}

		$checkBeforeAddData = $checkBeforeUpdateResult->getData();
		$dataset = $checkBeforeAddData['dataset'];
		$datasetFields = $checkBeforeAddData['fields'];
		$datasetSettings = $checkBeforeAddData['settings'];

		$updateResult = ExternalSource\DatasetManager::update($id, $dataset, $datasetFields, $datasetSettings);
		if (!$updateResult->isSuccess())
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_UPDATE_ERROR'), 'UPDATE_ERROR'));

			return null;
		}

		$file = $checkBeforeAddData['file'];
		if ($file)
		{
			try
			{
				$importer = new FileImporter($id, $file);
				$importResult = $importer->reImport();
				if (!$importResult->isSuccess())
				{
					$this->addError(
						new Error(
							Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_IMPORT_ERROR'),
							'IMPORT_ERROR'
						)
					);

					return null;
				}
			}
			catch (\Exception)
			{
				$this->addError(
					new Error(
						Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_IMPORT_ERROR'),
						'IMPORT_ERROR_EXCEPTION'
					)
				);

				return null;
			}
		}

		return [
			'id' => $id,
			'name' => $dataset['NAME'],
		];
	}

	private function checkAndPrepareBeforeUpdate(string $type, array $fields, ?int $sourceId = null): Result
	{
		$result = new Result();

		$enumType = ExternalSource\Type::tryFrom($type);
		if (!$enumType)
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_UNKNOWN_DATASET_TYPE'), 'UNKNOWN_TYPE'));

			return $result;
		}

		$prepareResult = $this->prepareFields($fields);
		if (!$prepareResult->isSuccess())
		{
			$result->addErrors($prepareResult->getErrors());

			return $result;
		}

		$preparedFields = $prepareResult->getData();

		$file = $preparedFields['file'];
		$dataset = $preparedFields['dataset'];
		$datasetFields = $preparedFields['fields'];
		$datasetSettings = $preparedFields['settings'];

		if ($file)
		{
			$checkFileResult = $this->checkFile($enumType, $file);
			if (!$checkFileResult->isSuccess())
			{
				$result->addErrors($checkFileResult->getErrors());

				return $result;
			}
		}

		$dataset['DATE_UPDATE'] = new DateTime();
		$dataset['UPDATED_BY_ID'] = $this->getCurrentUser()?->getId();

		$isNeedView = !empty($file) || ($sourceId && !empty($dataset['NAME']));
		if ($isNeedView)
		{
			$viewer = new DatasetViewer($enumType, $datasetFields, $datasetSettings);
			if ($file)
			{
				$viewer->setFile($file);
			}
			elseif ($sourceId)
			{
				$viewer
					->setSourceId($sourceId)
					->setExternalTableData($dataset ?? null)
				;
			}

			$data = $viewer->getData();

			$checkAfterViewResult = $this->checkAfterView($preparedFields, $data, $type);
			if (!$checkAfterViewResult->isSuccess())
			{
				$result->addErrors($checkAfterViewResult->getErrors());

				return $result;
			}
		}

		$result->setData([
			'file' => $file,
			'dataset' => $dataset,
			'fields' => $datasetFields,
			'settings' => $datasetSettings,
		]);

		return $result;
	}

	/**
	 * Views external dataset
	 *
	 * @param string $type
	 * @param array $fields
	 * @param int|null $sourceId
	 * @return ViewResponce|null
	 */
	public function viewAction(string $type, array $fields, ?int $sourceId = null): ?ViewResponce
	{
		$checkBeforeViewResult = $this->checkAndPrepareBeforeView($type, $fields, $sourceId);
		if (!$checkBeforeViewResult->isSuccess())
		{
			$this->addErrors($checkBeforeViewResult->getErrors());

			return null;
		}

		$checkBeforeViewData = $checkBeforeViewResult->getData();
		$file = $checkBeforeViewData['file'];
		$dataset = $checkBeforeViewData['dataset'];
		$datasetFields = $checkBeforeViewData['fields'];
		$datasetSettings = $checkBeforeViewData['settings'];

		$viewer = new DatasetViewer(
			ExternalSource\Type::tryFrom($type),
			$datasetFields,
			$datasetSettings
		);

		if ($file)
		{
			$viewer->setFile($file);
		}
		elseif ($sourceId)
		{
			$viewer
				->setSourceId($sourceId)
				->setExternalTableData($dataset ?? null)
			;
		}

		try
		{
			$data = $viewer->getDataForView();
		}
		catch (\Exception $e)
		{
			$this->addError(new Error($e->getMessage()));

			return null;
		}

		$checkAfterViewResult = $this->checkAfterView($checkBeforeViewData, $data, $type);
		if (!$checkAfterViewResult->isSuccess())
		{
			$this->addErrors($checkAfterViewResult->getErrors());

			return null;
		}

		$viewResponce = new ViewResponce();
		$viewResponce->setData($data);

		return $viewResponce;
	}

	private function checkAndPrepareBeforeView(string $type, array $fields, ?int $sourceId = null): Result
	{
		$result = new Result();

		$enumType = ExternalSource\Type::tryFrom($type);
		if (!$enumType)
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_UNKNOWN_DATASET_TYPE'), 'UNKNOWN_TYPE'));

			return $result;
		}

		$prepareResult = $this->prepareFields($fields);
		if (!$prepareResult->isSuccess())
		{
			$result->addErrors($prepareResult->getErrors());

			return $result;
		}

		$preparedFields = $prepareResult->getData();

		$file = $preparedFields['file'];
		$dataset = $preparedFields['dataset'];
		$datasetFields = $preparedFields['fields'];
		$datasetSettings = $preparedFields['settings'];

		if ($file)
		{
			$checkFileResult = $this->checkFile($enumType, $file);
			if (!$checkFileResult->isSuccess())
			{
				$result->addErrors($checkFileResult->getErrors());

				return $result;
			}
		}

		$result->setData([
			'file' => $file,
			'dataset' => $dataset,
			'fields' => $datasetFields,
			'settings' => $datasetSettings,
		]);

		return $result;
	}

	private function checkAfterView(array $preparedFields, array $viewData, string $type): Result
	{
		$result = new Result();

		if (empty($viewData['data']))
		{
			if (ExternalSource\Type::tryFrom($type) === ExternalSource\Type::Csv)
			{
				$result->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_EMPTY_DATA_ERROR'), 'EMPTY_DATA'));
			}

			return $result;
		}

		$dataset = $preparedFields['dataset'];
		if (
			!empty($dataset['ID'])
			&& (int)$dataset['ID'] > 0
			&& ExternalSource\Type::tryFrom($type) === ExternalSource\Type::Csv
		)
		{
			$datasetFields = ExternalSource\DatasetManager::getDatasetFieldsById((int)$dataset['ID']);
			if ($datasetFields->count() !== count($viewData['data'][0]))
			{
				$result->addError(
					new Error(
						Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DIFFERENT_COUNT_FIELDS_ERROR'),
						'DIFFERENT_COUNT_FIELDS'
					)
				);

				return $result;
			}
		}

		return $result;
	}

	public function checkFileAction(string $type, array $fields): ?array
	{
		$checkBeforeFileCheckResult = $this->checkAndPrepareBeforeFileCheck($type, $fields);
		if (!$checkBeforeFileCheckResult->isSuccess())
		{
			$this->addErrors($checkBeforeFileCheckResult->getErrors());

			return null;
		}

		$checkBeforeViewData = $checkBeforeFileCheckResult->getData();
		$file = $checkBeforeViewData['file'];

		if (!$file)
		{
			$this->addError(new Error('Empty file'));

			return null;
		}

		$datasetFields = $checkBeforeViewData['fields'];
		$datasetSettings = $checkBeforeViewData['settings'];
		$datasetSettings = array_column($datasetSettings, 'FORMAT', 'TYPE');

		$reader = ExternalSource\FileReader\Factory::getReader(Type::Csv, $file);
		$rulesMap = RulesProvider::getRules(Type::Csv, $datasetSettings);
		$validator = new ImportDataValidator($rulesMap, $datasetFields);
		$errorsCount = 0;
		$rowsCount = 0;
		$result = [];
		foreach ($reader->readAllRowsByOne() as $row)
		{
			$rowsCount++;
			if ($rowsCount > self::FILE_ROWS_LIMIT)
			{
				$this->addError(
					new Error(
						Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_MAX_ROWS')
					)
				);

				return null;
			}

			$rowValidationResult = $validator->validateRow($row);
			if (!$rowValidationResult->isSuccess())
			{
				$rowNumber = $file['hasHeaders'] ? $rowsCount + 1 : $rowsCount;
				$result[$rowNumber] = $rowValidationResult->getErrors();
				$errorsCount += count($rowValidationResult->getErrors());
				if ($errorsCount > self::MAX_ERRORS_COUNT)
				{
					break;
				}
			}
		}

		return [
			'checkFileErrors' => $result,
		];
	}

	private function checkAndPrepareBeforeFileCheck(string $type, array $fields, ?int $sourceId = null): Result
	{
		$result = new Result();

		$enumType = ExternalSource\Type::tryFrom($type);
		if (!$enumType)
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_UNKNOWN_DATASET_TYPE'), 'UNKNOWN_TYPE'));

			return $result;
		}

		$prepareResult = $this->prepareFields($fields);
		if (!$prepareResult->isSuccess())
		{
			$result->addErrors($prepareResult->getErrors());

			return $result;
		}

		$preparedFields = $prepareResult->getData();

		$file = $preparedFields['file'];
		$datasetFields = $preparedFields['fields'];
		$datasetSettings = $preparedFields['settings'];

		if ($file)
		{
			$checkFileResult = $this->checkIsFileEmpty($enumType, $file);
			if (!$checkFileResult->isSuccess())
			{
				$result->addErrors($checkFileResult->getErrors());

				return $result;
			}
		}

		$result->setData([
			'file' => $file,
			'fields' => $datasetFields,
			'settings' => $datasetSettings,
		]);

		return $result;
	}

	/**
	 * Deletes external dataset
	 *
	 * @param int $id
	 * @return bool|null
	 */
	public function deleteAction(int $id): ?bool
	{
		$deleteResult = ExternalSource\DatasetManager::delete($id);
		if (!$deleteResult->isSuccess())
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_DELETE_ERROR'), 'DELETE_ERROR'));

			return null;
		}

		return true;
	}

	public function syncFieldAction(ExternalSource\Internal\ExternalDataset $dataset): ?ViewResponce
	{
		$sourceId = $dataset->getSourceId();

		$fields = [];
		foreach (ExternalSource\DatasetManager::getDatasetFieldsById($dataset->getId()) as $field)
		{
			$fields[] = [
				'ID' => $field->getId(),
				'TYPE' => $field->getType(),
				'NAME' => $field->getName(),
				'EXTERNAL_CODE' => $field->getExternalCode(),
				'VISIBLE' => $field->getVisible(),
			];
		}

		$settings = [];
		foreach (ExternalSource\DatasetManager::getDatasetSettingsById($dataset->getId()) as $setting)
		{
			$settings[] = [
				'TYPE' => $setting->getType(),
				'FORMAT' => $setting->getFormat(),
			];
		}

		if ($settings === [] && Type::tryFrom($dataset->getType()) === Type::Rest)
		{
			$settings = [
				[
					'TYPE' => FieldType::Date->value,
					'FORMAT' => \Bitrix\BIConnector\ExternalSource\Const\Date::Ymd_dash->value,
				],
				[
					'TYPE' => FieldType::DateTime->value,
					'FORMAT' => \Bitrix\BIConnector\ExternalSource\Const\DateTime::Ymd_dash_His_colon->value,
				],
				[
					'TYPE' => FieldType::Double->value,
					'FORMAT' => \Bitrix\BIConnector\ExternalSource\Const\DoubleDelimiter::DOT->value,
				],
			];
		}

		$externalTableData = [
			'NAME' => $dataset->getName(),
			'DESCRIPTION' => $dataset->getDescription(),
			'EXTERNAL_CODE' => $dataset->getExternalCode(),
			'EXTERNAL_NAME' => $dataset->getExternalName(),
		];

		$viewer = new DatasetViewer(
			$dataset->getEnumType(),
			$fields,
			$settings
		);

		$viewer
			->setSourceId($sourceId)
			->setExternalTableData($externalTableData)
		;

		try
		{
			$data = $viewer->getDataForSync();
		}
		catch (\Exception)
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_SYNC_FIELDS_ERROR')));

			return null;
		}

		$data['isChanged'] = false;
		if (
			count($fields) !== count($data['headers'])
			|| count($fields) !== count(array_column($data['headers'], 'id'))
		)
		{
			$data['isChanged'] = true;
		}

		$viewResponce = new ViewResponce();
		$viewResponce->setData($data);

		return $viewResponce;
	}

	public function exportAction(ExternalSource\Internal\ExternalDataset $dataset, string $exportFormat): ?string
	{
		if (!$dataset || $dataset->getEnumType() !== Type::Csv)
		{
			$this->addError(new Error('Invalid dataset specified'));

			return null;
		}

		$exportType = ExternalSource\Exporter\ExportType::tryFrom($exportFormat);
		if (!$exportType)
		{
			$this->addError(new Error('Invalid export format'));

			return null;
		}

		$writer = ExternalSource\Exporter\WriterFactory::getWriter($exportType);
		$dataProvider = ExternalSource\Exporter\DataProviderFactory::getDataProvider($dataset);
		$settings = new ExternalSource\Exporter\Settings($dataset, $writer, $dataProvider);
		$exporter = new ExternalSource\Exporter\Exporter($settings);

		$result = $exporter->export();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		/** @var IO\File $file */
		$file = $result->getData()['file'];

		return $file->getContents();
	}

	private function prepareFields(array $fields): Result
	{
		$result = new Result();

		$fileProperties = $fields['fileProperties'] ?? [];
		$datasetProperties = $fields['datasetProperties'] ?? [];
		$datasetFields = $fields['fieldsSettings'] ?? [];
		$datasetSettings = $fields['dataFormats'] ?? [];

		$resultFile = [];
		$resultDataset = [];
		$resultDatasetFields = [];
		$resultDatasetSettings = [];

		if ($fileProperties)
		{
			$fileId = $fileProperties['fileToken'] ?? null;
			if ($fileId)
			{
				$datasetUploaderController = new DatasetUploaderController();
				$uploader = new FileUploader($datasetUploaderController);
				$pendingFiles = $uploader->getUploader()->getPendingFiles([$fileId]);

				$pendingFile = $pendingFiles->get($fileId);
				if ($pendingFile && $pendingFile->isValid())
				{
					try
					{
						$fileData = \CFile::MakeFileArray($pendingFile->getFileId());
						if ($fileData && !empty($fileData['tmp_name']))
						{
							$resultFile['path'] = $fileData['tmp_name'];

							foreach ($fileProperties as $code => $value)
							{
								if (isset(self::$fileMap[$code]))
								{
									if ($code === 'firstLineHeader')
									{
										$value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
									}

									$resultFile[self::$fileMap[$code]] = $value;
								}
							}
						}
					}
					catch (\Exception)
					{
						$result->addError(
							new Error(
								Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_EMPTY_DATA_ERROR'),
								'EXCEPTION'
							)
						);
					}
				}
			}
		}

		if ($datasetProperties)
		{
			foreach ($datasetProperties as $code => $value)
			{
				if (isset(self::$datasetMap[$code]))
				{
					$resultDataset[self::$datasetMap[$code]] = $value;
				}
			}
		}

		if ($datasetFields)
		{
			foreach ($datasetFields as $field)
			{
				$tmpField = [];
				foreach ($field as $code => $value)
				{
					if (isset(self::$fieldsMap[$code]))
					{
						if ($code === 'visible')
						{
							$value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
						}

						$tmpField[self::$fieldsMap[$code]] = $value;
					}
				}

				if ($tmpField)
				{
					$resultDatasetFields[] = $tmpField;
				}
			}
		}

		if ($datasetSettings)
		{
			foreach ($datasetSettings as $code => $value)
			{
				$fieldType = FieldType::tryFrom($code);
				if ($fieldType)
				{
					if (empty($value))
					{
						$value = match ($fieldType) {
							FieldType::Date => ExternalSource\Const\Date::Ymd_dash->value,
							FieldType::DateTime => ExternalSource\Const\DateTime::Ymd_dash_His_colon->value,
							FieldType::Double => ExternalSource\Const\DoubleDelimiter::DOT->value,
							FieldType::Money => ExternalSource\Const\MoneyDelimiter::DOT->value,
						};
					}
					elseif (
						$fieldType === FieldType::Date
						|| $fieldType === FieldType::DateTime
					)
					{
						$value = ExternalSource\Const\DateTimeFormatConverter::iso8601ToPhp($value);
					}

					$resultDatasetSettings[] = [
						'TYPE' => $code,
						'FORMAT' => $value,
					];
				}
			}
		}

		$result->setData([
			'file' => $resultFile,
			'dataset' => $resultDataset,
			'fields' => $resultDatasetFields,
			'settings' => $resultDatasetSettings,
		]);

		return $result;
	}

	public function getEditUrlAction(int $id): ?string
	{
		$dataset = ExternalSource\DatasetManager::getById($id);
		if (!$dataset)
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_NOT_FOUND_ERROR'), 'EDIT_URL_ERROR'));

			return null;
		}

		$supersetIntegration = new ExternalSource\SupersetIntegration();
		$getDatasetUrlResult = $supersetIntegration->getDatasetUrl($dataset);
		if (!$getDatasetUrlResult->isSuccess())
		{
			$this->addError(new Error($getDatasetUrlResult->getError(), 'EDIT_URL_ERROR'));

			return null;
		}

		$editUrl = $getDatasetUrlResult->getData()['url'];

		$loginUrl = (new SupersetController(Integrator::getInstance()))->getLoginUrl();
		if ($loginUrl)
		{
			$url = new Uri($loginUrl);
			$url->addParams([
				'next' => $editUrl,
			]);

			return $url->getLocator();
		}

		return $editUrl;
	}

	private function getDefaultDatasetName(ExternalSource\Type $type): string
	{
		if ($type === ExternalSource\Type::Csv)
		{
			$code = $type->value . '_dataset';
		}
		else
		{
			$code = "external_{$type->value}_dataset";
		}

		$dataset = ExternalSource\Internal\ExternalDatasetTable::getRow([
			'select' => ['NAME'],
			'filter' => ['%=NAME' => $code . '%'],
			'order' => ['ID' => 'DESC'],
		]);
		if ($dataset)
		{
			$currentCode = $dataset['NAME'];
			preg_match_all('/\d+$/', $currentCode, $matches);
			$number = (int)($matches[0][0] ?? 0) + 1;
			$code .= "_$number";
		}

		return $code;
	}

	private function checkFile(ExternalSource\Type $type, array $file): Result
	{
		$result = new Result();

		$isFileEmptyResult = $this->checkIsFileEmpty($type, $file);
		if (!$isFileEmptyResult->isSuccess())
		{
			$result->addErrors($isFileEmptyResult->getErrors());

			return $result;
		}

		$reader = ExternalSource\FileReader\Factory::getReader($type, $file);

		$row = $reader->getHeaders();
		if (!$row)
		{
			$row = $reader->readAllRowsByOne()->current();
		}

		if (!$row)
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_EMPTY_DATA_ERROR'), 'EMPTY_DATA'));

			return $result;
		}

		$rowNumber = 0;
		$valuesCount = count($row);
		foreach ($reader->readAllRowsByOne() as $row)
		{
			$rowNumber++;
			if ($rowNumber > self::FILE_ROWS_LIMIT)
			{
				$result->addError(
					new Error(
						Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_DATASET_MAX_ROWS')
					)
				);

				break;
			}

			if ($valuesCount !== count($row))
			{
				$result->addError(
					new Error(
						Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_IMPORT_ERROR')
					)
				);

				break;
			}
		}

		return $result;
	}

	private function checkIsFileEmpty(ExternalSource\Type $type, array $file): Result
	{
		$result = new Result();

		$reader = ExternalSource\FileReader\Factory::getReader($type, $file);

		$row = $reader->readAllRowsByOne();
		if (!$row->current())
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_EMPTY_DATA_ERROR'), 'EMPTY_DATA'));
		}

		return $result;
	}
}
