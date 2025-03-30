<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die;
}

use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Config\Feature;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Document;
use Bitrix\Sign\Internal\DocumentTable;
use Bitrix\Sign\Item\Api\Client\DomainRequest;
use Bitrix\Sign\Main\Application;
use Bitrix\Sign\Main\User;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\Document\GroupService;
use Bitrix\Sign\Type\Document\InitiatedByType;

\CBitrixComponent::includeComponentClass('bitrix:sign.base');

class SignMasterComponent extends SignBaseComponent
{
	/**
	 * Restricted size for images.
	 */
	private const IMAGE_SIZES = [
		'width' => 1275,
		'height' => 1650,
	];

	private const TEMPLATE_ID_URL_PARAMETER_NAME = 'templateId';

	private const SES_COM_AGREEMENT_DATE_YEAR = 2024;
	private const SES_COM_AGREEMENT_DATE_DAY = 1;
	private const SES_COM_AGREEMENT_DATE_MONTH = 3;

	private const MODE_TEMPLATE = 'template';
	private const MODE_DOCUMENT = 'document';

	/**
	 * Required params of component.
	 * If not specified, will be set to null.
	 * @var string[]
	 */
	protected static array $requiredParams = [
		'PAGE_URL_EDIT', 'CATEGORY_ID',
		'VAR_STEP_ID', 'VAR_DOC_ID',
		'CRM_ENTITY_TYPE_ID',
		'OPEN_URL_AFTER_CLOSE',
	];

	private int $userId;
	private ?\Bitrix\Sign\Item\Document $documentItem = null;

	/**
	 * Returns true if SMS is allowed by tariff.
	 * @return bool
	 */
	public function isSmsAllowed(): bool
	{
		return \Bitrix\Sign\Restriction::isSmsAllowed();
	}

	/**
	 * @return bool
	 */
	private function isHcmlinkAvailable(): bool
	{
		return \Bitrix\Sign\Service\Container::instance()->getHcmLinkService()->isAvailable();
	}

	/**
	 * Executing before actions.
	 * @return void
	 */
	protected function beforeActions(): void
	{
		$this->userId = (int)(\Bitrix\Main\Engine\CurrentUser::get()->getId() ?? 0);
		$document = $this->getDocument();
		$this->setResult('DOCUMENT', $document);
		$this->setResult('TEMPLATE_UID', $this->getTemplateUid());
		$this->setResult('MAX_DOCUMENT_COUNT', GroupService::MAX_DOCUMENT_COUNT);
		$this->setResult('RESPONSIBLE_NAME', $this->getResponsibleName($document));
		$this->setResult('CHAT_ID', $this->getChatId());
	}

	private function getDocument(): ?Document
	{
		$document = $this->getResult('DOCUMENT');
		if ($document !== null)
		{
			return $document;
		}

		$entityType = $this->getStringParam('ENTITY_TYPE_ID');
		$entityId = (int)$this->getRequest($this->getStringParam('VAR_DOC_ID'));

		$document = $entityId ? Document::resolveByEntity($entityType, $entityId) : null;
		if ($document !== null)
		{
			return $document;
		}

		$templateId = (int)$this->getRequest(self::TEMPLATE_ID_URL_PARAMETER_NAME);
		if ($templateId < 1)
		{
			return null;
		}

		$documentRepository = Container::instance()->getDocumentRepository();
		$document = $documentRepository->getByTemplateId((int)$templateId);
		if ($document?->id === null)
		{
			return null;
		}

		return Document::getById($document->id);
	}

	/**
	 * Executes component.
	 * @return void
	 */
	public function exec(): void
	{
		if (
			$this->getParam('SCENARIO') === \Bitrix\Sign\Type\BlankScenario::B2E
			&& !Storage::instance()->isB2eAvailable()
		)
		{
			showError('access denied');

			return;
		}
		$documentRepository = Container::instance()->getDocumentRepository();

		/** @var Document $document */
		$document = $this->getResult('DOCUMENT');

		if ($document && $this->getStringParam('OPEN_URL_AFTER_CLOSE'))
		{
			$this->setResult(
				'OPEN_URL_AFTER_CLOSE',
				str_replace('#id#', $document->getEntityId(), $this->getStringParam('OPEN_URL_AFTER_CLOSE')),
			);
		}

		$currentDomain = Storage::instance()->getSavedDomain();
		if ($currentDomain === null)
		{
			$currentDomain = Application::getServer()->getHttpHost();
			Container::instance()->getApiClientDomainService()->change(
				new DomainRequest($currentDomain),
			);
			Storage::instance()->setCurrentDomain($currentDomain);
		}

		$documentItem = ($document && $document->getUid())
			? $documentRepository->getByUid($document->getUid())
			: null;
		$this->setResult('SCENARIO', $this->getScenario());
		$this->setResult('WIZARD_CONFIG', $this->getWizardConfig());
		$this->setResult('STAGE_ID', $document?->getStageId());
		$this->setResult('DOCUMENT_MODE', $this->getDocumentMode());
		$this->setResult('INITIATED_BY_TYPE', $this->getInitiatedByType($documentItem)->value);
		$this->setResult('BLANKS', $this->getBlanks());
		$this->setResult('IS_MASTER_PERMISSIONS_FOR_USER_DENIED', $this->isMasterPermissionsForUserDenied($documentItem));
		$isSesComAgreementAccepted = $this->isSesComAgreementAccepted();
		$this->setResult('IS_SES_COM_AGREEMENT_ACCEPTED', $isSesComAgreementAccepted);
		$this->setResult('ANALYTIC_CONTEXT', $this->getAnalyticContext());
		if (!$isSesComAgreementAccepted)
		{
			$dateFormat = \Bitrix\Main\Application::getInstance()->getContext()->getCulture()->getDateFormat();
			$agreementDate = (new \Bitrix\Main\Type\Date())
				->setDate(self::SES_COM_AGREEMENT_DATE_YEAR, self::SES_COM_AGREEMENT_DATE_MONTH, self::SES_COM_AGREEMENT_DATE_DAY)
				->format(\Bitrix\Main\Type\Date::convertFormatToPhp($dateFormat))
			;
			$this->setResult('SES_COM_AGREEMENT_DATE', $agreementDate);
		}

		$this->resetParamsAsString(self::$requiredParams);

		if (
			$this->isHcmlinkAvailable()
			&& \Bitrix\Main\Loader::includeModule('pull')
		)
		{
			\CPullWatch::Add($this->userId, 'humanresources_person_mapping');
		}
	}

	private function getWizardConfig(): array
	{
		$storage = \Bitrix\Sign\Config\Storage::instance();

		$regionCode = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();

		$blankSelectorConfig = (new \Bitrix\Sign\Config\Ui\BlankSelector())->create(
			$this->getScenario(),
			$regionCode,
			$this->getB2eRegionDocumentTypes(),
		);

		$b2eTariffInstance = \Bitrix\Sign\Integration\Bitrix24\B2eTariff::instance();
		$b2eFeatureConfig = [
			'hcmLinkAvailable' => $this->isHcmlinkAvailable(),
		];

		return [
			'b2eFeatureConfig' => $b2eFeatureConfig,
			'blankSelectorConfig' => $blankSelectorConfig,
			'documentSendConfig' => [
				'region' => $regionCode,
				'languages' => $storage->getLanguages(),
			],
			'userPartyConfig' => [
				'region' => $regionCode,
				'b2eSignersLimitCount' => $b2eTariffInstance->getB2eSignersCountLimitWithUnlimitCheck(),
			],
		];
	}

	private function getResponsibleName(?Document $document): string
	{
		$responsibleName = $document?->getInitiatorName();

		if ($responsibleName !== null)
		{
			return $responsibleName;
		}

		$lastUserDocuments = $this->getLastUserDocuments(
			User::getInstance()->getId(),
			5,
		);

		foreach ($lastUserDocuments as $userDocument)
		{
			if ($userDocument === null)
			{
				continue;
			}

			$initiatorName = $userDocument->getInitiatorName();
			if ($initiatorName === null)
			{
				continue;
			}

			if ($document === null || $userDocument->getId() !== $document->getId())
			{
				return $initiatorName;
			}
		}

		return User::getCurrentUserName();
	}

	/**
	 * @param int $userId
	 * @param int $amount
	 *
	 * @return array<?Document>
	 */
	private function getLastUserDocuments(int $userId, int $amount): array
	{
		$rows = DocumentTable::query()
			->addSelect('*')
			->where('CREATED_BY_ID', $userId)
			->addOrder('DATE_CREATE', 'DESC')
			->setLimit($amount)
			->fetchAll()
		;

		return array_map(
			static fn (array $row) => Document::tryCreateByRow($row),
			$rows,
		);
	}

	private function isDomainChanged($currentDomain): bool
	{
		return $currentDomain !== Application::getServer()->getHttpHost();
	}

	private function isUnsecuredScheme(): bool
	{
		return !Context::getCurrent()->getRequest()->isHttps();
	}

	/**
	 * @return \Bitrix\Sign\Type\BlankScenario::*
	 */
	private function getScenario(): string
	{
		$scenario = $this->getParam('SCENARIO') ?? $this->getRequest('scenario');
		if (!in_array($scenario, \Bitrix\Sign\Type\BlankScenario::getAll(), true))
		{
			return \Bitrix\Sign\Type\BlankScenario::B2B;
		}

		return $scenario;
	}

	private function isMasterPermissionsForUserDenied(?\Bitrix\Sign\Item\Document $documentItem): bool
	{
		$userId = \Bitrix\Main\Engine\CurrentUser::get()->getId();
		$accessController = new \Bitrix\Sign\Access\AccessController($userId);

		foreach ($this->getRequiredPermissions($this->getScenario(), $this->getDocumentMode()) as $permission)
		{
			$passed = $documentItem ? $accessController->checkByItem($permission, $documentItem) : $accessController->check($permission);
			if (!$passed)
			{
				return true;
			}
		}

		return false;
	}

	private function getBlanks(): array
	{
		return Container::instance()
			->getBlankRepository()
			->getPublicList(scenario: $this->getScenario())
			->toArray()
		;
	}

	/**
	 * @return array
	 */
	public function getRequiredPermissions(string $scenario, string $documentMode = self::MODE_DOCUMENT): array
	{
		if ($scenario === \Bitrix\Sign\Type\BlankScenario::B2B)
		{
			return [
				\Bitrix\Sign\Access\ActionDictionary::ACTION_DOCUMENT_ADD,
				\Bitrix\Sign\Access\ActionDictionary::ACTION_DOCUMENT_EDIT,
			];
		}

		return match ($documentMode)
		{
			self::MODE_DOCUMENT => [
				\Bitrix\Sign\Access\ActionDictionary::ACTION_B2E_DOCUMENT_ADD,
				\Bitrix\Sign\Access\ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			],
			self::MODE_TEMPLATE => [
				\Bitrix\Sign\Access\ActionDictionary::ACTION_B2E_TEMPLATE_ADD,
				\Bitrix\Sign\Access\ActionDictionary::ACTION_B2E_TEMPLATE_EDIT,
			],
			default => [],
		};
	}

	protected function getB2eRegionDocumentTypes(): array
	{
		if ($this->getScenario() !== \Bitrix\Sign\Type\BlankScenario::B2E)
		{
			return [];
		}

		$regionCode = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();

		return Container::instance()
			->getRegionDocumentTypeRepository()
			->listByRegionCode($regionCode)
			->toArray()
		;
	}

	private function isSesComAgreementAccepted(): bool
	{
		$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();
		if (
			($region === 'ru')
			|| ($this->getScenario() === \Bitrix\Sign\Type\DocumentScenario::SCENARIO_TYPE_B2B)
		)
		{
			return true;
		}

		$agreementOptions = \CUserOptions::GetOption('sign', 'sign-agreement', null);

		return is_array($agreementOptions)
			&& isset($agreementOptions['decision'])
			&& $agreementOptions['decision'] === 'Y';
	}

	private function getDocumentMode(): string
	{
		$mode = self::MODE_DOCUMENT;

		if (!Feature::instance()->isSendDocumentByEmployeeEnabled())
		{
			return $mode;
		}

		$modeFromRequest = (string)$this->getRequest('mode');

		if (in_array($modeFromRequest, [self::MODE_TEMPLATE, self::MODE_DOCUMENT], true))
		{
			$mode = $modeFromRequest;
		}

		return $mode;
	}

	private function getInitiatedByType(?\Bitrix\Sign\Item\Document $document): InitiatedByType
	{
		if ($document?->initiatedByType !== null)
		{
			return $document->initiatedByType;
		}

		return $this->getDocumentMode() === self::MODE_TEMPLATE
			? InitiatedByType::EMPLOYEE
			: InitiatedByType::COMPANY;
	}

	private function getTemplateUid(): ?string
	{
		$templateId = (int)$this->getRequest(self::TEMPLATE_ID_URL_PARAMETER_NAME);
		if ($templateId < 1)
		{
			return null;
		}

		$template = Container::instance()->getDocumentTemplateRepository()->getById($templateId);

		return $template?->uid;
	}

	private function getChatId(): int
	{
		if (!Feature::instance()->isCollabIntegrationEnabled())
		{
			return 0;
		}

		$imService = Container::instance()->getImService();
		$chatIdFromRequest = $this->getChatIdFromRequest();
		if ($chatIdFromRequest < 1)
		{
			return 0;
		}

		$collabChat = $imService->getCollabById($chatIdFromRequest);
		$userId = (int)CurrentUser::get()->getId();
		if ($collabChat && $imService->isUserHaveAccessToChat($collabChat, $userId))
		{
			return (int)$collabChat->getId();
		}

		return 0;
	}

	private function getChatIdFromRequest(): int
	{
		return (int)$this->getRequest('chat_id');
	}

	private function getAnalyticContext(): ?array
	{
		$scenario = $this->getScenario();

		if (
			$scenario === \Bitrix\Sign\Type\BlankScenario::B2B
			&& Feature::instance()->isCollabIntegrationEnabled()
		)
		{
			if ($this->getChatId() >= 1 || $this->getDocumentItem()?->chatId > 0)
			{
				return [
					'category' => 'documents',
					'type' => 'b2b',
					'c_section' => 'collab',
					'c_element' => 'chat_textarea',
				];
			}

			return [
				'category' => 'documents',
				'type' => 'b2b',
				'c_section' => 'sign',
				'c_element' => 'create_button',
			];
		}

		if ($scenario !== \Bitrix\Sign\Type\BlankScenario::B2E)
		{
			return null;
		}

		if ($this->getDocumentMode() === self::MODE_TEMPLATE)
		{
			return [
				'c_section' => 'sign',
				'category' => 'templates',
				'c_sub_section' => 'templates',
				'c_element' => 'create_button',
			];
		}

		return ['c_section' => 'sign', 'type' => 'from_company', 'category' => 'documents'];
	}

	private function getDocumentItem(): ?\Bitrix\Sign\Item\Document
	{
		if ($this->documentItem !== null)
		{
			return $this->documentItem;
		}

		$document = $this->getResult('DOCUMENT');
		if ($document === null)
		{
			return null;
		}

		$documentRepository = Container::instance()->getDocumentRepository();
		$this->documentItem = $documentRepository->getByUid($document->getUid());

		return $this->documentItem;
	}
}
