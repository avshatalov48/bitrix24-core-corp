<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Main\Engine\AutoWire\ExactParameter;

final class Storage extends Engine\Controller
{
	public function getPrimaryAutoWiredParameter()
	{
		return new ExactParameter(Disk\Storage::class, 'storage', function($className, $id){
			return Disk\Storage::loadById($id);
		});
	}

	public function isEnabledSizeLimitRestrictionAction(Disk\Storage $storage)
	{
		if ($storage->isEnabledSizeLimitRestriction())
		{
			return [
				'isEnabledSizeLimitRestriction' => $storage->isEnabledSizeLimitRestriction(),
				'sizeLimitRestriction' => $storage->getSizeLimit(),
			] ;
		}

		return [
			'isEnabledSizeLimitRestriction' => false,
		];
	}
}