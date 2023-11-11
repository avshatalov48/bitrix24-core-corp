<?php

namespace Bitrix\Tasks\Replicator\Template;

abstract class Parameter
{

	public function __construct(protected Repository $repository)
	{
	}

	abstract public function getData();

	public function get(string $fieldName)
	{
		$data = $this->getData();
		return $data[$fieldName] ?? null;
	}
}