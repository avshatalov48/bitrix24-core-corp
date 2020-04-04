<?php

namespace Bitrix\SalesCenter\Model;

use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\SalesCenter\Integration\LandingManager;

/**
 * @see PageTable
 *
 * Class Page
 * @package Bitrix\SalesCenter\Model
 */
class Page extends EO_Page
{
	protected $params;

	/**
	 * @return bool
	 */
	public function isActive()
	{
		if($this->getLandingId() > 0)
		{
			$landingInfo = LandingManager::getInstance()->getLanding($this->getLandingId());
			if($landingInfo && ($landingInfo['ACTIVE'] !== 'Y' || $landingInfo['SITE_ACTIVE'] !== 'Y'))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function isHidden()
	{
		return $this->getHidden();
	}

	/**
	 * @return bool
	 */
	public function isWebform()
	{
		return $this->getIsWebform();
	}

	/**
	 * @return bool
	 */
	public function isFromConnectedSite()
	{
		if($this->getLandingId() > 0)
		{
			$landingInfo = LandingManager::getInstance()->getLanding($this->getLandingId());
			return ($landingInfo && $landingInfo['SITE_ID'] === LandingManager::getInstance()->getConnectedSiteId());
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		if(empty(parent::getName()) && $this->getLandingId() > 0)
		{
			$landingInfo = LandingManager::getInstance()->getLanding($this->getLandingId());
			if($landingInfo && $landingInfo['TITLE'])
			{
				return $landingInfo['TITLE'];
			}
		}

		return parent::getName();
	}

	/**
	 * @return string
	 */
	public function getUrl()
	{
		if(empty(parent::getUrl()) && $this->getLandingId() > 0)
		{
			$landingInfo = LandingManager::getInstance()->getLanding($this->getLandingId());
			if($landingInfo && $landingInfo['PUBLIC_URL'])
			{
				$uri = new Uri($landingInfo['PUBLIC_URL']);
				if(!$uri->getHost())
				{
					return UrlManager::getInstance()->getHostUrl().$uri->getLocator();
				}
				return $uri->getLocator();
			}
		}

		return parent::getUrl();
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		$description = '';

		if($this->getLandingId() > 0)
		{
			$landingInfo = LandingManager::getInstance()->getLanding($this->getLandingId());
			if($landingInfo)
			{
				$description = $landingInfo['DESCRIPTION'];
			}
		}

		if(!$description)
		{
			$description = $this->getName();
		}

		return $description;
	}

	/**
	 * @return null|string
	 */
	public function getDateModifiedAgo()
	{
		$dateModifiedAgo = $dateModify = null;

		if($this->getLandingId() > 0)
		{
			$landingInfo = LandingManager::getInstance()->getLanding($this->getLandingId());
			if($landingInfo)
			{
				$dateModify = $landingInfo['DATE_MODIFY'];
			}
		}

		if($dateModify)
		{
			$dateModify = FormatDate('ddiff', $dateModify);
			if(preg_match('#\d+#', $dateModify, $matches))
			{
				$days = (int)$matches[0];
				if($days === 0)
				{
					$dateModifiedAgo = FormatDate('today');
				}
				elseif($days === 1)
				{
					$dateModifiedAgo = FormatDate('yesterday');
				}
				else
				{
					$dateModifiedAgo = Loc::getMessage('SALESCENTER_MODIFIED_AGO_SUFFIX', ['#DATE#' => $dateModify]);
				}
			}
		}

		return $dateModifiedAgo;
	}

	/**
	 * @return int|null
	 */
	public function getSiteId()
	{
		if($this->getLandingId() > 0)
		{
			$landingInfo = LandingManager::getInstance()->getLanding($this->getLandingId());
			if($landingInfo)
			{
				return $landingInfo['SITE_ID'];
			}
		}

		return null;
	}

	/**
	 * @return array|false
	 */
	public function getUrlPreviewData()
	{
		if($this->getLandingId() > 0)
		{
			return LandingManager::getInstance()->getLandingUrlPreviewData($this->getLandingId());
		}

		$pageController = new \Bitrix\SalesCenter\Controller\Page();
		$urlPreviewData = $pageController->getUrlDataAction($this->getUrl(), true);

		return $urlPreviewData;
	}

	/**
	 * @return string|null
	 */
	public function getCode()
	{
		if($this->getLandingId() > 0)
		{
			$landingInfo = LandingManager::getInstance()->getLanding($this->getLandingId());
			if($landingInfo)
			{
				return $landingInfo['CODE'];
			}
		}

		return null;
	}

	public function getParams(): array
	{
		if($this->params === null)
		{
			$this->params = PageParamTable::getList([
				'filter' => [
					'=PAGE_ID' => $this->getId(),
				]
			])->fetchAll();
		}

		return $this->params;
	}

	public function setParams(array $params): Page
	{
		$this->params = $params;

		return $this;
	}
}