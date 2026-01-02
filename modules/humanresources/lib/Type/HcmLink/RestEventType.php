<?php

namespace Bitrix\HumanResources\Type\HcmLink;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;

enum RestEventType: string
{
	use ValuesTrait;

	case onEmployeeListRequested = 'OnHumanResourcesHcmLinkEmployeeListRequested';
	case onFieldValueRequested = 'OnHumanResourcesHcmLinkFieldValueRequested';
	case onEmployeeListMapped = 'OnHumanResourcesHcmLinkEmployeeListMapped';
}
