<?php

namespace Bitrix\Mobile\Rest;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\FolderTable;
use Bitrix\Main\Loader;

class Disk extends \IRestService
{
	public static function getMethods()
	{
		return [
			'mobile.disk.folder.getchildren' => ['callback' => [__CLASS__, 'get'], 'options' => ['private' => false]],
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
				$targetFolder = $storage->getRootObjectId();
				if ($folderId)
				{
					$targetFolder = $folderId;
				}
				$securityContext = $storage->getCurrentUserSecurityContext();
				$parameters = Driver::getInstance()->getRightsManager()->addRightsCheck($securityContext, $parameters, ['ID', 'CREATED_BY']);
				$childrenRows = FolderTable::getChildren($targetFolder, $parameters);
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
					return $arrayData;
				}, $children);

				$result["items"] = $items;
				$result["storageId"] = $storage->getId();
				$result["folderId"] = $targetFolder;
				$result["rootFolderId"] = $storage->getRootObjectId();

				$count = $childrenRows->getCount();
				if (($offset + count($items)) < $count)
				{
					$result['next'] = $offset + count($items);
				}
				$result['total'] = $count;

			}
		}

		return $result;
	}


}