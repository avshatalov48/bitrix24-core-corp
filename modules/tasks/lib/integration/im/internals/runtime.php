<?
/**
 * @internal
 */
namespace Bitrix\Tasks\Integration\IM\Internals;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Im\Model\ChatTable;

class Runtime extends \Bitrix\Tasks\Integration\IM
{
	public static function applyChatNotExist()
	{
		$result = array();

		$result[] = new ReferenceField('IM', ChatTable::getEntity(), array(
				'=ref.ENTITY_TYPE' => array('?', 'TASKS'),
				'=ref.ENTITY_ID' => 'this.ID',
			)
		);

		return array(
			'runtime' => $result,
			'filter' => array(
				'=IM.ID' => null, // get only tasks with no chat created for
			)
		);
	}
}