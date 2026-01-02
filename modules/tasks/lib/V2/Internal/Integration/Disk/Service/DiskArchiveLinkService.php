<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Disk\Service;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Security\ParameterSigner;
use Bitrix\Disk\ZipNginx;
use Bitrix\Main\Loader;
use Bitrix\Tasks\V2\Internal\Entity\UF\UserField;

class DiskArchiveLinkService
{
	private const DOWNLOAD_ACTION = 'downloadArchiveByEntity';

	public function get(int $taskId): ?string
	{
		if (!Loader::includeModule('disk'))
		{
			return null;
		}

		if (!ZipNginx\Configuration::isEnabled())
		{
			return null;
		}

		$urlManager = Driver::getInstance()->getUrlManager();

		return $urlManager::getUrlUfController(self::DOWNLOAD_ACTION, [
			'entityId' => $taskId,
			'entity' => UserField::TASK,
			'fieldName' => UserField::TASK_ATTACHMENTS,
			'signature' => ParameterSigner::getEntityArchiveSignature(
				entity: UserField::TASK,
				entityId: $taskId,
				fieldName: UserField::TASK_ATTACHMENTS,
			),
		]);
	}
}
