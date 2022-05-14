<?php
namespace Bitrix\Crm\Restriction;
use Bitrix\Crm\Service\Display;

class Bitrix24AccessRestriction extends AccessRestriction
{
	/** @var Bitrix24RestrictionInfo|null */
	private $restrictionInfo = null;
	private $errorMessage = null;
	private $errorCode;

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

	public function setErrorMessage(string $errorMessage): void
	{
		$this->errorMessage = $errorMessage;
	}

	public function getErrorMessage(): ?string
	{
		return $this->errorMessage;
	}

	public function setErrorCode(string $errorCode): void
	{
		$this->errorCode = $errorCode;
	}

	public function getErrorCode(): ?string
	{
		return $this->errorCode;
	}

	public function prepareDisplayOptions(int $entityTypeId, array $itemIds, Display\Options $options): void
	{
		if (empty($itemIds))
		{
			return;
		}

		$restrictedItemIds = $this->filterRestrictedItemIds(
			$entityTypeId,
			$itemIds
		);
		if (empty($restrictedItemIds))
		{
			return;
		}
		$options
			->setRestrictedItemIds($restrictedItemIds)
			->setRestrictedFieldsToShow($this->getFieldsToShow())
			->setRestrictedValueTextReplacer(\Bitrix\Main\Localization\Loc::getMessage('CRM_COMMON_HIDDEN_VALUE'))
			->setRestrictedValueHtmlReplacer('<img onclick="if(BX && BX.onCustomEvent){BX.onCustomEvent(window, \'onCrmRestrictedValueClick\')}" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTI4IiBoZWlnaHQ9IjEyIiB2aWV3Qm94PSIwIDAgMTI4IDEyIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHg9IjQyIiB3aWR0aD0iMjIiIGhlaWdodD0iMTIiIGZpbGw9IiNFREVFRUYiLz48cmVjdCB4PSI2NCIgd2lkdGg9IjIwIiBoZWlnaHQ9IjEyIiBmaWxsPSIjRTdFOEVBIi8+PHJlY3QgeD0iODQiIHdpZHRoPSIyMiIgaGVpZ2h0PSIxMiIgZmlsbD0iI0VCRUNFRSIvPjxyZWN0IHg9IjEwNiIgd2lkdGg9IjIyIiBoZWlnaHQ9IjEyIiBmaWxsPSIjRjdGN0Y4Ii8+PHJlY3Qgd2lkdGg9IjQ0IiBoZWlnaHQ9IjEyIiBmaWxsPSIjRUFFQkVEIi8+PHJlY3Qgd2lkdGg9IjQ0IiBoZWlnaHQ9IjEyIiBmaWxsPSIjRUFFQkVEIi8+PC9zdmc+Cg=="/>')
		;
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
