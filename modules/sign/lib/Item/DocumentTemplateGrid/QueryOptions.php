<?php

namespace Bitrix\Sign\Item\DocumentTemplateGrid;

use Bitrix\Sign\Contract;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class QueryOptions implements Contract\Item
{
	public function __construct(
	public ConditionTree $filter,
	public int $limit = 10,
	public int $offset = 0,
	) {}
}