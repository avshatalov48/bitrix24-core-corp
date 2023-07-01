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
				 * ��� ������������� ������ @see Group::GROUP_MNEMONIC - ������� ������ ���� �����, ������� ������� � �������� ������������
				 * � ��������� ��������� ��������� ������ ���������� ����� �� ������ ����� ������������� ������ @see Group::GROUP_MNEMONIC
				 * �� ����� �������� ��� ������ ��������� ������ ������ �����
				 *
				 * ���� ��� ������ �� ����������� �������:
				 * -������ ����������� ������������ ������
				 * -���� �� ������ ������������� ������ ����� �� ���������� ����������� ���������
				 * �� left join ����������� ������ ������������� ������� VG_GROUP_0 � ��������� ����������,
				 * ��� �������� ��������� ���������� ������ ��������� ��� �������� VG_GROUP_0.VIEWED_DATE IS NULL
				 *
				 * ���� ����������� ������������ ������������ ������� ������ �����
				 *
				 * ��� ������ ���������� ���� � ������ (�������� - @see Group::ACTION_USER_GROUP_ALL_ROLE_ALL )
				 *  � ������� ����� @see ViewedGroupTable
				 *  ��� ������������� ������ - @see Group::GROUP_MNEMONIC
				 *  ����� �������� ���� ��������� ������ ����� @see Group::fillByAction
				 *
				 * ��� ������ ���������� ����, �� ������� ������ ��� ��������:
				 *
				 * @see Group::ACTION_PROJECT_GROUP_ID_ROLE_ALL, Group::ACTION_PROJECT_GROUP_LIST_ROLE_ALL
				 * @see Group::ACTION_SCRUM_GROUP_ID_ROLE_ALL, Group::ACTION_SCRUM_GROUP_LIST_ROLE_ALL
				 * @see Group::ACTION_USER_GROUP_ID_ROLE_ALL
				 *  � ������� ����� @see ViewedGroupTable
				 *  ��� ���������� ������ ��� ������� ����� (���� � �������� ��������� groupId ���������� ������������� ������ @see Group::PROJECT_GROUP_LIST, Group::prepareByAction )
				 *  ����� �������� ���� ��������� ������ ����� @see Group::fillByAction
				 *
				 * ��� ������ ���������� ����, �� �� ���������� ������
				 * �������� - @see Group::ACTION_USER_GROUP_ALL_ROLE_ID )
				 *  � ������� ����� @see ViewedGroupTable
				 *    ��� ���������������� ����������� �� ������ @see Enum::USER
				 * 		��������� ���� ������ �� ������������� ������ @see Group::GROUP_MNEMONIC
				 * �������� - @see Group::ACTION_PROJECT_GROUP_LIST_ROLE_ALL
				 *  � ������� ����� @see ViewedGroupTable
				 *    ��� ����������� �� �������� @see Enum::PROJECT
				 * 		����� ������� �������������� ������ �����
				 * ����� ��������� ��������� ���� (���� ��� ��������� ���������� @see MemberTable::possibleTypes )
				 *
				 * ��� ������ ���������� ���� � ���������� ������ @see Group::ACTION_USER_GROUP_ID_ROLE_ID
				 *  � ������� ����� @see ViewedGroupTable
				 *  ��� ���������� ����������
				 *  ����� ��������� ��������� ���� (���� ��� ��������� ���������� @see MemberTable::possibleTypes )
				 *
				 * TODO: over time
				 * �������� ����� �������� ����������� ��� ������ ������ �� ������� ������� ���������� ������ � @see ViewedGroupTable
				 * �� ��������, �� �� �������, ��� ����� ����� ���� ����������� ������� �� ������� ������
				 * �� ����������� ����� �� ����� ��������, ������ ������ �������
				 * ... ���� ������� ����� ���������
				 * */

				$join = "
					LEFT JOIN ".ViewedGroupTable::getTableName()." VG
						ON VG.GROUP_ID = T.GROUP_ID AND VG.USER_ID = SU.USER_ID AND VG.MEMBER_TYPE = TM.TYPE ".
					/**
					 * ������ �������� ����������� ������ ��� ������������� ������ @see Group::GROUP_MNEMONIC
					 * ��� @see Enum::PROJECT_NAME �����������, ��� � ���������� �� �������� ������������� ������
					 * �.�. ��� ����� ���� ��������� ������ ��� ���������������� ������������ @see Enum::USER_NAME
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
					 * ������ ������� ������������� ������� ��� ����� ������������� ������ @see Group::GROUP_MNEMONIC
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
					 * ��� ����������� ����� �� GROUP_ID (����� ���� ������ � T.GROUP_ID = 0)
					 * ���� ��������� ����������� ����� ����� �� ������������� ������ @see Group::GROUP_MNEMONIC
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
			 * ���� ��� ������������� ������ @see Group::GROUP_MNEMONIC
			 * ���� ��� ����� ��� �������� �����
			 * � ���� ��� ����� �� ������, ���������� �� ���� �������� ��������� � ������
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