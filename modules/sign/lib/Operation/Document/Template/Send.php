<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main\Error;
use Bitrix\Main;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Helper\Field\NameHelper;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Document\BindingCollection;
use Bitrix\Sign\Item\Document\Template;
use Bitrix\Sign\Item\Field;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Operation;
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
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\Template\Status;
use Bitrix\Sign\Type\Template\Visibility;

final class Send implements Contract\Operation
{
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
	private ?BindingCollection $bindings = null;

	public function __construct(
		private readonly Template $template,
		private readonly int $responsibleUserId,
		private readonly array $fields = [],
		private readonly ?int $sendFromUserId = null,
		private readonly ?int $representativeUserId = null,
		private readonly ?MemberCollection $memberList = null,
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

		if ($this->template->status !== Status::COMPLETED)
		{
			return Result::createByErrorData(message: 'Template is not completed');
		}

		if ($this->template->visibility === Visibility::INVISIBLE)
		{
			return Result::createByErrorData(message: 'Template is not visible');
		}

		if ($this->getSendFromUserId() < 1)
		{
			return Result::createByErrorData(message: 'Send from user is not set');
		}

		if ($this->responsibleUserId < 1)
		{
			return Result::createByErrorData(message: 'Responsible user id not set');
		}

		$document = $this->documentRepository->getByTemplateId($this->template->id);
		if ($document === null)
		{
			return Result::createByErrorData(message: 'Document not found');
		}

		if (!in_array($document?->initiatedByType, InitiatedByType::getAll(), true))
		{
			return Result::createByErrorData(message: 'Cant send document by template');
		}

		if ($document->initiatedByType === InitiatedByType::EMPLOYEE)
		{
			if ($this->sendFromUserId === null)
			{
				return Result::createByErrorData(message: 'Send from user id is not set');
			}

			$result = $this->validateFields($document);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		$result = (new Operation\Document\Copy(
			document: $document,
			createdByUserId: $this->responsibleUserId,
			bindings: $this->bindings,
		))->launch();
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

		$assigneeMember = $this->memberRepository->getByDocumentIdWithRole($newDocument->id, Role::ASSIGNEE);
		if ($assigneeMember === null)
		{
			$rollbackResult = $this->documentService->rollbackDocument($newDocument->id);
			if (!$rollbackResult->isSuccess())
			{
				return $rollbackResult;
			}

			return (new Result())->addError(new Error('Assignee member not found'));
		}

		return new SendResult($newDocument, $employeeMember, $assigneeMember);
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
		$operation = new SetupTemplateMembers(
			document: $document,
			sendFromUserId: $this->sendFromUserId,
			representativeUserId: $this->representativeUserId,
			memberList: $this->memberList,
		);

		return $operation->launch();
	}

	private function configureAndStart(Document $newDocument): Main\Result
	{
		$configureResult = (new Operation\ConfigureFillAndStart($newDocument->uid))->launch();

		if (!$configureResult->isSuccess())
		{
			return $configureResult;
		}

		if ($configureResult instanceof Operation\Result\ConfigureResult && !$configureResult->completed)
		{
			Container::instance()->getDocumentAgentService()->addConfigureAndStartAgent($newDocument->uid);
		}

		return new Main\Result();
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
		if ($this->sendFromUserId === null)
		{
			return [];
		}

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

	private function getSendFromUserId(): int
	{
		$userId = (int)$this->sendFromUserId;

		return $userId ?: $this->template->createdById;
	}

	public function setBindings(BindingCollection $bindings): void
	{
		$this->bindings = $bindings;
	}
}
