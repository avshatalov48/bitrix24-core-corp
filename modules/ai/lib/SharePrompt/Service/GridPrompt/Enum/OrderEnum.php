<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Service\GridPrompt\Enum;

enum OrderEnum: string
{
	const DESC = 'desc';
	const ASC = 'asc';

	case NAME = 'NAME';
	case TYPE = 'TYPE';
	case AUTHOR = 'AUTHOR';
	case DATE_CREATE = 'DATE_CREATE';
	case DATE_MODIFY = 'DATE_MODIFY';
	case EDITOR = 'EDITOR';
}
