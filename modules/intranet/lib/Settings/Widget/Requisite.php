<?php

namespace Bitrix\Intranet\Settings\Widget;

use Bitrix\Intranet\Settings\Requisite\CompanyList;
use Bitrix\Crm\Integration\Landing\RequisitesLanding;
use Bitrix\Main\Loader;

class Requisite
{
	private static Requisite $instance;
	private ?RequisitesLanding $requisites = null;
	private int|string $companyId;
	private int $requisiteId = 0;

	private function __construct()
	{
		if (Loader::includeModule('crm'))
		{
			$this->initRequisites();
		}
	}

	private function initRequisites(): void
	{
		$companyId = \Bitrix\Crm\Requisite\EntityLink::getDefaultMyCompanyId();

		if ($companyId)
		{
			$company = new CompanyList(['=ID' => $companyId], ['DATE_CREATE' => 'DESC'], ['ID'], ['ID', 'ENTITY_ID']);
			$this->requisites = $company->getLandingList()->toArray()[$companyId] ?? null;
			$requisite = $company->getRequisiteList()->getByCompanyId($companyId);
			$this->requisiteId = $requisite['ID'] ?? 0;
		}

		$this->companyId = $companyId ?? 0;
	}

	public static function getInstance(): static
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function getRequisitesData(): ?array
	{
		if ($this->requisites)
		{
			return [
				'isCompanyCreated' => true,
				'companyId' => $this->companyId,
				'requisiteId' => $this->requisiteId,
				'isConnected' => $this->requisites->isLandingConnected(),
				'isPublic' => $this->requisites->isLandingPublic(),
				'publicUrl' => $this->requisites->getLandingPublicUrl(),
				'editUrl' => $this->requisites->getLandingEditUrl(),
			];
		}

		return [
			'isCompanyCreated' => false,
		];
	}

	public function getRequisites(): ?RequisitesLanding
	{
		return $this->requisites;
	}
}