<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die;
}

use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Access\Model\UserModel;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService;
use Bitrix\Sign\Config\Feature;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Connector\Crm\MyCompany;
use Bitrix\Sign\Debug\Logger;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Repository\UserRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\Document\TemplateService;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\Member\EntityType;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Item\UserCollection;
use Bitrix\Sign\Type\Template\Visibility;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass('bitrix:sign.base');

final class SignB2eEmployeeTemplateListComponent extends SignBaseComponent
{
	private const DEFAULT_GRID_ID = 'SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_GRID';
	private const DEFAULT_FILTER_ID = 'SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER';
	private const DEFAULT_NAVIGATION_KEY = 'sign-b2e-employee-template-list';
	private const DEFAULT_PAGE_SIZE = 10;
	private const ADD_NEW_TEMPLATE_LINK = '/sign/b2e/doc/0/?mode=template';
	private readonly TemplateService $documentTemplateService;
	private readonly PageNavigation $pageNavigation;
	private readonly DocumentRepository $documentRepository;
	private readonly UserRepository $userRepository;
	private readonly MemberRepository $memberRepository;
	private UserModel $currentUserAccessModel;
	/** @var array<int|string, string|null> */
	private array $currentUserPermissionValuesCache = [];

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->documentTemplateService = Container::instance()->getDocumentTemplateService();
		$this->documentRepository = Container::instance()->getDocumentRepository();
		$this->userRepository = Container::instance()->getUserRepository();
		$this->memberRepository = Container::instance()->getMemberRepository();
		$this->pageNavigation = $this->getPageNavigation();
	}

	public function executeComponent(): void
	{
		if (!Storage::instance()->isB2eAvailable())
		{
			showError((string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_B2E_NOT_ACTIVATED'));

			return;
		}

		if (!Feature::instance()->isSendDocumentByEmployeeEnabled())
		{
			showError((string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_TO_EMPLOYEE_NOT_ACTIVATED'));

			return;
		}
		$accessController = $this->getAccessController();
		if (!$accessController->check(ActionDictionary::ACTION_B2E_TEMPLATE_READ))
		{
			showError('Access denied');

			return;
		}

		parent::executeComponent();
	}

	public function exec(): void
	{
		$this->installPresetTemplatesIfNeed();
		$this->setResult('NAVIGATION_KEY', $this->pageNavigation->getId());
		$this->setResult('CURRENT_PAGE', $this->getNavigation()->getCurrentPage());
		$this->setParam('ADD_NEW_TEMPLATE_LINK', self::ADD_NEW_TEMPLATE_LINK);
		$this->setParam('COLUMNS', $this->getGridColumnList());
		$this->setParam('FILTER_FIELDS', $this->getFilterFieldList());
		$this->setParam('FILTER_PRESETS', $this->getFilterPresets());
		$this->setParam('GRID_ID', self::DEFAULT_GRID_ID);
		$this->setParam('FILTER_ID', self::DEFAULT_FILTER_ID);
		$this->setResult('TOTAL_COUNT', $this->pageNavigation->getRecordCount());
		$this->setResult('DOCUMENT_TEMPLATES', $this->getGridData());
		$this->setResult('PAGE_SIZE', $this->pageNavigation->getPageSize());
		$this->setResult('PAGE_NAVIGATION', $this->pageNavigation);
		$this->setResult('SHOW_TARIFF_SLIDER', B2eTariff::instance()->isB2eRestrictedInCurrentTariff());
		$this->setResult('CAN_ADD_TEMPLATE', $this->canAddTemplate());
		$this->setResult('CAN_EXPORT_BLANK', $this->canExportBlank());
		$this->collectAnalytics();
	}

	private function prepareNavigation(): PageNavigation
	{
		$pageNavigation = new \Bitrix\Sign\Util\UI\PageNavigation($this->arResult['NAVIGATION_KEY']);
		$pageNavigation
			->setPageSize($this->arResult['PAGE_SIZE'] ?? $this->pageNavigation->getPageSize())
			->allowAllRecords(false)
			->initFromUri()
		;
		$this->arResult['PAGE_NAVIGATION'] = $pageNavigation;

		return $pageNavigation;
	}

	private function getNavigation(): PageNavigation
	{
		if (!isset($this->arResult['PAGE_NAVIGATION']))
		{
			return $this->prepareNavigation();
		}

		return $this->arResult['PAGE_NAVIGATION'];
	}

	private function getGridData(): array
	{
		$currentPageElements = $this->getCurrentPageElements();

		if ($currentPageElements->isEmpty() && $this->pageNavigation->getCurrentPage() > 1)
		{
			$this->decrementCurrentPage();
			$currentPageElements = $this->getCurrentPageElements();
		}

		return $this->mapElementsToGridData($currentPageElements);
	}

	private function getCurrentPageElements(): Document\TemplateCollection
	{
		return $this->documentTemplateService->getB2eEmployeeTemplateList(
			$this->getFilterQuery(),
			$this->pageNavigation->getPageSize(),
			$this->pageNavigation->getOffset(),
		);
	}

	private function decrementCurrentPage(): void
	{
		$this->pageNavigation->setCurrentPage($this->pageNavigation->getCurrentPage() - 1);
	}

	private function mapElementsToGridData(Document\TemplateCollection $templates): array
	{
		$responsibleIds = [];
		$templateIds = [];
		foreach ($templates as $template)
		{
			$responsibleId = $this->getResponsibleByTemplate($template);
			$responsibleIds[$responsibleId] = $responsibleId;
			$templateIds[] = $template->id;
		}
		$responsibleUsers = $this->userRepository->getByIds($responsibleIds);
		$companiesByTemplateIds = $this->getCompaniesByTemplateIds($templateIds);

		return array_map(
			fn(Document\Template $template): array => $this->mapTemplateToGridData(
				$template,
				$responsibleUsers,
				$companiesByTemplateIds,
			),
			$templates->toArray(),
		);
	}

	/**
	 * @param Document\Template $template
	 * @param UserCollection $responsibleUsers
	 * @param array<int, MyCompany> $companiesByTemplateIds
	 *
	 * @return array
	 */
	private function mapTemplateToGridData(
		Document\Template $template,
		UserCollection $responsibleUsers,
		array $companiesByTemplateIds,
	): array
	{
		$responsibleData = $responsibleUsers->getByIdMap($this->getResponsibleByTemplate($template) ?? 0);
		$personalPhoto = $responsibleData?->personalPhotoId;
		$responsibleAvatarPath = $personalPhoto
			? htmlspecialcharsbx(CFile::GetPath($personalPhoto))
			: '';
		$responsibleName = $responsibleData?->name ?? '';
		$responsibleLastName = $responsibleData?->lastName ?? '';
		$responsibleFullName = htmlspecialcharsbx("$responsibleName $responsibleLastName");
		$company = $companiesByTemplateIds[$template->id] ?? null;
		$document = $this->documentRepository->getByTemplateId($template->id);

		$data = [
			'id' => $template->id,
			'columns' => [
				'ID' => $template->id,
				'TITLE' => $template->title,
				'DATE_MODIFY' => $template->dateModify ?? $template->dateCreate ?? null,
				'RESPONSIBLE' => [
					'ID' => $template->modifiedById,
					'FULL_NAME' => $responsibleFullName,
					'AVATAR_PATH' => $responsibleAvatarPath,
				],
				'VISIBILITY' => $template->visibility,
				'STATUS' => $template->status,
				'COMPANY' => $company?->name,
			],
			'access' => [
				'canEdit' => $this->canCurrentUserEditTemplate($template),
				'canDelete' => $this->canCurrentUserDeleteTemplate($template),
				'canCreate' => $this->canCurrentUserCreateTemplate($template),
			],
		];

		if (Feature::instance()->isSenderTypeAvailable())
		{
			$data['columns']['TYPE'] = $document?->initiatedByType;
		}

		return $data;
	}

	private function getGridColumnList(): array
	{
		$data = [
			[
				'id' => 'ID',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COLUMN_ID'),
				'default' => false,
			],
			[
				'id' => 'TITLE',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COLUMN_NAME'),
				'default' => true,
			],
		];

		if (Feature::instance()->isSenderTypeAvailable())
		{
			$data[] = [
				'id' => 'TYPE',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COLUMN_TYPE'),
				'default' => true,
			];
		}

		return array_merge($data, [
			[
				'id' => 'COMPANY',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COLUMN_COMPANY'),
				'default' => true,
			],
			[
				'id' => 'RESPONSIBLE',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COLUMN_RESPONSIBLE'),
				'default' => true,
			],
			[
				'id' => 'DATE_MODIFY',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COLUMN_DATE_MODIFY'),
				'default' => true,
			],
			[
				'id' => 'VISIBILITY',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COLUMN_VISIBILITY'),
				'default' => true,
			],
		]);
	}

	private function getFilterFieldList(): array
	{
		$filterFieldList = [
			[
				'id' => 'TITLE',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER_FIELD_NAME'),
				'default' => true,
			],
			[
				'id' => 'DATE_MODIFY',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER_FIELD_DATE_MODIFY'),
				'type' => 'date',
				'default' => true,
			],
			[
				'id' => 'EDITOR',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER_FIELD_EDITOR'),
				'type' => 'entity_selector',
				'partial' => true,
				'params' => $this->getEntitySelectorParamsByType(EntityType::USER),
				'default' => true,
			],
			[
				'id' => 'VISIBILITY',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER_FIELD_VISIBILITY'),
				'type' => 'list',
				'partial' => true,
				'default' => true,
				'items' => [
					Visibility::VISIBLE->toInt() => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER_FIELD_VISIBILITY_Y'),
					Visibility::INVISIBLE->toInt() => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER_FIELD_VISIBILITY_N'),
				],
				'params' => [
					'multiple' => 'Y',
				],
			],
			[
				'id' => 'COMPANY',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER_FIELD_COMPANY'),
				'type' => 'entity_selector',
				'partial' => true,
				'params' => $this->getEntitySelectorParamsByType(EntityType::COMPANY),
				'default' => true,
			],
		];

		if (Feature::instance()->isSenderTypeAvailable())
		{
			$filterFieldList[] = [
				'id' => 'TYPE',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER_FIELD_TYPE'),
				'type' => 'list',
				'partial' => true,
				'default' => true,
				'items' => [
					InitiatedByType::COMPANY->toInt() => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER_FIELD_TYPE_COMPANY'),
					InitiatedByType::EMPLOYEE->toInt() => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER_FIELD_TYPE_EMPLOYEE'),
				],
				'params' => [
					'multiple' => 'Y',
				],
			];
		}

		return $filterFieldList;
	}

	private function getPageNavigation(): PageNavigation
	{
		$pageSize = (int)$this->getParam('PAGE_SIZE');
		$pageSize = $pageSize > 0 ? $pageSize : self::DEFAULT_PAGE_SIZE;
		$navigationKey = $this->getParam('NAVIGATION_KEY') ?? self::DEFAULT_NAVIGATION_KEY;

		$pageNavigation = new \Bitrix\Sign\Util\UI\PageNavigation($navigationKey);
		$pageNavigation->setPageSize($pageSize)
			->setRecordCount($this->documentTemplateService->getB2eEmployeeTemplateListCount($this->getFilterQuery()))
			->allowAllRecords(false)
			->initFromUri()
		;

		return $pageNavigation;
	}

	private function getFilterQuery(): ConditionTree
	{
		$filterData = $this->getFilterValues();

		$queryFilter = $this->prepareQueryFilterByGridFilterData($filterData);

		return $this->prepareQueryFilterByTemplatePermission($queryFilter);
	}

	private function getFilterValues(): array
	{
		$options = new Options(self::DEFAULT_FILTER_ID);

		return $options->getFilter($this->getFilterFieldList());
	}

	private function prepareQueryFilterByGridFilterData(array $filterData): ConditionTree
	{
		$filter = Bitrix\Main\ORM\Query\Query::filter();

		$dateModifyFrom = $filterData['DATE_MODIFY_from'] ?? null;
		if ($dateModifyFrom && \Bitrix\Main\Type\DateTime::isCorrect($dateModifyFrom))
		{
			$filter->where('DATE_MODIFY', '>=', new \Bitrix\Main\Type\DateTime($dateModifyFrom));
		}

		$dateModifyTo = $filterData['DATE_MODIFY_to'] ?? null;
		if ($dateModifyTo && \Bitrix\Main\Type\DateTime::isCorrect($dateModifyTo))
		{
			$filter->where('DATE_MODIFY', '<=', new \Bitrix\Main\Type\DateTime($dateModifyTo));
		}

		$editorIds = $this->ensureArray($filterData['EDITOR'] ?? []);
		if ($editorIds)
		{
			$filter->whereIn('MODIFIED_BY_ID', $editorIds);
		}

		$companyIds = $this->ensureArray($filterData['COMPANY'] ?? []);
		if ($companyIds)
		{
			$filter
				->whereIn('DOCUMENT.MEMBER.ENTITY_ID', $companyIds)
				->where('DOCUMENT.MEMBER.ENTITY_TYPE', EntityType::COMPANY)
			;
		}

		$visibilityValues = $this->ensureArray($filterData['VISIBILITY'] ?? []);
		if ($visibilityValues)
		{
			$filter->whereIn('VISIBILITY', $visibilityValues);
		}

		$initiatedByTypeValues = $this->ensureArray($filterData['TYPE'] ?? []);
		if ($initiatedByTypeValues)
		{
			$filter->whereIn('DOCUMENT.INITIATED_BY_TYPE', $initiatedByTypeValues);
		}

		$find = $filterData['FIND'] ?? null;
		$title = $find ?: $filterData['TITLE'] ?? null;
		if ($title)
		{
			$filter->whereLike('TITLE', '%' . $title . '%');
		}

		return $filter;
	}

	private function ensureArray($value): array
	{
		return is_array($value) ? $value : [$value];
	}

	private function canAddTemplate(): bool
	{
		$accessController = $this->getAccessController();

		return $accessController->checkAll([ActionDictionary::ACTION_B2E_TEMPLATE_ADD, ActionDictionary::ACTION_B2E_TEMPLATE_EDIT]);
	}

	private function getCurrentUserAccessModel(): UserModel
	{
		$this->currentUserAccessModel ??= UserModel::createFromId(CurrentUser::get()->getId());

		return $this->currentUserAccessModel;
	}

	private function prepareQueryFilterByTemplatePermission(ConditionTree $queryFilter): ConditionTree
	{
		if (!Loader::includeModule('crm'))
		{
			return $queryFilter;
		}

		$user = $this->getCurrentUserAccessModel();
		if ($user->isAdmin())
		{
			return $queryFilter;
		}

		$templateReadPermission = $this->getValueForPermissionFromCurrentUser(SignPermissionDictionary::SIGN_B2E_TEMPLATE_READ);

		return match ($templateReadPermission)
		{
			CCrmPerms::PERM_ALL => $queryFilter,
			CCrmPerms::PERM_SELF => $queryFilter->where('CREATED_BY_ID', $user->getUserId()),
			CCrmPerms::PERM_DEPARTMENT => $queryFilter->whereIn('CREATED_BY_ID', $user->getUserDepartmentMembers()),
			CCrmPerms::PERM_SUBDEPARTMENT => $queryFilter->whereIn('CREATED_BY_ID', $user->getUserDepartmentMembers(true)),
			default => $queryFilter->where('CREATED_BY_ID', 0),
		};
	}

	private function canCurrentUserEditTemplate(Document\Template $template): bool
	{
		if (!$this->getAccessController()->check(ActionDictionary::ACTION_B2E_TEMPLATE_ADD))
		{
			return false;
		}

		return $this->hasCurrentUserAccessToPermissionByItemWithOwnerId(
			$template->getOwnerId(),
			SignPermissionDictionary::SIGN_B2E_TEMPLATE_WRITE,
		);
	}

	private function canCurrentUserDeleteTemplate(Document\Template $template): bool
	{
		return $this->hasCurrentUserAccessToPermissionByItemWithOwnerId(
			$template->getOwnerId(),
			SignPermissionDictionary::SIGN_B2E_TEMPLATE_DELETE,
		);
	}

	private function canCurrentUserCreateTemplate(Document\Template $template): bool
	{
		return $this->hasCurrentUserAccessToPermissionByItemWithOwnerId(
			$template->getOwnerId(),
			SignPermissionDictionary::SIGN_B2E_TEMPLATE_CREATE,
		);
	}

	private function hasCurrentUserAccessToPermissionByItemWithOwnerId(int $itemOwnerId, int|string $permissionId): bool
	{
		$userAccessModel = $this->getCurrentUserAccessModel();
		if ($userAccessModel->isAdmin())
		{
			return true;
		}

		$permission = $this->getValueForPermissionFromCurrentUser($permissionId);

		return match ($permission) {
			CCrmPerms::PERM_ALL => true,
			CCrmPerms::PERM_SELF => $itemOwnerId === $userAccessModel->getUserId(),
			CCrmPerms::PERM_DEPARTMENT => in_array($itemOwnerId, $userAccessModel->getUserDepartmentMembers(), true),
			CCrmPerms::PERM_SUBDEPARTMENT => in_array($itemOwnerId, $userAccessModel->getUserDepartmentMembers(true), true),
			default => false,
		};
	}

	private function getValueForPermissionFromCurrentUser(string|int $permissionId): ?string
	{
		$permissionService = new RolePermissionService();

		$this->currentUserPermissionValuesCache[$permissionId] ??= $permissionService->getValueForPermission(
			$this->getCurrentUserAccessModel()->getRoles(),
			$permissionId,
		);

		return $this->currentUserPermissionValuesCache[$permissionId];
	}

	private function getResponsibleByTemplate(Document\Template $template): ?int
	{
		return $template->modifiedById ?? $template->createdById;
	}

	/**
	 * @param list<int> $templateIds
	 *
	 * @return array<int, MyCompany>
	 */
	private function getCompaniesByTemplateIds(array $templateIds): array
	{
		$companyIdsByTemplateIds = $this->getCompanyIdsByTemplateIds($templateIds);
		if (empty($companyIdsByTemplateIds))
		{
			return [];
		}

		$templateIdsByCompanyId = [];
		foreach ($companyIdsByTemplateIds as $templateId => $companyId)
		{
			$templateIdsByCompanyId[$companyId][$templateId] = $templateId;
		}

		$companies = MyCompany::listItems(inIds: array_keys($templateIdsByCompanyId));
		$companiesByTemplateId = [];
		foreach ($companies as $company)
		{
			foreach ($templateIdsByCompanyId[$company->id] ?? [] as $templateId)
			{
				$companiesByTemplateId[$templateId] = $company;
			}
		}

		return $companiesByTemplateId;
	}

	/**
	 * @param list<int> $templateIds
	 *
	 * @return array<int, int> templateId => documentId
	 */
	private function getTemplateIdsByDocumentIds(array $templateIds): array
	{
		if (empty($templateIds))
		{
			return [];
		}

		$documents = $this->documentRepository->listByTemplateIds($templateIds);
		$templateIdsByDocumentIds = [];
		foreach ($documents as $document)
		{
			$templateIdsByDocumentIds[$document->id] = $document->templateId;
		}

		return $templateIdsByDocumentIds;
	}

	/**
	 * @param list<int> $templateIds
	 *
	 * @return array<int, int> templateId => companyId
	 */
	private function getCompanyIdsByTemplateIds(array $templateIds): array
	{
		$templateIdsByDocumentIds = $this->getTemplateIdsByDocumentIds($templateIds);
		if (empty($templateIdsByDocumentIds))
		{
			return [];
		}

		$documentIds = array_keys($templateIdsByDocumentIds);
		$members = $this->memberRepository->listByDocumentIdListAndRoles($documentIds, [Role::ASSIGNEE]);

		$companyIdsByTemplateIds = [];
		foreach ($members as $member)
		{
			if ($member->entityType !== EntityType::COMPANY || empty($member->entityId))
			{
				continue;
			}

			$templateId = $templateIdsByDocumentIds[$member->documentId] ?? null;
			if ($templateId)
			{
				$companyIdsByTemplateIds[$templateId] = $member->entityId;
			}
		}

		return $companyIdsByTemplateIds;
	}

	private function canExportBlank(): bool
	{
		return Storage::instance()->isBlankExportAllowed();
	}

	private function installPresetTemplatesIfNeed(): void
	{
		$result = (new \Bitrix\Sign\Operation\Document\Template\InstallPresetTemplates())->launch();
		if (!$result instanceof \Bitrix\Sign\Result\Operation\Document\Template\InstallPresetTemplatesResult)
		{
			$this->logWithErrorsFromResult('preset install errors: ', $result);

			return;
		}

		$operation = new \Bitrix\Sign\Operation\Document\Template\FixDismissalPresetTemplate(
			isOptionsReloaded: $result->isOptionsReloaded,
		);

		$result = $operation->launch();
		if (!$result->isSuccess())
		{
			$this->logWithErrorsFromResult('template fix errors: ', $result);
		}
	}

	private function logWithErrorsFromResult(string $message, \Bitrix\Main\Result $result): void
	{
		foreach ($result->getErrors() as $error)
		{
			$message .= "{$error->getMessage()} ({$error->getCode()})\n";
		}

		Logger::getInstance()->alert($message);
	}

	private function getEntitySelectorParamsByType(string $entityType): array
	{
		$entities = match ($entityType)
		{
			EntityType::USER => [
				[
					'id' => $entityType,
					'dynamicLoad' => true,
					'dynamicSearch' => true,
					'options' => [
						'inviteEmployeeLink' => false,
					],
				],
			],
			EntityType::COMPANY => [
				[
					'id' => 'sign-mycompany',
					'dynamicLoad' => true,
					'dynamicSearch' => true,
					'options' => [
						'enableMyCompanyOnly' => false,
					],
				],
			],
			default => [],
		};

		return [
			'multiple' => 'Y',
			'dialogOptions' => [
				'height' => 240,
				'entities' => $entities,
			],
		];
	}

	private function getFilterPresets(): array
	{
		$presets = [];
		if (Feature::instance()->isSenderTypeAvailable())
		{
			$presets['fromCompany'] = [
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER_PRESET_TYPE_COMPANY'),
				'fields' => [
					'TYPE' => InitiatedByType::COMPANY->toInt(),
				],
			];
			$presets['fromEmployee'] = [
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER_PRESET_TYPE_EMPLOYEE'),
				'fields' => [
					'TYPE' => InitiatedByType::EMPLOYEE->toInt(),
				],
			];
		}

		$presets['visibleTemplates'] = [
			'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER_PRESET_VISIBILITY_Y'),
			'fields' => [
				'VISIBILITY' => Visibility::VISIBLE->toInt(),
			],
		];
		$presets['invisibleTemplates'] = [
			'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER_PRESET_VISIBILITY_N'),
			'fields' => [
				'VISIBILITY' => Visibility::INVISIBLE->toInt(),
			],
		];

		$currentUserId = (int)CurrentUser::get()->getId();
		if ($currentUserId > 0)
		{
			$presets['editor'] = [
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER_PRESET_EDITOR_ME'),
				'fields' => [
					'EDITOR' => $currentUserId,
				],
			];
		}

		return $presets;
	}

	private function collectAnalytics(): void
	{
		if ($this->getRequest('grid_id') === self::DEFAULT_GRID_ID)
		{
			return;
		}
		$analyticService = Container::instance()->getAnalyticService();

		$event = (new AnalyticsEvent(
			'open_templates',
			'sign',
			'templates',
		))
			->setSection('left_menu')
		;
		$analyticService->sendEventWithSigningContext($event);
	}
}
