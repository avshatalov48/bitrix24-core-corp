<?php

namespace Bitrix\Crm\Integration;

use Bitrix\Crm\Format\TextHelper;
use Bitrix\Crm\MessageSender\Channel;
use Bitrix\Crm\MessageSender\ICanSendMessage;
use Bitrix\Crm\Multifield;
use Bitrix\Crm\Service\Container;
use Bitrix\Mail\Helper\LicenseManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

final class MailManager implements ICanSendMessage
{
	public const CALLBACK_ENTITY_TYPE = 'mail_mngr';

	public static function getSenderCode(): string
	{
		return 'mail';
	}

	public static function canUse(): bool
	{
		//todo do i really need those modules?
		return (
			Loader::includeModule('mail')
			&& Loader::includeModule('subscribe')
		);
	}

	public static function isAvailable(?int $userId = null): bool
	{
		if (self::canUse())
		{
			return (
				\Bitrix\Crm\Integration\Mail\Client::isReadyToUse($userId)

				/*
				 * currently this class only supports sending emails to a single recipient. therefore, we check that
				 * it is possible to send a message at all.
				 *
				 * if in some point in the future you will extend this class to handle multiple recipients, check whether
				 * limits from the method below are violated or not before sending an email. it's a spam protection
				 */
				&& LicenseManager::getEmailsLimitToSendMessage() !== 0
			);
		}

		return false;
	}

	public static function isConnected(?int $userId = null): bool
	{
		$userId ??= Container::getInstance()->getContext()->getUserId();

		return !empty(self::getFromList($userId));
	}

	public static function getConnectUrl()
	{
		return '/mail';
	}

	public static function getUsageErrors(): array
	{
		return [];
	}

	public static function getChannelsList(array $toListByType, int $userId): array
	{
		if (!self::canUse())
		{
			return [];
		}

		$channel = new Channel(
			self::class,
			[
				'id' => self::getSenderCode(),
				'isDefault' => true,
				'name' => Loc::getMessage('CRM_INTEGRATION_MAIL_MANAGER_NAME'),
				'shortName' => Loc::getMessage('CRM_INTEGRATION_MAIL_MANAGER_NAME'),
			],
			self::getFromList($userId),
			$toListByType[\Bitrix\Crm\Multifield\Type\Email::ID] ?? [],
			$userId,
		);

		return [$channel];
	}

	private static function getFromList(int $userId): array
	{
		$userData = \Bitrix\Main\UserTable::getList([
			'select' => ['ID', 'TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'EMAIL'],
			'filter' => array('=ID' => $userId),
		])->fetch();

		$userNameFormatted = \CUser::formatName(\CSite::getNameFormat(), $userData, true, false);

		$fromList = [];
		foreach (\Bitrix\Mail\MailboxTable::getUserMailboxes($userId) as $mailbox)
		{
			if (empty($mailbox['EMAIL']))
			{
				continue;
			}

			$mailboxName = trim($mailbox['USERNAME']) ?: $userNameFormatted;

			$id = (int)$mailbox['ID'];
			$email = (string)$mailbox['EMAIL'];
			$name = $mailboxName ? "$mailboxName <$email>" : $email;
			$ownerMailboxUserId = (int)$mailbox['USER_ID'];

			$fromList[] = new Channel\Correspondents\From(
				id: (string)$id,
				name: $name,
				isAvailable: LicenseManager::checkTheMailboxForSyncAvailability($id, $ownerMailboxUserId),
			);
		}

		return $fromList;
	}

	public static function canSendMessageViaChannel(Channel $channel): Result
	{
		$result = new Result();

		if (!self::canUse())
		{
			return $result->addError(Channel\ErrorCode::getNotEnoughModulesError());
		}

		if (!self::isAvailable($channel->getUserId()))
		{
			return $result->addError(Channel\ErrorCode::getNotAvailableError());
		}

		if (!self::isConnected($channel->getUserId()))
		{
			return $result->addError(Channel\ErrorCode::getNotConnectedError());
		}

		$fromListAvailable = array_filter($channel->getFromList(), fn($from) => $from->isAvailable());
		if (empty($fromListAvailable))
		{
			return $result->addError(
				new Error(
					Loc::getMessage('CRM_INTEGRATION_MAIL_MANAGER_ERROR_NO_FROM'),
					Channel\ErrorCode::NOT_CONNECTED
				)
			);
		}

		$errors = self::getUsageErrors();
		if (!empty($errors))
		{
			foreach ($errors as $errorMessage)
			{
				$result->addError(
					new Error($errorMessage, Channel\ErrorCode::USAGE_ERROR),
				);
			}
		}

		return $result;
	}

	public static function canSendMessage(?int $userId = null): bool
	{
		return (
			self::canUse()
			&& self::isAvailable($userId)
			&& self::isConnected($userId)
		);
	}

	//todo check how i send a email exactly, check pseudo-connected logic
	public static function sendMessage(array $messageFields)
	{
		$options = $messageFields['OPTIONS'] ?? null;
		$commonOptions = $messageFields['COMMON'] ?? null;
		if (!is_array($options) || !is_array($commonOptions))
		{
			return false;
		}

		$userId = $commonOptions['USER_ID'] ?? null;

		if (!self::canSendMessage($userId))
		{
			return false;
		}

		$fromMailboxId = (int)($options['MAILBOX_ID'] ?? 0);
		if ($fromMailboxId <= 0)
		{
			return false;
		}

		if (!LicenseManager::checkTheMailboxForSyncAvailability($fromMailboxId))
		{
			return (new Result())->addError(
				new Error(
					Loc::getMessage('CRM_INTEGRATION_MAIL_MANAGER_ERROR_FROM_MAILBOX_RESTRICTED'),
					Channel\ErrorCode::NOT_AVAILABLE,
				),
			);
		}

		[$entityTypeId, $entityId] = self::resolveItem($commonOptions);

		$now = new DateTime();

		$subject = Loc::getMessage('CRM_INTEGRATION_MAIL_MANAGER_EMPTY_SUBJECT_PLACEHOLDER', ['#DATE#' => $now]);
		if (!empty($options['MESSAGE_SUBJECT']))
		{
			$subject = $options['MESSAGE_SUBJECT'];
		}

		$body = (string)($options['MESSAGE_BODY'] ?? '');

		$bodyContentType = (int)($options['MESSAGE_BODY_CONTENT_TYPE'] ?? \CCrmContentType::Html);
		$bodyContentType = \CCrmContentType::IsDefined($bodyContentType) ? $bodyContentType : \CCrmContentType::Html;

		if (!empty($body) && ($options['ADD_EMAIL_SIGNATURE'] ?? true))
		{
			\CCrmActivity::AddEmailSignature($body, $bodyContentType);
		}

		if ($bodyContentType === \CCrmContentType::Html)
		{
			$activityDescription = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $body);
			$activityDescription = preg_replace('/<title[^>]*>.*?<\/title>/is', '', $activityDescription);

			$sanitizer = new \CBXSanitizer();
			$sanitizer->setLevel(\CBXSanitizer::SECURE_LEVEL_LOW);
			$sanitizer->applyDoubleEncode(false);
			$sanitizer->addTags(['style' => []]);
			$activityDescription = $sanitizer->SanitizeHtml($activityDescription);
		}
		else
		{
			$activityDescription = $body;
		}

		$addressSource = $commonOptions['ADDITIONAL_FIELDS']['ADDRESS_SOURCE'] ?? null;
		if (!is_array($addressSource))
		{
			$addressSource = [];
		}

		$bindings = $commonOptions['ADDITIONAL_FIELDS']['BINDINGS'] ?? null;
		if (!is_array($bindings))
		{
			$bindings = [];
		}

		$toEmail = (string)($commonOptions['EMAIL'] ?? '');
		if (!check_email($toEmail))
		{
			return (new Result())->addError(
				new Error(Loc::getMessage('CRM_INTEGRATION_MAIL_MANAGER_ERROR_INVALID_TO_EMAIL', ['#EMAIL#' => $toEmail])),
			);
		}

		if (self::isReceiverBlacklisted($toEmail))
		{
			return (new Result())->addError(
				new Error(Loc::getMessage('CRM_INTEGRATION_MAIL_MANAGER_ERROR_TO_BLACKLISTED', ['#EMAIL#' => $toEmail])),
			);
		}

		$mailboxHelper = \Bitrix\Mail\Helper\Mailbox::createInstance($fromMailboxId, false) ?: null;
		$activityFields = [
			'AUTHOR_ID' => $mailboxHelper?->getMailboxOwnerId() ?: $userId,
			'OWNER_TYPE_ID' => $entityTypeId,
			'OWNER_ID' => $entityId,
			'TYPE_ID' => \CCrmActivityType::Email,
			'SUBJECT' => $subject,
			'START_TIME' => (string)$now,
			'END_TIME' => (string)$now,
			'COMPLETED' => 'Y',
			'RESPONSIBLE_ID' => $userId,
			'PRIORITY' => \CCrmActivityPriority::Medium,
			'DESCRIPTION' => $activityDescription,
			'DESCRIPTION_TYPE' => $bodyContentType,
			'DIRECTION' => \CCrmActivityDirection::Outgoing,
			'BINDINGS' => $bindings,
			'COMMUNICATIONS' => [
				[
					'TYPE' => Multifield\Type\Email::ID,
					'VALUE' => $toEmail,
				] + $addressSource
			],
		];

		$attachments = [];
		$attachmentsStorageTypeId = StorageType::Undefined;

		if (
			!empty($options['ATTACHMENTS_IDS'])
			&& is_array($options['ATTACHMENTS_IDS'])
			&& isset($options['ATTACHMENTS_STORAGE_TYPE_ID'])
			&& $options['ATTACHMENTS_STORAGE_TYPE_ID'] === StorageType::File
		)
		{
			$attachmentsStorageTypeId = $options['ATTACHMENTS_STORAGE_TYPE_ID'];

			foreach ($options['ATTACHMENTS_IDS'] as $fileId)
			{
				$file = \CFile::MakeFileArray($fileId);
				if (is_array($file))
				{
					// each db row should reference its own b_file row. create new rows with same files for the activity
					$newBFileId = (int)\CFile::SaveFile($file, 'crm');
					if ($newBFileId > 0)
					{
						$attachments[] = $newBFileId;
					}
				}
			}
		}

		if (!empty($attachments) && StorageType::isDefined($attachmentsStorageTypeId))
		{
			$sizeCheckResult = self::checkAttachmentsFileSize($attachmentsStorageTypeId, $attachments);
			if (!$sizeCheckResult->isSuccess())
			{
				return $sizeCheckResult;
			}

			$activityFields['STORAGE_TYPE_ID'] = $attachmentsStorageTypeId;
			$activityFields['STORAGE_ELEMENT_IDS'] = $attachments;
		}

		\Bitrix\Crm\Activity\Provider\Email::compressActivity($activityFields);

		$activityId = (int)\CCrmActivity::Add(
			$activityFields,
			false,
			false,
			['REGISTER_SONET_EVENT' => true],
		);
		if ($activityId <= 0)
		{
			self::deleteFilesFromActivity($activityFields);

			$result =
				(new Result())
					->addError(new Error('Could not create an activity'))
			;

			foreach (\CCrmActivity::GetErrorMessages() as $errorMessage)
			{
				$result->addError(new Error($errorMessage));
			}

			return $result;
		}

		$urn = \CCrmActivity::PrepareUrn($activityFields);
		$messageId = self::compileMessageId($urn);

		$fromAddress = new \Bitrix\Main\Mail\Address($options['MESSAGE_FROM'] ?? null);

		$replyTo = self::compileReplyTo($fromAddress->getEmail());

		\CCrmActivity::Update(
			$activityId,
			[
				'URN' => $urn,
				'SETTINGS' => [
					'IS_BATCH_EMAIL' => self::isTrackOutgoingEmailsReadEnabled() ? false : null,
					'MESSAGE_HEADERS' => [
						'Message-Id' => $messageId,
						'Reply-To' => $replyTo ?: $fromAddress->get(),
					],
					'EMAIL_META' => [
						'__email' => $fromAddress->getEmail(),
						'from' => $fromAddress->get(),
						'replyTo' => $replyTo,
						'to' => $toEmail,
					],
				],
			],
			false,
			false,
			['REGISTER_SONET_EVENT' => true]
		);

		$bodyHtml = '';
		if ($body && $bodyContentType === \CCrmContentType::BBCode)
		{
			$bodyHtml = TextHelper::convertBbCodeToHtml($body);
		}
		elseif ($body)
		{
			$bodyHtml = $body;
		}

		if (
			($bodyContentType === \CCrmContentType::BBCode || $bodyContentType === \CCrmContentType::Html)
			&& mb_strpos($bodyHtml, '</html>') === false
		)
		{
			/** @noinspection HtmlRequiredLangAttribute */
			$bodyHtml = '<html><body>' . $bodyHtml . '</body></html>';
		}


		$outgoingSubject = $subject;

		$fromMailbox = current(array_filter(
			\Bitrix\Mail\MailboxTable::getUserMailboxes($userId),
			fn(array $mailbox) => $mailbox['EMAIL'] === $fromAddress->getEmail(),
		));

		$outgoingBody = $bodyHtml;

		$shouldInjectUrn = empty($fromMailbox);

		if ($shouldInjectUrn)
		{
			switch (\CCrmEMailCodeAllocation::getCurrent())
			{
				case \CCrmEMailCodeAllocation::Subject:
					$outgoingSubject = \CCrmActivity::injectUrnInSubject($urn, $outgoingSubject);
					break;
				case \CCrmEMailCodeAllocation::Body:
					$outgoingBody = \CCrmActivity::injectUrnInBody($urn, $outgoingBody);
					break;
			}
		}

		$emailAttachments = [];
		$attachmentsFileArrays = [];
		if (!empty($attachments) && StorageType::isDefined($attachmentsStorageTypeId))
		{
			$attachmentsFileArrays = StorageManager::makeFileArray($attachments, $attachmentsStorageTypeId);
			foreach ($attachmentsFileArrays as $fileArray)
			{
				$emailAttachments[] = [
					'ID' => $fileArray['external_id'],
					'NAME' => empty($fileArray['ORIGINAL_NAME']) ? $fileArray['name'] : $fileArray['ORIGINAL_NAME'],
					'PATH' => $fileArray['tmp_name'],
					'CONTENT_TYPE' => $fileArray['type'],
				];
			}
		}

		$fields = [
			'CHARSET' => SITE_CHARSET,
			'CONTENT_TYPE' => 'html',
			'ATTACHMENT' => $emailAttachments,
			'TO' => \Bitrix\Main\Mail\Mail::encodeHeaderFrom($toEmail, SITE_CHARSET),
			'SUBJECT' => $outgoingSubject,
			'BODY' => $outgoingBody ?: Loc::getMessage('CRM_INTEGRATION_MAIL_MANAGER_EMPTY_BODY_PLACEHOLDER'),
			'HEADER' => [
				'From' => $fromAddress->getEncoded() ?: $fromAddress->getEmail(),
				'Reply-To' => $replyTo,
				'Message-Id' => $messageId,
			],
			'TRACK_READ' => [
				'MODULE_ID' => 'crm',
				'FIELDS' => ['urn' => $urn],
				'URL_PAGE' => '/pub/mail/read.php',
			],
			'TRACK_CLICK' => [
				'MODULE_ID' => 'crm',
				'FIELDS' => ['urn' => $urn],
				'URL_PAGE' => '/pub/mail/click.php',
				'URL_PARAMS' => [],
			],
			'CONTEXT' =>
				(new \Bitrix\Main\Mail\Context())
					->setCategory(\Bitrix\Main\Mail\Context::CAT_EXTERNAL)
					->setPriority(\Bitrix\Main\Mail\Context::PRIORITY_LOW)
					->setCallback(
						(new \Bitrix\Main\Mail\Callback\Config())
							->setModuleId('crm')
							->setEntityType(self::CALLBACK_ENTITY_TYPE)
							->setEntityId($urn)
					)
			,
		];

		if (\Bitrix\Crm\WebForm\Manager::isEmbeddingAvailable())
		{
			$fields['TRACK_CLICK']['URL_PARAMS'][\Bitrix\Crm\WebForm\Embed\Sign::uriParameterName] =
				(new \Bitrix\Crm\WebForm\Embed\Sign())
					->addEntity($entityTypeId, $entityId)
					->pack()
			;
		}

		$isSuccess = \Bitrix\Main\Mail\Mail::send($fields);
		if (!$isSuccess)
		{
			\CCrmActivity::Delete($activityId);

			return self::tryGetResultWithSendFailReason();
		}

		addEventToStatFile(
			'crm',
			'send_email_message',
			'mail_manager',
			trim(trim($messageId), '<>')
		);

		if (is_array($fromMailbox))
		{
			\Bitrix\Mail\Helper::addImapMessage(
				$fromMailbox,
				(string)(new \Bitrix\Mail\DummyMail(array_merge(
					$fields,
					[
						'HEADER' => array_merge(
							$fields['HEADER'],
							[
								'To' => $fields['TO'],
								'Subject' => $fields['SUBJECT'],
							]
						),
					]
				))),
				$errorThatWeDontCareAbout,
			);
		}

		$eventBindings = array_map(
			fn(array $singleBinding) => [
				'ENTITY_TYPE' => \CCrmOwnerType::ResolveName($singleBinding['OWNER_TYPE_ID']),
				'ENTITY_ID' => $singleBinding['OWNER_ID'],
			],
			$bindings,
		);

		(new \CCrmEvent())->Add([
			'ENTITY' => array_unique($eventBindings, SORT_REGULAR),
			'EVENT_ID' => 'MESSAGE',
			'EVENT_TEXT_1' => str_replace(
				'<br/>',
				"\r\n",
				Loc::getMessage(
					'CRM_INTEGRATION_MAIL_MANAGER_HISTORY_EVENT_TEXT',
					[
						'#SUBJECT#' => $subject,
						'#FROM#' => $fromAddress->getEmail(),
						'#TO#' => $toEmail,
						'#MESSAGE_BODY#' => $bodyHtml,
					]
				)
			),
			'FILES' => $attachmentsFileArrays,
		]);

		return new Result();
	}

	/**
	 * @inheritDoc
	 *
	 * @param array{
	 *     ATTACHMENTS_STORAGE_TYPE_ID: ?int,
	 *     ATTACHMENTS_IDS: null | int[],
	 *     MESSAGE_SUBJECT: ?string,
	 *     MESSAGE_BODY: ?string,
	 *     MESSAGE_BODY_CONTENT_TYPE: ?int,
	 *     MESSAGE_FROM: string,
	 * } $options
	 */
	public static function makeMessageFields(array $options, array $commonOptions): array
	{
		// too many fields depend on activity id. creating activity before actual sending seems wrong to me, so all
		// the logic for making fields is performed in self::sendMessage
		return ['OPTIONS' => $options, 'COMMON' => $commonOptions];
	}

	private static function resolveItem(array $commonOptions): array
	{
		$entityTypeId = \CCrmOwnerType::Undefined;
		if (isset($commonOptions['ADDITIONAL_FIELDS']['ROOT_SOURCE']['ENTITY_TYPE_ID']))
		{
			$entityTypeId = (int)$commonOptions['ADDITIONAL_FIELDS']['ROOT_SOURCE']['ENTITY_TYPE_ID'];
		}

		$entityId = 0;
		if (isset($commonOptions['ADDITIONAL_FIELDS']['ROOT_SOURCE']['ENTITY_ID']))
		{
			$entityId = (int)$commonOptions['ADDITIONAL_FIELDS']['ROOT_SOURCE']['ENTITY_ID'];
		}

		if (!\CCrmOwnerType::IsDefined($entityTypeId) || $entityId <= 0)
		{
			if (
				!empty($commonOptions['ADDITIONAL_FIELDS']['BINDINGS'])
				&& is_array($commonOptions['ADDITIONAL_FIELDS']['BINDINGS'])
			)
			{
				$binding = reset($commonOptions['ADDITIONAL_FIELDS']['BINDINGS']);

				$entityTypeId = (int)($binding['OWNER_TYPE_ID'] ?? $entityTypeId);
				$entityId = (int)($binding['OWNER_ID'] ?? $entityId);
			}
		}

		return [$entityTypeId, $entityId];
	}

	private static function compileReplyTo(?string $fromEmail): string
	{
		$addresses = [];

		if (!empty($fromEmail))
		{
			$addresses[] = $fromEmail;
		}

		$crmEmail = self::getCrmEmail();
		if (!empty($crmEmail) && $fromEmail !== $crmEmail)
		{
			$addresses[] = $crmEmail;
		}

		return implode(', ', $addresses);
	}

	private static function compileMessageId(string $urn): string
	{
		if (defined('BX24_HOST_NAME'))
		{
			$host = BX24_HOST_NAME;
		}
		elseif (defined('SITE_SERVER_NAME'))
		{
			$host = SITE_SERVER_NAME;
		}
		else
		{
			$host = Option::get('main', 'server_name');
		}

		return "<crm.activity.{$urn}@{$host}>";
	}

	private static function getCrmEmail(): string
	{
		return (string)\CCrmMailHelper::extractEmail(\COption::getOptionString('crm', 'mail'));
	}

	private static function isTrackOutgoingEmailsReadEnabled(): bool
	{
		return (Option::get('main', 'track_outgoing_emails_read', 'Y') === 'Y');
	}

	private static function isReceiverBlacklisted(string $toEmail): bool
	{
		$row =
			\Bitrix\Main\Mail\Internal\BlacklistTable::query()
				->setSelect(['CODE'])
				->where('CODE', $toEmail)
				->fetch()
		;

		return (is_array($row) && !empty($row));
	}

	private static function checkAttachmentsFileSize(int $storageTypeId, array $storageElementsIds): Result
	{
		$result = new Result();
		if (self::isMaxAttachmentsFileSizeExceeded($storageTypeId, $storageElementsIds))
		{
			$maxSizePretty = \CFile::FormatSize(self::getMaxAttachmentsFileSize());

			$result->addError(
				new Error(Loc::getMessage('CRM_INTEGRATION_MAIL_MANAGER_ERROR_TOO_BIG_ATTACHMENT', ['#SIZE#' => $maxSizePretty]))
			);
		}

		return $result;
	}

	private static function isMaxAttachmentsFileSizeExceeded(int $storageTypeId, array $storageElementsIds): bool
	{
		$maxSize = self::getMaxAttachmentsFileSize();

		if ($maxSize <= 0)
		{
			return false;
		}

		$attachmentsFileArrays = StorageManager::makeFileArray($storageElementsIds, $storageTypeId);
		$totalSize = 0;
		foreach ($attachmentsFileArrays as $fileArray)
		{
			$size = $fileArray['size'] ?? 0;

			$totalSize += $size;
		}

		$totalSizeBase64Encoded = ceil($totalSize / 3) * 4;

		return $totalSizeBase64Encoded >= $maxSize;
	}

	private static function getMaxAttachmentsFileSize(): int
	{
		if (!self::canUse())
		{
			return 0;
		}

		return \Bitrix\Mail\Helper\Message::getMaxAttachedFilesSize();
	}

	private static function tryGetResultWithSendFailReason(): Result
	{
		$result = new Result();

		if (\CModule::includeModule('bitrix24'))
		{
			if (
				method_exists(\Bitrix\Bitrix24\MailCounter::class, 'isLimited')
				&& \Bitrix\Bitrix24\MailCounter::isLimited()
			)
			{
				$result->addError(
					new Error(Loc::getMessage('CRM_INTEGRATION_MAIL_MANAGER_ERROR_MONTHLY_LIMIT')),
				);
			}
			elseif (
				method_exists(\Bitrix\Bitrix24\MailCounter::class, 'isCustomLimited')
				&& \Bitrix\Bitrix24\MailCounter::isCustomLimited())
			{
				$result->addError(
					new Error(Loc::getMessage('CRM_INTEGRATION_MAIL_MANAGER_ERROR_DAILY_LIMIT')),
				);
			}
		}

		if (empty($result->getErrors()))
		{
			$result->addError(new Error(Loc::getMessage('CRM_INTEGRATION_MAIL_MANAGER_ERROR_UNKNOWN')));
		}

		return $result;
	}

	private static function deleteFilesFromActivity(array $activityFields): void
	{
		$storageTypeId = (int)($activityFields['STORAGE_TYPE_ID'] ?? StorageType::Undefined);
		if (!StorageType::isDefined($storageTypeId))
		{
			return;
		}

		$fileIds = $activityFields['STORAGE_ELEMENT_IDS'] ?? null;
		if (!is_array($fileIds) || empty($fileIds))
		{
			return;
		}

		if ($storageTypeId === StorageType::File)
		{
			$uploader = Container::getInstance()->getFileUploader();
			foreach ($fileIds as $fileId)
			{
				$uploader->deleteFilePersistently($fileId);
			}
		}
		else
		{
			$storageTypeName = StorageType::resolveName($storageTypeId);

			throw new InvalidOperationException("Unknown storage type id {$storageTypeId} ({$storageTypeName})");
		}
	}
}
