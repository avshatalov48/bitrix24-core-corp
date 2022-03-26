<?php

namespace Bitrix\Crm\WebForm\Options\Integration;

use Bitrix\Crm\WebForm\Options\Integration\Compatible;
use Bitrix\Crm\WebForm\Options\Integration;
use Bitrix\Main\NotImplementedException;

final class Factory
{
	public const TYPE_VKONTAKTE = "vkontakte";

	public const TYPE_FACEBOOK = "facebook";

	/**
	 * Build compatible fields' mapper by type
	 *
	 * @param string $type
	 * @param Integration $integration
	 *
	 * @return IFieldMapper
	 * @throws NotImplementedException
	 */
	public static function getCompatibleFieldsMapper(string $type, Integration $integration) : Integration\IFieldMapper
	{
		switch ($type)
		{
			case self::TYPE_VKONTAKTE:
					return new Compatible\VkontakteFieldsMapper($integration->getForm());
			case self::TYPE_FACEBOOK :
					return new Compatible\FacebookFieldsMapper($integration->getForm());
			default:
				throw new NotImplementedException("$type is not implemented");
		}
	}

	/**
	 * Build fields mapper by type and mappings
	 *
	 * @param string $type
	 * @param array $mappings
	 * @param Integration $integration
	 *
	 * @return IFieldMapper
	 * @throws NotImplementedException
	 */
	public static function getFieldsMapper(
		string $type,
		array $mappings,
		Integration $integration
	) : Integration\IFieldMapper
	{
		switch ($type)
		{
			case self::TYPE_VKONTAKTE:
				return new Integration\VkontakteFieldsMapper($mappings,$integration->getForm());
			case self::TYPE_FACEBOOK:
				return new Integration\FacebookFieldsMapper($mappings,$integration->getForm());
			default:
				throw new NotImplementedException("$type is not implemented");
		}
	}
}
