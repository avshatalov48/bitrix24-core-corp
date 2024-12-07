<?php

namespace Bitrix\Sign\Type\Field;

final class ConnectorType
{
	public const CRM_ENTITY = 'crmEntity';
	public const REQUISITE = 'requisite';
	public const STATIC = 'static';

	/**
	 * @return list<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::CRM_ENTITY,
			self::STATIC,
		];
	}
}