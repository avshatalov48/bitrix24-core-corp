<?php

namespace Bitrix\Baas\UseCase\Internal;

use \Bitrix\Baas;

class GetAdsInfo
{
	public function __construct(protected Baas\Repository\ServiceRepositoryInterface $serviceRepository)
	{
	}

	public function __invoke(Request\GetAdsInfoRequest $request): Response\GetAdsInfoResult
	{
		$service = $request->service;
		$languageId = $request->languageId;

		$ads = $this->serviceRepository->getAdsInfo($service, $languageId)
			?? $this->serviceRepository->getAdsInfo($service, LANGUAGE_ID);

		return new Response\GetAdsInfoResult(
			$ads,
		);
	}
}
