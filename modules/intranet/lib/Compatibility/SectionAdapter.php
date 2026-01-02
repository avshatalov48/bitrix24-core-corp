<?php

namespace Bitrix\Intranet\Compatibility;

use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Loader;

class SectionAdapter
{
	public function __construct(
		private int $sectionId
	)
	{
	}

	public function toNodeId(): ?int
	{
		if (!Loader::includeModule('humanresources'))
		{
			return null;
		}

		return Container::getNodeRepository()
			->getByAccessCode('D'.$this->sectionId)?->id;
	}
}