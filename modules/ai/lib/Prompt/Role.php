<?php

namespace Bitrix\AI\Prompt;

use Bitrix\AI\Facade\User;
use Bitrix\AI\Model\RoleTable;
use Bitrix\AI\Role\RoleManager;
use Bitrix\AI\Services\AvailableRuleService;
use Bitrix\Main\Type\DateTime;

class Role
{
	private const RUNTIME_CODE = 'runtime';

	private function __construct(
		private string $code,
		private string $instruction,
		private ?string $industryCode,
		private DateTime $modifyDate
	) {}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getInstruction(): string
	{
		return $this->instruction;
	}

	public function getModifyTime(): DateTime
	{
		return $this->modifyDate;
	}

	/**
	 * Return role industry code.
	 *
	 * @return string|null
	 */
	public function getIndustryCode(): ?string
	{
		return $this->industryCode;
	}

	/**
	 * Sets new instruction text, not saves. Use after text formatting for example.
	 *
	 * @param string $instruction New instruction text.
	 * @return $this
	 */
	public function setInstruction(string $instruction): self
	{
		$this->instruction = $instruction;
		return $this;
	}

	/**
	 * Append instruction text, not saves. Use after text formatting for example.
	 *
	 * @param string $instruction New text to append to existing.
	 * @return $this
	 */
	public function appendInstruction(string $instruction): self
	{
		$this->instruction .= "\n\n" . $instruction;
		return $this;
	}

	/**
	 * Creates runtime Role with specific instructions.
	 *
	 * @param string $instruction Role's instruction text.
	 * @return self
	 */
	public static function createRuntime(string $instruction): self
	{
		return new self(
			self::RUNTIME_CODE,
			$instruction,
			'',
			new DateTime
		);
	}

	/**
	 * Returns Role instance by code.
	 *
	 * @param string|null $code Role code.
	 * @return self|null
	 */
	public static function get(?string $code): ?self
	{
		/** @var static[] $roles */
		static $roles = [];

		if (!$code)
		{
			return null;
		}

		if (array_key_exists($code, $roles))
		{
			return $roles[$code];
		}

		$role = RoleTable::query()
			->setSelect(['ID', 'CODE', 'INSTRUCTION', 'INDUSTRY_CODE', 'DATE_MODIFY', 'RULES', 'IS_SYSTEM'])
			->where('CODE', $code)
			->setLimit(1)
			->fetchObject()
		;

		if (empty($role))
		{
			return null;
		}

		if (
			($role->getCode()) == RoleManager::getUniversalRoleCode()
			|| static::getAvailableRuleService()->isAvailableRules($role->getRules(), User::getUserLanguage())
		)
		{
			$roles[$code] = new self(
				$role->getCode(),
				$role->getInstruction(),
				$role->getIndustryCode(),
				$role->getDateModify(),
			);
		}

		return $roles[$code] ?? null;
	}

	/**
	 * Returns universal Role instance.
	 * @return self
	 */
	public static function getUniversalRole(): self
	{
		return self::get(RoleManager::getUniversalRoleCode());
	}

	/**
	 * Returns universal Role instance.
	 * @return self
	 */
	public static function getLibrarySystemRole(): self
	{
		return self::get(RoleManager::getLibrarySystemRoleCode());
	}


	/**
	 * Removes all roles from DB.
	 *
	 * @return void
	 */
	public static function clear(): void
	{
		$res = RoleTable::query()
			->setSelect(['ID'])
			->exec()
		;
		while ($row = $res->fetch())
		{
			RoleTable::delete($row['ID'])->isSuccess();
		}
	}

	private static function getAvailableRuleService(): AvailableRuleService
	{
		static $service;

		if (empty($service)) {
			$service = new AvailableRuleService();
		}

		return $service;
	}
}
