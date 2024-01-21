<?php

namespace Bitrix\Disk\Ui;

use Bitrix\Disk\Configuration;
use Bitrix\Disk\Search\Reindex\ExtendedIndex;
use Bitrix\Disk\User;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Main\UI\Filter\Options;

class FolderListFilter
{
	private const WITH_EXTERNAL_LINK = 1;
	private const SHARED_FROM_ME = 2;
	private const SHARED_TO_ME = 3;
	private int $storageId;
	private bool $trashcanMode;

	public function __construct(int $storageId, bool $trashcanMode = false)
	{
		$this->trashcanMode = $trashcanMode;
		$this->storageId = $storageId;
	}

	public function getId(): string
	{
		$prefix = $this->trashcanMode ? 'trashcan_' : 'folder_list_';

		return $prefix . $this->storageId;
	}

	/**
	 * @throws ObjectException
	 */
	public function getConfig(): array
	{
		return [
			'FILTER_ID' => $this->getId(),
			'FILTER' => $this->getFilters($this->trashcanMode),
			'FILTER_PRESETS' => $this->getPresetFields($this->trashcanMode),
			'ENABLE_LIVE_SEARCH' => true,
			'ENABLE_LABEL' => true,
			'RESET_TO_DEFAULT_MODE' => true,
		];
	}

	private function getFilters(bool $trashcanMode): array
	{
		if ($trashcanMode)
		{
			return $this->getFiltersForTrashMode();
		}

		$filters = [
			[
				'id' => 'NAME',
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_NAME'),
				'default' => true,
			],
			[
				'id' => 'ID',
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_ID'),
				'type' => 'number',
			],
			[
				'id' => 'CREATE_TIME',
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_CREATE_TIME'),
				'type' => 'date',
				'time' => true,
			],
			[
				'id' => 'UPDATE_TIME',
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_UPDATE_TIME'),
				'type' => 'date',
				'time' => true,
			],
			Configuration::allowUseExtendedFullText() && ExtendedIndex::isReady() ? [
				'id' => 'SEARCH_BY_CONTENT',
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_SEARCH_BY_CONTENT'),
				'type' => 'checkbox',
				'default' => true,
				'valueType' => 'numeric',
			]: null,
			[
				'id' => 'SEARCH_IN_CURRENT_FOLDER',
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_SEARCH_IN_CURRENT_FOLDER'),
				'type' => 'checkbox',
				'default' => true,
				'valueType' => 'numeric',
			],
			[
				'id' => 'WITH_EXTERNAL_LINK',
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_WITH_EXTERNAL_LINK'),
				'type' => 'list',
				'default' => false,
				'items' => [
					self::WITH_EXTERNAL_LINK => Loc::getMessage('DISK_FOLDER_FILTER_WITH_EXTERNAL_LINK_YES')
				],
			],
			[
				'id' => 'SHARED',
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_SHARED'),
				'type' => 'list',
				'default' => false,
				'items' => [
					self::SHARED_FROM_ME => Loc::getMessage('DISK_FOLDER_FILTER_SHARED_FROM_ME'),
					self::SHARED_TO_ME => Loc::getMessage('DISK_FOLDER_FILTER_SHARED_TO_ME'),
				],
			],
		];

		return array_filter($filters);
	}

	/**
	 * @throws ObjectException
	 * @throws NotImplementedException
	 */
	private function getPresetFields(bool $trashcanMode): array
	{
		if ($trashcanMode)
		{
			return $this->getPresetFieldsForTrashMode();
		}

		Options::calcDates(
			'UPDATE_TIME',
			[
				'UPDATE_TIME_datesel' => DateType::CURRENT_WEEK
			],
			$sevenDayBefore
		);

		$userGender = User::loadById(CurrentUser::get()->getId())->getPersonalGender();
		$sharedFromMePhrase = empty($userGender)
			? Loc::getMessage('DISK_FOLDER_FILTER_PRESETS_SHARED_FROM_ME')
			: Loc::getMessage("DISK_FOLDER_FILTER_PRESETS_SHARED_FROM_ME_$userGender");

		return [
			'recently_updated' => [
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_PRESETS_RECENTLY_UPDATED'),
				'default' => false,
				'fields' => $sevenDayBefore
			],
			'with_external_link' => [
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_PRESETS_WITH_EXTERNAL_LINK'),
				'default' => false,
				'fields' => [
					'WITH_EXTERNAL_LINK' => self::WITH_EXTERNAL_LINK
				]
			],
			'shared_to_me' => [
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_PRESETS_SHARED_TO_ME'),
				'default' => false,
				'fields' => [
					'SHARED' => self::SHARED_TO_ME
				]
			],
			'shared_from_me' => [
				'name' => $sharedFromMePhrase,
				'default' => false,
				'fields' => [
					'SHARED' => self::SHARED_FROM_ME
				]
			],
		];
	}

	private function getFiltersForTrashMode(): array
	{
		return [
			[
				'id' => 'NAME',
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_NAME'),
				'default' => true,
			],
			[
				'id' => 'ID',
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_ID'),
				'type' => 'number',
			],
			[
				'id' => 'CREATE_TIME',
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_CREATE_TIME'),
				'type' => 'date',
				'time' => true,
			],
			[
				'id' => 'UPDATE_TIME',
				'name' => Loc::getMessage('DISK_FOLDER_FILTER_UPDATE_TIME'),
				'type' => 'date',
				'time' => true,
			],
			[
				'id' => 'DELETE_TIME',
				'name' => Loc::getMessage('DISK_TRASHCAN_FOLDER_FILTER_DELETE_TIME'),
				'type' => 'date',
				'time' => true,
			],
		];
	}

	/**
	 * @throws ObjectException
	 */
	private function getPresetFieldsForTrashMode(): array
	{
		Options::calcDates(
			'UPDATE_TIME',
			[
				'UPDATE_TIME_datesel' => DateType::CURRENT_WEEK
			],
			$sevenDayBeforeUpdated
		);

		Options::calcDates(
			'DELETE_TIME',
			[
				'DELETE_TIME_datesel' => DateType::CURRENT_WEEK
			],
			$sevenDayBeforeDeleted
		);

		return [
			'recently_deleted' => [
				'name' => Loc::getMessage('DISK_TRASHCAN_FOLDER_FILTER_PRESETS_RECENTLY_DELETED'),
				'default' => false,
				'fields' => $sevenDayBeforeDeleted
			],
			'recently_updated' => [
				'name' => Loc::getMessage('DISK_TRASHCAN_FOLDER_FILTER_PRESETS_RECENTLY_UPDATED'),
				'default' => false,
				'fields' => $sevenDayBeforeUpdated
			],
		];
	}
}