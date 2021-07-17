<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Disk;
use Bitrix\Main;

class MobileIblockBizprocTypeDiskFileComponent extends CBitrixComponent
{
	private const ORIGINAL_POSTFIX = '_original';

	public function onPrepareComponentParams($arParams)
	{
		return $arParams;
	}

	public function executeComponent()
	{
		if (!Main\Loader::includeModule('disk'))
		{
			ShowError('Module "disk" is required.');
			return false;
		}

		$files = $this->getFiles();

		$this->arResult['inputName'] = $this->getInputName();
		$this->arResult['inputValue'] = array_values($files);

		$this->arResult['originalInputName'] = $this->getInputName() . self::ORIGINAL_POSTFIX;
		$this->arResult['originalInputValue'] = Main\Web\Json::encode(array_flip($files));

		$this->includeComponentTemplate();
	}

	protected function getFiles(): array
	{
		$value = (array)$this->arParams['INPUT_VALUE'];
		$ids = [];

		foreach ($value as $diskFileId)
		{
			if (!is_numeric($diskFileId))
			{
				continue;
			}

			$diskFile = Disk\File::getById($diskFileId);
			if ($diskFile)
			{
				$ids[$diskFile->getId()] = $diskFile->getFileId();
			}
		}

		return $ids;
	}

	protected function getInputName(): string
	{
		$name = (string)$this->arParams['INPUT_NAME'];

		if (substr($name, -2) === '[]')
		{
			$name = substr($name, 0, -2);
		}

		return $name;
	}

	public static function extractValues(string $fieldName, array $request): array
	{
		if (!Main\Loader::includeModule('disk'))
		{
			return [];
		}

		$result = [];
		$originalIds = self::extractOriginalValues($fieldName, $request);
		$deletedIds = self::extractDeletedValues($fieldName, $request);
		$toClear = [];

		$values = $request[$fieldName] ?? [];
		$values = (array)$values;

		foreach ($values as $value)
		{
			if (in_array($value, $deletedIds))
			{
				$toClear[] = $value;
			}
			elseif (isset($originalIds[$value]))
			{
				$result[] = Disk\Uf\FileUserType::NEW_FILE_PREFIX . $originalIds[$value];
				unset($originalIds[$value]);
			}
			else
			{
				$toClear[] = $value;
				$newId = self::uploadFileToDisk($value);
				if ($newId)
				{
					$result[] = Disk\Uf\FileUserType::NEW_FILE_PREFIX . $newId;
				}
			}
		}

		self::clearFiles(array_merge($toClear, array_keys($originalIds)));

		return $result;
	}

	private static function extractOriginalValues($fieldName, $request): array
	{
		$raw = $request[$fieldName . self::ORIGINAL_POSTFIX] ?? null;
		if ($raw)
		{
			$values = Main\Web\Json::decode($raw);
			if (is_array($values))
			{
				return $values;
			}
		}

		return [];
	}

	private static function extractDeletedValues($fieldName, $request): array
	{
		$values = $request[$fieldName . '_del'] ?? [];
		if (is_array($values))
		{
			return array_values($values);
		}

		return [];
	}

	private static function uploadFileToDisk(int $fileId): ?int
	{
		static $folder;
		$userId = Main\Engine\CurrentUser::get()->getId();

		if ($folder === null)
		{
			$storage = Disk\Driver::getInstance()->getStorageByUserId($userId);
			if ($storage)
			{
				$folder = $storage->getFolderForUploadedFiles();
				if ($folder)
				{
					$securityContext = $storage->getSecurityContext($userId);
					if(!$folder->canAdd($securityContext))
					{
						$folder = null;
					}
				}
			}
		}

		if (!$folder)
		{
			return null;
		}

		$file = \CFile::MakeFileArray($fileId);

		if (!$file)
		{
			return null;
		}

		$diskFile = $folder->uploadFile(
			$file,
			[
				'NAME' => $file['name'],
				'CREATED_BY' => $userId
			],
			[],
			true
		);

		if (!$diskFile)
		{
			return null;
		}

		return $diskFile->getId();
	}

	private static function clearFiles(array $ids)
	{
		foreach (array_unique($ids) as $id)
		{
			\CFile::delete($id);
		}
	}
}