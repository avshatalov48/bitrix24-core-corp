<?php

namespace Bitrix\Sign\Operation\Document\Validation;

use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\Document\SignUntilService;
use Bitrix\Sign\Type\DocumentScenario;
use Bitrix\Sign\Type\DocumentStatus;

final class ValidateDateSignUntil implements Contract\Operation
{
	private const ERROR_CODE_PERIOD_TOO_LONG = 'PERIOD_TOO_LONG';
	private const ERROR_CODE_PERIOD_TOO_SHORT = 'PERIOD_TOO_SHORT';
	private const ERROR_CODE_DOCUMENT_IN_FINAL_STATUS = 'DOCUMENT_IN_FINAL_STATUS';

	private readonly SignUntilService $signUntilService;

	public function __construct(
		private readonly Document $document,
	)
	{
		$container = Container::instance();
		$this->signUntilService = $container->getSignUntilService();
	}

	public function launch(): Main\Result
	{
		if (DocumentScenario::isB2BScenario($this->document->scenario))
		{
			return new Main\Result();
		}

		$dateCreate = $this->document->dateCreate;

		if ($this->document->dateSignUntil === null)
		{
			return (new Main\Result())->addError(new Main\Error('Sign until date is required'));
		}

		if (DocumentStatus::isFinalByDocument($this->document))
		{
			return (new Main\Result())->addError(new Main\Error('Document in final status', self::ERROR_CODE_DOCUMENT_IN_FINAL_STATUS));
		}

		$result = new Result();

		$minMinutes = $this->signUntilService->getMinSigningPeriodInMinutes();
		if (
			$this->document->dateSignUntil->getTimestamp() <= ($dateCreate->getTimestamp() + $minMinutes * 60)
			|| $this->document->dateSignUntil->getTimestamp() <= (new Main\Type\DateTime())->getTimestamp()
		)
		{
			return $result->addError(new Error('End date must be at least '.$minMinutes.' minutes greater than start date', self::ERROR_CODE_PERIOD_TOO_SHORT, [
				'MIN_PERIOD_MINUTES' => $minMinutes,
			]));
		}

		if ($this->signUntilService->isSignUntilDateExceedMaxPeriod($dateCreate, $this->document->dateSignUntil))
		{
			return $result->addError(new Error('The signing period is too long', self::ERROR_CODE_PERIOD_TOO_LONG, [
				'MAX_PERIOD_MONTHS' => $this->signUntilService->getMaxSigningPeriod()->format('%m'),
			]));
		}

		return $result;
	}
}