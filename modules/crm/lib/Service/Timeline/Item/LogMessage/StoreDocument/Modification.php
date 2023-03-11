<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\StoreDocument;

abstract class Modification extends Base
{
	public function getType(): string
	{
		return sprintf(
			'StoreDocument%s:Modification',
			$this->getConcreteType()
		);
	}
}
