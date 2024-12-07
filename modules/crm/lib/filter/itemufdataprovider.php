<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Component\EntityList\UserField\GridHeaders;
use Bitrix\Main\Application;
use Bitrix\Main\Filter\EntitySettings;

class ItemUfDataProvider extends UserFieldDataProvider
{
	private \CCrmUserType $userType;

	public function __construct(EntitySettings $settings)
	{
		parent::__construct($settings);

		$this->userType = new \CCrmUserType(Application::getUserTypeManager(), $this->getUserFieldEntityID());
	}

	public function getGridColumns(): array
	{
		$result = [];

		$headers =
			(new GridHeaders($this->userType))
				->setWithEnumFieldValues(false)
				->setWithHtmlSpecialchars(false)
		;

		$headers->append($result);

		return $result;
	}
}
