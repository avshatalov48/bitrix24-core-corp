<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\Facade\User;
use Bitrix\AI\Model\RoleFavoriteTable;
use Bitrix\AI\Model\RoleTable;
use Bitrix\AI\Model\RoleTranslateDescriptionTable;
use Bitrix\AI\Model\RoleTranslateNameTable;
use Bitrix\AI\ShareRole\Model\OwnerTable;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\DB\Result;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;

class RoleRepository extends BaseRepository
{
	public const FLAG_IS_ACTIVE = 1;
	public const FLAG_IS_SYSTEM = 'Y';
	public const FLAG_IS_NOT_SYSTEM = 'N';
	public const SYSTEM_USER_ID = 0;

	public function __construct(
		protected UserAccessRepository $userAccessRepository
	)
	{
	}

	public function getByCode(string $code): array|bool
	{
		return RoleTable::query()
			->setSelect([
				'ID',
				'HASH',
				'SORT',
				'EDITOR_ID',
				'IS_SYSTEM',
			])
			->where('CODE', $code)
			->setLimit(1)
			->fetch()
		;
	}

	/**
	 * @param int $roleId
	 * @return array|bool
	 */
	public function getByIdForUpdate(int $roleId): array|bool
	{
		$userLanguage = User::getUserLanguage();
		$accessCodesField = $this->getFieldByDbType(
			new ExpressionField(
				'CATEGORIES',
				'GROUP_CONCAT(DISTINCT %s SEPARATOR \',\')',
				['ROLE_SHARES.ACCESS_CODE']
			),
			new ExpressionField(
				'CATEGORIES',
				'STRING_AGG(DISTINCT %s, \',\')',
				['ROLE_SHARES.ACCESS_CODE']
			)
		);

		return RoleTable::query()
			->setSelect([
				'CODE',
				'DEFAULT_NAME',
				'DEFAULT_DESCRIPTION',
				'AVATAR',
				'INSTRUCTION',
				'AUTHOR_ID',
				'DESCRIPTION_TEXT' => 'ROLE_TRANSLATE_DESCRIPTION.TEXT',
				'NAME_TEXT' => 'ROLE_TRANSLATE_NAME.TEXT',
				'ACCESS_CODES' => $accessCodesField,
			])
			->registerRuntimeField(new Reference(
					'ROLE_TRANSLATE_DESCRIPTION',
					RoleTranslateDescriptionTable::class,
					Join::on('this.ID', 'ref.ROLE_ID')
						->where('ref.LANG', $userLanguage),
					['join_type' => Join::TYPE_LEFT]
				)
			)
			->registerRuntimeField(new Reference(
					'ROLE_TRANSLATE_NAME',
					RoleTranslateNameTable::class,
					Join::on('this.ID', 'ref.ROLE_ID')
						->where('ref.LANG', $userLanguage),
					['join_type' => Join::TYPE_LEFT]
				)
			)
			->where('ID', $roleId)
			->where('IS_SYSTEM', static::FLAG_IS_NOT_SYSTEM)
			->setGroup([
				'CODE',
				'AVATAR',
				'INSTRUCTION',
				'AUTHOR_ID'
			])
			->fetch()
		;
	}

	/**
	 * @param int $roleId
	 * @param bool $needActivate
	 * @param $userId
	 * @return UpdateResult
	 * @throws \Exception
	 */
	public function changeActivateRole(int $roleId, bool $needActivate, $userId): UpdateResult
	{
		return RoleTable::update(
			$roleId,
			[
				'IS_ACTIVE' => (int)$needActivate,
				'EDITOR_ID' => $userId,
				'DATE_MODIFY' => (new DateTime())->toUserTime(),
			]
		);
	}

	public function getRoleIdInAccessibleList(
		int $userId,
		int $roleId,
		?bool $checkInFavorite = null,
		?bool $checkInDelete = null
	): array|bool
	{
		$query = RoleTable::query()
			->setSelect([
				'ID',
			])
			->registerRuntimeField(new Reference(
				'ROLE_OWNERS',
				OwnerTable::class,
				Join::on('this.ID', 'ref.ROLE_ID')
					->where('ref.USER_ID', $userId),
				['join_type' => Join::TYPE_LEFT]
			))
			->where(
				$this->userAccessRepository
					->getAccessConditionsForUser($userId)
					->where('IS_SYSTEM', static::FLAG_IS_SYSTEM)
			)
			->where('ID', $roleId)
			->where('IS_ACTIVE', static::FLAG_IS_ACTIVE);

		$this->setIsDeletedConditions($query, $userId, $checkInDelete);
		$this->setIsFavoriteConditions($query, $userId, $checkInFavorite);

		return $query->setOrder([
			'ROLE_SHARES.ID' => 'DESC',
			'ROLE_OWNERS.ID' => 'DESC',
			'ID' => 'DESC',
			])
			->fetch()
		;
	}

	protected function setIsDeletedConditions(
		Query $query,
		int $userId,
		?bool $needHiddenDeleted = false
	): void
	{
		if (is_null($needHiddenDeleted))
		{
			return;
		}

		$subQuery = OwnerTable::query()
			->setSelect([
				'ROLE_ID',
			])
			->where('USER_ID', $userId)
			->where('IS_DELETED', (int)$needHiddenDeleted);

		$query->whereNotIn('ID', $subQuery);
	}

	protected function setIsFavoriteConditions(
		Query $query,
		int $userId,
		?bool $needHiddenFavorite = false,
	): void
	{
		if (is_null($needHiddenFavorite))
		{
			return;
		}

		$subQuery = RoleFavoriteTable::query()
			->setSelect([
				'ROLE_CODE',
			])
			->where('USER_ID', $userId);

		$query->whereNotIn('ID', $subQuery);
	}

	public function getByRoleCodes(array $roleCodes): array
	{
		return RoleTable::query()
			->setSelect([
				'ID',
				'IS_SYSTEM',
			])
			->whereIn('CODE', $roleCodes)
			->fetchAll()
		;
	}

	public function changeActivateRoles(array $roleIds, bool $needActivate, int $userId): UpdateResult
	{
		$dateUpdate = (new DateTime())->toUserTime();
		$needActivateInt = (int)$needActivate;

		return RoleTable::updateMulti(
			$roleIds,
			[
				'IS_ACTIVE' => $needActivateInt,
				'EDITOR_ID' => $userId,
				'DATE_MODIFY' => $dateUpdate,
			]
		);
	}

	public function getAvatarByRoleId(int $roleId): array|bool
	{
		return RoleTable::query()
			->setSelect([
				'AVATAR',
			])
			->where('ID', $roleId)
			->where('IS_SYSTEM', static::FLAG_IS_NOT_SYSTEM)
			->fetch()
		;
	}
}
