<?
namespace Bitrix\Main\Copy;

use Bitrix\Main\Result;

interface Copyable
{
	/**
	 * Copies entity.
	 *
	 * @param ContainerManager $containerManager
	 * @return Result
	 */
	public function copy(ContainerManager $containerManager);
}