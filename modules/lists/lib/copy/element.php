<?php
namespace Bitrix\Lists\Copy;

use Bitrix\Lists\Entity\Element as ElementEntity;
use Bitrix\Lists\Service\Param;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Copy\ContainerManager;
use Bitrix\Main\Copy\Copyable;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class Element implements Copyable
{
	/**
	 * @var Result
	 */
	private $result;

	public function __construct()
	{
		$this->result = new Result();
	}

	/**
	 * Starts agent to copy.
	 *
	 * @param ContainerManager $containerManager
	 * @return Result
	 */
	public function copy(ContainerManager $containerManager)
	{
		$agentName = __CLASS__."::copyElementsByAgent();";
		if (!$this->getAgentByName($agentName))
		{
			$dataToCopy = [];

			/** @var Container[] $containers */
			$containers = $containerManager->getContainers();
			foreach ($containers as $container)
			{
				$dataToCopy[$container->getEntityId()] = [
					"iblockTypeId" =>  $container->getIblockTypeId(),
					"copiedIblockId" => $container->getCopiedEntityId(),
					"offset" => 0
				];
			}

			try
			{
				Option::set("lists", "toCopyElements", serialize($dataToCopy));

				\CAgent::addAgent(
					$agentName,
					"lists",
					"Y",
					1,
					"",
					"Y",
					\ConvertTimeStamp(time()+\CTimeZone::GetOffset() + 10, "FULL"),
					100,
					false,
					false
				);
			}
			catch (\Exception $exception)
			{
				$this->result->addError(new Error($exception->getMessage()));
			}
		}

		return $this->result;
	}

	/**
	 * Agent handler.

	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function copyElementsByAgent()
	{
		$dataToCopy = Option::get("lists", "toCopyElements", "");
		if ($dataToCopy !== "" )
		{
			$dataToCopy = unserialize($dataToCopy);
		}
		if (!is_array($dataToCopy))
		{
			Option::delete("lists", ["name" => "toCopyElements"]);
			$GLOBALS["CACHE_MANAGER"]->cleanDir("user_option");
			return "";
		}

		$connection = Application::getInstance()->getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$limit = 100;

		foreach ($dataToCopy as $iblockId => $data)
		{
			$param = new Param(["IBLOCK_ID" => $iblockId, "IBLOCK_TYPE_ID" => $data["iblockTypeId"]]);
			$elementEntity = new ElementEntity($param);

			$queryObject = $connection->query("SELECT ID FROM `b_iblock_element` WHERE `IBLOCK_ID` = '".
				$sqlHelper->forSql($iblockId)."' ORDER BY ID ASC LIMIT ".$limit." OFFSET ".$data["offset"]);
			$selectedRowsCount = $queryObject->getSelectedRowsCount();

			while ($element = $queryObject->fetch())
			{
				$elementEntity->copyById($iblockId, $element["ID"], $data["copiedIblockId"]);
			}

			if ($selectedRowsCount < $limit)
			{
				unset($data[$iblockId]);
			}
			else
			{
				$dataToCopy[$iblockId]["offset"] = $data["offset"] + $selectedRowsCount;
			}
		}

		$GLOBALS["CACHE_MANAGER"]->cleanDir("user_option");

		if (!empty($iblockIdList))
		{
			Option::set("lists", "toCopyElements", serialize($iblockIdList));
			return __CLASS__."::copyElementsByAgent();";
		}
		else
		{
			Option::delete("lists", ["name" => "toCopyElements"]);
			return "";
		}
	}

	private function getAgentByName($name)
	{
		$queryObject = \CAgent::getList([], ["NAME" => $name]);
		if ($agent = $queryObject->fetch())
			return $agent;
		else
			return false;
	}
}