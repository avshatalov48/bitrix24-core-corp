<?php

namespace Bitrix\Crm\Service\Timeline\Layout;

class RestHeader extends Header
{
	public function toArray(): array
	{
		$data = parent::toArray();

		if ($data['date'] ?? null)
		{
			$data['date'] = $data['date'] instanceof \Bitrix\Main\Type\DateTime
				? \CRestUtil::ConvertDateTime($data['date']->toString())
				: null
			;
		}

		if ($data['user'] ?? null)
		{
			$data['userId'] = $data['user'] instanceof \Bitrix\Crm\Service\Timeline\Layout\User
				? $data['user']->getId()
				: null
			;
		}
		unset($data['changeStreamButton']);
		unset($data['user']);

		return $data;
	}
}
