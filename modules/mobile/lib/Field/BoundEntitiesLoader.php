<?php

namespace Bitrix\Mobile\Field;

use Bitrix\CatalogMobile\EntityEditor\StoreDocumentProvider;
use Bitrix\Crm\Service\Display\Field\CrmField;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Mobile\Field\Type\BaseField;
use Bitrix\Mobile\UI\File;

class BoundEntitiesLoader
{
	private const PATH_TO_USER_PROFILE = '/company/personal/user/#user_id#/';
	/**
	 * @param array $entities
	 * @return void
	 */
	public static function loadEntities(array $entities): void
	{
		$loadedEntities = [];
		foreach ($entities as $entityName => $entityInfos)
		{
			if ($entityName === 'file')
			{
				$entityIds = array_column($entityInfos, 'ids');
				$entityIds = array_merge(...$entityIds);
				$loadedEntities[$entityName] = File::loadBatch($entityIds);
			}
			elseif ($entityName === 'crm')
			{
				$loadedCrmEntities = [];
				foreach ($entityInfos as $entityInfo)
				{
					$preparedEntities = [];
					$field = $entityInfo['field']->getUserFieldInfo();
					$crmField = CrmField::createFromUserField($field['FIELD'], $field);
					$crmField->prepareLinkedEntities($preparedEntities, $entityInfo['field']->getValue(), 0, '');
					$preparedEntities = $preparedEntities['crm'];
					$crmEntities = [];
					$crmField->loadLinkedEntities($crmEntities, $preparedEntities);
					$crmEntities = $crmEntities['crm'];
					$loadedCrmEntities = array_replace_recursive($loadedCrmEntities, $crmEntities);
				}
				$loadedEntities[$entityName] = $loadedCrmEntities;
			}
			elseif ($entityName === 'iblock_element')
			{
				if (!Loader::includeModule('iblock'))
				{
					return;
				}

				$entityIds = array_column($entityInfos, 'ids');
				$entityIds = array_merge(...$entityIds);
				$iblockElements = [];

				foreach (array_chunk($entityIds, 500) as $pageIds)
				{
					$iterator = ElementTable::getList([
						'select' => [
							'ID',
							'IBLOCK_ID',
							'NAME',
						],
						'filter' => [
							'@ID' => $pageIds,
						],
					]);
					while ($row = $iterator->fetch())
					{
						$row['ID'] = (int)$row['ID'];
						$row['IBLOCK_ID'] = (int)$row['IBLOCK_ID'];
						$id = $row['ID'];
						$iblockElements[$id] = $row;
					}
					unset($row, $iterator);
				}

				$loadedEntities[$entityName] = $iblockElements;
			}
			elseif ($entityName === 'iblock_section')
			{
				if (!Loader::includeModule('iblock'))
				{
					return;
				}

				$entityIds = array_column($entityInfos, 'ids');
				$entityIds = array_merge(...$entityIds);
				$iblockElements = [];

				foreach (array_chunk($entityIds, 500) as $pageIds)
				{
					$iterator = SectionTable::getList([
						'select' => [
							'ID',
							'NAME',
						],
						'filter' => [
							'@ID' => $pageIds,
						],
					]);
					while ($row = $iterator->fetch())
					{
						$row['ID'] = (int)$row['ID'];
						$id = $row['ID'];
						$iblockElements[$id] = $row;
					}
					unset($row, $iterator);
				}

				$loadedEntities[$entityName] = $iblockElements;
			}
			elseif ($entityName === 'user')
			{
				$ids = array_column($entityInfos, 'ids');
				$ids = array_merge(...$ids);
				$userList = UserTable::getList([
					'select' => [
						'ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'TITLE', 'PERSONAL_PHOTO', 'WORK_POSITION', 'IS_REAL_USER',
					], 'filter' => [
						'=ID' => $ids,
					],
				]);

				$entries = [];
				while($userRaw = $userList->fetch())
				{
					$user = self::normalizeUser($userRaw);

					$entries[$user['ID']] = $user;
				}

				$loadedEntities[$entityName] = $entries;
			}
		}

		BoundEntitiesContainer::getInstance()->addBoundEntities($loadedEntities);
	}

	private static function normalizeUser(array $user): array
	{
		$user['ID'] = (int)$user['ID'];
		$user['FORMATTED_NAME'] = \CUser::FormatName(\CSite::GetNameFormat(false), $user, false, false);
		$user['SHOW_URL'] = \CComponentEngine::MakePathFromTemplate(
			self::PATH_TO_USER_PROFILE,
			['user_id' => $user['ID']]
		);
		$user['PHOTO_URL'] = null;
		if($user['PERSONAL_PHOTO'] > 0)
		{
			$photo = \CFile::ResizeImageGet(
				(int)$user['PERSONAL_PHOTO'],
				['width' => 60, 'height' => 60],
				BX_RESIZE_IMAGE_EXACT
			);
			if($photo)
			{
				$user['PHOTO_URL'] = $photo['src'];
			}
		}

		return $user;
	}
}
