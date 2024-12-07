<?php

namespace Bitrix\Crm\Tour\Sign;

use Bitrix\Crm\Tour\Base;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Type\Document\EntityType;
use Bitrix\Sign\Type\DocumentStatus;

final class SignB2eDocumentProcess extends Base
{
	protected const OPTION_NAME = 'signB2e-document-process';

	private ?int $entityTypeId = null;
	private ?int $entityId = null;


	protected function canShow(): bool
	{
		return (
			!$this->isUserSeenTour()
			&& ServiceLocator::getInstance()->get('crm.integration.sign')::isEnabled()
			&& $this->entityTypeId === \CCrmOwnerType::SmartB2eDocument
			&& $this->entityId > 0
			&& \Bitrix\Crm\Settings\Crm::isDocumentSigningEnabled()
			&& $this->isDocumentInProcess()
		);
	}

	protected function getSteps(): array
	{
		return [
			[
				'id' => 'signB2e-document-process-button',
				'title' => Loc::getMessage('CRM_TOUR_TIMLINE_SIGNING_PROCESS_TITLE'),
				'text' => Loc::getMessage('CRM_TOUR_TIMLINE_SIGNING_PROCESS_TEXT'),
				'position' => 'top',
				'target' => '#signB2e-document-process-button',
			],
		];
	}

	protected function getOptions(): array
	{
		return [
			'hideTourOnMissClick' => true,
			'steps' => [
				'popup' => [
					'width' => 400,
				],
			],
		];
	}

	public function setEntityTypeId(?int $entityTypeId): self
	{
		$this->entityTypeId = $entityTypeId;

		return $this;
	}

	public function setEntityId(?int $entityId): self
	{
		$this->entityId = $entityId;

		return $this;
	}

	private function isDocumentInProcess(): bool
	{
		if (!Loader::includeModule('sign'))
		{
			return false;
		}

		$document = \Bitrix\Sign\Service\Container::instance()
			->getDocumentRepository()
			->getByEntityIdAndType($this->entityId, EntityType::SMART_B2E)
		;

		return $document && $document->status === DocumentStatus::SIGNING;
	}

}
