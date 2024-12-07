<?php

namespace Bitrix\Crm\Agent\Files;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Component\EntityDetails\Files\CopyFilesOnItemClone;
use Bitrix\Crm\MultiValueStoreService;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Type\DateTime;

class CleanUnusedFilesAfterCopy extends AgentBase
{
	const FILES_LIFE_DAYS = 7;

	const CLEANER_BATCH_LIMIT = 10;

	public static function doRun(): ?string
	{
		$filesIds = self::filesToClean();

		if (empty($filesIds))
		{
			self::changeInterval(60 * 60 * 24); // nothing to clean just wait 24 hours

			return true;
		}

		foreach ($filesIds as $filesId)
		{
			Container::getInstance()->getFileUploader()->deleteFilePersistently($filesId);
			CopyFilesOnItemClone::removeFileFromNotUsedCleanQueue($filesId);
		}

		self::changeInterval(10); // queue has items, clean every 10 seconds

		return true;
	}

	private static function filesToClean(): array
	{
		$mvs = MultiValueStoreService::getInstance();

		$dt = new DateTime();

		$dt->add('- ' . self::FILES_LIFE_DAYS . ' days');

		return $mvs->getKeyByCreatedLt(CopyFilesOnItemClone::CLEAN_FILE_QUEUE_KEY, $dt, self::CLEANER_BATCH_LIMIT);
	}

	private static function changeInterval(int $interval): void
	{
		global $pPERIOD;

		$agent = \CAgent::getList(
			[],
			[
				'MODULE_ID' => 'crm',
				'=NAME' => 'Bitrix\Crm\Agent\Files\CleanUnusedFilesAfterCopy::run();',
			]
		)->Fetch();

		if ($agent)
		{
			if ((int)$agent['AGENT_INTERVAL'] !== $interval)
			{
				\CAgent::Update($agent['ID'], ['AGENT_INTERVAL' => $interval]);
				$pPERIOD = $interval;
			}
		}
	}

}