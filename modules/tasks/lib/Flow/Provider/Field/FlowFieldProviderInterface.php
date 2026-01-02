<?php

namespace Bitrix\Tasks\Flow\Provider\Field;

interface FlowFieldProviderInterface
{
	public function getModifiedFields(): array;
}