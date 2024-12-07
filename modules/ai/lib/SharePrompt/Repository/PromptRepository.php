<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\Model\PromptCategoryTable;
use Bitrix\AI\Model\PromptTable;
use Bitrix\AI\Model\PromptTranslateNameTable;
use Bitrix\AI\SharePrompt\Enums\Category;
use Bitrix\AI\SharePrompt\Model\OwnerTable;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\DB\Result;
use Bitrix\Main\ORM\Data\UpdateResult;

class PromptRepository extends BaseRepository
{
	public const IS_ACTIVE = 1;
	public const IS_SYSTEM = 'Y';
	public const IS_DELETED = 1;
	public const IS_NOT_SYSTEM = 'N';
	public const IS_WORK_WITH_RESULT = 'Y';

	public const SYSTEM_USER_ID = 0;

	public function __construct(
		protected UserAccessRepository $userAccessRepository
	)
	{
	}

	public function getByCodes(string $codeApp, string $code): bool|array
	{
		$filterExists = [];
		if (!empty($codeApp))
		{
			$filterExists['=APP_CODE'] = $codeApp;
		}

		if (!empty($code))
		{
			$filterExists['=CODE'] = $code;
		}

		if (empty($filterExists))
		{
			return false;
		}

		return PromptTable::query()
			->setSelect([
				'ID',
				'HASH',
				'SORT',
				'PARENT_ID',
				'EDITOR_ID',
			])
			->setFilter($filterExists)
			->setLimit(1)
			->fetch()
		;
	}

	/**
	 * @param int $promptId
	 * @param bool $needActivate
	 * @param $userId
	 * @return UpdateResult
	 * @throws \Exception
	 */
	public function changeActivatePrompt(int $promptId, bool $needActivate, $userId): UpdateResult
	{
		return PromptTable::update(
			$promptId,
			[
				'IS_ACTIVE' => (int)$needActivate,
				'EDITOR_ID' => $userId,
				'DATE_MODIFY' => (new DateTime())->toUserTime()
			]
		);
	}

	/**
	 * @param array $promptId
	 * @param bool $needActivate
	 * @param $userId
	 * @return Result
	 */
	public function changeActivatePrompts(array $promptId, bool $needActivate, $userId): Result
	{
		$promptIds = implode(',', $promptId);
		$dateUpdate = (new DateTime())->toUserTime();
		$needActivate = (int)$needActivate;

		return $this->getConnection()->query("
			UPDATE 
				{$this->getPromptTableName()} 
			SET 
				IS_ACTIVE = {$needActivate},
				EDITOR_ID = {$userId},
				DATE_MODIFY = '{$dateUpdate}'
			WHERE 
				ID IN ({$promptIds})
		");
	}

	/**
	 * @param string $categoryCode
	 * @param string $lang
	 * @param string|null $roleCode
	 * @return array
	 */
	public function getSystemPromptsByCategory(string $categoryCode, string $lang, ?string $roleCode): array
	{
		$query = PromptTable::query()
			->setSelect([
				'ID',
				'SORT',
				'ICON',
				'CODE',
				'TYPE',
				'APP_CODE',
				'TEXT_TRANSLATES',
				'SETTINGS',
				'CACHE_CATEGORY',
				'WORK_WITH_RESULT',
				'PROMPT',
				'IS_SYSTEM',
				'PARENT_ID',
				'SECTION',
				'TITLE_DEFAULT' => 'DEFAULT_TITLE',
				'CODE_CATEGORY_SYSTEM' => 'PROMPT_CATEGORY_SYSTEM_LEFT_JOIN.CODE',
				'TITLE_FOR_USER' => 'PROMPT_TRANSLATE_NAME_USER.TEXT'
			])
			->registerRuntimeField($this->getJoinForTranslateName($lang))
			->registerRuntimeField($this->getJoinForCodeCategorySystem())

			->where('IS_SYSTEM', '=', static::IS_SYSTEM)
			->whereNull('PARENT_ID')
			;

		$this->getJoinForCategory($query, $categoryCode);
		$this->getJoinForRoles($query, $roleCode);

		$query->setGroup([
			'ID',
			'SORT',
			'PROMPT_CATEGORY_SYSTEM_LEFT_JOIN.CODE',
			'PROMPT_TRANSLATE_NAME_USER.TEXT'
		])
		->setOrder(['SORT' => 'ASC']);

		return $query->fetchAll();
	}

	/**
	 * @param int $promptId
	 * @return array|bool
	 */
	public function getByIdForUpdate(int $promptId): array|bool
	{
		return PromptTable::query()
			->setSelect([
				'CODE',
				'TRANSLATE' => 'DEFAULT_TITLE',
				'ICON',
				'TYPE',
				'PROMPT',
				'CATEGORIES' => $this->getFieldByDbType(
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
				),
				'ACCESS_CODES' => $this->getFieldByDbType(
					new ExpressionField(
						'ACCESS_CODES',
						"GROUP_CONCAT(DISTINCT %s SEPARATOR ',')",
						['PROMPT_SHARES.ACCESS_CODE']
					),
					new ExpressionField(
						'ACCESS_CODES',
						"STRING_AGG(DISTINCT %s, ',')",
						['PROMPT_SHARES.ACCESS_CODE']
					)
				),
				'AUTHOR_ID'
			])
			->where('ID', '=', $promptId)
			->where('IS_SYSTEM', '=', static::IS_NOT_SYSTEM)
			->fetch()
		;
	}

	public function getByCode(string $code): array|bool
	{
		return PromptTable::query()
			->setSelect([
				'ID',
				'IS_SYSTEM',
			])
			->where('CODE', $code)
			->fetch()
		;
	}

	/**
	 * @param string[] $codes
	 * @return list<array{ID:string, IS_SYSTEM: string}>
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getByPromptCodes(array $codes): array
	{
		return PromptTable::query()
			->setSelect([
				'ID',
				'IS_SYSTEM',
			])
			->whereIn('CODE', $codes)
			->fetchAll()
		;
	}

	public function getMainDataWithPromptTextByCode(string $code): array|bool
	{
		return PromptTable::query()
			->setSelect([
				'ID',
				'IS_SYSTEM',
				'PROMPT',
			])
			->where('CODE', $code)
			->fetch()
		;
	}

	/**
	 * @param string $code
	 * @return array|bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getMainDataWithAuthorByCode(string $code): array|bool
	{
		return PromptTable::query()
			->setSelect([
				'ID',
				'IS_SYSTEM',
				'AUTHOR_ID',
			])
			->where('CODE', $code)
			->fetch()
		;
	}

	/**
	 * @param int[] $codes
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getByIds(array $codes): array
	{
		return PromptTable::query()
			->setSelect([
				'ID',
				'CODE'
			])
			->whereIn('CODE', $codes)
			->fetchAll()
		;
	}

	public function getAccessiblePromptList(
		int $userId,
		string $lang,
		string $category = '',
		string $promptCode = ''
	): array
	{
		$query = PromptTable::query()
			->setSelect([
				'ID',
				'ICON',
				'CODE',
				'TYPE',
				'APP_CODE',
				'TEXT_TRANSLATES',
				'SETTINGS',
				'CACHE_CATEGORY',
				'WORK_WITH_RESULT',
				'PROMPT',
				'IS_SYSTEM',
				'CODE_CATEGORY_SYSTEM' => 'PROMPT_CATEGORY_SYSTEM_LEFT_JOIN.CODE',
				'PARENT_ID',
				'SECTION',
				'SORT',
				'IS_FAVORITE' => 'PROMPT_OWNERS.IS_FAVORITE',
				'TITLE_DEFAULT' => 'DEFAULT_TITLE',
				'TITLE_FOR_USER' => 'PROMPT_TRANSLATE_NAME_USER.TEXT',
			])
			->registerRuntimeField(new Reference(
				'PROMPT_OWNERS',
				OwnerTable::class,
				Join::on('this.ID', 'ref.PROMPT_ID')
					->where('ref.USER_ID', '=', $userId),
				['join_type' => Join::TYPE_LEFT]
			))
			->registerRuntimeField($this->getJoinForTranslateName($lang))
			->registerRuntimeField($this->getJoinForCodeCategorySystem())
			->where(
				$this->userAccessRepository
					->getAccessConditionsForUser($userId)
					->where('IS_SYSTEM', '=', static::IS_SYSTEM)

			)
			->where('IS_ACTIVE', '=', static::IS_ACTIVE)
			->whereNotIn(
				'ID',
				OwnerTable::query()
					->setSelect(['PROMPT_ID'])
					->where('USER_ID', '=', $userId)
					->where('IS_DELETED', '=', static::IS_DELETED)
			)
		;

		$this->getJoinForCategory($query, $category);

		if (empty($promptCode))
		{
			$query->whereNull('PARENT_ID');
		}
		else
		{
			$query->where('CODE', '=', $promptCode);
		}

		return $query->setOrder([
			'PROMPT_SHARES.ID' => 'DESC',
			'PROMPT_OWNERS.ID' => 'DESC',
			'ID' => 'DESC',
		])
			->fetchAll()
		;
	}

	/**
	 * @param array $promptIds
	 * @param string $lang
	 * @return array
	 */
	public function getChildrenPromptListByIds(array $promptIds, array $forbiddenPromptIds, string $lang): array
	{
		$query = PromptTable::query()
			->setSelect([
				'ID',
				'SORT',
				'ICON',
				'CODE',
				'TYPE',
				'APP_CODE',
				'TEXT_TRANSLATES',
				'SETTINGS',
				'CACHE_CATEGORY',
				'WORK_WITH_RESULT',
				'PROMPT',
				'TYPE',
				'IS_SYSTEM',
				'PARENT_ID',
				'SECTION',
				'CODE_CATEGORY_SYSTEM' => 'PROMPT_CATEGORY_SYSTEM_LEFT_JOIN.CODE',
				'TITLE_DEFAULT' => 'DEFAULT_TITLE',
				'TITLE_FOR_USER' => 'PROMPT_TRANSLATE_NAME_USER.TEXT',
			])
			->registerRuntimeField($this->getJoinForTranslateName($lang))
			->registerRuntimeField($this->getJoinForCodeCategorySystem())
			->whereIn('PARENT_ID', $promptIds)
			;

		if (!empty($forbiddenPromptIds))
		{
			$query->whereNotIn('ID', $forbiddenPromptIds);
		}

		return $query->setGroup([
			'ID', 'SORT', 'PROMPT_CATEGORY_SYSTEM_LEFT_JOIN.CODE', 'PROMPT_TRANSLATE_NAME_USER.TEXT'
			])
			->setOrder([
				'SORT'
			])
			->fetchAll()
			;
	}

	public function getPromptIdInAccessibleList(
		int $userId,
		int $promptId,
		?bool $checkInFavorite = null,
		?bool $checkInDelete = null
	): array|bool
	{
		$query = PromptTable::query()
			->setSelect([
				'ID',
			])
			->registerRuntimeField(new Reference(
				'PROMPT_OWNERS',
				OwnerTable::class,
				Join::on('this.ID', 'ref.PROMPT_ID')
					->where('ref.USER_ID', '=', $userId),
				['join_type' => Join::TYPE_LEFT]
			))
			->where(
				$this->userAccessRepository
					->getAccessConditionsForUser($userId)
					->where('IS_SYSTEM', '=', static::IS_SYSTEM)

			)
			->where('ID', '=', $promptId)
			->where('IS_ACTIVE', '=', static::IS_ACTIVE);

		$this->setConditionsByOwnerFlags($query, $userId, $checkInFavorite, $checkInDelete);

		return $query->setOrder([
			'PROMPT_SHARES.ID' => 'DESC',
			'PROMPT_OWNERS.ID' => 'DESC',
			'ID' => 'DESC',
		])
			->fetch()
		;
	}

	public function getPromptDataInAccessibleList(int $userId, int $promptId): array|bool
	{
		$query = PromptTable::query()
			->setSelect([
				'ID',
				'PROMPT',
			])
			->registerRuntimeField(new Reference(
				'PROMPT_OWNERS',
				OwnerTable::class,
				Join::on('this.ID', 'ref.PROMPT_ID')
					->where('ref.USER_ID', '=', $userId),
				['join_type' => Join::TYPE_LEFT]
			))
			->where(
				$this->userAccessRepository
					->getAccessConditionsForUser($userId)
					->where('IS_SYSTEM', '=', static::IS_SYSTEM)

			)
			->where('ID', '=', $promptId)
			->where('IS_ACTIVE', '=', static::IS_ACTIVE)
		;

		return $query->setOrder([
			'PROMPT_SHARES.ID' => 'DESC',
			'PROMPT_OWNERS.ID' => 'DESC',
			'ID' => 'DESC',
		])
			->fetch()
		;
	}

	protected function setConditionsByOwnerFlags(
		Query $query,
		int $userId,
		?bool $needHiddenFavorite = null,
		?bool $needHiddenDeleted = false
	): void
	{
		if (is_null($needHiddenFavorite) && is_null($needHiddenDeleted))
		{
			return;
		}

		$subQuery = OwnerTable::query()
			->setSelect([
				'PROMPT_ID'
			])
			->where('USER_ID', '=', $userId);

		if (!is_null($needHiddenDeleted))
		{
			$subQuery->where('IS_DELETED', '=', (int)$needHiddenDeleted);
		}

		if (!is_null($needHiddenFavorite))
		{
			$subQuery->where('IS_FAVORITE', '=', (int)$needHiddenFavorite);
		}

		$query->whereNotIn('ID', $subQuery);
	}

	protected function getJoinForCategory(Query $query, string $category = ''): void
	{
		if (empty($category))
		{
			return;
		}

		$query->registerRuntimeField(
			new Reference(
				'PROMPT_CATEGORY_JOIN_INNER',
				PromptCategoryTable::class,
				Join::on('this.ID', 'ref.PROMPT_ID')
					->where('ref.CODE', $category),
				['join_type' => Join::TYPE_INNER]
			)
		);
	}

	protected function getJoinForTranslateName(string $lang): Reference
	{
		return new Reference(
			'PROMPT_TRANSLATE_NAME_USER',
			PromptTranslateNameTable::class,
			Join::on('this.ID', 'ref.PROMPT_ID')
				->where('ref.LANG', $lang),
			['join_type' => Join::TYPE_LEFT]
		);
	}

	protected function getJoinForCodeCategorySystem(): Reference
	{
		return new Reference(
			'PROMPT_CATEGORY_SYSTEM_LEFT_JOIN',
			PromptCategoryTable::class,
			Join::on('this.ID', 'ref.PROMPT_ID')
				->where('ref.CODE', Category::SYSTEM->value),
			['join_type' => Join::TYPE_LEFT]
		);
	}

	protected function getJoinForRoles(Query $query, $roleCode): void
	{
		if (empty($roleCode))
		{
			return;
		}

		$query->where('ROLES.CODE', '=', $roleCode);
	}

	private function getPromptTableName(): string
	{
		return PromptTable::getTableName();
	}
}
