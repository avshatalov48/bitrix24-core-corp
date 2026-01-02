<?php

namespace Bitrix\Crm\Service\Timeline\Item\Compatible;

use Bitrix\Crm\Component\Utils\JsonCompatibleConverter;
use Bitrix\Crm\Service\JsonCompatible;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item;

abstract class Compatible extends Item
{
	protected array $data = [];

	public function __construct(Context $context, \Bitrix\Crm\Service\Timeline\Item\Compatible\Model $model)
	{
		parent::__construct($context, $model);
		$this->data = $this->initializeData($model->getData());
	}

	public function jsonSerialize(): array
	{
		return $this->applyTypeCompatibility($this->data); // to be compatible with CUtil::PhpToJSObject() format
	}

	protected function initializeData(array $data): array
	{
		return $data;
	}

	protected function applyTypeCompatibility(array $data): array
	{
		$data = JsonCompatibleConverter::convert($data);

		if (isset($data['sort']) && is_array($data['sort']))
		{
			$data['sort'] = array_map('intval', $data['sort']);
		}

		return $data;
	}

	public function getSort(): array
	{
		return array_map('intval', $this->data['sort'] ?? []);
	}
}
