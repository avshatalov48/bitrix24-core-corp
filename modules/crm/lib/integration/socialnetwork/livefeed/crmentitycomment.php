<?php

namespace Bitrix\Crm\Integration\Socialnetwork\Livefeed;

use Bitrix\Socialnetwork\Livefeed\Provider;
use Bitrix\Socialnetwork\LogCommentTable;

final class CrmEntityComment extends Provider
{
	public const PROVIDER_ID = 'CRM_ENTITY_COMMENT';
	public const CONTENT_TYPE_ID = 'CRM_ENTITY_COMMENT';

	protected $logEventId = null;
	protected $logEntityType = null;
	protected $logEntityId = null;

	public static function getId(): string
	{
		return static::PROVIDER_ID;
	}

	public function getEventId(): array
	{
		return [
			\CCrmLiveFeedEvent::LeadPrefix.\CCrmLiveFeedEvent::Add.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::LeadPrefix.\CCrmLiveFeedEvent::Progress.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::LeadPrefix.\CCrmLiveFeedEvent::Denomination.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::LeadPrefix.\CCrmLiveFeedEvent::Responsible.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::LeadPrefix.\CCrmLiveFeedEvent::Message.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::ContactPrefix.\CCrmLiveFeedEvent::Add.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::ContactPrefix.\CCrmLiveFeedEvent::Owner.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::ContactPrefix.\CCrmLiveFeedEvent::Denomination.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::ContactPrefix.\CCrmLiveFeedEvent::Responsible.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::ContactPrefix.\CCrmLiveFeedEvent::Message.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::CompanyPrefix.\CCrmLiveFeedEvent::Add.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::CompanyPrefix.\CCrmLiveFeedEvent::Denomination.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::CompanyPrefix.\CCrmLiveFeedEvent::Responsible.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::CompanyPrefix.\CCrmLiveFeedEvent::Message.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::DealPrefix.\CCrmLiveFeedEvent::Add.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::DealPrefix.\CCrmLiveFeedEvent::Client.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::DealPrefix.\CCrmLiveFeedEvent::Progress.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::DealPrefix.\CCrmLiveFeedEvent::Responsible.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::DealPrefix.\CCrmLiveFeedEvent::Progress.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::DealPrefix.\CCrmLiveFeedEvent::Message.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::InvoicePrefix.\CCrmLiveFeedEvent::Add.\CCrmLiveFeedEvent::CommentSuffix,
			\CCrmLiveFeedEvent::ActivityPrefix.\CCrmLiveFeedEvent::Add.\CCrmLiveFeedEvent::CommentSuffix,
		];
	}

	public function getType(): string
	{
		return Provider::TYPE_COMMENT;
	}

	public function initSourceFields()
	{
		$commentId = $this->getEntityId();

		if ($commentId > 0)
		{
			$logId = false;

			$res = LogCommentTable::getList(array(
				'filter' => array(
					'=ID' => $commentId,
					'@EVENT_ID' => $this->getEventId(),
				),
				'select' => array('LOG_ID', 'MESSAGE')
			));
			if ($logComentFields = $res->fetch())
			{
				$logId = (int)$logComentFields['LOG_ID'];
			}

			if ($logId)
			{
				$res = \CSocNetLog::getList(
					array(),
					array(
						'=ID' => $logId
					),
					false,
					false,
					array('ID', 'EVENT_ID'),
					array(
						"CHECK_CRM_RIGHTS" => "Y",
						"IS_CRM" => "Y"
					)
				);
				if ($logFields = $res->fetch())
				{
					$this->setLogId($logFields['ID']);
					$this->setSourceFields(array_merge($logComentFields, array('LOG_EVENT_ID' => $logFields['EVENT_ID'])));
					$this->setSourceDescription($logComentFields['MESSAGE']);

					$title = htmlspecialcharsback($logComentFields['MESSAGE']);
					$title = preg_replace(
						"/\[USER\s*=\s*([^\]]*)\](.+?)\[\/USER\]/isu",
						"\\2",
						$title
					);
					$CBXSanitizer = new \CBXSanitizer;
					$CBXSanitizer->delAllTags();
					$title = preg_replace(array("/\n+/isu", "/\s+/isu"), " ", $CBXSanitizer->sanitizeHtml($title));
					$this->setSourceTitle(truncateText($title, 100));
					$this->setSourceAttachedDiskObjects($this->getAttachedDiskObjects($this->cloneDiskObjects));
					$this->setSourceDiskObjects($this->getDiskObjects($commentId, $this->cloneDiskObjects));
				}
			}
		}

	}

	protected function getAttachedDiskObjects($clone = false)
	{
		if (method_exists($this, 'getEntityAttachedDiskObjects'))
		{
			return $this->getEntityAttachedDiskObjects([
				'userFieldEntity' => 'SONET_COMMENT',
				'userFieldCode' => 'UF_SONET_COM_DOC',
				'clone' => $clone,
			]);
		}

		global $USER_FIELD_MANAGER;
		static $cache = array();

		$messageId = $this->entityId;

		$result = array();
		$cacheKey = $messageId.$clone;

		if (isset($cache[$cacheKey]))
		{
			$result = $cache[$cacheKey];
		}
		else
		{
			$messageUF = $USER_FIELD_MANAGER->getUserFields("SONET_COMMENT", $messageId, LANGUAGE_ID);
			if (
				!empty($messageUF['UF_SONET_COM_DOC'])
				&& !empty($messageUF['UF_SONET_COM_DOC']['VALUE'])
				&& is_array($messageUF['UF_SONET_COM_DOC']['VALUE'])
			)
			{
				if ($clone)
				{
					$this->attachedDiskObjectsCloned = self::cloneUfValues($messageUF['UF_SONET_COM_DOC']['VALUE']);
					$result = $cache[$cacheKey] = array_values($this->attachedDiskObjectsCloned);
				}
				else
				{
					$result = $cache[$cacheKey] = $messageUF['UF_SONET_COM_DOC']['VALUE'];
				}
			}
		}

		return $result;
	}

	public function getLiveFeedUrl(): string
	{
		$result = '';
		$logId = $this->getLogId();
		$commentId = $this->getEntityId();

		if ($logId > 0)
		{
			$result = "/crm/stream/?log_id=".$logId."&commentId=".$commentId."#com".$commentId;
		}

		return $result;
	}

	public static function canRead($params): bool
	{
		return true;
	}

	protected function getPermissions(array $post): string
	{
		return self::PERMISSION_READ;
	}

	public function add($params = array())
	{
		global $USER;

		static $parser = null;

		$authorId = (
			isset($params['AUTHOR_ID'])
			&& (int)$params['AUTHOR_ID'] > 0
				? (int)$params['AUTHOR_ID']
				: $USER->getId()
		);

		$message = (string)(
			isset($params['MESSAGE'])
			&& $params['MESSAGE'] <> ''
				? $params['MESSAGE']
				: ''
		);

		if ($message === '')
		{
			return false;
		}

		$logId = $this->getLogId();

		if (!$logId)
		{
			return false;
		}

		$this->setLogId($logId);

		if ($parser === null)
		{
			$parser = new \CTextParser();
		}

		$logFields = $this->getLogFields();

		$sonetCommentFields = array(
			"ENTITY_TYPE" => $this->getLogEntityType(),
			"ENTITY_ID" => $this->getLogEntityId(),
			"EVENT_ID" => $logFields['EVENT_ID'].\CCrmLiveFeedEvent::CommentSuffix,
			"MESSAGE" => $message,
			"TEXT_MESSAGE" => $parser->convert4mail($message),
			"MODULE_ID" => "tasks",
			"LOG_ID" => $logId,
			"RATING_TYPE_ID" => "LOG_COMMENT",
			"USER_ID" => $authorId,
			"=LOG_DATE" => \CDatabase::CurrentTimeFunction(),
		);

		if (!empty($params['SHARE_DEST']))
		{
			$sonetCommentFields['SHARE_DEST'] = $params['SHARE_DEST'];
		}

		if ($sonetCommentId = \CSocNetLogComments::add($sonetCommentFields, false, false))
		{
			\CSocNetLogComments::update($sonetCommentId, array(
				"RATING_ENTITY_ID" => $sonetCommentId
			));
		}

		return $sonetCommentId;
	}

	public function getSuffix(): string
	{
		$logEventId = $this->getLogEventId();

		if (!empty($logEventId))
		{
			$providerCrmLead = new CrmLead();
			if (in_array($logEventId, $providerCrmLead->getMessageEventId(), true))
			{
				return 'LEAD_MESSAGE';
			}
			if (in_array($logEventId, $providerCrmLead->getEventId(), true))
			{
				return 'LEAD';
			}

			$providerCrmContact = new CrmContact();
			if (in_array($logEventId, $providerCrmContact->getMessageEventId(), true))
			{
				return 'CONTACT_MESSAGE';
			}
			if (in_array($logEventId, $providerCrmContact->getEventId(), true))
			{
				return 'CONTACT';
			}

			$providerCrmCompany = new CrmCompany();
			if(in_array($logEventId, $providerCrmCompany->getMessageEventId(), true))
			{
				return 'COMPANY_MESSAGE';
			}
			if (in_array($logEventId, $providerCrmCompany->getEventId(), true))
			{
				return 'COMPANY';
			}

			$providerCrmDeal = new CrmDeal();
			if (in_array($logEventId, $providerCrmDeal->getMessageEventId(), true))
			{
				return 'DEAL_MESSAGE';
			}
			if (in_array($logEventId, $providerCrmDeal->getEventId(), true))
			{
				return 'DEAL';
			}

			$providerCrmInvoice = new CrmInvoice();
			if (in_array($logEventId, $providerCrmInvoice->getEventId(), true))
			{
				return 'INVOICE';
			}

			$providerCrmActivity = new CrmActivity();
			if (in_array($logEventId, $providerCrmActivity->getEventId(), true))
			{
				return 'ACTIVITY';
			}
		}

		return '';
	}


}
