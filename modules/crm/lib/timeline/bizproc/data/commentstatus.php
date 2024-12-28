<?php

namespace Bitrix\Crm\Timeline\Bizproc\Data;

enum CommentStatus: string
{
	case Created = 'created';
	case Deleted = 'deleted';
	case Viewed = 'viewed';
}
