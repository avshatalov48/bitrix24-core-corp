<?php

namespace Bitrix\Crm\Service\Timeline;

use Bitrix\Crm\Service\Timeline\Item\Model;

abstract class Item implements \JsonSerializable
{
	protected Context $context;
	protected Model $model;

	public function __construct(Context $context, Model $model)
	{
		$this->context = $context;
		$this->model = $model;
	}

	public function getContext(): Context
	{
		return $this->context;
	}

	public function getModel(): Model
	{
		return $this->model;
	}

	abstract public function getSort(): array;
}
