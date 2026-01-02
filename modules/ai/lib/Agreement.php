<?php

namespace Bitrix\AI;

use Bitrix\AI\Context;
use Bitrix\AI\Facade;
use Bitrix\Main\Config\Option;

class Agreement
{
	private int $id;
	private string $code;
	private string $title;
	private string $text;

	private static array $fixedAgreementValues = [
		'AI_BOX_AGREEMENT' => true,
	];

	private function __construct() {}

	/**
	 * Returns agreement instance by code, false on fail.
	 *
	 * @param string $code Agreement code.
	 * @return self|null
	 */
	public static function get(string $code): ?self
	{
		if (empty($code))
		{
			return null;
		}
		if (self::isFixedAgreementValue($code))
		{
			$agreement = new self();
			$agreement->id = 0;
			$agreement->code = $code;
			$agreement->title = '';
			$agreement->text = '';

			return $agreement;
		}
		$data = Facade\Agreement::getByCode($code, true);
		if ($data)
		{
			$agreement = new self();
			$agreement->id = $data['ID'];
			$agreement->code = $data['CODE'];
			$agreement->title = $data['NAME'];
			$agreement->text = $data['AGREEMENT_TEXT'];

			return $agreement;
		}
		return null;
	}

	private static function isFixedAgreementValue(string $code): bool
	{
		return array_key_exists($code, self::$fixedAgreementValues);
	}

	private static function getFixedAgreementValue(string $code): ?bool
	{
		return self::$fixedAgreementValues[$code] ?? null;
	}

	/**
	 * Returns agreement's code.
	 *
	 * @return string
	 */
	public function getCode(): string
	{
		return $this->code;
	}

	/**
	 * Returns agreement's title.
	 *
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}

	/**
	 * Returns agreement's text.
	 *
	 * @return string
	 */
	public function getText(): string
	{
		return $this->text;
	}

	/**
	 * Returns true if agreement exists, but Context's user doesn't accept it.
	 *
	 * @param Context $context
	 * @return bool
	 */
	public function isAcceptedByContext(Context $context): bool
	{
		if (self::isFixedAgreementValue($this->code))
		{
			return self::getFixedAgreementValue($this->code);
		}

		if (!$context->getUserId())
		{
			return true;
		}

		return Facade\Agreement::isAcceptedByUser($this->id, $context->getUserId());
	}

	/**
	 * Makes acceptation of AI agreement by Context's user.
	 *
	 * @param Context $context
	 * @return bool
	 */
	public function acceptByContext(Context $context): bool
	{
		return $this->isAcceptedByContext($context) || Facade\Agreement::acceptByUser($this->id, $context->getUserId());
	}

	/**
	 * Returns true if agreement exists, but Context's user doesn't accept it.
	 *
	 * @param int $userId
	 * @return bool
	 */
	public function isAcceptedByUser(int $userId): bool
	{
		if (self::isFixedAgreementValue($this->code))
		{
			return self::getFixedAgreementValue($this->code);
		}

		return Facade\Agreement::isAcceptedByUser($this->id, $userId);
	}

	/**
	 * Makes acceptation of AI agreement by user id.
	 *
	 * @param int $userId
	 * @return bool
	 */
	public function acceptByUser(int $userId): bool
	{
		return $this->isAcceptedByUser($userId) || Facade\Agreement::acceptByUser($this->id, $userId);
	}
}
