<?php


namespace Bitrix\Crm\Kanban;


use Bitrix\Crm\Kanban;
use Bitrix\Main\Web\Uri;

class Desktop extends Kanban
{
	/**
	 * @param array $status
	 * @return bool
	 */
	protected function isDropZone(array $status = []): bool
	{
		if ($this->viewMode === ViewMode::MODE_DEADLINES)
		{
			return false;
		}

		return parent::isDropZone($status);
	}

	protected function getPathToImport(): string
	{
		if (!empty($this->params['PATH_TO_IMPORT']))
		{
			$uriImport = new Uri($this->params['PATH_TO_IMPORT']);
			$importUriParams = [
				'from' => 'kanban',
			];
			if ($this->entity->getCategoryId() > 0)
			{
				$importUriParams['category_id'] = $this->entity->getCategoryId();
			}
			$uriImport->addParams($importUriParams);
			return $uriImport->getUri();
		}

		return '';
	}

	protected function prepareComponentParams(array &$params): void
	{
		parent::prepareComponentParams($params);
		$params['PATH_TO_IMPORT'] = $this->getPathToImport();
	}
}
