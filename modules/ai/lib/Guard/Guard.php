<?php

namespace Bitrix\AI\Guard;

interface Guard
{
	public function hasAccess(?int $userId = null): bool;
}