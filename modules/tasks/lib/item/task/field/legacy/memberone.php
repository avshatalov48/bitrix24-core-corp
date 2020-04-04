<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 * @internal
 */

namespace Bitrix\Tasks\Item\Task\Field\Legacy;

use Bitrix\Tasks\Util\Assert;

final class MemberOne extends \Bitrix\Tasks\Item\Field\Integer
{
	private $type = '';

	public function __construct(array $parameters)
	{
		$this->type = Assert::expectEnumerationMember($parameters['TYPE'], array('O', 'R'));

		parent::__construct($parameters);
	}

	public function getValue($key, $item, array $parameters = array())
	{
		/** @var \Bitrix\Tasks\Item\Task\Collection\Member $memberCollection */
		$memberCollection = $item['SE_MEMBER'];
		$member = $memberCollection->findOne(array('=TYPE' => $this->type));

		return $this->createValue($member ? $member['USER_ID'] : null, $key, $item);
	}

	public function setValue($value, $key, $item, array $parameters = array())
	{
		/** @var \Bitrix\Tasks\Item\Task\Collection\Member $memberCollection */
		$memberCollection = $item['SE_MEMBER'];

		$memberCollection->updateValuePart(array($value), $this->type, $item);
		$item->setFieldModified('SE_MEMBER'); // todo: get rid of this call when when implement backward connection between item and its field value
	}
}