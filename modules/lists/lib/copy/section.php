<?php
namespace Bitrix\Lists\Copy;

use Bitrix\Main\Copy\ContainerManager;
use Bitrix\Main\Copy\Copyable;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

Loc::loadMessages(__FILE__);

class Section implements Copyable
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
	 * Copies sections.
	 *
	 * @param ContainerManager $containerManager
	 * @return Result
	 */
	public function copy(ContainerManager $containerManager)
	{
		$sectionObject = new \CIBlockSection;

		$containers = $containerManager->getContainers();

		/** @var Container[] $containers */
		foreach ($containers as $container)
		{
			$sections = $this->getSections($container->getEntityId());
			foreach ($sections as $section)
			{
				$section["IBLOCK_ID"] = $container->getCopiedEntityId();
				$result = $sectionObject->add($section);
				if (!$result)
				{
					if ($sectionObject->LAST_ERROR)
					{
						$this->result->addError(new Error($sectionObject->LAST_ERROR));
					}
					else
					{
						$this->result->addError(new Error(Loc::getMessage("COPY_SECTION_UNKNOWN_ERROR")));
					}
				}
			}
		}

		return $this->result;
	}

	private function getSections($iblockId)
	{
		$sections = [];

		$queryObject = \CIBlockSection::getList([], ["IBLOCK_ID" => $iblockId, "CHECK_PERMISSIONS" => "N"], false);
		while ($section = $queryObject->fetch())
		{
			$sections[] = $section;
		}

		return $sections;
	}
}