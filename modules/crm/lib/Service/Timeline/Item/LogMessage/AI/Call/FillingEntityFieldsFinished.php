<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\AI\Call;

use Bitrix\Main\Localization\Loc;

final class FillingEntityFieldsFinished extends Base
{
	public function getType(): string
	{
		return 'FillingEntityFieldsFinished';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_FILLING_FIELDS_FINISHED');
	}
}
