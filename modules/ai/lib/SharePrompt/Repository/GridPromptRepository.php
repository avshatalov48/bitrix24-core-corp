<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\Model\PromptCategoryTable;
use Bitrix\AI\Model\PromptTable;
use Bitrix\AI\SharePrompt\Model\OwnerTable;
use Bitrix\AI\SharePrompt\Model\ShareTable;
use Bitrix\AI\SharePrompt\Service\GridPrompt\Dto\GridParamsDto;
use Bitrix\AI\SharePrompt\Service\GridPrompt\Enum\OrderEnum;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UserTable;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;


class GridPromptRepository extends BaseRepository
{
	public const FLAG_IS_NOT_SYSTEM = 'N';

	public const SYSTEM_USER_ID = 0;

	public const FLAG_IS_ACTIVE = 1;
	public const FLAG_IS_NOT_ACTIVE = 0;

	public const FLAG_IS_DELETED = 1;
	public const FLAG_IS_NOT_DELETED = 0;

	public function __construct(
		protected UserAccessRepository $userAccessRepository
	)
	{
	}

	/**
	 * @param int $userId
	 * @param GridParamsDto $params
	 * @return list<array{ID: string}>
	 */
	public function getAvailablePromptsForGrid(int $userId, GridParamsDto $params): array
	{
		$query = PromptTable::query()
			->setSelect(['ID'])
			->registerRuntimeField(new Reference(
				'PROMPT_OWNERS',
				OwnerTable::class,
				Join::on('this.ID', 'ref.PROMPT_ID')
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

	public function getAvailablePromptsForGridWithUserInShare(array $userList, array $availablePromptList): array
	{
		$query = PromptTable::query()
			->setSelect([
				'ID'
			])
			->whereIn('ID', $availablePromptList)
		;

		$query->where(
			$this->userAccessRepository->getAccessConditionsForUsers($userList)
		);

		return $query->fetchAll();
	}

	/**
	 * @param int $userId
	 * @param GridParamsDto $params
	 * @return list<array{
	 *  ID: string,
	 *  CODE: string,
	 *  TITLE: string,
	 *  TYPE: string,
	 *  IS_ACTIVE: string,
	 *  AUTHOR_NAME: string,
	 * 	AUTHOR_LAST_NAME: string,
	 * 	AUTHOR_SECOND_NAME: string,
	 * 	AUTHOR_EMAIL: string,
	 * 	AUTHOR_ID: string,
	 * 	AUTHOR_LOGIN: string,
	 *  AUTHOR_PHOTO_ID: string,
	 *  AUTHOR_GENDER: string,
	 *	EDITOR_NAME: string,
	 *	EDITOR_LAST_NAME: string,
	 *	EDITOR_SECOND_NAME: string,
	 *	EDITOR_EMAIL: string,
	 *	EDITOR_ID: string,
	 *	EDITOR_LOGIN: string,
	 *  EDITOR_PHOTO_ID: string,
	 *  EDITOR_GENDER: string,
	 *  DATE_CREATE: string,
	 *  DATE_MODIFY: string,
	 *  CATEGORIES: string,
	 *  SHARE_CODES: string,
	 *  IS_FAVORITE: string,
	 *  IS_DELETED: string,
	 * }>
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	public function getPromptsForGrid(int $userId, GridParamsDto $params): array
	{
		$categories = $this->getFieldByDbType(
			new ExpressionField(
				'CATEGORIES',
				"GROUP_CONCAT(DISTINCT %s SEPARATOR ',')",
				['PROMPT_CATEGORIES.CODE']
			),
			new ExpressionField(
				'CATEGORIES',
				"STRING_AGG(DISTINCT %s, ',')",
				['PROMPT_CATEGORIES.CODE']
			)
		);

		$shareCodes = $this->getFieldByDbType(
			new ExpressionField(
				'SHARE_CODES',
				"GROUP_CONCAT(DISTINCT %s SEPARATOR ',')",
				['PROMPT_SHARES.ACCESS_CODE']
			),
			new ExpressionField(
				'SHARE_CODES',
				"STRING_AGG(DISTINCT %s, ',')",
				['PROMPT_SHARES.ACCESS_CODE']
			)
		);
		$query = PromptTable::query()
			->setSelect([
				'ID',
				'CODE',
				'TITLE' => 'DEFAULT_TITLE',
				'TYPE',
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
				'CATEGORIES' => $categories,
				'SHARE_CODES' => $shareCodes,
				'IS_FAVORITE' => 'PROMPT_OWNERS.IS_FAVORITE',
				'IS_DELETED' => 'PROMPT_OWNERS.IS_DELETED',
			])
			->registerRuntimeField(new Reference(
				'PROMPT_OWNERS',
				OwnerTable::class,
				Join::on('this.ID', 'ref.PROMPT_ID')
					->where('ref.USER_ID', $userId),
				['join_type' => Join::TYPE_LEFT]
			))
			->whereIn('ID', $params->filter->promptIds)
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
				'PROMPT_OWNERS.IS_FAVORITE',
				'PROMPT_OWNERS.IS_DELETED',
			])
			->setLimit($params->limit)
			->setOffset($params->offset)
		;

		list($field, $rule) = $this->getDataForOrder($params->order);
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

	protected function setFilterByParams(Query $query, GridParamsDto $params): void
	{
		if (!empty($params->filter->types))
		{
			$query->whereIn('TYPE', $params->filter->types);
		}

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
			$query->whereLike('DEFAULT_TITLE', "{$searchStr}%");
		}

		if (!is_null($params->filter->isDeleted))
		{
			if (!$params->filter->isDeleted)
			{
				$accessConditions = (new ConditionTree())
					->logic(ConditionTree::LOGIC_OR)
					->where('PROMPT_OWNERS.IS_DELETED', static::FLAG_IS_NOT_DELETED)
					->whereNull('PROMPT_OWNERS.IS_DELETED');
				$query->where($accessConditions);
			}
			else
			{
				$query->where('PROMPT_OWNERS.IS_DELETED', static::FLAG_IS_DELETED);
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

		if (!empty($params->filter->categories))
		{
			$query->whereIn('PROMPT_CATEGORIES.CODE', $params->filter->categories);
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

	protected function getMapOrder(): array
	{
		return [
			OrderEnum::NAME->value => "DEFAULT_TITLE",
			OrderEnum::TYPE->value => "TYPE",
			OrderEnum::DATE_CREATE->value => "DATE_CREATE",
			OrderEnum::DATE_MODIFY->value => "DATE_MODIFY",
		];
	}
}
