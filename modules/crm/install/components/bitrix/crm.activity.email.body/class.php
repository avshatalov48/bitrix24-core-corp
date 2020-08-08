<?php

use Bitrix\Main\Config;
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

		$trackingAvailable = Config\Option::get('main', 'track_outgoing_emails_read', 'Y') == 'Y';

		$activity['__trackable'] = isset($activity['SETTINGS']['IS_BATCH_EMAIL']) && !$activity['SETTINGS']['IS_BATCH_EMAIL'];
		$activity['__trackable'] *= $trackingAvailable || $activity['SETTINGS']['READ_CONFIRMED'] > 0;

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
