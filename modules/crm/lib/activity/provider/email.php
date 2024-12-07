<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm;
use Bitrix\Crm\Activity;
use Bitrix\Crm\Activity\CommunicationStatistics;
use Bitrix\Crm\Automation\Trigger\EmailSentTrigger;
use Bitrix\Crm\Service\Timeline;
use Bitrix\Crm\Timeline\LogMessageType;
use Bitrix\Mail\Message;
use Bitrix\Main\Config;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class Email extends Activity\Provider\Base
{
	/**
	 * Size of html description can cause long sanitizing
	 */
	public const HTML_SIZE_LONG_SANITIZE_THRESHOLD = 50000;

	public const ERROR_TYPE_PARTIAL = "partial";
	public const ERROR_TYPE_FULL = "full";

	protected const TYPE_EMAIL = 'EMAIL';
	public const TYPE_EMAIL_COMPRESSED = 'EMAIL_COMPRESSED';

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

	/**
	 * Format email quote for answer editor
	 *
	 * @param array $activityFields Fields of activity
	 * @param string $quotedText Html text of previous email
	 * @param bool $uncompressed Is activity fields were uncompressed already
	 * @param bool $sanitized Is quited text sanitized already
	 *
	 * @return string
	 */
	public static function getMessageQuote(
		array $activityFields,
		string $quotedText,
		bool $uncompressed = false,
		bool $sanitized = false): string
	{
		if (!IsModuleInstalled('mail'))
		{
			return '';
		}

		if (!$uncompressed)
		{
			static::uncompressActivity($activityFields);
		}
		$header = Activity\Mail\Message::getHeader([
			'OWNER_TYPE_ID' => (int)$activityFields['OWNER_TYPE_ID'],
			'OWNER_ID' => (int)$activityFields['OWNER_ID'],
			'ID' => $activityFields['ID'],
			'SETTINGS' => $activityFields['SETTINGS'],
		], false)->getData();

		return Message::wrapTheMessageWithAQuote(
			$quotedText,
			$activityFields['SUBJECT'] ?? '',
			$activityFields['START_TIME'] ?? null,
			$header['from'],
			$header['to'],
			$header['cc'],
			$sanitized,
		);
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

		if ($direction === \CCrmActivityDirection::Outgoing)
		{
			$itemIdentifier = Crm\ItemIdentifier::createFromArray($activityFields);
			if ($itemIdentifier)
			{
				$badge = Crm\Service\Container::getInstance()->getBadge(
					Crm\Badge\Badge::MAIL_MESSAGE_DELIVERY_STATUS_TYPE,
					Crm\Badge\Type\MailMessageDeliveryStatus::MAIL_MESSAGE_DELIVERY_ERROR_VALUE,
				);
				$badge->deleteByEntity($itemIdentifier, $badge->getType(), $badge->getValue());
				Timeline\Monitor::getInstance()->onBadgesSync($itemIdentifier);
			}
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
		if (
			isset($activity['PROVIDER_TYPE_ID'])
			&& $activity['PROVIDER_TYPE_ID'] === self::TYPE_EMAIL_COMPRESSED
		)
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

	/**
	 * Sanitize email message body html
	 *
	 * @param string $html Raw html of email body
	 *
	 * @return string
	 */
	protected static function sanitizeBody(string $html): string
	{
		if (IsModuleInstalled('mail') && Loader::includeModule('mail'))
		{
			return \Bitrix\Mail\Helper\Message::sanitizeHtml($html, true);
		}
		$sanitizer = new \CBXSanitizer();
		$sanitizer->setLevel(\CBXSanitizer::SECURE_LEVEL_LOW);
		$sanitizer->applyDoubleEncode(false);
		$sanitizer->addTags(['style' => []]);

		return $sanitizer->sanitizeHtml($html);
	}

	/**
	 * Get description html according to description type
	 *
	 * @param string $description Description text
	 * @param int $type Description type
	 * @param bool $needSanitize Is need to sanotize html tags
	 *
	 * @return string
	 */
	public static function getDescriptionHtmlByType(string $description, int $type, bool $needSanitize): string
	{
		return match ($type)
		{
			\CCrmContentType::BBCode => (new \CTextParser())->convertText($description),
			\CCrmContentType::Html => ($needSanitize) ? static::sanitizeBody($description) : $description,
			default => preg_replace('/[\r\n]+/u',
				'<br>',
				htmlspecialcharsbx($description)
			),
		};
	}

	/**
	 * Get description field from activity fields
	 *
	 * @param array $activity Activity fields data
	 *
	 * @return string
	 */
	public static function getDescriptionHtmlByActivityFields(array $activity): string
	{
		$description = (string)($activity['DESCRIPTION'] ?? '');
		$type = (int)($activity['DESCRIPTION_TYPE'] ?? \CCrmContentType::PlainText);
		$needSanitize = (bool)($activity['SETTINGS']['SANITIZE_ON_VIEW'] ?? false);

		return self::getDescriptionHtmlByType($description, $type, $needSanitize);
	}

	/**
	 * Is sanitizing can be long?
	 *
	 * @param array $activity Activity fields data
	 *
	 * @return bool
	 */
	public static function isSanitizingCanBeLong(array $activity): bool
	{
		$description = (string)($activity['DESCRIPTION'] ?? '');
		$type = (int)($activity['DESCRIPTION_TYPE'] ?? \CCrmContentType::PlainText);
		$needSanitize = (bool)($activity['SETTINGS']['SANITIZE_ON_VIEW'] ?? false);

		return $needSanitize
			&& $type === \CCrmContentType::Html
			&& mb_strlen(trim($description)) > self::HTML_SIZE_LONG_SANITIZE_THRESHOLD;
	}

	/**
	 * Get fast process fallback for description, instead of sanitize
	 *
	 * @param string $description Html description
	 *
	 * @return string
	 */
	public static function getFallbackHtmlDescription(string $description): string
	{
		$textLikeTextBody = html_entity_decode(htmlToTxt($description), ENT_QUOTES | ENT_HTML401);

		return preg_replace('/(\s*(\r\n|\n|\r))+/', '<br>', htmlspecialcharsbx($textLikeTextBody));
	}

	public static function getMoveBindingsLogMessageType(): ?string
	{
		return LogMessageType::EMAIL_INCOMING_MOVED;
	}
}
