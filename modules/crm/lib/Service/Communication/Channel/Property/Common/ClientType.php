<?php

namespace Bitrix\Crm\Service\Communication\Channel\Property\Common;

use Bitrix\Crm\Service\Communication\Channel\Property\PropertiesManager;
use Bitrix\Crm\Service\Communication\Utils\Common;
use Bitrix\Main\Localization\Loc;

//@todo maybe this class should be extends Bitrix\Crm\Service\Communication\Channel\Property\Property
// and must be another class than implements the init method
final class ClientType extends BaseType
{
	public const CODE = 'clientType';

	public const NEW_STATUS = 'NEW';
	public const KNOWN_STATUS = 'KNOWN';
	public const AT_WORK_STATUS = 'AT_WORK';
	public const IN_BLACKLIST_STATUS = 'IN_BLACKLIST';

	public function getValue(array $params = []): string
	{
		foreach ($this->bindings as $binding)
		{
			$entityTypeId = $binding['OWNER_TYPE_ID'] ?? null;
			$entityId = $binding['OWNER_ID'] ?? null;

			if (!\CCrmOwnerType::IsDefined($entityTypeId) || $entityId < 0)
			{
				continue;
			}

			if (Common::isClientEntityTypeId($entityTypeId))
			{
				return self::KNOWN_STATUS; // @todo must support AT_WORK_STATUS too
			}
		}

		return self::NEW_STATUS;
	}

	public function getCode(): string
	{
		return self::CODE;
	}

	public function getTitle(): string
	{
		return Loc::getMessage('CRM_COMMUNICATION_CHANNEL_PROPERTY_CLIENT_TYPE_TITLE');
	}

	public function getType(): string
	{
		return PropertiesManager::TYPE_ENUMERATION;
	}

	protected function getPropertyParams(): array
	{
		$params = parent::getPropertyParams();

		$params['list'] = [
			self::NEW_STATUS => Loc::getMessage('CRM_COMMUNICATION_CHANNEL_PROPERTY_CLIENT_TYPE_NEW'),
			self::KNOWN_STATUS => Loc::getMessage('CRM_COMMUNICATION_CHANNEL_PROPERTY_CLIENT_TYPE_KNOWN'),
			self::AT_WORK_STATUS => Loc::getMessage('CRM_COMMUNICATION_CHANNEL_PROPERTY_CLIENT_TYPE_AT_WORK'),
			self::IN_BLACKLIST_STATUS => Loc::getMessage('CRM_COMMUNICATION_CHANNEL_PROPERTY_CLIENT_TYPE_IN_BLACKLIST'),
		];

		return $params;
	}
}
