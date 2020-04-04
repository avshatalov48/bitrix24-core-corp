<?php
namespace Bitrix\Crm\Restriction;
class Bitrix24SqlRestriction extends SqlRestriction
{
	/** @var Bitrix24RestrictionInfo|null */
	private $restrictionInfo = null;
	public function __construct($name = '', $threshold = 0, array $htmlInfo = null, array $popupInfo = null)
	{
		parent::__construct($name, $threshold);
		$this->restrictionInfo = new Bitrix24RestrictionInfo($htmlInfo, $popupInfo);
	}
	/**
	* @return string
	*/
	public function preparePopupScript()
	{
		return $this->restrictionInfo->preparePopupScript();
	}
	/**
	* @return string
	*/
	public function getHtml()
	{
		return $this->restrictionInfo->getHtml();
	}
}