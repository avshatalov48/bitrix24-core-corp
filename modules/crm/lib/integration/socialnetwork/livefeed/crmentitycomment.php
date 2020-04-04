<?
namespace Bitrix\Crm\Integration\Socialnetwork\Livefeed;

use \Bitrix\Socialnetwork\Livefeed\Provider;
use \Bitrix\Socialnetwork\LogCommentTable;
use \Bitrix\Main\Config\Option;

final class CrmEntityComment extends \Bitrix\Socialnetwork\Livefeed\Provider
{
	const PROVIDER_ID = 'CRM_ENTITY_COMMENT';
	const CONTENT_TYPE_ID = 'CRM_ENTITY_COMMENT';

	protected $logEventId = null;
	protected $logEntityType = null;
	protected $logEntityId = null;

	public static function getId()
	{
		return static::PROVIDER_ID;
	}

	public function getEventId()
	{
		return array(
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
			\CCrmLiveFeedEvent::ActivityPrefix.\CCrmLiveFeedEvent::Add.\CCrmLiveFeedEvent::CommentSuffix
		);
	}

	public function getType()
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
				$logId = intval($logComentFields['LOG_ID']);
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
						"/\[USER\s*=\s*([^\]]*)\](.+?)\[\/USER\]/is".BX_UTF_PCRE_MODIFIER,
						"\\2",
						$title
					);
					$CBXSanitizer = new \CBXSanitizer;
					$CBXSanitizer->delAllTags();
					$title = preg_replace(array("/\n+/is".BX_UTF_PCRE_MODIFIER, "/\s+/is".BX_UTF_PCRE_MODIFIER), " ", $CBXSanitizer->sanitizeHtml($title));
					$this->setSourceTitle(truncateText($title, 100));
					$this->setSourceAttachedDiskObjects($this->getAttachedDiskObjects($commentId));
					$this->setSourceDiskObjects($this->getDiskObjects($commentId, $this->cloneDiskObjects));
				}
			}
		}

	}

	protected function getAttachedDiskObjects($clone = false)
	{
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

	public function getLiveFeedUrl()
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

	public static function canRead($params)
	{
		return true;
	}

	protected function getPermissions(array $post)
	{
		$result = self::PERMISSION_READ;

		return $result;
	}

	public function add($params = array())
	{
		global $USER, $DB;

		static $parser = null;

		$authorId = (
			isset($params['AUTHOR_ID'])
			&& intval($params['AUTHOR_ID']) > 0
				? intval($params['AUTHOR_ID'])
				: $USER->getId()
		);

		$message = (
			isset($params['MESSAGE'])
			&& strlen($params['MESSAGE']) > 0
				? $params['MESSAGE']
				: ''
		);

		if (strlen($message) <= 0)
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
			"=LOG_DATE" => $DB->currentTimeFunction(),
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

	public function getSuffix()
	{
		$logEventId = $this->getLogEventId();

		if (!empty($logEventId))
		{
			$providerCrmLead = new CrmLead();
			if (in_array($logEventId, $providerCrmLead->getEventId()))
			{
				return 'LEAD';
			}
			$providerCrmContact = new CrmContact();
			if (in_array($logEventId, $providerCrmContact->getEventId()))
			{
				return 'CONTACT';
			}
			$providerCrmCompany = new CrmCompany();
			if (in_array($logEventId, $providerCrmCompany->getEventId()))
			{
				return 'COMPANY';
			}
			$providerCrmDeal = new CrmDeal();
			if (in_array($logEventId, $providerCrmDeal->getEventId()))
			{
				return 'DEAL';
			}
			$providerCrmInvoice = new CrmInvoice();
			if (in_array($logEventId, $providerCrmInvoice->getEventId()))
			{
				return 'INVOICE';
			}
			$providerCrmActivity = new CrmActivity();
			if (in_array($logEventId, $providerCrmActivity->getEventId()))
			{
				return 'ACTIVITY';
			}
		}

		return '';
	}


}