<?php

namespace Bitrix\SalesCenter\Integration;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\SalesCenter\Driver;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\JsHandler;
use Bitrix\UI\Toolbar\Facade\Toolbar;

Loc::loadLanguageFile(\Bitrix\Main\Application::getDocumentRoot() . '/bitrix/modules/salescenter/install/js/salescenter/app/config.php');

class Bitrix24Manager extends Base
{
	const FEATURE_NAME = 'salescenter';
	const OPTION_PAYMENTS_COUNT_PARAM = 'payments_limit';

	const ANALYTICS_DATA_SET_INTEGRATION = 'manager-openIntegrationRequestForm-params';
	const ANALYTICS_SENDER_PAGE = 'sender_page';
	const ANALYTICS_LABEL_SALESHUB = 'saleshub';
	const ANALYTICS_LABEL_SALESHUB_RECEIVING_PAYMENT = 'receiving_payment';
	const ANALYTICS_LABEL_SALESHUB_CRM_STORE = 'crm_store';
	const ANALYTICS_LABEL_SALESHUB_CRM_FORM = 'crm_form';
	const ANALYTICS_LABEL_SALESHUB_CASHBOX = 'saleshub_cashbox';
	const ANALYTICS_LABEL_SALESHUB_DELIVERY = 'saleshub_delivery';
	const ANALYTICS_LABEL_SALESHUB_PAYSYSTEM = 'saleshub_paysystem';

	/**
	 * @return string
	 */
	protected function getModuleName()
	{
		return 'bitrix24';
	}

	/**
	 * @return bool
	 */
	public function isSalescenterFeatureEnabled()
	{
		if($this->isEnabled)
		{
			return Feature::isFeatureEnabled(static::FEATURE_NAME);
		}

		return true;
	}

	public function showTariffRestrictionButtons(string $featureName)
	{
		if($this->isEnabled)
		{
			\CBitrix24::showTariffRestrictionButtons($featureName);
		}
	}

	public function isPaymentsLimitReached()
	{
		if($this->isPaymentsRestrictionActive())
		{
			return ($this->getPaymentsCount() >= $this->getPaymentsLimit());
		}

		return false;
	}

	public function increasePaymentsCount()
	{
		$count = $this->getPaymentsCount();
		$count++;

		return $this->setPaymentsCount($count);
	}

	protected function isPaymentsRestrictionActive()
	{
		if($this->isEnabled())
		{
			return ($this->getPaymentsLimit() > 0);
		}

		return false;
	}

	protected function getPaymentsCount()
	{
		return (int) Option::get(Driver::MODULE_ID, static::OPTION_PAYMENTS_COUNT_PARAM, 0);
	}

	protected function setPaymentsCount(int $count)
	{
		Option::set(Driver::MODULE_ID, static::OPTION_PAYMENTS_COUNT_PARAM, $count);

		return $this;
	}

	protected function getPaymentsLimit()
	{
		if($this->isEnabled())
		{
			return Feature::getVariable('salescenter_create_payments');
		}

		return 0;
	}

	public function getFeedbackFormInfo(string $region): array
	{
		if(in_array($region, ['ru', 'by', 'kz']))
		{
			return ['id' => 98, 'lang' => 'ru', 'sec' => 'nk2hk1'];
		}

		if ($region === 'ua')
		{
			return ['id' => 177, 'lang' => 'ua', 'sec' => 'tejx6y'];
		}

		if($region === 'de')
		{
			return ['id' => 173, 'lang' => 'de', 'sec' => 'i23onf'];
		}

		if($region === 'la')
		{
			return ['id' => 175, 'lang' => 'la', 'sec' => 'cw3mnc'];
		}

		if($region === 'br')
		{
			return ['id' => 179, 'lang' => 'br', 'sec' => 'q8r27g'];
		}

		return ['id' => 100, 'lang' => 'en', 'sec' => 'vfrbvy'];
	}

	public function getFeedbackPaySystemOfferFormInfo(string $region): array
	{
		if(in_array($region, ['ru', 'by', 'kz']))
		{
			return ['id' => 195, 'lang' => 'ru', 'sec' => '31fgey'];
		}

		if ($region === 'ua')
		{
			return ['id' => 197, 'lang' => 'ua', 'sec' => 'xtg2bv'];
		}

		if($region === 'de')
		{
			return ['id' => 205, 'lang' => 'de', 'sec' => 'k08nzb'];
		}

		if($region === 'la')
		{
			return ['id' => 203, 'lang' => 'la', 'sec' => 'u8cxid'];
		}

		if($region === 'br')
		{
			return ['id' => 199, 'lang' => 'br', 'sec' => 'mgbzgf'];
		}

		return ['id' => 207, 'lang' => 'en', 'sec' => 'ridr07'];
	}

	public function getFeedbackSmsProviderOfferFormInfo(string $region): array
	{
		if(in_array($region, ['ru', 'by', 'kz']))
		{
			return ['id' => 211, 'lang' => 'ru', 'sec' => 'guk31o'];
		}

		if ($region === 'ua')
		{
			return ['id' => 221, 'lang' => 'ua', 'sec' => 'fz1bw1'];
		}

		if($region === 'de')
		{
			return ['id' => 215, 'lang' => 'de', 'sec' => 'svj3uk'];
		}

		if($region === 'la')
		{
			return ['id' => 219, 'lang' => 'la', 'sec' => 'gyf5i4'];
		}

		if($region === 'br')
		{
			return ['id' => 217, 'lang' => 'br', 'sec' => 'ia5nzd'];
		}

		return ['id' => 213, 'lang' => 'en', 'sec' => 'gneuhx'];
	}

	public function getFeedbackPayOrderFormInfo(string $region): array
	{
		if(in_array($region, ['ru', 'by', 'kz']))
		{
			return ['id' => 225, 'lang' => 'ru', 'sec' => 'uw30zj'];
		}

		if ($region === 'ua')
		{
			return ['id' => 227, 'lang' => 'ua', 'sec' => 'gsms2y'];
		}

		if($region === 'de')
		{
			return ['id' => 233, 'lang' => 'de', 'sec' => '7wuccd'];
		}

		if($region === 'la')
		{
			return ['id' => 231, 'lang' => 'la', 'sec' => '499tjt'];
		}

		if($region === 'br')
		{
			return ['id' => 229, 'lang' => 'br', 'sec' => 'v0atah'];
		}

		return ['id' => 235, 'lang' => 'en', 'sec' => 'pnvsu7'];
	}

	public function getFeedbackDeliveryOfferFormInfo(string $region): array
	{
		if(in_array($region, ['ru', 'by', 'kz']))
		{
			return ['id' => 249, 'lang' => 'ru', 'sec' => '0u00kv'];
		}

		if ($region === 'ua')
		{
			return ['id' => 239, 'lang' => 'ua', 'sec' => 'ieo8ff'];
		}

		if($region === 'de')
		{
			return ['id' => 245, 'lang' => 'de', 'sec' => 'sae8g5'];
		}

		if($region === 'la')
		{
			return ['id' => 241, 'lang' => 'la', 'sec' => 'z16uux'];
		}

		if($region === 'br')
		{
			return ['id' => 243, 'lang' => 'br', 'sec' => '54dfz5'];
		}

		return ['id' => 247, 'lang' => 'en', 'sec' => 'jy7wgu'];
	}

	public function getFeedbackPaySystemSbpOfferFormInfo($region)
	{
		return ['id' => 263, 'sec' => '7q205j', 'code' => 'b5309667'];
	}

	/**
	 * @param $region
	 * @return array
	 */
	public function getIntegrationRequestFormInfo($region): array
	{
		if (LANGUAGE_ID === 'ua')
		{
			return ['id' => 1293, 'lang' => 'ua', 'sec' => 'vnb6hi'];
		}

		switch ($region)
		{
			case 'ru':
				return ['id' => 1291, 'lang' => 'ru', 'sec' => 'a9byq4'];
			case 'by':
				return ['id' => 1297, 'lang' => 'ru', 'sec' => 'b9rrf5'];
			case 'kz':
				return ['id' => 1298, 'lang' => 'ru', 'sec' => '6xe72g'];
			default:
				return ['id' => 1291, 'lang' => 'ru', 'sec' => 'a9byq4'];
		}
	}

	/**
	 * @return bool
	 */
	public function isIntegrationRequestPossible(): bool
	{
		$isPortalValidForIntegration = in_array(
			$this->getPortalZone(),
			['ru', 'ua', 'by', 'kz']
		);
		$doesFormHavePortalLanguage = in_array(LANGUAGE_ID, ['ru', 'ua']);

		return $isPortalValidForIntegration && $doesFormHavePortalLanguage;
	}

	/**
	 * @return string
	 */
	public function getPortalZone()
	{
		if($this->isEnabled())
		{
			return \CBitrix24::getPortalZone();
		}

		return null;
	}

	/**
	 * @return string
	 */
	public function getLicenseType()
	{
		if($this->isEnabled())
		{
			return \CBitrix24::getLicenseType();
		}

		return null;
	}

	/**
	 * @param $userId
	 * @return bool
	 */
	public function IsPortalAdmin($userId)
	{
		if($this->isEnabled())
		{
			return \CBitrix24::IsPortalAdmin($userId);
		}

		return false;
	}

	/**
	 * @param array|string $zone
	 * @return bool
	 */
	public function isCurrentZone($zone)
	{
		if ($this->isEnabled())
		{
			if(is_array($zone))
			{
				return in_array($this->getPortalZone(), $zone);
			}
			elseif(is_string($zone))
			{
				return $this->getPortalZone() == $zone;
			}
		}

		return false;
	}

	public function addFeedbackButtonToToolbar()
	{
		if($this->isEnabled() && Loader::includeModule('ui'))
		{
			Extension::load(['salescenter.manager']);
			Toolbar::addButton([
				'color' => Color::LIGHT_BORDER,
				'click' => new JsHandler('BX.Salescenter.Manager.openFeedbackForm'),
				'text' => Loc::getMessage('SALESCENTER_FEEDBACK'),
				'dataset' => [
					'toolbar-collapsed-icon' => \Bitrix\UI\Buttons\Icon::INFO
				],
			]);
		}
	}

	public function addIntegrationRequestButtonToToolbar($params = [])
	{
		if ($this->isEnabled() && $this->isIntegrationRequestPossible() && Loader::includeModule('ui'))
		{
			Extension::load(['salescenter.manager']);

			$button = [
				'color' => Color::LIGHT_BORDER,
				'click' => new JsHandler('BX.Salescenter.Manager.openIntegrationRequestForm'),
				'text' => Loc::getMessage('SALESCENTER_LEFT_PAYMENT_INTEGRATION_MSGVER_2'),
				'dataset' => ['toolbar-collapsed-icon' => \Bitrix\UI\Buttons\Icon::INFO],
			];

			if (count($params) > 0)
			{
				$button['dataset'][self::ANALYTICS_DATA_SET_INTEGRATION] = self::prepareParamsIntegrationRequest($params);
			}

			Toolbar::addButton($button);
		}
	}

	static private function prepareParamsIntegrationRequest(array $params = []): ?string
	{
		$list = [];

		if (count($params) > 0)
		{
			foreach ($params as $name => $value)
			{
				$list[] = $name . ':' . $value;
			}

			return implode(',', $list);
		}

		return null;
	}

	static private function renderAttrDataSet(array $params): string
	{
		return "data-" . self::ANALYTICS_DATA_SET_INTEGRATION . "=" . self::prepareParamsIntegrationRequest($params);
	}

	public function renderFeedbackButton()
	{
		if($this->isEnabled() && Loader::includeModule('ui'))
		{
			Extension::load(['salescenter.manager']);
			echo '<button class="ui-btn ui-btn-md ui-btn-light-border" onclick="BX.Salescenter.Manager.openFeedbackForm(event);">'.Loc::getMessage('SALESCENTER_FEEDBACK').'</button>';
		}
	}

	public function renderIntegrationRequestButton(array $params = [])
	{
		if($this->isEnabled() && $this->isIntegrationRequestPossible() && Loader::includeModule('ui'))
		{
			Extension::load(['salescenter.manager']);
			echo '<button class="ui-btn ui-btn-md ui-btn-light-border" ' . self::renderAttrDataSet($params) . ' onclick="BX.Salescenter.Manager.openIntegrationRequestForm(event);">' . Loc::getMessage('SALESCENTER_LEFT_PAYMENT_INTEGRATION_MSGVER_2') . '</button>';
		}
	}

	public function renderFeedbackSmsProviderOfferButton()
	{
		if($this->isEnabled() && Loader::includeModule('ui'))
		{
			Extension::load(['salescenter.manager']);
			echo '<button class="ui-btn ui-btn-md ui-btn-light-border" onclick="BX.Salescenter.Manager.openFeedbackFormParams(event, '.\CUtil::PhpToJSObject(['feedback_type'=>'smsprovider_offer']).', '.\CUtil::PhpToJSObject(['width'=>intval(735)], false, false, true).');">'.Loc::getMessage('SALESCENTER_FEEDBACK').'</button>';
		}
	}

	public function renderFeedbackPayOrderOfferButton()
	{
		if($this->isEnabled() && Loader::includeModule('ui'))
		{
			Extension::load(['salescenter.manager']);
			echo '<button class="ui-btn ui-btn-md ui-btn-light-border" onclick="BX.Salescenter.Manager.openFeedbackFormParams(event, '.\CUtil::PhpToJSObject(['feedback_type'=>'pay_order']).', '.\CUtil::PhpToJSObject(['width'=>intval(735)], false, false, true).');">'.Loc::getMessage('SALESCENTER_FEEDBACK').'</button>';
		}
	}

	public function renderFeedbackDeliveryOfferButton()
	{
		if($this->isEnabled() && Loader::includeModule('ui'))
		{
			Extension::load(['salescenter.manager']);
			echo '<button class="ui-btn ui-btn-sm ui-btn-light-border" onclick="BX.Salescenter.Manager.openFeedbackFormParams(event, '.\CUtil::PhpToJSObject(['feedback_type'=>'delivery_offer']).', '.\CUtil::PhpToJSObject(['width'=>intval(735)], false, false, true).');">'.Loc::getMessage('SALESCENTER_FEEDBACK').'</button>';
		}
	}
}