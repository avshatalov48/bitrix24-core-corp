<?php

namespace Bitrix\Market;

interface Loadable
{
	public function getAjaxData($params): array;
}
