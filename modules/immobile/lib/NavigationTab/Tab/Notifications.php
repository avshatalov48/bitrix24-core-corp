<?php

namespace Bitrix\ImMobile\NavigationTab\Tab;

use Bitrix\ImMobile\NavigationTab\MessengerComponentTitle;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Context;
use Bitrix\MobileApp;

class Notifications implements TabInterface
{
	use MessengerComponentTitle;
	
	private Context $context;
	
	public function __construct(Context $context)
	{
		$this->context = $context;
	}
	
	public function isAvailable(): bool
	{
		return true;
	}
	
	public function isNeedMergeSharedParams(): bool
	{
		return false;
	}
	
	public function getId(): string
	{
		return 'notifications';
	}
	
	public function getComponentData(): ?array
	{
		return [
			'id' => $this->getId(),
			'title' => Loc::getMessage('TAB_NAME_NOTIFY'),
			'component' => [
				'name' => 'JSStackComponent',
				'componentCode' => 'im.notify.legacy',
				'scriptPath' => MobileApp\Janative\Manager::getComponentPath('im:im.notify.legacy'),
				'params' => [
					'MESSAGES' => [
						'COMPONENT_TITLE' => $this->getTitle(),
					]
				],
				'rootWidget' => [
					'name' => 'web',
					'settings' => [
						'page' => [
							'url' => $this->context->siteDir . 'mobile/im/notify.php?navigation',
							'preload' => false,
						],
						'objectName' => 'widget',
						'titleParams' => [
							'useLargeTitleMode' => true,
							'text' => $this->getTitle(),
						],
					],
				],
			],
		];
	}
	
	public function mergeParams(array $params): void
	{}
}