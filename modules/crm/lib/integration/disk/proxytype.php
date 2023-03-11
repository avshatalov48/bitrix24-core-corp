<?php

namespace Bitrix\Crm\Integration\Disk;

use Bitrix\Disk\ProxyType\Base;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Crm\Security\DiskSecurityContext;
use Bitrix\Main\Localization\Loc;


class ProxyType extends Base
{
	final public function getSecurityContextByUser($user): SecurityContext
	{
		return new DiskSecurityContext($user);
	}

	final public function getEntityTitle(): string
	{
		return Loc::getMessage('CRM_HIDDEN_STORAGE_DISK_TITLE');
	}

	final public function getStorageBaseUrl(): string
	{
		return '';
	}

	final public function getEntityImageSrc($width, $height): string
	{
		return '/bitrix/js/crm/images/blank.gif';
	}

	final public function getTitle()
	{
		return $this->getEntityTitle();
	}
}
