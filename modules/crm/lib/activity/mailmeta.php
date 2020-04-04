<?php

namespace Bitrix\Crm\Activity;

use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

//Loc::loadMessages(__FILE__);

class MailMetaTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_act_mail_meta';
	}

	public static function add(array $data)
	{
		try
		{
			parent::add($data);
		}
		catch (DB\SqlQueryException $e)
		{
			if ($data['ACTIVITY_ID'] > 0)
			{
				$primary = $data['ACTIVITY_ID'];
				unset($data['ACTIVITY_ID']);

				static::update($primary, $data);
			}
			else
			{
				throw $e;
			}
		}
	}

	public static function getMap()
	{
		return array(
			'ACTIVITY_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'MSG_ID_HASH' => array(
				'data_type' => 'string',
			),
			'MSG_INREPLY_HASH' => array(
				'data_type' => 'string',
			),
			'MSG_HEADER_HASH' => array(
				'data_type' => 'string',
			),
			'ACTIVITY' => array(
				'data_type' => 'Bitrix\Crm\Activity',
				'reference' => array('=this.ACTIVITY_ID' => 'ref.ID'),
			),
		);
	}
}
