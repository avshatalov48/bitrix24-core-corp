<?php

namespace Bitrix\Intranet\Site\FirstPage;

use Bitrix\Main\Web\Uri;

interface FirstPage
{
	public function isEnabled(): bool;

	public function getUri(): Uri;
}