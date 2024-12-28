<?php

namespace Bitrix\BIConnector\Controller\ExternalSource;

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Type\DateTime;
use Bitrix\BIConnector\ExternalSource;
use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Integration\UI\FileUploader;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\UI\FileUploaderController\DatasetUploaderController;

class Dataset extends Controller
{
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

	/**
	 * Adds new external dataset
	 *
	 * @param string $type
	 * @param array $fields
	 * @param int|null $sourceId
	 * @return array|null
	 */
	public function addAction(string $type, array $fields, ?int $sourceId = null):? array
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
			$importer = new FileImporter($addResultData['id'], $file);
			$importResult = $importer->import();
			if (!$importResult->isSuccess())
			{
				$this->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_IMPORT_ERROR'), 'IMPORT_ERROR'));

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
	 * @return bool|null
	 */
	public function updateAction(int $id, string $type, array $fields, ?int $sourceId = null): ?bool
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
			$importer = new FileImporter($id, $file);
			$importResult = $importer->reImport();
			if (!$importResult->isSuccess())
			{
				$this->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_IMPORT_ERROR'), 'IMPORT_ERROR'));

				return null;
			}
		}

		return true;
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
			$data = $viewer->getData();
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
				$fieldType = ExternalSource\FieldType::tryFrom($code);
				if ($fieldType)
				{
					if (empty($value))
					{
						$value = match ($fieldType) {
							ExternalSource\FieldType::Date => ExternalSource\Const\Date::Ymd_dot->value,
							ExternalSource\FieldType::DateTime => ExternalSource\Const\DateTime::Ymd_dot_His_colon->value,
							ExternalSource\FieldType::Double => ExternalSource\Const\DoubleDelimiter::DOT->value,
							ExternalSource\FieldType::Money => ExternalSource\Const\MoneyDelimiter::DOT->value,
						};
					}
					elseif (
						$fieldType === ExternalSource\FieldType::Date
						|| $fieldType === ExternalSource\FieldType::DateTime
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
		if ($type === ExternalSource\Type::Source1C)
		{
			$code = 'external_dataset';
		}
		else
		{
			$code = $type->value . '_dataset';
		}

		$dataset = ExternalSource\Internal\ExternalDatasetTable::getRow([
			'select' => ['NAME'],
			'filter' => ['%=NAME' => $code . '%'],
			'order' => ['ID' => 'DESC'],
		]);
		if ($dataset)
		{
			$currentCode = $dataset['NAME'];
			preg_match_all('/\d+/', $currentCode, $matches);
			$number = (int)($matches[0][0] ?? 0) + 1;
			$code .= "_$number";
		}

		return $code;
	}

	private function checkFile(ExternalSource\Type $type, array $file): Result
	{
		$result = new Result();

		$reader = ExternalSource\FileReader\Factory::getReader($type, $file);

		$row = $reader->readAllRowsByOne();
		if (!$row->current())
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_EXTERNAL_SOURCE_EMPTY_DATA_ERROR'), 'EMPTY_DATA'));

			return $result;
		}

		$rowNumber = 0;
		$valuesCount = count($row->current());
		foreach ($reader->readAllRowsByOne() as $row)
		{
			$rowNumber++;
			if ($rowNumber > 300000)
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
}
