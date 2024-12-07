<?php

namespace Bitrix\Sign\Connector;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Connector\FieldCollection;

final class NullConnector implements Contract\Connector
{
	public function fetchFields(): FieldCollection
	{
		return new FieldCollection();
	}

	public function getName(): string
	{
		return '';
	}
}