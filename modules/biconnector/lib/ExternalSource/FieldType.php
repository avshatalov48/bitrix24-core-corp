<?php

namespace Bitrix\BIConnector\ExternalSource;

Enum FieldType: string
{
	case Int = 'int';
	case String = 'string';
	case Double = 'double';
	case Date = 'date';
	case DateTime = 'datetime';
	case Money = 'money';
}
