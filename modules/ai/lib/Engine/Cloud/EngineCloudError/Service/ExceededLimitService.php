<?php declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud\EngineCloudError\Service;

use Bitrix\AI\Container;
use Bitrix\AI\Engine\Cloud\EngineCloudError\Dto\ExceededLimitDto;
use Bitrix\AI\Integration\Baas\BaasTokenService;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Util;

class ExceededLimitService
{
	protected const SLIDER_CODE_REQUESTS = 'limit_copilot_requests_box';
	protected const SLIDER_CODE_BOOST = 'limit_boost_copilot_box';
	protected const SLIDER_CODE_BOX = 'limit_copilot_box';

	protected const ERROR_CODE_LIMIT_STANDARD = 'LIMIT_IS_EXCEEDED_MONTHLY';
	protected const ERROR_CODE_LIMIT_BAAS = 'LIMIT_IS_EXCEEDED_BAAS';
	protected const ERROR_CODE_RATE_LIMIT = 'RATE_LIMIT';

	protected const RATE_LIMIT_HELP_CODE = '24736310';

	public function mapExceededLimitError(Error $errorLimit): Error
	{
		$exceededLimitDto = $this->getErrorsLimitRules($errorLimit);

		return new Error(
			$this->getMessageByErrorCode($exceededLimitDto->errorCode),
			$exceededLimitDto->errorCode,
			[
				'sliderCode' => $exceededLimitDto->sliderCode,
				'showSliderWithMsg' => $exceededLimitDto->showSliderWithMsg,
				'msgForIm' => $exceededLimitDto->msgForIm,
				'mainData' => $exceededLimitDto->toArray()
			]
		);
	}

	protected function getMessageByErrorCode(string $errorCode): string
	{
		if ($errorCode === static::ERROR_CODE_RATE_LIMIT)
		{
			return Loc::getMessage(
				'AI_ENGINE_ERROR_RATE_LIMIT_IS_EXCEEDED',
				[
					'[helpdesklink]' => '<a href="' . $this->getLinkOnHelp() . '" target="blank">',
					'[/helpdesklink]' => '</a>',
				]
			);
		}

		return Loc::getMessage('AI_ENGINE_ERROR_LIMIT_IS_EXCEEDED');
	}

	protected function getErrorsLimitRules(Error $errorLimit): ExceededLimitDto
	{
		$errorData = $errorLimit->getCustomData();
		$isAvailableBaas = $this->isBaasAvailable();
		$errorCode = $this->getErrorCode($errorData, $isAvailableBaas);
		$sliderCode = static::SLIDER_CODE_REQUESTS;

		$msgForIm = Loc::getMessage(
			'AI_ENGINE_ERROR_LIMIT_IS_EXCEEDED_WITH_MORE',
			[
				'#LINK#' => '/online/?FEATURE_PROMOTER=' . $sliderCode,
			]
		);

		if (empty($errorData['baasAvailable']))
		{
			return new ExceededLimitDto(
				showSliderWithMsg: false,
				sliderCode: $sliderCode,
				errorCode: $errorCode,
				msgForIm: $msgForIm,
				isAvailableBaas: false
			);
		}

		$sliderCode = $isAvailableBaas ? static::SLIDER_CODE_BOOST : static::SLIDER_CODE_BOX;


		if ($errorCode === static::ERROR_CODE_RATE_LIMIT)
		{
			$msgForIm = Loc::getMessage(
				'AI_ENGINE_ERROR_IM_RATE_LIMIT_IS_EXCEEDED',
				[
					'#LINK#' => $this->getLinkOnHelp(),
				]
			);

			$sliderCode = 'redirect=detail&code=' . static::RATE_LIMIT_HELP_CODE;
		}
		else
		{
			$msgForIm = Loc::getMessage(
				'AI_ENGINE_ERROR_LIMIT_BAAS',
				[
					'#LINK#' => '/online/?FEATURE_PROMOTER=' . $sliderCode,
				]
			);
		}

		return new ExceededLimitDto(
			showSliderWithMsg: !$isAvailableBaas,
			sliderCode: $sliderCode,
			errorCode: $errorCode,
			msgForIm: $msgForIm,
			isAvailableBaas: $isAvailableBaas
		);
	}

	protected function isBaasAvailable(): bool
	{
		/** @var BaasTokenService $baasTokenService */
		$baasTokenService = Container::init()->getItem(BaasTokenService::class);

		return $baasTokenService->isAvailable();
	}

	protected function getErrorCode(array $errorData, bool $isAvailableBaas)
	{
		if (empty($errorData['errorLimitType']))
		{
			return static::ERROR_CODE_LIMIT_STANDARD;
		}

		if ($errorData['errorLimitType'] === static::ERROR_CODE_RATE_LIMIT)
		{
			return static::ERROR_CODE_RATE_LIMIT;
		}

		if ($isAvailableBaas)
		{
			return static::ERROR_CODE_LIMIT_BAAS;
		}

		return $errorData['errorLimitType'];
	}

	private function getLinkOnHelp(): string
	{
		return Loader::includeModule('ui')
			? Util::getArticleUrlByCode(static::RATE_LIMIT_HELP_CODE)
			: 'https://helpdesk.bitrix24.ru/open/' . static::RATE_LIMIT_HELP_CODE;
	}
}
