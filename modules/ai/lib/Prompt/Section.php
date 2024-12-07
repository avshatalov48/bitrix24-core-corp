<?php

namespace Bitrix\AI\Prompt;

use Bitrix\AI\Facade\User;
use Bitrix\AI\Model\SectionTable;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\Type\DateTime;

class Section
{
	private function __construct(
		private string $code,
		private array $translate,
		private DateTime $modifyDate
	) {}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getTitle(): string
	{
		$lang = User::getUserLanguage();

		if (isset($this->translate[$lang]))
		{
			return $this->translate[$lang];
		}
		if (isset($this->translate['en']))
		{
			return $this->translate['en'];
		}

		return $this->code;
	}

	public function getModifyTime(): DateTime
	{
		return $this->modifyDate;
	}

	/**
	 * Returns Section instance by code.
	 *
	 * @param string|null $code Section code.
	 * @return self|null
	 */
	public static function get(?string $code): ?self
	{
		static $sections = [];

		if (!$code)
		{
			return null;
		}

		if (array_key_exists($code, $sections))
		{
			return $sections[$code];
		}

		$sections[$code] = SectionTable::query()
			->setSelect(['CODE', 'TRANSLATE', 'DATE_MODIFY'])
			->where('CODE', $code)
			->setLimit(1)
			->fetch() ?: null
		;

		if ($sections[$code])
		{
			$sections[$code] = new self(
				$sections[$code]['CODE'],
				$sections[$code]['TRANSLATE'],
				$sections[$code]['DATE_MODIFY']
			);
		}

		return $sections[$code];
	}

	/**
	 * Removes all sections from DB.
	 *
	 * @return void
	 */
	public static function clear(): void
	{
		$res = SectionTable::query()
			->setSelect(['ID'])
			->exec()
		;
		while ($row = $res->fetch())
		{
			SectionTable::delete($row['ID'])->isSuccess();
		}
	}
}