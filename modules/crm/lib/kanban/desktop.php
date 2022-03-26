<?php


namespace Bitrix\Crm\Kanban;


use Bitrix\Crm\Kanban;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Web\Uri;

class Desktop extends Kanban
{
	/**
	 * @param array $status
	 * @return bool
	 */
	protected function isDropZone(array $status = []): bool
	{
		if (!isset($status['STATUS_ID']))
		{
			return false;
		}

		if (!empty($this->allowStages) && !in_array($status['STATUS_ID'], $this->allowStages, true))
		{
			return true;
		}

		if (in_array($status['STATUS_ID'], $this->allowStages, true))
		{
			return false;
		}

		if (
			(
				$status['PROGRESS_TYPE'] === 'WIN' &&
				!in_array(PhaseSemantics::SUCCESS, $this->allowSemantics, true)
			)
			|| (
				$status['PROGRESS_TYPE'] === 'LOOSE' &&
				!in_array(PhaseSemantics::FAILURE, $this->allowSemantics, true)
			)
			|| (
				$status['PROGRESS_TYPE'] !== 'WIN' &&
				$status['PROGRESS_TYPE'] !== 'LOOSE' &&
				!in_array(PhaseSemantics::PROCESS, $this->allowSemantics, true)
			)
		)
		{
			return true;
		}

		return false;
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
