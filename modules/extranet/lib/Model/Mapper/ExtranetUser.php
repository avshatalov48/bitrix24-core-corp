<?php

namespace Bitrix\Extranet\Model\Mapper;

use Bitrix\Extranet\Entity;
use Bitrix\Extranet\Model;

class ExtranetUser
{
	public function map(Model\EO_ExtranetUser $model): Entity\ExtranetUser
	{
		return new Entity\ExtranetUser(
			userId: $model->getUserId(),
			role: $model->getRole(),
			id: $model->getId(),
			user: $model->getUser(),
		);
	}
}
