<?php

namespace Bitrix\Sign\Service\Integration\Crm;

use Bitrix\Crm\Service\Container;
use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Model\FileTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Sign\Access;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Blank;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Document\Entity\Smart;
use Bitrix\Sign\File;
use Bitrix\Sign\Model\DocumentGeneratorBlankTemplate;
use Bitrix\Sign\Model\SignDocumentGeneratorBlank;
use Bitrix\Sign\Model\SignDocumentGeneratorBlankTable;
use Bitrix\Sign\Operation\CloneBlankForDocument;
use Bitrix\Sign\Repository\BlankRepository;
use Bitrix\Sign\Service\Sign;
use Bitrix\Sign\Type\Member\EntityType;

Loc::loadMessages(__FILE__);
class BaseDocumentService implements DocumentService
{
	private BlankRepository $blankRepository;

	public function __construct(
		private Sign\DocumentService $signDocumentService,
		private Sign\BlankService $blankService,
		private Sign\MemberService $memberService,
		?BlankRepository $blankRepository = null,
	)
	{
		$container = \Bitrix\Sign\Service\Container::instance();
		$this->blankRepository = $blankRepository ?? $container->getBlankRepository();
	}

	private const DEFAULT_CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

	/**
	 * @param int $templateId
	 * @return mixed
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getLinkedBlankForDocumentGeneratorTemplate(int $templateId)
	{
		/**
		 * @var SignDocumentGeneratorBlank $templateBlank
		 */
		$templateBlank = SignDocumentGeneratorBlankTable::query()
			->setSelect(['BLANK_ID', 'CREATED_AT', 'INITIATOR', 'ID',])
			->where('DOCUMENT_GENERATOR_TEMPLATE_ID', $templateId)
			->fetchObject()
		;
		if (!$templateBlank)
		{
			return [];
		}

		$blankId = $templateBlank->getBlankId();
		$blank = $this->blankRepository->getById($blankId);
		if ($blank === null)
		{
			return [];
		}

		$hasAccessToUseTemplate = (new AccessController(CurrentUser::get()->getId()))
			->checkByItem(ActionDictionary::ACTION_USE_TEMPLATE, $blank)
		;
		if (!$hasAccessToUseTemplate)
		{
			return [];
		}

		$lastDocument = $this->getLastDocumentByBlank($templateBlank->getBlankId());
		if (!$lastDocument)
		{
			return [];
		}

		return [
			'ID' => $templateBlank->getId(),
			'INITIATOR' => $lastDocument->getMeta()['initiatorName'] ?? '',
			'CREATED_AT' => $templateBlank->getCreatedAt(),
			'BLANK_ID' => $templateBlank->getBlankId(),
			'TITLE' => $lastDocument?->getTitle()
		];
	}

	public function createSignDocumentFromDealDocument(
		int $fileId,
		Document $document,
		int $smartDocumentId,
		bool $isNew = false
	): Result
	{
		if (
			!Loader::includeModule('documentgenerator')
			|| !Loader::includeModule('crm')
		)
		{
			return (new Result())->addError(new Error(Loc::getMessage('SIGN_INTEGRATION_NO_MODULE') ?? ''));
		}

		$cFile = \CFile::GetByID(FileTable::getBFileId($fileId))->fetch();
		$type = $cFile['CONTENT_TYPE'] ?? self::DEFAULT_CONTENT_TYPE;

		if ($isNew && Storage::instance()->isNewSignEnabled())
		{
			$createBlankResult = $this->blankService->createFromFileIds([$cFile['ID']]);
			if (!$createBlankResult->isSuccess())
			{
				return $createBlankResult;
			}
			$blankId = $createBlankResult->getId();
			$smartDocument = Container::getInstance()
				->getFactory(\CCrmOwnerType::SmartDocument)
				->getItem($smartDocumentId);

			$createDocumentResult = $this->signDocumentService->register(
				blankId: $blankId,
				title: $smartDocument->getTitle(),
				entityId: $smartDocumentId,
				entityType: 'SMART',
			);

			if (!$createDocumentResult->isSuccess())
			{
				return $createDocumentResult;
			}

			$uploadDocumentResult = $this->signDocumentService->upload($createDocumentResult->getData()['document']->uid);
			if (!$uploadDocumentResult->isSuccess())
			{
				return $uploadDocumentResult;
			}

			SignDocumentGeneratorBlankTable::linkToSignBlank(
				new DocumentGeneratorBlankTemplate(
					$blankId,
					$document->TEMPLATE_ID
				)
			);

			return (new Result());
		}

		$file = new File([
			'name' => $cFile['ORIGINAL_NAME'] ?? $document->getFileName(),
			'size' => FileTable::getSize($fileId),
			'content' => base64_encode(FileTable::getContent($fileId)),
			'type' => $type,
		]);

		$blank = Blank::createFromFile($file);

		$signDocument = $blank->createDocument('SMART', $smartDocumentId);
		if (!$signDocument)
		{
			return (new Result())->addError(new Error('Error creating document'));
		}

		$signDocument->register(true);

		SignDocumentGeneratorBlankTable::linkToSignBlank(
			new DocumentGeneratorBlankTemplate(
				$blank->getId(),
				$document->TEMPLATE_ID
			)
		);

		return (new Result());
	}

	/**
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function createSignDocumentFromBlank(
		int $fileId,
		\Bitrix\Sign\Blank|int $oldBlankId,
		Document $document,
		int $smartDocumentId
	): Result
	{
		$result = new Result();
		if (
			!Loader::includeModule('documentgenerator')
			|| !Loader::includeModule('crm')
		)
		{
			return $result->addError(new Error(Loc::getMessage('SIGN_INTEGRATION_NO_MODULE') ?? ''));
		}
		$oldBlank = is_int($oldBlankId) ? Blank::getById($oldBlankId) : $oldBlankId;

		if (is_int($oldBlankId) && Storage::instance()->isNewSignEnabled())
		{
			return $this->createNewSigningDocumentWithCopyingToTheNew($oldBlank->getId(), $smartDocumentId, $document->TEMPLATE_ID,);
		}

		$blank = $oldBlank->copyBlank();
		$cFile = \CFile::GetByID(FileTable::getBFileId($fileId))->fetch();
		$type = $cFile['CONTENT_TYPE'] ?? self::DEFAULT_CONTENT_TYPE;

		$file = new File([
			'name' => $cFile['ORIGINAL_NAME'] ?? $document->getFileName(),
			'size' => FileTable::getSize($fileId),
			'content' => base64_encode(FileTable::getContent($fileId)),
			'type' => $type,
		]);

		$preparedFileResult = Blank::prepareFile($file);

		$lastDocument = $this->getLastDocumentByBlank($oldBlank->getId());

		if ($lastDocument && $preparedFileResult->isSuccess())
		{
			$data = $blank->getData();
			$data['FILE_ID'] = $file->getId();
			$blank->setData($data);

			$signDocument = $blank->createDocument('SMART', $smartDocumentId);
			$blank->setDocument($signDocument);

			$oldSmartDocument = Container::getInstance()
				->getFactory(\CCrmOwnerType::SmartDocument)
				->getItem($lastDocument->getEntityId());

			$smartDocument = Container::getInstance()
				->getFactory(\CCrmOwnerType::SmartDocument)
				->getItem($smartDocumentId);

			if ($oldSmartDocument && $oldSmartDocument->getMycompanyId())
			{
				$smartDocument->setMycompanyId($oldSmartDocument->getMycompanyId());
				$smartDocument->save();
				$signDocument->setEntity((new Smart())->setItem($smartDocument));
			}

			$signDocument->register(true);
			$assignResult = $signDocument->assignMembers();
			$members = $signDocument->getMembers();
			foreach ($members as $member)
			{
				if (is_null($member->getCommunicationType()) || is_null($member->getCommunicationValue()))
				{
					$smartDocument->delete();
					$signDocument->rollback();
					return $result->addError(
						new \Bitrix\Main\Error(
							Loc::getMessage(
								$member->isInitiator()
									? 'SIGN_INTEGRATION_DOCUMENT_ERROR_MEMBERS_NO_COMMUNICATION_YOUR_SIDE'
									: 'SIGN_INTEGRATION_DOCUMENT_ERROR_MEMBERS_NO_COMMUNICATION_OTHER_SIDE'
							),
							'MEMBER_NOT_HAVE_COMMUNICATION'
						)
					);
				}
			}
			
			if (!$assignResult->isSuccess())
			{
				$smartDocument->delete();
				$signDocument->rollback();
				return $assignResult;
			}
			
			$updateResult = $blank->updateBlocks();
			if (!$updateResult->isSuccess())
			{
				$smartDocument->delete();
				$signDocument->rollback();
				return $updateResult;
			}
			
			$initiator = isset($lastDocument->getMeta()['initiatorName'])
				? ($lastDocument->getMeta()['initiatorName'] ?: '')
				: '';

			SignDocumentGeneratorBlankTable::linkToSignBlank(
				new DocumentGeneratorBlankTemplate(
					$blank->getId(),
					$document->TEMPLATE_ID,
					$initiator
				)
			);

			return $result->setData(['signDocument' => $signDocument]);
		}

		return $preparedFileResult;
	}

	/**
	 * Get last sign document by blank id
	 *
	 * @param int $blankId
	 * @return \Bitrix\Sign\Document|null
	 */
	public function getLastDocumentByBlank(int $blankId): ?\Bitrix\Sign\Document
	{
		$lastDocumentRow = \Bitrix\Sign\Document::getList([
			'select' => ['ID', ],
			'filter' => ['=BLANK_ID' => $blankId,],
			'limit' => 1,
			'order' => ['ID' => 'asc',]
		])->fetch();

		if ($lastDocumentRow)
		{
			$lastDocument = \Bitrix\Sign\Document::getById($lastDocumentRow['ID']);
		}

		return $lastDocument ?? null;
	}

	public function configureMembers(\Bitrix\Sign\Item\Document $document)
	{
		$smartDocument = Container::getInstance()
			->getFactory(\CCrmOwnerType::SmartDocument)
			->clearCategoriesCache()
			->clearUserFieldsInfoCache()
			->clearFieldsCollectionCache()
			->getItem($document->entityId)
		;

		$companyId = $smartDocument->getMycompanyId();

		$this->memberService->addForDocument($document->uid, EntityType::COMPANY, $companyId, 1);
		$contactIds = $smartDocument->getContactIds();

		foreach ($contactIds as $contactId)
		{
			$this->memberService->addForDocument($document->uid, EntityType::CONTACT, $contactId, 2);
		}
	}

	/**
	 * @param int $oldBlankId
	 * @param int $smartDocumentId
	 * @param int $templateId
	 *
	 * @return \Bitrix\Main\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function createNewSigningDocumentWithCopyingToTheNew(int $oldBlankId, int $smartDocumentId, int $templateId): Result
	{
		$lastDocument = $this->signDocumentService->getLastByBlankId($oldBlankId);
		$oldSmartDocument = $lastDocument?->entityId ? Container::getInstance()
			->getFactory(\CCrmOwnerType::SmartDocument)
			->getItem($lastDocument->entityId) : null;

		$smartDocument = Container::getInstance()
			->getFactory(\CCrmOwnerType::SmartDocument)
			->getItem($smartDocumentId);

		if ($oldSmartDocument && $oldSmartDocument->getMycompanyId())
		{
			$smartDocument->setMycompanyId($oldSmartDocument->getMycompanyId());
			$smartDocument->save();
		}

		$createDocumentResult = $this->signDocumentService->register(
			$oldBlankId,
			$smartDocument->getTitle(),
			$smartDocumentId,
			\Bitrix\Sign\Type\Document\EntityType::SMART
		);

		if (!$createDocumentResult->isSuccess())
		{
			return $createDocumentResult;
		}

		$document = $createDocumentResult->getData()['document'];
		$cloneBlankResult = (new CloneBlankForDocument($document))->launch();

		if (!$cloneBlankResult->isSuccess())
		{
			return $cloneBlankResult;
		}

		$result = $this->signDocumentService->upload($document->uid);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$this->configureMembers($document);

		SignDocumentGeneratorBlankTable::linkToSignBlank(
			new DocumentGeneratorBlankTemplate(
				$document->blankId,
				$templateId
			)
		);

		return $result->setData([
			'newSign' => true,
			'signDocument' => $document,
		]);
	}
}
