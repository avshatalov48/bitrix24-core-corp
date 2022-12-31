<?php

namespace Bitrix\CrmMobile\Kanban\Dto;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\UI\SimpleList\Dto\Data;

final class ItemData extends Data
{
	/** @var array|null */
	public ?array $permissions;

	/** @var string|null */
	public ?string $dateFormatted;

	/** @var bool|null */
	public ?bool $return;

	/** @var bool|null */
	public ?bool $returnApproach;

	/** @var string|null */
	public ?string $subTitleText;

	/** @var float|null */
	public ?float $price;

	/** @var string|null */
	public ?string $columnId;

	/** @var array|null */
	public ?array $descriptionRow;

	/** @var array|null */
	public ?array $client;

	/** @var array|null */
	public ?array $money;

	/** @var array|null */
	public ?array $counters;

	/** @var array|null */
	public ?array $badges;

	public function getCasts(): array
	{
		return array_merge(parent::getCasts(), [
			'dateFormatted' => Type::string(),
			'price' => Type::float(),
			'return' => Type::bool(),
			'returnApproach' => Type::bool(),
			'subTitleText' => Type::string(),
			'columnId' => Type::string(),
		]);
	}
}
