<?php
namespace Bitrix\Crm\Integration\Landing;

use Bitrix\Main\Loader;
use Bitrix\Landing;

/**
 * Manage landing pages for requisites
 */
class RequisitesLanding
{
	protected const LANDING_TEMPLATE_NAME = 'requisites';
	protected const LANDING_TEMPLATE_PAGE_NAME = 'requisites/main';

	protected int $companyId;
	protected int $requisiteId;
	protected int $bankRequisiteId;
	protected bool $isEnabled = false;

	/**
	 * Connected landing
	 */
	protected ?int $siteId = null;
	protected ?int $landingId = null;
	protected ?Landing\Landing $landing = null;

	public function __construct(int $companyId, int $requisiteId = 0, int $bankRequisiteId = 0)
	{
		$this->companyId = $companyId;
		$this->requisiteId = $requisiteId;
		$this->bankRequisiteId = $bankRequisiteId;
		$this->isEnabled = Loader::includeModule('landing');
		if ($this->isEnabled)
		{
			$this->findConnectedSite();
			$this->findConnectedLanding();
		}
	}

	public function isEnabled(): bool
	{
		return $this->isEnabled;
	}

	protected function findConnectedSite(): void
	{
		if ($this->siteId === null)
		{
			Landing\Rights::setOff();
			$site = Landing\Site::getList([
				'select' => ['ID', 'ACTIVE', 'XML_ID'],
				'filter' => [
					'=TPL_CODE' => self::LANDING_TEMPLATE_NAME,
					'=DELETED' => 'N',
				],
			])->fetch();

			if ($site)
			{
				$this->siteId = $site['ID'];
			}

			Landing\Rights::setOn();
		}
	}

	protected function findConnectedLanding(): void
	{
		if (!$this->isSiteConnected())
		{
			return;
		}

		if ($this->landingId === null)
		{
			Landing\Rights::setOff();
			$landingData = Landing\Landing::getList([
				'select' => ['ID'],
				'filter' => [
					'=TPL_CODE' => self::LANDING_TEMPLATE_PAGE_NAME,
					'=XML_ID' => $this->getLandingXmlId(),
				],
			])->fetch();

			if ($landingData)
			{
				$landing = Landing\Landing::createInstance($landingData['ID']);
				if ($landing->exist())
				{
					$this->landingId = $landingData['ID'];
					$this->landing = $landing;
				}
			}

			Landing\Rights::setOn();
		}
	}

	protected function getLandingXmlId(): string
	{
		return self::LANDING_TEMPLATE_NAME . "_{$this->companyId}_{$this->requisiteId}_{$this->bankRequisiteId}|" . self::LANDING_TEMPLATE_PAGE_NAME;
	}

	public function connectLanding(): bool
	{
		if (!$this->isEnabled())
		{
			return false;
		}

		if ($this->isLandingConnected())
		{
			return true;
		}

		if (!$this->isSiteConnected())
		{
			return $this->connectSite();
		}

		return $this->addLandingToConnectedSite();
	}

	protected function connectSite(): bool
	{
		if ($this->isSiteConnected())
		{
			return true;
		}

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->addEventHandler('landing', 'onAfterDemoCreate',
			function(\Bitrix\Main\Event $event)
			{
				Landing\Rights::setOff();

				$siteId = $event->getParameter('id');
				$firstLanding = Landing\Landing::getList([
					'select' => ['ID'],
					'filter' => [
						'=SITE_ID' => $siteId,
					],
				])->fetch();
				if ($firstLanding)
				{
					Landing\Landing::update($firstLanding['ID'], [
						'XML_ID' => $this->getLandingXmlId(),
					]);
				}

				Landing\Rights::setOn();
			}
		);

		Landing\Rights::setOff();
		$componentName = 'bitrix:landing.demo';
		$className = \CBitrixComponent::includeComponentClass($componentName);
		/** @var \LandingSiteDemoComponent $demoCmp */
		$demoCmp = new $className;
		$demoCmp->initComponent($componentName);
		$demoCmp->arParams = [
			'TYPE' => 'PAGE',
			'SITE_ID' => 0,
			'FOLDER_ID' => 0,
			'DISABLE_REDIRECT' => 'Y',
			'SITE_WORK_MODE' => 'N',
			'DONT_LEAVE_FRAME' => 'N',
			'BINDING_TYPE' => '',
			'PREPARE_BLOCKS' => true,
			'PREPARE_BLOCKS_DATA' => $this->getPrepareBlockData(),
		];
		$success = $demoCmp->actionSelect(self::LANDING_TEMPLATE_NAME);
		Landing\Rights::setOn();

		if ($success)
		{
			$this->findConnectedSite();
			$this->findConnectedLanding();

			if ($this->isLandingConnected())
			{
				return $this->isPublicationAvailable() && $this->landing->publication();
			}
		}

		return false;
	}

	protected function addLandingToConnectedSite(): bool
	{
		if (!$this->isSiteConnected())
		{
			return false;
		}

		if ($this->isLandingConnected())
		{
			return true;
		}

		$result = Landing\Landing::addByTemplate($this->siteId, self::LANDING_TEMPLATE_PAGE_NAME, [
			'SITE_TYPE' => 'PAGE',
			'XML_ID' => $this->getLandingXmlId(),
			'PREPARE_BLOCKS' => true,
			'PREPARE_BLOCKS_DATA' => $this->getPrepareBlockData(),
		]);

		if ($result->isSuccess() && $result->getId())
		{
			Landing\Landing::update($result->getId(), [
				'XML_ID' => $this->getLandingXmlId(),
			]);

			$this->landingId = $result->getId();
			$this->landing = Landing\Landing::createInstance($this->landingId);

			return $this->isPublicationAvailable() && $this->landing->publication();
		}

		return false;
	}

	protected function isSiteConnected(): bool
	{
		return (bool)$this->siteId;
	}

	public function isLandingConnected(): bool
	{
		return
			$this->landingId
			&& $this->landing
			&& $this->landing->exist()
		;
	}

	public function isPublicationAvailable(): bool
	{
		if ($this->isEnabled())
		{
			$res = Landing\Manager::checkFeature(
				Landing\Manager::FEATURE_PUBLICATION_SITE,
				['type' => 'PAGE',]
			);

			return $res;
		}

		return false;
	}

	public function isLandingPublic(): bool
	{
		if (!$this->isLandingConnected())
		{
			return false;
		}

		$meta = $this->landing->getMeta();

		return $meta['PUBLIC'] === 'Y';
	}

	public function getLandingPublicUrl(): ?string
	{
		if (!$this->isLandingConnected())
		{
			return null;
		}

		return $this->landing->getPublicUrl();
	}

	public function getLandingEditUrl(): ?string
	{
		if (!$this->isLandingConnected())
		{
			return null;
		}

		return Landing\Domain::getHostUrl() . "/sites/site/{$this->siteId}/view/{$this->landingId}/";
	}

	protected function getPrepareBlockData(): array
	{
		return [
			'69.1.contacts' => [
				'ACTION' => 'changeComponentParams',
				'PARAMS' => [
					'REQUISITE' => $this->companyId . '_' . $this->requisiteId,
					'PRIMARY_ICON' => 'Y',
				],
			],
			'69.2.requisites' => [
				'ACTION' => 'changeComponentParams',
				'PARAMS' => [
					'REQUISITE' => $this->companyId . '_' . $this->requisiteId,
				],
			],
			'69.3.bank_requisites' => [
				'ACTION' => 'changeComponentParams',
				'PARAMS' => [
					'BANK_REQUISITE' => $this->companyId . '_' . $this->bankRequisiteId,
				],
			],
		];
	}
}
