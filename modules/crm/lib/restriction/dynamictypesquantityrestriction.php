<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

final class DynamicTypesQuantityRestriction extends Bitrix24QuantityRestriction
{
	public const RESTRICTION_NAME = 'crm_smart_processes';
	public const UNAVAILABLE_SLIDER_CODE = 'limit_smart_process_automation';
	public const RESTRICTION_150_SLIDER_CODE = 'limit_150_smart_processes';
	public const RESTRICTION_TECH_SLIDER_CODE = 'limit_smart_processes_max_number';

	private const DEFAULT_DYNAMIC_TYPE_LIMIT_BY_OPTIONS = 1000;
	private const DYNAMIC_TYPE_LIMIT_OPTION = 'crm_smart_processes_limit';
	private const DYNAMIC_COUNT_CACHE_TTL = 60 * 60; // 1 hour

	private bool $isBitrix24Included;

	public function __construct()
	{
		$this->isBitrix24Included = Loader::includeModule('bitrix24');

		parent::__construct(
			self::RESTRICTION_NAME,
			$this->getLimit(),
		);

		$this->load();
	}

	public function isExceeded(): bool
	{
		$limit = $this->getQuantityLimit();

		if ($limit === 0)
		{
			return true;
		}

		if ($limit < 0)
		{
			return false;
		}

		return $limit <= $this->getCount();
	}

	public function getCount(): int
	{
		return TypeTable::getCount([], [ 'ttl' => self::DYNAMIC_COUNT_CACHE_TTL ]);
	}

	public function getRestrictionSliderCode(): string
	{
		$maxLimit = $this->getMaxLimit();
		$limit = $this->getLimit();

		return match(true){
			$limit === $maxLimit => self::RESTRICTION_TECH_SLIDER_CODE,
			$limit === 0 => self::UNAVAILABLE_SLIDER_CODE,
			default => self::RESTRICTION_150_SLIDER_CODE,
		};
	}

	private function getLimit(): int
	{
		if ($this->isBitrix24Included)
		{
			$limit = Bitrix24Manager::getVariable(self::DYNAMIC_TYPE_LIMIT_OPTION);
			if ($limit !== null)
			{
				return $limit;
			}
		}

		return $this->getLimitByOptions();
	}

	private function getLimitByOptions(): int
	{
		return Option::get('crm', self::DYNAMIC_TYPE_LIMIT_OPTION, self::DEFAULT_DYNAMIC_TYPE_LIMIT_BY_OPTIONS);
	}

	private function getMaxLimit(): int
	{
		return $this->isBitrix24Included
			? Bitrix24Manager::getMaxVariable(self::DYNAMIC_TYPE_LIMIT_OPTION)
			: $this->getLimitByOptions()
		;
	}
}
