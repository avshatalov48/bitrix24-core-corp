<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\WebForm\Internals\ResultEntityTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;

class WebFormResultsRestriction extends Bitrix24AccessRestriction
{
	public const ERROR_CODE = 'restriction_web_form_results';
	public const SLIDER_ID = 'limit_crm_100_form';

	protected const IS_LIMIT_REACHED_OPTION_PREFIX = 'crm_web_form_result_is_limit_reached_';
	protected const IS_LIMIT_REACHED_OPTION_VALUE = 'Y';
	protected const MIN_RESULT_ID_OPTION_PREFIX = 'crm_web_form_min_result_id_';

	/** @var ResultEntityTable */
	protected $resultEntityTable = ResultEntityTable::class;
	/** @var Option */
	protected $option = Option::class;

	protected $resultsLimit;
	protected $startDate;
	protected $isLimitReached;

	public function setResultsLimit(int $resultsLimit, ?Date $startDate = null): self
	{
		$this->resultsLimit = $resultsLimit;
		$this->startDate = $startDate;

		return $this;
	}

	public function setStartDate(?Date $startDate): self
	{
		$this->startDate = $startDate;

		return $this;
	}

	protected function getMinResultIdAboveLimit(): int
	{
		if (!$this->isLimitReached())
		{
			return 0;
		}

		$resultsLimit = $this->resultsLimit;
		$optionName = static::MIN_RESULT_ID_OPTION_PREFIX . $resultsLimit;
		$value = (int)$this->option::get('crm', $optionName);
		if ($value > 0)
		{
			return $value;
		}

		$value = $this->resultEntityTable::getMinUniqueResultIdHigherThan($resultsLimit);
		if ($value > 0)
		{
			$this->option::set('crm', $optionName, $value);
		}

		return $value;
	}

	protected function processLimit(): bool
	{
		$limit = (int)$this->resultsLimit;
		if ($limit <= 0)
		{
			return false;
		}

		$startDate = $this->startDate;
		if ($startDate !== null && (new Date())->getTimestamp() <= $startDate->getTimestamp())
		{
			return false;
		}
		$optionName = static::IS_LIMIT_REACHED_OPTION_PREFIX . $this->resultsLimit;
		$optionValue = $this->option::get('crm', $optionName);
		if ($optionValue === static::IS_LIMIT_REACHED_OPTION_VALUE)
		{
			return true;
		}

		$count = $this->resultEntityTable::getCountOfUniqueResultIds();
		$isLimitReached = ($count >= $this->resultsLimit);

		if ($isLimitReached)
		{
			$this->option::set('crm', $optionName, static::IS_LIMIT_REACHED_OPTION_VALUE);
		}

		return $isLimitReached;
	}

	public function isLimitReached(): bool
	{
		if ($this->hasPermission())
		{
			return false;
		}
		if ($this->isLimitReached === null)
		{
			$this->isLimitReached = $this->processLimit();
		}

		return $this->isLimitReached;
	}

	/**
	 * Check restriction for single item. Do not use it in cycle.
	 *
	 * @param ItemIdentifier $identifier
	 * @return bool
	 */
	public function isItemRestricted(ItemIdentifier $identifier): bool
	{
		$resultId = $this->getMinResultIdAboveLimit();
		if ($resultId <= 0)
		{
			return false;
		}

		return $this->resultEntityTable::isResultExistsForItemHigherThan($identifier, $resultId);
	}

	public function filterRestrictedItemIds(int $entityTypeId, array $itemIds): array
	{
		$resultId = $this->getMinResultIdAboveLimit();
		if ($resultId <= 0)
		{
			return [];
		}

		return $this->resultEntityTable::getItemIdsThatHasResultsHigherThan($entityTypeId, $resultId, $itemIds);
	}

	public function getFieldsToShow(array $additionalFields = []): array
	{
		return array_merge(
			[
				Item::FIELD_NAME_ID,
				Item::FIELD_NAME_TITLE,
				Item::FIELD_NAME_ASSIGNED,
				Item::FIELD_NAME_STAGE_ID,
				Item::FIELD_NAME_CATEGORY_ID,
				Item::FIELD_NAME_WEBFORM_ID,
				Item::FIELD_NAME_CREATED_BY,
				Item::FIELD_NAME_CREATED_TIME,
			],
			$additionalFields
		);
	}

	/**
	 * Return list of restricted item Ids of type $entityTypeId
	 *
	 * @param int $entityTypeId
	 * @return array
	 */
	public function getRestrictedItemIds(int $entityTypeId): array
	{
		$resultId = $this->getMinResultIdAboveLimit();
		if ($resultId <= 0)
		{
			return [];
		}

		return $this->resultEntityTable::getEntityIdsHigherThan($entityTypeId, $resultId);
	}

	public function getErrorMessage(): ?string
	{
		return Loc::getMessage('CRM_RESTRICTION_WEB_FORM_RESULTS');
	}

	public function getErrorCode(): ?string
	{
		return static::ERROR_CODE;
	}
}
