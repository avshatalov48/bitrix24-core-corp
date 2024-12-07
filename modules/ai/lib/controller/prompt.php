<?php declare(strict_types=1);

namespace Bitrix\AI\Controller;

use Bitrix\AI\Config;
use Bitrix\AI\Context;
use Bitrix\AI\Engine;
use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Facade\User;
use Bitrix\AI\Prompt\Collection;
use Bitrix\AI\Prompt\Manager;
use Bitrix\AI\Role\RoleManager;
use Bitrix\AI\SharePrompt\Enums\Category;
use Bitrix\AI\SharePrompt\Events\EditAnalyticEvent;
use Bitrix\AI\SharePrompt\Events\Enums\Status;
use Bitrix\AI\SharePrompt\Events\SaveAnalyticEvent;
use Bitrix\AI\Exception\ErrorCollectionException;
use Bitrix\AI\Parameter\DefaultParameter;
use Bitrix\AI\SharePrompt\Request\AddInFavoriteListRequest;
use Bitrix\AI\SharePrompt\Request\ChangeSortingInFavoritesRequest;
use Bitrix\AI\SharePrompt\Request\ChangeRequest;
use Bitrix\AI\SharePrompt\Request\CreateRequest;
use Bitrix\AI\SharePrompt\Request\DeleteFromFavoriteListRequest;
use Bitrix\AI\SharePrompt\Request\GetByCategoryRequest;
use Bitrix\AI\SharePrompt\Request\GetPromptDataByCodeRequest;
use Bitrix\AI\SharePrompt\Request\GetTextByCodeRequest;
use Bitrix\AI\SharePrompt\Service\CategoryService;
use Bitrix\AI\SharePrompt\Service\GridPrompt\PartGridPromptService;
use Bitrix\AI\SharePrompt\Service\OwnerOptionService;
use Bitrix\AI\SharePrompt\Service\OwnerService;
use Bitrix\AI\SharePrompt\Service\PromptService;
use Bitrix\AI\SharePrompt\Service\ShareService;
use Bitrix\Main\Engine\Controller;
use Bitrix\Intranet\ActionFilter\IntranetUser;
use Bitrix\Main\Loader;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;

class Prompt extends Controller
{
	public function getDefaultPreFilters(): array
	{
		$filters = parent::getDefaultPreFilters();
		if (Loader::includeModule('intranet'))
		{
			$filters[] = new IntranetUser();
		}

		return $filters;
	}

	/**
	 * Returns a list of prompts
	 *
	 * @param GetByCategoryRequest $request
	 * @param ShareService $shareService
	 * @param OwnerOptionService $ownerOptionService
	 * @return array
	 * @throws \Exception
	 */
	public function getPromptsForUserAction(
		GetByCategoryRequest $request,
		ShareService $shareService,
		OwnerOptionService $ownerOptionService
	): array
	{
		$requestDTO = $request->getData($this->request, $this->getCurrentUser());
		$favoriteDto = $shareService->getAccessiblePrompts(
			$requestDTO->userId,
			$requestDTO->userLang,
			$requestDTO->category
		);

		if ($favoriteDto->needUpdateSortingInFavoriteList)
		{
			$ownerOptionService->updateFavoritesListSorting(
				$requestDTO->userId,
				$favoriteDto->sortingList,
				$favoriteDto->hasRowOption
			);
		}

		$category = 'text';
		$roleCode = User::getLastUsedRoleCode($category, $requestDTO->moduleId);
		$roleManager = new RoleManager($requestDTO->userId, User::getUserLanguage());

		return [
			'promptsFavorite' => Manager::preparePromptCollection(
				new Collection($favoriteDto->favoritePrompts),
				false
			),
			'promptsSystem' => Manager::preparePromptCollection(
				new Collection($favoriteDto->promptsSystem)
			),
			'promptsOther' => Manager::preparePromptCollection(
				new Collection($favoriteDto->promptsUsers)
			),
			'engines' => Engine::getData(
				$category,
				new Context($requestDTO->moduleId,
					$requestDTO->context,
					$requestDTO->userId
				)
			),
			'role' => $roleManager->getRoleByCode($roleCode)
				?? $roleManager->getRoleByCode($roleManager::getUniversalRoleCode()),
			'permissions' => [
				'can_edit_settings' => User::isAdmin(),
			],
			'first_launch' => Config::getPersonalValue('first_launch') !== 'N' && Bitrix24::shouldUseB24(),
		];
	}

	/**
	 * Creating a prompt and sharing it for users
	 *
	 * @param CreateRequest $request
	 * @param PromptService $promptService
	 * @param ShareService $shareService
	 * @return array
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function createAction(
		CreateRequest $request,
		PromptService $promptService,
		ShareService $shareService
	): array
	{
		$requestDTO = $request->getData($this->request, $this->getCurrentUser());
		$promptCreateResult = $promptService->createPrompt($requestDTO);
		$event = new SaveAnalyticEvent(
			$requestDTO->analyticCategoryData,
			$requestDTO->shareType
		);

		if (!$promptCreateResult->isSuccess())
		{
			$event->send(Status::ERROR);
			$this->addErrors($promptCreateResult->getErrors());

			return [];
		}

		$requestDTO->promptId = $promptCreateResult->getId();

		$shareCreateResult = $shareService->create($requestDTO);
		if (!$shareCreateResult->isSuccess())
		{
			$event->send(Status::ERROR);
			$this->addErrors($shareCreateResult->getErrors());

			return [];
		}

		$event->send(Status::SUCCESS);

		return [
			"code" => $requestDTO->promptCode
		];
	}

	/**
	 * Editing a prompt and sharing it for users
	 *
	 * @param ChangeRequest $request
	 * @param PromptService $promptService
	 * @param ShareService $shareService
	 * @param OwnerService $ownerService
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function changeAction(
		ChangeRequest $request,
		PromptService $promptService,
		ShareService $shareService,
		OwnerService $ownerService
	): void
	{
		$requestDTO = $request->getData($this->request, $this->getCurrentUser());
		$updateResult = $promptService->savePrompt($requestDTO);
		$event = new EditAnalyticEvent(Category::LIST, $requestDTO->shareType);

		if (!$updateResult->isSuccess())
		{
			$event->send(Status::ERROR);

			return;
		}

		$shareService->deleteSharingForChange($requestDTO->promptId);
		$createResult = $shareService->create($requestDTO);

		if (!$createResult->isSuccess())
		{
			$event->send(Status::ERROR);

			return;
		}

		$ownerService->unsetDeletedFlagsForUsers($requestDTO->usersIdsInAccessCodes, $requestDTO->promptId);

		$event->send(Status::SUCCESS);
	}

	/**
	 * Returns prompt by code for update
	 *
	 * @param GetPromptDataByCodeRequest $request
	 * @param PromptService $promptService
	 * @param CategoryService $categoryService
	 * @throws \Exception
	 */
	public function getPromptByCodeForUpdateAction(
		GetPromptDataByCodeRequest $request,
		PromptService $promptService,
		CategoryService $categoryService
	): array
	{
		$requestDTO = $request->getData($this->request, $this->getCurrentUser());
		$categoriesListWithTranslations = $categoryService->getCategoryListWithTranslations();

		return [
			'prompt' => $promptService->getPromptByIdForUpdate(
				$requestDTO->promptId,
				$requestDTO->userId,
				array_map(fn($item) => $item['code'], $categoriesListWithTranslations)
			),
			'categoriesListWithTranslations' => $categoriesListWithTranslations
		];
	}

	/**
	 * Change orders in favorite list
	 *
	 * @param ChangeSortingInFavoritesRequest $request
	 * @param OwnerOptionService $ownerOptionService
	 * @throws \Exception
	 */
	public function changeOrdersInFavoriteListAction(
		ChangeSortingInFavoritesRequest $request,
		OwnerOptionService $ownerOptionService
	): void
	{
		$requestDTO = $request->getData($this->request, $this->getCurrentUser());
		$ownerOptionService->updateFavoriteListSortingForce($requestDTO->userId, $requestDTO->promptIds);
	}

	/**
	 * Add prompt in favorite list
	 *
	 * @param AddInFavoriteListRequest $request
	 * @param ShareService $shareService
	 * @throws \Exception
	 */
	public function addInFavoriteListAction(
		AddInFavoriteListRequest $request,
		ShareService $shareService
	): void
	{
		$requestDTO = $request->getData($this->request, $this->getCurrentUser());
		$shareService->addInFavoriteList($requestDTO->userId, $requestDTO->promptId);
	}

	/**
	 * Delete prompt from favorite list
	 *
	 * @param DeleteFromFavoriteListRequest $request
	 * @param ShareService $shareService
	 * @throws \Exception
	 */
	public function deleteFromFavoriteListAction(
		DeleteFromFavoriteListRequest $request,
		ShareService $shareService
	): void
	{
		$requestDTO = $request->getData($this->request, $this->getCurrentUser());
		$shareService->deleteInFavoriteList($requestDTO->userId, $requestDTO->promptId);
	}

	/**
	 * Returned categories list with translations
	 *
	 * @param CategoryService $categoryService
	 * @return array
	 */
	public function getCategoriesListWithTranslationsAction(CategoryService $categoryService): array
	{
		return [
			'list' => $categoryService->getCategoryListWithTranslations()
		];
	}

	/**
	 * Returned text prompt by code
	 *
	 * @param GetTextByCodeRequest $request
	 * @return array
	 * @throws \Exception
	 */
	public function getTextByCodeAction(GetTextByCodeRequest $request): array
	{
		$requestDTO = $request->getData($this->request, $this->getCurrentUser());

		return [
			'text' => $requestDTO->text
		];
	}

	/**
	 * Returned shares for prompt by prompt code
	 *
	 * @param GetPromptDataByCodeRequest $request
	 * @param PartGridPromptService $partGridPromptService
	 * @return array
	 * @throws \Exception
	 */
	public function getShareForPromptAction(
		GetPromptDataByCodeRequest $request,
		PartGridPromptService $partGridPromptService
	): array
	{
		$requestDTO = $request->getData($this->request, $this->getCurrentUser());

		return [
			'list' => $partGridPromptService->getShare($requestDTO->promptId, $requestDTO->userId)
		];
	}

	/**
	 * Returned prompt data by code
	 *
	 * @param GetPromptDataByCodeRequest $request
	 * @param CategoryService $categoryService
	 * @return array
	 * @throws \Exception
	 */
	public function getCategoriesForPromptAction(
		GetPromptDataByCodeRequest $request,
		CategoryService $categoryService
	): array
	{
		$requestDTO = $request->getData($this->request, $this->getCurrentUser());

		return [
			'list' => $categoryService->getByPromptId($requestDTO->promptId)
		];
	}

	public function getAutoWiredParameters(): array
	{
		return [
			new DefaultParameter()
		];
	}

	protected function runProcessingThrowable(\Throwable $throwable): void
	{
		if ($throwable instanceof ErrorCollectionException)
		{
			foreach ($throwable->getCollection() as $error)
			{
				$this->errorCollection[] = $error;
			}

			return;
		}

		parent::runProcessingThrowable($throwable);
	}
}
