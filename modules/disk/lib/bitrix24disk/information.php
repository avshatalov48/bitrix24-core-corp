<?php

namespace Bitrix\Disk\Bitrix24Disk;
use Bitrix\Disk\Driver;
use Bitrix\Disk\User;
use Bitrix\Main\Type\DateTime;
use CUserOptions;

final class Information
{
	private const OPTION_NAME = 'bdiskInformation';

	/** @var int */
	protected $userId;
	/** @var array */
	protected $state = [];

	/**
	 * Information constructor.
	 *
	 * @param $user
	 */
	public function __construct($user)
	{
		$this->userId = User::resolveUserId($user);
	}

	/**
	 * Returns disk space in bytes.
	 * @return int|null
	 */
	public function getDiskSpaceUsage(): ?int
	{
		return $this->getSavedInformation()['spaceUsage'] ?? null;
	}

	public function setDiskSpaceUsage(int $size)
	{
		$this->state['spaceUsage'] = (int)$size;

		return $this;
	}

	public function hasInstallationDatetime(): bool
	{
		return (bool)$this->getInstallationDatetime();
	}

	public function getInstallationDatetime(): ?DateTime
	{
		$savedInformation = $this->getSavedInformation();
		if (!empty($savedInformation['installationDate']))
		{
			return DateTime::createFromTimestamp($savedInformation['installationDate']);

		}

		return null;
	}

	public function setUninstallationDatetime($timestamp)
	{
		$this->state['installationDate'] = null;
		$this->state['uninstallationDate'] = (int)$timestamp;

		return $this;
	}

	public function setInstallationDatetime($timestamp)
	{
		$this->state['installationDate'] = (int)$timestamp;

		return $this;
	}

	private function saveInformation($information)
	{
		return CUserOptions::setOption(
			Driver::INTERNAL_MODULE_ID,
			self::OPTION_NAME,
			$information,
			false,
			$this->userId
		);
	}

	private function getSavedInformation()
	{
		return CUserOptions::getOption(
			Driver::INTERNAL_MODULE_ID,
			self::OPTION_NAME,
			[],
			$this->userId
		);
	}

	public function __destruct()
	{
		if (!empty($this->state))
		{
			$information = array_merge($this->getSavedInformation(), $this->state);
			$this->saveInformation($information);
		}
	}
}