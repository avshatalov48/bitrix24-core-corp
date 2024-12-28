<?php

namespace Bitrix\Sign\Type\MyDocumentsGrid;

use Bitrix\Sign\Type\ValuesTrait;

enum Action: string
{
	use ValuesTrait;

	case DOWNLOAD = 'download';
	case VIEW = 'view';
	case APPROVE = 'approve';
	case EDIT = 'edit';
	case SIGN = 'sign';
}
