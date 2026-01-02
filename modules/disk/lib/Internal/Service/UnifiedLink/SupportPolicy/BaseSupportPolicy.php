<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service\UnifiedLink\SupportPolicy;

use Bitrix\Disk\File;
use Bitrix\Disk\Internal\Service\UnifiedLink\Configuration;
use Bitrix\Disk\Internal\Service\UnifiedLink\UniqueCodeBackfiller;

class BaseSupportPolicy implements SupportPolicy
{
	/** @var array<int, bool> Cache of supported file types. */
	private array $supportedFileTypes = [];

	public function __construct(
		private readonly UniqueCodeBackfiller $uniqueCodeBackfiller,
	)
	{
	}

	/**
	 * Checks if the given file has unified link support.
	 * @param File $file
	 * @return bool
	 */
	final public function supports(File $file): bool
	{
		return $this->passesBaseSupportChecks($file)
			&& $this->passesSpecializedChecks($file);
	}

	/**
	 * Basic checks common to all support policies.
	 * Has side effects: backfills the file's unique code.
	 * @param File $file
	 * @return bool
	 */
	final protected function passesBaseSupportChecks(File $file): bool
	{
		$uniqueCode = $file->getUniqueCode();
		$fileType = (int)$file->getTypeFile();

		$isSetUniqueCode = (string)$uniqueCode !== '';
		$passesConfigurationChecks = $this->passesConfigurationChecks($fileType);

		if (!$isSetUniqueCode && $passesConfigurationChecks)
		{
			$isSetUniqueCode = $this->uniqueCodeBackfiller->backfillUniqueCode((int)$file->getId());
		}

		return $isSetUniqueCode
			&& $passesConfigurationChecks;
	}

	/**
	 * Checks against configuration settings.
	 * @param int $fileType
	 * @return bool
	 */
	final protected function passesConfigurationChecks(int $fileType): bool
	{
		$this->supportedFileTypes[$fileType] ??= (
			Configuration::isEnabled()
			&& Configuration::isFileTypeAllowed($fileType)
		);

		return $this->supportedFileTypes[$fileType];
	}

	/**
	 * Additional (specialized) checks.
	 * Can be extended by descendants if necessary.
	 * @param File $file
	 * @return bool
	 */
	protected function passesSpecializedChecks(File $file): bool
	{
		return true;
	}
}
