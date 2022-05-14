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
	 * @param array|null $params
	 * @return array|null
	 */
	public function prepareStubInfo(array $params = null)
	{
		if($params === null)
		{
			$params = array();
		}

		if($this->popupInfo !== null)
		{
			$params = array_merge($this->popupInfo, $params);
		}
		return Integration\Bitrix24Manager::prepareStubInfo($params);
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
	public function prepareInfoHelperScript()
	{
		return $this->popupInfo !== null
			? Integration\Bitrix24Manager::prepareLicenseInfoHelperScript($this->popupInfo)
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

	public function getInfoHelperId(): string
	{
		return  (string)$this->popupInfo['ID'];
	}

	public function getMobileInfoHelperId(): string
	{
		return  (string)($this->popupInfo['MOBILE_ID'] ?? $this->getInfoHelperId() . '_mobile');
	}
}
