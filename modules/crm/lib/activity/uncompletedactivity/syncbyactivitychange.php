<?php

namespace Bitrix\Crm\Activity\UncompletedActivity;

use Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeRepo;
use Bitrix\Crm\Activity\UncompletedActivityChange;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Config\Option;

/**
 * This class check what was changed in the `activity` and try to update `uncompletedactivitytable` by this
 * changeset without recalculate data from the DB or will return flag to recalculate.
 * @see trySynchronize method
 */
class SyncByActivityChange
{
	private ActCounterLightTimeRepo $lightTimeRepo;

	private bool $isUncompletedActivityHasInconsistentData;

	public function __construct(
		private ItemIdentifier $itemIdentifier,
		private ?UncompletedActivityChange $activityChange,
		private int $responsibleId,
		private UncompletedActivityRepo $uncompletedActivityRepo
	)
	{
		$this->lightTimeRepo = new ActCounterLightTimeRepo();
		$this->isUncompletedActivityHasInconsistentData = Option::get('crm', 'enable_any_incoming_act', 'Y') === 'N';
	}

	/**
	 * @return bool return
	 * 		- false when data must be recalculated.
	 *		- true when data in the consistent state and recalculation not needed
	 *	  If data can be updated from changeset it will be run `updateByActivityChange` to do it.
	 */
	public function trySynchronize(): bool
	{
		if ($this->activityChange === null)
		{
			return false;
		}
		if (!$this->activityChange->hasChanges())
		{
			return true;
		}
		if ($this->isUncompletedActivityHasInconsistentData)
		{
			return false;
		}

		if (
			!$this->activityChange->wasActivityJustDeleted()
			&& $this->responsibleId > 0
			&& $this->activityChange->isResponsibleIdChanged()
			&& $this->activityChange->getOldResponsibleId() === $this->responsibleId)
		{
			// for old responsible activityChange can not be used
			return false;
		}

		$existedRecord = $this->uncompletedActivityRepo->getExistedRecord();
		if (!$existedRecord) // activityChange is about first activity
		{
			if (!$this->activityChange->wasActivityJustDeleted() && !$this->activityChange->getNewIsCompleted())
			{
				$this->updateByActivityChange();
			}

			return true;
		}

		$existedRecordDto = RecordDto::fromOrmArray($existedRecord);

		if ($this->activityChange->wasActivityJustDeleted() || $this->activityChange->wasActivityJustCompleted())
		{
			return $this->activityJustDeletedOrCompleted($existedRecordDto);
		}
		elseif ($this->activityChange->wasActivityJustAdded() || $this->activityChange->wasActivityJustUnCompleted())
		{
			return $this->activityJustAddedOrUnCompleted($existedRecordDto);
		}
		else
		{
			return $this->activityJustUpdated($existedRecordDto);
		}
	}



	private function activityJustDeletedOrCompleted(RecordDto $existedRecord): bool
	{
		// check if deleted(completed) activity can affect the values in $existedRecord:
		$deletedActivityDeadline = $this->activityChange->getOldDeadline();
		$deletedActivityIsIncomingChannel = $this->activityChange->getOldIsIncomingChannel();
		$deletedLightTime = $this->activityChange->getOldLightTime();

		$minLTTimestamp = $existedRecord->minLightTime()?->getTimestamp() ?? 0;

		if ($deletedLightTime && $deletedLightTime->getTimestamp() <= $minLTTimestamp)
		{
			return false;
		}

		if (
			$existedRecord->minDeadline() &&
			(
				($deletedActivityDeadline && $existedRecord->minDeadline()->getTimestamp() < $deletedActivityDeadline->getTimestamp())
				|| (!$deletedActivityDeadline && !$deletedActivityIsIncomingChannel)
			)
		) {
			return true; // deleted activity doesn't affect
		}

		if (!$existedRecord->minDeadline() && $existedRecord->isIncomingChannel() && !$deletedActivityIsIncomingChannel) {
			return true; // deleted activity doesn't affect
		}

		return false;
	}

	private function activityJustAddedOrUnCompleted(RecordDto $existedRecord): bool
	{
		$newDeadline = $this->activityChange->getNewDeadline();
		$newIsIncomingChannel = $this->activityChange->getNewIsIncomingChannel();
		$newLightTime = $this->activityChange->getNewLightTime();

		if ($this->activityChange->getNewIsCompleted()) // new completed activity doesn't affect
		{
			return true;
		}
		if (!$existedRecord->minDeadline() && !$newDeadline) {
			if (
				$existedRecord->isIncomingChannel()
				|| (!$existedRecord->isIncomingChannel() && !$newIsIncomingChannel)
			) {
				return true; // new(uncompleted) activity doesn't affect
			}
			$this->updateByActivityChange(); // update IS_INCOMING_CHANNEL

			return true;
		}
		if (!$newDeadline) {
			if (!$existedRecord->isAnyIncomingChannel() && $newIsIncomingChannel) {
				return false; // need update HAS_ANY_INCOMING_CHANEL only
			}

			return true; //  new(uncompleted) activity doesn't affect because $existedDeadline has more privileges
		}
		if (!$existedRecord->minDeadline()) {
			$this->updateByActivityChange(); // set MIN_DEADLINE

			return true;
		}

		$isRecalculateByLightTime = false;
		if ($existedRecord->minLightTime() && $newLightTime)
		{
			$isRecalculateByLightTime = $newLightTime->getTimestamp() < $existedRecord->minLightTime()->getTimestamp();
		}

		if (
			$existedRecord->minDeadline()->getTimestamp() <= $newDeadline->getTimestamp()
		) {
			if ($isRecalculateByLightTime)
			{
				$this->updateByActivityChange();
			}
			return true; // $newDeadline is greater than $existedDeadline, so doesn't affect
		}

		$this->updateByActivityChange(); // update MIN_DEADLINE

		return true;
	}

	public function activityJustUpdated(RecordDto $existedRecord): bool
	{
		$newDeadline = $this->activityChange->getNewDeadline();
		$oldDeadline = $this->activityChange->getOldDeadline();

		$existedLightTime = $existedRecord->minLightTime();
		$activityLightTime = $this->activityChange->getNewLightTime();

		if (
			!$existedLightTime
			|| !$activityLightTime
			|| $existedLightTime->getTimestamp() > $activityLightTime->getTimestamp()
		) {
			$this->updateByActivityChange();
			return true;
		}

		if ($existedRecord->minDeadline() && $newDeadline && $oldDeadline) {
			if (
				$existedRecord->minDeadline()->getTimestamp() < $oldDeadline->getTimestamp()
				&& $existedRecord->minDeadline()->getTimestamp() < $newDeadline->getTimestamp()
			) {
				return true; // deadline change doesn't affect
			}
		}

		return false;
	}

	private function updateByActivityChange(): void
	{
		$isIncomingChannel = false;
		$deadline = $this->activityChange->getNewDeadline();
		if (!$deadline)
		{
			$deadline = \CCrmDateTimeHelper::getMaxDatabaseDateObject();
			$isIncomingChannel = ($this->activityChange->getNewIsIncomingChannel() === true);
		}
		$hasAnyIncomingChannel = $isIncomingChannel;
		if (!$isIncomingChannel)
		{
			$hasAnyIncomingChannel = !!$this->uncompletedActivityRepo->getUncompletedIncomingActivityId();
		}

		$minLightTime = $isIncomingChannel
			? \CCrmDateTimeHelper::getMaxDatabaseDateObject()
			: $this->lightTimeRepo->minLightTimeByItemIdentifier($this->itemIdentifier);

		$this->uncompletedActivityRepo->upsert(
			new UpsertDto(
				$this->activityChange->getId(),
				$deadline,
				$isIncomingChannel,
				$hasAnyIncomingChannel,
				$minLightTime,
				$this->itemIdentifier,
				$this->responsibleId
			)
		);
	}
}
