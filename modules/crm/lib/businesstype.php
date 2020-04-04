<?php
namespace Bitrix\Crm;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class BusinessTypeTable extends Entity\DataManager
{
	protected static $allLangIDs = null;

	public static function getTableName()
	{
		return 'b_crm_biz_type';
	}

	public static function getMap()
	{
		return array(
			'CODE' => array(
				'data_type' => 'string',
				'primary' => true,
				'required' => true
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true
			),
			'LANG' => array(
				'data_type' => 'string',
				'required' => false
			)
		);
	}

	protected static function getAllLangIDs()
	{
		if(self::$allLangIDs !== null)
		{
			return self::$allLangIDs;
		}

		self::$allLangIDs = array();
		$sort = 'sort';
		$order = 'asc';
		$langEntity = new \CLanguage();
		$dbLangs = $langEntity->GetList($sort, $order);
		while($lang = $dbLangs->Fetch())
		{
			if(isset($lang['LID']))
			{
				self::$allLangIDs[] = $lang['LID'];
			}
		}
		return self::$allLangIDs;
	}

	public static function installDefault()
	{
		$langIDs = self::getAllLangIDs();
		foreach($langIDs as $langID)
		{
			IncludeModuleLangFile(__FILE__, $langID);
			$bizTypeStr = trim(GetMessage('CRM_BIZ_TYPE_DEFAULT'));
			if($bizTypeStr === '' || $bizTypeStr === '-')
			{
				//Skip stub
				continue;
			}

			foreach(explode('|', $bizTypeStr) as $slug)
			{
				$ary = explode(';', $slug);
				if(count($ary) < 2)
				{
					continue;
				}

				if(is_array(self::getByPrimary($ary[0])->fetch()))
				{
					//Already exists
					continue;
				}

				$fields = array(
					'CODE' => $ary[0],
					'NAME' => $ary[1]
				);

				if(isset($ary[2]))
				{
					$fields['LANG'] = $ary[2];
				}
				self::add($fields);
			}
		}
	}
}