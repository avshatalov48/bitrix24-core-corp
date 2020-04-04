<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Voximplant as VI;

class CVoxImplantEvent
{
	public function OnBeforeUserAdd(&$arFields)
	{
		global $APPLICATION;
		$error = false;
		if(is_set($arFields, "WORK_PHONE"))
		{
			if (strlen($arFields["WORK_PHONE"])>0)
			{
				$arCorrectPhones["WORK_PHONE"] = CVoxImplantPhone::Normalize($arFields["WORK_PHONE"]);
				if (!$arCorrectPhones["WORK_PHONE"])
				{
					$APPLICATION->throwException(GetMessage('ERROR_WORK_PHONE').' '.GetMessage('ERROR_NUMBER'));
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
			if (strlen($arFields["PERSONAL_PHONE"])>0)
			{
				$arCorrectPhones["PERSONAL_PHONE"] = CVoxImplantPhone::Normalize($arFields["PERSONAL_PHONE"]);
				if (!$arCorrectPhones["PERSONAL_PHONE"])
				{
					$APPLICATION->throwException(GetMessage('ERROR_PERSONAL_PHONE').' '.GetMessage('ERROR_NUMBER'));
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
			if (strlen($arFields["PERSONAL_MOBILE"])>0)
			{
				$arCorrectPhones["PERSONAL_MOBILE"] = CVoxImplantPhone::Normalize($arFields["PERSONAL_MOBILE"]);
				if (!$arCorrectPhones["PERSONAL_MOBILE"])
				{
					$APPLICATION->throwException(GetMessage('ERROR_PERSONAL_MOBILE').' '.GetMessage('ERROR_NUMBER'));
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
			if (strlen($arFields["UF_PHONE_INNER"])>0)
			{
				$phoneInner = intval(preg_replace("/[^0-9]/i", "", $arFields["UF_PHONE_INNER"]));
				if ($phoneInner > 0 && $phoneInner < 10000)
				{
					$result = \Bitrix\Main\UserTable::getList(array(
						'select' => array('COUNT'),
						'filter' => array(
							'!=ID' => intval($arFields['ID']),
							'=UF_PHONE_INNER' => $phoneInner,
							'=ACTIVE' => 'Y'
						),
						'runtime' => array(
							'COUNT' => array(
								'data_type' => 'integer',
								'expression' => array('COUNT(1)')
							)
						)
					));
					$data = $result->fetch();
					CVoxImplantHistory::WriteToLog($data);
					if ($data['COUNT'] > 0)
					{
						$APPLICATION->throwException(GetMessage('ERROR_PHONE_INNER'));
						$error = true;
					}
					else
					{
						$arFields["UF_PHONE_INNER"] = $phoneInner;
					}

				}
				else
				{
					$APPLICATION->throwException(GetMessage('ERROR_PHONE_INNER_2'));
					$error = true;
				}
			}
			$arCorrectPhones["UF_PHONE_INNER"] = '';
		}

		if ($error)
			return false;
	}

	public function OnBeforeUserUpdate(&$arFields)
	{
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
				if (strlen($phone['PHONE_MNEMONIC']) > 0)
				{
					$arPhones[$phone['PHONE_MNEMONIC']] = $phone;
				}
			}
			global $APPLICATION;
			$error = false;
			if(is_set($arFields, "WORK_PHONE"))
			{
				if (strlen($arFields["WORK_PHONE"])>0)
				{
					$arCorrectPhones["WORK_PHONE"] = CVoxImplantPhone::Normalize($arFields["WORK_PHONE"]);
					if (!$arCorrectPhones["WORK_PHONE"])
					{
						$APPLICATION->throwException(GetMessage('ERROR_WORK_PHONE').' '.GetMessage('ERROR_NUMBER'));
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
				if (strlen($arFields["PERSONAL_PHONE"])>0)
				{
					$arCorrectPhones["PERSONAL_PHONE"] = CVoxImplantPhone::Normalize($arFields["PERSONAL_PHONE"]);
					if (!$arCorrectPhones["PERSONAL_PHONE"])
					{
						$APPLICATION->throwException(GetMessage('ERROR_PERSONAL_PHONE').' '.GetMessage('ERROR_NUMBER'));
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
				if (strlen($arFields["PERSONAL_MOBILE"])>0)
				{
					$arCorrectPhones["PERSONAL_MOBILE"] = CVoxImplantPhone::Normalize($arFields["PERSONAL_MOBILE"]);
					if (!$arCorrectPhones["PERSONAL_MOBILE"])
					{
						$APPLICATION->throwException(GetMessage('ERROR_PERSONAL_MOBILE').' '.GetMessage('ERROR_NUMBER'));
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
				if (strlen($arFields["UF_PHONE_INNER"])>0)
				{
					$phoneInner = intval(preg_replace("/[^0-9]/i", "", $arFields["UF_PHONE_INNER"]));
					if ($phoneInner > 0 && $phoneInner < 10000)
					{
						$result = \Bitrix\Main\UserTable::getList(array(
							'select' => array('COUNT'),
							'filter' => array(
								'!=ID' => intval($arFields['ID']),
								'=UF_PHONE_INNER' => $phoneInner,
								'=ACTIVE' => 'Y'
							),
							'runtime' => array(
								'COUNT' => array(
									'data_type' => 'integer',
									'expression' => array('COUNT(1)')
								)
							)
						));
						$data = $result->fetch();
						if ($data['COUNT'] > 0)
						{
							$APPLICATION->throwException(GetMessage('ERROR_PHONE_INNER'));
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
						$APPLICATION->throwException(GetMessage('ERROR_PHONE_INNER_2'));
						$error = true;
					}
				}
				else
				{
					$arCorrectPhones["UF_PHONE_INNER"] = '';
				}
			}

			if ($arFields["ACTIVE"] == 'N' && CVoximplantUser::GetPhoneActive($arFields['ID']))
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
							if (strlen($phone) == 0)
							{
								VI\PhoneTable::delete($arPhones[$mnemonic]['ID']);
							}
							else
							{
								VI\PhoneTable::update($arPhones[$mnemonic]['ID'], Array('PHONE_NUMBER' => $phone));
							}
						}
					}
					else if (strlen($phone) > 0)
					{
						VI\PhoneTable::add(Array('USER_ID' => intval($arFields['ID']), 'PHONE_NUMBER' => $phone, 'PHONE_MNEMONIC' => $mnemonic));
					}
				}
			}
			else
			{
				return false;
			}
		}
	}

	public function OnAfterUserUpdate(&$fields)
	{
		if ($fields['RESULT'] && isset($fields['ACTIVE']))
		{
			if ($fields['ACTIVE'] == 'N')
			{
				$userId = (int)$fields['ID'];
				if($userId > 0)
					VI\Model\QueueUserTable::deleteByUserId($userId);
			}
		}
	}

	public function OnUserDelete($ID)
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
		return array(
			"voximplant" => array(
				"notifications" => array(
					"NAME" => GetMessage('VI_EVENTS_NOTIFICATIONS'),
					"SITE" => "Y",
					"MAIL" => "Y",
					"XMPP" => "N",
					"PUSH" => "N",
					"DISABLED" => array( ),
				),
			),
		);
	}
}
?>
