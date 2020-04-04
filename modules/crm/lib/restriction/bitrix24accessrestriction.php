<?php
namespace Bitrix\Crm\Restriction;
class Bitrix24AccessRestriction extends AccessRestriction
{
	/** @var Bitrix24RestrictionInfo|null */
	private $restrictionInfo = null;
	public function __construct($name = '', $permitted = false, array $htmlInfo = null, array $popupInfo = null)
	{
		parent::__construct($name, $permitted);
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