<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document\Contract;

use Bitrix\Disk\Document\DocumentSessionResult;

interface DocumentSessionFactory
{
	public function createInternalSession(int $type): DocumentSessionResult;

	public function createExternalSession(int $type, int $externalLinkId): DocumentSessionResult;
}