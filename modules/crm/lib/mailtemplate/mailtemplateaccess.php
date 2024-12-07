<?php

namespace Bitrix\Crm\MailTemplate;

use Bitrix\Crm\Dto\MailTemplate\AccessEntity;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Crm\Model\UserMailTemplateAccessTable;

class MailTemplateAccess
{
	public const USER_ENTITY_TYPE = 'user';
	public const DEPARTMENT_ENTITY_TYPE = 'department';
	public const USER_ENTITY_TYPE_CODE = 1;
	public const DEPARTMENT_ENTITY_TYPE_CODE = 2;
	public const ALL_USERS_ENTITY = ['meta-user','all-users'];

	/**
	 * @param int $templateId
	 * @param AccessEntity[] $accessEntities
	 * @return void
	 * @throws \Exception
	 */
	public static function setLimitedAccessToTemplate(int $templateId, array $newAccessEntities, array $curAccessEntities = []): void
	{
		if (empty($curAccessEntities))
		{
			$entitiesToAdd = $newAccessEntities;
		}
		else
		{
			$compareAccessEntities = function ($firstAccessEntity,$secondAccessEntity){
				return $firstAccessEntity <=> $secondAccessEntity;
			};

			$entitiesToAdd = array_udiff($newAccessEntities, $curAccessEntities, $compareAccessEntities);
			$entitiesToRemove = array_udiff($curAccessEntities, $newAccessEntities, $compareAccessEntities);
			if (!empty($entitiesToRemove))
			{
				foreach ($entitiesToRemove as $entity)
				{
					self::deleteAccessEntity($templateId,$entity);
				}
			}
		}

		if (!empty($entitiesToAdd))
		{
			foreach ($entitiesToAdd as $entity)
			{
				if ($entity->entityCode > 0)
				{
					self::addAccessEntity($templateId, $entity);
				}
			}
		}
	}

	/**
	 * @return AccessEntity[]
	 */
	public static function getAccessDataByTemplateID(int $templateId): array
	{
		$list = \Bitrix\Crm\Model\UserMailTemplateAccessTable::getList([
			'select' => ['ENTITY_ID','ENTITY_TYPE'],
			'filter' => [
				'=TEMPLATE_ID' => $templateId,
			],
		]);
		$result = [];
		while ($row = $list->fetch())
		{
			$result[] = new AccessEntity((int)$row['ENTITY_ID'],self::getTypeByCode($row['ENTITY_TYPE']));
		}
		return $result;
	}

	public static function addAccessEntity(int $templateId, AccessEntity $entity): void
	{
		\Bitrix\Crm\Model\UserMailTemplateAccessTable::add([
			'TEMPLATE_ID' => $templateId,
			'ENTITY_ID' => $entity->entityId,
			'ENTITY_TYPE' => $entity->entityCode,
		]);
	}

	public static function deleteAccessEntity(int $templateId, AccessEntity $entity): void
	{
		$list = \Bitrix\Crm\Model\UserMailTemplateAccessTable::getList([
			'filter' => [
				'=TEMPLATE_ID' => $templateId,
				'=ENTITY_ID' => $entity->entityId,
				'=ENTITY_TYPE' => $entity->entityCode,
			],
		]);
		while ($row = $list->fetch())
		{
			\Bitrix\Crm\Model\UserMailTemplateAccessTable::delete($row['ID']);
		}
	}

	public static function deleteAccessRelationsByTemplateID(int $templateId): void
	{
		$list = \Bitrix\Crm\Model\UserMailTemplateAccessTable::getList([
			'filter' => [
				'=TEMPLATE_ID' => $templateId,
			],
		]);
		while ($row = $list->fetch())
		{
			\Bitrix\Crm\Model\UserMailTemplateAccessTable::delete($row['ID']);
		}
	}

	public static function getAllAvailableSharedTemplatesId(int $userId = 0): array
	{
		if($userId <= 0)
		{
			$userId = \Bitrix\Crm\Service\Container::getInstance()->getContext()->getUserId();
		}

		$userDepartments = \CIntranetUtils::GetUserDepartments($userId);
		$filter = [
			'LOGIC' => 'OR',
			[
				'LOGIC' => 'AND',
				'=ENTITY_ID' => $userId,
				'=ENTITY_TYPE' => self::USER_ENTITY_TYPE_CODE,
			],
			[
				'LOGIC' => 'AND',
				'=ENTITY_ID' => $userDepartments,
				'=ENTITY_TYPE' => self::DEPARTMENT_ENTITY_TYPE_CODE,
			]

		];

		$result = [];
		$list = \Bitrix\Crm\Model\UserMailTemplateAccessTable::getList([
			'select' => ['TEMPLATE_ID'],
			'filter' => $filter,
		]);
		while ($row = $list->fetch())
		{
			$result[] = (int)$row['TEMPLATE_ID'];
		}
		return array_unique($result);
	}

	public static function getCodeByType(string $type): int
	{
		return match($type){
			self::USER_ENTITY_TYPE => self::USER_ENTITY_TYPE_CODE,
			self::DEPARTMENT_ENTITY_TYPE => self::DEPARTMENT_ENTITY_TYPE_CODE,
			default => 0
		};
	}

	public static function getTypeByCode(int $type): string | null
	{
		return match($type){
			self::USER_ENTITY_TYPE_CODE => self::USER_ENTITY_TYPE,
			self::DEPARTMENT_ENTITY_TYPE_CODE => self::DEPARTMENT_ENTITY_TYPE,
			default => null
		};
	}

	public static function checkAccessToLimitedTemplate(int $templateId, int $userId = 0): bool
	{
		if($userId <= 0)
		{
			$userId = (int)CurrentUser::get()->getId();
		}

		if($userId <= 0)
		{
			return false;
		}

		$userDepartments = \CIntranetUtils::GetUserDepartments($userId);
		$filter = [
			'LOGIC' => 'AND',
			'=TEMPLATE_ID' => $templateId,
			[
				'LOGIC' => 'OR',
				[
					'LOGIC' => 'AND',
					'=ENTITY_ID' => $userId,
					'=ENTITY_TYPE' => self::USER_ENTITY_TYPE_CODE,
				],
				[
					'LOGIC' => 'AND',
					'@ENTITY_ID' => $userDepartments,
					'=ENTITY_TYPE' => self::DEPARTMENT_ENTITY_TYPE_CODE,
				]
			]
		];

		$template = \Bitrix\Crm\Model\UserMailTemplateAccessTable::getList([
			'select' => ['ID'],
			'filter' => $filter,
			'limit' => 1
		])->fetch();

		if ($template)
		{
			return true;
		}

		return false;
	}
}
