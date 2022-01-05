<?php

namespace Bitrix\Tasks\Internals\Project;

use Bitrix\Main\Grid;

class Order
{
	private $gridId;

	public function __construct(string $gridId)
	{
		$this->gridId = $gridId;
	}

	private function getDefaultGridSorting(): array
	{
		return [
			'sort' => ['ACTIVITY_DATE' => 'desc'],
			'vars' => [
				'by' => 'by',
				'order' => 'order',
			],
		];
	}

	public function getGridSorting(): array
	{
		$gridOptions = new Grid\Options($this->gridId);

		return $gridOptions->getSorting($this->getDefaultGridSorting())['sort'];
	}

	public function getOrder(): array
	{
		$order = ['IS_PINNED' => 'desc'];

		return array_merge($order, $this->getGridSorting());
	}
}
