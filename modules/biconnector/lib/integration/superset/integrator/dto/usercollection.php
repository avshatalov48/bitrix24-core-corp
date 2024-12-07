<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator\Dto;

class UserCollection extends Collection
{
	public function getClientIdList(): array
	{
		$idList = [];

		/** @var User $user */
		foreach ($this->collection as $user)
		{
			if ($user->clientId)
			{
				$idList[] = $user->clientId;
			}
		}

		return $idList;
	}
}
