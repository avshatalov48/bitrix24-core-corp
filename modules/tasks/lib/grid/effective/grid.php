<?php
namespace Bitrix\Tasks\Grid\Effective;

use Bitrix\Main\LoaderException;

/**
 * Class Grid
 * @package Bitrix\Tasks\Grid\Effective
 */
class Grid extends \Bitrix\Tasks\Grid
{
	/**
	 * @return array[]
	 * @throws LoaderException
	 */
	public function prepareHeaders(): array
	{
		return [];
	}

	/**
	 * @return array
	 */
	public function prepareRows(): array
	{
		$preparedRows = [];

		foreach ($this->getRows() as $key => $data)
		{
			$row = new Row($data, $this->getParameters());
			$preparedRows[$key] = [
				'actions' => $row->prepareActions(),
				'content' => $row->prepareContent(),
				'cellActions' => $row->prepareCellActions(),
				'counters' => $row->prepareCounters(),
			];
		}

		return $preparedRows;
	}

	public function prepareGroupActions(): array
	{
		return [];
	}
}