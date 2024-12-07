<?php
namespace Bitrix\Sign\Callback\Messages;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Callback;
use DateTimeInterface;

class DocumentStatus extends Callback\Message
{
	public const Type = 'DocumentStatus';
	private const SIGN_DATE_FORMAT = DateTimeInterface::ATOM;

	protected array $data = [
		'status' => '',
		'code' => '',
		'signDate' => '',
		'initiatorUid' => '',
	];

	public function setStatus(string $status): self
	{
		$this->data['status'] = $status;
		return $this;
	}

	public function getStatus(): string
	{
		return $this->data['status'] ?? '';
	}

	public function setCode(string $code): self
	{
		$this->data['code'] = $code;
		return $this;
	}

	public function getCode(): string
	{
		return $this->data['code'];
	}

	public function getSignDate(): ?DateTime
	{
		if (!$this->data['signDate'])
		{
			return null;
		}

		return (new DateTime($this->data['signDate'], self::SIGN_DATE_FORMAT));
	}

	public function getInitiatorUid(): string
	{
		return $this->data['initiatorUid'] ?? '';
	}
}
