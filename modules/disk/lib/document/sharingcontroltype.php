<?php
declare(strict_types=1);

namespace Bitrix\Disk\document;

enum SharingControlType: string
{
	case WithoutEdit = 'without-edit';
	case WithChangeRights = 'with-change-rights';
	case WithSharing = 'with-sharing';
	case BlockedByFeature = 'blocked-by-feature';
}
