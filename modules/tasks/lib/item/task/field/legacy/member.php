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

use Bitrix\Tasks\Item;
use Bitrix\Tasks\Util\Assert;
use Bitrix\Tasks\Util\Collection;

final class Member extends \Bitrix\Tasks\Item\Field\Collection
{
	private $type = '';

	public function __construct(array $parameters)
	{
		$this->type = Assert::expectEnumerationMember($parameters['TYPE'], array('A', 'U'));

		parent::__construct($parameters);
	}

	public function getValue($key, $item, array $parameters = array())
	{
		/** @var \Bitrix\Tasks\Item\Task\Collection\Member $memberCollection */
		$memberCollection = $item['SE_MEMBER'];

		$result = array();
		$members = $memberCollection->find(array('=TYPE' => $this->type));
		foreach($members as $member)
		{
			$result[] = $member['USER_ID'];
		}

		return $this->createValue($result, $key, $item);
	}

	/**
	 * @param $value
	 * @param $key
	 * @param Item $item
	 * @param array $parameters
	 *
	 * @return mixed
	 */
	public function setValue($value, $key, $item, array $parameters = array())
	{
		/** @var \Bitrix\Tasks\Item\Task\Collection\Member $memberCollection */
		$memberCollection = $item['SE_MEMBER'];
		$memberCollection->updateValuePart($value, $this->type, $item);
		$item->setFieldModified('SE_MEMBER'); // todo: get rid of this call when when implement backward connection between item and its field value

		return $value;
	}
}