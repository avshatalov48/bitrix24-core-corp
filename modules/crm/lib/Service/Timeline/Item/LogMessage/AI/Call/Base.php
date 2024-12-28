<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\AI\Call;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Client;

abstract class Base extends LogMessage
{
	public function getContentBlocks(): ?array
	{
		$result = [];

		$clientBlock = $this->buildClientBlock(Client::BLOCK_WITH_FORMATTED_VALUE);
		if (isset($clientBlock))
		{
			$result['client'] = $clientBlock;
		}

		return $result;
	}
}
