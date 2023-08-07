<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var $arParams array
 * @var $arResult array
 * @var $this CBitrixComponent
 * @var $APPLICATION CMain
 * @var $USER CUser
 */

use Bitrix\Main\Loader;
use Bitrix\Voximplant\Security\Permissions;

class CVoxImplantComponentDocuments extends CBitrixComponent
{
	protected $showTemplate = true;

	protected function init()
	{
		if(isset($this->arParams['TEMPLATE_HIDE']) && $this->arParams['TEMPLATE_HIDE'] === 'Y')
			$this->showTemplate = false;
	}

	protected function prepareData()
	{
		$documents = new CVoxImplantDocuments();
		$request = Bitrix\Main\Context::getCurrent()->getRequest();

		$this->arResult['DOCUMENTS'] = $documents->GetStatus();
		if(is_array($this->arResult['DOCUMENTS']) && !empty($this->arResult['DOCUMENTS']))
		{
			foreach($this->arResult['DOCUMENTS'] as $key => $verification)
			{
				$this->arResult['DOCUMENTS'][$key]['COUNTRY_CODE'] = $this->arResult['DOCUMENTS'][$key]['REGION'];
				$this->arResult['DOCUMENTS'][$key]['COUNTRY'] = $this->arResult['DOCUMENTS'][$key]['REGION_NAME'];
				$this->arResult['DOCUMENTS'][$key]['ADDRESS'] = $this->arResult['DOCUMENTS'][$key]['COUNTRY'];
				$this->arResult['DOCUMENTS'][$key]['UPLOAD_URL'] = $documents->GetUploadUrl($this->arResult['DOCUMENTS'][$key]['REGION']);
				unset($this->arResult['DOCUMENTS'][$key]['REGION']);
				unset($this->arResult['DOCUMENTS'][$key]['REGION_NAME']);
			}
		}
		else
		{
			$this->arResult['DOCUMENTS'] = [];
		}

		$documents->setFilledByUser($this->getCurrentUserId());

		$addressVerification = new \Bitrix\VoxImplant\AddressVerification();
		$verifications = $addressVerification->getVerifications();
		if (isset($verifications['VERIFIED_ADDRESS']) && is_array($verifications['VERIFIED_ADDRESS']))
		{
			if(!is_array($this->arResult['DOCUMENTS']))
			{
				$this->arResult['DOCUMENTS'] = [];
			}

			foreach($verifications['VERIFIED_ADDRESS'] as $verification)
			{
				$verification['ADDRESS'] = $verification['ZIP_CODE'].', '.$verification['COUNTRY'].', '.$verification['CITY'].', '.$verification['STREET'].' '.$verification['BUILDING_NUMBER'].($verification['BUILDING_LETTER'] ? '-'.$verification['BUILDING_LETTER'] : '');
				$verification['DOCUMENTS'] = [];
				$verification['DOCUMENTS'][] = [
					'REG_ID' => $verification['ID'],
					'UPLOADED' => 'n/a',
					'OWNER' => $verification['SALUTATION'] . ' ' . $verification['FIRST_NAME'] . ' ' . $verification['LAST_NAME'],
					'DOCUMENT_STATUS' => $verification['STATUS'],
					'DOCUMENT_STATUS_NAME' => GetMessage('VOX_DOCUMENT_STATUS_' . $verification['STATUS']),
					'REVIEWER_COMMENT' => $verification['REJECT_MESSAGE']
				];
				$this->arResult['DOCUMENTS'][] = $verification;
			}
		}

		if(isset($request['SHOW_UPLOAD_IFRAME'])
				&& $request['SHOW_UPLOAD_IFRAME'] === 'Y'
				&& isset($request['UPLOAD_COUNTRY_CODE'])
		)
		{
			$addressVerification->setFilledByUser($this->getCurrentUserId());
			if(!is_array($this->arResult['DOCUMENTS']))
				$this->arResult['DOCUMENTS'] = array();

			$verificationFound = false;
			foreach($this->arResult['DOCUMENTS'] as $key => $verification)
			{
				if($verification['COUNTRY_CODE'] === $request['UPLOAD_COUNTRY_CODE'])
				{
					$verificationFound = true;
					$this->arResult['DOCUMENTS'][$key]['SHOW_UPLOAD_IFRAME'] = true;
					if(!isset($this->arResult['DOCUMENTS'][$key]['UPLOAD_IFRAME_URL']))
					{
						$iframeUrl = $documents->GetUploadUrl($request['UPLOAD_COUNTRY_CODE'], $request['UPLOAD_ADDRESS_TYPE'], $request['UPLOAD_PHONE_CATEGORY'], $request['UPLOAD_REGION_CODE'], $request['UPLOAD_REGION_ID']);
						if($iframeUrl === false)
						{
							$this->arResult['DOCUMENTS'][$key]['SHOW_UPLOAD_IFRAME'] = false;
							$this->arResult['DOCUMENTS'][$key]['STATUS'] = 'ERROR';
						}
						else
						{
							$this->arResult['DOCUMENTS'][$key]['UPLOAD_IFRAME_URL'] = $iframeUrl;
						}
					}

					break;
				}
			}

			if(!$verificationFound)
			{
				$verification = $this->createVerification($request['UPLOAD_COUNTRY_CODE'], CVoxImplantDocuments::STATUS_REQUIRED);

				$verification['SHOW_UPLOAD_IFRAME'] = true;
				if(!isset($verification['UPLOAD_IFRAME_URL']))
				{
					$iframeUrl = $documents->GetUploadUrl($request['UPLOAD_COUNTRY_CODE'], $request['UPLOAD_ADDRESS_TYPE'], $request['UPLOAD_PHONE_CATEGORY'], $request['UPLOAD_REGION_CODE'], $request['UPLOAD_REGION_ID']);
					if($iframeUrl === false)
					{
						$verification['SHOW_UPLOAD_IFRAME'] = false;
						$verification['STATUS'] = 'ERROR';
					}
					else
					{
						$verification['UPLOAD_IFRAME_URL'] = $iframeUrl;
					}
				}

				$this->arResult['DOCUMENTS'][] = $verification;
			}
		}
	}

	protected function createVerification($countryCode, $status)
	{
		return array(
			'COUNTRY_CODE' => $countryCode,
			'COUNTRY' => CVoxImplantPhone::getCountryName($countryCode),
			'ADDRESS' => CVoxImplantPhone::getCountryName($countryCode),
			'STATUS' => $status,
			'STATUS_NAME' => CVoxImplantDocuments::GetStatusName($status)
		);
	}

	protected function getCurrentUserId()
	{
		global $USER;
		return $USER->GetID();
	}

	protected function checkAccess()
	{
		$permissions = Permissions::createWithCurrentUser();
		return $permissions->canPerform(Permissions::ENTITY_SETTINGS, Permissions::ACTION_MODIFY);
	}

	/**
	 * Executes component
	 */
	public function executeComponent()
	{
		if (!Loader::includeModule('voximplant'))
			return false;

		$this->init();
		if(!$this->checkAccess())
			return false;

		$this->prepareData();
		if ($this->showTemplate)
			$this->includeComponentTemplate();

		return $this->arResult;
	}
}
