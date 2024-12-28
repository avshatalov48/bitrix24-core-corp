<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\Facade\User;
use Bitrix\AI\Model\RoleFavoriteTable;
use Bitrix\AI\Model\RoleTable;
use Bitrix\AI\Model\RoleTranslateNameTable;
use Bitrix\AI\ShareRole\Model\OwnerTable;
use Bitrix\AI\ShareRole\Service\GridRole\Dto\GridParamsDto;
use Bitrix\AI\ShareRole\Service\GridRole\Enum\Order;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM\Query\Join;

class GridRoleRepository extends BaseRepository
{
	public const FLAG_IS_NOT_SYSTEM = 'N';
	public const FLAG_IS_ACTIVE = 1;
	public const FLAG_IS_NOT_ACTIVE = 0;
	public const FLAG_IS_DELETED = 1;
	public const FLAG_IS_NOT_DELETED = 0;

	public function __construct(
		protected UserAccessRepository $userAccessRepository
	)
	{
	}

	public function getAvailableRolesForGrid(int $userId, GridParamsDto $params): array
	{
		$query = RoleTable::query()
			->setSelect(['ID'])
			->registerRuntimeField(new Reference(
				'ROLE_OWNERS',
				OwnerTable::class,
				Join::on('this.ID', 'ref.ROLE_ID')
					->where('ref.USER_ID', $userId),
				['join_type' => Join::TYPE_LEFT]
			))
			->where('IS_SYSTEM', static::FLAG_IS_NOT_SYSTEM)
			->where($this->userAccessRepository->getAccessConditionsForUser($userId))
			->setGroup(['ID'])
		;

		$this->setFilterByParams($query, $params);

		return $query->fetchAll();
	}

	public function getRolesForGrid(int $userId, GridParamsDto $params): array
	{
		$shareCodes = $this->getFieldByDbType(
			new ExpressionField(
				'SHARE_CODES',
				"GROUP_CONCAT(DISTINCT %s SEPARATOR ',')",
				['ROLE_SHARES.ACCESS_CODE']
			),
			new ExpressionField(
				'SHARE_CODES',
				"STRING_AGG(DISTINCT %s, ',')",
				['ROLE_SHARES.ACCESS_CODE']
			)
		);

		$isFavorite = $this->getFieldByDbType(
			new ExpressionField(
				'IS_FAVORITE',
				'IF(%s IS NOT NULL, 1, 0)',
				['ROLE_FAVORITES.ROLE_CODE']
			),
			new ExpressionField(
				'IS_FAVORITE',
				'CASE WHEN %s IS NOT NULL THEN 1 ELSE 0 END',
				['ROLE_FAVORITES.ROLE_CODE']
			)
		);

		$authorName = $this->getFieldByDbType(
			new ExpressionField(
				'AUTHOR',
				'CONCAT(%s, \' \' , %s)',
				['USER_AUTHOR.NAME', 'USER_AUTHOR.LAST_NAME']
			),
			new ExpressionField(
				'AUTHOR',
				'CONCAT_WS(\' \', %s, %s)',
				['USER_AUTHOR.NAME', 'USER_AUTHOR.LAST_NAME']
			)
		);

		$editorName = $this->getFieldByDbType(
			new ExpressionField(
				'EDITOR',
				'CONCAT(%s, \' \' , %s)',
				['USER_EDITOR.NAME', 'USER_EDITOR.LAST_NAME']
			),
			new ExpressionField(
				'EDITOR',
				'CONCAT_WS(\' \', %s, %s)',
				['USER_EDITOR.NAME', 'USER_EDITOR.LAST_NAME']
			)
		);

		$query = RoleTable::query()
			->setSelect([
				'ID',
				'CODE',
				'TITLE' => 'DEFAULT_NAME',
				'LOCALIZED_NAME' => 'NAME_TRANSLATE.TEXT',
				'IS_ACTIVE',
				'AUTHOR_ID',
				'AUTHOR_NAME' => 'USER_AUTHOR.NAME',
				'AUTHOR_LAST_NAME' => 'USER_AUTHOR.LAST_NAME',
				'AUTHOR_SECOND_NAME' => 'USER_AUTHOR.SECOND_NAME',
				'AUTHOR_EMAIL' => 'USER_AUTHOR.EMAIL',
				'AUTHOR_LOGIN' => 'USER_AUTHOR.LOGIN',
				'AUTHOR_PHOTO_ID' => 'USER_AUTHOR.PERSONAL_PHOTO',
				'AUTHOR_GENDER' => 'USER_AUTHOR.PERSONAL_GENDER',
				'EDITOR_ID',
				'EDITOR_NAME' => 'USER_EDITOR.NAME',
				'EDITOR_LAST_NAME' => 'USER_EDITOR.LAST_NAME',
				'EDITOR_SECOND_NAME' => 'USER_EDITOR.SECOND_NAME',
				'EDITOR_EMAIL' => 'USER_EDITOR.EMAIL',
				'EDITOR_LOGIN' => 'USER_EDITOR.LOGIN',
				'EDITOR_PHOTO_ID' => 'USER_EDITOR.PERSONAL_PHOTO',
				'EDITOR_GENDER' => 'USER_EDITOR.PERSONAL_GENDER',
				'DATE_CREATE',
				'DATE_MODIFY',
				'SHARE_CODES' => $shareCodes,
				'IS_FAVORITE' => $isFavorite,
				'AUTHOR' => $authorName,
				'EDITOR' => $editorName,
				'IS_DELETED' => 'ROLE_OWNERS.IS_DELETED',
			])
			->registerRuntimeField(new Reference(
				'ROLE_OWNERS',
				OwnerTable::class,
				Join::on('this.ID', 'ref.ROLE_ID')
					->where('ref.USER_ID', $userId),
				['join_type' => Join::TYPE_LEFT]
			))
			->registerRuntimeField(new Reference(
				'ROLE_FAVORITES',
				RoleFavoriteTable::class,
				Join::on('this.CODE', 'ref.ROLE_CODE')
					->where('ref.USER_ID', $userId),
				['join_type' => Join::TYPE_LEFT]
			))
			->registerRuntimeField(new Reference(
				'NAME_TRANSLATE',
				RoleTranslateNameTable::class,
				Join::on('this.ID', 'ref.ROLE_ID')
					->where('ref.LANG', User::getUserLanguage()),
				['join_type' => Join::TYPE_LEFT]
			))
			->whereIn('ID', $params->filter->roleIds)
			->setGroup([
				'ID',
				'USER_AUTHOR.NAME',
				'USER_AUTHOR.LAST_NAME',
				'USER_EDITOR.NAME',
				'USER_EDITOR.LAST_NAME',
				'USER_AUTHOR.PERSONAL_PHOTO',
				'USER_AUTHOR.PERSONAL_GENDER',
				'USER_EDITOR.PERSONAL_PHOTO',
				'USER_EDITOR.PERSONAL_GENDER',
				'IS_FAVORITE',
				'ROLE_OWNERS.IS_DELETED',
			])
			->setLimit($params->limit)
			->setOffset($params->offset)
		;

		[$field, $rule] = $this->getDataForOrder($params->order);

		if (!empty($field) && !empty($rule))
		{
			$query->setOrder([$field => $rule]);
		}

		return $query->fetchAll();
	}

	/**
	 * @param array{string, string} $orderArray
	 * @return array
	 */
	protected function getDataForOrder(array $orderArray): array
	{
		if (empty($orderArray[0]) || empty($orderArray[1]))
		{
			return ['', ''];
		}

		$orderField = $orderArray[0];
		$rule = $orderArray[1];

		$map = $this->getMapOrder();
		if (empty($map[$orderField]))
		{
			return ['', ''];
		}

		return [$map[$orderField], $rule];
	}

	protected function getMapOrder(): array
	{
		return [
			Order::Name->value => "DEFAULT_NAME",
			Order::Author->value => "AUTHOR",
			Order::Editor->value => "EDITOR",
			Order::DateCreate->value => "DATE_CREATE",
			Order::DateModify->value => "DATE_MODIFY",
		];
	}

	protected function setFilterByParams(Query $query, GridParamsDto $params): void
	{
		if (!empty($params->filter->authors))
		{
			$query->whereIn('AUTHOR_ID', $params->filter->authors);
		}

		if (!empty($params->filter->editors))
		{
			$query->whereIn('EDITOR_ID', $params->filter->editors);
		}

		if (!empty($params->filter->name))
		{
			$searchStr = $this->getSqlHelper()->forSql($params->filter->name);
			$query->whereLike('DEFAULT_NAME', "%{$searchStr}%");
		}

		if (!is_null($params->filter->isDeleted))
		{
			if (!$params->filter->isDeleted)
			{
				$accessConditions = (new ConditionTree())
					->logic(ConditionTree::LOGIC_OR)
					->where('ROLE_OWNERS.IS_DELETED', static::FLAG_IS_NOT_DELETED)
					->whereNull('ROLE_OWNERS.IS_DELETED')
				;
				$query->where($accessConditions);
			}
			else
			{
				$query->where('ROLE_OWNERS.IS_DELETED', static::FLAG_IS_DELETED);
			}
		}

		if (!is_null($params->filter->isActive))
		{
			if (!$params->filter->isActive)
			{
				$query->where('IS_ACTIVE', static::FLAG_IS_NOT_ACTIVE);
			}
			else
			{
				$query->where('IS_ACTIVE', static::FLAG_IS_ACTIVE);
			}
		}

		if (!empty($params->filter->dateCreateStart) && !empty($params->filter->dateCreateEnd))
		{
			$query->where('DATE_CREATE', '>=', $params->filter->dateCreateStart);
			$query->where('DATE_CREATE', '<=', $params->filter->dateCreateEnd);
		}

		if (!empty($params->filter->dateModifyStart) && !empty($params->filter->dateModifyEnd))
		{
			$query->where('DATE_MODIFY', '>=', $params->filter->dateModifyStart);
			$query->where('DATE_MODIFY', '<=', $params->filter->dateModifyEnd);
		}
	}

	public function getAvailableRolesForGridWithUserInShare(array $userList, array $availableRoleList): array
	{
		$query = RoleTable::query()
			->setSelect(['ID'])
			->whereIn('ID', $availableRoleList)
		;

		$query->where(
			$this->userAccessRepository->getAccessConditionsForUsers($userList)
		);

		return $query->fetchAll();
	}
}
