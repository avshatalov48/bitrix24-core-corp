<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Disk;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;


class MailTemplate // extends Crm\Volume\Base
{
	/**
	 * Returns title of the indicator.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('CRM_VOLUME_MAIL_TEMPLATE_TITLE');
	}

	/**
	 * Returns table list corresponding to indicator.
	 * @return string[]
	 */
	public function getTableList()
	{
		return array(\CCrmMailTemplate::TABLE_NAME);
	}

	/**
	 * Returns list of mail template attachment files.
	 * @return int[]
	 */
	public static function getAttachmentList()
	{
		static $attachments;

		if (!isset($attachments))
		{
			$attachments = [];
			if (Main\Loader::includeModule('disk'))
			{
				/** @global \CUserTypeManager $USER_FIELD_MANAGER */
				global $USER_FIELD_MANAGER;
				/** @var \Bitrix\Disk\Uf\UserFieldManager $diskUfManager */
				$diskUfManager = Disk\Driver::getInstance()->getUserFieldManager();

				$result = \CCrmMailTemplate::getList(array(), array(), false, false, array('ID'));
				while ($template = $result->fetch())
				{
					$files = $USER_FIELD_MANAGER->getUserFieldValue('CRM_MAIL_TEMPLATE', 'UF_ATTACHMENT', $template['ID']);
					if (!empty($files) && is_array($files))
					{
						$diskUfManager->loadBatchAttachedObject($files);
						foreach ($files as $attachedId)
						{
							if ($attachedObject = $diskUfManager->getAttachedObjectById($attachedId))
							{
								if ($attachedObject instanceof Disk\AttachedObject)
								{
									$attachments[] = (int)$attachedObject->getObjectId();
								}
							}
						}
					}
				}
			}
		}

		return $attachments;
	}
}

