<?php
namespace Bitrix\Sign\Integration\CRM\Model;

use Bitrix\Sign\Document;
use Bitrix\Sign\Item\Member;

class EventData
{
	public const NOTIFICATION_DELIVERED = 'ON_NOTIFICATION_DELIVERED';
	public const NOTIFICATION_READ = 'ON_NOTIFICATION_READ';
	public const NOTIFICATION_ERROR = 'ON_NOTIFICATION_ERROR';
	private string $eventType;
	private ?Document $document = null;
	private ?Document\Member $member = null;
	private ?\Bitrix\Sign\Item\Document $documentItem = null;
	private ?Member $memberItem = null;
	private ?\Bitrix\Main\Error $error = null;
	private array $data = [];
	public const TYPE_ON_CREATE = 'ON_CREATE';
	public const TYPE_ON_SEND = 'ON_SEND';
	public const TYPE_ON_VIEW = 'ON_VIEW';
	public const TYPE_ON_REQUEST_RESULT = 'ON_REQUEST_RESULT';
	public const TYPE_ON_SIGN = 'ON_SIGN';
	public const TYPE_ON_SIGN_COMPLETED = 'ON_SIGN_COMPLETED';
	public const TYPE_ON_SEND_FINAL = 'ON_SEND_FINAL';
	public const TYPE_ON_FILL = 'ON_FILL';
	public const TYPE_ON_PREPARE_TO_FILL = 'ON_PREPARE_TO_FILL';
	public const TYPE_ON_COMPLETE = 'ON_COMPLETE';
	public const TYPE_ON_SEND_INTEGRITY_FAILURE_NOTICE = 'ON_SEND_INTEGRITY_FAILURE_NOTICE';
	public const TYPE_ON_SEND_REPEATEDLY = 'ON_SEND_REPEATEDLY';
	public const TYPE_ON_INTEGRITY_SUCCESS = 'ON_INTEGRITY_SUCCESS';
	public const TYPE_ON_REGISTER = 'ON_REGISTER';
	public const TYPE_ON_PIN_SEND_LIMIT_REACHED = 'ON_PIN_SEND_LIMIT_REACHED';

	public const TYPE_ON_STOPPED = 'TYPE_ON_STOPPED';
	public const TYPE_ON_DONE = 'TYPE_ON_DONE';
	public const TYPE_ON_STARTED = 'TYPE_ON_STARTED';

	public const TYPE_ON_READY_BY_REVIEWER_OR_EDITOR = 'TYPE_ON_READY_BY_REVIEWER_OR_EDITOR';
	public const TYPE_ON_READY_BY_REVIEWER = 'TYPE_ON_READY_BY_REVIEWER';
	public const TYPE_ON_READY_BY_EDITOR = 'TYPE_ON_READY_BY_EDITOR';
	public const TYPE_ON_SIGNED_BY_REVIEWER = 'TYPE_ON_SIGNED_BY_REVIEWER'; // log
	public const TYPE_ON_SIGNED_BY_EDITOR = 'TYPE_ON_SIGNED_BY_EDITOR'; // log
	public const TYPE_ON_SIGNED_BY_RESPONSIBILITY_PERSON = 'TYPE_ON_SIGNED_BY_RESPONSIBILITY_PERSON'; // log
	public const TYPE_ON_SIGNED_BY_EMPLOYEE = 'TYPE_ON_SIGNED_BY_EMPLOYEE'; // log
	public const TYPE_ON_CANCELED_BY_RESPONSIBILITY_PERSON = 'TYPE_ON_CANCELED_BY_RESPONSIBILITY_PERSON'; // log
	public const TYPE_ON_CANCELED_BY_EMPLOYEE = 'TYPE_ON_CANCELED_BY_EMPLOYEE'; // log
	public const TYPE_ON_CANCELED_BY_REVIEWER = 'TYPE_ON_CANCELED_BY_REVIEWER'; // log
	public const TYPE_ON_CANCELED_BY_EDITOR = 'TYPE_ON_CANCELED_BY_EDITOR'; // log

	public const TYPE_ON_CONFIGURATION_ERROR = 'TYPE_ON_CONFIGURATION_ERROR';

	public const TYPE_ON_DELIVERED = 'ON_DELIVERED';
	public const TYPE_ON_DELIVERY_ERROR = 'ON_DELIVERY_ERROR';

	public const DATA_KEY_GOSKEY_ORDER_ID = 'provider.goskey.orderId';
	public const DATA_KEY_SES_USERNAME = 'provider.ses.username';
	public const DATA_KEY_SES_SIGN = 'provider.ses.sign';
	public const DATA_KEY_PROVIDER_NAME = 'provider.name';
	public const DATA_KEY_INITIATOR = 'initiator';

	public const TYPE_ON_ERROR_SIGNING_EXPIRED = 'ON_ERROR_SIGNING_EXPIRED';
	public const TYPE_ON_ERROR_REQUEST_ERROR = 'ON_ERROR_REQUEST_ERROR';
	public const TYPE_ON_ERROR_SNILS_NOT_FOUND = 'ON_ERROR_SNILS_NOT_FOUND';
	public const TYPE_ON_MEMBER_STOPPED_BY_ASSIGNEE = 'TYPE_ON_STOPPED_BY_ASSIGNEE';
	public const TYPE_ON_MEMBER_STOPPED_BY_REVIEWER = 'TYPE_ON_STOPPED_BY_REVIEWER';
	public const TYPE_ON_MEMBER_STOPPED_BY_EDITOR = 'TYPE_ON_STOPPED_BY_EDITOR';
	public const TYPE_ON_MEMBER_SIGNED_DELIVERED = 'TYPE_ON_MEMBER_SIGNED_DELIVERED';
	public const TYPE_ON_SENDING = 'TYPE_ON_SENDING';
	public const TYPE_ON_ASSIGNEE_DONE = 'TYPE_ON_ASSIGNEE_DONE';

	/**
	 * @return string
	 */
	public function getEventType(): string
	{
		return $this->eventType;
	}

	/**
	 * @param string $eventType
	 * @return EventData
	 */
	public function setEventType(string $eventType): EventData
	{
		$this->eventType = $eventType;
		return $this;
	}

	/**
	 * @return Document|null
	 * @deprecated use getDocumentItem() instead
	 */
	public function getDocument(): ?Document
	{
		return $this->document;
	}

	/**
	 *
	 * @param Document|null $document
	 * @return EventData
	 * @deprecated use setDocumentItem(Document $documentItem) instead
	 */
	public function setDocument(?Document $document): EventData
	{
		$this->document = $document;
		return $this;
	}

	/**
	 * @deprecated use getMemberItem() instead
	 * @return Document\Member|null
	 */
	public function getMember(): ?Document\Member
	{
		return $this->member;
	}

	/**
	 * @param Document\Member|null $member
	 *
	 * @return EventData
	 * @deprecated use setMemberItem(Member $memberItem) instead
	 */
	public function setMember(?Document\Member $member): EventData
	{
		$this->member = $member;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}

	public function addDataValue(string $key, $value): EventData
	{
		$this->data[$key] = $value;
		return $this;
	}

	public function getDocumentItem(): \Bitrix\Sign\Item\Document
	{
		return $this->documentItem;
	}

	public function setDocumentItem(\Bitrix\Sign\Item\Document $documentItem): EventData
	{
		$this->documentItem = $documentItem;

		return $this;
	}

	public function getError(): ?\Bitrix\Main\Error
	{
		return $this->error;
	}

	public function setError(\Bitrix\Main\Error $error): EventData
	{
		$this->error = $error;

		return $this;
	}

	public function getMemberItem(): ?Member
	{
		return $this->memberItem;
	}

	/**
	 * @param \Bitrix\Sign\Item\Member|null $memberItem
	 *
	 * @return $this
	 */
	public function setMemberItem(?Member $memberItem): EventData
	{
		$this->memberItem = $memberItem;

		return $this;
	}
}
