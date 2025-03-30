<?php

namespace Bitrix\Disk\Document\Flipchart;

enum WebhookEventType: string
{
	case WasModified = 'WAS_MODIFIED';
	case LastUserLeftTheFlip = 'LAST_USER_LEFT_THE_FLIP';
	case FlipDeleted = 'FLIP_DELETED';
	case FlipRenamed = 'FLIP_RENAMED';
	case UserEntry = 'USER_ENTRY';
	case UserLeft = 'USER_LEFT';
}