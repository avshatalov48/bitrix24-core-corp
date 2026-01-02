<?php
namespace Bitrix\Mobile\AppTabs;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Crm\Terminal\AvailabilityManager;
use Bitrix\Mobile\Tab\Utils;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\Mobile\Config\Feature;
use Bitrix\Mobile\Feature\MenuFeature;

class Terminal implements Tabable
{

	/**
	 * @var \Bitrix\Mobile\Context $context
	 */
	private $context;

	public function isAvailable()
	{
		return (!$this->context->extranet
			&& Loader::includeModule('crm')
			&& Container::getInstance()->getIntranetToolsManager()->checkTerminalAvailability()
			&& AvailabilityManager::getInstance()->isAvailable()
		);
	}

	public function getData()
	{
		return [
			'id' => 'terminal',
			'sort' => $this->defaultSortValue(),
			'imageName' => $this->getIconId(),
			'badgeCode' => 'terminal',
			'component' => $this->getComponentParams(),
		];
	}

	public function getMenuData()
	{
		return [
			'id' => $this->getId(),
			'title' => Feature::isEnabled(MenuFeature::class)
				? Loc::getMessage('TAB_NAME_TERMINAL_MENU_TITLE')
				: $this->getTitle(),
			'useLetterImage' => true,
			'sectionCode' => 'terminal',
			'section_code' => 'crm',
			'sort' => 500,
			'color' => '#0169B3',
			'imageUrl' => 'terminal/terminal.png',
			'imageName' => $this->getIconId(),
			'params' => [
				'onclick' => Utils::getComponentJSCode($this->getComponentParams()),
				'analytics' => [
					'tool' => 'sale',
					'category' => 'terminal',
					'event' => 'open_section',
					'c_section' => 'ava_menu',
				],
			],
		];
	}

	private function getComponentParams(): array
	{
		return [
			'name' => 'JSStackComponent',
			'title' => $this->getTitle(),
			'componentCode' => "crm:crm.terminal.list",
			'scriptPath' => Manager::getComponentPath('crm:crm.terminal.list'),
			'rootWidget' => [
				'name' => 'layout',
				'settings' => [
					'objectName' => 'layout',
					'titleParams' => [
						'useLargeTitleMode' => true,
						'text' => $this->getTitle(),
					],
				],
			],
			'params' => [],
		];
	}

	public function shouldShowInMenu()
	{
		return $this->isAvailable();
	}

	public function canBeRemoved()
	{
		return true;
	}

	public function defaultSortValue()
	{
		return 100;
	}

	public function canChangeSort()
	{
		return true;
	}

	public function getTitle()
	{
		return Loc::getMessage("TAB_NAME_TERMINAL");
	}

	public function getShortTitle()
	{
		return Loc::getMessage("TAB_NAME_TERMINAL");
	}

	public function getId()
	{
		return 'terminal';
	}

	public function setContext($context)
	{
		$this->context = $context;
	}

	public function getIconId(): string
	{
		return 'payment_terminal';
	}
}
