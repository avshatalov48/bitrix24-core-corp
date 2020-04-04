<?php
namespace Bitrix\Lists\Copy;

use Bitrix\Main\Copy\CompositeImplementation;
use Bitrix\Main\Copy\ContainerManager;
use Bitrix\Main\Copy\Copyable;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

Loc::loadMessages(__FILE__);

class Iblock implements Copyable
{
	use CompositeImplementation;

	const CODE_PREFIX_TO_COPY = "COPY_";

	public function __construct()
	{
		$this->result = new Result();
	}

	/**
	 * Copies iblocks.
	 *
	 * @param ContainerManager $containerManager
	 * @return Result
	 */
	public function copy(ContainerManager $containerManager)
	{
		$containers = $containerManager->getContainers();

		$result = [];

		/** @var Container[] $containers */
		foreach ($containers as $container)
		{
			$iblockIdToCopy = $container->getEntityId();

			$iblockFields = $this->getIblockData($container);

			$copiedIblockId = $this->addIblock($iblockFields);

			if ($this->result->getErrors())
			{
				$result[$iblockIdToCopy] = false;
			}
			else
			{
				$container->setCopiedEntityId($copiedIblockId);
				$this->addRightsToIblock($container, $iblockFields);
				$result[$iblockIdToCopy] = $copiedIblockId;
			}
		}

		$this->startCopyEntities($containerManager);

		$this->result->setData($result);

		return $this->result;
	}

	private function getIblockData(Container $container)
	{
		$iblockId = $container->getEntityId();

		if (!$this->isExist($iblockId))
		{
			$this->result->addError(new Error(Loc::getMessage("COPY_LISTS_NOT_FOUND", ["#IBLOCK_ID#" => $iblockId])));
			return [];
		}

		$query = \CIBlock::getList([], ["ID" => $iblockId], true);
		$iblock = $query->fetch();

		if ($iblock)
		{
			$iblockMessage = \CIBlock::getMessages($iblockId);
			$iblock = array_merge($iblock, $iblockMessage);
		}

		$targetIblockTypeId = $container->getTargetIblockTypeId();
		$targetSocnetGroupId = $container->getTargetSocnetGroupId();

		if ($targetIblockTypeId)
		{
			$iblock["IBLOCK_TYPE_ID"] = $targetIblockTypeId;
		}

		if ($targetSocnetGroupId)
		{
			$iblock["SOCNET_GROUP_ID"] = $targetSocnetGroupId;
		}

		if (!empty($iblock["PICTURE"]))
		{
			$iblock["PICTURE"] = \CFile::makeFileArray($iblock["PICTURE"]);
		}

		if (!empty($iblock["CODE"]))
		{
			$iblock["CODE"] = self::CODE_PREFIX_TO_COPY.$iblock["CODE"];
		}

		return $iblock;
	}

	private function addIblock(array $iblockFields)
	{
		$iblockObject = new \CIBlock;
		$iblockId = $iblockObject->add($iblockFields);

		if ($iblockId)
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag("lists_list_".$iblockId);
			$CACHE_MANAGER->ClearByTag("lists_list_any");
			$CACHE_MANAGER->CleanDir("menu");

			return $iblockId;
		}
		else
		{
			$this->result->addError(new Error($iblockObject->LAST_ERROR));

			return false;
		}
	}

	private function addRightsToIblock(Container $container, array $iblockFields)
	{
		$iblockId = $container->getEntityId();

		$iblockFields["RIGHTS"] = $this->getRights(
			$iblockId,
			$iblockFields["RIGHTS_MODE"],
			$iblockFields["SOCNET_GROUP_ID"]
		);

		if (!$this->updateIblock($container->getCopiedEntityId(), $iblockFields))
		{
			$this->result->addError(new Error(Loc::getMessage("COPY_LISTS_SET_RIGHT", ["#IBLOCK_ID#" => $iblockId])));
		}
	}

	private function getRights($iblockId, $rightMode, $socnetGroupId = 0)
	{
		$rights = [];

		if ($rightMode == "E")
		{
			$rightObject = new \CIBlockRights($iblockId);
			$i = 0;
			foreach ($rightObject->getRights() as $right)
			{
				$rights["n".($i++)] = [
					"GROUP_CODE" => $right["GROUP_CODE"],
					"DO_CLEAN" => "N",
					"TASK_ID" => $right["TASK_ID"],
				];
			}
		}
		else
		{
			$i = 0;
			if ($socnetGroupId)
			{
				$socnetPerm = \CLists::getSocnetPermission($iblockId);
				foreach ($socnetPerm as $role => $permission)
				{
					if ($permission > "W")
					{
						$permission = "W";
					}
					switch ($role)
					{
						case "A":
						case "E":
						case "K":
							$rights["n".($i++)] = [
								"GROUP_CODE" => "SG" . $socnetGroupId."_".$role,
								"IS_INHERITED" => "N",
								"TASK_ID" => \CIBlockRights::letterToTask($permission),
							];
							break;
						case "L":
							$rights["n".($i++)] = [
								"GROUP_CODE" => "AU",
								"IS_INHERITED" => "N",
								"TASK_ID" => \CIBlockRights::letterToTask($permission),
							];
							break;
						case "N":
							$rights["n".($i++)] = [
								"GROUP_CODE" => "G2",
								"IS_INHERITED" => "N",
								"TASK_ID" => \CIBlockRights::letterToTask($permission),
							];
							break;
					}
				}
			}
			else
			{
				$groupPermissions = \CIBlock::getGroupPermissions($iblockId);
				foreach ($groupPermissions as $groupId => $permission)
				{
					if ($permission > "W")
					{
						$rights["n" . ($i++)] = [
							"GROUP_CODE" => "G".$groupId,
							"IS_INHERITED" => "N",
							"TASK_ID" => \CIBlockRights::letterToTask($permission),
						];
					}
				}
			}
		}

		return $rights;
	}

	private function updateIblock($iblockId, array $iblockFields)
	{
		$iblockObject = new \CIBlock;
		if ($iblockObject->update($iblockId, $iblockFields))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	private function isExist($iblockId)
	{
		$filter = [
			"ID" => $iblockId,
			"CHECK_PERMISSIONS" => "N",
		];
		$queryObject = \CIBlock::getList([], $filter);
		return (bool) $queryObject->fetch();
	}
}