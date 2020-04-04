<?php
namespace Bitrix\Crm\Search\Entity;
use Bitrix\Main;
use Bitrix\Main\Entity;
class ContactSearchTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_contact_search';
	}
	public static function getMap()
	{
		return array(
			'OWNER_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'required' => true
			),
			'CONTENT' => array('data_type' => 'string'),
			'PHONE' => array('data_type' => 'string'),
			'EMAIL' => array('data_type' => 'string')
		);
	}
	/**
	 * Execute UPSERT operation.
	 * @param array $data Field data.
	 * @return void
	 */
	public static function upsert(array $data)
	{
		$ownerID = isset($data['OWNER_ID']) ? (int)$data['OWNER_ID'] : 0;
		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Must contains OWNER_ID field.', 'data');
		}

		$content = isset($data['CONTENT']) ? $data['CONTENT'] : '';
		$phone = isset($data['PHONE']) ? $data['PHONE'] : '';
		$email = isset($data['EMAIL']) ? $data['EMAIL'] : '';

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_contact_search',
			array('OWNER_ID'),
			array('OWNER_ID' => $ownerID, 'CONTENT' => $content, 'PHONE' => $phone, 'EMAIL' => $email),
			array('CONTENT' => $content, 'PHONE' => $phone, 'EMAIL' => $email)
		);

		foreach($queries as $query)
		{
			$connection->queryExecute($query);
		}
	}
}