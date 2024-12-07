<?php declare(strict_types=1);

namespace Bitrix\AI\Controller;

use \Bitrix\AI\Facade;
use Bitrix\AI\Facade\User;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;


class Agreement extends Controller
{
	private const AGREEMENTS = ['AI_BOX_AGREEMENT'];

	public function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
		];
	}

	/**
	 * Check agreement accept by code.
	 *
	 * @param string $agreementCode
	 * @param array $parameters
	 *
	 * @return bool
	 */
	public function checkAction(string $agreementCode, array $parameters): array
	{
		$agreement = $this->getAgreement($agreementCode);

		if ($agreement === null)
		{
			return [];
		}

		return [
			'isAccepted' => $agreement->isAcceptedByUser(User::getCurrentUserId()),
		];
	}

	/**
	 * Accept agreement by code.
	 *
	 * @param string $agreementCode
	 * @param array $parameters
	 *
	 * @return bool
	 */
	public function acceptAction(string $agreementCode, array $parameters): array
	{
		$agreement = $this->getAgreement($agreementCode);

		if ($agreement === null)
		{
			return [];
		}

		$agreement->acceptByUser(User::getCurrentUserId());

		return [
			'isAccepted' => $agreement->isAcceptedByUser(User::getCurrentUserId()),
		];
	}

	private function getAgreement(string $agreementCode): ?\Bitrix\AI\Agreement
	{
		if (!in_array($agreementCode, self::AGREEMENTS, true))
		{
			$error = new Error('Agreement with code: "' . $agreementCode .'" not found.', 'AI_AGREEMENT_NOT_FOUND');
			$this->addError($error);

			return null;
		}

		$agreement = \Bitrix\AI\Agreement::get($agreementCode);
		if ($agreement === null)
		{
			$error = new Error('Agreement with code ' . $agreementCode . ' not found.', 'AI_AGREEMENT_NOT_FOUND');
			$this->addError($error);

			return null;
		}

		return $agreement;
	}
}