<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Controller\Response\PreviewResponseBuilder;
use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Security\ParameterSigner;
use Bitrix\Main;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\PageNavigation;

class TrackedObject extends BaseObject
{
	public function configureActions()
	{
		$configureActions = parent::configureActions();
		$configureActions['download'] = [
			'-prefilters' => [
				Main\Engine\ActionFilter\Csrf::class,
				Authentication::class,
			],
			'+prefilters' => [
				new Authentication(true),
				new Main\Engine\ActionFilter\CloseSession(),
			]
		];
		$configureActions['showPreview'] = [
			'-prefilters' => [
				Main\Engine\ActionFilter\Csrf::class,
				Authentication::class,
			],
			'+prefilters' => [
				new Authentication(true),
				new Engine\ActionFilter\CheckImageSignature(idExtractor: function ($arguments) {
					foreach ($arguments as $argument)
					{
						if ($argument instanceof Disk\Document\TrackedObject)
						{
							return $argument->getId();
						}
					}
					return null;
				}),
				new Main\Engine\ActionFilter\CloseSession(),
			]
		];

		return $configureActions;
	}

	public function getPrimaryAutoWiredParameter()
	{
		return new ExactParameter(Disk\Document\TrackedObject::class, 'object', function ($className, $id) {
			return Disk\Document\TrackedObject::loadById($id);
		});
	}

	/**
	 * Get tracked object.
	 * @param Disk\Document\TrackedObject $object Tracked object.
	 * @param CurrentUser $currentUser Current user.
	 * @param bool $showRights Show rights for current user.
	 * @return array
	 */
	public function getAction(
		Disk\Document\TrackedObject $object,
		CurrentUser $currentUser,
		bool $showRights = false
	): array
	{
		$rights = null;
		if ($showRights)
		{
			$rightsManager = Disk\Driver::getInstance()->getRightsManager();
			$rights = $rightsManager->getAvailableActionsByTrackedObject($object, $currentUser->getId());
		}

		return $this->exportToArray($object, $rights);
	}

	private function exportToArray(
		Disk\Document\TrackedObject $object,
		?array $rights = null,
	): array
	{
		$links = [
			/** @see TrackedObject::downloadAction */
			'download' => $this->getActionUri('download', ['id' => $object->getId()]),
			/** @see TrackedObject::showPreviewAction */
			'preview' => $this->getActionUri('showPreview', [
				'id' => $object->getId(),
				'humanRE' => 1,
				'width' => 640,
				'height' => 640,
				'signature' => ParameterSigner::getImageSignature($object->getId(), 640, 640),
			]),
		];

		if ($rights !== null)
		{
			return [
				'trackedObject' => [
					'id' => (int)$object->getId(),
					'updateTime' => $object->getUpdateTime(),
					'rights' => $rights,
					'links' => $links,
					'file' => [
						...$object->getFile()->jsonSerialize(),
					],
				],
			];
		}

		return [
			'trackedObject' => [
				'id' => (int)$object->getId(),
				'updateTime' => $object->getUpdateTime(),
				'file' => $object->getFile()->jsonSerialize(),
				'links' => $links,
			],
		];
	}

	public function getByIdsAction(
		Disk\Type\TrackedObjectCollection $trackedObjectCollection,
		CurrentUser $currentUser,
		bool $showRights = false
	): array
	{
		$items = [];
		foreach ($trackedObjectCollection as $trackedObject)
		{
			$rights = null;
			if ($showRights)
			{
				$rightsManager = Disk\Driver::getInstance()->getRightsManager();
				$rights = $rightsManager->getAvailableActionsByTrackedObject($trackedObject, $currentUser->getId());
			}

			$items[] = $this->exportToArray($trackedObject, $rights);
		}

		return [
			'items' => $items,
		];
	}

	/**
	 * List of tracked objects.
	 * Show only files which are tracked by user.
	 * @param CurrentUser $currentUser
	 * @param PageNavigation $pageNavigation
	 * @param bool $showRights
	 * @param string|null $search
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public function listAction(
		Main\Engine\CurrentUser $currentUser,
		PageNavigation $pageNavigation,
		bool $showRights = false,
		string $search = null
	): ?array
	{
		$limit = $pageNavigation?->getLimit() ?: 50;
		$offset = $pageNavigation?->getOffset() ?: 0;

		$rightsManager = Disk\Driver::getInstance()->getRightsManager();
		$storage = Disk\Driver::getInstance()->getStorageByUserId($currentUser->getId());
		$securityContext = $storage?->getSecurityContext($currentUser->getId());

		if (!$securityContext)
		{
			$this->addError(new Error('Could not find storage for current user.'));

			return null;
		}

		$search = $search ?: '';
		$searchFilterBuilder = new Disk\Search\SearchFilterBuilder();
		$filter = $searchFilterBuilder->buildFilter($search);

		$filter['=TRACKED_OBJECT.USER_ID'] = $currentUser->getId();
		$filter['=DELETED_TYPE'] = Disk\Internals\ObjectTable::DELETED_TYPE_NONE;
		$filter['=TYPE'] = Disk\Internals\ObjectTable::TYPE_FILE;

		$idParameters = [
			'select' => ['ID', 'TRACKED_OBJECT_ID' => 'TRACKED_OBJECT.ID'],
			'filter' => $filter,
			'order' => ['TRACKED_OBJECT.UPDATE_TIME' => 'DESC'],
			'limit' => $limit,
			'offset' => $offset,
		];

		$fileToTrackObjectIds = [];
		$fileIds = [];
		$fileResult = Disk\File::getList($idParameters);
		foreach ($fileResult as $row)
		{
			$fileIds[] = (int)$row['ID'];
			$fileToTrackObjectIds[(int)$row['ID']] = (int)$row['TRACKED_OBJECT_ID'];
		}

		if (empty($fileIds))
		{
			return ['items' => []];
		}

		$dataParameters = [
			'select' => [
				'*',
			],
			'filter' => ['@ID' => $fileIds],
		];

		$items = array_flip($fileIds);
		$fileDataResult = Disk\File::getList($dataParameters);
		foreach ($fileDataResult as $row)
		{
			/** @var Disk\File $model */
			$model = Disk\File::buildFromRow($row);

			$modelId = (int)$model->getId();
			$trackedObjectId = $fileToTrackObjectIds[$modelId];
			$file = $model->jsonSerialize();

			$items[$modelId] = [
				'trackedObject' => [
					'id' => $trackedObjectId,
					'links' => [
						/** @see TrackedObject::downloadAction */
						'download' => $this->getActionUri('download', ['id' => $trackedObjectId]),
						/** @see \Bitrix\Disk\Controller\File::showPreviewAction() */
						/** @see \Bitrix\Disk\Controller\File::showImageAction() */
						'preview' => $file['links']['preview'] ?? null,
					],
					'file' => $file,
				],
			];

			if ($showRights)
			{
				$items[$modelId]['trackedObject']['rights'] = $rightsManager->getAvailableActions($model, $securityContext);
			}
		}

		return [
			'items' => array_values($items),
		];
	}

	public function renameAction(Disk\Document\TrackedObject $object, $newName, $autoCorrect = false)
	{
		return $this->rename($object->getFile(), $newName, $autoCorrect);
	}

	public function generateExternalLinkAction(Disk\Document\TrackedObject $object)
	{
		if (!$this->checkExternalLinkFeature($object->getFile()))
		{
			$this->addError(new Error('Could not generate external link. Feature is disabled by tarif.'));

			return null;
		}

		return $this->generateExternalLink($object->getFile());
	}

	public function disableExternalLinkAction(Disk\Document\TrackedObject $object)
	{
		return $this->disableExternalLink($object->getFile());
	}

	public function getExternalLinkAction(Disk\Document\TrackedObject $object)
	{
		return $this->getExternalLink($object->getFile());
	}

	public function downloadAction(Disk\Document\TrackedObject $object)
	{
		$response = Response\BFile::createByFileId($object->getFile()->getFileId(), $object->getFile()->getName());
		$response->setCacheTime(Disk\Configuration::DEFAULT_CACHE_TIME);

		return $response;
	}

	public function showPreviewAction(Disk\Document\TrackedObject $object, int $width = 0, int $height = 0, string $exact = null): ?Main\Response
	{
		return (new PreviewResponseBuilder())->createByFile($object->getFile(), $width, $height, $exact);
	}
}