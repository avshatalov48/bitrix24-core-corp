<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\AI\Call;

use Bitrix\Main\Localization\Loc;

final class FillingEntityFieldsStarted extends Base
{
	public function getType(): string
	{
		return 'FillingEntityFieldsStarted';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_FILLING_FIELDS_STARTED');
	}
}
