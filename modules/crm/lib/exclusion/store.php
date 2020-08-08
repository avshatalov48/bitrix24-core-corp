<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Exclusion;

use Bitrix\Main\DB\Result;
use Bitrix\Crm\Communication;
use Bitrix\Crm\FieldMultiTable;
use Bitrix\Crm\Exclusion\Entity\ExclusionTable;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\DB\SqlQueryException;

use Bitrix\Crm\LeadTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\QuoteTable;

/**
 * Class Store of exclusions
 *
 * @package Bitrix\Crm\Exclusion
 */
class Store
{
	/**
	 * Add exclusions from entity.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID.
	 * @param string|null $comment Comment.
	 * @return void
	 * @throws NotSupportedException
	 */
	public static function addFromEntity($entityTypeId, $entityId, $comment = null)
	{
		self::addFromEntities($entityTypeId, [$entityId], $comment);
	}

	/**
	 * Add exclusions from entities.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @param int[] $entities Entities.
	 * @param string|null $comment Comment.
	 * @return void
	 * @throws NotSupportedException
	 */
	public static function addFromEntities($entityTypeId, array $entities, $comment = null)
	{
		$batch = [];

		$list = [];
		$entityTypeId = (int) $entityTypeId;
		switch ($entityTypeId)
		{
			case \CCrmOwnerType::Contact:
			case \CCrmOwnerType::Company:
				$batch[$entityTypeId] = $entities;
				break;

			case \CCrmOwnerType::Lead:
				$leads = LeadTable::getList([
					'select' => [
						'ID', 'IS_RETURN_CUSTOMER', 'COMPANY_ID', 'CONTACT_ID',
						'HAS_EMAIL', 'HAS_PHONE'
					],
					'filter' => ['=ID' => $entities]
				]);
				foreach ($leads as $entity)
				{
					if ($entity['IS_RETURN_CUSTOMER'] === 'Y')
					{
						$list[] = [
							'CONTACT_ID' => $entity['CONTACT_ID'],
							'COMPANY_ID' => $entity['COMPANY_ID'],
						];
					}
					elseif ($entity['HAS_EMAIL'] === 'Y' || $entity['HAS_PHONE'] === 'Y')
					{
						$batch[\CCrmOwnerType::Lead][] = $entity['ID'];
					}
				}
				break;

			case \CCrmOwnerType::Deal:
				$list = DealTable::getList([
					'select' => ['COMPANY_ID', 'CONTACT_ID'],
					'filter' => ['=ID' => $entities]
				]);
				break;

			case \CCrmOwnerType::Quote:
				$list = QuoteTable::getList([
					'select' => ['COMPANY_ID', 'CONTACT_ID'],
					'filter' => ['=ID' => $entities]
				]);
				break;

			default:
				throw new NotSupportedException("Entity type ID `$entityTypeId` not supported.");
		}

		foreach ($list as $entity)
		{
			if ($entity['CONTACT_ID'])
			{
				$batch[\CCrmOwnerType::Contact][] = $entity['CONTACT_ID'];
			}
			if ($entity['COMPANY_ID'])
			{
				$batch[\CCrmOwnerType::Company][] = $entity['COMPANY_ID'];
			}
		}


		if (empty($batch))
		{
			return;
		}


		$typeMap = [
			\CCrmFieldMulti::PHONE => Communication\Type::PHONE,
			\CCrmFieldMulti::EMAIL => Communication\Type::EMAIL,
		];
		foreach ($batch as $entityTypeId => $list)
		{
			$list = FieldMultiTable::getList([
				'select' => ['TYPE_ID', 'VALUE'],
				'filter' => [
					'=ENTITY_ID' => \CCrmOwnerType::resolveName($entityTypeId),
					'=ELEMENT_ID' => $list,
					'=TYPE_ID' => array_keys($typeMap)
				],
			]);
			foreach ($list as $item)
			{
				self::add($typeMap[$item['TYPE_ID']], $item['VALUE'], $comment);
			}
		}
	}

	/**
	 * Add exclusion.
	 *
	 * @param int $typeId Type ID.
	 * @param string $code Code.
	 * @param string|null $comment Comment.
	 * @return int|null
	 */
	public static function add($typeId, $code, $comment = null)
	{
		if (self::has($typeId, $code))
		{
			return null;
		}

		try
		{
			return ExclusionTable::add(array(
				'TYPE_ID' => $typeId,
				'CODE' => Communication\Normalizer::normalize($code, $typeId),
				'COMMENT' => $comment,
			))->getId();
		}
		catch (SqlQueryException $exception)
		{
			if (mb_strpos($exception->getDatabaseMessage(), '(1062)') === false)
			{
				throw $exception;
			}
		}

		return null;
	}

	/**
	 * Returns true if code is in list.
	 *
	 * @param int $typeId Type ID.
	 * @param string $code Code.
	 * @return bool
	 */
	public static function has($typeId, $code)
	{
		$result = ExclusionTable::getRow([
			'select' => ['ID'],
			'filter' => [
				'=TYPE_ID' => $typeId,
				'=CODE' => Communication\Normalizer::normalize($code, $typeId)
			]
		]);

		return $result !== null;
	}

	/**
	 * Remove exclusion.
	 *
	 * @param int $id ID.
	 * @return bool
	 */
	public static function remove($id)
	{
		return ExclusionTable::delete($id)->isSuccess();
	}

	/**
	 * Get list.
	 *
	 * @param array $parameters Parameters.
	 * @return Result
	 */
	public static function getList(array $parameters)
	{
		return ExclusionTable::getList($parameters);
	}

	/**
	 * Returns true if email is in list.
	 *
	 * @param string $email Email.
	 * @return bool
	 */
	public static function hasEmail($email)
	{
		return static::has(Communication\Type::EMAIL, $email);
	}

	/**
	 * Returns true if phone is in list.
	 *
	 * @param string $phone Phone.
	 * @return bool
	 */
	public static function hasPhone($phone)
	{
		return static::has(Communication\Type::PHONE, $phone);
	}

	/**
	 * Import list of exclusions.
	 * <p>
	 * [
	 *    '+71112223344;Comment 1',
	 *    'example@example.com;Comment 2'
	 * ]
	 * </p>
	 *
	 * @param array $list List.
	 * @return void
	 */
	public static function import(array $list)
	{
		ExclusionTable::addExclusionBatch($list);
	}
}