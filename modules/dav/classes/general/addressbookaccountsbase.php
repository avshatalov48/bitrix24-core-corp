<?php

/**
 * Class CDavAddressbookAccountsBase
 */
abstract class CDavAddressbookAccountsBase
	extends CDavAddressbookBaseLimited
{

	/**
	 * Timestamp of account last modification
	 * @param array $account
	 * @return \Bitrix\Main\Type\DateTim
	 */
	protected function EntityLastModifiedAt($account)
	{
		return $account['TIMESTAMP_X'];
	}

	/**
	 * Map: key=>value vCard properties of account
	 * @param $account
	 * @return array
	 */
	protected function GetVCardDataMap($account)
	{
		$map = array(
			"N" => $account["LAST_NAME"] . ";" . $account["NAME"] . ";" . $account["SECOND_NAME"] . ";;",
			"FN" => $account["NAME"] . ($account["SECOND_NAME"] ? " " . $account["SECOND_NAME"] : "") . " " . $account["LAST_NAME"],
			"EMAIL" => array(
				"VALUE" => !empty($account["EMAIL"]) ? $account["EMAIL"] : 'test@test.ru',
				"PARAMETERS" => array("TYPE" => "INTERNET")
			),
			"REV" => date("Ymd\\THis\\Z", MakeTimeStamp($account["TIMESTAMP_X"])),
			"UID" => $account["ID"],
		);

		if (intval($account["PERSONAL_BIRTHDAY"]) > 0)
			$map["BDAY"] = date("Y-m-d", MakeTimeStamp($account["PERSONAL_BIRTHDAY"]));

		if (strlen($account["WORK_PHONE"]) > 0)
			$map["TEL"][] = array(
				"VALUE" => $account["WORK_PHONE"],
				"PARAMETERS" => array("TYPE" => "WORK")
			);
		if (strlen($account["PERSONAL_MOBILE"]) > 0)
			$map["TEL"][] = array(
				"VALUE" => $account["PERSONAL_MOBILE"],
				"PARAMETERS" => array("TYPE" => "CELL")
			);
		if (strlen($account["PERSONAL_PHONE"]) > 0)
			$map["TEL"][] = array(
				"VALUE" => $account["PERSONAL_PHONE"],
				"PARAMETERS" => array("TYPE" => "HOME")
			);
		if (!empty($account['UF_PHONE_INNER']))
		{
			$map["TEL"][] = array(
				"VALUE" => $account["UF_PHONE_INNER"],
				"PARAMETERS" => array("TYPE" => "WORK")
			);
		}
		$org = '';
		if (strlen($account["WORK_COMPANY"]) > 0)
		{
			$org .= $account["WORK_COMPANY"];
		}

		if (!empty($account['UF_DEPARTMENT']))
		{
			foreach ($account['UF_DEPARTMENT'] as $department)
			{
				$org .= (!empty($org) ? ';' : '') . $department['NAME'];
			}
		}

		if ($org)
		{
			$map["ORG"] = $org;
		}

		if (strlen($account["WORK_POSITION"]) > 0)
			$map["TITLE"] = $account["WORK_POSITION"];

		if (strlen($account["WORK_WWW"]) > 0)
			$map["URL"][] = array(
				"VALUE" => $account["WORK_WWW"],
				"PARAMETERS" => array("TYPE" => "WORK")
			);
		if (strlen($account["PERSONAL_WWW"]) > 0)
			$map["URL"][] = array(
				"VALUE" => $account["PERSONAL_WWW"],
				"PARAMETERS" => array("TYPE" => "HOME")
			);

		if (strlen($account["PERSONAL_STREET"]) > 0)
			$map["ADR"][] = array(
				"VALUE" => ";;" . $account["PERSONAL_STREET"] . ";" . $account["PERSONAL_CITY"] . ";" . $account["PERSONAL_STATE"] . ";" . $account["PERSONAL_ZIP"] . ";" . GetCountryByID($account["PERSONAL_COUNTRY"]) . "",
				"PARAMETERS" => array("TYPE" => "HOME")
			);
		if (strlen($account["WORK_STREET"]) > 0)
			$map["ADR"][] = array(
				"VALUE" => ";;" . $account["WORK_STREET"] . ";" . $account["WORK_CITY"] . ";" . $account["WORK_STATE"] . ";" . $account["WORK_ZIP"] . ";" . GetCountryByID($account["WORK_COUNTRY"]) . "",
				"PARAMETERS" => array("TYPE" => "WORK")
			);

		$map['IMG'] = !empty($account['PERSONAL_PHOTO']) ? $account['PERSONAL_PHOTO'] : '';

		return $map;
	
	}
}