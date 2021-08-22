<?php declare(strict_types=1);

namespace Bitrix\ImConnector\Data;

use Bitrix\Im;
use Bitrix\Main;

/**
 * Class data store.
 *
 * @method Im\Model\EO_MessageParam get($primaryId, string $type = 'default')
 * @method Main\ORM\Query\Result save(Im\Model\EO_MessageParam $object, string $type = 'default')
 * @package Bitrix\ImConnector\Data
 */
class MessageParam extends DataBroker
{
	/** @var self */
	protected static $instance;

	protected function __construct()
	{
		Main\Loader::includeModule('im');
		$this->register(Im\Model\MessageParamTable::class);
	}
}
