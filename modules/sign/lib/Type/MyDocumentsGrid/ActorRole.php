<?php

namespace Bitrix\Sign\Type\MyDocumentsGrid;

use Bitrix\Sign\Type\ValuesTrait;

enum ActorRole: string
{
	use ValuesTrait;

	case INITIATOR = 'initiator';
	case SIGNER = 'signer';
	case ASSIGNEE = 'assignee';
	case REVIEWER = 'reviewer';
	case EDITOR = 'editor';
}
