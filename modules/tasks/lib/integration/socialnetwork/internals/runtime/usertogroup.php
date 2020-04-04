<?
namespace Bitrix\Tasks\Integration\Socialnetwork\Internals\RunTime;

use \Bitrix\Main\DB\SqlExpression;

class UserToGroup extends \Bitrix\Tasks\Integration\SocialNetwork
{
	/**
	 * Returns a new runtime field that represents filter by group(s).
	 * It can be attached to a user-related entity or a dynamically-created query.
	 *
	 * Example:
	 *
	 * $query = new \Bitrix\Main\Entity\Query(...);
	 * $result = UserToGroup::getFilterByGroup();
	 * $query->registerRuntimeField('', $result);
	 * $query->setFilter(array('!SN_UTG' => false));
	 *
	 * @param array $groups
	 * @return mixed[]
	 */
	public static function getFilterByGroup(array $groups)
	{
		$result = array();

		$groups = array_filter(array_map('intval', array_unique($groups)));

		// no module or nothing to filter by => no mixin should be applied
		if(empty($groups) || !static::includeModule())
		{
			return $result;
		}

		$condition = array('=this.ID' => 'ref.USER_ID');
		if(count($groups) == 1)
		{
			$condition['=ref.GROUP_ID'] = array('?', array_shift($groups));
		}
		else
		{
			$condition['@ref.GROUP_ID'] = new SqlExpression(implode(', ', $groups));
		}

		$result[] = new \Bitrix\Main\Entity\ReferenceField(
			'SN_UTG',
			'\Bitrix\Socialnetwork\UserToGroup',
			$condition,
			array('join_type' => 'left')
		);

		return array('runtime' => $result);
	}
}