<?php

namespace Bitrix\StaffTrack\Agent;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\StaffTrack\Dictionary\Location;
use Bitrix\StaffTrack\Helper\DateHelper;
use Bitrix\StaffTrack\Integration\Im\MessageService;
use Bitrix\StaffTrack\Model\ShiftGeoTable;
use Bitrix\StaffTrack\Model\ShiftMessageTable;
use Bitrix\StaffTrack\Model\ShiftTable;

final class ClearGeoAgent
{
	/** @var int  */
	private const PORTION = 50;
	private const NO_NEED_TO_RUN_ZONES = ['ru', 'by', 'kz', 'br', 'in'];

	protected function __construct()
	{
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function run(): string
	{
	 	(new self())->runInternal();

		return self::class . '::run();';
	}

	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function runInternal(): void
	{
		if (!$this->isNeedToRun())
		{
			return;
		}

		$shiftCollection = $this->getShiftCollectionToHandle();

		if ($shiftCollection->isEmpty())
		{
			return;
		}

		$messageIds = [];
		$shiftMessageIds = [];

		foreach ($shiftCollection as $shift)
		{
			if (!$shift->getMessages()->isEmpty())
			{
				foreach ($shift->getMessages() as $message)
				{
					$messageIds[] = $message->getMessageId();
				}

				$shiftMessageIds[] = $shift->getId();
			}
		}

		if (!empty($messageIds))
		{
			$this->deleteMessages($messageIds);
		}

		if (!empty($shiftMessageIds))
		{
			$this->deleteMessagesConnection($shiftMessageIds);
		}

		$shiftIdList = $shiftCollection->getIdList();

		$this->changeLocationToDeleted($shiftIdList);
		$this->deleteGeoConnection($shiftIdList);
	}

	/**
	 * @return bool
	 */
	private function isNeedToRun(): bool
	{
		$portalZone = Application::getInstance()->getLicense()->getRegion() ?? 'en';

		return !in_array($portalZone, self::NO_NEED_TO_RUN_ZONES, true);
	}

	/**
	 * @return \Bitrix\StaffTrack\Model\ShiftCollection
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getShiftCollectionToHandle(): \Bitrix\StaffTrack\Model\ShiftCollection
	{
		return ShiftTable::query()
			->setSelect(['ID', 'SHIFT_DATE', 'GEO_INNER.*', 'MESSAGES.*'])
			->where('SHIFT_DATE', '<=', DateHelper::getInstance()->getServerDate()->add('-3 months'))
			->setLimit(self::PORTION)
			->exec()
			->fetchCollection()
		;
	}

	/**
	 * @param array $messageIds
	 *
	 * @return void
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function deleteMessages(array $messageIds): void
	{
		$messageService = new MessageService(0);

		foreach ($messageIds as $messageId)
		{
			$messageService->deleteMessage($messageId);
		}
	}

	/**
	 * @param array $shiftIdList
	 *
	 * @return void
	 * @throws ArgumentException
	 */
	private function deleteMessagesConnection(array $shiftIdList): void
	{
		ShiftMessageTable::deleteByFilter([
			'SHIFT_ID' => $shiftIdList,
		]);
	}

	/**
	 * @param array $shiftIdList
	 *
	 * @return void
	 * @throws ArgumentException
	 */
	private function deleteGeoConnection(array $shiftIdList): void
	{
		ShiftGeoTable::deleteByFilter([
			'SHIFT_ID' => $shiftIdList,
		]);
	}

	/**
	 * @param array $shiftIdList
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function changeLocationToDeleted(array $shiftIdList): void
	{
		ShiftTable::updateMulti($shiftIdList, [
			'LOCATION' => Location::DELETED->value,
		]);
	}
}
