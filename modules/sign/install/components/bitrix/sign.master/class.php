<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Access\Model\UserModel;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService;
use Bitrix\Sign\Blank;
use Bitrix\Sign\Config\Feature;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Document;
use Bitrix\Sign\Error;
use Bitrix\Sign\File;
use Bitrix\Sign\Internal\DocumentTable;
use Bitrix\Sign\Item\Api\Client\DomainRequest;
use Bitrix\Sign\Main\Application;
use Bitrix\Sign\Main\User;
use Bitrix\Sign\Service\Container;

\CBitrixComponent::includeComponentClass('bitrix:sign.base');

class SignMasterComponent extends SignBaseComponent
{
	/**
	 * Restricted size for images.
	 */
	private const IMAGE_SIZES = [
		'width' => 1275,
		'height' => 1650
	];

	const SES_COM_AGREEMENT_DATE_YEAR = 2024;
	const SES_COM_AGREEMENT_DATE_DAY = 1;
	const SES_COM_AGREEMENT_DATE_MONTH = 3;
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
		'OPEN_URL_AFTER_CLOSE'
	];

	/**
	 * Returns true if SMS is allowed by tariff.
	 * @return bool
	 */
	public function isSmsAllowed(): bool
	{
		return \Bitrix\Sign\Restriction::isSmsAllowed();
	}

	/**
	 * Executing before actions.
	 * @return void
	 */
	protected function beforeActions(): void
	{
		$document = $this->getResult('DOCUMENT');

		if (!$document)
		{
			$entityType = $this->getStringParam('ENTITY_TYPE_ID');
			$entityId = $this->getRequest($this->getStringParam('VAR_DOC_ID'));
			if ($entityId)
			{
				$document = Document::resolveByEntity($entityType, $entityId);
				$this->setResult('DOCUMENT', $document);
			}
		}

		$this->setResult('RESPONSIBLE_NAME', $this->getResponsibleName($document));
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

		/** @var Document $document */
		$document = $this->getResult('DOCUMENT');

		if ($document && $this->getStringParam('OPEN_URL_AFTER_CLOSE'))
		{
			$this->setResult(
				'OPEN_URL_AFTER_CLOSE',
				str_replace('#id#', $document->getEntityId(), $this->getStringParam('OPEN_URL_AFTER_CLOSE'))
			);
		}

		$currentDomain = Storage::instance()->getSavedDomain();
		if ($currentDomain === null)
		{
			$currentDomain = Application::getServer()->getHttpHost();
			Container::instance()->getApiClientDomainService()->change(
				(new DomainRequest($currentDomain))
			);
			Storage::instance()->setCurrentDomain($currentDomain);
		}
		$this->setResult('SCENARIO', $this->getScenario());
		$this->setResult('WIZARD_CONFIG', $this->getWizardConfig());
		$this->setResult('STAGE_ID', $document?->getStageId());
		$this->setResult('DOCUMENT_MODE', $this->getDocumentMode());
		$this->setResult('BLANKS', $this->getBlanks());
		$this->setResult('IS_MASTER_PERMISSIONS_FOR_USER_DENIED', $this->isMasterPermissionsForUserDenied($document));
		$isSesComAgreementAccepted = $this->isSesComAgreementAccepted();
		$this->setResult('IS_SES_COM_AGREEMENT_ACCEPTED', $isSesComAgreementAccepted);
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
	}

	private function getWizardConfig(): array
	{
		$storage = \Bitrix\Sign\Config\Storage::instance();

		$regionCode = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();

		$blankSelectorConfig = (new \Bitrix\Sign\Config\Ui\BlankSelector())->create(
			$this->getScenario(),
			$regionCode,
			$this->getB2eRegionDocumentTypes()
		);

		$b2eTariffInstance = \Bitrix\Sign\Integration\Bitrix24\B2eTariff::instance();
		return [
			'blankSelectorConfig' => $blankSelectorConfig,
			'documentSendConfig' => [
				'region' => $regionCode,
				'languages' => $storage->getLanguages(),
			],
			'userPartyConfig' => [
				'region' => $regionCode,
				'b2eSignersLimitCount' => $b2eTariffInstance->getB2eSignersCountLimitWithUnlimitCheck(),
			]
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
			5
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
		$rows = DocumentTable
			::query()
			->addSelect('*')
			->where('CREATED_BY_ID', $userId)
			->addOrder('DATE_CREATE', 'DESC')
			->setLimit($amount)
			->fetchAll()
		;

		return array_map(
			static fn (array $row) => Document::tryCreateByRow($row),
			$rows
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

	private function isMasterPermissionsForUserDenied(?Document $document): bool
	{
		$userId = \Bitrix\Main\Engine\CurrentUser::get()->getId();
		$accessController = new \Bitrix\Sign\Access\AccessController($userId);
		$item = ($document && $document->getUid())
			? Container::instance()->getDocumentRepository()->getByUid($document->getUid())
			: null;

		foreach ($this->getRequiredPermissions() as $permission)
		{
			$passed = $item ? $accessController->checkByItem($permission, $item) : $accessController->check($permission);
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
			->toArray();
	}

	/**
	 * @return array
	 */
	public function getRequiredPermissions(): array
	{
		if ($this->getScenario() === \Bitrix\Sign\Type\BlankScenario::B2B)
		{
			return [
				\Bitrix\Sign\Access\ActionDictionary::ACTION_DOCUMENT_ADD,
				\Bitrix\Sign\Access\ActionDictionary::ACTION_DOCUMENT_EDIT,
			];
		}
		else
		{
			return [
				\Bitrix\Sign\Access\ActionDictionary::ACTION_B2E_DOCUMENT_ADD,
				\Bitrix\Sign\Access\ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			];
		}
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
			&& $agreementOptions['decision'] === 'Y'
		;
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
}
