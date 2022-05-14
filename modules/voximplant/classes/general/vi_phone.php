<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Voximplant as VI;
use Bitrix\Main\Localization\Loc;

class CVoxImplantPhone
{

	const PHONE_TYPE_FIXED = 'GEOGRAPHIC';
	const PHONE_TYPE_TOLLFREE = 'TOLLFREE';
	const PHONE_TYPE_TOLLFREE804 = 'TOLLFREE804';
	const PHONE_TYPE_MOSCOW495 = 'MOSCOW495';
	const PHONE_TYPE_MOBILE = 'MOBILE';
	const PHONE_TYPE_NATIONAL = 'NATIONAL';
	const PHONE_TYPE_SPECIAL = 'SPECIAL';
	const PHONE_TYPE_GEO_CATEGORY_1 = 'GEO_CATEGORY1';
	const PHONE_TYPE_GEO_CATEGORY_2 = 'GEO_CATEGORY2';

	const PHONE_USER_WORK = 'WORK_PHONE';
	const PHONE_USER_PERSONAL = 'PERSONAL_PHONE';
	const PHONE_USER_MOBILE = 'PERSONAL_MOBILE';
	const PHONE_USER_INNER = 'UF_PHONE_INNER';

	const CACHE_TTL = 31536000;

	public static function GetUserPhone($userId)
	{
		$userRecord = \Bitrix\Main\UserTable::getRow([
			'select' => ['WORK_PHONE', 'PERSONAL_PHONE', 'PERSONAL_MOBILE'],
			'filter' => ['=ID' => $userId]
		]);
		if (!is_array($userRecord))
		{
			return false;
		}
		foreach ($userRecord as $key => $value)
		{
			$userRecord[$key] = CVoxImplantPhone::stripLetters($value);
		}
		if ($userRecord['PERSONAL_MOBILE'] != '')
		{
			return $userRecord['PERSONAL_MOBILE'];
		}
		if ($userRecord['PERSONAL_PHONE'] != '')
		{
			return $userRecord['PERSONAL_PHONE'];
		}
		if ($userRecord['WORK_PHONE'] != '')
		{
			return $userRecord['WORK_PHONE'];
		}

		return false;
	}

	public static function stripLetters($number)
	{
		return preg_replace("/[^0-9\#\*\+,;]/i", "", $number);
	}

	public static function Normalize($number, $minLength = 10)
	{
		if (mb_substr($number, 0, 2) == '+8')
		{
			$number = '008'.mb_substr($number, 2);
		}
		$number = self::stripLetters($number);
		$number = str_replace("+", "", $number);
		if (mb_substr($number, 0, 2) == '80' || mb_substr($number, 0, 2) == '81' || mb_substr($number, 0, 2) == '82')
		{
		}
		else if (mb_substr($number, 0, 2) == '00')
		{
			$number = mb_substr($number, 2);
		}
		else if (mb_substr($number, 0, 3) == '011')
		{
			$number = mb_substr($number, 3);
		}
		else if (mb_substr($number, 0, 1) == '8' && mb_strlen($number) === 11)
		{
			$number = '7'.mb_substr($number, 1);
		}
		else if (mb_substr($number, 0, 1) == '0')
		{
			$number = mb_substr($number, 1);
		}

		if($minLength > 0 && mb_strlen($number) < $minLength)
		{
			return false;
		}

		return $number;
	}

	public static function GetCategories()
	{
		return array(
			self::PHONE_TYPE_FIXED,
			self::PHONE_TYPE_TOLLFREE,
			self::PHONE_TYPE_TOLLFREE804,
			self::PHONE_TYPE_MOSCOW495,
			self::PHONE_TYPE_MOBILE,
			self::PHONE_TYPE_NATIONAL,
			self::PHONE_TYPE_SPECIAL,
			self::PHONE_TYPE_GEO_CATEGORY_1,
			self::PHONE_TYPE_GEO_CATEGORY_2,
		);
	}

	public static function SynchronizeUserPhones()
	{
		$offset = intval(COption::GetOptionInt("voximplant", "sync_offset", 0));

		$result = \Bitrix\Main\UserTable::getList(Array(
			'select' => Array('ID', 'WORK_PHONE', 'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'UF_PHONE_INNER'),
			'limit' => 100,
			'offset' => $offset,
			'order' => 'ID'
		));
		$count = 0;
		while($user = $result->fetch())
		{
			$user["WORK_PHONE"] = CVoxImplantPhone::Normalize($user["WORK_PHONE"]);
			if ($user["WORK_PHONE"])
			{
				VI\PhoneTable::merge(['USER_ID' => intval($user['ID']), 'PHONE_NUMBER' => $user["WORK_PHONE"], 'PHONE_MNEMONIC' => "WORK_PHONE"]);
			}

			$user["PERSONAL_PHONE"] = CVoxImplantPhone::Normalize($user["PERSONAL_PHONE"]);
			if ($user["PERSONAL_PHONE"])
			{
				VI\PhoneTable::merge(Array('USER_ID' => intval($user['ID']), 'PHONE_NUMBER' => $user["PERSONAL_PHONE"], 'PHONE_MNEMONIC' => "PERSONAL_PHONE"));
			}

			$user["PERSONAL_MOBILE"] = CVoxImplantPhone::Normalize($user["PERSONAL_MOBILE"]);
			if ($user["PERSONAL_MOBILE"])
			{
				VI\PhoneTable::merge(Array('USER_ID' => intval($user['ID']), 'PHONE_NUMBER' => $user["PERSONAL_MOBILE"], 'PHONE_MNEMONIC' => "PERSONAL_MOBILE"));
			}

			$user["UF_PHONE_INNER"] = intval(preg_replace("/[^0-9]/i", "", $user["UF_PHONE_INNER"]));
			if ($user["UF_PHONE_INNER"] > 0 && $user["UF_PHONE_INNER"] < 10000)
			{
				VI\PhoneTable::merge(Array('USER_ID' => intval($user['ID']), 'PHONE_NUMBER' => $user["UF_PHONE_INNER"], 'PHONE_MNEMONIC' => "UF_PHONE_INNER"));
			}
			$count++;
		}
		if ($count > 0)
		{
			$offset = $offset+100;
			COption::SetOptionInt("voximplant", "sync_offset", $offset);
			return "CVoxImplantPhone::SynchronizeUserPhones();";
		}
		else
		{
			COption::RemoveOption("voximplant", "sync_offset");
			return false;
		}
	}

	public static function GetCallerId()
	{
		$arResult['PHONE_NUMBER'] = '';
		$arResult['PHONE_NUMBER_FORMATTED'] = '';
		$arResult['VERIFIED'] = false;
		$arResult['VERIFIED_UNTIL'] = '';

		$ViHttp = new CVoxImplantHttp();
		$result = $ViHttp->GetCallerIDs();

		if ($result && !empty($result->result))
		{
			$phone = array_shift($result->result);

			COption::SetOptionString("voximplant", "backphone_number", $phone->callerid_number);

			$arResult['PHONE_NUMBER'] = $phone->callerid_number;
			$arResult['PHONE_NUMBER_FORMATTED'] = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($phone->callerid_number)->format();
			$arResult['VERIFIED'] = $phone->verified;
			$arResult['VERIFIED_UNTIL'] = ConvertTimeStamp($phone->verified_until_ts+CTimeZone::GetOffset()+date("Z"), 'FULL');
		}

		return $arResult;
	}

	public static function ActivateCallerID($number, $code)
	{
		$number = CVoxImplantPhone::Normalize($number);
		if ($number && $code <> '')
		{
			$ViHttp = new CVoxImplantHttp();
			$ViHttp->ClearConfigCache();
			$result = $ViHttp->ActivateCallerID($number, $code);
			if ($result)
			{
				return Array(
					'NUMBER' => $result->callerid_number,
					'VERIFIED' => $result->verified,
					'VERIFIED_UNTIL' => $result->verified_until,
				);
			}
		}
		return false;
	}

	public static function GetLinkNumber()
	{
		return COption::GetOptionString("voximplant", "backphone_number", "");
	}

	public static function GetPhoneCategories()
	{
		$arResult = Array();

		$viAccount = new CVoxImplantAccount();
		$currency = $viAccount->GetAccountCurrency();

		$viHttp = new CVoxImplantHttp();
		$result = $viHttp->GetPhoneNumberCategories();
		if ($result && !empty($result->result))
		{
			foreach ($result->result as $value)
			{
				$categories = Array();

				$countryName = GetMessage('VI_PHONE_CODE_'.$value->country_code);
				if ($countryName == '')
					$countryName = $value->country_code.' (+'.$value->phone_prefix.')';

				foreach ($value->phone_categories as $category)
				{
					if ($category->phone_category_name === "TOLLFREE" && $value->country_code != "RU")
					{
						$title = Loc::getMessage("VI_PHONE_CATEGORY_TOLLFREE_OTHER");
					}
					else if (!($title = Loc::getMessage("VI_PHONE_CATEGORY_" . $category->phone_category_name)))
					{
						$title = $category->phone_category_name;
					}

					$categories[$category->phone_category_name] = Array(
						'PHONE_TYPE' => $category->phone_category_name,
						'HAS_STATES' => $category->country_has_states,
						'FULL_PRICE' => floatval($category->phone_price)+floatval($category->phone_installation_price),
						'INSTALLATION_PRICE' => $category->phone_installation_price,
						'MONTH_PRICE' => $category->phone_price,
						'CURRENCY' => $currency,
						'TITLE' => $title
					);
				}

				$arResult[$value->country_code] = Array(
					'CAN_LIST_PHONES' => $value->can_list_phone_numbers,
					'NAME' => $countryName,
					'CODE' => $value->country_code,
					'CATEGORIES' => $categories
				);

				$accountLang = ToUpper($viAccount->GetAccountLang());
				uasort($arResult, function($a, $b) use ($accountLang)
				{
					if($a['CODE'] == $accountLang)
						return -1;
					else if($b['CODE'] == $accountLang)
						return 1;
					else
						return strcmp($a['NAME'], $b['NAME']);
				});
			}
		}

		return $arResult;
	}

	public static function GetPhoneCountryStates($country, $category = self::PHONE_TYPE_FIXED)
	{
		$arResult = Array();
		if (!in_array($category, self::GetCategories()))
			return $arResult;

		$viHttp = new CVoxImplantHttp();
		$result = $viHttp->GetPhoneNumberCountryStates($category, $country);
		if ($result && !empty($result->result))
		{
			foreach ($result->result as $value)
			{
				$arResult[$value->country_state] = [
					'NAME' => $value->country_state_name
				];
			}
		}

		return $arResult;
	}

	public static function GetPhoneRegions($country, $category, $countryState = '', $bundleSize = 0)
	{
		$arResult = Array();
		if (!in_array($category, self::GetCategories()))
			return $arResult;

		$viHttp = new CVoxImplantHttp();
		$result = $viHttp->GetPhoneNumberRegions($category, $country, $countryState);

		if ($result && !empty($result->result))
		{
			foreach ($result->result as $value)
			{
				$regionName = '';
				if ($country == 'RU' || $country == 'KZ')
				{
					$regionName = GetMessage('VI_PHONE_CODE_'.$country.'_'.$value->phone_region_code);
					if ($regionName != '')
						$regionName = $regionName.' ('.$value->phone_region_code.')';
				}

				if ($regionName == '')
				{
					$regionName = $value->phone_region_name != $value->phone_region_code? $value->phone_region_name.' ('.$value->phone_region_code.')': $value->phone_region_name;
				}

				if($bundleSize > 0)
				{
					if(is_array($value->multiple_numbers_price))
					{
						$bundle = null;
						foreach ($value->multiple_numbers_price as $multiplePriceFields)
						{
							if($multiplePriceFields->count == $bundleSize)
							{
								$bundle = $multiplePriceFields;
								break;
							}
						}

						if($bundle)
						{
							$monthPrice = $bundle->price;
							$installationPrice = $bundle->installation_price;
							$phoneCount = $value->phone_count;
						}
						else
						{
							$monthPrice = 0;
							$installationPrice = 0;
							$phoneCount = 0;
						}
					}
				}
				else
				{
					$monthPrice = $value->phone_price;
					$installationPrice = $value->phone_installation_price;
					$phoneCount = $value->phone_count;
				}

				$arResult[$value->phone_region_id] = [
					'REGION_ID' => $value->phone_region_id,
					'REGION_NAME' => $regionName,
					'REGION_CODE' => $value->phone_region_code,
					'PHONE_COUNT' => $phoneCount,
					'MONTH_PRICE' => $monthPrice,
					'INSTALLATION_PRICE' => $installationPrice,
					'REQUIRED_VERIFICATION' => $value->required_verification ?: '',
					'IS_NEED_REGULATION_ADDRESS' => $value->is_need_regulation_address ?: false,
					'REGULATION_ADDRESS_TYPE' => $value->regulation_address_type ?: '',
				];
			}
		}

		return $arResult;
	}

	/**
	 * @param array $parameters Parameters to pass to CVoxImplantHttp::GetPhoneNumbers method.
	 * @return array|false
	 * @see CVoxImplantHttp::GetPhoneNumbers
	 * @throws \Bitrix\Main\ObjectException
	 */
	public static function GetRentNumbers(array $parameters = [])
	{
		$viHttp = new CVoxImplantHttp();
		$apiResponse = $viHttp->GetPhoneNumbers($parameters);
		if(!$apiResponse)
		{
			return false;
		}

		return static::PrepareNumberFields($apiResponse);
	}

	public static function PrepareNumberFields($apiResponse)
	{
		$arResult = [];
		if ($apiResponse && !empty($apiResponse->result))
		{
			foreach ($apiResponse->result as $value)
			{
				$renewalDate = $renewalDateTs = '';
				if ($value->phone_next_renewal)
				{
					$data = new Bitrix\Main\Type\DateTime($value->phone_next_renewal.' 00:00:00', 'Y-m-d H:i:s');
					$renewalDate = $data->format(Bitrix\Main\Type\Date::getFormat());
					$renewalDateTs = $data->getTimestamp();
				}

				$unverifiedHoldDate = $unverifiedHoldDateTs = '';
				if ($value->verification_status != 'VERIFIED' && $value->unverified_hold_until)
				{
					$data = new Bitrix\Main\Type\DateTime($value->unverified_hold_until.' 00:00:00', 'Y-m-d H:i:s');
					$unverifiedHoldDate = $data->format(Bitrix\Main\Type\Date::getFormat());
					$unverifiedHoldDateTs = $data->getTimestamp();
				}

				$arResult[$value->phone_number] = Array(
					'ACTIVE' => $value->deactivated? 'N': 'Y',
					'NUMBER' => $value->phone_number,
					'FORMATTED_NUMBER' => \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($value->phone_number)->format(),
					'PAID_BEFORE' => $renewalDate,
					'PAID_BEFORE_TS' => $renewalDateTs,
					'PRICE' => $value->phone_price,
					'COUNTRY_CODE' => $value->phone_country_code,
					'SUBSCRIPTION_ID' => $value->subscription_id,
					'VERIFICATION_STATUS' => $value->verification_status,
					'VERIFICATION_STATUS_NAME' => CVoxImplantDocuments::GetStatusName($value->verification_status),
					'VERIFY_BEFORE' => $unverifiedHoldDate,
					'VERIFY_BEFORE_TS' => $unverifiedHoldDateTs,
					'TO_DELETE' => $value->to_delete ? 'Y' : 'N',
					'DATE_DELETE' => $value->delete_date != '' ? new \Bitrix\Main\Type\Date($value->delete_date, DATE_ATOM) : null,
				);
			}
		}

		return $arResult;
	}

	public static function PrepareCallerIdFields($apiResponse)
	{
		$arResult = [];
		if ($apiResponse && !empty($apiResponse->result))
		{
			foreach ($apiResponse->result as $value)
			{
				$arResult[$value->callerid_number] = [
					'NUMBER' => $value->callerid_number,
					'FORMATTED_NUMBER' => \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($value->callerid_number)->format(),
					'VERIFIED' => $value->verified,
					'VERIFIED_UNTIL' => \Bitrix\Main\Type\Date::createFromTimestamp($value->verified_until_ts),
					'VERIFIED_UNTIL_TS' => $value->verified_until_ts,
				];
			}
		}

		return $arResult;
	}

	public static function GetPhoneNumbers($country, $regionId, $category, $offset = 0, $count = 20)
	{
		$arResult = Array();
		if (!in_array($category, self::GetCategories()))
			return $arResult;

		$arResult = Array();

		$viAccount = new CVoxImplantAccount();
		$currency = $viAccount->GetAccountCurrency();

		$viHttp = new CVoxImplantHttp();
		$result = $viHttp->GetNewPhoneNumbers($category, $country, $regionId, $offset, $count);

		if ($result && !empty($result->result))
		{
			foreach ($result->result as $value)
			{
				$parsedNumber = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($value->phone_number, $country);
				$arResult[$value->phone_number] = Array(
					'FULL_PRICE' => floatval($value->phone_price)+floatval($value->can_list_phone_numbers),
					'INSTALLATION_PRICE' => $value->phone_installation_price,
					'MONTH_PRICE' => $value->phone_price,
					'PHONE_NUMBER' => $value->phone_number,
					'PHONE_NUMBER_INTERNATIONAL' => $parsedNumber->format(\Bitrix\Main\PhoneNumber\Format::INTERNATIONAL),
					'PHONE_NUMBER_LOCAL' => $parsedNumber->format(\Bitrix\Main\PhoneNumber\Format::NATIONAL),
					'COUNTRY_CODE' => $country,
					'REGION_ID' => $regionId,
					'CURRENCY' => $currency
				);
			}
		}

		return $arResult;
	}

	/**
	 * @param string $name Name of the rented number or number group.
	 * @param array $params Array of parameters to pass to \CVoxImplantHttp::AttachPhoneNumber.
	 * @see \CVoxImplantHttp::AttachPhoneNumber
	 * @return \Bitrix\Voximplant\Result
	 * @throws Exception
	 */
	public static function AttachPhoneNumber($name, $params)
	{
		$result = new \Bitrix\Voximplant\Result();

		$arPhones = Array();
		$viHttp = new CVoxImplantHttp();
		$apiResult = $viHttp->AttachPhoneNumber($params);
		if ($apiResult->result && !empty($apiResult->phone_numbers))
		{
			foreach ($apiResult->phone_numbers as $number)
			{
				$arPhones[$number->phone_number]['PHONE_NUMBER'] = '+'.$number->phone_number;
				$arPhones[$number->phone_number]['PHONE_NUMBER_FORMATTED'] = static::formatInternational($number->phone_number);
				$arPhones[$number->phone_number]['SUBSCRIPTION_ID'] = $number->subscription_id;
				$arPhones[$number->phone_number]['COUNTRY_CODE'] = $number->phone_country_code;
				$arPhones[$number->phone_number]['VERIFICATION_REGION'] = isset($number->required_verification)? $number->required_verification: '';
				$arPhones[$number->phone_number]['VERIFICATION_STATUS'] = isset($number->verification_status)? $number->verification_status: 'VERIFIED';
			}
			CVoxImplantHistory::WriteToLog($arPhones, 'ATTACHED PHONES');
		}
		else
		{
			CVoxImplantHistory::WriteToLog($viHttp->GetError(), 'ERROR WHILE ATTACH');
			$errorCode = $viHttp->GetError()->code;
			$errorMessage = Loc::getMessage("VI_PHONE_ATTACH_ERROR_" . $errorCode) ?: $viHttp->GetError()->msg;

			$result->addError(new \Bitrix\Main\Error($errorMessage, $errorCode));
			return $result;
		}

		$configId = static::createConfig($name, $arPhones);
		static::savePhoneNumbers($configId, $arPhones);

		CVoxImplantUser::clearCache();
		CVoxImplantConfig::SetModeStatus(CVoxImplantConfig::MODE_RENT, true);

		$result->setData([
			'configId' => $configId,
			'numbers' => $arPhones,
		]);

		return $result;
	}

	/**
	 * Deletes local records for the numbers.
	 * Does not try to deactivate number subscription.
	 * @param array $numbers Array of numbers to delete.
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function deleteLocal(array $numbers)
	{
		$cursor = VI\Model\NumberTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=NUMBER' => $numbers
			]
		]);

		while($row = $cursor->fetch())
		{
			VI\Model\NumberTable::delete($row['ID']);
		}
	}

	public static function DeletePhoneConfig($configId)
	{
		$configId = intval($configId);
		$result = VI\ConfigTable::getList(Array(
			'select'=>Array('ID', 'SEARCH_ID'),
			'filter'=>Array(
				'=ID' => $configId
			)
		));
		$config = $result->fetch();
		if (!$config)
			return false;

		VI\ConfigTable::delete($configId);

		$needChangePortalNumber = false;
		if ($config['SEARCH_ID'] == CVoxImplantConfig::GetPortalNumber())
		{
			$needChangePortalNumber = true;
		}

		$firstPhoneNumber = '';
		$result = VI\ConfigTable::getList(Array(
			'select'=>Array('ID', 'SEARCH_ID'),
		));
		while ($row = $result->fetch())
		{
			if (!$firstPhoneNumber)
			{
				$firstPhoneNumber = $row['SEARCH_ID'];
				break;
			}
		}

		if (!$firstPhoneNumber)
		{
			CVoxImplantConfig::SetModeStatus(CVoxImplantConfig::MODE_RENT, false);
		}

		if ($needChangePortalNumber)
		{
			if ($firstPhoneNumber)
			{
				CVoxImplantConfig::SetPortalNumber($firstPhoneNumber);
			}
			else
			{
				CVoxImplantConfig::SetPortalNumber(CVoxImplantConfig::LINK_BASE_NUMBER);
			}
		}

		return true;
	}

	public static function hasRentedNumber()
	{
		$row = \Bitrix\Voximplant\Model\NumberTable::getRow([
			'select' => ['ID'],
			'limit' => 1
		]);

		return $row != false;
	}

	public static function hasRentedNumberPacket($packetSize)
	{
		$row = VI\Model\NumberTable::getRow([
			'select' => ['SUBSCRIPTION_ID', 'CNT'],
			'group' => ['SUBSCRIPTION_ID'],
			'filter' => [
				'=CNT' => $packetSize
			]
		]);

		return (bool)$row;
	}

	public static function GetRentedNumbersCount()
	{
		$result = VI\Model\NumberTable::getList(array(
			'select' => ['CNT'],
			'cache' => ['ttl' => 31536000]
		))->fetch();

		return $result['CNT'];
	}

	public static function getRentedNumbersHash()
	{
		$result = VI\Model\NumberTable::getList(array(
			'select' => ['NUMBER'],
			'cache' => ['ttl' => 31536000]
		))->fetchAll();

		$numbers = array_map(function($n){return $n['NUMBER'];}, $result);
		sort($numbers, SORT_STRING);

		return md5(join("", $numbers));
	}

	/**
	 * @param $configId
	 * @return bool
	 * @deprecated
	 */
	public static function CheckDeleteAgent($configId)
	{
		return false;
	}

	/**
	 * Deletes disconnected numbers and their configurations.
	 * @agent
	 */
	public static function DeleteDisconnectedNumbers()
	{
		$cursor = VI\Model\NumberTable::getList([
			'filter' => [
				'=TO_DELETE' => 'Y',
				'<DATE_DELETE' => new \Bitrix\Main\Type\DateTime()
			]
		]);

		while ($row = $cursor->fetch())
		{

		}

		return 'CVoxImplantPhone::DeleteDisconnectedNumbers();';
	}

	public static function getCountryName($countryCode)
	{
		return Loc::getMessage('VI_PHONE_CODE_'.$countryCode);
	}

	/**
	 * Creates config for new phone numbers
	 *
	 * @param $configName
	 * @param $arPhones
	 * @return array|int
	 * @throws Exception
	 */
	public static function createConfig($configName, $arPhones)
	{
		$melodyLang = ToUpper(LANGUAGE_ID);
		if($melodyLang === 'KZ')
		{
			$melodyLang = 'RU';
		}
		else if(!in_array($melodyLang, CVoxImplantConfig::GetMelodyLanguages()))
		{
			$melodyLang = 'EN';
		}

		if($configName == '')
		{
			$configName = static::generateConfigName($arPhones, VI\ConfigTable::MAX_LENGTH_NAME);
		}

		// assuming, that all numbers are from the same country
		$firstPhone = array_keys($arPhones)[0];
		$countryCode = $arPhones[$firstPhone]['COUNTRY_CODE'];

		// one config for all numbers
		$configFields = [
			'PHONE_NAME' => $configName,
			'MELODY_LANG' => $melodyLang,
			'PORTAL_MODE' => count($arPhones) > 1 ? CVoxImplantConfig::MODE_GROUP : CVoxImplantConfig::MODE_RENT,
			'QUEUE_ID' => CVoxImplantMain::getDefaultGroupId(),
			'REDIRECT_WITH_CLIENT_NUMBER' => ($countryCode == 'RU') ? 'Y' : 'N'
		];

		$insertResult = VI\ConfigTable::add($configFields);
		$configId = $insertResult->getId();

		return $configId;
	}

	public static function savePhoneNumbers($configId, $arPhones)
	{
		foreach ($arPhones as $phone => $phoneObj)
		{
			$insertResult = VI\Model\NumberTable::add([
				'NUMBER' => $phone,
				'NAME' => $phone,
				'VERIFIED' => $phoneObj['VERIFICATION_STATUS'] === 'VERIFIED'? 'Y': 'N',
				'COUNTRY_CODE' => $phoneObj['COUNTRY_CODE'],
				'CONFIG_ID' => $configId,
				'SUBSCRIPTION_ID' => $phoneObj['SUBSCRIPTION_ID'],
			]);
		}
	}

	public static function generateConfigName($rentedPhones, $maxLength = 0)
	{
		if(count($rentedPhones) === 1)
		{
			$firstPhone = array_keys($rentedPhones)[0];
			return $rentedPhones[$firstPhone]['PHONE_NUMBER'];
		}
		else
		{
			// checking if it is a single subscription or not
			$subscriptions = [];

			foreach ($rentedPhones as $phone => $fields)
			{
				$subscriptions[$fields['SUBSCRIPTION_ID']] = true;
			}

			$isSingleSubscription = count($subscriptions) === 1;

			if($isSingleSubscription)
			{
				return Loc::getMessage("VI_PHONE_PACKAGE", ["#COUNT#" => count($rentedPhones)]);
			}
			else
			{
				$result = Loc::getMessage("VI_PHONE_GROUP") . ": " . join(", ", array_keys($rentedPhones));
				if ($maxLength > 0 && mb_strlen($result) > $maxLength)
				{
					$result = mb_substr($result, 0, $maxLength - 3) . "...";
				}
				return $result;
			}
		}
	}

	/**
	 * @param $parameters
	 * <li> array numbers
	 * <li> bool create
	 * <li> bool delete
	 *
	 * @return bool
	 */
	public static function syncWithController($parameters)
	{
		if(isset($parameters['numbers']))
		{
			$numbers = $parameters['numbers'];
		}
		else
		{
			$numbers = static::GetRentNumbers();
		}

		if(!is_array($numbers))
		{
			return false;
		}

		$allowDelete = $parameters['delete'] === true;
		$allowCreate = $parameters['create'] === true;

		if(!$allowCreate && !$allowDelete)
		{
			// nothing to do
			return false;
		}

		$remoteNumbers = array_keys($numbers);

		$localNumbers = [];
		$cursor = VI\Model\NumberTable::getList();
		while($row = $cursor->fetch())
		{
			$localNumbers[] = $row['NUMBER'];
		}

		if($allowCreate)
		{
			$numbersToCreate = array_diff($remoteNumbers, $localNumbers);
			$numbersBySubscription = [];

			foreach ($numbersToCreate as $newNumber)
			{
				$numberRecord = $numbers[$newNumber];
				$subscriptionId = $numberRecord['SUBSCRIPTION_ID'];

				if(!isset($numbersBySubscription[$subscriptionId]))
				{
					$numbersBySubscription[$subscriptionId] = [];
				}

				$numbersBySubscription[$subscriptionId][$numberRecord['NUMBER']] = $numberRecord;
			}

			foreach ($numbersBySubscription as $subscriptionId => $newNumbers)
			{
				$configId = static::createConfig('', $newNumbers);
				static::savePhoneNumbers($configId, $newNumbers);
			}
		}

		if($allowDelete)
		{
			$numbersToDelete = array_diff($localNumbers, $remoteNumbers);
			if(!empty($numbersToDelete))
			{
				static::deleteLocal($numbersToDelete);

				CVoxImplantConfig::deleteOrphanConfigurations();
			}
		}

		return true;
	}

	public static function addCallerId($phoneNumber, $isVerified, \Bitrix\Main\Type\DateTime $verifiedUntil = null)
	{
		$addResult = VI\ConfigTable::add([
			'PORTAL_MODE' => CVoxImplantConfig::MODE_LINK
		]);

		$configId = $addResult->getId();

		VI\Model\CallerIdTable::add([
			'NUMBER' => $phoneNumber,
			'VERIFIED' => $isVerified ? 'Y' : 'N',
			'VERIFIED_UNTIL' => $verifiedUntil,
			'CONFIG_ID' => $configId
		]);
	}

	public static function syncCallerIds(array $parameters = [])
	{
		if(isset($parameters['callerIds']))
		{
			$callerIds = $parameters['callerIds'];
		}
		else
		{
			return false;
		}

		$knownCallerIds = [];
		foreach ($callerIds as $callerId)
		{
			$knownCallerIds[$callerId['NUMBER']] = true;
			VI\Model\CallerIdTable::merge([
				'NUMBER' => $callerId['NUMBER'],
				'VERIFIED' => $callerId['VERIFIED'] ? 'Y' : 'N',
				'VERIFIED_UNTIL' => $callerId['VERIFIED_UNTIL']
			]);
		}

		$cursor = VI\Model\CallerIdTable::getList([
			'select' => ['ID', 'NUMBER', 'CONFIG_ID'],
			'cache' => ['ttl' => 31536000]
		]);

		while ($row = $cursor->fetch())
		{
			if(isset($knownCallerIds[$row['NUMBER']]))
			{
				if(!$row['CONFIG_ID'])
				{
					$addResult = VI\ConfigTable::add([
						'PORTAL_MODE' => CVoxImplantConfig::MODE_LINK
					]);

					$configId = $addResult->getId();
					VI\Model\CallerIdTable::update($row['ID'], [
						'CONFIG_ID' => $configId
					]);
				}
			}
			else
			{
				if($row['CONFIG_ID'])
				{
					VI\ConfigTable::delete($row['CONFIG_ID']);
				}
				VI\Model\CallerIdTable::delete($row['ID']);
			}
		}
	}

	public static function getNumberDescription($numberFields)
	{
		$price = $numberFields["PRICE"];

		if($numberFields["TO_DELETE"] == "Y")
		{
			return Loc::getMessage("VI_PHONE_DESCRIPTION_RENT_TO_DELETE_DESCRIPTION");
		}
		else
		{
			return Loc::getMessage("VI_PHONE_DESCRIPTION_RENT_DESCRIPTION", [
				"#PRICE#" => CVoxImplantMain::formatMoney($price)
			]);
		}
	}

	public static function getNumberStatus($numberFields)
	{
		$paidUntil = $numberFields["PAID_BEFORE"];

		if($numberFields["TO_DELETE"] == "Y")
		{
			return Loc::getMessage("VI_PHONE_DESCRIPTION_RENT_TO_DELETE_STATUS", [
				"#DISCONNECT_DATE#" => $numberFields["DATE_DELETE"] ? $numberFields["DATE_DELETE"]->toString() : ""
			]);
		}
		else
		{
			return Loc::getMessage("VI_PHONE_DESCRIPTION_RENT_STATUS", [
				"#PAID_UNTIL#" => $paidUntil,
			]);
		}
	}

	public static function getCallerIdDescription($callerIdFields)
	{
		if($callerIdFields["VERIFIED"])
		{
			return Loc::getMessage("VI_PHONE_DESCRIPTION_LINK", [
				"#VERIFIED_UNTIL#" => $callerIdFields["VERIFIED_UNTIL"]->toString()
			]);
		}
		else
		{
			return Loc::getMessage("VI_PHONE_DESCRIPTION_LINK_UNVERIFIED");
		}
	}

	public static function formatInternational($number)
	{
		return \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($number)->format(\Bitrix\Main\PhoneNumber\Format::INTERNATIONAL);
	}
}
?>
