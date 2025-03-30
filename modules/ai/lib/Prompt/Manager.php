<?php

namespace Bitrix\AI\Prompt;

use Bitrix\AI\Container;
use Bitrix\AI\Facade\User;
use Bitrix\AI\Model\PromptTable;
use Bitrix\AI\SharePrompt\Repository\PromptRepository;
use Bitrix\AI\SharePrompt\Service\PromptService;
use Bitrix\AI\Updater;
use Bitrix\Main\Type\Collection as TypeCollection;

class Manager
{
	/**
	 * Returns Prompt Item by code.
	 *
	 * @param string $promptCode Prompt's code.
	 * @return Item|null
	 */
	public static function getByCode(string $promptCode): ?Item
	{
		static $prompts = [];

		if (empty($promptCode))
		{
			return null;
		}

		if (array_key_exists($promptCode, $prompts))
		{
			return $prompts[$promptCode];
		}

		$prompts[$promptCode] = self::getPromptService()->getAccessiblePrompt(
			User::getCurrentUserId(),
			User::getUserLanguage(),
			$promptCode
		);

		return $prompts[$promptCode];
	}

	/**
	 * Returns Prompts by category in Tree mode from cache.
	 * @deprecated Use static::getList()
	 */
	public static function getCachedTree(string $category, ?string $roleCode = null): ?array
	{
		return static::getList($category, $roleCode);
	}

	public static function getList(string $category, ?string $roleCode = null): ?array
	{
		$result = static::preparePromptCollection(
			self::getByCategory($category, $roleCode)
		);

		return static::sortBySection($result);
	}

	public static function sortBySection(array $result): array
	{
		TypeCollection::sortByColumn($result, 'section');

		return $result;
	}

	public static function preparePromptCollection(Collection $prompts, bool $needSections = true): ?array
	{
		if ($prompts->isEmpty())
		{
			return [];
		}

		$result = [];
		$prevPromptSection = null;

		foreach ($prompts as $prompt)
		{
			$children = [];
			foreach ($prompt->getChildren() as $child)
			{
				$children[] = [
					'code' => $child->getCode(),
					'type' => $prompt->getType(),
					'icon' => $child->getIcon(),
					'title' => $child->getTitle(),
					'text' => $prompt->getText(),
					'required' => [
						'user_message' => $child->isRequiredUserMessage(),
						'context_message' => $child->isRequiredOriginalMessage(),
					],
				];
			}

			if ($needSections && $prompt->getSectionCode() && $prompt->getSectionCode() !== $prevPromptSection)
			{
				$result[] = [
					'separator' => true,
					'title' => $prompt->getSectionTitle(),
					'section' => $prompt->getSectionCode(),
				];
			}

			$result[] = [
				'section' => $prompt->getSectionCode(),
				'code' => $prompt->getCode(),
				'type' => $prompt->getType(),
				'icon' => $prompt->getIcon(),
				'title' => $prompt->getTitle(),
				'text' => $prompt->getText(),
				'isFavorite' => $prompt->isFavorite(),
				'workWithResult' => $prompt->isWorkWithResult(),
				'children' => $children,
				'required' => [
					'user_message' => $prompt->isRequiredUserMessage(),
					'context_message' => $prompt->isRequiredOriginalMessage(),
				],
			];

			$prevPromptSection = $prompt->getSectionCode();
		}

		return $result;
	}

	public static function deleteByFilter(array $filterToDelete): bool
	{
		$result = true;

		$prompts = PromptTable::query()
			->setSelect(['ID'])
			->setFilter($filterToDelete)
		;
		foreach ($prompts->fetchAll() as $prompt)
		{
			$result = self::deleteByFilter(['PARENT_ID' => $prompt['ID']])
				&& PromptTable::delete($prompt['ID'])->isSuccess()
				&& $result;
		}

		return $result;
	}

	public static function getItemFromRawRow(array $data): Item
	{
		return new Item(
			$data['ID'],
			$data['SECTION'],
			$data['SORT'],
			$data['CODE'],
			$data['TYPE'],
			$data['APP_CODE'],
			$data['ICON'],
			$data['PROMPT'],
			$data['TITLE'],
			$data['TEXT_TRANSLATES'],
			$data['SETTINGS'],
			$data['CACHE_CATEGORY'],
			$data['HAS_SYSTEM_CATEGORY'],
			$data['WORK_WITH_RESULT'] === PromptRepository::IS_WORK_WITH_RESULT,
			!empty($data['IS_SYSTEM']) && ($data['IS_SYSTEM'] === PromptRepository::IS_SYSTEM),
			!empty($data['IS_FAVORITE']) && (int)$data['IS_FAVORITE'],
		);
	}

	/**
	 * Returns Prompt's raw tree by category code.
	 *
	 * @param string $code Category code.
	 * @param string|null $roleCode Role code.
	 * @return Collection
	 */
	private static function getByCategory(string $code, ?string $roleCode): Collection
	{
		return new Collection(
			array_values(
				self::getPromptService()->getSystemsPromptsByCategory($code, User::getUserLanguage(), $roleCode)
			)
		);
	}

	/**
	 * Deletes all system prompts from local DB and loads new.
	 *
	 * @return void
	 */
	public static function clearAndRefresh(): void
	{
		Updater::refreshFromRemote();
	}

	private static function getPromptService(): PromptService
	{
		return Container::init()->getItem(PromptService::class);
	}
}
