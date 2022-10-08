<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Crm;
use Bitrix\Main\Web\Uri;

class Manager
{
	/**
	 * Is crm-forms in use.
	 *
	 * @param string $isCallback Is callback.
	 * @return bool
	 */
	public static function isInUse($isCallback = 'N')
	{
		$filter = array();
		if (in_array($isCallback, array('N', 'Y')))
		{
			$filter['=FORM.IS_CALLBACK_FORM'] = $isCallback;
		}
		$resultDb = Internals\ResultTable::getList(array('select' => array('ID'), 'filter' => $filter, 'limit' => 1));
		if ($resultDb->fetch())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Check read permissions.
	 *
	 * @param null|\CCrmAuthorizationHelper $userPermissions User permissions.
	 * @return bool
	 */
	public static function checkReadPermission($userPermissions = null)
	{
		return \CCrmAuthorizationHelper::checkReadPermission('WEBFORM', 0, $userPermissions);
	}

	/**
	 * Check update permissions.
	 *
	 * @param null|\CCrmAuthorizationHelper $userPermissions User permissions.
	 * @return bool
	 */
	public static function checkWritePermission($userPermissions = null)
	{
		return \CCrmAuthorizationHelper::checkUpdatePermission('WEBFORM', 0, $userPermissions);
	}

	/**
	 * Get path to crm-form list page.
	 *
	 * @return string
	 */
	public static function getUrl()
	{
		return Option::get('crm', 'path_to_webform_list', '/crm/webform/');
	}

	/**
	 * Get path to crm-form edit page.
	 *
	 * @param integer $formId Form Id.
	 * @param bool $landingOnly Get link in landing editor.
	 * @return string
	 */
	public static function getEditUrl($formId = 0, $landingOnly = false)
	{
		$url = str_replace('#form_id#', $formId, Option::get('crm', 'path_to_webform_edit', '/crm/webform/edit/#form_id#/'));

		$landingUrl = $formId
			? Internals\LandingTable::getLandingEditUrl($formId)
			: null;

		return ($landingUrl || $landingOnly)
			? $landingUrl
			: $url;
	}

	public static function getCallbackNewFormEditUrl() : string
	{
		return static::getCallbackEditUrl(0);
	}

	/**
	 * Get Facebook integration scenario Form edit url
	 * @param int $formId
	 * @return string
	 */
	public static function getVkontakteIntegrationFormEditUrl(int $formId) : string
	{
		$editUrl = static::getEditUrl($formId);
		$uri = new Uri($editUrl);
		$uri->addParams(
			$formId
				? ['preset' => 'vk',]
				: ['PRESET' => 'vk',]
		);

		return $uri->getUri();
	}

	/**
	 * Get Facebook integration scenario Form edit url
	 * @param int $formId
	 * @return string
	 */
	public static function getFacebookIntegrationFormEditUrl(int $formId) : string
	{
		$editUrl = static::getEditUrl($formId);
		$uri = new Uri($editUrl);
		$uri->addParams(
			$formId
				? ['preset' => 'facebook',]
				: ['PRESET' => 'facebook',]
		);

		return $uri->getUri();
	}

	/**
	 * Get path to crm-form edit page
	 * @param int $formId
	 * @return string
	 */
	public static function getCallbackEditUrl(int $formId) : string
	{
		$editUrl = static::getEditUrl($formId);
		$uri = new Uri($editUrl);
		$uri->addParams(
			$formId
				? ['preset' => 'callback',]
				: [
				'ACTIVE' => 'Y',
				'IS_CALLBACK_FORM' => 'Y',
				'PRESET' => 'callback'
			]
		);

		return $uri->getUri();
	}

	/**
	 * @return string
	 */
	public static function getCallbackListUrl($additionalParameters = []) : string
	{
		$formEditUrl = Manager::getUrl();
		$uri = new Uri($formEditUrl);
		$uri->addParams(array_merge([
			'apply_filter' => 'Y',
			'IS_CALLBACK_FORM' => 'Y',
			'PRESET' => 'Y'
		], $additionalParameters));

		return $uri->getUri();
	}

	/**
	 * Get active non-callback form list.
	 *
	 * @param array $params Query parameters.
	 * @return array
	 */
	public static function getActiveForms($params = array('order' => array('ID' => 'DESC'), 'cache' => array('ttl' => 36000)))
	{
		if (!isset($params['filter']))
		{
			$params['filter'] = array();
		}
		$params['filter']['=ACTIVE'] = 'Y';
		$params['filter']['=IS_CALLBACK_FORM'] = 'N';

		$list = array();
		$listDb = Internals\FormTable::getDefaultTypeList($params);
		while($item = $listDb->fetch())
		{
			$list[] = $item;
		}

		return $list;
	}

	/**
	 * Get plain form list.
	 *
	 * @return array
	 */
	public static function getListPlain(array $parameters = [])
	{
		$parameters["cache"] = array("ttl" => 3600);
		return Internals\FormTable::getDefaultTypeList($parameters)->fetchAll();
	}

	/**
	 * Get list form names list.
	 *
	 * @return array
	 */
	public static function getListNames()
	{
		static $result = null;
		if (!is_array($result))
		{
			$result = array();
			$formList = self::getListPlain(['select' => ['ID', 'NAME']]);
			foreach ($formList as $form)
			{
				$result[$form['ID']] = $form['NAME'];
			}
		}

		return $result;
	}

	/**
	 * Get prepared data to entity selector component.
	 *
	 * @param string $entityId
	 * @param string $tabId
	 *
	 * @return array|array[]|null
	 */
	public static function getListForEntitySelector(string $entityId, string $tabId)
	{
		static $result = null;
		if (!is_array($result))
		{
			$formList = self::getListPlain(['select' => ['ID', 'NAME']]);
			$result = array_map(fn($form): array => [
				'id' => $form['ID'],
				'entityId' => $entityId,
				'tabs' => $tabId,
				'title' => sprintf('%s [%d]', $form['NAME'], $form['ID']),
			], $formList);
		}

		return $result;
	}

	/**
	 * Get encoded form names list.
	 *
	 * @return array
	 */
	public static function getListNamesEncoded(): array
	{
		static $result = null;

		if (!is_array($result))
		{
			$result = self::getListNames();
			foreach ($result as $id => $name)
			{
				$result[$id] = htmlspecialcharsbx($name);
			}
		}

		return $result;
	}

	public static function isEmbeddingEnabled($formId)
	{
		return !!$formId;
	}

	public static function isEmbeddingAvailable()
	{
		return true;
	}

	public static function isOrdersAvailable()
	{
		return Loader::includeModule('salescenter');
	}

	public static function updateScriptCache($fromFormId = null, $limit = 50)
	{
		$filter = [];
		if ($fromFormId)
		{
			$filter['>=ID'] = $fromFormId;
		}

		$parameters = [
			'select' => ['ID'],
			'filter' => $filter,
			'order' => ['ID' => 'ASC'],
		];
		if ($limit)
		{
			$parameters['limit'] = $limit + 1;
		}
		$forms = Internals\FormTable::getList($parameters);
		foreach ($forms as $index => $item)
		{
			if ($limit && $index >= $limit)
			{
				return $item['ID'];
			}

			$form = new Form();
			$form->loadOnlyForm($item['ID']);
			if (!$form->buildScript())
			{
				return $form->getId();
			}
		}

		return null;
	}

	public static function updateScriptCacheAgent($fromFormId = null)
	{
		/*@var $USER CUser*/
		global $USER;
		if (!is_object($USER))
		{
			$USER = new \CUser();
		}

		$resultId = self::updateScriptCache($fromFormId);
		if ($resultId)
		{
			return '\\Bitrix\\Crm\\WebForm\\Manager::updateScriptCacheAgent(' . $resultId . ');';
		}
		else
		{
			return '';
		}
	}

	/**
	 * Handler for Bitrix\Catalog\Model\Price::OnAfterUpdate.
	 *
	 * @see \Bitrix\Catalog\Model\Price
	 * @param \Bitrix\Catalog\Model\Event $event
	 */
	public static function onCatalogPriceAfterUpdate(\Bitrix\Catalog\Model\Event $event): void
	{
		if (Loader::includeModule('catalog'))
		{
			$priceId = $event->getParameter('id');
			$data = \Bitrix\Catalog\Model\Price::getCacheItem($priceId);
			if (isset($data['OLD_PRICE']) || isset($data['OLD_CURRENCY'])) // price changed
			{
				$productId = isset($data['PRODUCT_ID']) ? (int)$data['PRODUCT_ID'] : null;

				if ($productId)
				{
					$oldPrice = isset($data['OLD_PRICE']) ? (float)$data['OLD_PRICE'] : null;
					$newPrice = isset($data['PRICE']) ? (float)$data['PRICE'] : null;
					$oldCurrency = $data['OLD_CURRENCY'] ?? null;
					$newCurrency = $data['CURRENCY'] ?? null;

					$oldPrice = self::getRoundedPrice($oldPrice, $oldCurrency);
					$newPrice = self::getRoundedPrice($newPrice, $newCurrency);

					if ($oldPrice !== $newPrice || $oldCurrency !== $newCurrency)
					{
						self::updateProductFormsWithNewPrice($productId, $oldPrice, $newPrice, $oldCurrency, $newCurrency);
					}
				}
			}
		}
	}

	/**
	 * Workaround for precision issues from price update event.
	 *
	 * @param float $price
	 * @param string $currency currency ISO code
	 * @return float
	 */
	private static function getRoundedPrice(float $price, string $currency)
	{
		if (Loader::includeModule('currency'))
		{
			$price = \CCurrencyLang::CurrencyFormat($price, $currency, false);
			$price = \CCurrencyLang::getUnFormattedValue($price, $currency);
			return (float)str_replace(' ', '', $price);
		}

		return round($price, 2);
	}

	private static function updateProductFormsWithNewPrice(int $productId, float $oldPrice, float $newPrice, string $oldCurrency, string $newCurrency)
	{
		static $formFields; // cache fields

		if (is_null($formFields))
		{
			// get all product form fields
			$formFields = \Bitrix\Crm\WebForm\Internals\FieldTable::getList([
				'select' => ['ID', 'FORM_ID', 'ITEMS'],
				'filter' => [
					'=TYPE' => 'product',
				],
			]);
		}

		static $forms = []; // cache forms

		$updatedForms = [];
		// update only fields related to updated price
		foreach ($formFields as $field)
		{
			$formId = $field['FORM_ID'];
			if (!array_key_exists($formId, $forms))
			{
				$forms[$formId] = new \Bitrix\Crm\WebForm\Form($formId);
			}
			$form = $forms[$formId];

			$formCurrency = $form->getCurrencyId();
			if ($formCurrency !== $oldCurrency || $formCurrency !== $newCurrency)
			{
				continue;
			}

			$items = $field['ITEMS'] ?? [];

			$isUpdated = false;
			foreach ($items as &$item)
			{
				// update if price in form matches catalog price
				if ($productId === (int)$item['ID'] && $oldPrice === (float)$item['PRICE']) // && $item['CUSTOM_PRICE'] === 'N'
				{
					$item['PRICE'] = $newPrice;
					$isUpdated = true;
				}
			}

			if ($isUpdated)
			{
				\Bitrix\Crm\WebForm\Internals\FieldTable::update($field['ID'], ['ITEMS' => $items]);
				$updatedForms[] = $formId;
			}
		}

		// rebuild form cache
		foreach ($updatedForms as $formId)
		{
			\Bitrix\Crm\WebForm\Manager::updateScriptCache($formId, 1);
		}
	}
}
