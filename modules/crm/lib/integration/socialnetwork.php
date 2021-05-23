<?php

namespace Bitrix\Crm\Integration;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;

class Socialnetwork
{
	const DATA_ENTITY_TYPE_CRM_LEAD = 'CRM_LOG_LEAD';
	const DATA_ENTITY_TYPE_CRM_CONTACT = 'CRM_LOG_CONTACT';
	const DATA_ENTITY_TYPE_CRM_COMPANY = 'CRM_LOG_COMPANY';
	const DATA_ENTITY_TYPE_CRM_DEAL = 'CRM_LOG_DEAL';
	const DATA_ENTITY_TYPE_CRM_INVOICE = 'CRM_INVOICE';
	const DATA_ENTITY_TYPE_CRM_ACTIVITY = 'CRM_ACTIVITY';
	const DATA_ENTITY_TYPE_CRM_ENTITY_COMMENT = 'CRM_ENTITY_COMMENT';

	public static function onUserProfileRedirectGetUrl(Event $event)
	{
		$result = new EventResult(
			EventResult::UNDEFINED,
			array(),
			'crm'
		);

		$userFields = $event->getParameter('userFields');

		if (
			!is_array($userFields)
			|| empty($userFields["UF_USER_CRM_ENTITY"])
		)
		{
			return $result;
		}

		$entityCode = trim($userFields["UF_USER_CRM_ENTITY"]);
		$entityData = explode('_', $entityCode);

		if (
			!empty($entityData[0])
			&& !empty($entityData[1])
			&& intval($entityData[1]) > 0
		)
		{
			$url = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::resolveID(\CUserTypeCrm::getLongEntityType($entityData[0])), $entityData[1]);
			if (!empty($url))
			{
				$result = new EventResult(
					EventResult::SUCCESS,
					array(
						'url' => $url,
					),
					'crm'
				);
			}
		}

		return $result;
	}

	public static function onLogProviderGetProvider(Event $event)
	{
		$result = new EventResult(
			EventResult::UNDEFINED,
			array(),
			'crm'
		);

		$entityType = $event->getParameter('entityType');

		switch ($entityType)
		{
			case self::DATA_ENTITY_TYPE_CRM_LEAD:
				$provider = new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmLead();
				break;
			case self::DATA_ENTITY_TYPE_CRM_CONTACT:
				$provider = new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmContact();
				break;
			case self::DATA_ENTITY_TYPE_CRM_COMPANY:
				$provider = new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmCompany();
				break;
			case self::DATA_ENTITY_TYPE_CRM_DEAL:
				$provider = new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmDeal();
				break;
			case self::DATA_ENTITY_TYPE_CRM_INVOICE:
				$provider = new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmInvoice();
				break;
			case self::DATA_ENTITY_TYPE_CRM_ACTIVITY:
				$provider = new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmActivity();
				break;
			case self::DATA_ENTITY_TYPE_CRM_ENTITY_COMMENT:
				$provider = new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmEntityComment();
				break;
			default:
				$provider = false;
		}

		if ($provider)
		{
			$result = new EventResult(
				EventResult::SUCCESS,
				array(
					'provider' => $provider,
				),
				'crm'
			);
		}

		return $result;
	}

	public static function onLogProviderGetContentId(Event $event)
	{
		$result = new EventResult(
			EventResult::UNDEFINED,
			array(),
			'crm'
		);

		$eventFields = $event->getParameter('eventFields');
		$contentEntityType = $contentEntityId = false;

		if (!empty($eventFields["EVENT_ID"]))
		{
			$providersList = array(
				new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmInvoice(),
				new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmActivity(),
				new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmEntityComment(),
			);
			foreach($providersList as $provider)
			{
				if (in_array($eventFields["EVENT_ID"], $provider->getEventId()))
				{
					$contentEntityType = $provider->getContentTypeId();
					$contentEntityId = intval($eventFields["ENTITY_ID"]);
					break;
				}
			}

			$providersList = array(
				new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmLead(),
				new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmContact(),
				new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmCompany(),
				new \Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmDeal(),
			);
			foreach($providersList as $provider)
			{
				if (in_array($eventFields["EVENT_ID"], $provider->getEventId()))
				{
					$contentEntityType = $provider->getContentTypeId();
					$contentEntityId = intval($eventFields["ID"]);
					break;
				}
			}
		}

		if (
			$contentEntityType
			&& $contentEntityId > 0
		)
		{
			$result = new EventResult(
				EventResult::SUCCESS,
				array(
					'contentEntityType' => $contentEntityType,
					'contentEntityId' => $contentEntityId
				),
				'crm'
			);
		}

		return $result;
	}

	public static function onCommentAuxGetPostTypeList(Event $event)
	{
		return new EventResult(
			EventResult::SUCCESS,
			array(
				'typeList' => \Bitrix\Crm\Integration\Socialnetwork\CommentAux\CreateTask::getPostTypeList(),
			),
			'crm'
		);
	}

	public static function onCommentAuxGetCommentTypeList(Event $event)
	{
		return new EventResult(
			EventResult::SUCCESS,
			array(
				'typeList' => \Bitrix\Crm\Integration\Socialnetwork\CommentAux\CreateTask::getCommentTypeList(),
			),
			'crm'
		);
	}

	public static function onCommentAuxInitJs(Event $event)
	{
		\Bitrix\Crm\Integration\Socialnetwork\CommentAux::initJs();

		Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/lib/integration/socialnetwork/commentaux/createtask.php');

		return new EventResult(
			EventResult::SUCCESS,
			array(
				'lang_additional' => array(
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_LOG_LEAD' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_LOG_LEAD'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_LOG_LEAD_MESSAGE' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_LOG_LEAD_MESSAGE'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_LEAD' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_LEAD'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_LEAD_MESSAGE' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_LEAD_MESSAGE'),
					'SONET_COMMENTAUX_JS_CREATETASK_POST_CRM_LOG_LEAD' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_POST_CRM_LOG_LEAD'),
					'SONET_COMMENTAUX_JS_CREATETASK_POST_CRM_LOG_LEAD_MESSAGE' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_POST_CRM_LOG_LEAD_MESSAGE'),
					'SONET_COMMENTAUX_JS_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_LEAD' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_LEAD'),
					'SONET_COMMENTAUX_JS_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_LEAD_MESSAGE' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_LEAD_MESSAGE'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_LOG_CONTACT' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_LOG_CONTACT'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_LOG_CONTACT_MESSAGE' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_LOG_CONTACT_MESSAGE'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_CONTACT' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_CONTACT'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_CONTACT_MESSAGE' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_CONTACT_MESSAGE'),
					'SONET_COMMENTAUX_JS_CREATETASK_POST_CRM_LOG_CONTACT' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_POST_CRM_LOG_CONTACT'),
					'SONET_COMMENTAUX_JS_CREATETASK_POST_CRM_LOG_CONTACT_MESSAGE' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_POST_CRM_LOG_CONTACT_MESSAGE'),
					'SONET_COMMENTAUX_JS_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_CONTACT' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_CONTACT'),
					'SONET_COMMENTAUX_JS_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_CONTACT_MESSAGE' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_CONTACT_MESSAGE'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_LOG_COMPANY' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_LOG_COMPANY'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_LOG_COMPANY_MESSAGE' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_LOG_COMPANY_MESSAGE'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_COMPANY' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_COMPANY'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_COMPANY_MESSAGE' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_COMPANY_MESSAGE'),
					'SONET_COMMENTAUX_JS_CREATETASK_POST_CRM_LOG_COMPANY' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_POST_CRM_LOG_COMPANY'),
					'SONET_COMMENTAUX_JS_CREATETASK_POST_CRM_LOG_COMPANY_MESSAGE' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_POST_CRM_LOG_COMPANY_MESSAGE'),
					'SONET_COMMENTAUX_JS_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_COMPANY' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_COMPANY'),
					'SONET_COMMENTAUX_JS_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_COMPANY_MESSAGE' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_COMPANY_MESSAGE'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_LOG_DEAL' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_LOG_DEAL'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_LOG_DEAL_MESSAGE' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_LOG_DEAL_MESSAGE'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_DEAL' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_DEAL'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_DEAL_MESSAGE' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_DEAL_MESSAGE'),
					'SONET_COMMENTAUX_JS_CREATETASK_POST_CRM_LOG_DEAL' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_POST_CRM_LOG_DEAL'),
					'SONET_COMMENTAUX_JS_CREATETASK_POST_CRM_LOG_DEAL_MESSAGE' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_POST_CRM_LOG_DEAL_MESSAGE'),
					'SONET_COMMENTAUX_JS_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_DEAL' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_DEAL'),
					'SONET_COMMENTAUX_JS_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_DEAL_MESSAGE' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_DEAL_MESSAGE'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_INVOICE' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_INVOICE'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_INVOICE' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_INVOICE'),
					'SONET_COMMENTAUX_JS_CREATETASK_POST_CRM_INVOICE' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_POST_CRM_INVOICE'),
					'SONET_COMMENTAUX_JS_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_INVOICE' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_INVOICE'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ACTIVITY' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ACTIVITY'),
					'SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_ACTIVITY' => Loc::getMessage('SONET_EXT_COMMENTAUX_CREATE_TASK_CRM_ENTITY_COMMENT_ACTIVITY'),
					'SONET_COMMENTAUX_JS_CREATETASK_POST_CRM_ACTIVITY' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_POST_CRM_ACTIVITY'),
					'SONET_COMMENTAUX_JS_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_ACTIVITY' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_ACTIVITY')
				)
			),
			'crm'
		);
	}

	public static function buildAuxTaskDescription(array $params, $entityType = '')
	{
		$result = false;

		if (isset($params['TITLE']))
		{
			$result = $params['TITLE'];
		}
		else if (
			$entityType == \Bitrix\Crm\Integration\Socialnetwork::DATA_ENTITY_TYPE_CRM_CONTACT
			&& isset($params['NAME'])
			&& isset($params['LAST_NAME'])
		)
		{
			$result = \CCrmContact::prepareFormattedName([
				'HONORIFIC' => isset($params['HONORIFIC']) ? $params['HONORIFIC'] : '',
				'NAME' => $params['NAME'],
				'LAST_NAME' => $params['LAST_NAME'],
				'SECOND_NAME' => isset($params['SECOND_NAME']) ? $params['SECOND_NAME'] : '',
			]);
		}
		elseif (
			isset($params['FINAL_RESPONSIBLE_ID'])
			&& intval($params['FINAL_RESPONSIBLE_ID']) > 0
		)
		{
			$res = UserTable::getList([
				'filter' => [
					'=ID' => intval($params['FINAL_RESPONSIBLE_ID'])
				],
				'select' => ['NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN']
			]);
			if ($userFields = $res->fetch())
			{
				$result = \CUser::formatName(\CSite::getNameFormat(), $userFields, true, false);
			}
		}
		elseif (
			$entityType == \Bitrix\Crm\Integration\Socialnetwork::DATA_ENTITY_TYPE_CRM_DEAL
			&& !empty($params['FINAL_STATUS_ID'])
			&& isset($params['CATEGORY_ID'])
		)
		{
			$info = \CCrmViewHelper::getDealStageInfos($params['CATEGORY_ID']);
			if (!empty($info[$params['FINAL_STATUS_ID']]))
			{
				$result = $info[$params['FINAL_STATUS_ID']]['NAME'];
			}
		}
		elseif (
			$entityType == \Bitrix\Crm\Integration\Socialnetwork::DATA_ENTITY_TYPE_CRM_LEAD
			&& !empty($params['FINAL_STATUS_ID'])
		)
		{
			$info = \CCrmViewHelper::getLeadStatusInfos();
			if (!empty($info[$params['FINAL_STATUS_ID']]))
			{
				$result = $info[$params['FINAL_STATUS_ID']]['NAME'];
			}
		}


		return $result;
	}
}
