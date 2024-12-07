<?php

namespace Bitrix\BizprocMobile\EntityEditor\Fields;

use Bitrix\Bizproc\FieldType;
use Bitrix\BizprocMobile\Fields\Iblock\DiskFile;
use Bitrix\Main\Loader;
use Bitrix\Mobile\UI\File;
use Bitrix\UI\FileUploader\PendingFileCollection;
use Bitrix\UI\FileUploader\UploaderController;

Loader::requireModule('ui');

class FileField extends BaseField
{
	protected bool $isDiskFile;
	protected ?array $configFileInfo = null;
	protected string $endpoint = '';
	protected array $controllerOptionNames = [];
	protected ?UploaderController $controller = null;

	public function __construct(array $property, mixed $value, array $documentType)
	{
		parent::__construct($property, $value, $documentType);

		$this->isDiskFile = $property['Type'] === 'S:DiskFile';
		if ($this->isDiskFile && $this->fieldTypeObject)
		{
			$this->fieldTypeObject->setTypeClass(DiskFile::class);
		}
	}

	public function setEndpoint(string $endpoint): void
	{
		$this->endpoint = $endpoint;
	}

	public function setControllerOptionNames(array $optionNames): void
	{
		$this->controllerOptionNames = $optionNames;
	}

	public function setController(UploaderController $controller): void
	{
		$this->controller = $controller;
	}

	public function getType(): string
	{
		return 'file';
	}

	public function getConfig(): array
	{
		return [
			'fileInfo' => $this->getConfigFileInfo(),
			'mediaType' => 'file',
			'controller' => ['endpoint' => $this->endpoint],
			'controllerOptionNames' => $this->controllerOptionNames,
		];
	}

	protected function convertToMobileType($value): ?int
	{
		if (is_numeric($value) && (int)$value > 0)
		{
			return (int)$value;
		}

		return null;
	}

	public function convertValueToMobile(): ?array
	{
		$fileInfo = $this->getConfigFileInfo();
		if ($fileInfo)
		{
			return array_values($fileInfo);
		}

		return null;
	}

	protected function convertToWebType($value): ?int
	{
		return $this->convertToMobileType($value);
	}

	public function convertValueToWeb(): array
	{
		$value = $this->value;
		if (is_array($value) && (isset($value['token']) || isset($value['id'])))
		{
			$value = [$value];
		}

		if ($this->controller && is_array($value))
		{
			$ids = array_column(
				array_filter(
					$value,
					static fn($singleValue) => is_array($singleValue) && is_numeric($singleValue['id'] ?? null)
				),
				'id'
			);

			$uploader = new \Bitrix\UI\FileUploader\Uploader($this->controller);
			$pendingFiles = $uploader->getPendingFiles(
				array_column(array_filter($value), 'token'),
			);

			$value = $pendingFiles->getFileIds();
			if (!$this->isDiskFile)
			{
				$value = $this->addDefaultIdsFromRequestIds($value, $ids);
			}

			if ($this->isDiskFile && $this->fieldTypeObject)
			{
				$value = $this->fieldTypeObject->internalizeValue(FieldType::VALUE_CONTEXT_JN_MOBILE, $value);
				if (!is_array($value))
				{
					$value = empty($value) ? [] : [$value];
				}

				if ($ids)
				{
					$defaultValue = (array)$this->property['Default'];
					foreach ($defaultValue as $singleValue)
					{
						$this->fieldTypeObject->setTypeClass(DiskFile::class);
						$fileId = $this->fieldTypeObject->convertValue($singleValue, \Bitrix\Bizproc\BaseType\File::class);
						$fileId = $this->convertToWebType(
							is_array($fileId) ? $fileId[array_key_first($fileId)] : $fileId
						);
						if ($fileId && in_array($fileId, $ids, false))
						{
							$value[] = $this->convertToWebType($singleValue);
						}
					}
				}
			}

			return [$value, $pendingFiles];
		}

		return [[], new PendingFileCollection()];
	}

	protected function getConfigFileInfo(): array
	{
		if ($this->configFileInfo === null)
		{
			$this->configFileInfo = $this->isDiskFile ? $this->getDiskFileInfo() : $this->getFileInfo();
		}

		return $this->configFileInfo;
	}

	protected function getFileInfo(array $value = null): array
	{
		$info = [];
		if ($value === null)
		{
			$value = (array)($this->value);
		}
		foreach ($value as $singleValue)
		{
			$id = $this->convertToMobileType($singleValue);
			if ($id)
			{
				$fileInfo = File::loadWithPreview($id);
				if ($fileInfo)
				{
					$info[$id] = $fileInfo->getInfo();
				}
			}
		}

		return $info;
	}

	protected function getDiskFileInfo(): array
	{
		$value = (array)$this->value;

		$diskIds = [];
		foreach ($value as $singleValue)
		{
			$id = $this->convertToMobileType($singleValue);
			if ($id)
			{
				$diskIds['n' . $id] = $id;
			}
		}

		$info = [];
		if ($diskIds && Loader::includeModule('disk'))
		{
			$diskUploader = new \Bitrix\Disk\Uf\Integration\DiskUploaderController([]);
			$loadResults = $diskUploader->load(array_keys($diskIds));
			foreach ($loadResults as $result)
			{
				if ($result->isSuccess())
				{
					$file = $result->getFile();
					if ($file)
					{
						$fileId = $file->getFileId();

						$fileInfo = $this->getFileInfo([$fileId]);
						$info[$diskIds[$file->getId()]] = $fileInfo[$fileId];
					}
				}
			}
		}

		return $info;
	}

	protected function addDefaultIdsFromRequestIds(array $value, array $ids): array
	{
		foreach ($ids as $id)
		{
			$id = $this->convertToWebType($id);
			if ($id && isset($this->property['Default']))
			{
				$defaultValue = (array)$this->property['Default'];
				if (in_array($id, $defaultValue, false))
				{
					$value[] = $id;
				}
			}
		}

		return $value;
	}
}
