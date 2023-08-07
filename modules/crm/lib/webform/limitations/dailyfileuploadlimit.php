<?php

namespace Bitrix\Crm\WebForm\Limitations;

use Bitrix\Crm\WebForm\Internals\LimitationTable;
use Bitrix\Main\Config;

use Bitrix\Main;

class DailyFileUploadLimit
{
	private const LIMIT_CODE = 'file_size';
	private const LIMIT_TYPE = 'daily';

	private static ?DailyFileUploadLimit $instance = null;

	private function __construct(){}

	public static function instance(): DailyFileUploadLimit
	{
		if (!self::$instance)
		{
			self::$instance = new static();
		}
		return self::$instance;
	}

	public function getCurrent(): int
	{
		$row = LimitationTable::getCurrent(self::LIMIT_CODE, self::LIMIT_TYPE);

		return $row['VALUE'] ?? 0;
	}

	public function check(int $sizeBytes): bool
	{
		$size = $this->getCurrent() + $sizeBytes;
		return $this->getLimit() * (1024 * 1024) > $size;
	}

	public function incrementByValue(int $size): Main\Result
	{
		return LimitationTable::incrementByValue(self::LIMIT_CODE, self::LIMIT_TYPE, $size);
	}

	public function isUsed(): bool
	{
		return $this->getLimit() !== null;
	}

	public function getLimit(): ?int
	{
		return Config\Option::get("crm", "~webform_file_size_upload_limit_daily_mb", null);
	}

	public function setLimit(?int $size): void
	{
		Config\Option::set("crm", "~webform_file_size_upload_limit_daily_mb", $size);
	}
}