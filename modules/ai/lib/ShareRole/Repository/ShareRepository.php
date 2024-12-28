<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\Model\RoleTable;
use Bitrix\AI\ShareRole\Dto\CreateDto;
use Bitrix\AI\ShareRole\Model\ShareTable;
use Bitrix\AI\ShareRole\Model\OwnerTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Main\ORM\Data\AddResult;

class ShareRepository extends BaseRepository
{
	public function __construct(
		protected UserAccessRepository $userAccessRepository
	)
	{
	}

	/**
	 * @param CreateDto $createRequestDTO
	 * @return AddResult
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function create(CreateDto $createRequestDTO): AddResult
	{
		$rows = [];
		foreach ($createRequestDTO->accessCodesData as $code)
		{
			$rows[] = [
				'ROLE_ID' => $createRequestDTO->roleId,
				'ACCESS_CODE' => $code,
				'DATE_CREATE' => $createRequestDTO->dateCreate,
				'CREATED_BY' => $createRequestDTO->userCreatorId,
			];
		}

		return ShareTable::addMulti($rows, true);
	}

	/**
	 * @param int $roleId
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function findByRoleId(int $roleId): array
	{
		return ShareTable::query()
			->setSelect(['ID'])
			->where('ROLE_ID', $roleId)
			->setLimit(1)
			->fetchAll()
		;
	}

	/**
	 * @param int[] $roleId
	 * @param int $userId
	 * @return array
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	public function getInfoAccessRole(array $roleId, int $userId): array
	{
		return ShareTable::query()
			->setSelect([
				'SHARE_ID' => 'ID',
				'ROLE_ID',
				'OWNER_ID' => 'ROLE_OWNERS.ID'
			])
			->registerRuntimeField(new Reference(
				'ROLE_OWNERS',
				OwnerTable::class,
				Join::on('this.ROLE_ID', 'ref.ROLE_ID')
					->where('ref.USER_ID', '=', $userId),
				['join_type'=>Join::TYPE_LEFT]
			))
			->whereIn('ROLE_ID', $roleId)
			->where($this->userAccessRepository->getAccessConditionsForUser($userId, ''))
			->fetchAll()
		;
	}

	/**
	 * @param string $roleCode
	 * @param int $userId
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getInfoAccessRoleByCode(string $roleCode, int $userId): bool
	{
		$result = ShareTable::query()
			->setSelect([
				'SHARE_ID' => 'ID',
				'ROLE_ID',
				'OWNER_ID' => 'ROLE_OWNERS.ID'
			])
			->registerRuntimeField(new Reference(
				'ROLE_OWNERS',
				OwnerTable::class,
				Join::on('this.ROLE_ID', 'ref.ROLE_ID')
					->where('ref.USER_ID', '=', $userId),
				['join_type'=>Join::TYPE_LEFT]
			))
			->registerRuntimeField(new Reference(
				'ROLES',
				RoleTable::class,
				Join::on('this.ROLE_ID', 'ref.ID'),
				['join_type'=>Join::TYPE_LEFT]
			))
			->where('ROLES.CODE', $roleCode)
			->where('ROLES.IS_ACTIVE', true)
			->where($this->userAccessRepository->getAccessConditionsForUser($userId, ''))
			->fetchAll()
		;
		return !empty($result);
	}

	public function deleteRoleId(int $roleId): void
	{
		ShareTable::deleteByFilter([
			'=ROLE_ID' => $roleId
		]);
	}

	/**
	 * @param int $roleId
	 * @return list<array{ACCESS_CODE: string}>
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getAccessCodesForRole(int $roleId): array
	{
		return ShareTable::query()
			->setSelect(['ACCESS_CODE'])
			->where('ROLE_ID', $roleId)
			->fetchAll()
		;
	}
}
