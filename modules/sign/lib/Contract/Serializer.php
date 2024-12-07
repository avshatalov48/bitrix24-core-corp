<?php

namespace Bitrix\Sign\Contract;

interface Serializer
{
	public function serialize(Item $item): array;
}