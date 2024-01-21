<?php

namespace Bitrix\ListsMobile\EntityEditor;

use Bitrix\Main\Loader;
use Bitrix\Mobile\UI\File;

class Helper
{
	private static array $cache = [];

	public static function getUserEntityList($userIds): array
	{
		$cacheKey = 'user';
		self::initCacheByKey($cacheKey);
		[$result, $toGet] = self::getCachedValues($cacheKey, (array)$userIds);
		$result = array_values($result);

		if ($toGet)
		{
			$userFields = ['ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'TITLE', 'EMAIL', 'PERSONAL_PHOTO'];
			$users = \CUser::GetList('id', 'asc', ['ID' => implode('|', $toGet)], ['FIELDS' => $userFields]);

			while ($user = $users->Fetch())
			{
				$fullName = \CUser::FormatName(\CSite::GetNameFormat(false), $user, true, false);
				$imageUrl = null;

				if ((int)$user['PERSONAL_PHOTO'] > 0)
				{
					$fileInfo = \CFile::ResizeImageGet(
						$user['PERSONAL_PHOTO'],
						['width' => 60, 'height' => 60],
						BX_RESIZE_IMAGE_EXACT
					);
					if (is_array($fileInfo) && isset($fileInfo['src']))
					{
						$imageUrl = $fileInfo['src'];
					}
				}

				$userId = (int)$user['ID'];
				self::setCachedValue($cacheKey, $userId, ['id' => $userId, 'title' => $fullName, 'imageUrl' => $imageUrl]);
				$result[] = self::getCachedValue($cacheKey, $userId);
			}
		}

		return $result;
	}

	public static function getIBlockElementEntityList($elementIds): array
	{
		$cacheKey = 'iblock-element';
		self::initCacheByKey($cacheKey);
		[$result, $toGet] = self::getCachedValues($cacheKey, (array)$elementIds);
		$result = array_values($result);

		if ($toGet && Loader::includeModule('iblock'))
		{
			$filter = [
				'ID' => $toGet,
				'CHECK_PERMISSIONS' => 'Y',
				'MIN_PERMISSION' => 'R',
			];
			$elements = \CIBlockElement::GetList([], $filter, false, false, ['ID', 'NAME']);
			while ($element = $elements->Fetch())
			{
				$elementId = (int)$element['ID'];
				self::setCachedValue($cacheKey, $elementId, ['id' => $elementId, 'title' => $element['NAME']]);
				$result[] = self::getCachedValue($cacheKey, $elementId);
			}
		}

		return $result;
	}

	public static function getIBlockSectionEntityList($sectionIds, int $iBlockId): array
	{
		$cacheKey = 'iblock-section';
		self::initCacheByKey($cacheKey);
		[$result, $toGet] = self::getCachedValues($cacheKey, (array)$sectionIds);
		$result = array_values($result);

		if ($toGet && Loader::includeModule('iblock'))
		{
			$sections = \CIBlockSection::GetTreeList(['IBLOCK_ID' => $iBlockId]);
			while ($section = $sections->GetNext())
			{
				$sectionId = (int)$section['ID'];
				self::setCachedValue($cacheKey, $sectionId, ['id' => $sectionId, 'title' => $section['~NAME']]);
				if (in_array($sectionId, $toGet, true))
				{
					$result[] = self::getCachedValue($cacheKey, $sectionId);
				}
			}
		}

		return $result;
	}

	private static function initCacheByKey(string $cacheKey): void
	{
		if (!isset(self::$cache[$cacheKey]))
		{
			self::$cache[$cacheKey] = [];
		}
	}
	private static function getCachedValues(string $cacheKey, array $ids): array
	{
		$cachedValues = self::$cache[$cacheKey] ?? [];

		$cached = [];
		$toGet = [];
		foreach ($ids as $id)
		{
			$id = (int)$id;
			if ($id > 0)
			{
				if (array_key_exists($id, $cachedValues))
				{
					$cached[$id] = $cachedValues[$id];
				}
				else
				{
					$toGet[] = $id;
				}
			}
		}

		return [$cached, $toGet];
	}

	private static function setCachedValue(string $cacheKey, $key, $value): void
	{
		self::$cache[$cacheKey][$key] = $value;
	}

	private static function getCachedValue(string $cacheKey, $key)
	{
		return self::$cache[$cacheKey][$key] ?? null;
	}

	public static function getCrmEntityList($values): array
	{
		static $entities = [];

		$result = [];
		if (Loader::includeModule('crm'))
		{
			$values = (array)$values;
			foreach ($values as $value)
			{
				[$typeId, $id] = explode(':', $value);
				if ($typeId && $id)
				{
					$cacheKey = $value;
					if (!array_key_exists($cacheKey, $entities))
					{
						$isDynamic = \CCrmOwnerType::isPossibleDynamicTypeId($typeId);

						$entities[$cacheKey] = [
							'id' => $isDynamic ? $typeId. ':' . $id : $id,
							'title' => \CCrmOwnerType::GetCaption($typeId, $id),
							'type' => $isDynamic ? 'dynamic_multiple' : mb_strtolower(\CCrmOwnerType::ResolveName($typeId)),
						];
					}

					if (!empty($entities[$cacheKey]['title']))
					{
						$result[] = $entities[$cacheKey];
					}
				}
			}
		}

		return $result;
	}

	public static function getFileInfo($value): array
	{
		$cacheKey = 'file';
		self::initCacheByKey($cacheKey);
		[$result, $toGet] = self::getCachedValues($cacheKey, (array)$value);

		if ($toGet && Loader::includeModule('mobile'))
		{
			foreach ($toGet as $id)
			{
				$fileInfo = File::loadWithPreview($id);
				if ($fileInfo)
				{
					self::setCachedValue($cacheKey, $id, $fileInfo->getInfo());
					$result[$id] = self::getCachedValue($cacheKey, $id);
				}
			}
		}

		return $result;
	}

	public static function getDiskFileInfo($value): ?array
	{
		$result = [];

		static $fileInfo = [];

		if ($value && Loader::includeModule('disk'))
		{
			$toGet = [];
			foreach ((array)$value as $fileId)
			{
				if (array_key_exists($fileId, $fileInfo))
				{
					$result[$fileId] = $fileInfo[$fileId];

					continue;
				}

				$toGet[] = $fileId;
			}

			if ($toGet)
			{
				$diskUploader = new \Bitrix\Disk\Uf\Integration\DiskUploaderController([]);
				$loadResults = $diskUploader->load($toGet);

				$toGetFileInfo = [];
				foreach ($loadResults as $loadResult)
				{
					if ($loadResult->isSuccess() && $loadResult->getFile() !== null)
					{
						$file = $loadResult->getFile();
						$toGetFileInfo[$file->getId()] = $file->getFileId();
					}
				}

				$mobileFileInfo = self::getFileInfo($toGetFileInfo);
				if ($mobileFileInfo)
				{
					foreach ($toGetFileInfo as $id => $fileId)
					{
						if (array_key_exists($fileId, $mobileFileInfo))
						{
							$result[$id] = $mobileFileInfo[$fileId];
							$result[$id]['id'] = $id;

							$fileInfo[$id] = $result[$id];
						}
					}
				}
			}
		}

		return $result ?: null;
	}
}
