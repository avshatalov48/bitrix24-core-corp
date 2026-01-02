<?php

namespace Bitrix\Rpa\Integration;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Model\TypeTable;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Toolbar\Facade\Toolbar;

class Bitrix24Manager
{
	public const VARIABLE_TYPES_LIMIT = 'rpa_types_limit';

	protected $isEnabled;
	/** @var TypeTable */
	protected $typeTable = TypeTable::class;
	/** @var Feature */
	protected $feature = Feature::class;

	public function __construct()
	{
		$this->isEnabled = $this->includeModule();
	}

	protected function includeModule(): bool
	{
		try
		{
			return Loader::includeModule('bitrix24');
		}
		catch(LoaderException $exception)
		{
			return false;
		}
	}

	public function isEnabled(): bool
	{
		return $this->isEnabled;
	}

	public function isCreateTypeRestricted(): bool
	{
		return (!$this->isTypesLimitReached());
	}

	public function isCreateItemRestricted(int $typeId): bool
	{
		if($this->isCreateTypeRestricted())
		{
			return !in_array($typeId, $this->getFullControllableTypeIds());
		}

		return false;
	}

	public function isTypeSettingsRestricted(int $typeId): bool
	{
		return $this->isCreateItemRestricted($typeId);
	}

	protected function getTypesCount(): int
	{
		return (int) $this->typeTable::getCount();
	}

	protected function getTypesLimit(): int
	{
		if($this->isEnabled())
		{
			return (int) $this->feature::getVariable(static::VARIABLE_TYPES_LIMIT);
		}

		return 0;
	}

	protected function isTypesLimitReached(): bool
	{
		$typesLimit = $this->getTypesLimit();

		return (
			($typesLimit === 0) || ($this->getTypesCount() < $typesLimit)
		);
	}

	protected function getFullControllableTypeIds(): array
	{
		static $typeIds;
		if($typeIds === null)
		{
			return array_column($this->typeTable::getList([
				'select' => ['ID'],
				'order' => ['ID' => 'ASC'],
				'limit' => $this->getTypesLimit(),
			])->fetchAll(), 'ID');
		}

		return $typeIds;
	}

	/**
	 * @return string
	 */
	public function getPortalZone(): ?string
	{
		if($this->isEnabled)
		{
			return \CBitrix24::getPortalZone();
		}

		return null;
	}

	/**
	 * @return string
	 */
	public function getLicenseType(): ?string
	{
		if($this->isEnabled)
		{
			return \CBitrix24::getLicenseType();
		}

		return null;
	}

	public function addFeedbackButtonToToolbar(string $context = null): void
	{
		if($this->isEnabled() && Loader::includeModule('ui'))
		{
			Extension::load(['rpa.manager']);
			$url = Driver::getInstance()->getUrlManager()->getFeedbackUrl($context);
			if($url)
			{
				Toolbar::addButton([
					'color' => Color::LIGHT_BORDER,
					'link' => $url->getLocator(),
					'text' => Loc::getMessage('RPA_FEEDBACK'),
					'dataset' => [
						'toolbar-collapsed-icon' => \Bitrix\UI\Buttons\Icon::INFO
					]
				]);
			}
		}
	}

	/**
	 * @param string|null $region
	 * @return array
	 */
	public function getFeedbackFormInfo($region = null): array
	{
		return [
			['zones' => ['br'], 'id' => 164, 'lang' => 'br', 'sec' => 'j1t95n'],
			['zones' => ['la'], 'id' => 166, 'lang' => 'la', 'sec' => 'nh1t7p'],
			['zones' => ['de'], 'id' => 168, 'lang' => 'de', 'sec' => 'uphcj0'],
			['zones' => ['ru', 'kz', 'by'], 'id' => 162, 'lang' => 'ru', 'sec' => 'x24l3h'],
			['id' => 170, 'lang' => 'en', 'sec' => '0c6pnp'],
		];
	}
}