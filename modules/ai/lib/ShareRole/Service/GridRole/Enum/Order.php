<?php

namespace Bitrix\AI\ShareRole\Service\GridRole\Enum;

enum Order: string
{
	const Desc = 'desc';
	const Asc = 'asc';

	case Name = 'NAME';
	case Author = 'AUTHOR';
	case DateCreate = 'DATE_CREATE';
	case DateModify = 'DATE_MODIFY';
	case Editor = 'EDITOR';
}