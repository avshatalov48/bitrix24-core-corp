<?php
namespace Bitrix\BIConnector;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Diag;

/**
 * Unfortunatly we have to keep this empty class for compatibility with clients .settings php.
 *
 * @deprecated Use \Bitrix\BIConnector\DB\MysqliConnection.
 */
class Connection extends \Bitrix\Main\DB\MysqliConnection
{
}
