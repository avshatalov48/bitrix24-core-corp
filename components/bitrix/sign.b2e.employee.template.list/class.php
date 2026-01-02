<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Config\Feature;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Connector\Crm\MyCompany;
use Bitrix\Sign\Debug\Logger;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\Sign\Item\Document\Template;
use Bitrix\Sign\Item\Document\TemplateCollection;
use Bitrix\Sign\Item\DocumentTemplateGrid\QueryOptions;
use Bitrix\Sign\Item\DocumentTemplateGrid\Row;
use Bitrix\Sign\Item\DocumentTemplateGrid\RowCollection;
use Bitrix\Sign\Repository\Grid\TemplateGridRepository;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Repository\UserRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\Document\Template\AccessService;
use Bitrix\Sign\Service\Sign\Document\TemplateFolderService;
use Bitrix\Sign\Service\Sign\Document\TemplateService;
use Bitrix\Sign\Service\Sign\UrlGeneratorService;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\Member\EntityType;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Item\UserCollection;
use Bitrix\Sign\Type\Template\Visibility;
use Bitrix\UI\Buttons\BaseButton;
use Bitrix\Main\Context;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass('bitrix:sign.base');

final class SignB2eEmployeeTemplateListComponent extends SignBaseComponent
{
	private const DEFAULT_GRID_ID = 'SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_GRID';
	private const SEND_TEMPLATE_MODE_GRID_ID = 'SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_GRID_SEND_MODE';
	private const DEFAULT_FILTER_ID = 'SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FILTER';
	private const FOLDER_TEMPLATE_MODE_FILTER_ID = 'SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_FOLDER_MODE_FILTER';
	private const SEND_TEMPLATE_MODE_NAVIGATION_KEY = 'sign-b2e-employee-template-list-send-mode';
	private const DEFAULT_NAVIGATION_KEY = 'sign-b2e-employee-template-list';
	private const DEFAULT_PAGE_SIZE = 10;
	private readonly TemplateFolderService $templateFolderService;
	private readonly TemplateService $templateService;
	private readonly TemplateGridRepository $templateGridRepository;
	private readonly PageNavigation $pageNavigation;
	private readonly DocumentRepository $documentRepository;
	private readonly UserRepository $userRepository;
	private readonly MemberRepository $memberRepository;
	private readonly AccessService $templateAccessService;
	private readonly UrlGeneratorService $urlGeneratorService;
	private readonly int $folderId;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->templateService = Container::instance()->getDocumentTemplateService();
		$this->templateFolderService = Container::instance()->getTemplateFolderService();
		$this->templateGridRepository = Container::instance()->getTemplateGridRepository();
		$this->documentRepository = Container::instance()->getDocumentRepository();
		$this->userRepository = Container::instance()->getUserRepository();
		$this->memberRepository = Container::instance()->getMemberRepository();
		$this->templateAccessService = Container::instance()->getTemplateAccessService();
		$this->pageNavigation = $this->getPageNavigation();
		$this->urlGeneratorService = Container::instance()->getUrlGeneratorService();
		$this->folderId = $this->getCurrentFolderId();
	}

	public function executeComponent(): void
	{
		$currentUserId = (int)CurrentUser::get()->getId();
		if ($currentUserId < 1)
		{
			return;
		}

		if (!Storage::instance()->isB2eAvailable())
		{
			showError((string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_B2E_NOT_ACTIVATED'));

			return;
		}

		$accessController = $this->getAccessController();
		$hasAccessDocumentAdd = $accessController->check(ActionDictionary::ACTION_B2E_DOCUMENT_ADD);
		$hasAccessDocumentRead = $accessController->check(ActionDictionary::ACTION_B2E_DOCUMENT_READ);
		$hasAccessDocumentEdit = $accessController->check(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT);
		$notAccess = !$hasAccessDocumentAdd || !$hasAccessDocumentRead || !$hasAccessDocumentEdit;
		$isB2eRestrictedInCurrentTariff = B2eTariff::instance()->isB2eRestrictedInCurrentTariff();
		$isTemplateFolderGroupingAllowed = Feature::instance()->isTemplateFolderGroupingAllowed();

		if ($this->fromCompanyTemplateSendMode() && ($isB2eRestrictedInCurrentTariff || $notAccess || !$isTemplateFolderGroupingAllowed))
		{
			showError('Access denied');

			return;
		}

		if (!Feature::instance()->isDocumentTemplatesAvailable())
		{
			showError((string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_TO_EMPLOYEE_NOT_ACTIVATED'));

			return;
		}

		if (!$accessController->check(ActionDictionary::ACTION_B2E_TEMPLATE_READ))
		{
			showError('Access denied');

			return;
		}

		if ($this->isFolderContentMode())
		{
			if (!Feature::instance()->isTemplateFolderGroupingAllowed())
			{
				showError('Access denied');

				return;
			}

			$folder = $this->templateFolderService->getById($this->getCurrentFolderId());
			if (!$folder)
			{
				showError('Folder not found');

				return;
			}

			if (!$accessController->checkByItem(ActionDictionary::ACTION_B2E_TEMPLATE_READ, $folder))
			{
				showError('Access denied');

				return;
			}
		}

		parent::executeComponent();
	}

	public function exec(): void
	{
		$this->installPresetTemplatesIfNeed();
		$this->setResult('NAVIGATION_KEY', $this->pageNavigation->getId());
		$this->setResult('CURRENT_PAGE', $this->getNavigation()->getCurrentPage());
		$this->setParam('ADD_NEW_TEMPLATE_LINK', $this->getCreateTemplateLink());
		$this->setParam('COLUMNS', $this->getGridColumnList());
		$this->setParam('FILTER_FIELDS', $this->getFilterFieldList());
		$this->setParam('FILTER_PRESETS', $this->getFilterPresets());
		$this->setParam('GRID_ID', $this->getGridId());
		$this->setParam('FILTER_ID', $this->getFilterId());
		$this->setParam('CURRENT_FOLDER_TITLE', $this->getCurrentFolderTitle());
		$this->setResult('TOTAL_COUNT', $this->pageNavigation->getRecordCount());
		$this->setResult('DOCUMENT_TEMPLATES', $this->getGridData());
		$this->setResult('PAGE_SIZE', $this->pageNavigation->getPageSize());
		$this->setResult('PAGE_NAVIGATION', $this->pageNavigation);
		$this->setResult('SHOW_TARIFF_SLIDER', B2eTariff::instance()->isB2eRestrictedInCurrentTariff());
		$this->setResult('CAN_ADD_TEMPLATE', $this->canAddTemplate());
		$this->setResult('CAN_EXPORT_BLANK', $this->canExportBlank());
		$this->setResult('FOLDER_ID', $this->folderId);
		$this->setResult('IS_FOLDER_CONTENT_MODE', $this->isFolderContentMode());
		$this->setResult('CREATE_TEMPLATE_ENTITY_BUTTON', $this->getCreateTemplateEntityButton());
		$this->collectAnalytics();
	}

	private function getGridId(): string
	{
		return $this->isFolderIdProvided()
			? self::SEND_TEMPLATE_MODE_GRID_ID
			: self::DEFAULT_GRID_ID
		;
	}

	private function getNavigationKey(): string
	{
		return $this->isFolderContentMode()
			? self::SEND_TEMPLATE_MODE_NAVIGATION_KEY
			: self::DEFAULT_NAVIGATION_KEY
		;
	}

	private function getFilterId(): string
	{
		return $this->isFolderContentMode()
			? self::FOLDER_TEMPLATE_MODE_FILTER_ID
			: self::DEFAULT_FILTER_ID
		;
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

	private function getCurrentPageElements(): RowCollection
	{
		$options = new QueryOptions(
			filter: $this->getFilterQuery(),
			limit: $this->pageNavigation->getPageSize(),
			offset: $this->pageNavigation->getOffset()
		);

		if ($this->isFolderContentMode() && $this->getCurrentFolderId() !== 0)
		{
			return $this->templateGridRepository->listTemplatesByFolderId($this->getCurrentFolderId(), $options);
		}

		return $this->templateGridRepository->listFoldersAndTemplates($options);
	}

	private function getCurrentFolderTitle(): ?string
	{
		if ($this->isFolderContentMode())
		{
			return $this->templateFolderService->getById($this->getCurrentFolderId())->title;
		}

		return null;
	}

	private function getCurrentFolderId(): int
	{
		return (int)Context::getCurrent()->getRequest()->getQuery("folderId");

	}

	private function isFolderIdProvided(): bool
	{
		return Context::getCurrent()->getRequest()->getQuery("folderId") !== null;
	}

	private function decrementCurrentPage(): void
	{
		$this->pageNavigation->setCurrentPage($this->pageNavigation->getCurrentPage() - 1);
	}

	private function mapElementsToGridData(RowCollection $templateEntities): array
	{
		$responsibleIds = [];
		$templateIds = [];
		$folderIds = [];

		foreach ($templateEntities as $templateEntity)
		{
			$responsibleId = $this->getResponsibleByRow($templateEntity);
			$responsibleIds[$responsibleId] = $responsibleId;

			if ($templateEntity->entityType->isFolder())
			{
				$folderIds[] = $templateEntity->id;
			}

			$templateIds[] = $templateEntity->id;
		}

		$responsibleUsers = $this->userRepository->getByIds($responsibleIds);
		$companiesByTemplateIds = $this->getCompaniesByTemplateIds($templateIds);
		$folderTemplateIdsMap = $this->templateFolderService->getTemplateIdsByIdsMap($folderIds);

		$allTemplateIds = array_merge($templateIds, ...array_values($folderTemplateIdsMap));
		$allTemplates = $this->templateService->getByIds($allTemplateIds);

		$rootAndNestedTemplatesMap = [];
		foreach ($allTemplates as $template)
		{
			$rootAndNestedTemplatesMap[$template->id] = $template;
		}

		return array_map(
			fn(Row $row): array => $this->mapTemplateToGridData(
				$row,
				$responsibleUsers,
				$companiesByTemplateIds,
				$folderTemplateIdsMap,
				$rootAndNestedTemplatesMap
			),
			$templateEntities->toArray(),
		);
	}

	/**
	 * @param Row $row
	 * @param UserCollection $responsibleUsers
	 * @param array<int, MyCompany> $companiesByTemplateIds
	 * @param array<int, list<int>> $folderTemplateIdsMap
	 * @param array<int, Template> $rootAndNestedTemplatesMap
	 *
	 * @return array
	 */
	private function mapTemplateToGridData(
		Row $row,
		UserCollection $responsibleUsers,
		array $companiesByTemplateIds,
		array $folderTemplateIdsMap,
		array $rootAndNestedTemplatesMap
	): array
	{
		$responsibleData = $responsibleUsers->getByIdMap($this->getResponsibleByRow($row) ?? 0);
		$personalPhoto = $responsibleData?->personalPhotoId;
		$responsibleAvatarPath = $personalPhoto ? htmlspecialcharsbx(CFile::GetPath($personalPhoto)) : '';
		$responsibleName = $responsibleData?->name ?? '';
		$responsibleLastName = $responsibleData?->lastName ?? '';
		$responsibleFullName = htmlspecialcharsbx("$responsibleName $responsibleLastName");

		$document = $this->documentRepository->getByTemplateId($row->id);
		$company = $this->getCompanies($companiesByTemplateIds, $row);

		$isMultipleCompaniesInFolder = $company['COUNT'] > 0 && $row->entityType->isFolder();
		$isNoCompaniesInFolder = $company['COUNT'] < 0 && $row->entityType->isFolder();
		$isInvisible = $row->visibility->isInvisible();
		$isTemplateDisabled = $row->entityType->isTemplate() && $row->visibility->isInvisible() && $row->status->isNew();

		$templateIds = $row->entityType->isFolder() ? ($folderTemplateIdsMap[$row->id] ?? []) : [$row->id];
		$templates = array_filter(array_map(fn(int $id) => $rootAndNestedTemplatesMap[$id] ?? null, $templateIds));
		$templatesCollection = new TemplateCollection(...$templates);
		$hasAnyInvisibleTemplates = $this->templateService->hasAnyInvisibleTemplates($templatesCollection);
		$hasAccessToRead = $this->templateAccessService->hasAccessToReadForCollection($templatesCollection);

		$isBlocked = $isMultipleCompaniesInFolder
			|| $isNoCompaniesInFolder
			|| $isInvisible
			|| $isTemplateDisabled
			|| !$hasAccessToRead
			|| $hasAnyInvisibleTemplates
		;

		$data = [
			'id' => $row->id,
			'templateIds' => $this->templateFolderService->getTemplateIdsByRow($row),
			'entityType' => $row->entityType,
			'sendBlockedParams' => [
				'isBlocked' => $isBlocked,
				'isMultipleCompaniesInFolder' => $isMultipleCompaniesInFolder,
				'isNoCompaniesInFolder' => $isNoCompaniesInFolder,
				'isInvisible' => $isInvisible,
				'hasAnyInvisibleTemplates' => $hasAnyInvisibleTemplates,
				'isTemplateDisabled' => $isTemplateDisabled,
				'hasNoReadAccess' => !$hasAccessToRead,
			],
			'columns' => [
				'ID' => $row->id,
				'TITLE' => $row->title,
				'DATE_MODIFY' => $row->dateModify ?? $row->dateCreate ?? null,
				'RESPONSIBLE' => [
					'ID' => $row->modifiedById,
					'FULL_NAME' => $responsibleFullName,
					'AVATAR_PATH' => $responsibleAvatarPath,
				],
				'VISIBILITY' => $row->visibility,
				'STATUS' => $row->status,
				'COMPANY' => $company,
			],
			'access' => [
				'canRead' => $this->canCurrentUserReadTemplates($row),
				'canEdit' => $this->canCurrentUserEditTemplate($row),
				'canDelete' => $this->canCurrentUserDeleteTemplate($row),
				'canCreate' => $this->canCurrentUserCreateTemplate($row),
				'canMoveToFolder' => $row->entityType->isTemplate()
					&& Feature::instance()->isTemplateFolderGroupingAllowed()
					&& $document?->initiatedByType?->isCompany()
					&& $this->canCurrentUserEditTemplate($row)
				,
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

		$data = array_merge($data, [
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

		if ($this->fromCompanyTemplateSendMode())
		{
			$data[] = [
				'id' => 'ACTION',
				'name' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COLUMN_ACTION'),
				'default' => true,
			];
		}

		return $data;
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

		if ($this->isSenderTypeAvailableAndNotInFolderContentMode())
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

	private function isSenderTypeAvailableAndNotInFolderContentMode(): bool
	{
		return Feature::instance()->isSenderTypeAvailable() && !$this->isFolderContentMode();
	}

	private function getPageNavigation(): PageNavigation
	{
		$pageSize = (int)$this->getParam('PAGE_SIZE');
		$pageSize = $pageSize > 0 ? $pageSize : self::DEFAULT_PAGE_SIZE;
		$navigationKey = $this->getParam('NAVIGATION_KEY') ?? $this->getNavigationKey();

		$pageNavigation = new \Bitrix\Sign\Util\UI\PageNavigation($navigationKey);
		$pageNavigation->setPageSize($pageSize)
			->setRecordCount($this->templateGridRepository->getListCount(
				new QueryOptions($this->getFilterQuery()),
				$this->getCurrentFolderId()
			))
			->allowAllRecords(false)
			->initFromUri()
		;

		return $pageNavigation;
	}

	private function getFilterQuery(): ConditionTree
	{
		$filterData = $this->getFilterValues();

		$queryFilter = $this->prepareQueryFilterByGridFilterData($filterData);

		return $this->templateAccessService->prepareQueryFilterByTemplatePermission($queryFilter);
	}

	private function getFilterValues(): array
	{
		$options = new Options($this->getFilterId());

		return $options->getFilter($this->getFilterFieldList());
	}

	private function prepareQueryFilterByGridFilterData(array $filterData): ConditionTree
	{
		$filter = Bitrix\Main\ORM\Query\Query::filter();
		if (!Feature::instance()->isTemplateFolderGroupingAllowed())
		{
			$filter->where('ENTITY_TYPE', \Bitrix\Sign\Type\Template\EntityType::TEMPLATE->value);
		}

		$dateModifyFrom = $filterData['DATE_MODIFY_from'] ?? null;
		if ($dateModifyFrom && \Bitrix\Main\Type\DateTime::isCorrect($dateModifyFrom))
		{
			$filter->where(
				Query::filter()
					->logic('or')
					->where('TEMPLATE.DATE_MODIFY', '>=', new \Bitrix\Main\Type\DateTime($dateModifyFrom))
					->where('FOLDER.DATE_MODIFY', '>=', new \Bitrix\Main\Type\DateTime($dateModifyFrom))
			);
		}

		$dateModifyTo = $filterData['DATE_MODIFY_to'] ?? null;
		if ($dateModifyTo && \Bitrix\Main\Type\DateTime::isCorrect($dateModifyTo))
		{
			$filter->where(
				Query::filter()
					->logic('or')
					->where('TEMPLATE.DATE_MODIFY', '<=', new \Bitrix\Main\Type\DateTime($dateModifyTo))
					->where('FOLDER.DATE_MODIFY', '<=', new \Bitrix\Main\Type\DateTime($dateModifyTo))
			);
		}

		$editorIds = $this->ensureArray($filterData['EDITOR'] ?? []);
		if ($editorIds)
		{
			$filter->where(
				Query::filter()
					->logic('or')
					->whereIn('TEMPLATE.MODIFIED_BY_ID', $editorIds)
					->whereIn('FOLDER.MODIFIED_BY_ID', $editorIds)
			);
		}

		$companyIds = $this->ensureArray($filterData['COMPANY'] ?? []);
		if ($companyIds)
		{
			$filter->where(
				(Query::filter()
					->logic('or')
					->where(Query::filter()
						->logic('and')
						->whereIn('TEMPLATE.DOCUMENT.MEMBER.ENTITY_ID', $companyIds)
						->where('TEMPLATE.DOCUMENT.MEMBER.ENTITY_TYPE', EntityType::COMPANY)
					)
					->where(Query::filter()
						->logic('and')
						->whereIn('FOLDER.TEMPLATE.DOCUMENT.MEMBER.ENTITY_ID', $companyIds)
						->where('FOLDER.TEMPLATE.DOCUMENT.MEMBER.ENTITY_TYPE', EntityType::COMPANY)
					)
				)
			);
		}

		$visibilityValues = $this->ensureArray($filterData['VISIBILITY'] ?? []);
		if ($visibilityValues)
		{
			$filter->where(
				Query::filter()
					->logic('or')
					->whereIn('TEMPLATE.VISIBILITY', $visibilityValues)
					->whereIn('FOLDER.VISIBILITY', $visibilityValues)
			);
		}

		$initiatedByTypeValues = array_map(function($value)
		{
			return (int)$value;
		}, $this->ensureArray($filterData['TYPE'] ?? []));

		if ($initiatedByTypeValues)
		{
			if (in_array(InitiatedByType::COMPANY->toInt(), $initiatedByTypeValues, true))
			{
				$filter->where(
					Query::filter()
						->logic('or')
						->whereIn('TEMPLATE.DOCUMENT.INITIATED_BY_TYPE', $initiatedByTypeValues)
						->where('ENTITY_TYPE', \Bitrix\Sign\Type\Template\EntityType::FOLDER->value)
				);
			}
			else
			{
				$filter->whereIn('TEMPLATE.DOCUMENT.INITIATED_BY_TYPE', $initiatedByTypeValues);
			}
		}

		$find = $filterData['FIND'] ?? null;
		$title = $find ?: $filterData['TITLE'] ?? null;
		if ($title)
		{
			$filter->where(
				Query::filter()
					->logic('or')
					->whereLike('TEMPLATE.TITLE', '%' . $title . '%')
					->whereLike('FOLDER.TITLE', '%' . $title . '%')
			);
		}

		if ($this->fromCompanyTemplateSendMode())
		{
			$filter->where(
				Query::filter()
					->logic('or')
					->where('TEMPLATE.DOCUMENT.INITIATED_BY_TYPE', InitiatedByType::COMPANY->toInt())
					->where('ENTITY_TYPE', \Bitrix\Sign\Type\Template\EntityType::FOLDER->value)
			);
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

	private function canCurrentUserEditTemplate(Row $row): bool
	{
		if (!$this->getAccessController()->check(ActionDictionary::ACTION_B2E_TEMPLATE_ADD))
		{
			return false;
		}

		return $this->templateAccessService->hasCurrentUserAccessToPermissionByItemWithOwnerId(
			$row->createdById,
			SignPermissionDictionary::SIGN_B2E_TEMPLATE_WRITE,
		);
	}

	private function canCurrentUserReadTemplates(Row $row): bool
	{
		return $this->templateAccessService->hasCurrentUserAccessToPermissionByItemWithOwnerId(
			$row->createdById,
			SignPermissionDictionary::SIGN_B2E_TEMPLATE_READ,
		);
	}

	private function canCurrentUserDeleteTemplate(Row $row): bool
	{
		return $this->templateAccessService->hasCurrentUserAccessToPermissionByItemWithOwnerId(
			$row->createdById,
			SignPermissionDictionary::SIGN_B2E_TEMPLATE_DELETE,
		);
	}

	private function canCurrentUserCreateTemplate(Row $row): bool
	{
		return $this->templateAccessService->hasCurrentUserAccessToPermissionByItemWithOwnerId(
			$row->createdById,
			SignPermissionDictionary::SIGN_B2E_TEMPLATE_CREATE,
		);
	}

	private function getResponsibleByRow(Row $row): ?int
	{
		return $row->modifiedById ?? $row->createdById;
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

	private function getCompanies(array $companiesByTemplateIds, Row $row): array
	{
		$company = $companiesByTemplateIds[$row->id] ?? null;
		$templateIdsForFolder = $row->entityType->isFolder()
			? $this->templateFolderService->getTemplateIdsById($row->id)
			: [];
		$companies = $this->getCompaniesByTemplateIds($templateIdsForFolder);
		$companyNames = [];
		foreach ($companies as $company)
		{
			$companyNames[] = $company?->name;
		}

		$uniqueCompanyNames = array_unique($companyNames);
		$companyForFolder = $uniqueCompanyNames === [] ? null : $uniqueCompanyNames[0];
		$companyTitle = $row->entityType->isTemplate() ? $company?->name : $companyForFolder;

		return [
			'TITLE' => $companyTitle,
			'COUNT' => count($uniqueCompanyNames) - 1,
		];
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
			if (!in_array(
					$member->entityType,
					[EntityType::COMPANY, EntityType::ROLE],
					true
				) || empty($member->entityId))
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
		$createdById = (int)CurrentUser::get()->getId();
		if($createdById < 1)
		{
			return;
		}

		$result = (new \Bitrix\Sign\Operation\Document\Template\InstallPresetTemplates(
			createdById: $createdById,
		))->launch();
		if (!$result instanceof \Bitrix\Sign\Result\Operation\Document\Template\InstallPresetTemplatesResult)
		{
			$this->logWithErrorsFromResult('preset install errors: ', $result);

			return;
		}

		$operation = new \Bitrix\Sign\Operation\Document\Template\FixDismissalPresetTemplate(
			createdById: $createdById,
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
		if ($this->isSenderTypeAvailableAndNotInFolderContentMode())
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

	private function getCreateTemplateEntityButton(): ?BaseButton
	{
		$currentUserId = (int)CurrentUser::get()->getId();
		if ($currentUserId < 1)
		{
			return null;
		}

		$canCreate = $this->templateAccessService->hasCurrentUserAccessToPermissionByItemWithOwnerId(
			$currentUserId,
			SignPermissionDictionary::SIGN_B2E_TEMPLATE_CREATE,
		);
		if (!$canCreate)
		{
			return null;
		}

		$createTemplateLink = $this->getCreateTemplateLink();
		if (!Feature::instance()->isTemplateFolderGroupingAllowed() || $this->isFolderContentMode())
		{
			return (new \Bitrix\UI\Buttons\CreateButton([]))
				->setText(Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ADD_NEW_TEMPLATE_MAIN_TITLE') ?? '')
				->setLink($createTemplateLink)
			;
		}

		$buttonParams = [
			'text' => Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ADD_NEW_TEMPLATE_MAIN_TITLE') ?? '',
		];

		$menuItems = [
			[
				'text' => Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ADD_NEW_TEMPLATE_ITEM_TITLE'),
				'href' => $createTemplateLink,
				'onclick' => new \Bitrix\UI\Buttons\JsCode('this.close()'),
			],
			[
				'text' => Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ADD_NEW_TEMPLATE_FOLDR_ITEM_TITLE'),
				'onclick' => new \Bitrix\UI\Buttons\JsCode('this.close(); templateGrid.createFolder();'),
			],
		];

		$showTariffSlider = B2eTariff::instance()->isB2eRestrictedInCurrentTariff();
		if (!$showTariffSlider)
		{
			$buttonParams['mainButton'] = ['link' => $createTemplateLink];
			$buttonParams['menu'] = ['items' => $menuItems];

		}

		return new \Bitrix\UI\Buttons\Split\CreateButton($buttonParams);
	}

	private function isFolderContentMode(): bool
	{
		return $this->getCurrentFolderId() > 0;
	}

	private function fromCompanyTemplateSendMode(): bool
	{
		return $this->isFolderIdProvided() && $this->getCurrentFolderId() === 0;
	}

	private function getCreateTemplateLink(): string
	{
		return $this->urlGeneratorService->makeCreateTemplateLink($this->isFolderIdProvided(), $this->folderId);
	}
}
