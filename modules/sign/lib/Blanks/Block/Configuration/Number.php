<?php

namespace Bitrix\Sign\Blanks\Block\Configuration;

use Bitrix\Sign\Blanks\Block\Configuration;
use Bitrix\Sign\Document;
use Bitrix\Sign\Item;

class Number extends Configuration
{
	private Document\Entity\Factory $documentEntityFactory;

	public function __construct(
		?Document\Entity\Factory $documentEntityFactory = null,
	)
	{
		$this->documentEntityFactory = $documentEntityFactory ?? new Document\Entity\Factory();
	}

	public function loadData(Item\Block $block, Item\Document $document, ?Item\Member $member = null): array
	{
		return [
			'text' => $this->documentEntityFactory->create($document->entityType, $document->entityId)?->getNumber(),
		];
	}

	public function getViewSpecificData(Item\Block $block): ?array
	{
		return [
			'crmNumeratorUrl' => \Bitrix\Sign\Integration\CRM::getNumeratorUrl(),
		];
	}
}