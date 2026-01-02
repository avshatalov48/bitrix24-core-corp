<?php

namespace Bitrix\Crm\RepeatSale;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;

final class FlowController
{
	use Singleton;

	public const FLOW_ENABLE_DATE_OPTION_NAME = 'repeat_sale_flow_enable_date';
	public const FLOW_EXPECTED_ENABLE_DATE_OPTION_NAME = 'repeat_sale_flow_expected_enable_date';
	public const FLOW_EXPECTED_USER_ID_OPTION_NAME = 'repeat_sale_flow_expected_user_id';

	public function saveEnableDate(DateTime $date): void
	{
		$this->saveDate(self::FLOW_ENABLE_DATE_OPTION_NAME, $date);
	}

	public function saveExpectedEnableDate(DateTime $date): void
	{
		$this->saveDate(self::FLOW_EXPECTED_ENABLE_DATE_OPTION_NAME, $date);
	}

	private function saveDate(string $optionName, DateTime $date): void
	{
		Option::set('crm', $optionName, $date->getTimestamp());
	}

	public function getEnableDate(): ?DateTime
	{
		return $this->getDate(self::FLOW_ENABLE_DATE_OPTION_NAME);
	}

	public function getExpectedEnableDate(): ?DateTime
	{
		return $this->getDate(self::FLOW_EXPECTED_ENABLE_DATE_OPTION_NAME);
	}

	private function getDate(string $optionName): ?DateTime
	{
		$value = Option::get('crm', $optionName, null);

		if ($value === null)
		{
			return null;
		}

		if (is_numeric($value))
		{
			return DateTime::createFromTimestamp($value);
		}

		return DateTime::createFromText($value);
	}

	public function deleteExpectedOptions(): void
	{
		Option::delete('crm', ['name' => self::FLOW_EXPECTED_ENABLE_DATE_OPTION_NAME]);
		Option::delete('crm', ['name' => self::FLOW_EXPECTED_USER_ID_OPTION_NAME]);
	}

	public function saveExpectedUserId(int $userId): void
	{
		Option::set('crm', self::FLOW_EXPECTED_USER_ID_OPTION_NAME, $userId);
	}

	public function getExpectedUserId(): ?int
	{
		return Option::get('crm', self::FLOW_EXPECTED_USER_ID_OPTION_NAME, null);
	}
}
