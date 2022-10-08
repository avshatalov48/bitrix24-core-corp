<?php

namespace Bitrix\Disk\Internals\Engine;


use Bitrix\Disk\Type;
use Bitrix\Main\Engine\CurrentUser;

class Binder extends \Bitrix\Main\Engine\Binder
{
	public static function registerDefaultAutoWirings()
	{
		static::registerParameterDependsOnName(
			\Bitrix\Disk\Bitrix24Disk\TmpFile::class,
			function($className, $token) {
				/** @var \Bitrix\Disk\Bitrix24Disk\TmpFile $className */
				$filter = [
					'=TOKEN' => (string)$token
				];
				$userId = CurrentUser::get()->getId();
				if ($userId)
				{
					$filter['CREATED_BY'] = $userId;
				}

				return $className::load($filter);
			}
		);

		static::registerParameterDependsOnName(
			Type\ObjectCollection::class,
			function($className, $id) {
				/** @var Type\ObjectCollection $className */
				return $className::createByIds(...$id);
			},
			function(\ReflectionParameter $parameter) {
				return $parameter->getName();
			}
		);

		static::registerParameterDependsOnName(
			\Bitrix\Disk\Internals\Model::class,
			function($className, $id) {
				if (is_numeric($id) && $id <= 0)
				{
					return null;
				}

				/** @var \Bitrix\Disk\Internals\Model $className */
				return $className::getById($id);
			}
		);
	}
}