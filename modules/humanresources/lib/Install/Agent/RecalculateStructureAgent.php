<?php

namespace Bitrix\HumanResources\Install\Agent;

use Bitrix\HumanResources\Item\Structure;
use Bitrix\HumanResources\Service\Container;

class RecalculateStructureAgent
{
	public static function run(): string
	{
		$structureRepository = Container::getStructureRepository();

		$structure = $structureRepository->getByXmlId(Structure::DEFAULT_STRUCTURE_XML_ID);

		if ($structure !== null && $structure?->id !== null)
		{
			Container::getStructureWalkerService()->rebuildStructure($structure->id);
		}

		return '';
	}
}