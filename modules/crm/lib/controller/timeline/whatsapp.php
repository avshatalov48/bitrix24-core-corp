<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar;
use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Main\Engine\ActionFilter\ContentType;
use Bitrix\Main\Engine\ActionFilter\Scope;

final class WhatsApp extends Base
{
	protected function getDefaultPreFilters(): array
	{
		$filters = parent::getDefaultPreFilters();

		$filters[] = new Scope(Scope::AJAX);
		$filters[] = new ContentType([ContentType::JSON]);

		return $filters;
	}

	public function getConfigAction(int $entityTypeId, int $entityId): ?array
	{
		if ($entityTypeId <= 0 || $entityId <= 0)
		{
			$this->addError(ErrorCode::getOwnerNotFoundError());

			return null;
		}

		$whatsApp = new TimelineMenuBar\Item\WhatsApp(
			new TimelineMenuBar\Context($entityTypeId, $entityId)
		);
		$provider = $whatsApp->getProvider();
		if (!$provider || !$whatsApp->isAvailable())
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		return [
			'communications' => (new TimelineMenuBar\Communications($entityTypeId, $entityId))->get(),
			'provider' => $provider,
		];
	}
}
