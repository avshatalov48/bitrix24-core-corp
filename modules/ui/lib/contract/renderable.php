<?php

namespace Bitrix\UI\Contract;

interface Renderable
{
	/**
	 * Returns content as string.
	 * @return string
	 */
	public function render();
}