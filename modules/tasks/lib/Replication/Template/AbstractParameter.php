<?php

namespace Bitrix\Tasks\Replication\Template;

use Bitrix\Tasks\Replication\RepositoryInterface;

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