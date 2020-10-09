<?php
namespace Bitrix\Tasks\Integration\Disk\Connector;

use Bitrix\Disk\Uf\StubConnector;

class ScrumItem extends StubConnector
{
	public function canRead($userId)
	{
		return true;
	}

	public function canUpdate($userId)
	{
		return true;
	}
}