<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Crm\Integration\Sale\Reservation;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Catalog\Component\UseStore;
use Bitrix\Catalog\Config\Feature;
use Bitrix\Catalog\Config\Options\CheckRightsOnDecreaseStoreAmount;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\ActionFilter\ContentType;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Bitrix\Main\LoaderException;

class CCrmConfigCatalogSettings extends \CBitrixComponent implements Controllerable
{
	private const OPTION_DEFAULT_QUANTITY_TRACE = 'default_quantity_trace';
	private const OPTION_DEFAULT_CAN_BUY_ZERO = 'default_can_buy_zero';
	private const OPTION_DEFAULT_SUBSCRIBE = 'default_subscribe';
	private const OPTION_PRODUCT_CARD_SLIDER_ENABLED = 'product_card_slider_enabled';
	private const OPTION_DEFAULT_PRODUCT_VAT_INCLUDED = 'default_product_vat_included';

	private const PRODUCT_SLIDER_HELP_LINK_EU = 'https://training.bitrix24.com/support/training/course/index.php?COURSE_ID=178&LESSON_ID=25692';
	private const PRODUCT_SLIDER_HELP_LINK_RU = 'https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=48&LESSON_ID=25488';

	/**
	 * @inheritDoc
	 */
	public function executeComponent()
	{
		if (!(
			$this->checkModules()
			&& $this->hasPermissions())
		)
		{
			$this->includeComponentTemplate('denied');
			return;
		}

		$this->arResult = $this->getResult();
		$this->includeComponentTemplate();
	}

	/**
	 * @inheritDoc
	 */
	public function configureActions()
	{
		return [
			'initialize' => [
				'+prefilters' => [
					new ContentType([ContentType::JSON]),
				],
			],
			'save' => [
				'+prefilters' => [
					new ContentType([ContentType::JSON]),
				],
			],
		];
	}

	/**
	 * @return AjaxJson
	 */
	public function initializeAction(): AjaxJson
	{
		$errorResponse = $this->checkActionError();
		if (!is_null($errorResponse))
		{
			return $errorResponse;
		}

		return $this->respondSuccess($this->getResult());
	}

	/**
	 * @param array $values
	 * @return AjaxJson
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function saveAction(array $values): AjaxJson
	{
		$errorResponse = $this->checkActionError();
		if (!is_null($errorResponse))
		{
			return $errorResponse;
		}

		if (is_array($values['reservationSettings']))
		{
			foreach ($values['reservationSettings'] as $entityCode => $reservationSettingsValue)
			{
				Reservation\Config\EntityFactory::make($entityCode)
					->setValues($reservationSettingsValue)
					->save();
			}
		}

		if (isset($values['checkRightsOnDecreaseStoreAmount']))
		{
			if ($values['checkRightsOnDecreaseStoreAmount'] === true)
			{
				CheckRightsOnDecreaseStoreAmount::set(
					CheckRightsOnDecreaseStoreAmount::ENABLED_VALUE
				);
			}
			else
			{
				CheckRightsOnDecreaseStoreAmount::set(
					CheckRightsOnDecreaseStoreAmount::DISABLED_VALUE
				);
			}
		}

		$catalogOptionSettings = [
			'defaultQuantityTrace' => self::OPTION_DEFAULT_QUANTITY_TRACE,
			'defaultCanBuyZero' => self::OPTION_DEFAULT_CAN_BUY_ZERO,
			'defaultSubscribe' => self::OPTION_DEFAULT_SUBSCRIBE,
			'defaultProductVatIncluded' => self::OPTION_DEFAULT_PRODUCT_VAT_INCLUDED,
		];

		if ($this->isCanEnableProductCardSlider())
		{
			$catalogOptionSettings['productCardSliderEnabled'] = self::OPTION_PRODUCT_CARD_SLIDER_ENABLED;
		}

		foreach ($catalogOptionSettings as $key => $optionName)
		{
			if (!isset($values[$key]))
			{
				continue;
			}

			Option::set('catalog', $optionName, ($values[$key] ? 'Y' : 'N'));
		}

		return $this->respondSuccess();
	}

	/**
	 * @return AjaxJson|null
	 */
	private function checkActionError(): ?AjaxJson
	{
		if (!$this->checkModules())
		{
			return $this->respondError('Required modules have not been found');
		}

		if (!$this->hasPermissions())
		{
			return $this->respondError('Access denied');
		}

		return null;
	}

	/**
	 * @inheritDoc
	 */
	protected function listKeysSignedParameters()
	{
		return [];
	}

	/**
	 * @return array
	 */
	private function getResult(): array
	{
		$accessController = AccessController::getCurrent();

		return [
			'isStoreControlUsed' => UseStore::isUsed(),
			'isBitrix24' => static::isBitrix24(),
			'productsCnt' => $this->getProductsCnt(),
			'reservationEntities' => $this->getReservationEntities(),
			'defaultQuantityTrace' => Option::get('catalog', self::OPTION_DEFAULT_QUANTITY_TRACE) === 'Y',
			'defaultCanBuyZero' => Option::get('catalog', self::OPTION_DEFAULT_CAN_BUY_ZERO) === 'Y',
			'defaultSubscribe' => Option::get('catalog', self::OPTION_DEFAULT_SUBSCRIBE) === 'Y',
			'productCardSliderEnabled' => Option::get('catalog', self::OPTION_PRODUCT_CARD_SLIDER_ENABLED) === 'Y', // TODO: delete if not need
			'isCanEnableProductCardSlider' => $this->isCanEnableProductCardSlider(),
			'busProductCardHelpLink' => static::getBusProductCardHelpLink(),
			'defaultProductVatIncluded' => Option::get('catalog', self::OPTION_DEFAULT_PRODUCT_VAT_INCLUDED) === 'Y',
			'configCatalogSource' => \Bitrix\Main\Context::getCurrent()->getRequest()->get('configCatalogSource'),
			'checkRightsOnDecreaseStoreAmount' => CheckRightsOnDecreaseStoreAmount::isEnabled(),
			'hasAccessToCatalogSettings' => $accessController->check(ActionDictionary::ACTION_CATALOG_SETTINGS_ACCESS),
			'hasAccessToReservationSettings' => $accessController->check(ActionDictionary::ACTION_RESERVED_SETTINGS_ACCESS),
			'hasAccessToInventoryManagmentSettings' => $accessController->check(ActionDictionary::ACTION_RESERVED_SETTINGS_ACCESS),
		];
	}

	private function isCanEnableProductCardSlider(): bool
	{
		if (static::isBitrix24())
		{
			return Option::get('catalog', self::OPTION_PRODUCT_CARD_SLIDER_ENABLED) !== 'Y';
		}

		return true;
	}

	/**
	 * @return int
	 */
	private function getProductsCnt(): int
	{
		$result = 0;

		$catalogList = \CCatalogProductSettings::getCatalogList();
		foreach ($catalogList as $catalog)
		{
			$result += $catalog['COUNT'];

		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function getReservationEntities(): array
	{
		$result = [];

		$reservationEntities = Reservation\Config\EntityFactory::makeAllKnown();
		foreach ($reservationEntities as $reservationEntity)
		{
			$result[] = [
				'code' => $reservationEntity::getCode(),
				'name' => $reservationEntity::getName(),
				'settings' => [
					'scheme' => $reservationEntity::getScheme(),
					'values' => $reservationEntity->getValues(),
				],
			];
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	private function checkModules(): bool
	{
		return (
			Loader::includeModule('crm')
			&& Loader::includeModule('catalog')
			&& Loader::includeModule('sale')
		);
	}

	/**
	 * @return bool
	 */
	private function hasPermissions(): bool
	{
		$accessController = \Bitrix\Catalog\Access\AccessController::getCurrent();

		return
			(
				$accessController->check(ActionDictionary::ACTION_CATALOG_READ)
				&& $accessController->check(ActionDictionary::ACTION_CATALOG_SETTINGS_ACCESS)
			)
			||
			$accessController->check(ActionDictionary::ACTION_RESERVED_SETTINGS_ACCESS)
		;
	}

	/**
	 * @param string $message
	 * @param string $code
	 * @return AjaxJson
	 */
	private function respondError(string $message, $code = ''): AjaxJson
	{
		return AjaxJson::createError(
			new ErrorCollection([new Error($message, $code)])
		);
	}

	/**
	 * @param null $data
	 * @return AjaxJson
	 */
	private function respondSuccess($data = null): AjaxJson
	{
		return AjaxJson::createSuccess($data);
	}

	private static function isBitrix24(): bool
	{
		static $isBitrix24Included;

		if (!isset($isBitrix24Included))
		{
			$isBitrix24Included = Loader::includeModule('bitrix24');
		}

		return $isBitrix24Included;
	}

	private static function getBusProductCardHelpLink(): string
	{
		if (static::isBitrix24())
		{
			return '';
		}

		if (in_array(self::getZone(), ['ru', 'by', 'kz'], true))
		{
			return self::PRODUCT_SLIDER_HELP_LINK_RU;
		}

		return self::PRODUCT_SLIDER_HELP_LINK_EU;
	}

	private static function getZone(): string
	{
		if (self::isBitrix24())
		{
			$zone = \CBitrix24::getPortalZone();
		}
		else
		{
			$row = Bitrix\Main\Localization\LanguageTable::getList([
				'select' => ['ID'],
				'filter' => [
					'=DEF' => 'Y',
					'=ACTIVE' => 'Y'
				]
			])->fetch();

			if ($row)
			{
				return $row['ID'];
			}
		}

		return 'en';
	}
}
