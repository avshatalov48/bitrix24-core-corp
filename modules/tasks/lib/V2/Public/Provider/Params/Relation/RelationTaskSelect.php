<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Provider\Params\Relation;

use Bitrix\Main\Provider\Params\SelectInterface;

class RelationTaskSelect implements SelectInterface
{
	private const ALLOWED_FIELDS = [
		'id',
		'title',
		'responsible',
		'deadline',
		'parentId',
	];

	public function __construct(
		public array $select = [],
	)
	{
	}

	public function prepareSelect(): array
	{
		return array_filter(
			$this->select,
			static fn (mixed $field): bool =>
				is_string($field) && in_array($field, self::ALLOWED_FIELDS, true),
		);
	}
}
