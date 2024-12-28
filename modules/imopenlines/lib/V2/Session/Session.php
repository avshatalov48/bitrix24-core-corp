<?php

namespace Bitrix\ImOpenLines\V2\Session;

use ArrayAccess;
use Bitrix\Im\V2\ActiveRecord;
use Bitrix\Im\V2\Chat;
use Bitrix\Im\V2\Common\ActiveRecordImplementation;
use Bitrix\Im\V2\Common\ContextCustomer;
use Bitrix\Im\V2\Common\FieldAccessImplementation;
use Bitrix\Im\V2\Common\RegistryEntryImplementation;
use Bitrix\Im\V2\Entity\User\UserPopupItem;
use Bitrix\Im\V2\RegistryEntry;
use Bitrix\Im\V2\Rest\PopupData;
use Bitrix\Im\V2\Rest\PopupDataAggregatable;
use Bitrix\Im\V2\Rest\PopupDataItem;
use Bitrix\Im\V2\Rest\RestEntity;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\ImOpenLines\Rest;
use Bitrix\ImOpenLines\V2\Status\StatusGroup;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

Loader::requireModule('im');

class Session implements RegistryEntry, ActiveRecord, PopupDataItem, RestEntity, PopupDataAggregatable
{
	use FieldAccessImplementation;
	use ActiveRecordImplementation;
	use RegistryEntryImplementation;
	use ContextCustomer;

	/**
	 * @var self[]
	 */
	protected static array $sessionStaticCache = [];
	protected ?int $id = null;
	protected ?string $mode = null;
	protected ?int $status = null;
	protected ?string $source = null;
	protected ?int $configId = null;
	protected ?int $operatorId = null;
	protected bool $operatorFromCrm = false;
	protected ?string $userCode = null;
	protected int $parentId = 0;
	protected ?int $userId = null;
	protected ?int $chatId = null;
	protected ?int $messageCount = null;
	protected ?int $likeCount = null;
	protected ?int $startId = null;
	protected ?int $endId = null;
	protected bool $crm = false;
	protected bool $crmCreate = false;
	protected bool $crmCreateLead = false;
	protected bool $crmCreateCompany = false;
	protected bool $crmCreateContact = false;
	protected bool $crmCreateDeal = false;
	protected ?int $crmActivityId = null;
	protected ?DateTime $dateCreate = null;
	protected ?DateTime $dateOperator = null;
	protected ?DateTime $dateModify = null;
	protected ?DateTime $dateOperatorAnswer = null;
	protected ?DateTime $dateOperatorClose = null;
	protected ?DateTime $dateFirstAnswer = null;
	protected ?DateTime $dateLastMessage = null;
	protected ?DateTime $dateFirstLastUserAction = null;
	protected ?DateTime $dateClose = null;
	protected ?DateTime $dateCloseVote = null;
	protected ?int $timeFirstAnswer = null;
	protected ?int $timeAnswer = null;
	protected ?int $timeClose = null;
	protected ?int $timeBot = null;
	protected ?int $timeDialog = null;
	protected ?int $categoryId = null;
	protected bool $waitAction = false;
	protected bool $waitAnswer = false;
	protected bool $waitVote = false;
	protected ?string $sendForm = null;
	protected bool $sendHistory = false;
	protected bool $closed = false;
	protected bool $pause = false;
	protected bool $spam = false;
	protected bool $worktime = false;
	protected ?string $queueHistory = null;

	protected ?Chat $chat = null;

	public function __construct(mixed $source = null)
	{
		$this->initByDefault();

		if (!empty($source))
		{
			$this->load($source);
		}

		if (isset($this->id))
		{
			self::$sessionStaticCache[$this->id] = $this;
		}
	}

	public static function getInstance(?int $sessionId): ?self
	{
		if (!isset($sessionId))
		{
			return null;
		}

		if (isset(self::$sessionStaticCache[$sessionId]))
		{
			return self::$sessionStaticCache[$sessionId];
		}

		return new Session($sessionId);
	}

	public static function getInstanceByChatId(?int $chatId): ?self
	{
		if (!isset($chatId))
		{
			return null;
		}

		$sessionId = SessionTable::query()
			->setSelect([Query::expr('SESSION_ID')->max('ID')])
			->where('CHAT_ID', $chatId)
			->fetch()['SESSION_ID']
		;

		return self::getInstance((int)$sessionId);
	}

	public function isClosed(): bool
	{
		return $this->closed;
	}

	public function setIsClosed(?bool $isClosed): self
	{
		$this->closed = $isClosed ?? false;

		return $this;
	}

	public function getPinMode(): bool
	{
		return $this->pause;
	}

	public function setPinMode(?bool $pinMode): self
	{
		$this->pause = $pinMode ?? false;

		return $this;
	}

	public function getConfigId(): ?int
	{
		return $this->configId;
	}

	public function setConfigId(?int $configId): self
	{
		$this->configId = $configId;

		return $this;
	}

	public function getSessionId(): ?int
	{
		return $this->id;
	}

	public function setSessionId(?int $id): self
	{
		$this->id = $id;
		if (isset($id))
		{
			self::$sessionStaticCache[$id] = $this;
		}

		return $this;
	}

	public function getChatId(): ?int
	{
		return $this->chatId;
	}

	public function setChatId(?int $chatId): self
	{
		$this->chatId = $chatId;

		return $this;
	}

	public function getChat(): ?Chat
	{
		return $this->chat;
	}

	public function setChat(?Chat $chat): self
	{
		$this->chat = $chat;

		return $this;
	}

	public function getStatus(): ?int
	{
		return $this->status;
	}

	public function setStatus(?int $status): self
	{
		$this->status = $status;

		return $this;
	}

	public function getOperatorId(): ?int
	{
		return $this->operatorId;
	}

	public function setOperatorId(?int $operatorId): self
	{
		$this->operatorId = $operatorId;

		return $this;
	}

	public function getPrimaryId(): ?int
	{
		return $this->id;
	}

	public function setPrimaryId(int $primaryId): self
	{
		return $this->setSessionId($primaryId);
	}

	public function getMode(): ?string
	{
		return $this->mode;
	}

	public function setMode(?string $mode): void
	{
		$this->mode = $mode;
	}

	public function getSource(): ?string
	{
		return $this->source;
	}

	public function setSource(?string $source): void
	{
		$this->source = $source;
	}

	public function isOperatorFromCrm(): bool
	{
		return $this->operatorFromCrm;
	}

	public function setOperatorFromCrm(bool $operatorFromCrm): void
	{
		$this->operatorFromCrm = $operatorFromCrm;
	}

	public function getUserCode(): ?string
	{
		return $this->userCode;
	}

	public function setUserCode(?string $userCode): void
	{
		$this->userCode = $userCode;
	}

	public function getParentId(): int
	{
		return $this->parentId;
	}

	public function setParentId(int $parentId): void
	{
		$this->parentId = $parentId;
	}

	public function getUserId(): ?int
	{
		return $this->userId;
	}

	public function setUserId(?int $userId): void
	{
		$this->userId = $userId;
	}

	public function getMessageCount(): ?int
	{
		return $this->messageCount;
	}

	public function setMessageCount(?int $messageCount): void
	{
		$this->messageCount = $messageCount;
	}

	public function getLikeCount(): ?int
	{
		return $this->likeCount;
	}

	public function setLikeCount(?int $likeCount): void
	{
		$this->likeCount = $likeCount;
	}

	public function getStartId(): ?int
	{
		return $this->startId;
	}

	public function setStartId(?int $startId): void
	{
		$this->startId = $startId;
	}

	public function getEndId(): ?int
	{
		return $this->endId;
	}

	public function setEndId(?int $endId): void
	{
		$this->endId = $endId;
	}

	public function isCrm(): bool
	{
		return $this->crm;
	}

	public function setCrm(bool $crm): void
	{
		$this->crm = $crm;
	}

	public function isCrmCreate(): bool
	{
		return $this->crmCreate;
	}

	public function setCrmCreate(bool $crmCreate): void
	{
		$this->crmCreate = $crmCreate;
	}

	public function isCrmCreateLead(): bool
	{
		return $this->crmCreateLead;
	}

	public function setCrmCreateLead(bool $crmCreateLead): void
	{
		$this->crmCreateLead = $crmCreateLead;
	}

	public function isCrmCreateCompany(): bool
	{
		return $this->crmCreateCompany;
	}

	public function setCrmCreateCompany(bool $crmCreateCompany): void
	{
		$this->crmCreateCompany = $crmCreateCompany;
	}

	public function isCrmCreateContact(): bool
	{
		return $this->crmCreateContact;
	}

	public function setCrmCreateContact(bool $crmCreateContact): void
	{
		$this->crmCreateContact = $crmCreateContact;
	}

	public function isCrmCreateDeal(): bool
	{
		return $this->crmCreateDeal;
	}

	public function setCrmCreateDeal(bool $crmCreateDeal): void
	{
		$this->crmCreateDeal = $crmCreateDeal;
	}

	public function getCrmActivityId(): ?int
	{
		return $this->crmActivityId;
	}

	public function setCrmActivityId(?int $crmActivityId): void
	{
		$this->crmActivityId = $crmActivityId;
	}

	public function getDateCreate(): ?DateTime
	{
		return $this->dateCreate;
	}

	public function setDateCreate(?DateTime $dateCreate): void
	{
		$this->dateCreate = $dateCreate;
	}

	public function getDateOperator(): ?DateTime
	{
		return $this->dateOperator;
	}

	public function setDateOperator(?DateTime $dateOperator): void
	{
		$this->dateOperator = $dateOperator;
	}

	public function getDateModify(): ?DateTime
	{
		return $this->dateModify;
	}

	public function setDateModify(?DateTime $dateModify): void
	{
		$this->dateModify = $dateModify;
	}

	public function getDateOperatorAnswer(): ?DateTime
	{
		return $this->dateOperatorAnswer;
	}

	public function setDateOperatorAnswer(?DateTime $dateOperatorAnswer): void
	{
		$this->dateOperatorAnswer = $dateOperatorAnswer;
	}

	public function getDateOperatorClose(): ?DateTime
	{
		return $this->dateOperatorClose;
	}

	public function setDateOperatorClose(?DateTime $dateOperatorClose): void
	{
		$this->dateOperatorClose = $dateOperatorClose;
	}

	public function getDateFirstAnswer(): ?DateTime
	{
		return $this->dateFirstAnswer;
	}

	public function setDateFirstAnswer(?DateTime $dateFirstAnswer): void
	{
		$this->dateFirstAnswer = $dateFirstAnswer;
	}

	public function getDateLastMessage(): ?DateTime
	{
		return $this->dateLastMessage;
	}

	public function setDateLastMessage(?DateTime $dateLastMessage): void
	{
		$this->dateLastMessage = $dateLastMessage;
	}

	public function getDateFirstLastUserAction(): ?DateTime
	{
		return $this->dateFirstLastUserAction;
	}

	public function setDateFirstLastUserAction(?DateTime $dateFirstLastUserAction): void
	{
		$this->dateFirstLastUserAction = $dateFirstLastUserAction;
	}

	public function getDateClose(): ?DateTime
	{
		return $this->dateClose;
	}

	public function setDateClose(?DateTime $dateClose): void
	{
		$this->dateClose = $dateClose;
	}

	public function getDateCloseVote(): ?DateTime
	{
		return $this->dateCloseVote;
	}

	public function setDateCloseVote(?DateTime $dateCloseVote): void
	{
		$this->dateCloseVote = $dateCloseVote;
	}

	public function getTimeFirstAnswer(): ?int
	{
		return $this->timeFirstAnswer;
	}

	public function setTimeFirstAnswer(?int $timeFirstAnswer): void
	{
		$this->timeFirstAnswer = $timeFirstAnswer;
	}

	public function getTimeAnswer(): ?int
	{
		return $this->timeAnswer;
	}

	public function setTimeAnswer(?int $timeAnswer): void
	{
		$this->timeAnswer = $timeAnswer;
	}

	public function getTimeClose(): ?int
	{
		return $this->timeClose;
	}

	public function setTimeClose(?int $timeClose): void
	{
		$this->timeClose = $timeClose;
	}

	public function getTimeBot(): ?int
	{
		return $this->timeBot;
	}

	public function setTimeBot(?int $timeBot): void
	{
		$this->timeBot = $timeBot;
	}

	public function getTimeDialog(): ?int
	{
		return $this->timeDialog;
	}

	public function setTimeDialog(?int $timeDialog): void
	{
		$this->timeDialog = $timeDialog;
	}

	public function getCategoryId(): ?int
	{
		return $this->categoryId;
	}

	public function setCategoryId(?int $categoryId): void
	{
		$this->categoryId = $categoryId;
	}

	public function isWaitAction(): bool
	{
		return $this->waitAction;
	}

	public function setWaitAction(bool $waitAction): void
	{
		$this->waitAction = $waitAction;
	}

	public function isWaitAnswer(): bool
	{
		return $this->waitAnswer;
	}

	public function setWaitAnswer(bool $waitAnswer): void
	{
		$this->waitAnswer = $waitAnswer;
	}

	public function isWaitVote(): bool
	{
		return $this->waitVote;
	}

	public function setWaitVote(bool $waitVote): void
	{
		$this->waitVote = $waitVote;
	}

	public function getSendForm(): ?string
	{
		return $this->sendForm;
	}

	public function setSendForm(?string $sendForm): void
	{
		$this->sendForm = $sendForm;
	}

	public function isSendHistory(): bool
	{
		return $this->sendHistory;
	}

	public function setSendHistory(bool $sendHistory): void
	{
		$this->sendHistory = $sendHistory;
	}

	public function isPause(): bool
	{
		return $this->pause;
	}

	public function setPause(bool $pause): void
	{
		$this->pause = $pause;
	}

	public function isSpam(): bool
	{
		return $this->spam;
	}

	public function setSpam(bool $spam): void
	{
		$this->spam = $spam;
	}

	public function isWorktime(): bool
	{
		return $this->worktime;
	}

	public function setWorktime(bool $worktime): void
	{
		$this->worktime = $worktime;
	}

	public static function getDataClass(): string
	{
		return SessionTable::class;
	}

	public function getId(): ?int
	{
		return $this->getPrimaryId();
	}

	public function getStatusGroup(): ?StatusGroup
	{
		if (isset($this->status))
		{
			return StatusGroup::getFromNumericalCode($this->status);
		}

		return null;
	}

	protected static function mirrorDataEntityFields(): array
	{
		return [
			'ID' => [
				'primary' => true,
				'field' => 'id',
				'set' => 'setId',
				'get' => 'getId',
			],
			'MODE' => [
				'field' => 'mode',
				'set' => 'setMode',
				'get' => 'getMode',
			],
			'STATUS' => [
				'field' => 'status',
				'set' => 'setStatus',
				'get' => 'getStatus',
			],
			'SOURCE' => [
				'field' => 'source',
				'set' => 'setSource',
				'get' => 'getSource',
			],
			'CONFIG_ID' => [
				'field' => 'configId',
				'set' => 'setConfigId',
				'get' => 'getConfigId',
			],
			'OPERATOR_ID' => [
				'field' => 'operatorId',
				'set' => 'setOperatorId',
				'get' => 'getOperatorId',
			],
			'OPERATOR_FROM_CRM' => [
				'field' => 'operatorFromCrm',
				'set' => 'setOperatorFromCrm',
				'get' => 'isOperatorFromCrm',
			],
			'USER_CODE' => [
				'field' => 'userCode',
				'set' => 'setUserCode',
				'get' => 'getUserCode',
			],
			'PARENT_ID' => [
				'field' => 'parentId',
				'set' => 'setParentId',
				'get' => 'getParentId',
			],
			'USER_ID' => [
				'field' => 'userId',
				'set' => 'setUserId',
				'get' => 'getUserId',
			],
			'CHAT_ID' => [
				'field' => 'chatId',
				'set' => 'setChatId',
				'get' => 'getChatId',
			],
			'START_ID' => [
				'field' => 'startId',
				'set' => 'setStartId',
				'get' => 'getStartId',
			],
			'END_ID' => [
				'field' => 'endId',
				'set' => 'setEndId',
				'get' => 'getEndId',
			],
			'CRM' => [
				'field' => 'crm',
				'set' => 'setCrm',
				'get' => 'isCrm',
			],
			'CRM_CREATE' => [
				'field' => 'crmCreate',
				'set' => 'setCrmCreate',
				'get' => 'isCrmCreate',
			],
			'CRM_CREATE_LEAD' => [
				'field' => 'crmCreateLead',
				'set' => 'setCrmCreateLead',
				'get' => 'isCrmCreateLead',
			],
			'CRM_CREATE_COMPANY' => [
				'field' => 'crmCreateCompany',
				'set' => 'setCrmCreateCompany',
				'get' => 'isCrmCreateCompany',
			],
			'CRM_CREATE_CONTACT' => [
				'field' => 'crmCreateContact',
				'set' => 'setCrmCreateContact',
				'get' => 'isCrmCrateContact',
			],
			'CRM_CREATE_DEAL' => [
				'field' => 'crmCreateDeal',
				'set' => 'setCrmCreateDeal',
				'get' => 'isCrmCreateDeal',
			],
			'CRM_ACTIVITY_ID' => [
				'field' => 'crmActivityId',
				'set' => 'setCrmActivityId',
				'get' => 'getCrmActivityId',
			],
			'DATE_CREATE' => [
				'field' => 'dateCreate',
				'set' => 'setDateCreate',
				'get' => 'getDateCreate',
			],
			'DATE_OPERATOR' => [
				'field' => 'dateOperator',
				'set' => 'setDateOperator',
				'get' => 'getDateOperator',
			],
			'DATE_MODIFY' => [
				'field' => 'dateModify',
				'set' => 'setDateModify',
				'get' => 'getDateModify',
			],
			'DATE_OPERATOR_ANSWER' => [
				'field' => 'dateOperatorAnswer',
				'set' => 'setDateOperatorAnswer',
				'get' => 'getDateOperatorAnswer',
			],
			'DATE_OPERATOR_CLOSE' => [
				'field' => 'dateOperatorClose',
				'set' => 'setDateOperatorClose',
				'get' => 'getDateOperatorClose',
			],
			'DATE_FIRST_ANSWER' => [
				'field' => 'dateFirstAnswer',
				'set' => 'setDateFirstAnswer',
				'get' => 'getDateFirstAnswer',
			],
			'DATE_LAST_MESSAGE' => [
				'field' => 'dateLastMessage',
				'set' => 'set',
				'get' => 'get',
			],
			'DATE_FIRST_LAST_USER_ACTION' => [
				'field' => 'dateFirstLastUserAction',
				'set' => 'setDateFirstLastUserAction',
				'get' => 'getDateFirstLastUserAction',
			],
			'DATE_CLOSE' => [
				'field' => 'dateClose',
				'set' => 'setDateClose',
				'get' => 'getDateClose',
			],
			'DATE_CLOSE_VOTE' => [
				'field' => 'dateCloseVote',
				'set' => 'setDateCloseVote',
				'get' => 'getDateCloseVote',
			],
			'TIME_FIRST_ANSWER' => [
				'field' => 'timeFirstAnswer',
				'set' => 'setTimeFirstAnswer',
				'get' => 'getTimeFirstAnswer',
			],
			'TIME_ANSWER' => [
				'field' => 'timeAnswer',
				'set' => 'setTimeAnswer',
				'get' => 'getTimeAnswer',
			],
			'TIME_CLOSE' => [
				'field' => 'timeClose',
				'set' => 'setTimeClose',
				'get' => 'getTimeClose',
			],
			'TIME_BOT' => [
				'field' => 'timeBot',
				'set' => 'setTimeBot',
				'get' => 'getTimeBot',
			],
			'TIME_DIALOG' => [
				'field' => 'timeDialog',
				'set' => 'setTimeDialog',
				'get' => 'getTimeDialog',
			],
			'CATEGORY_ID' => [
				'field' => 'categoryId',
				'set' => 'setCategoryId',
				'get' => 'getCategoryId',
			],
			'WAIT_ACTION' => [
				'field' => 'waitAction',
				'set' => 'setWaitAction',
				'get' => 'isWaitAction',
			],
			'WAIT_ANSWER' => [
				'field' => 'waitAnswer',
				'set' => 'setWaitAnswer',
				'get' => 'isWaitAnswer',
			],
			'WAIT_VOTE' => [
				'field' => 'waitVote',
				'set' => 'setWaitVote',
				'get' => 'isWaitVote',
			],
			'SEND_FORM' => [
				'field' => 'sendForm',
				'set' => 'setSendForm',
				'get' => 'getSendForm',
			],
			'SEND_HISTORY' => [
				'field' => 'sendHistory',
				'set' => 'setSendHistory',
				'get' => 'isSendHistory',
			],
			'CLOSED' => [
				'field' => 'closed',
				'set' => 'setIsClosed',
				'get' => 'isClosed',
			],
			'PAUSE' => [
				'field' => 'pause',
				'set' => 'setPinMode',
				'get' => 'getPinMode',
			],
			'SPAM' => [
				'field' => 'spam',
				'set' => 'setSpam',
				'get' => 'isSpam',
			],
			'WORKTIME' => [
				'field' => 'worktime',
				'set' => 'setWorktime',
				'get' => 'isWorktime',
			],
		];
	}

	public function getPopupData(array $excludedList = []): PopupData
	{
		$data = new PopupData([
			new UserPopupItem([$this->getOperatorId()]),
		]);

		return $data;
	}

	public static function getRestEntityName(): string
	{
		return 'session';
	}

	public function toRestFormat(array $option = []): ?array
	{
		if (isset($option['OVERWRITE_STATUS']))
		{
			$this->setStatus($option['OVERWRITE_STATUS']);
		}

		$rest = [
			'id' => $this->getId(),
			'operatorId' => $this->getOperatorId(),
			'chatId' => $this->getChatId(),
			'status' => $this->getStatusGroup(),
			'queueId' => $this->getConfigId(),
			'pinned' => $this->getPinMode(),
			'isClosed' => $this->isClosed(),
		];

		return $rest;
	}

	public static function updateStateAfterOrmEvent(int $id, array $fields): void
	{
		$session = self::$sessionStaticCache[$id];
		$session?->onAfterOrmUpdate($fields);
	}

	public function merge(PopupDataItem $item): PopupDataItem
	{
		return $this;
	}
}