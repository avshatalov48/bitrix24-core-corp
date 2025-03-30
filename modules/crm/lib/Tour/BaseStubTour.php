<?php

namespace Bitrix\Crm\Tour;


abstract class BaseStubTour
{
	abstract public function getTitle(): string;

	abstract public function getText(): string;

	public function getOptionCategory(): string
	{
		return 'crm';
	}

	abstract public function getOptionName(): string;
}
