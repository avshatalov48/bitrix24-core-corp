<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class VoximplantRentAjaxController extends \Bitrix\Main\Engine\Controller
{
	public function __construct(\Bitrix\Main\Request $request = null)
	{
		parent::__construct($request);

		\Bitrix\Main\Loader::includeModule('voximplant');
	}

	public function getCountriesAction()
	{
		$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE, \Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error("AUTHORIZE_ERROR");
			return null;
		}

		$result = CVoxImplantPhone::GetPhoneCategories();
		if (empty($result))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error("ERROR");
			return null;
		}

		return $result;
	}

	public function getStatesAction($country, $category)
	{
		return CVoxImplantPhone::GetPhoneCountryStates($country, $category);
	}

	public function getRegionsAction($country, $category, $state = '', $bundleSize = 0)
	{
		$result = CVoxImplantPhone::GetPhoneRegions($country, $category, $state, $bundleSize);

		return $result;
	}

	public function getPhoneNumbersAction($country, $category, $region, $offset = 0, $count = 20)
	{
		return CVoxImplantPhone::GetPhoneNumbers($country, $region, $category, $offset, $count);
	}

	public function getAvailableVerificationsAction($country, $category, $region)
	{
		$arSend['ERROR'] = '';
		$addressVerification = new \Bitrix\VoxImplant\AddressVerification();
		$result = $addressVerification->getAvailableVerifications($country, $category, $region);
		if ($result !== false)
		{
			return $result;
		}
		else
		{
			$error = $addressVerification->getError();
			$this->errorCollection[] = new \Bitrix\Main\Error($error->msg, $error->code);
			return null;
		}
	}

	public function attachNumbersAction($country, $category, $region, $numbers = null, $count = 0, $state = '', $verificationId = '', $singleSubscription = 'N', $name = '')
	{
		if (!\Bitrix\Voximplant\Limits::canManageTelephony())
		{
			$this->errorCollection[] = new \Bitrix\Main\Error('PAID_PLAN_REQUIRED');
			return null;
		}

		if (!\Bitrix\Voximplant\Limits::canRentNumber())
		{
			$this->errorCollection[] = new \Bitrix\Main\Error('LIMIT_REACHED');
			return null;
		}

		$result = CVoxImplantPhone::AttachPhoneNumber(
			$name,
			[
				'countryCode' => $country,
				'regionId' => $region,
				'number' => $numbers,
				'count' => $count,
				'countryState' => $state,
				'categoryName' => $category,
				'addressVerification' => $verificationId,
				'singleSubscription' => $singleSubscription == 'Y'
			]
		);

		if($result->isSuccess())
		{
			return $result->getData();
		}
		else
		{
			$this->errorCollection->add($result->getErrors());
			return null;
		}
	}
}
