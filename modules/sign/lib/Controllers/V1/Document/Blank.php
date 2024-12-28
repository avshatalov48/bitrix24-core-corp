<?php

namespace Bitrix\Sign\Controllers\V1\Document;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Request;
use Bitrix\Main\SystemException;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Upload\BlankUploadController;
use Bitrix\Sign\Type;
use Bitrix\Sign\Item;
use Bitrix\Sign\Util\Query\Db\Paginator;

class Blank extends \Bitrix\Sign\Engine\Controller
{
	public function __construct(Request $request = null)
	{
		parent::__construct($request);
		Loader::includeModule('ui');
	}

	/**
	 * @param array $files
	 * @param string $scenario
	 *
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function createAction(
		array $files,
		?string $scenario = null,
		bool $forTemplate = false,
	): array
	{
		/** @var array<int> $fileIds */
		$fileIds = [];
		foreach ($files as $fileId)
		{
			if (!is_string($fileId))
			{
				$this->addError(new Error("Invalid file id"));

				return [];
			}

			$fileController = new BlankUploadController([]);
			$uploader = new \Bitrix\UI\FileUploader\Uploader($fileController);
			$pendingFiles = $uploader->getPendingFiles([$fileId]);
			$pendingFiles->makePersistent();
			$file = $pendingFiles->get($fileId);
			$persistentFileId = $file?->getFileId();

			if ($persistentFileId === null)
			{
				$this->addError(new Error("Invalid file id"));

				return [];
			}
			$fileIds[] = $persistentFileId;
		}
		$scenario ??= Type\BlankScenario::B2B;
		$result = Container::instance()->getSignBlankService()->createFromFileIds($fileIds, $scenario, $forTemplate);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return [];
		}

		$item = [
			'id' => $result->getId(),
			'userAvatarUrl' => null,
			'userName' => null,
		];

		$userId = CurrentUser::get()->getId();
		if ($userId !== null)
		{
			$user = Container::instance()->getUserService()->getUserById($userId);
			if ($user !== null)
			{
				$item['userAvatarUrl'] = Container::instance()->getUserService()->getUserAvatar($user);
				$item['userName'] = Container::instance()->getUserService()->getUserName($user);
			}
		}

		return $item;
	}

	/**
	 * @param int $blankId
	 *
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function loadAction(int $blankId): array
	{
		$blank = Container::instance()
			->getBlankRepository()
			->getByIdAndValidatePermissions($blankId)
		;

		if (!$blank)
		{
			return [];
		}

		return get_object_vars($blank);
	}

	/**
	 * @param int $countPerPage
	 * @param int $page
	 * @param ?string $scenario
	 *
	 * @return array
	 */
	public function listAction(
		int $countPerPage = 10,
		int $page = 1,
		?string $scenario = null
	): array
	{
		$scenario ??= Type\BlankScenario::B2B;
		if (!in_array($scenario, Type\BlankScenario::getAll(), true))
		{
			$this->addError(new Error('Wrong blank scenario'));
			return [];
		}

		if ($countPerPage <= 0)
		{
			$this->addError(new Error('Blanks count must be greater than 0. Now: '.$countPerPage));

			return [];
		}
		if ($page <= 0)
		{
			$this->addError(new Error('Blanks page must be greater than 0. Now: '.$page));

			return [];
		}

		[$limit, $offset] = Paginator::getLimitAndOffset($countPerPage, $page);

		$data = Container::instance()
			->getBlankRepository()
			->getPublicList($limit, $offset, $scenario)
			->toArray();

		return array_map(
			function (Item\Blank $blank): array {
				$item = (array)$blank;
				$item['previewUrl'] = null;
				$item['userAvatarUrl'] = null;
				$item['userName'] = null;
				if ($blank->id !== null)
				{
					$resource = Container::instance()->getBlankResourceRepository()->getFirstByBlankId($blank->id);
					if ($resource !== null)
					{
						$item['previewUrl'] = Container::instance()->getFileRepository()->getFileSrc($resource->fileId);
					}
				}
				if ($blank->createdById !== null)
				{
					$user = Container::instance()->getUserService()->getUserById($blank->createdById);
					if ($user !== null)
					{
						$item['userAvatarUrl'] = Container::instance()->getUserService()->getUserAvatar($user);
						$item['userName'] = Container::instance()->getUserService()->getUserName($user);
					}
				}

				return $item;
			},
			$data
		);
	}

	public function getByIdAction(
		int $id,
	): array
	{
		$container = Container::instance();
		$blankRepository = $container->getBlankRepository();
		$blank = $blankRepository->getByIdAndValidatePermissions($id);
		if ($blank === null)
		{
			$blank = $blankRepository->getById($id);
			if ($blank === null || !$this->isUserHasAccessToLinkedDocuments($blank))
			{
				$this->addError(new Error("Blank with id `$id` doesnt exist", "DOESNT_EXIST"));

				return [];
			}
		}
		$result = [
			'id' => $blank->id,
			'title' => $blank->title,
			'status' => $blank->status,
			'userAvatarUrl' => null,
			'userName' => null,
			'previewUrl' => null,
			'dateCreate' => $blank->dateCreate
		];
		$resource = $container->getBlankResourceRepository()->getFirstByBlankId($blank->id);
		if ($resource !== null)
		{
			$result['previewUrl'] = $container->getFileRepository()->getFileSrc($resource->fileId);
		}
		if ($blank->createdById !== null)
		{
			$user = $container->getUserService()->getUserById($blank->createdById);
			if ($user !== null)
			{
				$result['userAvatarUrl'] = $container->getUserService()->getUserAvatar($user);
				$result['userName'] = $container->getUserService()->getUserName($user);
			}
		}

		return $result;
	}

	private function isUserHasAccessToLinkedDocuments(Item\Blank $blank): bool
	{
		$accessController = $this->getAccessController();
		$blankDocuments = $this->container->getDocumentRepository()->listByBlankId($blank->id);

		$documentNotFinalizedAndHasAccessToIt = fn(Item\Document $document) =>
			!Type\DocumentStatus::isFinalByDocument($document)
			&& $accessController->checkByItem(
				$blank->scenario === Type\BlankScenario::B2E
					? ActionDictionary::ACTION_B2E_DOCUMENT_READ
					: ActionDictionary::ACTION_DOCUMENT_READ,
				$document,
			)
		;

		return $blankDocuments->any($documentNotFinalizedAndHasAccessToIt);
	}
}
