<?php
namespace Bitrix\Crm;

class Honorific
{
	public static function getDefaultLanguageID()
	{
		$entity = new \CSite();
		$dbSites = $entity->GetList($by = 'sort', $order = 'asc', array('DEFAULT' => 'Y', 'ACTIVE' => 'Y'));
		$site = $dbSites->Fetch();
		return is_array($site) && isset($site['LANGUAGE_ID']) ? $site['LANGUAGE_ID'] : '';
	}
	protected static function getAll()
	{
		return \CCrmStatus::GetStatusList('HONORIFIC');
	}
	public static function installDefault()
	{
		$items = array();
		$defaultLangID = self::getDefaultLanguageID();
		if($defaultLangID === '')
		{
			return;
		}

		IncludeModuleLangFile(__FILE__, $defaultLangID);
		$s = trim(GetMessage('CRM_HONORIFIC_DEFAULT'));
		if($s === '' || $s === '-')
		{
			return;
		}

		$slugs = explode('|', $s);
		$slugCount = count($slugs);
		for($i = 0; $i < $slugCount; $i++)
		{
			$ary = explode(';', $slugs[$i]);
			$count = count($ary);
			if($count >= 2)
			{
				$name = trim($ary[1]);
				$statusID = trim($ary[0]);
			}
			else
			{
				$name = trim($ary[0]);
				$statusID = '';
			}

			if($statusID === '')
			{
				$statusID = 'HNR_'.strtoupper($defaultLangID).'_'.($i + 1);
			}

			if($name === '' || isset($items[$statusID]))
			{
				continue;
			}

			$items[$statusID] = array(
				'STATUS_ID' => $statusID,
				'NAME' => $name,
				'SORT' => ($i + 1) * 10,
				'SYSTEM' => 'N'
			);
		}

		$statusEntity = new \CCrmStatus('HONORIFIC');
		$presentItems = \CCrmStatus::GetStatusList('HONORIFIC');
		foreach($items as $item)
		{
			if(!isset($presentItems[$item['STATUS_ID']]))
			{
				$statusEntity->Add($item);
			}
		}
	}
}