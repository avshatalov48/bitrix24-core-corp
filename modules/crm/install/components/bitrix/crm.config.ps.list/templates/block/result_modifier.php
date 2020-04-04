<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$paySystemList = array();

if (array_key_exists('PAY_SYSTEMS', $arResult))
{
	foreach ($arResult['PAY_SYSTEMS'] as $id => $data)
	{
		if (preg_match('/bill(\w+)*$/i'.BX_UTF_PCRE_MODIFIER, $data['HANDLER']) ||
			preg_match('/quote(_\w+)*$/i'.BX_UTF_PCRE_MODIFIER, $data['HANDLER'])
		)
		{
			continue;
		}

		$personTypeIdList = \Bitrix\Sale\PaySystem\Manager::getPersonTypeIdList($id);
		$personTypeId = current($personTypeIdList);

		$paySystemList[$personTypeId][$data['ACTIVE']][$id] = $data;
	}

	$arResult['PAY_SYSTEM'] = $paySystemList;
}

