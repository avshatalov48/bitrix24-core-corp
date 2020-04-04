<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Item\Replicator;

use Bitrix\Tasks\Util\Collection;

final class Result extends \Bitrix\Tasks\Item\Result
{
	protected $sIResult = null;

	public function setSubInstanceResult(Collection $subInstanceResults)
	{
		$this->sIResult = $subInstanceResults;
	}

	public function getSubInstanceResults()
	{
		return $this->sIResult;
	}
}