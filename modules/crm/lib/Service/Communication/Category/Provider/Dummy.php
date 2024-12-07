<?php

namespace Bitrix\Crm\Service\Communication\Category\Provider;

final class Dummy implements \Bitrix\Crm\Service\Communication\Category\CategoryInterface
{
	public function getTitle(): string
	{
		return 'Dummy Category';
	}

	public function getDescription(): string
	{
		return 'Dummy Description';
	}

	public function getLogoPath(): string
	{
		return '';
	}
}
