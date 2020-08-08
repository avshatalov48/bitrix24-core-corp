<?php
namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Main\Entity\DataManager;

/**
 * Class CounterTable
 *
 * @package Bitrix\Tasks\Internals
 */
class CounterTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_tasks_counters';
	}

	public static function getClass(): string
	{
		return static::class;
	}

	public static function getMap(): array
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'USER_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'GROUP_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],

			'OPENED' => [
				'data_type' => 'integer',
			],
			'CLOSED' => [
				'data_type' => 'integer',
			],
			'EXPIRED' => [
				'data_type' => 'integer',
			],
			'NEW_COMMENTS' => [
				'data_type' => 'integer',
			],

			'MY_EXPIRED' => [
				'data_type' => 'integer',
			],
			'MY_EXPIRED_SOON' => [
				'data_type' => 'integer',
			],
			'MY_NOT_VIEWED' => [
				'data_type' => 'integer',
			],
			'MY_WITHOUT_DEADLINE' => [
				'data_type' => 'integer',
			],
			'MY_NEW_COMMENTS' => [
				'data_type' => 'integer',
			],

			'ORIGINATOR_WITHOUT_DEADLINE' => [
				'data_type' => 'integer',
			],
			'ORIGINATOR_EXPIRED' => [
				'data_type' => 'integer',
			],
			'ORIGINATOR_WAIT_CTRL' => [
				'data_type' => 'integer',
			],
			'ORIGINATOR_NEW_COMMENTS' => [
				'data_type' => 'integer',
			],

			'AUDITOR_EXPIRED' => [
				'data_type' => 'integer',
			],
			'AUDITOR_NEW_COMMENTS' => [
				'data_type' => 'integer',
			],

			'ACCOMPLICES_EXPIRED' => [
				'data_type' => 'integer',
			],
			'ACCOMPLICES_EXPIRED_SOON' => [
				'data_type' => 'integer',
			],
			'ACCOMPLICES_NOT_VIEWED' => [
				'data_type' => 'integer',
			],
			'ACCOMPLICES_NEW_COMMENTS' => [
				'data_type' => 'integer',
			],

			// references
			'USER' => [
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => ['=this.USER_ID' => 'ref.ID'],
			],
			'GROUP' => [
				'data_type' => 'Bitrix\Socialnetwork\Workgroup',
				'reference' => ['=this.GROUP_ID' => 'ref.ID'],
			],
		];
	}
}