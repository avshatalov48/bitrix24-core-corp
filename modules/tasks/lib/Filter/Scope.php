<?php

namespace Bitrix\Tasks\Filter;

enum Scope: string
{
	case COLLABER = 'collaber';
	case SCRUM = 'scrum';
	case DEFAULT = 'default';
	case RELATION = 'relation';
}
