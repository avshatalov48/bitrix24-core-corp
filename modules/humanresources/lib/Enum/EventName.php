<?php

namespace Bitrix\HumanResources\Enum;

use Bitrix\HumanResources\Contract;

enum EventName implements Contract\Enum\EventName
{
	case OnMemberAdded;
	case OnMemberUpdated;
	case OnMemberDeleted;
	case OnRelationAdded;
	case OnRelationDeleted;
	case OnRelationPartDeleted;
	case OnNodeAdded;
	case OnNodeUpdated;
	case OnNodeDeleted;
}