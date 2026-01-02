<?php

namespace Bitrix\HumanResources\Access\Enum;

enum PermissionValueType: string
{
	case NoneValue = 'N';
	case DepartmentValue = 'D';
	case TeamValue = 'T';
	case AllValue = 'A';
}