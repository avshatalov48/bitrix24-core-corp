<?php
namespace Bitrix\Crm\Restriction;
class Bitrix24QuantityRestriction extends QuantityRestriction
{
	/** @var Bitrix24RestrictionInfo|null */
	protected $restrictionInfo = null;
	public function __construct($name = '', $limit = 0, array $htmlInfo = null, array $popupInfo = null)
	{
		parent::__construct($name, $limit);
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
	public function prepareInfoHelperScript()
	{
		return $this->restrictionInfo->prepareInfoHelperScript();
	}
	/**
	* @return string
	*/
	public function getHtml()
	{
		return $this->restrictionInfo->getHtml();
	}

	/**
	 * Get id for tariff slider
	 * @return string
	 */
	public function getInfoHelperId(): string
	{
		return $this->restrictionInfo->getInfoHelperId();
	}

	/**
	 * Get id for tariff slider in mobile app
	 * @return string
	 */
	public function getMobileInfoHelperId(): string
	{
		return $this->restrictionInfo->getMobileInfoHelperId();
	}
}
