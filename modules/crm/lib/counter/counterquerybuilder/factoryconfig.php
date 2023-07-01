<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder;

use Bitrix\Main\Config\Option;

final class FactoryConfig
{
	private bool $readyUncompletedActivity;

	private bool $readyCountableActivity;

	private bool $readyActCounterLight;

	private bool $mustUseUncompletedActivityTable;

	private ?bool $onlyMinIncomingChannel;

	public function __construct(
		bool $readyUncompletedActivity,
		bool $readyCountableActivity,
		bool $readyActCounterLight,
		bool $mustUseUncompletedActivity,
		?bool $onlyMinIncomingChannel
	)
	{
		$this->readyUncompletedActivity = $readyUncompletedActivity;
		$this->readyCountableActivity = $readyCountableActivity;
		$this->readyActCounterLight = $readyActCounterLight;
		$this->mustUseUncompletedActivityTable = $mustUseUncompletedActivity;
		$this->onlyMinIncomingChannel = $onlyMinIncomingChannel;
	}

	/**
	 * Returns true if table "b_crm_entity_uncompleted_act" is already filled with data and ready to use.
	 * On large portals, this can take a significant amount of time, so scripts are played when it was necessary
	 * to work with compatibility mode.
	 * @return bool
	 */
	public function readyUncompleted(): bool
	{
		return $this->readyUncompletedActivity;
	}

	/**
	 * Returns true if table "b_crm_entity_countable_act" is already filled with data and ready to use.
	 * On large portals, this can take a significant amount of time, so scripts are played when it was necessary
	 * to work with compatibility mode.
	 * @return bool
	 */
	public function readyCountable(): bool
	{
		return $this->readyCountableActivity;
	}

	/**
	 * Returns true if table "b_crm_act_counter_light" is already filled with data and ready to use.
	 * On large portals, this can take a significant amount of time, so scripts are played when it was necessary
	 * to work with compatibility mode.
	 * @return bool
	 */
	public function readyActCounterLight(): bool
	{
		return $this->readyActCounterLight;
	}

	/**
	 * Returns true if it is necessary to use the "b_crm_entity_uncompleted_act" table when building a query
	 * @return bool
	 */
	public function mustUseUncompleted(): bool
	{
		return $this->mustUseUncompletedActivityTable;
	}

	public function onlyMinIncomingChannel(): bool
	{
		return $this->onlyMinIncomingChannel;
	}

	public function isCompatibleWay(): bool
	{
		return !$this->readyUncompleted()
			|| (
				!$this->mustUseUncompleted()
				&& !$this->readyCountable()
			);
	}

	public function isUncompletedActivityWay(): bool
	{
		return !$this->readyCountable()
			|| $this->mustUseUncompleted();
	}

	public static function create(
		bool $mustUseUncompletedTable,
		bool $onlyMinIncomingChannel = false
	): self
	{
		$readyUncompletedActivity = Option::get('crm', 'enable_entity_uncompleted_act', 'Y') === 'Y';
		$readyEntityCountableActivity = Option::get('crm', 'enable_entity_countable_act', 'Y') === 'Y';
		$readyActCounterLightTable = Option::get('crm', 'enable_act_counter_light', 'Y') === 'Y';

		return new self(
			$readyUncompletedActivity,
			$readyEntityCountableActivity,
			$readyActCounterLightTable,
			$mustUseUncompletedTable,
			$onlyMinIncomingChannel
		);
	}

}