<?php
declare(strict_types=1);

namespace Bitrix\Disk\Controller\Types\Folder;

/**
 * Class ContextType
 * Description of the context type folder in folder listing.
 * @see \Bitrix\Disk\Controller\Folder::getChildrenAction
 */
enum ContextType: string
{
	case Basic = 'basic';
	case Group = 'group';
	case Collab = 'collab';
	case Sharing = 'sharing';
}
