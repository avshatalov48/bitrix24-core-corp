<?php

namespace Bitrix\Location\Source;

use Bitrix\Location\Repository\Location\IRepository;

/**
 * Class BaseSource
 * @package Bitrix\Location\Source
 */
abstract class BaseSource
{
	/** @var string */
	protected $code = '';
	/** @var IRepository */
	protected $repository = null;

	/** Returns source code */
	public function getCode(): string
	{
		return $this->code;
	}

	/** Returns source repository */
	public function getRepository(): IRepository
	{
		return $this->repository;
	}

	/**
	 * Is used for the transferring params to JS Source
	 * @return array
	 */
	public function getJSParams(): array
	{
		return [];
	}
}
