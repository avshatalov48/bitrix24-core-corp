<?php

namespace Bitrix\Mobile\Field\Type;

interface HasBoundEntities
{
	/**
	 * @return array
	 */
	public function getBoundEntities(): array;
}
