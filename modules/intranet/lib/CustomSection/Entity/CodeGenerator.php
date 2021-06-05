<?php

namespace Bitrix\Intranet\CustomSection\Entity;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Security\Random;

class CodeGenerator
{
	protected const RANDOM_CODE_LENGTH = 5;
	protected const MAX_NUMBER_OF_TRIES = 5;

	/** @var DataManager */
	protected $dataManager;
	/** @var StringField */
	protected $codeField;

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
	 */
	public function isCodeValid(string $code): bool
	{
		return $this->codeField->validateValue($code, [], [], new Result())->isSuccess();
	}

	protected function isCodeUnique(string $code): bool
	{
		$count = $this->dataManager::getCount([
			'=' . $this->codeField->getName() => $code,
		]);

		return ($count <= 0);
	}

	protected function isCodeShouldBeUnique(): bool
	{
		return $this->codeField->isUnique();
	}
}
