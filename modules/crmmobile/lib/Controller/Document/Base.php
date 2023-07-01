<?php

namespace Bitrix\CrmMobile\Controller\Document;

use Bitrix\CrmMobile\Controller\PrimaryAutoWiredEntity;
use Bitrix\CrmMobile\Controller\PublicErrorsTrait;
use Bitrix\CrmMobile\ProductGrid\ProductGridDocumentQuery;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;

abstract class Base extends Controller
{
	use PublicErrorsTrait;
	use PrimaryAutoWiredEntity;

	protected function markErrors(): void
	{
		if (!empty($this->getErrors()))
		{
			$errors = $this->getErrors();
			$this->addErrors($this->markErrorsAsPublic($errors));
		}
	}

	protected function processAfterAction(Action $action, $result)
	{
		$this->markErrors();
	}

	public function getDocumentDataAction(int $documentId): array
	{
		return [
			'grid' => (new ProductGridDocumentQuery($documentId))->execute(),
		];
	}
}