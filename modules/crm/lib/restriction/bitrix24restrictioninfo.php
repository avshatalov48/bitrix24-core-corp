<?php
namespace Bitrix\Crm\Restriction;
use Bitrix\Main;
use \Bitrix\Crm\Integration;

class Bitrix24RestrictionInfo
{
	/** @var array|null  */
	private $htmlInfo = null;
	/** @var array|null  */
	private $popupInfo = null;
	public function __construct(array $htmlInfo = null, array $popupInfo = null)
	{
		if($htmlInfo !== null)
		{
			$this->htmlInfo = $htmlInfo;
		}

		if($popupInfo !== null)
		{
			$this->popupInfo = $popupInfo;
		}
	}

	/**
	* @return string
	*/
	public function preparePopupScript()
	{
		return $this->popupInfo !== null
			? Integration\Bitrix24Manager::prepareLicenseInfoPopupScript($this->popupInfo)
			: '';
	}
	/**
	* @return string
	*/
	public function getHtml()
	{
		return $this->htmlInfo !== null
			? Integration\Bitrix24Manager::prepareLicenseInfoHtml($this->htmlInfo)
			: '';
	}
}