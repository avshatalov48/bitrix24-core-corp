<?php

namespace Bitrix\Sign\Type\User;

enum Gender: string
{
	case MALE = 'M';
	case FEMALE = 'F';
	case DEFAULT = '';
}
