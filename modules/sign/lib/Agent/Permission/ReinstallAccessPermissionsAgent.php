<?php

namespace Bitrix\Sign\Agent\Permission;

use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Access;

class ReinstallAccessPermissionsAgent
{
	public static function run(): string
	{
		$documentRepository = Container::instance()->getDocumentRepository();
		if ($documentRepository->existAnyDocument())
		{
			return '';
		}

		$anyPermission = Access\Permission\PermissionTable::query()
			->setSelect(['ID'])
			->setLimit(1)
			->fetchObject()
		;

		if ($anyPermission !== null)
		{
			return '';
		}

		\Bitrix\Sign\Access\Install\AccessInstaller::install();

		return '';
	}
}