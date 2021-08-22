<?php declare(strict_types=1);

namespace Bitrix\ImConnector\Data;

use Bitrix\ImOpenLines;
use Bitrix\Main;

/**
 * Class data store.
 *
 * @method ImOpenLines\Model\EO_Session get($primaryId, string $type = 'default')
 * @method Main\ORM\Query\Result save(ImOpenLines\Model\EO_Session $object, string $type = 'default')
 * @package Bitrix\ImConnector\Data
 */
class Session extends DataBroker
{
	/** @var self */
	protected static $instance;

	protected function __construct()
	{
		$this->register(ImOpenLines\Model\SessionTable::class);
	}
}
