<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;

class Bitrix24QuantityRestriction extends QuantityRestriction
{
	protected const CACHE_DIR = '/crm/entity_count/';
	protected const CACHE_TTL = 60 * 60; // 1 hour

	protected ?Bitrix24RestrictionInfo $restrictionInfo;
	protected ?Cache $cache;

	public function __construct($name = '', $limit = 0, array $htmlInfo = null, array $popupInfo = null)
	{
		parent::__construct($name, $limit);

		$this->restrictionInfo = new Bitrix24RestrictionInfo($htmlInfo, $popupInfo);
		$this->cache = Application::getInstance()->getCache();
	}

	public function preparePopupScript(): ?string
	{
		return $this->restrictionInfo->preparePopupScript();
	}

	public function prepareInfoHelperScript(): ?string
	{
		return $this->restrictionInfo->prepareInfoHelperScript();
	}

	public function getHtml(): ?string
	{
		return $this->restrictionInfo->getHtml();
	}

	public function getInfoHelperId(): string
	{
		return $this->restrictionInfo->getInfoHelperId();
	}
	
	public function getMobileInfoHelperId(): string
	{
		return $this->restrictionInfo->getMobileInfoHelperId();
	}
}
