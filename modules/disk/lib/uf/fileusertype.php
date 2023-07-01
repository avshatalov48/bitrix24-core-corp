<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Search\ContentManager;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\SystemUser;
use Bitrix\Disk\User;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\Internals\AttachedViewTypeTable;

Loc::loadMessages(__FILE__);

final class FileUserType
{
	const ERROR_COULD_NOT_FIND_ATTACHED_OBJECT = 'DISK_FUT_22002';

	const USER_TYPE_ID = 'disk_file';
	const TYPE_NEW_OBJECT = 2;
	const TYPE_ALREADY_ATTACHED = 3;
	const NEW_FILE_PREFIX = 'n';

	/** @var File[]  */
	protected static $loadedFiles = [];
	/** @var array */
	private static $valuesAllowEditByEntityType = [];

	public static function getUserTypeDescription()
	{
		return [
			"USER_TYPE_ID" => static::USER_TYPE_ID,
			"CLASS_NAME" => __CLASS__,
			"DESCRIPTION" => Loc::getMessage('DISK_FILE_USER_TYPE_NAME'),
			"BASE_TYPE" => "int",
			"TAG" => [
				"DISK FILE ID",
				"DOCUMENT ID"
			]
		];
	}

	public static function getDBColumnType($userField)
	{
		return 'int(11)';
	}

	public static function prepareSettings($userField)
	{
		if (!is_array($userField))
		{
			$userField = [];
		}

		$iblockId = null;
		$sectionId = null;
		if (isset($userField["SETTINGS"]["IBLOCK_ID"]))
		{
			$iblockId = (int)$userField["SETTINGS"]["IBLOCK_ID"];
		}
		if (isset($userField["SETTINGS"]["SECTION_ID"]))
		{
			$sectionId = (int)$userField["SETTINGS"]["SECTION_ID"];
		}

		return [
			"IBLOCK_ID" => $iblockId,
			"SECTION_ID" => $sectionId,
			"UF_TO_SAVE_ALLOW_EDIT" => $userField["SETTINGS"]["UF_TO_SAVE_ALLOW_EDIT"] ?? false,
		];
	}

	public static function getSettingsHTML($userField, $htmlControl, $varsFromForm)
	{
		return "&nbsp;";
	}

	public static function getEditFormHTML($userField, $htmlControl)
	{
		$html = '';
		$values = $userField['VALUE'];
		if (!is_array($values))
		{
			$values = [$userField['VALUE']];
		}

		$urlManager = Driver::getInstance()->getUrlManager();
		foreach ($values as $value)
		{
			if (!$value)
			{
				continue;
			}

			[$type, $realValue] = self::detectType($value);
			if ($type === self::TYPE_ALREADY_ATTACHED)
			{
				$attachedObject = AttachedObject::loadById($realValue, ['OBJECT']);
				$file = $attachedObject->getObject();
				if (!$attachedObject || !$file)
				{
					continue;
				}

				$name = htmlspecialcharsbx($file->getName());
				$size = \CFile::formatSize($file->getSize());

				$html .= '<br/><a href="' .
					$urlManager->getUrlUfController('download', ['attachedId' => $realValue])
				. '">' . $name . ' (' . $size .  ')</a>';
			}
		}

		return $html;
	}

	public static function getFilterHTML($userField, $htmlControl)
	{
		return '&nbsp;';
	}

	public static function getAdminListViewHTML($userField, $htmlControl)
	{
		return "&nbsp;";
	}

	public static function getAdminListEditHTML($userField, $htmlControl)
	{
		return "&nbsp;";
	}

	public static function getAdminListEditHTMLMulty($userField, $htmlControl)
	{
		return "&nbsp;";
	}

	public static function onSearchIndex($userField)
	{
		$values = $userField['VALUE'];
		if (!is_array($values))
		{
			$values = [$userField['VALUE']];
		}

		$searchData = [];
		$fileIdsForLoad = [];
		$attachedIdsForLoad = [];
		$contentManager = new ContentManager();

		foreach ($values as $value)
		{
			[$type, $realValue] = self::detectType($value);
			if ($type === self::TYPE_NEW_OBJECT)
			{
				if (self::isLoadedFile($realValue))
				{
					$searchData[] =
						$contentManager->getFileContentFromIndex(self::getFileById($realValue))?:
						$contentManager->getFileContent(self::getFileById($realValue))
					;
				}
				else
				{
					$fileIdsForLoad[] = $realValue;
				}
			}
			else
			{
				$attachedIdsForLoad[] = $realValue;
			}
		}

		if ($attachedIdsForLoad)
		{
			$attachedObjects = AttachedObject::getModelList([
				'with' => ['OBJECT'],
				'filter' => [
					'ID' => $attachedIdsForLoad
				],
			]);
			foreach ($attachedObjects as $attachedObject)
			{
				$searchData[] =
					$contentManager->getFileContentFromIndex($attachedObject->getObject())?:
					$contentManager->getObjectContent($attachedObject->getObject())
				;
			}
		}

		if ($fileIdsForLoad)
		{
			$files = File::getModelList([
				'filter' => [
					'ID' => $fileIdsForLoad
				],
			]);
			foreach ($files as $file)
			{
				$searchData[] =
					$contentManager->getFileContentFromIndex($file)?:
					$contentManager->getObjectContent($file)
				;
			}
		}

		return implode("\r\n", $searchData);
	}

	public static function onBeforeSaveAll($userField, $values, $userId = false)
	{
		if (!is_array($values))
		{
			$values = [];
		}

		if ($values)
		{
			static $alreadyRunDetach = [];
			if (!isset($alreadyRunDetach[$userField['FIELD_NAME'] . '|' . $userField['ENTITY_VALUE_ID']]))
			{
				$alreadyRunDetach[$userField['FIELD_NAME'] . '|' . $userField['ENTITY_VALUE_ID']] = true;
				if ($userField['VALUE'])
				{
					$alreadyExistsValues = $userField['VALUE'];
					if (!is_array($alreadyExistsValues))
					{
						$alreadyExistsValues = [$userField['VALUE']];
					}

					$needToDetach = array_diff($alreadyExistsValues, $values);
					AttachedObject::detachByFilter(['ID' => $needToDetach]);
				}
			}
		}

		$valuesToInsert = [];
		foreach($values as $value)
		{
			if (!empty($value))
			{
				$valuesToInsert[] = (int)self::onBeforeSave($userField, $value, $userId);
			}
		}

		$userFieldManager = Driver::getInstance()->getUserFieldManager();

		if ($templateType = Application::getInstance()->getContext()->getRequest()->getPost(
			$userFieldManager->getInputNameForTemplateView($userField['ENTITY_ID'])
		))
		{
			self::setTemplateType([
				'ENTITY_ID' => $userField['ENTITY_ID'],
				'ENTITY_VALUE_ID' => $userField['ENTITY_VALUE_ID'],
				'VALUE' =>  ($templateType === 'grid' ? 'grid' : 'gallery')
			]);
		}

		return $valuesToInsert;
	}

	public static function onBeforeSave($userField, $value, $userId = false)
	{
		[$type, $realValue] = self::detectType($value);

		if (empty($value))
		{
			$alreadyExistsValues = $userField['VALUE'];
			if (!is_array($alreadyExistsValues))
			{
				$alreadyExistsValues = array($userField['VALUE']);
			}
			AttachedObject::detachByFilter(array('ID' => $alreadyExistsValues));

			return $value;
		}

		if ($type === self::TYPE_NEW_OBJECT)
		{
			$fileModel = self::getFileById($realValue);
			if (!$fileModel || !$fileModel->getStorage())
			{
				return '';
			}

			if ($userId === false)
			{
				$securityContext = $fileModel->getStorage()->getCurrentUserSecurityContext();
			}
			else
			{
				$securityContext = $fileModel->getStorage()->getSecurityContext($userId);
			}

			$canUpdate = $fileModel->canUpdate($securityContext);
			$attachedModel = $fileModel->attachToEntity(
				[
					'id' => $userField['VALUE_ID'],
					'type' => $userField['ENTITY_ID'],
				],
				[
					'allowEdit' => $canUpdate,
					'isEditable' => ($canUpdate && (int)self::getValueForAllowEdit($userField)),
					'createdBy' => $userId === false? self::getActivityUserId() : $userId,
				]
			);

			if (!$attachedModel)
			{
				return '';
			}

			return $attachedModel->getId();
		}

		return $realValue;
	}

	/**
	 * Prepares values before copying.
	 *
	 * @param $userField
	 * @param int $newEntityId List old attached id.
	 * @param array $attachedIds List new attached id.
	 * @param object $entityObject Entity object for update entity.
	 * @param bool $userId
	 * @return array List new attached id to add.
	 */
	public static function onBeforeCopy(array $userField, int $newEntityId, $attachedIds, $entityObject, $userId = false)
	{
		$attachedIds = $attachedIds ?: [];
		if (!$attachedIds)
		{
			return [];
		}

		$userId = (($userId === false) ? self::getActivityUserId() : $userId);
		$userFieldManager = Driver::getInstance()->getUserFieldManager();

		return $userFieldManager->cloneUfValuesFromAttachedObject($attachedIds, $userId);
	}

	/**
	 * Performs the necessary actions after copying. For example: updates attached id to text.
	 *
	 * @param $userField
	 * @param int $entityId Id copied entity.
	 * @param array $attachedIds List new attached id.
	 * @param object $entityObject Entity object for update entity.
	 * @param bool $userId
	 */
	public static function onAfterCopy(array $userField, int $entityId, $attachedIds, $entityObject, $userId = false)
	{
		$attachedIds = $attachedIds ?: [];
		if (is_callable([$entityObject, "updateAttachedIdsInText"]))
		{
			$entityObject->updateAttachedIdsInText($entityId, $attachedIds, [FileUserType::class, "updateText"]);
		}
	}

	public static function onDelete($userField, $value)
	{
		[$type, $realValue] = self::detectType($value);
		if ($type !== self::TYPE_ALREADY_ATTACHED)
		{
			return;
		}

		if (!$realValue)
		{
			return;
		}

		$attachedModel = AttachedObject::loadById($realValue, array('OBJECT'));
		if (!$attachedModel)
		{
			return;
		}

		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		if (!$userFieldManager->belongsToEntity($attachedModel, $userField['ENTITY_ID'], $userField['ENTITY_VALUE_ID']))
		{
			return;
		}

		$attachedModel->delete();
	}

	public static function getPublicViewHTML($userField, $id, $params = "", $settings = array(), $matches)
	{
		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		$res = (is_array($matches) && is_string($matches[0]) ? $matches[0] : '');
		[$type, $realValue] = self::detectType($id);

		if ($type == self::TYPE_NEW_OBJECT || (is_array($matches) && $matches[1] == "DOCUMENT ID"))
		{
			$userFieldManager->loadBatchAttachedObject($userField["VALUE"]);

			$originalId = $id;
			$id = false;
			foreach ($userField["VALUE"] as $attachedObjectId)
			{
				if (!$userFieldManager->isLoadedAttachedObject($attachedObjectId))
				{
					continue;
				}

				$fileObject = $userFieldManager->getAttachedObjectById($attachedObjectId)->getFile();
				if (!$fileObject)
				{
					continue;
				}
				if ($type === self::TYPE_NEW_OBJECT && $fileObject->getId() == $realValue)
				{
					$id = $attachedObjectId;
					break;
				}
				elseif ($matches[1] === "DOCUMENT ID" && $fileObject->getXmlId() == $originalId)
				{
					$id = $attachedObjectId;
					break;
				}
			}
		}

		if ($id > 0)
		{
			$userField["VALUE"] = array_intersect($userField["VALUE"], array($id));
			$maxSize = array();
			if (is_array($settings) && !empty($settings) && array_key_exists("imageWidth", $settings) && array_key_exists("imageHeight", $settings))
				$maxSize = array("width" => $settings["imageWidth"], "height" => $settings["imageHeight"]);
			$size = array();
			if ($params != '' && is_string($params) && preg_match_all("/(width|height)=(\d+)/is", $params, $matches))
				$size = array_combine($matches[1], $matches[2]);
			ob_start();

			$newParams = $settings + array(
				"arUserField" => $userField,
				"INLINE" => "Y",
				"LAZYLOAD" => (isset($settings["LAZYLOAD"]) && $settings["LAZYLOAD"] == "Y" ? "Y" : "N"),
				"MAX_SIZE" => $maxSize,
				"SIZE" => array($id => $size),
				"TEMPLATE" => $settings["TEMPLATE"]
			);

			$newResult = array("VALUE" => array($id));
			if ($newParams['INLINE'] === 'Y')
			{
				if (
					isset($newParams['TEMPLATE'])
					&& $newParams['TEMPLATE'] === 'mobile'
				)
				{
					$newParams['MOBILE'] = 'Y';
				}

				$userFieldManager->showInlineView(
					$newParams,
					$newResult,
					null
				);
			}
			else
			{
				$userFieldManager->showView(
					$newParams,
					$newResult,
					null
				);
			}
			$res = ob_get_clean();
		}
		return $res;
	}


	/**
	 * Detect: this is already exists attachedObject or new object
	 * @param $value
	 * @return array
	 */
	public static function detectType($value)
	{
		if (is_string($value) && mb_substr($value, 0, 1) == self::NEW_FILE_PREFIX)
		{
			return [self::TYPE_NEW_OBJECT, mb_substr($value, 1)];
		}

		return [self::TYPE_ALREADY_ATTACHED, (int)$value];
	}

	/**
	 * @param      $userField
	 * @param      $value
	 * @param bool $userId False means current user id.
	 * @return array
	 */
	public static function checkFields($userField, $value, $userId = false)
	{
		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		$errors = [];

		[$type, $realValue] = self::detectType($value);

		if ($type === self::TYPE_ALREADY_ATTACHED)
		{
			$attachedModel = $userFieldManager->getAttachedObjectById($realValue);
			if (!$attachedModel)
			{
				$errors[] = [
					"id" => $userField["FIELD_NAME"],
					"text" => Loc::getMessage('DISK_FILE_USER_TYPE_ERROR_COULD_NOT_FIND_FILE'),
				];

				return $errors;
			}
			[$connectorClass, $moduleId] = $userFieldManager->getConnectorDataByEntityType($userField['ENTITY_ID']);

			if(
				!$userFieldManager->belongsToEntity($attachedModel, $userField['ENTITY_ID'], $userField['ENTITY_VALUE_ID']) &&
				!(
					is_subclass_of($connectorClass, 'Bitrix\Disk\Uf\ISupportForeignConnector') ||
					in_array('Bitrix\Disk\Uf\ISupportForeignConnector', class_implements($connectorClass)) //5.3.9
				)
			)
			{
				$errors[] = [
					"id" => $userField["FIELD_NAME"],
					"text" => Loc::getMessage('DISK_FILE_USER_TYPE_ERROR_COULD_NOT_FIND_FILE'),
				];

				return $errors;
			}
		}
		else
		{
			if ($realValue <= 0)
			{
				$errors[] = [
					"id" => $userField["FIELD_NAME"],
					"text" => Loc::getMessage('DISK_FILE_USER_TYPE_ERROR_INVALID_VALUE'),
				];

				return $errors;
			}

			$fileModel = self::getFileById($realValue);
			if (!$fileModel || !$fileModel->getStorage())
			{
				$errors[] = [
					"id" => $userField["FIELD_NAME"],
					"text" => Loc::getMessage('DISK_FILE_USER_TYPE_ERROR_COULD_NOT_FIND_FILE'),
				];

				return $errors;
			}

			if ($userId === false)
			{
				$securityContext = $fileModel->getStorage()->getCurrentUserSecurityContext();
			}
			else
			{
				$securityContext = $fileModel->getStorage()->getSecurityContext($userId);
			}

			if (!$fileModel->canRead($securityContext))
			{
				$errors[] = [
					"id" => $userField["FIELD_NAME"],
					"text" => Loc::getMessage('DISK_FILE_USER_TYPE_ERROR_BAD_RIGHTS'),
				];

				return $errors;
			}
		}

		return $errors;
	}

	/**
	 * @param $id
	 * @return File|null
	 */
	protected static function getFileById($id)
	{
		if (!isset(self::$loadedFiles[$id]))
		{
			self::$loadedFiles[$id] = File::loadById($id, ['STORAGE']);
		}

		return self::$loadedFiles[$id];
	}

	protected static function isLoadedFile($id)
	{
		return isset(self::$loadedFiles[$id]);
	}

	private static function getActivityUserId()
	{
		global $USER;
		if ($USER && $USER instanceof \CUser)
		{
			$userId = User::resolveUserId($USER);
			if ($userId !== null)
			{
				return $userId;
			}
		}

		return SystemUser::SYSTEM_USER_ID;
	}

	/**
	 * Sets value which allow/disallow editing file by another user.
	 * In general UI disk.uf.file renders checkbox (@see \Bitrix\Disk\Uf\UserFieldManager::getInputNameForAllowEditByEntityType),
	 * but here you can set value directly.
	 *
	 * @param string $entity Entity name.
	 * @param bool  $value Value.
	 * @internal
	 * @return void
	 */
	public static function setValueForAllowEdit($entity, $value)
	{
		self::$valuesAllowEditByEntityType[$entity] = (bool)$value;
	}

	private static function getValueForAllowEdit(array $userField)
	{
		if (isset(self::$valuesAllowEditByEntityType[$userField['ENTITY_ID']]))
		{
			return self::$valuesAllowEditByEntityType[$userField['ENTITY_ID']];
		}

		$userFieldManager = Driver::getInstance()->getUserFieldManager();

		return Application::getInstance()->getContext()->getRequest()->getPost(
			$userFieldManager->getInputNameForAllowEditByEntityType($userField['ENTITY_ID'])
		);
	}

	public static function getItemsInfo($itemsList)
	{
		$objects = [];
		$itemsIds = [
			'objects'  => [],
			'attached' => [],
		];

		foreach ($itemsList as $k => $item)
		{
			if (is_scalar($item))
			{
				$itemsList[$k] = $item = trim($item);

				if (preg_match(sprintf('/^(%s)?(\d+)$/', preg_quote(self::NEW_FILE_PREFIX, '/')), $item, $matches))
				{
					$itemType = $matches[1] ? 'objects' : 'attached';
					$itemsIds[$itemType][$matches[2]] = $matches[0];
				}
			}
			else if ($item instanceof BaseObject)
			{
				$objects[$k] = $item;
			}
			else if ($item instanceof AttachedObject)
			{
				$objects[$k] = $item->getObject();
			}
		}

		if (!empty($itemsIds['objects']))
		{
			$filter = array('@ID' => array_keys($itemsIds['objects']));
			foreach (BaseObject::getModelList(array('filter' => $filter)) as $object)
				$objects[$itemsIds['objects'][$object->getId()]] = $object;
		}

		if (!empty($itemsIds['attached']))
		{
			$diskUfManager = Driver::getInstance()->getUserFieldManager();
			$diskUfManager->loadBatchAttachedObject($itemsIds['attached']);
			foreach ($itemsIds['attached'] as $attachedId)
			{
				if ($attachedObject = $diskUfManager->getAttachedObjectById($attachedId))
					$objects[$attachedId] = $attachedObject->getObject();
			}
		}

		$urlManager = Driver::getInstance()->getUrlManager();
		foreach ($itemsList as $k => $item)
		{
			$key = $k;

			if (is_scalar($item))
			{
				$itemId = $item;
				$key    = $item;
			}
			else if ($item instanceof BaseObject)
			{
				$itemId = sprintf('%s%u', self::NEW_FILE_PREFIX, $item->getId());
			}
			else if ($item instanceof AttachedObject)
			{
				$itemId = $item->getId();
			}

			if (!array_key_exists($key, $objects))
			{
				unset($itemsList[$k]);
				continue;
			}

			$item = $objects[$key];
			$itemsList[$k] = array(
				'id'            => $itemId,
				'attachId'      => $itemId,
				'fileId'        => $item->getId(),
				'originalId'    => $item->getId(),
				'name'          => $item->getName(),
				'type'          => $item instanceof File ? 'file' : 'folder',
				'size'          => '',
				'sizeInt'       => '',
				'modifyBy'      => $item->getUpdateUser()->getFormattedName(),
				'modifyDate'    => $item->getUpdateTime()->format('d.m.Y'),
				'modifyDateInt' => $item->getUpdateTime()->getTimestamp(),
			);

			if ($item instanceof File)
			{
				$itemsList[$k] = array_merge(
					$itemsList[$k],
					array(
						'ext'      => $item->getExtension(),
						'size'     => \CFile::formatSize($item->getSize()),
						'sizeInt'  => $item->getSize(),
						'storage'  => sprintf(
							'%s / %s',
							$item->getStorage()->getProxyType()->getTitleForCurrentUser(),
							$item->getParent()->getName()
						),
					)
				);

				if (\Bitrix\Disk\TypeFile::isImage($item))
					$itemsList[$k]['previewUrl'] = $urlManager->getUrlForShowFile($item);

				if ($fileType = $item->getView()->getEditorTypeFile())
					$itemsList[$k]['fileType'] = $fileType;
			}
		}

		return $itemsList;
	}

	/**
	 * Update entity text.
	 *
	 * @param $text
	 * @param string $entity String entity id.
	 * @param string|int $entityId Entity id.
	 * @param string $fieldName UF Field name of entity.
	 * @param array $filesIdToUpdate List files ids.
	 * @return string
	 */
	public static function updateText($text, $entity, $entityId, $fieldName, array $filesIdToUpdate)
	{
		$currentRelations = self::getCurrentRelationAttachedFiles($entity, $entityId, $fieldName);
		$relationsToReplace = self::getRelationToReplaceDescription($currentRelations, $filesIdToUpdate);

		$text = preg_replace_callback(
			"/\[DISK FILE ID\s*=\s*([^\]]*)\]/is",
			function ($matches) use ($relationsToReplace)
			{
				if ($matches[1])
				{
					if (array_key_exists($matches[1], $relationsToReplace))
					{
						return "[DISK FILE ID=".$relationsToReplace[$matches[1]]."]";
					}
					else
					{
						return "";
					}
				}

				return "";
			},
			$text
		);

		return $text;
	}

	private static function getCurrentRelationAttachedFiles($entity, $entityId, $fieldName)
	{
		$currentRelations = [];
		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		$attachedObjects = $userFieldManager->getAttachedObjectByEntity($entity, $entityId, $fieldName);
		foreach ($attachedObjects as $attachedObject)
		{
			$currentRelations[$attachedObject->getObjectId()] = $attachedObject->getId();
		}

		return $currentRelations;
	}

	private static function getRelationToReplaceDescription(array $currentRelations, array $filesIdToUpdate)
	{
		$relationsToReplace = [];

		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		foreach ($filesIdToUpdate as $attachedId => $newFileId)
		{
			[$type, $newFileId] = FileUserType::detectType($newFileId);
			if (array_key_exists($newFileId, $currentRelations))
			{
				$relationsToReplace[$attachedId] = $currentRelations[$newFileId];
				$attachedObject = $userFieldManager->getAttachedObjectById($attachedId);
				if ($attachedObject)
				{
					$relationsToReplace[FileUserType::NEW_FILE_PREFIX.$attachedObject->getObjectId()] = $currentRelations[$newFileId];
				}
			}
		}

		return $relationsToReplace;
	}

	/**
	 * @param array $params
	 */
	public static function setTemplateType(array $params = [])
	{
		if (
			!empty($params['ENTITY_ID'])
			&& !empty($params['ENTITY_VALUE_ID'])
			&& isset($params['VALUE'])
		)
		{
			AttachedViewTypeTable::set([
				'ENTITY_TYPE' => $params['ENTITY_ID'],
				'ENTITY_ID' => $params['ENTITY_VALUE_ID'],
				'VALUE' => $params['VALUE'],
			]);
		}
	}

	/**
	 * @param array $params
	 * @return string
	 */
	public static function getTemplateType($params)
	{
		$params = array_change_key_case($params, CASE_UPPER);
		$result = '';
		if (isset($params['GRID']) && $params['GRID'] == 'Y')
		{
			$result = 'grid';
		}

		if (
			!empty($params['ARUSERFIELD'])
			&& !empty($params['ARUSERFIELD']['ENTITY_ID'])
			&& !empty($params['ARUSERFIELD']['ENTITY_VALUE_ID'])
		)
		{
			$res = AttachedViewTypeTable::getList([
				'filter' => [
					'=ENTITY_TYPE' => $params['ARUSERFIELD']['ENTITY_ID'],
					'ENTITY_ID' => $params['ARUSERFIELD']['ENTITY_VALUE_ID']
				],
				'select' => [ 'VALUE' ]
			]);
			if ($paramFields = $res->fetch())
			{
				$result = $paramFields['VALUE'];
			}
		}

		return $result;
	}
}
