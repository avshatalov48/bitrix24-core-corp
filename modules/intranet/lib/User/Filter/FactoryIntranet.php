<?php

namespace Bitrix\Intranet\User\Filter;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\ModuleManager;

class FactoryIntranet
{
	public static function onBuildFilterFactoryMethods(Event $event): EventResult
	{
		return new EventResult(
			EventResult::SUCCESS,
			[
				'callbacks' => [
					\Bitrix\Main\UserTable::getUfId() => function($entityTypeName, array $settingsParams, array $additionalParams = null) {

						if ($entityTypeName == \Bitrix\Main\UserTable::getUfId())
						{
							$settings = new IntranetUserSettings($settingsParams);
							$filterID = $settings->getID();

							$extraProviders = [
								new \Bitrix\Main\Filter\UserUFDataProvider($settings),
								new \Bitrix\Intranet\User\Filter\Provider\IntranetUserDataProvider($settings),
								new \Bitrix\Intranet\User\Filter\Provider\IntegerUserDataProvider($settings),
								new \Bitrix\Intranet\User\Filter\Provider\StringUserDataProvider($settings),
								new \Bitrix\Intranet\User\Filter\Provider\DateUserDataProvider($settings),
								new \Bitrix\Intranet\User\Filter\Provider\PhoneUserDataProvider($settings),
							];

							if (ModuleManager::isModuleInstalled('extranet'))
							{
								$extranetSettings = new ExtranetUserSettings($settingsParams);
								$extraProviders[] = new \Bitrix\Intranet\User\Filter\Provider\ExtranetUserDataProvider($extranetSettings);
							}

							return new \Bitrix\Main\Filter\Filter(
								$filterID,
								new \Bitrix\Main\Filter\UserDataProvider($settings),
								$extraProviders,
							);
						}
					}
				]
			]
		);
	}
}