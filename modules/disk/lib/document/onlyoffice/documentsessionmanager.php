<?php

namespace Bitrix\Disk\Document\OnlyOffice;

use Bitrix\Disk\Document\Models\DocumentService;
use Bitrix\Disk\Document\SessionManager;

final class DocumentSessionManager extends SessionManager
{
	protected DocumentService|null $service = DocumentService::OnlyOffice;

	protected function buildFilter(): array
	{
		$filter = parent::buildFilter();
		$filter['TYPE'] = $this->sessionType;

		return $filter;
	}
}