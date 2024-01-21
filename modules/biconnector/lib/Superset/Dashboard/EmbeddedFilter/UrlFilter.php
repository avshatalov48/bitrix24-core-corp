<?php

namespace Bitrix\BIConnector\Superset\Dashboard\EmbeddedFilter;

abstract class UrlFilter
{
	abstract public function getCode(): string;

	abstract function getFormatted(): string;
}