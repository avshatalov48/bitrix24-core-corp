<?php

namespace Bitrix\Sign\Helper;

final class CloneHelper
{
	/**
	 * @template ObjT of object
	 * @template T of ObjT|null
	 * @psalm-param T $value
	 *
	 * @return T
	 * @psalm-return (T is null ? null : ObjT)
	 */
	public static function cloneIfNotNull(?object $value): ?object
	{
		return $value !== null ? clone $value : null;
	}
}