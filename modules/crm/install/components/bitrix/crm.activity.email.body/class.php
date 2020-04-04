<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.activity.email/class.php');

class CrmActivityEmailBodyComponent extends CBitrixComponent
{

	public function executeComponent()
	{
		global $USER;

		$activity = $this->arParams['~ACTIVITY'];
		if (empty($activity))
			return;

		\CBitrixComponent::includeComponentClass('bitrix:crm.activity.email');

		if (!empty($activity['__communications']))
		{
			foreach ($activity['__communications'] as $k => $item)
			{
				\CrmActivityEmailComponent::prepareCommunication($item);
				$activity['__communications'][$k] = $item;
			}
		}

		\CrmActivityEmailComponent::prepareActivityRcpt($activity);

		$this->arParams['ACTIVITY']  = $activity;
		$this->arParams['MAILBOXES'] = \CrmActivityEmailComponent::prepareMailboxes();

		$userFields = \Bitrix\Main\UserTable::getList(array(
			'select' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO'),
			'filter' => array('=ID' => $USER->getId()),
		))->fetch();
		$userImage = \CFile::resizeImageGet(
			$userFields['PERSONAL_PHOTO'], array('width' => 38, 'height' => 38),
			BX_RESIZE_IMAGE_EXACT, false
		);
		$this->arParams['USER_IMAGE'] = !empty($userImage['src']) ? $userImage['src'] : '';
		$this->arParams['USER_FULL_NAME'] = \CUser::formatName(\CSite::getNameFormat(), $userFields, true, false);

		$this->includeComponentTemplate();
	}

}
