<?php
declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service\UnifiedLink;

use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\File;
use Bitrix\Disk\Internal\Service\UnifiedLink\SupportPolicy\SupportPolicyFactory;
use Bitrix\Disk\TypeFile;

class UnifiedLinkSupportService
{
	public function __construct(
		private readonly SupportPolicyFactory $factory,
	)
	{
	}

	public function supports(File $file): bool
	{
		return $this->factory->create($file)->supports($file);
	}

	public function supportsDocumentHandler(DocumentHandler $documentHandler): bool
	{
		return Configuration::isEnabled()
			&& Configuration::isFileTypeAllowed(TypeFile::DOCUMENT)
			&& Configuration::isDocumentHandlerAllowed($documentHandler);
	}
}
