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
					'USER_INTRANET' => function($entityTypeName, array $settingsParams, array $additionalParams = null) {

						if ($entityTypeName == 'USER_INTRANET')
						{
							$settings = ModuleManager::isModuleInstalled('extranet')
								? new ExtranetUserSettings($settingsParams)
								: new IntranetUserSettings($settingsParams);
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
								$extraProviders[] = new \Bitrix\Intranet\User\Filter\Provider\ExtranetUserDataProvider($settings);
							}

							return new \Bitrix\Main\Filter\Filter(
								$filterID,
								new \Bitrix\Main\Filter\UserDataProvider($settings),
								$extraProviders,
							);
						}
					}
				]
			],
			'intranet',
		);
	}
}