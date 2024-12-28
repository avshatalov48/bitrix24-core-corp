<?php

namespace Bitrix\BIConnector\ExternalSource\Source;

use Bitrix\Main;

abstract class Base
{
	protected int $id;

	/**
	 * @param int $id source id
	 */
	public function __construct(int $id)
	{
		$this->id = $id;
	}

	/**
	 * Connects to external source
	 */
	abstract public function connect(string $host, string $username, string $password): Main\Result;

	/**
	 * @return array
	 */
	abstract public function getEntityList(): array;

	/**
	 * @param string $entityName
	 * @return array
	 */
	abstract public function getDescription(string $entityName): array;

	/**
	 * @param string $entityName
	 * @param int $n
	 * @return array
	 */
	abstract public function getFirstNData(string $entityName, int $n): array;
}
