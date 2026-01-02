<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service\UnifiedLink\SupportPolicy;

use Bitrix\Disk\File;
use Bitrix\Disk\TypeFile;

/**
 * UnifiedLink support policy factory by file types.
 * By default, returns BaseSupportPolicy for unknown types.
 */
final class SupportPolicyFactory
{
	/** @var array<int, SupportPolicy> */
	private readonly array $registry;

	public function __construct(
		private readonly BaseSupportPolicy $baseSupportPolicy,
		private readonly DocumentSupportPolicy $documentSupportPolicy,
	)
	{
		$this->registry = [
			TypeFile::DOCUMENT => $this->documentSupportPolicy,
			TypeFile::PDF => $this->documentSupportPolicy,
		];
	}

	/**
	 * Returns the policy for the given file.
	 * Unknown types receive BaseSupportPolicy.
	 * @param File $file
	 * @return SupportPolicy
	 */
	public function create(File $file): SupportPolicy
	{
		$fileType = (int)$file->getTypeFile();

		return $this->registry[$fileType] ?? $this->baseSupportPolicy;
	}

}
