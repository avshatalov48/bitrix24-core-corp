<?php

declare(strict_types=1);

namespace Bitrix\Disk\Analytics\Enum;

enum Event: string
{
	case FileUploaded = 'upload_file';
	case FolderAdded = 'add_folder';
	case FileCreated = 'create_file';
}
