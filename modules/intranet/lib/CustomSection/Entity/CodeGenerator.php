<?php

namespace Bitrix\Intranet\CustomSection\Entity;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Security\Random;
use Bitrix\Main\SystemException;
use Closure;

class CodeGenerator
{
	protected const RANDOM_CODE_LENGTH = 5;
	protected const MAX_NUMBER_OF_TRIES = 5;

	/** @var DataManager */
	protected $dataManager;
	/** @var StringField */
	protected $codeField;

	protected ?Closure $uniqueCheckCallback = null;
	protected bool $useOnlyUniqueCheckCallback = false;
	protected array $uniqueCheckFilter = [];
	protected bool $useOnlyUniqueCheckFilter = false;

	/**
	 * CodeGenerator constructor.
	 *
	 * @param string $dataManager
	 * @param StringField $codeField
	 *
	 * @throws ArgumentException
	 */
	public function __construct(string $dataManager, StringField $codeField)
	{
		if (!is_a($dataManager, DataManager::class, true))
		{
			throw new ArgumentException('$dataManager should be a subclass of ' . DataManager::class, 'dataManager');
		}

		$this->dataManager = $dataManager;
		$this->codeField = $codeField;
	}

	/**
	 * Generate code
	 *
	 * @param string|null $title
	 *
	 * @return string|null
	 */
	public function generate(?string $title = null): ?string
	{
		$code = null;
		if (!empty($title))
		{
			$code = $this->generateByTitle($title);
		}
		if (is_null($code) || !$this->isCodeValid($code))
		{
			$code = $this->generateRandomCode();
		}

		if (is_null($code))
		{
			return null;
		}

		return ($this->isCodeValid($code) ? $code : null);
	}

	protected function generateByTitle(string $title, int $try = 1): ?string
	{
		if ($try >= static::MAX_NUMBER_OF_TRIES)
		{
			return null;
		}

		if ($try > 1)
		{
			//e.g. 'Not unique title 2'
			$title .= ' ' . $try;
		}

		$code = \CUtil::translit(
			$title,
			Loc::getCurrentLang(),
			[
				'max_len' => $this->codeField->getSize(),
				'change_case' => true,
				'replace_space' => '_',
				'replace_other' => '',
				'delete_repeat_replace' => true,
				'safe_chars' => '',
			]
		);

		if ($this->isCodeShouldBeUnique() && !$this->isCodeUnique($code))
		{
			return $this->generateByTitle($title, $try + 1);
		}

		return $code;
	}

	protected function generateRandomCode(int $try = 1): ?string
	{
		if ($try >= static::MAX_NUMBER_OF_TRIES)
		{
			return null;
		}

		$code = Random::getStringByAlphabet(static::RANDOM_CODE_LENGTH + $try, Random::ALPHABET_ALPHALOWER);

		if ($this->isCodeShouldBeUnique() && !$this->isCodeUnique($code))
		{
			return $this->generateRandomCode($try + 1);
		}

		return $code;
	}

	/**
	 * Returns true if validation specified for the code string field passes successfully
	 *
	 * @param string $code
	 *
	 * @return bool
	 * @throws SystemException
	 */
	public function isCodeValid(string $code): bool
	{
		return $this->codeField->validateValue($code, [], [], new Result())->isSuccess();
	}

	protected function isCodeUnique(string $code): bool
	{
		$filter = $this->getPreparedFilter($code);
		$count = $this->dataManager::getCount($filter);

		$uniqueCheckCallbackResult = true;
		if (!is_null($this->uniqueCheckCallback))
		{
			$uniqueCheckCallbackResult = ($this->uniqueCheckCallback)($code);
			if ($this->useOnlyUniqueCheckCallback)
			{
				return $uniqueCheckCallbackResult;
			}
		}

		return ($count <= 0 && $uniqueCheckCallbackResult);
	}

	protected function getPreparedFilter($code): array
	{
		$filter = ['=' . $this->codeField->getName() => $code];

		if ($this->useOnlyUniqueCheckFilter)
		{
			return $this->uniqueCheckFilter;
		}

		return array_merge($filter, $this->uniqueCheckFilter);
	}

	protected function isCodeShouldBeUnique(): bool
	{
		return $this->codeField->isUnique();
	}

	/**
	 * Set additional callback to check uniqueness code
	 *
	 * @param Closure|null $uniqueCheckCallback
	 * @return $this
	 */
	public function setUniqueCheckCallback(?Closure $uniqueCheckCallback): self
	{
		$this->uniqueCheckCallback = $uniqueCheckCallback;

		return $this;
	}

	public function getUniqueCheckCallback(): ?Closure
	{
		return $this->uniqueCheckCallback;
	}

	public function setUseOnlyUniqueCheckCallback(bool $isUse): self
	{
		$this->useOnlyUniqueCheckCallback = $isUse;

		return $this;
	}

	/**
	 * Set an additional filter that will be taken into account
	 * when fetching data using $this->dataManager to check the uniqueness of the code
	 *
	 * @param array $uniqueCheckFilter
	 * @return $this
	 */
	public function setUniqueCheckFilter(array $uniqueCheckFilter): self
	{
		$this->uniqueCheckFilter = $uniqueCheckFilter;

		return $this;
	}

	public function getUniqueCheckFilter(): array
	{
		return $this->uniqueCheckFilter;
	}

	/**
	 * If true, then when checking the uniqueness of the code, only the filter set in $uniqueCheckFilter will be taken into account.
	 * Default $uniqueCheckFilter = []
	 *
	 * @param bool $isUse
	 * @return $this
	 */
	public function setUseOnlyUniqueCheckFilter(bool $isUse): self
	{
		$this->useOnlyUniqueCheckFilter = $isUse;

		return $this;
	}
}
