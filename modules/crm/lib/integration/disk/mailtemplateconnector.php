<?php
/**
 * Note: this is an internal class for disk module. It wont work without the module installed.
 * @internal
 */

namespace Bitrix\Crm\Integration\Disk;

use Bitrix\Disk\Uf;
use Bitrix\Disk\File;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Crm\MailTemplate\MailTemplateAccess;

class MailTemplateConnector extends Uf\StubConnector
{
	protected static array $templates = [];

	public function getDataToShow()
	{
		return [];
	}

	public function getDataToShowByUser(int $userId)
	{
		return [];
	}

	public function canRead($userId)
	{
		if (!isset(static::$templates[$this->entityId]))
		{
			static::$templates[$this->entityId] = \CCrmMailTemplate::GetByID($this->entityId);
		}

		$template = static::$templates[$this->entityId];
		if (
			$template
			&& ($template['IS_ACTIVE'] === 'Y')
			&& (
				((int)$template['SCOPE'] === \CCrmMailTemplateScope::Common)
				|| ((int)$template['OWNER_ID'] === (int)$userId)
				|| ((int)$template['SCOPE'] === \CCrmMailTemplateScope::Limited
					&& MailTemplateAccess::checkAccessToLimitedTemplate((int)$template['ID']))
			)
		)
		{
			return true;
		}

		return false;
	}

	public function canUpdate($userId)
	{
		return false;
	}
}
