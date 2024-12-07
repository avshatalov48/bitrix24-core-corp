<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Uri;
use Bitrix\Rest\PlacementTable;
use Bitrix\Rest\Sqs;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\OAuthService;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

/**
 * class IntranetPlacementComponent
 */
class IntranetPlacementComponent extends \CBitrixComponent implements Controllerable
{
	private const MAX_OVERTIME_COUNT = 10;
	private const MAX_LOAD_DELAY = 5000;
	private const MAX_OVERTIME_RESET_COUNT_OPTION_NAME = 'max_overtime_reset_count_time';
	private const DEFAULT_PLACEMENT_CODE = \CIntranetRestService::PAGE_BACKGROUND_WORKER_PLACEMENT;

	protected function listKeysSignedParameters()
	{
		return [
			'PLACEMENT_CODE',
		];
	}

	public function onPrepareComponentParams($arParams): array
	{
		$arParams['PLACEMENT_CODE'] = !empty($arParams['PLACEMENT_CODE'])
			? $arParams['PLACEMENT_CODE']
			: self::DEFAULT_PLACEMENT_CODE
		;

		return parent::onPrepareComponentParams($arParams);
	}

	private function getPlacamentList($placementCode)
	{
		$result = [];
		$server = Application::getInstance()->getContext()->getServer();

		$res = PlacementTable::getList(
			[
				'filter' => [
					'=PLACEMENT' => $placementCode,
				],
				'cache' => [
					'ttl' => 86400
				]
			]
		);

		while ($handler = $res->fetch())
		{
			$result[] = [
				'ID' => 'placement_rest_' . $handler['ID'],
				'PLACEMENT' => $placementCode,
				'PLACEMENT_OPTIONS' => [
					'ID' => $placementCode,
					'URI' => $server->getRequestUri(),
				],
				'APP_ID' => $handler['APP_ID'],
				'PLACEMENT_ID' => $handler['ID'],
			];
		}

		return $result;
	}

	public function executeComponent()
	{
		if (!Loader::includeModule('rest'))
		{
			return false;
		}

		$serviceUrl = '/bitrix/components/bitrix/app.layout/lazyload.ajax.php?' . bitrix_sessid_get();
		$uri = new Uri($serviceUrl);
		$uri->addParams(
			[
				'site' => SITE_ID,
			]
		);
		$this->arResult['SERVICE_URL'] = $uri->getUri();
		$this->arResult['MAX_LOAD_DELAY'] = self::MAX_LOAD_DELAY;

		$this->arResult['ITEMS'] = $this->getPlacamentList($this->arParams['PLACEMENT_CODE']);

		if (sizeof($this->arResult['ITEMS']) > 0)
		{
			$this->includeComponentTemplate();
		}
		return true;
	}

	public function setLongLoadAction($item)
	{
		$res = PlacementTable::getList(
			[
				'filter' => [
					'=ID' => $item['PLACEMENT_ID'],
					'=PLACEMENT' => $this->arParams['PLACEMENT_CODE'],
					'=APP_ID' => $item['APP_ID'],
				],
				'select' => [
					'ID',
					'OPTIONS',
					'APP_ID',
					'PLACEMENT',
				],
			]
		);
		if ($handler = $res->fetch())
		{
			if (!$handler['OPTIONS']['maxTimeCount'] || (int)Option::get('rest', self::MAX_OVERTIME_RESET_COUNT_OPTION_NAME, 0) < time())
			{
				$handler['OPTIONS']['maxTimeCount'] = 0;
				Option::set('rest', self::MAX_OVERTIME_RESET_COUNT_OPTION_NAME, (new DateTime())->add('+1 day')->getTimestamp());
			}
			$handler['OPTIONS']['maxTimeCount']++;

			if ($handler['OPTIONS']['maxTimeCount'] > self::MAX_OVERTIME_COUNT)
			{
				PlacementTable::delete(
					$handler['ID']
				);

				if (!empty($handler['OPTIONS']['errorHandlerUrl']))
				{
					$app = AppTable::getByClientId($handler['APP_ID']);
					if (!empty($app['CLIENT_ID']))
					{
						$queryItems = [
							Sqs::queryItem(
								$app['CLIENT_ID'],
								$handler['OPTIONS']['errorHandlerUrl'],
								[
									'error' => 'ERROR_PLACEMENT_LOADING_OVERTIME',
									'error_description' => Loc::getMessage(
										'ERROR_PLACEMENT_LOADING_OVERTIME',
										[
											'#MAX_LOADING_TIME#' => self::MAX_LOAD_DELAY / 1000,
											'#OVERTIME_COUNT#' => self::MAX_OVERTIME_COUNT,
										]
									),
								],
								[],
								[
									"sendAuth" => false,
									"sendRefreshToken" => false,
									"category" => Sqs::CATEGORY_DEFAULT,
								]
							),
						];
					}

					OAuthService::getEngine()->getClient()->sendEvent($queryItems);
				}
			}
			else
			{
				PlacementTable::update(
					$handler['ID'],
					[
						'OPTIONS' => $handler['OPTIONS'],
					]
				);
			}
		}
		return [
			'success' => 'Y'
		];
	}

	public function configureActions()
	{
		return [
			'setLongLoad' => [],
		];
	}
}