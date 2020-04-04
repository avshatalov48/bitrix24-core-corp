<?php
namespace Bitrix\Crm\Agent\Activity;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Pseudoactivity\Entity\WaitTable;
use \Bitrix\Crm\Pseudoactivity\WaitEntry;

class WaitAgent extends AgentBase
{
	public static function doRun()
	{
		$now = time() + \CTimeZone::GetOffset();
		//\CCrmUtils::Trace("WaitAgent: run", ConvertTimeStamp($now, 'FULL'), 1);
		$dbResult = WaitTable::getList(
			array(
				'select' => array('ID', 'END_TIME', 'AUTHOR_ID', 'COMPLETED'),
				'filter' => array(
					'COMPLETED' => 'N',
					'<=END_TIME' => DateTime::createFromTimestamp($now)
				),
				'order' => array('ID' => 'ASC')
			)
		);

		$list = array();
		while($fields = $dbResult->Fetch())
		{
			//\CCrmUtils::Trace("WaitAgent: processing activity", mydump($fields), 1);
			$list[] = $fields;
		}

		foreach($list as $fields)
		{
			WaitEntry::complete(
				(int)$fields['ID'],
				true,
				array('USER_ID' => (int)$fields['AUTHOR_ID'])
			);
		}
		return true;
	}

	public static function isActive()
	{
		$dbResult = \CAgent::GetList(
			array('ID' => 'DESC'),
			array('NAME' => __CLASS__.'::run(%')
		);
		return is_array($dbResult->Fetch());
	}

	public static function activate()
	{
		\CAgent::AddAgent(
			__CLASS__.'::run();',
			'crm',
			'N',
			3600,
			'',
			'Y',
			ConvertTimeStamp(time() + \CTimeZone::GetOffset(), 'FULL')
		);
	}
}
