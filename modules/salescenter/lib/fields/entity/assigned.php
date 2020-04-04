<?php

namespace Bitrix\SalesCenter\Fields\Entity;

class Assigned extends User
{
	public function getCode(): string
	{
		return 'ASSIGNED_BY';
	}
}