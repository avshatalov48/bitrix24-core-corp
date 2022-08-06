<?php

IncludeModuleLangFile(__FILE__);

use Bitrix\Voximplant as VI;
use Bitrix\Main\Localization\Loc;

class CVoxImplantEvent
{
	public static function OnBeforeUserAdd(&$arFields)
	{
		global $APPLICATION;
		$error = false;
		if(is_set($arFields, "WORK_PHONE"))
		{
			if ($arFields["WORK_PHONE"] <> '')
			{
				$arCorrectPhones["WORK_PHONE"] = CVoxImplantPhone::Normalize($arFields["WORK_PHONE"]);
				if (!$arCorrectPhones["WORK_PHONE"])
				{
					$APPLICATION->throwException(Loc::getMessage('ERROR_WORK_PHONE').' '.Loc::getMessage('ERROR_NUMBER'));
					$error = true;
				}
			}
			else
			{
				$arCorrectPhones["WORK_PHONE"] = '';
			}
		}
		if(is_set($arFields, "PERSONAL_PHONE"))
		{
			if ($arFields["PERSONAL_PHONE"] <> '')
			{
				$arCorrectPhones["PERSONAL_PHONE"] = CVoxImplantPhone::Normalize($arFields["PERSONAL_PHONE"]);
				if (!$arCorrectPhones["PERSONAL_PHONE"])
				{
					$APPLICATION->throwException(Loc::getMessage('ERROR_PERSONAL_PHONE').' '.Loc::getMessage('ERROR_NUMBER'));
					$error = true;
				}
			}
			else
			{
				$arCorrectPhones["PERSONAL_PHONE"] = '';
			}
		}
		if(is_set($arFields, "PERSONAL_MOBILE"))
		{
			if ($arFields["PERSONAL_MOBILE"] <> '')
			{
				$arCorrectPhones["PERSONAL_MOBILE"] = CVoxImplantPhone::Normalize($arFields["PERSONAL_MOBILE"]);
				if (!$arCorrectPhones["PERSONAL_MOBILE"])
				{
					$APPLICATION->throwException(Loc::getMessage('ERROR_PERSONAL_MOBILE').' '.Loc::getMessage('ERROR_NUMBER'));
					$error = true;
				}
			}
			else
			{
				$arCorrectPhones["PERSONAL_MOBILE"] = '';
			}
		}
		
		if(is_set($arFields, "UF_PHONE_INNER"))
		{
			if ($arFields["UF_PHONE_INNER"] <> '')
			{
				$phoneInner = preg_replace("/\D/", "", $arFields["UF_PHONE_INNER"]);
				$phoneLength = mb_strlen($phoneInner);
				if ($phoneLength > 0 && $phoneLength < 5)
				{
					$existingEntity = CVoxImplantIncoming::getByInternalPhoneNumber($phoneInner);
					if ($existingEntity && !($existingEntity['ENTITY_TYPE'] === 'user' && $existingEntity['ENTITY_ID'] == $arFields['ID']))
					{
						$APPLICATION->throwException(Loc::getMessage('ERROR_PHONE_INNER_IN_USAGE'));
						$error = true;
					}
					else
					{
						$arFields["UF_PHONE_INNER"] = $phoneInner;
					}
				}
				else
				{
					$APPLICATION->throwException(Loc::getMessage('ERROR_PHONE_INNER_2'));
					$error = true;
				}
			}
			$arCorrectPhones["UF_PHONE_INNER"] = '';
		}

		if ($error)
			return false;
	}

	public static function OnBeforeUserUpdate(&$arFields)
	{
		$error = false;
		if ($arFields["ID"] > 0)
		{
			$arPhones = Array();
			$arCorrectPhones = Array();
			$dbViPhone = VI\PhoneTable::getList(Array(
				'select' => Array('ID', 'PHONE_MNEMONIC', 'PHONE_NUMBER'),
				'filter' => Array('=USER_ID' => intval($arFields['ID']))
			));
			while ($phone = $dbViPhone->fetch())
			{
				if ($phone['PHONE_MNEMONIC'] <> '')
				{
					$arPhones[$phone['PHONE_MNEMONIC']] = $phone;
				}
			}
			/** @global \CMain $APPLICATION */
			global $APPLICATION;
			if(is_set($arFields, "WORK_PHONE"))
			{
				if ($arFields["WORK_PHONE"] <> '')
				{
					$arCorrectPhones["WORK_PHONE"] = CVoxImplantPhone::Normalize($arFields["WORK_PHONE"]);
					if (!$arCorrectPhones["WORK_PHONE"])
					{
						$APPLICATION->throwException(Loc::getMessage('ERROR_WORK_PHONE').' '.Loc::getMessage('ERROR_NUMBER'));
						$error = true;
					}
				}
				else
				{
					$arCorrectPhones["WORK_PHONE"] = '';
				}
			}
			if(is_set($arFields, "PERSONAL_PHONE"))
			{
				if ($arFields["PERSONAL_PHONE"] <> '')
				{
					$arCorrectPhones["PERSONAL_PHONE"] = CVoxImplantPhone::Normalize($arFields["PERSONAL_PHONE"]);
					if (!$arCorrectPhones["PERSONAL_PHONE"])
					{
						$APPLICATION->throwException(Loc::getMessage('ERROR_PERSONAL_PHONE').' '.Loc::getMessage('ERROR_NUMBER'));
						$error = true;
					}
				}
				else
				{
					$arCorrectPhones["PERSONAL_PHONE"] = '';
				}
			}
			if(is_set($arFields, "PERSONAL_MOBILE"))
			{
				if ($arFields["PERSONAL_MOBILE"] <> '')
				{
					$arCorrectPhones["PERSONAL_MOBILE"] = CVoxImplantPhone::Normalize($arFields["PERSONAL_MOBILE"]);
					if (!$arCorrectPhones["PERSONAL_MOBILE"])
					{
						$APPLICATION->throwException(Loc::getMessage('ERROR_PERSONAL_MOBILE').' '.Loc::getMessage('ERROR_NUMBER'));
						$error = true;
					}
				}
				else
				{
					$arCorrectPhones["PERSONAL_MOBILE"] = '';
				}
			}
			if(is_set($arFields, "UF_PHONE_INNER"))
			{
				if ($arFields["UF_PHONE_INNER"] <> '')
				{
					$phoneInner = preg_replace("/\D/", "", $arFields["UF_PHONE_INNER"]);
					$phoneLength = mb_strlen($phoneInner);
					if ($phoneLength > 0 && $phoneLength < 5)
					{
						$existingEntity = CVoxImplantIncoming::getByInternalPhoneNumber($phoneInner);
						if ($existingEntity && !($existingEntity['ENTITY_TYPE'] === 'user' && $existingEntity['ENTITY_ID'] == $arFields['ID']))
						{
							$APPLICATION->throwException(Loc::getMessage('ERROR_PHONE_INNER_IN_USAGE'));
							$error = true;
						}
						else
						{
							$arFields["UF_PHONE_INNER"] = $phoneInner;
							$arCorrectPhones["UF_PHONE_INNER"] = $phoneInner;
						}
					}
					else
					{
						$APPLICATION->throwException(Loc::getMessage('ERROR_PHONE_INNER_2'));
						$error = true;
					}
				}
				else
				{
					$arCorrectPhones["UF_PHONE_INNER"] = '';
				}
			}

			if ($arFields["ACTIVE"] === 'N' && CVoximplantUser::GetPhoneActive($arFields['ID']))
			{
				$viUser = new CVoximplantUser();
				$viUser->UpdateUserPassword($arFields['ID'], CVoxImplantUser::MODE_PHONE);
				$viUser->SetPhoneActive($arFields['ID'], false);
			}

			if (!$error)
			{
				foreach ($arCorrectPhones as $mnemonic => $phone)
				{
					if (isset($arPhones[$mnemonic]))
					{
						if ($phone != $arPhones[$mnemonic]['PHONE_NUMBER'])
						{
							if ($phone == '')
							{
								$res = VI\PhoneTable::delete($arPhones[$mnemonic]['ID']);
								if (!$res->isSuccess())
								{
									$APPLICATION->throwException(implode('; ', $res->getErrorMessages()));
								}
							}
							else
							{
								$res = VI\PhoneTable::update($arPhones[$mnemonic]['ID'], Array('PHONE_NUMBER' => $phone));
								if (!$res->isSuccess())
								{
									$error = true;
								}
							}
						}
					}
					elseif ($phone <> '')
					{
						$res = VI\PhoneTable::add(Array('USER_ID' => (int)$arFields['ID'], 'PHONE_NUMBER' => $phone, 'PHONE_MNEMONIC' => $mnemonic));
						if (!$res->isSuccess())
						{
							$error = true;
						}
					}
					if ($error)
					{
						$errDesc = '';
						if ($res instanceof \Bitrix\Main\ORM\Data\Result)
						{
							$errDesc .= implode(' ', $res->getErrorMessages());
						}
						else
						{
							switch ($mnemonic)
							{
								case 'PERSONAL_MOBILE':
									$errDesc = Loc::getMessage('ERROR_PERSONAL_MOBILE');
									break;
								case 'PERSONAL_PHONE':
									$errDesc = Loc::getMessage('ERROR_PERSONAL_PHONE');
									break;
								case 'WORK_PHONE':
									$errDesc = Loc::getMessage('ERROR_WORK_PHONE');
									break;
							}
						}
						$errDesc .= ' '.Loc::getMessage('ERROR_NUMBER');
						$APPLICATION->throwException($errDesc);
					}
				}
			}
		}

		return !$error;
	}

	public static function OnAfterUserUpdate(&$fields)
	{
		if ($fields['RESULT'] && isset($fields['ACTIVE']))
		{
			if ($fields['ACTIVE'] === 'N')
			{
				$userId = (int)$fields['ID'];
				if($userId > 0)
					VI\Model\QueueUserTable::deleteByUserId($userId);
			}
		}
	}

	public static function OnUserDelete($ID)
	{
		VI\PhoneTable::deleteByUser($ID);

		global $USER_FIELD_MANAGER;

		if (CVoximplantUser::GetPhoneActive($ID))
		{
			$viUser = new CVoximplantUser();
			$viUser->UpdateUserPassword($ID, CVoxImplantUser::MODE_PHONE);
			$viUser->SetPhoneActive($ID, false);
		}
		$USER_FIELD_MANAGER->Update("USER", $ID, Array('UF_VI_PASSWORD' => '', 'UF_VI_PHONE_PASSWORD' => '', 'UF_VI_PHONE' => 'N'));
		VI\Model\QueueUserTable::deleteByUserId($ID);
	}

	public static function PullOnGetDependentModule()
	{
		return Array(
			'MODULE_ID' => "voximplant",
			'USE' => Array("PUBLIC_SECTION")
		);
	}

	public static function onGetNotifySchema()
	{
		return [
			"voximplant" => [
				"notifications" => [
					"NAME" => Loc::getMessage('VI_EVENTS_NOTIFICATIONS'),
					"SITE" => "Y",
					"MAIL" => "Y",
					"XMPP" => "N",
					"PUSH" => "N",
					"DISABLED" => [],
				],
				"status_notifications" => [
					"NAME" => Loc::getMessage('VI_EVENTS_SIP_STATUS_NOTIFICATIONS'),
					"SITE" => "Y",
					"MAIL" => "Y",
					"XMPP" => "N",
					"PUSH" => "N",
					"DISABLED" => [IM_NOTIFY_FEATURE_PUSH],
				],
			],
		];
	}

}
