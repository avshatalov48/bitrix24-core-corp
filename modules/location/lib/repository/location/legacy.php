<?php

namespace Bitrix\Location\Repository\Location;

use Bitrix\Location\Entity\Location;
use Bitrix\Location\Repository\Location\Capability\IFindByExternalId;
use Bitrix\Location\Repository\Location\Capability\IFindByText;
use Bitrix\Location\Repository\Location\Capability\IFindParents;
use Bitrix\Main\NotImplementedException;

/**
 * Class Legacy
 * @package Bitrix\Location\Repository
 */
class Legacy
	implements IFindByExternalId, IFindByText, IFindParents
{

	/**
	 * @inheritDoc
	 */
	public function findByExternalId(string $externalId, string $sourceCode, string $languageId)
	{
		throw new NotImplementedException();
	}

	/**
	 * @inheritDoc
	 */
	public function findByText(string $text, string $languageId)
	{
		throw new NotImplementedException();
	}

	/** @inheritDoc */
	public function findParents(Location $location, string $languageId)
	{
		return null;
	}
}
