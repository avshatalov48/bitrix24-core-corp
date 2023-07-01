<?php

namespace Bitrix\Tasks\Internals\Counter\Collector;

use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Tasks\Comments\Viewed\Enum;
use Bitrix\Tasks\Comments\Viewed\Group;
use Bitrix\Tasks\Internals\Task\ViewedGroupTable;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\MemberTable;

final class Counter
{
	static public function getJoinForRecountCommentsByType(string $type, array $fields): string
	{
		$join = "";

		if (Group::isOn())
		{
			if ($type === Enum::PROJECT_NAME)
			{
				/**
				 * для мнемонической группы @see Group::GROUP_MNEMONIC - получим список всех групп, которые связаны с задачами пользователя
				 * и выполняем декартово умножение списка полученных групп на список ролей мнемонической группы @see Group::GROUP_MNEMONIC
				 * по итогу получаем для каждой выбранной группы список ролей
				 *
				 * если для задачи не выполняются условия:
				 * -группа принадлежит пользователю задачи
				 * -роль из задачи соответствует списку ролей по результату декартового умножения
				 * то left join присоединит пустую мнемоническую таблицу VG_GROUP_0 к итоговому результату,
				 * что позволит проверить отсутствие такого множества при проверке VG_GROUP_0.VIEWED_DATE IS NULL
				 *
				 * само декартового произведение определяется длинной списка ролей
				 *
				 * для случая отсутствия роли и группы (действие - @see Group::ACTION_USER_GROUP_ALL_ROLE_ALL )
				 *  в таблице меток @see ViewedGroupTable
				 *  для мнемонической группы - @see Group::GROUP_MNEMONIC
				 *  будет сохранен весь доступный список ролей @see Group::fillByAction
				 *
				 * для случая отсутствия роли, но наличия группы для действий:
				 *
				 * @see Group::ACTION_PROJECT_GROUP_ID_ROLE_ALL, Group::ACTION_PROJECT_GROUP_LIST_ROLE_ALL
				 * @see Group::ACTION_SCRUM_GROUP_ID_ROLE_ALL, Group::ACTION_SCRUM_GROUP_LIST_ROLE_ALL
				 * @see Group::ACTION_USER_GROUP_ID_ROLE_ALL
				 *  в таблице меток @see ViewedGroupTable
				 *  для переданной группы или списока групп (если в качестве параметра groupId передается мнемоническая группа @see Group::PROJECT_GROUP_LIST, Group::prepareByAction )
				 *  будет сохранен весь доступный список ролей @see Group::fillByAction
				 *
				 * для случая переданной роли, но не переданной группы
				 * действие - @see Group::ACTION_USER_GROUP_ALL_ROLE_ID )
				 *  в таблице меток @see ViewedGroupTable
				 *    для пользовательских комментарий по задаче @see Enum::USER
				 * 		сохраняем одну строку дя мнемонической группы @see Group::GROUP_MNEMONIC
				 * действие - @see Group::ACTION_PROJECT_GROUP_LIST_ROLE_ALL
				 *  в таблице меток @see ViewedGroupTable
				 *    для комментарий по проектам @see Enum::PROJECT
				 * 		будет сохранён сформированный список групп
				 * будет сохранена передання роль (если она считается допустимой @see MemberTable::possibleTypes )
				 *
				 * для случая переданной роли и переданной группы @see Group::ACTION_USER_GROUP_ID_ROLE_ID
				 *  в таблице меток @see ViewedGroupTable
				 *  для переданной директории
				 *  будет сохранена передання роль (если она считается допустимой @see MemberTable::possibleTypes )
				 *
				 * TODO: over time
				 * возможно можно обойтись подзапросом для каждой записи на предмет наличия подходящей строки в @see ViewedGroupTable
				 * по условиям, но не понятно, как будет вести себя оптимизатор запроса на больших данных
				 * на подзапросах точно не будет индексов, запрос станет тяжелее
				 * ... надо опытным путем проверять
				 * */

				$join = "
					LEFT JOIN ".ViewedGroupTable::getTableName()." VG
						ON VG.GROUP_ID = T.GROUP_ID AND VG.USER_ID = SU.USER_ID AND VG.MEMBER_TYPE = TM.TYPE ".
					/**
					 * задача получить добавленные группы без мнемонической группы @see Group::GROUP_MNEMONIC
					 * тип @see Enum::PROJECT_NAME гарантирует, что в результате не окажется мнемонической группы
					 * т.к. она может быть добавлена только для пользовательских комментариев @see Enum::USER_NAME
					 */
					"AND VG.TYPE_ID = ".Enum::resolveIdByName($type)."
					LEFT JOIN (
						SELECT DISTINCT VG_0_T.GROUP_ID, VG_0_VG.USER_ID, VG_0_VG.MEMBER_TYPE, VG_0_VG.VIEWED_DATE
						FROM 
							".TaskTable::getTableName()." VG_0_T
						INNER JOIN ".UserToGroupTable::getTableName()." VG_0_SU 
							ON VG_0_T.GROUP_ID = VG_0_SU.GROUP_ID 
						INNER JOIN  ".MemberTable::getTableName()." VG_0_TM
							ON VG_0_TM.TASK_ID = VG_0_T.ID AND VG_0_TM.USER_ID = VG_0_SU.USER_ID 
						INNER JOIN  ".ViewedGroupTable::getTableName()." VG_0_VG ".
					/**
					 * задача создать мнемоническою таблицу при налии мнемонической группы @see Group::GROUP_MNEMONIC
					 */
					"	ON VG_0_VG.GROUP_ID = ".Group::GROUP_MNEMONIC." AND VG_0_VG.USER_ID = VG_0_SU.USER_ID 
					) VG_GROUP_0
					ON VG_GROUP_0.GROUP_ID = T.GROUP_ID 
					AND VG_GROUP_0.USER_ID = TM.USER_ID 
					AND VG_GROUP_0.MEMBER_TYPE = TM.TYPE
				";
			}
			else if ($type === Enum::USER_NAME)
			{
				$userId = (int)$fields['userId'];
				$join = "
					LEFT JOIN ".ViewedGroupTable::getTableName()." VG 
						ON VG.GROUP_ID = T.GROUP_ID AND VG.USER_ID = {$userId} AND VG.MEMBER_TYPE = TM.TYPE ".
					/**
					 * при обьединение задач по GROUP_ID (могут быть задачи с T.GROUP_ID = 0)
					 * надо исключить обьединение таких задач по мнемонической группе @see Group::GROUP_MNEMONIC
					 */
					"AND IF (VG.TYPE_ID = ".Enum::resolveIdByName($type)." AND VG.GROUP_ID = ".Group::GROUP_MNEMONIC.", 'Y', 'N' ) = 'N'
							AND VG.MEMBER_TYPE = TM.TYPE
					LEFT JOIN (
						 SELECT DISTINCT VG_0_T.GROUP_ID, VG_0_VG.USER_ID, VG_0_VG.MEMBER_TYPE, VG_0_VG.VIEWED_DATE
						 FROM 
							b_tasks VG_0_T
						 INNER JOIN  ".MemberTable::getTableName()." VG_0_TM
							ON VG_0_TM.TASK_ID = VG_0_T.ID
						 INNER JOIN  ".ViewedGroupTable::getTableName()." VG_0_VG
						 	ON VG_0_VG.GROUP_ID = ".Group::GROUP_MNEMONIC." AND VG_0_VG.USER_ID = VG_0_TM.USER_ID
					) VG_GROUP_0
					ON VG_GROUP_0.GROUP_ID = T.GROUP_ID 
					AND VG_GROUP_0.USER_ID = {$userId} 
					AND VG_GROUP_0.MEMBER_TYPE = TM.TYPE
				";
			}
		}

		return $join;
	}

	static public function getConditionForRecountComments(): string
	{
		if (Group::isOn() === false )
		{
			$sql = "(
				(TV.VIEWED_DATE IS NOT NULL AND FM.POST_DATE > TV.VIEWED_DATE)
				OR (TV.VIEWED_DATE IS NULL AND FM.POST_DATE >= T.CREATED_DATE)
			)";

			return $sql;
		}

		$sql = "
			(
				(
						VG_GROUP_0.VIEWED_DATE 	IS NOT NULL 
					AND VG.VIEWED_DATE 			IS NOT NULL 
					AND TV.VIEWED_DATE 			IS NOT NULL 
						AND FM.POST_DATE > VG_GROUP_0.VIEWED_DATE
						AND FM.POST_DATE > VG.VIEWED_DATE
						AND FM.POST_DATE > TV.VIEWED_DATE
				)
				OR
				(
						VG_GROUP_0.VIEWED_DATE 	IS NOT NULL 
					AND VG.VIEWED_DATE 			IS NOT NULL 
					AND TV.VIEWED_DATE 			IS NULL 
						AND FM.POST_DATE > VG_GROUP_0.VIEWED_DATE
						AND FM.POST_DATE > VG.VIEWED_DATE
				)
				OR
				(
						VG_GROUP_0.VIEWED_DATE 	IS NOT NULL 
					AND VG.VIEWED_DATE 			IS NULL 
					AND TV.VIEWED_DATE 			IS NOT NULL 
						AND FM.POST_DATE > VG_GROUP_0.VIEWED_DATE
						AND FM.POST_DATE > TV.VIEWED_DATE
				)
				OR
				(
						VG_GROUP_0.VIEWED_DATE 	IS NULL 
					AND VG.VIEWED_DATE 			IS NOT NULL 
					AND TV.VIEWED_DATE 			IS NOT NULL 
						AND FM.POST_DATE > VG.VIEWED_DATE
						AND FM.POST_DATE > TV.VIEWED_DATE
				)
				OR
				(
						VG_GROUP_0.VIEWED_DATE 	IS NOT NULL 
					AND VG.VIEWED_DATE 			IS NULL 
					AND TV.VIEWED_DATE 			IS NULL 
						AND FM.POST_DATE > VG_GROUP_0.VIEWED_DATE
				)
				OR
				(
						VG_GROUP_0.VIEWED_DATE 	IS NULL 
					AND VG.VIEWED_DATE 			IS NOT NULL 
					AND TV.VIEWED_DATE 			IS NULL 					
						AND FM.POST_DATE > VG.VIEWED_DATE
				)
				OR
				(
						VG_GROUP_0.VIEWED_DATE 	IS NULL 
					AND VG.VIEWED_DATE 			IS NULL 
					AND TV.VIEWED_DATE 			IS NOT NULL 
						AND FM.POST_DATE > TV.VIEWED_DATE
				)
				OR
				(
					".
			/**
			 * если нет мнемонической группы @see Group::GROUP_MNEMONIC
			 * если нет меток для связаных групп
			 * и если нет меток на задачи, сравниваем по дате создания сообщения и задачи
			 */
			"			VG_GROUP_0.VIEWED_DATE 	IS NULL 
					AND VG.VIEWED_DATE 			IS NULL 
					AND TV.VIEWED_DATE 			IS NULL 
						AND FM.POST_DATE >= T.CREATED_DATE
				)
			) 
		";

		return $sql;
	}
}