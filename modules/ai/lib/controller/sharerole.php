<?php declare(strict_types=1);

namespace Bitrix\AI\Controller;

use Bitrix\AI\Exception\ErrorCollectionException;
use Bitrix\AI\Parameter\DefaultParameter;
use Bitrix\AI\ShareRole\Service\GridRole\PartGridRoleService;
use Bitrix\AI\ShareRole\Events\EditAnalyticEvent;
use Bitrix\AI\ShareRole\Events\Enums\Status;
use Bitrix\AI\ShareRole\Events\SaveAnalyticEvent;
use Bitrix\AI\ShareRole\Request\ChangeRequest;
use Bitrix\AI\ShareRole\Request\GetRoleDataByCodeRequest;
use Bitrix\Intranet\ActionFilter\IntranetUser;
use Bitrix\AI\ShareRole\Request\CreateRequest;
use Bitrix\AI\ShareRole\Service\RoleService;
use Bitrix\AI\ShareRole\Service\ShareService;
use Bitrix\AI\ShareRole\Service\OwnerService;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\Response\BFile;
use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Controller;

class ShareRole extends Controller
{
	public function configureActions(): array
	{
		return [
			'showAvatar' => [
				'-prefilters' => [Csrf::class],
			],
		];
	}

	public function getAutoWiredParameters(): array
	{
		return [
			new DefaultParameter()
		];
	}

	public function getDefaultPreFilters(): array
	{
		$filters = parent::getDefaultPreFilters();
		if (Loader::includeModule('intranet'))
		{
			$filters[] = new IntranetUser();
		}

		return $filters;
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

	public function createAction(
		CreateRequest $request,
		RoleService $roleService,
		ShareService $shareService,
	): array
	{
		$requestDTO = $request->getData($this->request, $this->getCurrentUser());
		$roleCreateResult = $roleService->createRole($requestDTO);
		$event = new SaveAnalyticEvent($requestDTO->shareType);

		if (!$roleCreateResult->isSuccess())
		{
			$event->send(Status::Error);
			$this->addErrors($roleCreateResult->getErrors());
			return [];
		}

		$requestDTO->roleId = $roleCreateResult->getId();

		$shareCreateResult = $shareService->create($requestDTO);

		if (!$shareCreateResult->isSuccess())
		{
			$event->send(Status::Error);
			$this->addErrors($roleCreateResult->getErrors());
			return [];
		}

		$event->send(Status::Success);

		return [
			'code' => $requestDTO->roleCode,
		];
	}

	public function changeAction(
		ChangeRequest $request,
		RoleService $roleService,
		ShareService $shareService,
		OwnerService $ownerService,
	): void
	{
		$requestDTO = $request->getData($this->request, $this->getCurrentUser());
		$updateResult = $roleService->saveRole($requestDTO);
		$event = new EditAnalyticEvent($requestDTO->shareType);

		if (!$updateResult->isSuccess())
		{
			$event->send(Status::Error);

			return;
		}

		$shareService->deleteSharingForChange($requestDTO->roleId);
		$createResult = $shareService->create($requestDTO);

		if (!$createResult->isSuccess())
		{
			$event->send(Status::Error);

			return;
		}

		$event->send(Status::Success);

//		$ownerService->unsetDeletedFlagsForUsers($requestDTO->usersIdsInAccessCodes, $requestDTO->roleId);
	}


	public function getRoleByCodeForUpdateAction(
		GetRoleDataByCodeRequest $request,
		RoleService $roleService,
	): array
	{
		$requestDTO = $request->getData($this->request, $this->getCurrentUser());

		return [
			'role' => $roleService->getRoleByIdForUpdate($requestDTO->roleId),
		];
	}

	/**
	 * Returned shares for role by role code
	 *
	 * @param GetRoleDataByCodeRequest $request
	 * @param PartGridRoleService $partGridRoleService
	 * @return array
	 * @throws \Exception
	 */
	public function getShareForRoleAction(
		GetRoleDataByCodeRequest $request,
		PartGridRoleService $partGridRoleService
	): array
	{
		$requestDTO = $request->getData($this->request, $this->getCurrentUser());

		return [
			'list' => $partGridRoleService->getShare($requestDTO->roleId, $requestDTO->userId)
		];
	}

	public function showAvatarAction(
		RoleService $roleService,
		int $roleId,
	): BFile
	{
		$fileId = $roleService->getAvatarIdByRoleId($roleId);

		return BFile::createByFileId($fileId)->showInline(true);
	}
}
