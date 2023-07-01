<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm;
use Bitrix\Crm\Automation\Trigger\EmailSentTrigger;
use Bitrix\Main\Config;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Activity;
use Bitrix\Crm\Activity\CommunicationStatistics;

class Email extends Activity\Provider\Base
{

	public const ERROR_TYPE_PARTIAL = "partial";
	public const ERROR_TYPE_FULL = "full";

	protected const TYPE_EMAIL = 'EMAIL';
	protected const TYPE_EMAIL_COMPRESSED = 'EMAIL_COMPRESSED';

	public static function getId()
	{
		return 'CRM_EMAIL';
	}

	public static function getTypeId(array $activity)
	{
		if (isset($activity['PROVIDER_TYPE_ID']) && $activity['PROVIDER_TYPE_ID'] === self::TYPE_EMAIL_COMPRESSED)
		{
			return self::TYPE_EMAIL_COMPRESSED;
		}

		return self::TYPE_EMAIL;
	}

	public static function getTypes()
	{
		return [
			[
				'NAME' => 'E-mail',
				'PROVIDER_ID' => static::getId(),
				'PROVIDER_TYPE_ID' => self::TYPE_EMAIL,
			],
			[
				'NAME' => 'E-mail (Compressed)',
				'PROVIDER_ID' => static::getId(),
				'PROVIDER_TYPE_ID' => self::TYPE_EMAIL_COMPRESSED,
			],
		];
	}

	public static function getName()
	{
		return 'E-mail';
	}

	public static function getTypeName($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_EMAIL_NAME');
	}

	public static function getCommunicationType($providerTypeId = null)
	{
		return static::COMMUNICATION_TYPE_EMAIL;
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @return bool
	 */
	public static function canUseLiveFeedEvents($providerTypeId = null)
	{
		return true;
	}

	/**
	 * @param array $activity Activity data.
	 * @return bool
	 */
	public static function checkForWaitingCompletion(array $activity)
	{
		$completed = isset($activity['COMPLETED']) && $activity['COMPLETED'] === 'Y';
		$incoming = isset($activity['DIRECTION']) && $activity['DIRECTION'] == \CCrmActivityDirection::Incoming;

		return !$completed || $incoming;
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @param int $direction Activity direction.
	 * @return bool
	 */
	public static function isTypeEditable($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return false;
	}

	public static function getSupportedCommunicationStatistics()
	{
		return [
			CommunicationStatistics::STATISTICS_QUANTITY,
		];
	}

	public static function checkFields($action, &$fields, $id, $params = null)
	{
		$result = new \Bitrix\Main\Result();

		if (isset($fields['END_TIME']) && $fields['END_TIME'] != '')
		{
			$fields['DEADLINE'] = $fields['END_TIME'];
		}
		elseif (isset($fields['~END_TIME']) && $fields['~END_TIME'] !== '')
		{
			$fields['~DEADLINE'] = $fields['~END_TIME'];
		}

		return $result;
	}

	public static function onAfterAdd($activityFields, array $params = null)
	{
		$direction = isset($activityFields['DIRECTION']) ? (int)$activityFields['DIRECTION'] : \CCrmActivityDirection::Undefined;

		if ($direction === \CCrmActivityDirection::Outgoing && Crm\Automation\Factory::canUseAutomation())
		{
			EmailSentTrigger::execute($activityFields['BINDINGS'], $activityFields);
		}
	}

	public static function renderView(array $activity)
	{
		global $APPLICATION;

		ob_start();

		$APPLICATION->IncludeComponent(
			'bitrix:crm.activity.email', '',
			[
				'ACTIVITY' => $activity,
				'ACTION'   => 'view',
			]
		);

		return ob_get_clean();
	}

	public static function renderEdit(array $activity)
	{
		global $APPLICATION;

		ob_start();

		$APPLICATION->IncludeComponent(
			'bitrix:crm.activity.email', '',
			[
				'ACTIVITY' => $activity,
				'ACTION'   => 'create',
			]
		);

		return ob_get_clean();
	}

	public static function prepareEmailInfo(array $fields)
	{
		$direction = isset($fields['DIRECTION']) ? (int)$fields['DIRECTION'] : \CCrmActivityDirection::Undefined;
		if ($direction !== \CCrmActivityDirection::Outgoing)
		{
			return null;
		}

		$settings = isset($fields['SETTINGS'])
			? (is_array($fields['SETTINGS']) ? $fields['SETTINGS'] : unserialize($fields['SETTINGS'], ['allowed_classes' => false]))
			: [];
		if (!(isset($settings['IS_BATCH_EMAIL']) && $settings['IS_BATCH_EMAIL'] === false))
		{
			return null;
		}


		if (isset($settings['READ_CONFIRMED']) && $settings['READ_CONFIRMED'] > 0)
		{
			return [
				"STATUS_ERROR" => false,
				"STATUS_TEXT" => Loc::getMessage('CRM_ACTIVITY_PROVIDER_EMAIL_STATUS_READ')
			];
		}

		switch (($settings["SENT_ERROR"] ?? null))
		{
			case self::ERROR_TYPE_FULL:
				return array(
					"STATUS_ERROR" => true,
					"STATUS_TEXT" => Loc::getMessage('CRM_ACTIVITY_PROVIDER_EMAIL_STATUS_ERROR')
				);
			case self::ERROR_TYPE_PARTIAL:
				return array(
					"STATUS_ERROR" => false,
					"STATUS_TEXT" => Loc::getMessage('CRM_ACTIVITY_PROVIDER_EMAIL_STATUS_SENT_WITH_ERROR')
				);
			default:
				return Config\Option::get('main', 'track_outgoing_emails_read', 'Y') != 'Y'? null : array(
					"STATUS_ERROR" => false,
					"STATUS_TEXT" => Loc::getMessage('CRM_ACTIVITY_PROVIDER_EMAIL_STATUS_SENT')
				);
		}
	}

	public static function getParentByEmail(&$msgFields)
	{
		$inReplyTo = isset($msgFields['IN_REPLY_TO']) ? $msgFields['IN_REPLY_TO'] : '';

		// @TODO: multiple
		if (!empty($inReplyTo))
		{
			if (preg_match('/<crm\.activity\.((\d+)-[0-9a-z]+)@[^>]+>/i', sprintf('<%s>', $inReplyTo), $matches))
			{
				$matchActivity = \CCrmActivity::getById($matches[2], false);
				if ($matchActivity && mb_strtolower($matchActivity['URN']) == mb_strtolower($matches[1]))
					$targetActivity = $matchActivity;
			}

			if (empty($targetActivity))
			{
				$res = Activity\MailMetaTable::getList([
					'select' => ['ACTIVITY_ID'],
					'filter' => [
						'=MSG_ID_HASH' => md5(mb_strtolower($inReplyTo)),
					],
				]);

				while ($mailMeta = $res->fetch())
				{
					if ($matchActivity = \CCrmActivity::getById($mailMeta['ACTIVITY_ID'], false))
					{
						$targetActivity = $matchActivity;
						break;
					}
				}
			}
		}

		if (empty($targetActivity))
		{
			$urnInfo = \CCrmActivity::parseUrn(
				\CCrmActivity::extractUrnFromMessage(
					$msgFields, \CCrmEMailCodeAllocation::getCurrent()
				)
			);

			if ($urnInfo['ID'] > 0)
			{
				$matchActivity = \CCrmActivity::getById($urnInfo['ID'], false);
				if (!empty($matchActivity) && mb_strtolower($matchActivity['URN']) == mb_strtolower($urnInfo['URN']))
					$targetActivity = $matchActivity;
			}
		}

		if (!empty($targetActivity))
		{
			if ($targetActivity['OWNER_TYPE_ID'] > 0 && $targetActivity['OWNER_ID'] > 0)
			{
				return $targetActivity;
			}
		}

		return false;
	}

	public static function compressActivity(array &$activity): void
	{
		$activity['PROVIDER_ID'] = Crm\Activity\Provider\Email::getId();
		$activity['PROVIDER_TYPE_ID'] = self::TYPE_EMAIL_COMPRESSED;

		$bodyId = Crm\Activity\MailBodyTable::addByBody($activity['DESCRIPTION'] ?? '');

		$description = [
			'DESCRIPTION' => $activity['DESCRIPTION'],
			'DESCRIPTION_TYPE' => $activity['DESCRIPTION_TYPE']
		];

		\CCrmActivity::PrepareDescriptionFields(
			$description,
			[
				'ENABLE_HTML' => false,
				'ENABLE_BBCODE' => false,
				'LIMIT' => 200,
			]
		);

		$activity['DESCRIPTION'] = $description['DESCRIPTION_RAW'];
		//$activity['DESCRIPTION_TYPE'] = \CCrmContentType::PlainText;
		$activity['ASSOCIATED_ENTITY_ID'] = $bodyId;
	}

	public static function uncompressActivity(array &$activity)
	{
		if ($activity['PROVIDER_TYPE_ID'] === self::TYPE_EMAIL_COMPRESSED)
		{
			$body = Crm\Activity\MailBodyTable::getById($activity['ASSOCIATED_ENTITY_ID'])->fetch();

			if ($body)
			{
				$activity['DESCRIPTION'] = $body['BODY'];
			}
		}
	}

	public static function deleteAssociatedEntity($entityId, array $activity, array $options = [])
	{
		if ($activity['PROVIDER_TYPE_ID'] === self::TYPE_EMAIL_COMPRESSED)
		{
			$row = Crm\ActivityTable::getList([
				'select' => ['ID'],
				'filter' => [
					'=TYPE_ID' => \CCrmActivityType::Email,
					'=ASSOCIATED_ENTITY_ID' => $entityId,
					'!=ID' => $activity['ID'],
				],
				'limit' => 1,
			])->fetch();

			if (!$row)
			{
				Activity\MailBodyTable::delete($entityId);
			}
		}

		return parent::deleteAssociatedEntity($entityId, $activity, $options);
	}
}
