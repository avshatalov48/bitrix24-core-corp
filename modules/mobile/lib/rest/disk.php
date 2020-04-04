<?php

namespace Bitrix\Mobile\Rest;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\FolderTable;
use Bitrix\Disk\TypeFile;
use Bitrix\Main\Loader;

class Disk extends \IRestService
{
	public static function getMethods()
	{
		return [
			'mobile.disk.folder.getchildren' => ['callback' => [__CLASS__, 'get'], 'options' => ['private' => false]],
			'mobile.disk.getattachmentsdata' => ['callback' => [__CLASS__, 'getAttachmentsData'], 'options' => ['private' => false]],
		];
	}

	public static function get($params, $offset, \CRestServer $server)
	{
		$types = ["user", "group", "common"];
		$type = "user";
		$entityId = $params["entityId"];
		$result = null;
		$folderId = null;

		if (intval($params["folderId"]))
		{
			$folderId = intval($params["folderId"]);
		}

		if ($params["type"] && in_array($params["type"], $types))
		{
			$type = $params["type"];
		}

		$parameters = [
			"filter" => $params["filter"],
			'limit' => 50,
			'count_total' => true,
			'offset' => $offset > 0 ? $offset : 0
		];

		if (is_array($params["filter"]))
		{
			$parameters["filter"] = $params["filter"];
		}

		$parameters['filter']['DELETED_TYPE'] = \Bitrix\Disk\Internals\ObjectTable::DELETED_TYPE_NONE;
		if ($params["order"])
		{
			$parameters["order"] = $params["order"];
		}

		if (Loader::includeModule("disk"))
		{
			$storage = null;
			if ($type == "user")
			{
				$storage = Driver::getInstance()->getStorageByUserId($entityId);
			}
			else
			{
				if ($type == "common")
				{
					$storage = Driver::getInstance()->getStorageByCommonId($entityId);
				}
				else
				{
					if ($type == "group")
					{
						$storage = Driver::getInstance()->getStorageByGroupId($entityId);
					}
				}
			}

			if ($storage)
			{
				$targetFolderId = $storage->getRootObjectId();
				if ($folderId)
				{
					$targetFolderId = $folderId;
				}
				$securityContext = $storage->getCurrentUserSecurityContext();
				$parameters = Driver::getInstance()->getRightsManager()->addRightsCheck($securityContext, $parameters, ['ID', 'CREATED_BY']);

				$folder = Folder::getById($targetFolderId);
				if($folder)
				{
					$childrenRows = FolderTable::getChildren($folder->getRealObjectId(), $parameters);
					$children = [];
					foreach ($childrenRows as $childrenRow)
					{
						$children[] = \Bitrix\Disk\BaseObject::buildFromArray($childrenRow);
					}

					$items = array_map(function (\Bitrix\Disk\BaseObject $item) {
						$arrayData = $item->toArray();
						$arrayData["CREATE_TIME"] = \CRestUtil::ConvertDateTime($arrayData["CREATE_TIME"]);
						$arrayData["UPDATE_TIME"] = \CRestUtil::ConvertDateTime($arrayData["UPDATE_TIME"]);
						$arrayData["SYNC_UPDATE_TIME"] = \CRestUtil::ConvertDateTime($arrayData["SYNC_UPDATE_TIME"]);
						unset($arrayData["PARENT"]);
						$arrayData["TYPE"] = $arrayData["TYPE"] == "2" ? "folder" : "file";
						if($arrayData["TYPE"] == "file")
							$arrayData["PREVIEW_URL"] = \Bitrix\Main\Engine\UrlManager::getInstance()->create('disk.api.file.showImage', [
								'fileId' => $arrayData["ID"],
								'signature' => \Bitrix\Disk\Security\ParameterSigner::getImageSignature($arrayData["ID"], 400, 400),
								'width' => 400,
								'height' => 400,
							])->getUri();

						return $arrayData;
					}, $children);

					$result["items"] = $items;
					$result["storageId"] = $storage->getId();
					$result["folderId"] = $targetFolderId;
					$result["name"] = $folder->getName();
					$result["rootFolderId"] = $storage->getRootObjectId();

					$count = $childrenRows->getCount();
					if (($offset + count($items)) < $count)
					{
						$result['next'] = $offset + count($items);
					}

					$result['total'] = $count;
				}
			}
		}

		return $result;
	}

	/**
	 * @param array $params
	 * @return array
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public static function getAttachmentsData($params)
	{
		$result = [];
		$attachmentsIds = (is_array($params['attachmentsIds']) ? $params['attachmentsIds'] : []);

		$driver = Driver::getInstance();
		$urlManager = $driver->getUrlManager();

		foreach ($attachmentsIds as $id)
		{
			$attachedObject = AttachedObject::loadById($id, ['OBJECT']);
			if(!$attachedObject || $file = !$attachedObject->getFile())
			{
				continue;
			}

			$file = $attachedObject->getFile();
			$extension = $file->getExtension();

			$result[] = [
				'ID' => $id,
				'OBJECT_ID' => $file->getId(),
				'NAME' => $file->getName(),
				'SIZE' => \CFile::formatSize($file->getSize()),
				'EXTENSION' => $extension,
				'TYPE' => TypeFile::getByExtension($extension),
				'URL' => $urlManager::getUrlUfController('show', ['attachedId' => $id]),
				'IS_IMAGE' => TypeFile::isImage($file),
				'CREATE_TIME' => \CRestUtil::ConvertDateTime($file->getCreateTime()),
				'UPDATE_TIME' => \CRestUtil::ConvertDateTime($file->getUpdateTime()),
			];
		}

		return $result;
	}
}