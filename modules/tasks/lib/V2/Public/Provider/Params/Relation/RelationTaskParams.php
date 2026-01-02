<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Provider\Params\Relation;

use Bitrix\Main\Provider\Params\GridParams;
use Bitrix\Main\Provider\Params\PagerInterface;
use Bitrix\Main\Provider\Params\SelectInterface;
use Bitrix\Main\Provider\Params\SortInterface;

class RelationTaskParams extends GridParams
{
	public function __construct(
		public int $userId,
		public int $taskId,
		public PagerInterface $pager,
		public bool $checkRootAccess = true,
		public ?SelectInterface $select = null,
	)
	{
		parent::__construct(
			pager: $pager,
			select: $select,
		);
	}
}
