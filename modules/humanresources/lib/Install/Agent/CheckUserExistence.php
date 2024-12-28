<?php

namespace Bitrix\HumanResources\Install\Agent;

use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Application;

class CheckUserExistence
{
	public static function run(): string
	{
		$query = "
			SELECT snm.ID FROM b_hr_structure_node_member snm
			LEFT JOIN b_user u ON u.ID = ENTITY_ID
			WHERE snm.ENTITY_TYPE = 'USER' AND u.ID IS NULL
			LIMIT 20
		";
		$extraNodeMemberIds = Application::getConnection()->query($query)->fetchAll();

		if (empty($extraNodeMemberIds))
		{
			if (\Bitrix\Main\Loader::includeModule('iblock'))
			{
				$iblockId = \COption::getOptionInt('intranet', 'iblock_structure');
				\CIBlockSection::ReSort($iblockId);
			}

			return '';
		}

		$nodeMemberRepository = Container::getNodeMemberRepository();
		foreach ($extraNodeMemberIds as $id)
		{
			$nodeMember = $nodeMemberRepository->findById((int)$id['ID']);
			if ($nodeMember)
			{
				$nodeMemberRepository->remove($nodeMember);
			}
		}

		return "\\Bitrix\\HumanResources\\Install\\Agent\\CheckUserExistence::run();";
	}
}