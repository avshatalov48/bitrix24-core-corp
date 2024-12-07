<?php

namespace Bitrix\HumanResources\Type;

enum StructureType: string
{
	case DEFAULT = 'DEFAULT';
	case COMPANY = 'COMPANY';

	use ValuesTrait;
}
