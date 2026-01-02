<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;
use Bitrix\HumanResources\Item\NodeMember;

enum StructureRole: int
{
	// Department roles
	case HEAD = 1;
	case EMPLOYEE = 2;
	case DEPUTY_HEAD = 3;
	// Team roles;
	case TEAM_HEAD = 4;
	case TEAM_DEPUTY_HEAD = 5;
	case TEAM_EMPLOYEE = 6;

	use ValuesTrait;

	public function getXmlId(): string
	{
		$combinedXmlIdDictionary = array_merge(
			NodeMember::TEAM_ROLE_XML_ID,
			NodeMember::DEFAULT_ROLE_XML_ID
		);

		return $combinedXmlIdDictionary[$this->name] ?? '';
	}
}
