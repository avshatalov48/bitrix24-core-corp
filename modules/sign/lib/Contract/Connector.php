<?php

namespace Bitrix\Sign\Contract;


use Bitrix\Sign\Item\Connector\FieldCollection;

interface Connector
{
	public function fetchFields(): FieldCollection;
	public function getName(): string;
}