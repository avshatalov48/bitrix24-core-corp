<?php

namespace Bitrix\Sign\Type\Access;

final class AccessibleItemType
{
	public const DOCUMENT = 'document';
	public const TEMPLATE = 'template';
	public const TEMPLATE_FOLDER = 'templateFolder';
	public const SIGNERS_LIST = 'signers_list';

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::DOCUMENT,
			self::TEMPLATE,
			self::TEMPLATE_FOLDER,
			self::SIGNERS_LIST,
		];
	}
}
