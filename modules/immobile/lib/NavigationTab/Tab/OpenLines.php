<?php

namespace Bitrix\ImMobile\NavigationTab\Tab;

use Bitrix\ImMobile\NavigationTab\MessengerComponentTitle;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Mobile\Context;

class OpenLines extends BaseRecent
{
	use MessengerComponentTitle;
	
	private ?Context $context = null;
	
	public function __construct(Context $context)
	{
		parent::__construct();

		$this->context = $context;
	}
	
	public function isAvailable(): bool
	{
		if ($this->context->extranet)
		{
			return false;
		}

		if ($this->context->isCollaber)
		{
			return false;
		}
		
		if (!ModuleManager::isModuleInstalled('imopenlines'))
		{
			return false;
		}
		
		return true;
	}
	
	public function getId(): string
	{
		return 'openlines';
	}
	
	protected function getTabTitle(): ?string
	{
		return Loc::getMessage('TAB_NAME_IM_OPENLINES_SHORT');
	}
	
	protected function getComponentCode(): string
	{
		return 'im.openlines.recent';
	}
	
	protected function getComponentName(): string
	{
		return 'im:im.recent';
	}
	
	protected function getParams(): array
	{
		return [
			'TAB_CODE' => 'openlines',
			'COMPONENT_CODE' => 'im.openlines.recent',
			'MESSAGES' => [
				'COMPONENT_TITLE' => $this->getTitle(),
			]
		];
	}
	
	protected function getWidgetSettings(): array
	{
		return [
			'preload' => false,
			'useSearch' => false,
			'titleParams' => [
				'useLargeTitleMode' => true,
				'text' => $this->getTitle(),
			],
		];
	}
}