<?php

namespace Bitrix\Sign\Callback\Messages;

use Bitrix\Sign\Callback;

class TimelineEvent extends Callback\Message
{
	public const Type = 'TimelineEvent';

	protected array $data = [
		'event' => [
			'type' => '',
			'data' => [],
		],
		'documentCode' => '',
		'memberCode' => '',
		'securityCode' => '',
		'version' => 1,
	];

	public function getEventType(): string
	{
		return $this->data['event']['type'];
	}

	public function setEventType(string $eventType): TimelineEvent
	{
		$this->data['event']['type'] = $eventType;
		return $this;
	}


	public function getEventData(): array
	{
		return isset($this->data['event']['data']) && is_array($this->data['event']['data'])
			? $this->data['event']['data']
			: []
		;
	}

	public function setEventData(array $data): TimelineEvent
	{
		$this->data['event']['data'] = $data;
		return $this;
	}

	public function getDocumentCode(): string
	{
		return $this->data['documentCode'];
	}

	public function setDocumentCode(string $documentCode): self
	{
		$this->data['documentCode'] = $documentCode;
		return $this;
	}

	public function getMemberCode(): string
	{
		return $this->data['memberCode'];
	}

	public function setMemberCode(string $memberCode): self
	{
		$this->data['memberCode'] = $memberCode;
		return $this;
	}

	public function getSecurityCode(): string
	{
		return $this->data['securityCode'];
	}

	public function toArray(): array
	{
		return array_merge([
			'documentHash' => $this->getDocumentCode(),
			'memberHash' => $this->getMemberCode(),
			'eventType' => $this->getEventType(),
			'version' => $this->getVersion(),
		], $this->getEventData());
	}

	public function getVersion(): int
	{
		return $this->data['version'];
	}

	public function setVersion(int $version): self
	{
		$this->data['version'] = $version;
		return $this;
	}
}