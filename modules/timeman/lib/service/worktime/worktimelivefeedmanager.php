<?php
namespace Bitrix\Timeman\Service\Worktime;

use Bitrix\Socialnetwork\Livefeed\Provider;
use CSocNetLogFollow;

class WorktimeLiveFeedManager
{
	public function continueWorkdayPostTrackingForApprover($recordId, $userId): void
	{
		if ($userId <= 0 || !\Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			return;
		}
		$postCode = $this->getPostCodeByRecordId($recordId);

		if ($postCode !== null)
		{
			$logItem = CSocNetLogFollow::getList(
				[
					'CODE' => $postCode,
					'TYPE' => 'N',
					'USER_ID' => $userId,
					'BY_WF' => 'Y', // post was ignored by system, not by user manually
				],
				['USER_ID']
			)->fetch();

			if ($logItem)
			{
				CSocNetLogFollow::delete($userId, $postCode);
			}
		}
	}

	public function stopWorkdayPostTrackingForApprover($recordId, $approvedBy): void
	{
		if ($approvedBy <= 0 || !\Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			return;
		}
		$postCode = $this->getPostCodeByRecordId($recordId);

		if ($postCode !== null)
		{
			// auto post about workday approving will not pop up for the supervisor that approved this day
			CSocNetLogFollow::set($approvedBy, $postCode, 'N', $followDate = false, SITE_ID, $byWorkFlow = true);
		}
	}

	private function getPostIdByRecordId($recordId): ?int
	{
		$recordId = (int)$recordId;
		static $postIdsByRecord = [];
		if (!array_key_exists($recordId, $postIdsByRecord))
		{
			$postIdsByRecord[$recordId] = false;
			$provider = Provider::init([
				'ENTITY_TYPE' => Provider::DATA_ENTITY_TYPE_TIMEMAN_ENTRY,
				'ENTITY_ID' => $recordId,
			]);
			if ($provider)
			{
				$id = $provider->getLogId();
				if ($id > 0)
				{
					$postIdsByRecord[$recordId] = $id;
				}
			}
		}

		return $postIdsByRecord[$recordId] === false ? null : $postIdsByRecord[$recordId];
	}

	private function getPostCodeByRecordId($recordId): ?string
	{
		if ($this->getPostIdByRecordId($recordId) > 0)
		{
			return 'L' . $this->getPostIdByRecordId($recordId);
		}
		return null;
	}
}