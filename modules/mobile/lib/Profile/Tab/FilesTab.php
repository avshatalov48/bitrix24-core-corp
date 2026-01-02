<?php

namespace Bitrix\Mobile\Profile\Tab;

use Bitrix\Disk\Folder;
use Bitrix\Disk\UrlManager;
use Bitrix\Disk\Security\DiskSecurityContext;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Profile\Enum\TabContextType;
use Bitrix\Mobile\Profile\Enum\TabType;

class FilesTab extends BaseProfileTab
{
	private Folder $folder;

	/**
	 * @return TabType
	 */
	public function getType(): TabType
	{
		return TabType::FILES;
	}

	/**
	 * @return TabContextType
	 */
	public function getContextType(): TabContextType
	{
		return TabContextType::WIDGET;
	}

	/**
	 * @return bool
	 */
	public function isAvailable(): bool
	{
		if (!Loader::includeModule('disk') || !Loader::includeModule('diskmobile'))
		{
			return false;
		}

		return $this->isFolderPathResolved();
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return Loc::getMessage('PROFILE_TAB_FILES_TITLE');
	}

	public function getParams(): array
	{
		return [
			'folderId' => $this->folder->getId(),
			'context' => [
				'storageId' => $this->folder->getStorageId(),
			],
		];
	}

	private function isFolderPathResolved(): bool
	{
		if (!method_exists(UrlManager::class, 'resolveFolderPath'))
		{
			return false;
		}

		$result = (new UrlManager())->resolveFolderPath('user', $this->ownerId, '/');
		if (!$result->isSuccess())
		{
			return false;
		}

		$this->folder = $result->getData()['targetFolder'];

		return true;
	}
}
