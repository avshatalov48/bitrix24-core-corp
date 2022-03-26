<?php

namespace Bitrix\Crm\Service\WebForm\Scenario\DependencyScenario;

use Bitrix\Crm\WebForm\Internals\FieldDepGroupTable;

class DependencyItem
{
	/**
	 * @var string
	 */
	private $logic;

	/**
	 * @var array
	 */
	private $list;

	/**
	 * @var array
	 */
	private $type;

	private function __construct(array $list, string $logic = 'or', int $type =  FieldDepGroupTable::TYPE_DEF)
	{
		$this->logic = $logic;
		$this->list = $list;
		$this->type = $type;
	}

	/**
	 * Short calling for get DependencyAction class instance.
	 *
	 * @param DependencyListItem[] $items
	 * @param string $logic
	 * @return DependencyItem
	 */
	public static function of(array $items, string $logic = 'or', int $type =  FieldDepGroupTable::TYPE_DEF): DependencyItem
	{
		$listItems = [];
		foreach ($items as $item)
		{
			$listItems[] = $item->toArray();
		}
		return new self($listItems, $logic, $type);
	}

	public function toArray(): array
	{
		return [
			'logic' => $this->logic,
			'list' => $this->list,
			'typeId' => $this->type,
		];
	}
}