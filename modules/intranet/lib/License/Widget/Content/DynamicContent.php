<?php

namespace Bitrix\Intranet\License\Widget\Content;

abstract class DynamicContent extends BaseContent
{
	abstract public function getDynamicConfiguration(): array;
}
