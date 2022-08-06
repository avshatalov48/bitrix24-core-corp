<?php

namespace Bitrix\Crm\UserField;

use CComponentEngine;
use COption;

class Router
{
	/**
	 * @var string
	 */
	private $entityId;

	public function __construct(string $entityId)
	{
		$this->entityId = $entityId;
	}

	public function getEditUrl(int $fieldId = 0): string
	{
		if($fieldId <= 0)
		{
			$fieldId = 0;
		}

		return CComponentEngine::MakePathFromTemplate(
			COption::GetOptionString('crm', 'path_to_user_field_edit'),
			[
				'entity_id' => $this->entityId,
				'field_id'  => $fieldId
			]
		);
	}

	public function getEditUrlByCategory(int $categoryId): string
	{
		return sprintf(
			'%s?category_id=%d',
			$this->getEditUrl(),
			$categoryId
		);
	}
}
