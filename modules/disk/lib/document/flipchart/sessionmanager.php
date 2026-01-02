<?php

namespace Bitrix\Disk\Document\Flipchart;

use Bitrix\Disk\Document\Models\DocumentService;
use Bitrix\Disk\Document\SessionManager as BaseSessionManager;
use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Main\ArgumentException;

final class SessionManager extends BaseSessionManager
{
	protected DocumentService|null $service = DocumentService::FlipChart;

	protected function buildFilter(): array
	{
		$filter = parent::buildFilter();
		if (!is_null($this->sessionType))
		{
			$filter['TYPE'] = $this->sessionType;
		}

		return $filter;
	}

	/**
	 * @return DocumentSession[]
	 * @throws ArgumentException
	 */
	public function findAllSessions(): array
	{
		$filter = $this->buildFilter();
		unset($filter['USER_ID']);

		return DocumentSession::getModelList([
			'select' => ['*'],
			'filter' => $filter,
			'order' => ['ID' => 'DESC'],
		]);
	}
}