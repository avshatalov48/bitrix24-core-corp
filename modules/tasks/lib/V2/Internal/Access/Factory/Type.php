<?php

namespace Bitrix\Tasks\V2\Internal\Access\Factory;

enum Type
{
	case Task;
	case Template;
	case Flow;
	case Group;
	case Collab;
	case Reminder;
}
