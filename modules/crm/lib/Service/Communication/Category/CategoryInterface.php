<?php

namespace Bitrix\Crm\Service\Communication\Category;

interface CategoryInterface
{
	public function getTitle(): string;
	public function getDescription(): string;
	public function getLogoPath(): string;
}
