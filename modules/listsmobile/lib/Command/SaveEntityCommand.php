<?php

namespace Bitrix\ListsMobile\Command;

use Bitrix\BizprocMobile\EntityEditor\Converter;
use Bitrix\Disk\Uf\Integration\DiskUploaderController;
use Bitrix\Lists\Api\Request\ServiceFactory\AddElementRequest;
use Bitrix\Lists\Api\Request\ServiceFactory\GetElementDetailInfoRequest;
use Bitrix\Lists\Api\Request\ServiceFactory\GetIBlockFieldsRequest;
use Bitrix\Lists\Api\Request\ServiceFactory\GetIBlockInfoRequest;
use Bitrix\Lists\Api\Request\ServiceFactory\UpdateElementRequest;
use Bitrix\Lists\Api\Service\ServiceFactory\ServiceFactory;
use Bitrix\Lists\Api\Service\WorkflowService;
use Bitrix\Listsmobile\UI\FileUploader\EntityFieldUploaderController;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Mobile\Command;
use Bitrix\UI\FileUploader\PendingFileCollection;
use Bitrix\UI\FileUploader\Uploader;

Loader::requireModule('lists');
Loader::requireModule('ui');

final class SaveEntityCommand extends Command
{
	private ServiceFactory $service;
	private array $data;
	private ?array $entityData = null;
	private int $modifiedBy;
	private int $entityId;
	private int $iBlockId;
	private int $sectionId;
	private ?int $timeToStart;

	private ?PendingFileCollection $pendingFileCollection = null;
	private array $parametersPendingFileCollection = [];

	public function __construct(ServiceFactory $service, array $entityData)
	{
		$this->service = $service;
		$this->data = $entityData;

		$this->entityId = (int)$entityData['ID'];
		$this->iBlockId = (int)$entityData['IBLOCK_ID'];
		$this->sectionId = (int)$entityData['IBLOCK_SECTION_ID'];
		$this->modifiedBy = (int)$entityData['MODIFIED_BY'];
		$this->timeToStart = isset($entityData['timeToStart']) ? (int)$entityData['timeToStart'] : null;
	}

	public function execute(): Result
	{
		$result = new Result();

		$preparedDataResult = $this->getPreparedData();
		if ($preparedDataResult->isSuccess())
		{
			$data = $preparedDataResult->getData()['data'];
			$wfParameters = $preparedDataResult->getData()['wfParameters'];

			$isSuccess = false;
			if ($this->entityId === 0)
			{
				$response = $this->create($data, $wfParameters);
				$newId = $response->getId();
				$this->entityId = (int)$newId;
				if ($newId !== null)
				{
					$isSuccess = true;
				}
			}
			else
			{
				$response = $this->update($data, $wfParameters);
				$isSuccess = $response->getIsSuccessElementUpdate();
			}

			if ($isSuccess)
			{
				$this->commitAddedFiles();
			}

			$result->addErrors($response->getErrors());
			if ($result->isSuccess())
			{
				$this->commitAddedParametersFiles();
			}
		}

		$result
			->addErrors($preparedDataResult->getErrors())
			->setData(['id' => $this->entityId])
		;

		return $result;
	}

	private function getPreparedData(): Result
	{
		$result = new Result();
		$data = [];
		$wfParameters = [];

		$iBlockFieldsRequest = new GetIBlockFieldsRequest($this->iBlockId, true, true, false);
		$iBlockFieldsResult = $this->service->getIBlockFields($iBlockFieldsRequest);
		$result->addErrors($iBlockFieldsResult->getErrors());
		if ($iBlockFieldsResult->isSuccess())
		{
			$originalData = $this->data;
			$fieldsData = $this->getPreparedFields($iBlockFieldsResult->getFields(), $originalData);
			$propsData = $this->getPreparedProps($iBlockFieldsResult->getProps(), $originalData);

			$data = array_merge($fieldsData, $propsData);
		}

		if ($result->isSuccess())
		{
			$wfParameters = $this->getPreparedWfParameters();
		}

		$result->setData([
			'data' => $data,
			'wfParameters' => $wfParameters,
		]);

		return $result;
	}

	private function getPreparedFields(array $fields, array $originalData): array
	{
		$data = [];
		foreach ($fields as $fieldId => $fieldProperty)
		{
			if (!array_key_exists($fieldId, $originalData))
			{
				continue;
			}

			$type = $fieldProperty['TYPE'];

			if (in_array($type, ['ACTIVE_FROM', 'DATE_CREATE', 'TIMESTAMP_X', 'ACTIVE_TO'], true))
			{
				$data[$fieldId] = $this->toWebDate($originalData[$fieldId]);

				continue;
			}

			if (in_array($type, ['PREVIEW_PICTURE', 'DETAIL_PICTURE'], true))
			{
				$value = $originalData[$fieldId];

				if (is_numeric($value))
				{
					continue; // do not update the value
				}

				$fileId = is_array($value) ? $this->toWebFile($value, $fieldId) : null;
				$file = $fileId ? $this->pendingFileCollection->getByFileId($fileId) : null;

				if ($file && $file->isValid())
				{
					$data[$fieldId] = \CIBlock::makeFileArray($fileId, false, null, ['allow_file_id' => true]);
				}

				continue;
			}

			$data[$fieldId] = $originalData[$fieldId];
		}

		return $data;
	}

	private function getPreparedProps(array $props, array $originalData): array
	{
		$data = [];
		foreach ($props as $propId => $propProperty)
		{
			if (!array_key_exists($propId, $originalData))
			{
				$data[$propId] = [];

				continue;
			}

			$type = $propProperty['TYPE'];
			$isMultiple = $propProperty['MULTIPLE'] === 'Y';

			$data[$propId] = [];

			$values = (array)$originalData[$propId];

			$isFileType = $type === 'F' || $type === 'S:DiskFile';
			if (($type === 'S:Money' || $isFileType) && !$isMultiple)
			{
				$values = [$values];
			}

			foreach ($values as $key => $value)
			{
				$isUseModifiedKey = true;
				$modifiedKey = isset($value['id']) && (!$isFileType) ? $value['id'] : 'n' . $key;
				$value = $value['value'] ?? $value;

				switch ($type)
				{
					case 'S:Date':
					case 'S:DateTime':
						$value = $this->toWebDate($value, $type === 'S:DateTime');
						break;
					case 'L':
					case 'S:employee':
					case 'E':
					case 'G':
						$isUseModifiedKey = false;
						break;
					case 'S:Money':
						$value = $this->toWebMoney($value);
						$isUseModifiedKey = false;
						break;
					case 'S:ECrm':
						$userTypeSettings = (
							isset($propProperty['USER_TYPE_SETTINGS']) && is_array($propProperty['USER_TYPE_SETTINGS'])
								? $propProperty['USER_TYPE_SETTINGS']
								: []
						);
						unset($userTypeSettings['VISIBLE']);
						$allowedEntityTypes = array_keys($userTypeSettings, 'Y');

						$value = $this->toWebECrm($value, count($allowedEntityTypes) === 1);
						break;
					case 'F':
						if (is_array($value) && isset($value['token']))
						{
							$value = $this->toWebFile($value, $propId);
						}
						else
						{
							$tmpValue = $this->toWebFile($value, $propId);
							$value = null;
							if (is_int($tmpValue))
							{
								$entityData = $this->getEntityData();
								if (isset($entityData[$propId]) && is_array($entityData[$propId]))
								{
									$foundKey = array_search($tmpValue, $entityData[$propId], false);
									if ($foundKey !== false)
									{
										$modifiedKey = $foundKey;
										$value = $tmpValue;
									}
								}
							}
						}
						break;
					case 'S:DiskFile':
						if (is_array($value))
						{
							$value = $this->toWebDiskFile($value);
						}
						else
						{
							$value = null;
						}
				}

				$data[$propId][$isUseModifiedKey ? $modifiedKey : $key] = $value;
			}
		}

		return $data;
	}

	private function getPreparedWfParameters(): array
	{
		if (!Loader::includeModule('bizprocmobile'))
		{
			return [];
		}

		$wfParameters = [];

		$workflowService = new WorkflowService(
			$this->service->getIBlockInfo(new GetIBlockInfoRequest($this->iBlockId, false))->getIBlock()
		);
		if ($workflowService->canUserStartWorkflow($this->modifiedBy, $this->entityId, $this->sectionId))
		{
			$states = $workflowService->getDocumentStates($workflowService->getComplexDocumentId($this->entityId));
			foreach ($states as $state)
			{
				$parameters = $state['TEMPLATE_PARAMETERS'] ?? [];
				if (!empty($parameters))
				{
					$templateId = $state['TEMPLATE_ID'];
					$wfParameters[$templateId] = [];

					$changedParameters = $this->changeParametersPropertyName($parameters, $templateId);
					$converter = $this->getParametersConverter($templateId, $changedParameters);
					$convertedValues = $converter->toWeb()->getConvertedValues();

					$wfParameters[$templateId] = $this->unChangeParametersValues($changedParameters, $convertedValues);
					$this->parametersPendingFileCollection[$templateId] = $converter->getPendingFiles();
				}
			}
		}

		return $wfParameters;
	}

	private function getParametersConverter($templateId, array $properties): Converter
	{
		$complexDocumentType = \BizprocDocument::generateDocumentComplexType(
			$this->service->getInnerIBlockTypeId(), $this->iBlockId
		);
		$complexDocumentId =
			$this->entityId !== 0
				? \BizprocDocument::getDocumentComplexId($this->service->getInnerIBlockTypeId(), $this->entityId)
				: null
		;

		$converter = new Converter($properties, $complexDocumentId, $this->data);
		$converter
			->setDocumentType($complexDocumentType)
			->setContext(
				Converter::CONTEXT_PARAMETERS,
				[
					'templateId' => $templateId,
					'signedDocument' => \CBPDocument::signParameters([
						$complexDocumentType,
						is_array($complexDocumentId) ? (string)$complexDocumentId[2] : '',
					])
				]
			);

		return $converter;
	}

	private function changeParametersPropertyName(array $parameters, $templateId): array
	{
		$changedProperties = [];
		foreach ($parameters as $key => $property)
		{
			$name = 'template_' . $templateId . '_' . $key;
			$property['realName'] = $key;
			$changedProperties[$name] = $property;
		}

		return $changedProperties;
	}

	private function unChangeParametersValues(array $changedParametersProperty, array $values): array
	{
		$unChangedValues = [];
		foreach ($values as $key => $value)
		{
			if (array_key_exists($key, $changedParametersProperty))
			{
				$realParameterName = $changedParametersProperty[$key]['realName'];
				$unChangedValues[$realParameterName] = $value;
			}
		}

		return $unChangedValues;
	}

	private function toWebDate($value, $useTime = true): ?string
	{
		if (!is_numeric($value))
		{
			return null;
		}

		return (
			$useTime
				? (string)DateTime::createFromTimestamp((int)$value)
				: (string)Date::createFromTimestamp((int)$value)
		);
	}

	private function toWebMoney($value): string
	{
		if (
			isset($value['currency'], $value['amount'])
			&& ($value['amount'] !== '' && $value['amount'] !== 'null')
			&& $value['currency'] !== ''
		)
		{
			return $value['amount'] . '|' . $value['currency'];
		}

		return '';
	}

	private function toWebFile($value, string $fieldId): ?int
	{
		if (is_array($value) && isset($value['token']))
		{
			$controller = new EntityFieldUploaderController([
				'elementId' => $this->entityId,
				'iBlockId' => $this->iBlockId,
				'fieldName' => $fieldId,
			]);
			$uploader = new Uploader($controller);
			$pendingFiles = $uploader->getPendingFiles([$value['token']]);
			$file = $pendingFiles->get($value['token']);

			if ($file && $file->isValid())
			{
				if (!$this->pendingFileCollection)
				{
					$this->pendingFileCollection = new PendingFileCollection();
				}

				$this->pendingFileCollection->add($file);

				return $file->getFileId();
			}

			return null;
		}

		if (is_array($value))
		{
			$value = current($value);
		}

		if (is_numeric($value) && $value > 0 && $this->entityId > 0)
		{
			return (int)$value;
		}

		return null;
	}

	private function toWebDiskFile($value)
	{
		if (Loader::includeModule('disk'))
		{
			$controller = new DiskUploaderController([]);

			if (isset($value['serverFileId']) || isset($value['id']))
			{
				$id = $value['serverFileId'] ?? $value['id'];

				$fileInfo = $controller::getFileInfo([$id]);
				if ($fileInfo)
				{
					return $id;
				}
			}
		}

		return null;
	}

	private function toWebECrm($value, bool $hasOneType): ?string
	{
		if (
			is_array($value)
			&& count($value) === 2
			&& isset($value[0], $value[1])
			&& Loader::includeModule('crm')
		)
		{
			$typeAbbr = null;
			$id = 0;

			if ($value[0] === 'dynamic_multiple')
			{
				$parts = explode(':', $value[1]);
				if (count($parts) === 2)
				{
					$typeAbbr = \CCrmOwnerTypeAbbr::ResolveByTypeID($parts[0]);
					$id = $parts[1];
				}
			}
			else
			{
				$typeAbbr = \CCrmOwnerTypeAbbr::ResolveByTypeName((string)$value[0]);
				$id = $value[1];
			}

			if (!empty($typeAbbr) && !empty($id))
			{
				return $hasOneType ? (string)$id : $typeAbbr . '_' . $id;
			}
		}

		return null;
	}

	private function commitAddedFiles(): void
	{
		$this->pendingFileCollection?->makePersistent();
	}

	private function commitAddedParametersFiles(): void
	{
		foreach ($this->parametersPendingFileCollection as $parameters)
		{
			foreach ($parameters as $collection)
			{
				$collection->makePersistent();
			}
		}
	}

	private function create(array $data, array $wfParameters): \Bitrix\Lists\Api\Response\ServiceFactory\AddElementResponse
	{
		$request = new AddElementRequest(
			$this->iBlockId,
			$this->sectionId,
			$data,
			$this->modifiedBy,
			true,
			true,
			$wfParameters,
			$this->timeToStart
		);

		return $this->service->addElement($request);
	}

	private function update(array $data, array $wfParameters)
	{
		$request = new UpdateElementRequest(
			$this->entityId,
			$this->iBlockId,
			$this->sectionId,
			$data,
			$this->modifiedBy,
			true,
			true,
			$wfParameters,
			$this->timeToStart,
		);

		return $this->service->updateElement($request);
	}

	private function getEntityData(): array
	{
		if ($this->entityData === null)
		{
			$elementInfoResponse = $this->service->getElementDetailInfo(
				new GetElementDetailInfoRequest(
					$this->iBlockId,
					$this->entityId,
					$this->sectionId,
					['PROPS'],
					false,
				)
			);
			$this->entityData = $elementInfoResponse->hasInfo() ? $elementInfoResponse->getInfo() : [];
		}

		return $this->entityData;
	}
}
