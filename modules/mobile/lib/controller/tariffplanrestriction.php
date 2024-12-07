<?php

namespace Bitrix\Mobile\Controller;

use Bitrix\Intranet\ActionFilter\IntranetUser;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Mobile\TariffPlanRestriction\Provider\TariffPlanRestrictionProvider;

class TariffPlanRestriction extends Controller
{
	public function configureActions(): array
	{
		return [
			'getTariffPlanRestrictions' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'isDemoAvailable' => [
				'+prefilters' => [
					new CloseSession(),
					new IntranetUser(),
				],
			],
			'activateDemo' => [
				'+prefilters' => [
					new CloseSession(),
					new IntranetUser(),
				],
			],
		];
	}

	/**
	 * @restMethod mobile.tariffplanrestriction.getTariffPlanRestrictions
	 * @return AjaxJson
	 */
	public function getTariffPlanRestrictionsAction(): AjaxJson
	{
		return AjaxJson::createSuccess([
			'restrictions' => (new TariffPlanRestrictionProvider())->getTariffPlanRestrictions(),
		]);
	}

	/**
	 * @restMethod mobile.tariffplanrestriction.isDemoAvailable
	 * @return AjaxJson
	 */
	public function isDemoAvailableAction(): AjaxJson
	{
		return AjaxJson::createSuccess([
			'isDemoAvailable' => (new TariffPlanRestrictionProvider())->isDemoAvailable(),
		]);
	}

	/**
	 * @restMethod mobile.tariffplanrestriction.activateDemo
	 * @return AjaxJson
	 */
	public function activateDemoAction(): AjaxJson
	{
		return AjaxJson::createSuccess([
			'isDemoAvailable' => (new TariffPlanRestrictionProvider())->activateDemo(),
		]);
	}
}
