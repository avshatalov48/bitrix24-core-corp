<?php

namespace Bitrix\Tasks\Replicator\Template;

abstract class AbstractParameter
{
	public function __construct(protected RepositoryInterface $repository)
	{
	}

	abstract public function getData();

	public function get(string $fieldName)
	{
		$data = $this->getData();
		return $data[$fieldName] ?? null;
	}
}