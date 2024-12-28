<?php

namespace Bitrix\Extranet\Model\Mapper;

use Bitrix\Extranet\Entity;
use Bitrix\Extranet\Model;

class ExtranetUserCollection
{
	public function map(Model\EO_ExtranetUser_Collection $modelCollection): Entity\Collection\ExtranetUserCollection
	{
		$modelMapper = new ExtranetUser();
		$extranetUserCollection = new Entity\Collection\ExtranetUserCollection();

		foreach ($modelCollection as $model)
		{
			$extranetUserCollection->add($modelMapper->map($model));
		}

		return $extranetUserCollection;
	}
}
