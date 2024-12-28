<?php

namespace Bitrix\Tasks\Provider\Query;

use Bitrix\Main\ORM\Query\Query;

interface QueryBuilderInterface
{
	public static function build(TaskQueryInterface $query): Query;
}
