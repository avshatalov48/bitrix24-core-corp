<?php

namespace Bitrix\DocumentGenerator\DataProvider;

interface Filterable
{
	public static function getExtendedList();

	public function getFilterString();
}