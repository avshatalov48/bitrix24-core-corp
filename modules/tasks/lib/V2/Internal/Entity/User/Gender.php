<?php

namespace Bitrix\Tasks\V2\Internal\Entity\User;

enum Gender: string
{
	case Male = 'M';
	case Female = 'F';
	case None = 'N';
}
