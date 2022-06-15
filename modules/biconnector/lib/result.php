<?php
namespace Bitrix\BIConnector;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Diag;

class Result extends \Bitrix\Main\DB\MysqliResult
{
	public function fetchArray()
	{
		return $this->resource->fetch_array(MYSQLI_NUM);
	}
}
