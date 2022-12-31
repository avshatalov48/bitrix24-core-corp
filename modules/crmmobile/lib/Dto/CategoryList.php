<?php

namespace Bitrix\CrmMobile\Dto;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Dto;

final class CategoryList extends Dto
{
	/** @var Category[]|null */
	public $categories = [];

	/** @var Restriction[]|null */
	public $restrictions;

	/** @var bool */
	public $canUserEditCategory;

	public function getCasts(): array
	{
		return [
			'categories' => Type::collection(Category::class),
			'restrictions' => Type::collection(Restriction::class),
			'canUserEditCategory' => Type::bool(),
		];
	}
}