<?php

namespace Bitrix\BizprocMobile;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Mobile\Tab\Utils;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\MobileApp\Mobile;

class BizpocTab implements Tabable
{
	private const INITIAL_COMPONENT = 'bizproc:tab';
	private const MINIMAL_API_VERSION = 52;

	/** @var Context $context */
	private $context;

	public function isAvailable(): bool
	{
		if (!Loader::includeModule('bizproc') || !Loader::includeModule('bizprocmobile'))
		{
			return false;
		}

		if (Mobile::getApiVersion() < self::MINIMAL_API_VERSION)
		{
			return false;
		}

		if (Loader::includeModule('extranet') && !\CExtranet::isIntranetUser())
		{
			return false;
		}

		return \CBPRuntime::isFeatureEnabled();
	}

	public function getData(): ?array
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		return [
			'id' => $this->getId(),
			'sort' => $this->defaultSortValue(),
			'imageName' => $this->getIconId(),
			'badgeCode' => 'bp_tasks',
			'component' => $this->getComponentParams(),
		];
	}

	private function getComponentParams(): array
	{
		return [
			'name' => 'JSStackComponent',
			'title' => $this->getTitle(),
			'componentCode' => self::INITIAL_COMPONENT,
			'scriptPath' => Manager::getComponentPath(self::INITIAL_COMPONENT),
			'rootWidget' => [
				'name' => 'layout',
				'settings' => [
					'objectName' => 'layout',
					'useLargeTitleMode' => true,
				],
			],
			'params' => [],
		];
	}

	public function shouldShowInMenu(): bool
	{
		return $this->isAvailable();
	}

	public function getMenuData(): ?array
	{
		$counterId = (
			$this->isAvailable() && class_exists('\Bitrix\Bizproc\Workflow\WorkflowUserCounters')
				? 'bp_workflow'
				: 'bp_tasks'
		);

		return [
			'title' => $this->getTitle(),
			'useLetterImage' => true,
			'min_api_version' => self::MINIMAL_API_VERSION,
			'color' => '#00ace3',
			'imageUrl' => 'favorite/icon-bp.png',
			'imageName' => $this->getIconId(),
			'params' => [
				'onclick' => Utils::getComponentJSCode($this->getComponentParams()),
				'counter' => $counterId,
			],
		];
	}

	public function canBeRemoved(): bool
	{
		return true;
	}

	public function defaultSortValue(): int
	{
		return 510;
	}

	public function canChangeSort(): bool
	{
		return true;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('TAB_NAME_BIZPROC');
	}

	public function setContext($context): void
	{
		$this->context = $context;
	}

	public function getShortTitle(): ?string
	{
		return Loc::getMessage('TAB_SHORTNAME_BIZPROC');
	}

	public function getId(): string
	{
		return 'bizproc';
	}

	public function getIconId(): string
	{
		return Mobile::getApiVersion() < 56 ?  $this->getId() : 'business_process';
	}

	public static function onBeforeTabsGet(): array
	{
		return [
			[
				'code' => 'bizproc',
				'class' => static::class
			],
		];
	}
}
