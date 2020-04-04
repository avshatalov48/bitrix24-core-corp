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

class Bitrix24Manager extends Base
{
	const FEATURE_NAME = 'salescenter';
	const OPTION_PAYMENTS_COUNT_PARAM = 'payments_limit';

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
		if($this->isEnabled())
		{
			return Feature::isFeatureEnabled(static::FEATURE_NAME);
		}

		return true;
	}

	public function showTariffRestrictionButtons(string $featureName)
	{
		if($this->isEnabled())
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

	/**
	 * @param string $region
	 * @return array
	 */
	public function getFeedbackFormInfo($region)
	{
		if(in_array($region, ['ru', 'by', 'kz', 'ua']))
		{
			return ['id' => 98, 'lang' => 'ru', 'sec' => 'nk2hk1'];
		}
		else
		{
			return ['id' => 100, 'lang' => 'en', 'sec' => 'vfrbvy'];
		}
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
				'icon' => \Bitrix\UI\Buttons\Icon::INFO,
				"className" => 'ui-toolbar-btn-icon-hidden',
			]);
		}
	}

	public function renderFeedbackButton()
	{
		if($this->isEnabled() && Loader::includeModule('ui'))
		{
			Extension::load(['salescenter.manager']);
			echo '<button class="ui-btn ui-btn-md ui-btn-light-border" onclick="BX.Salescenter.Manager.openFeedbackForm(event);">'.Loc::getMessage('SALESCENTER_FEEDBACK').'</button>';
		}
	}
}