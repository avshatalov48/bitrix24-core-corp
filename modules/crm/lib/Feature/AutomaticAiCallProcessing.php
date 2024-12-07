<?php

namespace Bitrix\Crm\Feature;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Feature\Category\BaseCategory;
use Bitrix\Crm\Feature\Category\Activities;

class AutomaticAiCallProcessing extends BaseFeature
{
	public function getName(): string
	{
		return Loc::getMessage('AUTOMATIC_AI_CALL_PROCESS_NAME');
	}

	public function getCategory(): BaseCategory
	{
		return Activities::getInstance();
	}

	protected function getOptionName(): string
	{
		return 'AI_CALL_PROCESSING_ALLOWED_AUTO_V2';
	}

	protected function getEnabledValue(): mixed
	{
		return true;
	}
}
