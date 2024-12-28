<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main\Error;
use Bitrix\Main;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Helper\Field\NameHelper;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Document\Template;
use Bitrix\Sign\Item\Field;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Operation\Result\ConfigureResult;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Result\CreateDocumentResult;
use Bitrix\Sign\Result\Operation\Document\Template\SendResult;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Providers\MemberDynamicFieldInfoProvider;
use Bitrix\Sign\Service\Providers\ProfileProvider;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\Member\EntityType;
use Bitrix\Sign\Type\Member\Role;

final class Send implements Contract\Operation
{
	private const TRIES_TO_CONFIGURE_AND_START_SIGNING = 10;
	private const TRIES_TO_CONFIGURE_AND_START_SIGNING_TIMEOUT = 2;
	private const DOCUMENT_INCORRECT_STATE_ERROR_CODE = 'DOCUMENT_INCORRECT_STATE';

	private readonly DocumentService $documentService;
	private readonly DocumentRepository $documentRepository;
	private readonly MemberRepository $memberRepository;
	private readonly MemberService $memberService;
	private readonly ProfileProvider $profileProvider;
	private readonly MemberDynamicFieldInfoProvider $dynamicFieldProvider;
	/**
	 * @var list<array{name: string, value: string}>
	 */
	private array $validDynamicFields = [];
	/**
	 * @var list<array{name: string, value: string}>
	 */
	private array $validLocalFields = [];

	public function __construct(
		private readonly Template $template,
		private readonly int $sendFromUserId,
		private readonly array $fields = [],
		?DocumentService $documentService = null,
		?ProfileProvider $profileProvider = null,
		?MemberDynamicFieldInfoProvider $dynamicFieldProvider = null,
	)
	{
		$this->documentService = $documentService ?? Container::instance()->getDocumentService();
		$this->documentRepository = Container::instance()->getDocumentRepository();
		$this->memberRepository = Container::instance()->getMemberRepository();
		$this->memberService = Container::instance()->getMemberService();
		$this->profileProvider = $profileProvider ?? Container::instance()->getServiceProfileProvider();
		$this->dynamicFieldProvider = $dynamicFieldProvider ?? Container::instance()->getMemberDynamicFieldProvider();
	}

	public function launch(): Main\Result|SendResult
	{
		if ($this->template->id === null)
		{
			return Result::createByErrorData(message: 'Template is not saved');
		}
		$document = $this->documentRepository->getByTemplateId($this->template->id);
		if ($document?->initiatedByType !== InitiatedByType::EMPLOYEE)
		{
			return Result::createByErrorData(message: 'Cant send document by template');
		}

		$result = $this->validateFields($document);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$result = (new Operation\Document\Copy($document, $this->sendFromUserId))->launch();
		if (!$result instanceof CreateDocumentResult)
		{
			return $result;
		}

		$newDocument = $result->document;

		if ($newDocument->id === null)
		{
			return Result::createByErrorData(message: 'Document is not created.');
		}

		$newDocument->title = $this->template->title;
		$result = $this->documentRepository->update($newDocument);
		if (!$result->isSuccess())
		{
			$rollbackResult = $this->documentService->rollbackDocument($newDocument->id);
			if (!$rollbackResult->isSuccess())
			{
				return $rollbackResult;
			}

			return $result;
		}

		$result = $this->updateMembers($newDocument);
		if (!$result->isSuccess())
		{
			$rollbackResult = $this->documentService->rollbackDocument($newDocument->id);
			if (!$rollbackResult->isSuccess())
			{
				return $rollbackResult;
			}

			return $result;
		}

		$result = $this->fillFields($newDocument->id);
		if (!$result->isSuccess())
		{
			$rollbackResult = $this->documentService->rollbackDocument($newDocument->id);
			if (!$rollbackResult->isSuccess())
			{
				return $rollbackResult;
			}

			return $result;
		}

		$result = $this->configureAndStart($newDocument);
		if (!$result->isSuccess())
		{
			$rollbackResult = $this->documentService->rollbackDocument($newDocument->id);
			if (!$rollbackResult->isSuccess())
			{
				return $rollbackResult;
			}

			return $result;
		}

		$setSmartDocumentAssignedByIdResult = $this->setSmartDocumentAssignedById($newDocument);
		if (!$setSmartDocumentAssignedByIdResult->isSuccess())
		{
			$rollbackResult = $this->documentService->rollbackDocument($newDocument->id);
			if (!$rollbackResult->isSuccess())
			{
				return $rollbackResult;
			}

			return $setSmartDocumentAssignedByIdResult;
		}

		$employeeMember = $this->memberRepository->getByDocumentIdWithRole($newDocument->id, Role::SIGNER);
		if ($employeeMember === null)
		{
			$rollbackResult = $this->documentService->rollbackDocument($newDocument->id);
			if (!$rollbackResult->isSuccess())
			{
				return $rollbackResult;
			}

			return (new Result())->addError(new Error('Employee member not found'));
		}

		return new SendResult($newDocument, $employeeMember);
	}

	private function setSmartDocumentAssignedById(Document $document): Main\Result
	{
		$result = new Main\Result();
		$entity = $this->documentService->getDocumentEntity($document);
		if ($entity === null)
		{
			return $result->addError(new Error('Entity not found'));
		}

		$assignee = $this->memberService->getAssignee($document);
		if (!$assignee)
		{
			return $result->addError(new Error('Assignee not found'));
		}

		$assigneeUserId = $this->memberService->getUserIdForMember($assignee, $document);
		if ($assigneeUserId === null)
		{
			return $result->addError(new Error('Assignee user not found'));
		}

		if (!$entity->setAssignedById($assigneeUserId))
		{
			return $result->addError(new Error('Cannot set assignee user'));
		}

		if (!$entity->addObserver($assigneeUserId))
		{
			return $result->addError(new Error('Cannot add observer user'));
		}

		return $result;
	}

	private function updateMembers(Document $document): Main\Result
	{
		$members = $this->memberRepository->listByDocumentIdExcludeRoles($document->id, Role::SIGNER, Role::EDITOR);
		$members->add(
			new Member(
				party: 1,
				entityType: EntityType::USER,
				entityId: $this->sendFromUserId,
				role: Role::SIGNER,
			),
		);
		foreach ($members as $member)
		{
			$member->id = null;
		}
		$result = $this->memberService->setupB2eMembers($document->uid, $members, $document->representativeId);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$document->parties = $members->count();

		return $this->documentRepository->update($document);
	}

	private function configureAndStart(Document $newDocument): Main\Result
	{
		$result = new Main\Result();
		$tries = 1;

		for ($i = 1; $i <= self::TRIES_TO_CONFIGURE_AND_START_SIGNING; $i++)
		{
			$configureResult = $this->documentService->configureAndStart($newDocument->uid);

			if (!$configureResult->isSuccess())
			{
				$result->addErrors($configureResult->getErrors());
				if (!$this->isDocumentIncorrectStateInErrors($configureResult->getErrors()))
				{
					$tries = $i;
					break;
				}

				sleep(self::TRIES_TO_CONFIGURE_AND_START_SIGNING_TIMEOUT);
				continue;
			}

			if ($configureResult instanceof ConfigureResult && $configureResult->completed)
			{
				return new Main\Result();
			}
		}

		return $result->addError(new Main\Error("Signing to started after `$tries` tries"));
	}

	/**
	 * @param Main\Error[] $errors
	 */
	private function isDocumentIncorrectStateInErrors(array $errors): bool
	{
		foreach ($errors as $error)
		{
			if ($error->getCode() === self::DOCUMENT_INCORRECT_STATE_ERROR_CODE)
			{
				return true;
			}
		}

		return false;
	}

	private function fillFields(int $documentId): Main\Result
	{
		$signer = $this->memberRepository->getByDocumentIdWithRole($documentId, Role::SIGNER);
		if (!$signer)
		{
			return (new Main\Result())->addError(new Main\Error('Signer not found in new document'));
		}

		$result = $this->saveValidDynamicFields($signer);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$result = $this->saveValidLocalFields($signer);
		if (!$result->isSuccess())
		{
			return $result;
		}

		return new Main\Result();
	}

	private function validateFields(Document $document): Main\Result
	{
		$allowedFieldMap = $this->getAllowedFieldsMap($document);
		$presentFieldsMap = [];
		foreach ($this->fields as $field)
		{
			$name = trim((string)($field['name'] ?? ''));
			$value = trim((string)($field['value'] ?? ''));
			$allowedField = $allowedFieldMap[$name] ?? null;
			if (!$allowedField instanceof Field)
			{
				return (new Result())->addError(new Main\Error("Unexpected field: $name"));
			}

			if ($allowedField->required !== false && $value === '')
			{
				return (new Result())->addError(new Main\Error("No value for required field: $name"));
			}

			['fieldCode' => $fieldCode] = NameHelper::parse($name);
			if ($this->profileProvider->isFieldCodeUserProfileField($fieldCode))
			{
				$this->validLocalFields[] = ['name' => $name, 'value' => $value];
			}
			elseif ($this->dynamicFieldProvider->isFieldCodeMemberDynamicField($fieldCode))
			{
				$this->validDynamicFields[] = ['name' => $name, 'value' => $value];
			}
			else
			{
				return (new Result())->addError(new Main\Error("Unexpected field: $name"));
			}

			$presentFieldsMap[$name] = $value;
		}

		foreach ($allowedFieldMap as $field)
		{
			$value = $presentFieldsMap[$field->name] ?? '';
			if ($field->required !== false && $value === '')
			{
				return (new Result())->addError(new Main\Error("No value for required field: $field->name"));
			}
		}

		return new Result();
	}

	/**
	 * @param Document $document
	 *
	 * @return array<string, Field>
	 */
	private function getAllowedFieldsMap(Document $document): array
	{
		return (new \Bitrix\Sign\Factory\Field())
			->createDocumentFutureSignerFields($document, $this->sendFromUserId)
			->getNameMap()
		;
	}

	private function saveValidLocalFields(Member $signer): Main\Result
	{
		if (!$this->validLocalFields)
		{
			return new Main\Result();
		}

		$operation = new Operation\Member\SaveFields(
			member: $signer,
			fields: $this->validLocalFields,
		);

		return $operation->launch();
	}

	private function saveValidDynamicFields(Member $signer): Main\Result
	{
		if (!$this->validDynamicFields)
		{
			return new Main\Result();
		}

		$operation = new Operation\FillFields(
			fields: $this->validDynamicFields,
			member: $signer,
		);

		return $operation->launch();
	}
}