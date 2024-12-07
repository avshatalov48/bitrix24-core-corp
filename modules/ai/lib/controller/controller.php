<?php
namespace Bitrix\AI\Controller;

use Bitrix\AI\Context;
use Bitrix\AI\Engine;
use Bitrix\AI\Facade\User;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Error;

abstract class Controller extends \Bitrix\Main\Engine\Controller
{
	protected ?string $category = null;
	protected Context $context;

	/**
	 * Returns agreement's data.
	 *
	 * @param array $parameters Parameters with context.
	 * @return array|null
	 */
	public function agreementAction(array $parameters = []): ?array
	{
		if (!empty($this->getErrors()) || empty($this->context) || !$this->category)
		{
			return null;
		}

		$engine = Engine::getByCategory($this->category, $this->context);
		if (!$engine)
		{
			return null;
		}

		$agreement = $engine->getAgreement();
		if ($agreement)
		{
			return [
				'accepted' => $agreement->isAcceptedByContext($this->context),
				'title' => $agreement->getTitle(),
				'text' => $agreement->getText(),
				'inTariff' => $engine->inTariff(),
			];
		}

		return null;
	}

	/**
	 * Accepts agreement by current user.
	 *
	 * @param string $engineCode
	 * @param array $parameters Parameters with context.
	 * @return bool
	 */
	public function acceptationAction(string $engineCode, array $parameters = []): bool
	{
		if (!empty($this->getErrors()) || empty($this->context) || !$this->category)
		{
			return false;
		}

		$engine = Engine::getByCode($engineCode, $this->context, $this->category);
		if (!$engine)
		{
			return false;
		}

		$agreement = $engine->getAgreement();
		if ($agreement)
		{
			return $agreement->acceptByContext($this->context);
		}

		return false;
	}

	/**
	 * Called before any Controller Action.
	 *
	 * @param Action $action
	 * @return bool
	 */
	protected function processBeforeAction(Action $action): bool
	{
		$parameters = (array)$action->getArguments()['parameters'] ?? [];

		$this->retrieveContext($parameters);

		return parent::processBeforeAction($action);
	}

	/**
	 * Checks current user has accepted agreement.
	 *
	 * @param Engine $engine Engine instance.
	 * @return bool
	 */
	protected function checkAgreementAccepted(Engine $engine): bool
	{
		if ($engine->getAgreement() && !$engine->getAgreement()->isAcceptedByContext($this->context))
		{
			$this->addError(new Error('You must accept Agreement.', 'AGREEMENT_IS_NOT_ACCEPTED', [
				'title' => $engine->getAgreement()->getTitle(),
				'text' => $engine->getAgreement()->getText(),
			]));
			return false;
		}

		return true;
	}

	/**
	 * Parses string and returns true if its human equivalent of true.
	 * (true / 'true' / 'y' / 1)
	 *
	 * @param string|bool $value Raw value.
	 * @return bool
	 */
	protected function isTrue(string|bool $value): bool
	{
		if (is_bool($value))
		{
			return $value;
		}
		if (!is_string($value))
		{
			return false;
		}

		$value = mb_strtolower($value);
		if ($value === 'true' || $value === 'y' || intval($value) > 0)
		{
			return true;
		}

		return false;
	}

	/**
	 * Parse number from integer-like string. Another values will become null
	 * @param mixed $value
	 * @return int|null
	 */
	protected function parseInt(mixed $value): ?int
	{
		if (is_int($value))
		{
			return $value;
		}

		if (is_string($value) && $value <> '')
		{
			return (int)$value;
		}

		return null;
	}

	/**
	 * Tries to retrieve parameters from request.
	 *
	 * @param array $parameters Request parameters.
	 * @return void
	 */
	private function retrieveContext(array $parameters): void
	{
		if (
			!isset($parameters['bx_module']) || !isset($parameters['bx_context'])
			|| !is_string($parameters['bx_module']) || !is_string($parameters['bx_context'])
		)
		{
			$this->addError(new Error('Missed required string parameters: `bx_module` or `bx_context`.'));
		}
		else
		{
			$this->context = new Context(
				$parameters['bx_module'],
				$parameters['bx_context'],
				User::getCurrentUserId(),
			);
			$this->context->setParameters($parameters['bx_context_parameters'] ?? null);
		}
	}
}
