<?php

namespace Bitrix\Intranet\Update;

use Bitrix\Intranet\Counters\Counter;
use Bitrix\Intranet\Counters\Synchronizations\TotalInvitationSynchronization;
use Bitrix\Intranet\Counters\Synchronizations\WaitConfirmationSynchronization;
use Bitrix\Intranet\Invitation;
use Bitrix\Intranet\UserTable;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Update\Stepper;
use Bitrix\Main\UserProfileHistoryTable;
use Bitrix\Main\UserProfileRecordTable;

// Converts invited fired users into fired instead waiting confirmation
class WaitingConfirmationConvert extends Stepper
{
	protected static $moduleId = 'intranet';
	private int $limit = 100;
	private function getUserIds($startId = 1): array
	{
		return UserTable::query()
			->setLimit($this->limit)
			->setSelect(['ID'])
			->setOrder(['ID' => 'ASC'])
			->registerRuntimeField('PROFILE_HISTORY', new Reference(
				'PROFILE_HISTORY',
				UserProfileHistoryTable::class,
				Join::on('this.ID', 'ref.USER_ID')
			))
			->registerRuntimeField('PROFILE_RECORD', new Reference(
				'PROFILE_RECORD',
				UserProfileRecordTable::class,
				Join::on('this.PROFILE_HISTORY.ID', 'ref.HISTORY_ID')
			))
			->where('ID', '>=', $startId)
			->where('ACTIVE', '=', 'N')
			->where('CONFIRM_CODE', '!=', '')
			->where('PROFILE_RECORD.FIELD', '=', 'ACTIVE')
			->fetchCollection()
			->getIdList();
	}

	public function execute(array &$result): bool
	{
		if (empty($result['lastId']))
		{
			$result['lastId'] = 1;
		}

		$userIds = $this->getUserIds($result['lastId']);
		$cUser = new \CUser();

		foreach ($userIds as $userId)
		{
			$cUser->Update($userId, ['CONFIRM_CODE' => '']);
			$result['lastId'] = $userId;
		}

		if (\Bitrix\Main\Loader::includeModule('intranet'))
		{
			$waitConfirmSync = new WaitConfirmationSynchronization();
			$counter = new Counter(Invitation::getWaitConfirmationCounterId(), $waitConfirmSync);
			$counter->sync();

			$totalCounter = new Counter(
				Invitation::getTotalInvitationCounterId(),
				new TotalInvitationSynchronization()
			);
			$totalCounter->sync();
		}

		return count($userIds) < $this->limit ? self::FINISH_EXECUTION : self::CONTINUE_EXECUTION;
	}
}