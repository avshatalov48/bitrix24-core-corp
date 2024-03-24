<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Requisite\DefaultRequisite;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Item\Model;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Type\Document\EntityType;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\Member\Role;

final class SignB2eDocument extends Activity
{

	private ?\Bitrix\Crm\Item $document = null;
	private ?\Bitrix\Sign\Item\Document $signDocument = null;
	private ?DocumentRepository $documentRepository = null;
	private ?MemberRepository $memberRepository = null;
	private ?MemberCollection $members = null;

	private const MAX_USER_IN_LINE = 3;

	public function __construct(Context $context, Model $model)
	{
		if (Loader::includeModule('sign'))
		{
			$this->documentRepository = \Bitrix\Sign\Service\Container::instance()->getDocumentRepository();
			$this->memberRepository = \Bitrix\Sign\Service\Container::instance()->getMemberRepository();
		}

		parent::__construct($context, $model);
	}

	protected function getActivityTypeId(): string
	{
		return 'SignB2eDocument';
	}

	public function getIconCode(): ?string
	{
		return Icon::DOCUMENT;
	}

	public function getTitle(): ?string
	{
		return $this->getModel()->isScheduled()
		? Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT')
		: Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT_CLOSED')
		;
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		$action = $this->getShowSigningProcessAction();
		$logo = Layout\Common\Logo::getInstance(Layout\Common\Logo::DOCUMENT)
			->createLogo()
			->setAdditionalIconCode('search')
		;
		if ($action)
		{
			$logo->setAction($action);
		}

		return $logo;
	}

	private function getShowSigningProcessAction(): ?Layout\Action
	{
		if (!\Bitrix\Crm\Activity\Provider\SignB2eDocument::isActive())
		{
			return null;
		}

		$signDocument = $this->getSignDocument();
		if (!$signDocument)
		{
			return null;
		}

		$uri = new Uri('/bitrix/components/bitrix/sign.document.list/slider.php');
		$uri->addParams([
			'site_id' => SITE_ID,
			'sessid' => bitrix_sessid_get(),
			'type' => 'document',
			'entity_id' => $signDocument->entityId,
		]);

		return
			(new Layout\Action\JsEvent($this->getType() . ':ShowSigningProcess'))
				->addActionParamString('processUri', $uri->getUri())
		;
	}

	private function getDocumentBlock(): Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(false)
			->setTitle(Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT'))
			->setContentBlock((new Layout\Body\ContentBlock\Text())
				->setValue($this->getDocument()?->getTitle())
			);
	}

	public function getContentBlocks(): ?array
	{
		$blocks = [];
		if (!\Bitrix\Crm\Activity\Provider\SignDocument::isActive())
		{
			return [(new ContentBlock\Text())->setValue(Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_NOT_EXISTS'))];
		}

		$blocks['doc'] = $this->getDocumentBlock();

		if ($this->getSignDocument())
		{
			$companyBlock = $this->getCompanyBlock();
			if ($companyBlock)
			{
				$blocks['company'] = $companyBlock;
			}

			$blocks += $this->getRolesUsersBlocks();
		}

		return $blocks;
	}

	public function getButtons(): ?array
	{
		$signDocument = $this->getSignDocument();

		$buttons = [];

		$inProcess = in_array($signDocument->status, [DocumentStatus::NEW, DocumentStatus::UPLOADED, DocumentStatus::READY,]);

		if ($signDocument)
		{
			$buttons['signingProcess'] = (new Layout\Footer\Button(
				Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_SIGNING_PROCESS'),
				!$inProcess ? Layout\Footer\Button::TYPE_PRIMARY : Layout\Footer\Button::TYPE_SECONDARY))
				->setAction($this->getShowSigningProcessAction());
		}

		if ($signDocument && $inProcess)
		{
			$action = (new Layout\Action\JsEvent($this->getType() . ':Modify'))
				->addActionParamInt('documentId', $this->getDocumentId());
			$action->addActionParamString('documentUid', $signDocument->uid);

			$buttons['edit'] = (new Layout\Footer\Button(
				Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT_MODIFY'),
				Layout\Footer\Button::TYPE_PRIMARY,
			))->setAction($action);
		}

		return $buttons;
	}

	public function getMenuItems(): array
	{
		$items = parent::getMenuItems();

		unset($items['delete'], $items['view']);

		return $items;
	}

	private function getDocumentId(): int
	{
		return (int)$this->getAssociatedEntityModel()->get('ASSOCIATED_ENTITY_ID');
	}

	private function getEntityTypeId(): int
	{
		return (int)$this->getAssociatedEntityModel()->get('OWNER_TYPE_ID');
	}

	private function getDocument(): ?\Bitrix\Crm\Item
	{
		if (!$this->document)
		{
			$factory = Container::getInstance()->getFactory($this->getEntityTypeId());
			if (!$factory)
			{
				return null;
			}

			$documentId = $this->getDocumentId();

			$this->document = $factory->getItem($documentId);
		}

		return $this->document;
	}

	private function getMyCompanyCaption(): string
	{
		$link = EntityLink::getByEntity($this->getEntityTypeId(), $this->getDocumentId());
		if ($link)
		{
			$requisiteId = $link['MC_REQUISITE_ID'] ?? null;
			$linkedRequisiteId = ((int)$requisiteId > 0) ? (int)$requisiteId : null;
		}

		$document = $this->getDocument();
		if (!empty($linkedRequisiteId))
		{
			$requisites = EntityRequisite::getSingleInstance()->getById($linkedRequisiteId);
		}
		elseif ($document && isset($document->getData()['MYCOMPANY_ID']) && $document->getMycompanyId() > 0)
		{
			$defaultRequisite = new DefaultRequisite(
				new ItemIdentifier(\CCrmOwnerType::Company, $document->getMycompanyId())
			);

			$requisites = $defaultRequisite->get();
		}

		if (!empty($requisites))
		{
			$myCompanyCaption = \Bitrix\Crm\Format\Requisite::formatOrganizationName($requisites);
		}

		return $myCompanyCaption ?? Loc::getMessage('CRM_COMMON_EMPTY_VALUE');
	}

	private function getSignDocument(): ?\Bitrix\Sign\Item\Document
	{
		if (!$this->signDocument)
		{
			$this->signDocument = $this->documentRepository->getByEntityIdAndType(
				$this->getDocumentId(),
				EntityType::SMART_B2E
			);
		}

		return $this->signDocument;
	}

	private function getCompanyBlock(): ?Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		$companyName = $this->getMembers()->findFirstByRole(Role::ASSIGNEE)?->companyName;
		if (!$companyName)
		{
			return null;
		}

		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setTitle(Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_FIELD_COMPANY'))
			->setContentBlock(
				(new Layout\Body\ContentBlock\Text())
					->setValue($companyName)
			)
		;
	}

	/**
	 * @return array<Layout\Body\ContentBlock\ContentBlockWithTitle>
	 */
	private function getRolesUsersBlocks(): array
	{
		$userIdsByRoles = $this->getUserIdsByRoles();

		$blocks = [];
		foreach ($this->getRolesBlockOrder() as $role)
		{
			$roleUserIds = $userIdsByRoles[$role] ?? [];
			$block = $this->getRoleBlock($role, $roleUserIds);
			if ($block)
			{
				$blocks[$role] = $block;
			}
		}

		return $blocks;
	}

	private function getRolesBlockOrder() : array
	{
		return [
			Role::ASSIGNEE,
			Role::REVIEWER,
			Role::EDITOR,
			Role::SIGNER,
		];
	}

	private function getRoleBlock(string $role, array $userIds): ?Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		if (empty($userIds))
		{
			return null;
		}

		$lineOfTextBlocks = new Layout\Body\ContentBlock\LineOfTextBlocks();
		$userCount = count($userIds);
		$num = 0;
		foreach ($userIds as $userId)
		{
			$num += 1;
			if ($num === self::MAX_USER_IN_LINE && $userCount > self::MAX_USER_IN_LINE)
			{
				$link = $this->getUserMoreLink($userCount - $num + 1);
				$lineOfTextBlocks->addContentBlock("roleUserMore", $link);

				break;
			}

			$link = $this->getUserProfileLink($userId, $num !== $userCount);
			$lineOfTextBlocks->addContentBlock("roleUser_$userId", $link);
		}

		if ($lineOfTextBlocks->isEmpty())
		{
			return null;
		}

		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setTitle($this->getUserRoleTitle($role))
			->setContentBlock($lineOfTextBlocks)
		;
	}

	private function getUserProfileLink(int $userId, bool $withDelimiter = false): Layout\Body\ContentBlock\Link
	{
		$user = $this->getUserData($userId);
		$name = $user['FORMATTED_NAME'] ?? '';

		return (new Layout\Body\ContentBlock\Link())
			->setValue($withDelimiter ? $name . ',' : $name)
			->setAction(new Redirect(new Uri($user['SHOW_URL'] ?? '')))
		;
	}

	private function getUserMoreLink(int $moreUserCount):Layout\Body\ContentBlock\Link
	{
		$text = Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_FIELD_MORE_USERS', [
			'#USER_COUNT#' => $moreUserCount,
		]);

		return (new Layout\Body\ContentBlock\Link())
			->setValue($text)
			->setAction($this->getShowSigningProcessAction())
		;
	}

	private function getUserRoleTitle(string $role): ?string
	{
		return match ($role)
		{
			Role::ASSIGNEE => Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_FIELD_REPRESENTATIVE'),
			Role::REVIEWER => Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_FIELD_REVIEWER'),
			Role::EDITOR => Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_FIELD_EDITOR'),
			Role::SIGNER => Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_FIELD_SIGNER'),
			default => null,
		};
	}

	/**
	 * @return array<Role::*, array<int>>
	 */
	private function getUserIdsByRoles(): array
	{
		$ids = [];
		foreach ($this->getMembers() as $member)
		{
			$ids[$member->role][] = $member->role === Role::ASSIGNEE
				? $this->getSignDocument()->representativeId
				: $member->entityId
			;
		}

		return $ids;
	}

	private function getMembers(): MemberCollection
	{
		if (isset($this->members))
		{
			return $this->members;
		}

		$document = $this->getSignDocument();

		$this->members = $document
			? $this->memberRepository->listByDocumentId($document->id)
			: new MemberCollection()
		;

		return $this->members;
	}
}
