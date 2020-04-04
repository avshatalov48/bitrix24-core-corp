<?php
namespace Bitrix\Lists\Copy;

use Bitrix\Main\Copy\CompositeImplementation;
use Bitrix\Main\Copy\ContainerManager;
use Bitrix\Main\Copy\Copyable;
use Bitrix\Main\Result;

class IblockType implements Copyable
{
	use CompositeImplementation;

	private $iblockTypeId;
	private $socnetGroupId;

	private $targetIblockTypeId;
	private $targetSocnetGroupId;

	public function __construct($iblockTypeId, $socnetGroupId = 0)
	{
		$this->iblockTypeId = $iblockTypeId;
		$this->socnetGroupId = $socnetGroupId;

		$this->result = new Result();
	}

	/**
	 * Writes the entities ids of the target place.
	 * This is necessary if you want to copy the lists to another type of information block or another group.
	 *
	 * @param string $targetIblockTypeId Id type of information block.
	 * @param int $targetSocnetGroupId Group id.
	 */
	public function setTargetLocation($targetIblockTypeId, $targetSocnetGroupId = 0)
	{
		$this->targetIblockTypeId = $targetIblockTypeId;
		$this->targetSocnetGroupId = $targetSocnetGroupId;
	}

	/**
	 * Copies all iblock of the specified iblock type.
	 *
	 * @param ContainerManager $containerManager The object with data to copy.
	 * @return Result
	 */
	public function copy(ContainerManager $containerManager)
	{
		$queryObject = \CIBlock::getList([], $this->getFilterToCopy());
		while ($iblock = $queryObject->fetch())
		{
			$container = new Container($iblock["ID"]);

			$this->setDataToContainer($container);

			$containerManager->addContainer($container);
		}

		if (!$containerManager->isEmpty())
		{
			$this->startCopyEntities($containerManager);
		}

		return $this->result;
	}

	private function getFilterToCopy()
	{
		$filter = [
			"ACTIVE" => "Y",
			"TYPE" => $this->iblockTypeId,
			"CHECK_PERMISSIONS" => "N",
		];

		if ($this->socnetGroupId)
		{
			$filter["=SOCNET_GROUP_ID"] = $this->socnetGroupId;
		}
		else
		{
			$filter["SITE_ID"] = SITE_ID;
		}

		return $filter;
	}

	private function setDataToContainer(Container $container)
	{
		$container->setIblockTypeId($this->iblockTypeId);
		$container->setSocnetGroupId($this->socnetGroupId);
		$container->setTargetIblockTypeId($this->targetIblockTypeId);
		$container->setTargetSocnetGroupId($this->targetSocnetGroupId);
	}
}