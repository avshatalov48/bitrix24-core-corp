<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\PhoneNumber;
use Bitrix\Sign\Compatibility\Document\Scheme;
use Bitrix\Sign\Compatibility\Role;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Connector\MemberConnectorFactory;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Debug\Logger;
use Bitrix\Sign\Factory;
use Bitrix\Sign\Helper\Field\NameHelper;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\Sign\Integration\CRM\Model\EventData;
use Bitrix\Sign\Item;
use Bitrix\Sign\Item\Api\Document\Signing\ConfigureRequest;
use Bitrix\Sign\Item\Api\Property\Request\Signing\Configure\Block;
use Bitrix\Sign\Item\Api\Property\Request\Signing\Configure\BlockCollection;
use Bitrix\Sign\Item\Api\Property\Request\Signing\Configure\FieldCollection;
use Bitrix\Sign\Item\Api\Property\Request\Signing\Configure\Member;
use Bitrix\Sign\Item\Api\Property\Request\Signing\Configure\MemberCollection;
use Bitrix\Sign\Item\Api\Property\Request\Signing\Configure\Owner;
use Bitrix\Sign\Item\Field;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Repository\RequiredFieldRepository;
use Bitrix\Sign\Service;
use Bitrix\Sign\Type;
use Bitrix\Sign\Type\BlockCode;
use Bitrix\Sign\Type\DocumentScenario;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\FieldType;
use Bitrix\Sign\Type\Member\ChannelType;

class ConfigureDocument implements Contract\Operation
{
	private Factory\Api\Property\Request\Field\Fill\Value $fieldsFillRequestValueFactory;
	private Factory\Field $fieldFactory;
	private Service\Providers\ProfileProvider $profileProvider;
	private Service\Sign\DocumentService $documentService;
	private Service\Sign\MemberService $memberService;
	private Service\Cache\Memory\Sign\UserCache $userCache;
	private MemberRepository $memberRepository;
	private readonly RequiredFieldRepository $requiredFieldRepository;
	private readonly Service\Api\Document\SigningService $apiDocumentSigningService;
	private readonly Service\Sign\BlockService $signBlockService;
	private readonly Service\Sign\Document\ProviderCodeService $providerCodeService;
	private readonly Logger $logger;
	private readonly Service\Integration\HumanResources\HcmLinkService $hcmLinkService;

	public function __construct(
		private readonly string $uid,
		?Service\Sign\DocumentService $documentService = null,
		?Service\Sign\MemberService $memberService = null,
		?MemberRepository $memberRepository = null,
		?RequiredFieldRepository $requiredFieldRepository = null,
	)
	{
		$container = Service\Container::instance();
		$this->fieldsFillRequestValueFactory = new Factory\Api\Property\Request\Field\Fill\Value();
		$this->fieldFactory = new Factory\Field();
		$this->profileProvider = $container->getServiceProfileProvider();
		$this->documentService = $documentService ?? $container->getDocumentService();
		$this->memberService = $memberService ?? $container->getMemberService();
		$this->userCache = new Service\Cache\Memory\Sign\UserCache();
		$this->profileProvider->setCache($this->userCache);
		$this->memberService->setProfileProviderCache($this->userCache);
		$this->memberRepository = $memberRepository ?? $container->getMemberRepository();
		$this->requiredFieldRepository = $requiredFieldRepository ?? $container->getRequiredFieldRepository();
		$this->apiDocumentSigningService = $container->getApiDocumentSigningService();
		$this->signBlockService = $container->getSignBlockService();
		$this->providerCodeService = $container->getProviderCodeService();
		$this->logger = Logger::getInstance();
		$this->hcmLinkService = $container->getHcmLinkService();
	}

	public function launch(): Main\Result
	{
		$document = $this->documentService->getByUid($this->uid);
		if (!$document)
		{
			return (new Main\Result())->addError(new Main\Error('Document not found'));
		}
		if ($document->blankId === null)
		{
			return (new Main\Result())->addError(new Main\Error('Document doesnt contains blank'));
		}

		if (Type\DocumentScenario::isB2eScenarioByDocument($document))
		{
			if (!Storage::instance()->isB2eAvailable())
			{
				return (new Main\Result())->addError(new Main\Error('Document scenario not available'));
			}
			if (B2eTariff::instance()->isB2eRestrictedInCurrentTariff())
			{
				return (new Main\Result())->addError(B2eTariff::instance()->getCommonAccessError());
			}
		}

		if ($document->status !== DocumentStatus::UPLOADED)
		{
			return (new Main\Result())->addError(new Main\Error(
				message: 'Document has improper status',
				code: 'SIGN_DOCUMENT_INCORRECT_STATUS',
				customData:	[
					'has' => $document->status,
					'expected' => [DocumentStatus::UPLOADED],
				],
			));
		}

		$members = $this->memberRepository
			->setUserCache($this->userCache)
			->listByDocumentId($document->id)
		;

		$validateHcmLinkResult = $this->fillAndValidateHcmLinkEmployees($document, $members);
		if (!$validateHcmLinkResult->isSuccess())
		{
			return $validateHcmLinkResult;
		}

		$memberCollection = new MemberCollection();
		$owner = new Owner($document->initiator);
		foreach ($members as $member)
		{
			$memberChannelValue = $member->channelValue === ''
				? null
				: $member->channelValue
			;

			$channelType = $member->channelType;
			// todo: remove it later
			if (DocumentScenario::isB2EScenario($document->scenario))
			{
				$channelType = ChannelType::IDLE;
			}

			if ($channelType === ChannelType::PHONE)
			{
				$memberChannelValue = PhoneNumber\Parser::getInstance()
					->parse($member->channelValue)
					->format(PhoneNumber\Format::E164);
			}

			if (
				!$owner->channelType
				&& $member->role === Type\Member\Role::ASSIGNEE
				&& $member->entityType === \Bitrix\Sign\Type\Member\EntityType::COMPANY
			)
			{
				$company = Main\Loader::includeModule('crm')
					? Container::getInstance()
						->getFactory(\CCrmOwnerType::Company)
						->getItem($member->entityId)
					: null
				;

				$owner = new Owner(
					name: $document->initiator,
					companyName: $company?->getTitle(),
					channelType: $channelType,
					channelValue: $memberChannelValue,
				);
			}

			$requestMember = new Member(
				party: $member->party,
				channel: new Member\Channel(
					type: $channelType, value: $memberChannelValue,
				),
				uid: $member->uid,
				name: $this->getMemberNameToSend($member),
				role: $member->role ?? Role::createByParty($member->party),
			);

			if (DocumentScenario::isB2EScenario($document->scenario))
			{
				$requestMember->avatarUrl = Service\Container::instance()
					->getUrlGeneratorService()
					->makeMemberAvatarLoadUrl($member)
				;
				if (in_array($member->role, [Type\Member\Role::SIGNER, Type\Member\Role::ASSIGNEE], true))
				{
					$userId = $this->memberService->getUserIdForMember($member);
					$requestMember->sesSigningLogin = $this->getUserModelById($userId)?->getLogin();
				}
			}

			$memberCollection->addItem($requestMember);
		}

		$signersCount = $members->filterByRole(Type\Member\Role::SIGNER)->count();
		if (
			Type\DocumentScenario::isB2eScenarioByDocument($document)
			&& B2eTariff::instance()->isB2eSignersCountRestricted($signersCount)
		)
		{
			return (new Main\Result())->addError(B2eTariff::instance()->getSignersCountAccessError());
		}

		$memberFieldsCollection = $this->signBlockService
		   ->loadBlocksAndDataByDocument(
			   document: $document,
			   skipSecurity: true,
		   )
		;
		if (!$memberFieldsCollection->isSuccess())
		{
			return $memberFieldsCollection;
		}
		$blocks = $memberFieldsCollection->getBlocks();
		if ($blocks === null)
		{
			return (new Main\Result())->addError(new Main\Error('Blocks doesnt loaded'));
		}

		$requiredFields = $this->createRequiredFields($document, $members);
		$requestBlocks = new BlockCollection();
		$registeredFields = new Item\FieldCollection(...$requiredFields);
		$requestFields = new FieldCollection(...array_map(
			static fn ($field) => Item\Api\Property\Request\Signing\Configure\Field::createFromFieldItem($field),
			$requiredFields->toArray(),
		));

		foreach ($blocks as $block)
		{
			$memberWithBlockRole = $members->findFirstByRole($block->role);
			$requestBlock = new Block(
				party: $memberWithBlockRole?->party ?? 0,
				type: $block->type,
				blockPosition: Block\BlockPosition::createFromBlockItemPosition($block->position),
			);
			$requestBlock->style = $block->style === null
				? null
				: Block\BlockStyle::createFromBlockItemStyle($block->style)
			;
			if ($memberWithBlockRole === null && $block->party !== 0)
			{
				return (new Main\Result())->addError(new Main\Error("Block has party: `{$block->party}` but member with party: `{$block->party}` doesnt exist"));
			}

			$fields = $this->createFields($block, $memberWithBlockRole, $document);
			foreach ($fields as $field)
			{
				$requestBlock->addFieldNames($field->name);
				if (!$requestFields->existWithName($field->name))
				{
					$requestFields->addItem(
						Item\Api\Property\Request\Signing\Configure\Field::createFromFieldItem($field),
					);
					$registeredFields->add($field);
				}
			}

			if (!$fields->isEmpty())
			{
				$requestBlocks->addItem($requestBlock);
			}
		}
		$isB2eScenario = DocumentScenario::isB2eScenarioByDocument($document);
		if ($isB2eScenario)
		{
			$result = $this->providerCodeService->loadByDocument($document);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		$culture = Main\Application::getInstance()->getContext()->getCulture();

		$response = $this->apiDocumentSigningService->configure(
			new ConfigureRequest(
				documentUid: $document->uid,
				title: $this->documentService->getComposedTitleByDocument($document),
				owner: $owner,
				parties: $document->parties,
				scenario: $document->scenario,
				fields: $requestFields,
				blocks: $requestBlocks,
				members: $memberCollection,
				langId: $document->langId,
				companyUid: $document->companyUid,
				nameFormat: $this->profileProvider->getNameFormat(),
				regionDocumentType: $document->regionDocumentType,
				externalId: $document->externalId,
				titleWithoutNumber: $document->title,
				scheme: $this->getDocumentScheme($document),
				externalDateCreate: $document->externalDateCreate?->format('Y-m-d') ?? '',
				dateFormat: $culture?->getDateFormat(),
				dateTimeFormat: $culture?->getDateTimeFormat(),
				weekStart: $culture?->getWeekStart(),
			),
		);

		if (!$response->isSuccess())
		{
			return (new Main\Result())->addErrors($response->getErrors());
		}

		$result = $this->fillCommonFields($registeredFields, $document);
		if (!$result->isSuccess())
		{
			return (new Main\Result())->addErrors($result->getErrors());
		}

		$document->status = DocumentStatus::READY;
		Service\Container::instance()->getDocumentRepository()->update($document);
		$this->sendStatusChangedEvent($document);

		return new Main\Result();
	}

	private function createFields(Item\Block $block, ?Item\Member $member, Item\Document $document): Item\FieldCollection
	{
		$fields = $this->fieldFactory->createByBlocks(new Item\BlockCollection($block), $member, $document);
		if (!BlockCode::isCommon($block->code))
		{
			return $fields;
		}

		$firstField = $fields->getFirst();
		if ($firstField === null)
		{
			return new Item\FieldCollection();
		}
		$value = null;

		if ($block->code === BlockCode::NUMBER)
		{
			$valueString = $block->data['text'] ?? null;
			if (is_string($valueString))
			{
				$value = new Item\Field\Value(0, text: $valueString);
			}
		}
		elseif (in_array($block->code, [BlockCode::DATE, BlockCode::TEXT], true))
		{
			$valueString = $block->data['text'] ?? null;
			if (is_string($valueString))
			{
				$value = new Item\Field\Value(0, text: $valueString);
			}
		}
		$firstField->values = $value === null ? null : new Item\Field\ValueCollection($value);

		return $fields;
	}

	private function createRequiredFields(
		Item\Document $document,
		Item\MemberCollection $memberCollection,
	): Item\FieldCollection
	{
		if (in_array($document->scenario, DocumentScenario::getB2EScenarios(), true))
		{
			return $this->createB2eRequiredFields($document, $memberCollection);
		}

		$result = new Item\FieldCollection();

		foreach (range(1, $document->parties) as $party)
		{
			$result->add(
				new Item\Field(
					0,
					$party,
					FieldType::SIGNATURE,
					$this->createFieldName(
						$party === 1 ? BlockCode::MY_SIGN : BlockCode::SIGN,
						FieldType::SIGNATURE,
						$party,
						null,
					),
					label: null,
					connectorType: '',
				),
			);
		}

		return $result;
	}

	private function createFieldName(string $blockCode, string $fieldType, int $party, ?string $fieldCode, ?string $subfieldCode = null): string
	{
		return NameHelper::create($blockCode, $fieldType, $party, $fieldCode, $subfieldCode);
	}

	private function fillCommonFields(
		Item\FieldCollection $registeredFields,
		Item\Document $document,
	): Item\Api\Document\Field\FillResponse
	{
		$memberFieldsCollection = new Item\Api\Property\Request\Field\Fill\MemberFieldsCollection();
		$generalFields = $registeredFields->filter(static fn(Item\Field $field) => $field->party === 0);
		foreach ($generalFields as $generalField)
		{
			$value = $generalField?->values?->getFirst();
			if ($value === null)
			{
				continue;
			}
			$fieldValue = $this->fieldsFillRequestValueFactory->createByValueItem($value);
			if ($fieldValue === null)
			{
				continue;
			}
			$memberFieldsCollection->addItem(
				new Item\Api\Property\Request\Field\Fill\MemberFields(
					memberId: '',
					fields: new Item\Api\Property\Request\Field\Fill\FieldCollection(
						new Item\Api\Property\Request\Field\Fill\Field(
							$generalField->name,
							new Item\Api\Property\Request\Field\Fill\FieldValuesCollection($fieldValue),
						),
					),
				),
			);
		}

		$apiDocumentFieldService = Service\Container::instance()->getApiDocumentFieldService();
		$request = new Item\Api\Document\Field\FillRequest(
			$document->uid, $memberFieldsCollection,
		);

		return $apiDocumentFieldService->fill($request);
	}

	private function createB2eRequiredFields(Item\Document $document, Item\MemberCollection $memberCollection): Item\FieldCollection
	{
		$fieldsCollection = new Item\FieldCollection();

		$apiResult = Service\Container::instance()
			->getApiB2eProviderFieldsService()
			->loadRequiredFields($document->companyUid)
		;

		if (!$apiResult instanceof Service\Result\Sign\Block\B2eRequiredFieldsResult)
		{
			return $fieldsCollection;
		}

		$requiredFields = $apiResult->collection;
		$this->requiredFieldRepository->replaceRequiredItemsCollectionForDocumentId($requiredFields, $document->id);

		foreach ($requiredFields as $requiredField)
		{
			$field = $this->fieldFactory->createByRequired($document, $memberCollection, $requiredField);
			if ($field instanceof Field)
			{
				$fieldsCollection->add($field);
			}
		}

		return $fieldsCollection;
	}

	private function getUserModelById(int $userId): ?Main\EO_User
	{
		$modelFromCache = $this->userCache->getLoadedModel($userId);
		if ($modelFromCache !== null)
		{
			return $modelFromCache;
		}
		$cachedFields = $this->userCache->getCachedFields();
		if (empty($cachedFields))
		{
			$cachedFields = [
				'ID',
				'NAME',
				'SECOND_NAME',
				'LAST_NAME',
				'LOGIN',
			];
			$this->userCache->setCachedFields($cachedFields);
		}

		$model = Main\UserTable::query()
			->where('ID', $userId)
			->setSelect($cachedFields)
			->fetchObject()
		;
		if ($model === null)
		{
			return null;
		}

		$this->userCache->setCacheByModel($model);

		return $model;
	}

	private function getMemberNameToSend(Item\Member $member): ?string
	{
		if ($member->role === Type\Member\Role::SIGNER && $member->entityType === Type\Member\EntityType::USER)
		{
			// legal name is going to send with fields
			return $member->name;
		}

		return $this->memberService->getMemberRepresentedName($member);
	}

	private function sendStatusChangedEvent(Item\Document $document): void
	{
		$eventData = (new EventData())
			->setEventType(EventData::TYPE_ON_SENDING)
			->setDocumentItem($document)
		;

		try
		{
			Service\Container::instance()->getEventHandlerService()->createTimelineEvent($eventData);
		}
		catch (Main\ArgumentException|Main\ArgumentOutOfRangeException $e)
		{
		}
	}

	private function getDocumentScheme(Item\Document $document): ?string
	{
		if (!DocumentScenario::isB2eScenarioByDocument($document))
		{
			return null;
		}

		$result = $this->providerCodeService->loadByDocument($document);
		if (!$result->isSuccess())
		{
			$this->logger->error(
				'Failed to load provider code by configuration. Errors: {errorsText}',
				[
					'errorsText' => implode('| ', $result->getErrorMessages()),
				],
			);

			return Type\Document\SchemeType::DEFAULT;
		}

		return $document->scheme ?? Scheme::createDefaultSchemeByProviderCode($document->providerCode);
	}

	private function fillAndValidateHcmLinkEmployees(Item\Document $document, Item\MemberCollection $members): Main\Result
	{
		if (!$document->hcmLinkCompanyId || !$this->hcmLinkService->isAvailable())
		{
			return new Main\Result();
		}

		$notFilled = $members->filter(static fn(Item\Member $member) => !$member->employeeId);
		if ($notFilled->isEmpty())
		{
			return new Main\Result();
		}

		$this->hcmLinkService->fillOneLinkedMembersWithEmployeeId($document, $notFilled, $document->representativeId);
		$someoneNotFilled = $notFilled->findFirst(static fn(Item\Member $member) => !$member->employeeId);
		if ($someoneNotFilled)
		{
			return (new Main\Result())->addError(new Main\Error('Not all members mapped'));
		}

		foreach ($notFilled as $member)
		{
			$result = $this->memberRepository->update($member);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return new Main\Result();
	}
}
