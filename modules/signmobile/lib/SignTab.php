<?php

namespace Bitrix\SignMobile;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Mobile\Tab\Utils;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\SignMobile\Config\Feature;
use Bitrix\Sign\Type\CounterType;
use Bitrix\Mobile\Menu\Analytics;

class SignTab implements Tabable
{
	private const INITIAL_COMPONENT = 'sign:sign.b2e.grid';

	/** @var Context $context */
	private Context $context;

	public static function onBeforeTabsGet(): array
	{
		return [
			[
				'code' => 'sign',
				'class' => static::class,
			],
		];
	}

	public function isAvailable(): bool
	{
		return Loader::includeModule('sign')
			&& Loader::includeModule('signmobile')
			&& Feature::instance()?->isMyDocumentsGridAvailable();
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
			'component' => $this->getComponentParams(),
		];
	}

	public function shouldShowInMenu(): bool
	{
		return $this->isAvailable();
	}

	public function getMenuData(): ?array
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		return [
			'id' => 'signing',
			'section_code' => 'teamwork',
			'sort' => 200,
			'title' => $this->getTitle(),
			'useLetterImage' => true,
			'imageUrl' => 'sign/my-documents.png',
			'imageName' => $this->getIconId(),
			'params' => [
				'onclick' => Utils::getComponentJSCode($this->getComponentParams()),
				'counter' => enum_exists(CounterType::class)
					? CounterType::SIGN_B2E_MY_DOCUMENTS->value
					: 'sign_b2e_current',
				'analytics' => Analytics::signDocuments(),
			],
		];
	}

	public function canBeRemoved(): bool
	{
		return true;
	}

	public function defaultSortValue(): int
	{
		return 500;
	}

	public function canChangeSort(): bool
	{
		return true;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('SIGN_MOBILE_TAB_TITLE_MSGVER_1');
	}

	public function setContext($context): void
	{
		$this->context = $context;
	}

	public function getShortTitle(): ?string
	{
		return Loc::getMessage('SIGN_MOBILE_TAB_SHORT_TITLE');
	}

	public function getId(): string
	{
		return 'sign';
	}

	public function getIconId(): string
	{
		return 'sign';
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
}
