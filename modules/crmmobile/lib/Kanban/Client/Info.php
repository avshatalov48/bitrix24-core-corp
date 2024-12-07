<?php

namespace Bitrix\CrmMobile\Kanban\Client;

use Bitrix\Main\ArgumentException;
use Bitrix\Crm\Integration\OpenLineManager;

final class Info
{
	private array $item;
	private string $type;
	private string $title;

	public static function get(array $item, string $type, string $title, bool $hidden = false): array
	{
		return (new self($item, $type, $title))->getByType($hidden);
	}

	private function __construct(array $item, string $type, string $title)
	{
		$this->item = $item;
		$this->type = $type;
		$this->title = $title;
	}

	private function getByType(bool $hidden = true): array
	{
		if (empty($this->item['FM']))
		{
			return [];
		}

		if ($this->type !== \CCrmOwnerType::ContactName && $this->type !== \CCrmOwnerType::CompanyName)
		{
			throw new ArgumentException('Unsupported contact type');
		}

		$data = [];
		foreach ($this->item['FM'] as $fmItem)
		{
			$fmType = $fmItem->getTypeId();
			$value = $fmItem->getValue();
			$complexName = $fmType . '_' . $fmItem->getValueType();
			$title = (OpenLineManager::isImOpenLinesValue($value) ? OpenLineManager::getOpenLineTitle($value) : '');

			$data[mb_strtolower($fmType)][] = [
				'value' => $value,
				'complexName' => \CCrmFieldMulti::GetEntityNameByComplex($complexName, false),
				'valueType' => $fmItem->getValueType(),
				'title' => ($title ?? ''),
			];
		}

		$type = mb_strtolower($this->type);

		return [
			$type => [
				array_merge($data, [
					'id' => $this->item['ID'],
					'title' => $this->title,
					'subtitle' => '',
					'type' => $this->type,
					'hidden' => $hidden,
					'hiddenInKanbanFields' => $hidden,
				]),
			],
		];
	}
}
