<?php

namespace Bitrix\Crm\Integration;

use Bitrix\Crm\Activity\Provider\Tasks\Task;
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
			[],
			'crm'
		);

		$userFields = $event->getParameter('userFields');

		if (
			!is_array($userFields)
			|| empty($userFields['UF_USER_CRM_ENTITY'])
		)
		{
			return $result;
		}

		$entityCode = trim($userFields['UF_USER_CRM_ENTITY']);
		$entityData = explode('_', $entityCode);

		if (
			!empty($entityData[0])
			&& !empty($entityData[1])
			&& (int)$entityData[1] > 0
		)
		{
			$url = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::resolveID(\CUserTypeCrm::getLongEntityType($entityData[0])), $entityData[1]);
			if (!empty($url))
			{
				$result = new EventResult(
					EventResult::SUCCESS,
					[
						'url' => $url,
					],
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
			[],
			'crm'
		);

		$entityType = $event->getParameter('entityType');

		switch ($entityType)
		{
			case self::DATA_ENTITY_TYPE_CRM_LEAD:
				$provider = new Socialnetwork\Livefeed\CrmLead();
				break;
			case self::DATA_ENTITY_TYPE_CRM_CONTACT:
				$provider = new Socialnetwork\Livefeed\CrmContact();
				break;
			case self::DATA_ENTITY_TYPE_CRM_COMPANY:
				$provider = new Socialnetwork\Livefeed\CrmCompany();
				break;
			case self::DATA_ENTITY_TYPE_CRM_DEAL:
				$provider = new Socialnetwork\Livefeed\CrmDeal();
				break;
			case self::DATA_ENTITY_TYPE_CRM_INVOICE:
				$provider = new Socialnetwork\Livefeed\CrmInvoice();
				break;
			case self::DATA_ENTITY_TYPE_CRM_ACTIVITY:
				$provider = new Socialnetwork\Livefeed\CrmActivity();
				break;
			case self::DATA_ENTITY_TYPE_CRM_ENTITY_COMMENT:
				$provider = new Socialnetwork\Livefeed\CrmEntityComment();
				break;
			default:
				$provider = false;
		}

		if ($provider)
		{
			$result = new EventResult(
				EventResult::SUCCESS,
				[
					'provider' => $provider,
				],
				'crm'
			);
		}

		return $result;
	}

	public static function onLogProviderGetContentId(Event $event)
	{
		$result = new EventResult(
			EventResult::UNDEFINED,
			[],
			'crm'
		);

		$eventFields = $event->getParameter('eventFields');
		$contentEntityType = false;
		$contentEntityId = false;

		if (!empty($eventFields['EVENT_ID']))
		{
			$providersList = [
				new Socialnetwork\Livefeed\CrmInvoice(),
				new Socialnetwork\Livefeed\CrmActivity(),
				new Socialnetwork\Livefeed\CrmEntityComment(),
			];
			foreach ($providersList as $provider)
			{
				if (in_array($eventFields['EVENT_ID'], $provider->getEventId(), true))
				{
					if ($provider::className() === Socialnetwork\Livefeed\CrmActivity::className())
					{
						$res = \CCrmActivity::getList(
							[],
							[
								'ID' => (int)$eventFields['ENTITY_ID'],
								'CHECK_PERMISSIONS' => 'N',
							],
							false,
							false,
							['ASSOCIATED_ENTITY_ID', 'TYPE_ID', 'PROVIDER_ID']
						);
						if (
							($activityFields = $res->fetch())
							&& ((int)$activityFields['ASSOCIATED_ENTITY_ID'] > 0)
						)
						{
							if (
								(int)$activityFields['TYPE_ID'] === \CCrmActivityType::Task
								|| (
									(int)$activityFields['TYPE_ID'] === \CCrmActivityType::Provider
									&& $activityFields['PROVIDER_ID'] === Task::getId()
								)
							)
							{
								$provider = new \Bitrix\Socialnetwork\Livefeed\TasksTask();
								$contentEntityType = $provider->getContentTypeId();
								$contentEntityId = (int)$activityFields['ASSOCIATED_ENTITY_ID'];
							}
						}
					}

					if (!$contentEntityType)
					{
						$contentEntityType = $provider->getContentTypeId();
						$contentEntityId = (int)$eventFields['ENTITY_ID'];
					}

					break;
				}
			}

			if (!$contentEntityType)
			{
				$providersList = [
					new Socialnetwork\Livefeed\CrmLead(),
					new Socialnetwork\Livefeed\CrmContact(),
					new Socialnetwork\Livefeed\CrmCompany(),
					new Socialnetwork\Livefeed\CrmDeal(),
				];
				foreach ($providersList as $provider)
				{
					if (in_array($eventFields['EVENT_ID'], $provider->getEventId(), true))
					{
						$contentEntityType = $provider->getContentTypeId();
						$contentEntityId = (int)$eventFields['ID'];
						break;
					}
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
				[
					'contentEntityType' => $contentEntityType,
					'contentEntityId' => $contentEntityId,
				],
				'crm'
			);
		}

		return $result;
	}

	public static function onCommentAuxGetPostTypeList(Event $event)
	{
		return new EventResult(
			EventResult::SUCCESS,
			[
				'typeList' => Socialnetwork\CommentAux\CreateTask::getPostTypeList(),
			],
			'crm'
		);
	}

	public static function onCommentAuxGetCommentTypeList(Event $event)
	{
		return new EventResult(
			EventResult::SUCCESS,
			[
				'typeList' => Socialnetwork\CommentAux\CreateTask::getCommentTypeList(),
			],
			'crm'
		);
	}

	public static function onCommentAuxInitJs(Event $event)
	{
		Socialnetwork\CommentAux::initJs();

		Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/lib/integration/socialnetwork/commentaux/createtask.php');

		return new EventResult(
			EventResult::SUCCESS,
			[
				'lang_additional' => [
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
					'SONET_COMMENTAUX_JS_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_ACTIVITY' => Loc::getMessage('SONET_COMMENTAUX_CREATETASK_COMMENT_CRM_ENTITY_COMMENT_ACTIVITY'),
				]
			],
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
			$entityType === self::DATA_ENTITY_TYPE_CRM_CONTACT
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
			&& (int)$params['FINAL_RESPONSIBLE_ID'] > 0
		)
		{
			$res = UserTable::getList([
				'filter' => [
					'=ID' => (int)$params['FINAL_RESPONSIBLE_ID']
				],
				'select' => ['NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN']
			]);
			if ($userFields = $res->fetch())
			{
				$result = \CUser::formatName(\CSite::getNameFormat(), $userFields, true, false);
			}
		}
		elseif (
			$entityType === self::DATA_ENTITY_TYPE_CRM_DEAL
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
			$entityType === self::DATA_ENTITY_TYPE_CRM_LEAD
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