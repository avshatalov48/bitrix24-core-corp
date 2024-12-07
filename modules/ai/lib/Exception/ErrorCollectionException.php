<?php declare(strict_types=1);

namespace Bitrix\AI\Exception;

use Bitrix\Main\ErrorCollection;

class ErrorCollectionException extends \Exception
{
	public function __construct(
		protected ErrorCollection $collection,
		$message = ''
	)
	{
		parent::__construct($message);
	}

	public function getCollection(): ErrorCollection
	{
		return $this->collection;
	}
}
