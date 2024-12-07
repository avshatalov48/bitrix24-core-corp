<?php

namespace Bitrix\Crm\Timeline\SignDocument;

use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Type\Contract\Arrayable;

final class MessageData implements \JsonSerializable, Arrayable
{
	public const STATUS_SENT = 'sent';
	public const STATUS_ERROR = 'error';
	public const STATUS_DELIVERED = 'delivered';
	public const INTEGRITY_STATE_SUCCESS = 'success';
	public const INTEGRITY_STATE_FAIL = 'fail';

	protected Signer $recipient;
	protected string $status = self::STATUS_SENT;
	protected Channel $channel;
	protected ?string $description = null;
	protected ?string $subject = null;
	protected ?\Bitrix\Main\Error $error = null;
	protected ?string $author = null;
	protected ?string $integrityState = null;
	protected ?string $goskeyOrderId = null;
	protected ?string $provider = null;
	protected ?string $sesUsername = null;
	protected ?string $sesSign = null;
	protected ?string $providerName = null;

	protected function __construct(Signer $recipient, Channel $channel)
	{
		$this->recipient = $recipient;
		$this->channel = $channel;
	}

	public static function createFromArray(array $data): self
	{
		$recipient = $data['recipient'] ?? null;
		if (is_array($recipient))
		{
			$recipient = Signer::createFromArray($recipient);
		}

		$channel = $data['channel'] ?? null;
		if (is_array($channel))
		{
			$channel = Channel::createFromArray($channel);
		}

		$messageData = new self($recipient, $channel);

		if ($data['subject'])
		{
			$messageData->setSubject($data['subject']);
		}

		if ($data['author'])
		{
			$messageData->setAuthor($data['author']);
		}
		if (
			($data['integrityState'] ?? null)
			&& in_array($data['integrityState'], [self::INTEGRITY_STATE_SUCCESS, self::INTEGRITY_STATE_FAIL])
		)
		{
			$messageData->setIntegrityState($data['integrityState']);
		}

		if ($data['provider']['goskey']['orderId'] ?? null)
		{
			$messageData->setGoskeyOrderId($data['provider']['goskey']['orderId']);
		}
		if (!empty($data['provider']['ses']['username']))
		{
			$messageData->setSesUsername($data['provider']['ses']['username']);
		}
		if (!empty($data['provider']['ses']['sign']))
		{
			$messageData->setSesSign($data['provider']['ses']['sign']);
		}
		if (!empty($data['provider']['name']))
		{
			$messageData->setProviderName($data['provider']['name']);
		}
		if (isset($data['error']) && $data['error'])
		{
			$messageData->setError(
				$data['error'] instanceof \Bitrix\Main\Error
					? $data['error']
					: new \Bitrix\Main\Error(
					$data['error']['message'] ?? '',
					$data['error']['code'] ?? 0,
					$data['error']['customData'] ?? [],
					)
			);
		}

		return $messageData->setStatus($data['status'] ?? self::STATUS_SENT);
	}

	/**
	 * @return Signer
	 */
	public function getRecipient(): Signer
	{
		return $this->recipient;
	}

	public function getStatus(): string
	{
		return $this->status;
	}

	public function setStatus(string $status): self
	{
		$this->validateStatus($status);
		$this->status = $status;

		return $this;
	}

	public function getGoskeyOrderId(): ?string
	{
		return $this->goskeyOrderId;
	}

	public function setGoskeyOrderId(?string $id): static
	{
		$this->goskeyOrderId = $id;

		return $this;
	}

	private function validateStatus(string $status): void
	{
		if (
			$status !== static::STATUS_SENT
			&& $status !== static::STATUS_ERROR
			&& $status !== static::STATUS_DELIVERED
		)
		{
			throw new ArgumentOutOfRangeException('status');
		}
	}

	public function getChannel(): Channel
	{
		return $this->channel;
	}

	public function isStatusSent(): bool
	{
		return $this->status === static::STATUS_SENT;
	}

	public function isStatusError(): bool
	{
		return $this->status === static::STATUS_ERROR;
	}

	public function isStatusDelivered(): bool
	{
		return $this->status === static::STATUS_DELIVERED;
	}

	/**
	 * Error message / delivery time / etc.
	 *
	 * @return string|null
	 */
	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function setDescription(?string $description): self
	{
		$this->description = $description;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'recipient' => $this->recipient->toArray(),
			'status' => $this->status,
			'channel' => $this->channel->toArray(),
			'description' => $this->description,
			'subject' => $this->subject,
			'author' => $this->author,
			'error' => [
				'code' => $this->error?->getCode(),
				'message' => $this->error?->getMessage(),
				'customData' => $this->error?->getCustomData() ?? [],
			],
			'provider' => [
				'name' => $this->getProviderName(),
				'goskey' => [
					'orderId' => $this->getGoskeyOrderId(),
				],
				'ses' => [
					'username' => $this->getSesUsername(),
					'sign' => $this->getSesSign(),
				],
			],
		];
	}

	/**
	 * @return string|null
	 */
	public function getSubject(): ?string
	{
		return $this->subject;
	}

	/**
	 * @param string|null $subject
	 * @return MessageData
	 */
	public function setSubject(?string $subject): MessageData
	{
		$this->subject = $subject;
		return $this;
	}

	public function setError(?\Bitrix\Main\Error $error): MessageData
	{
		$this->error = $error;
		return $this;
	}

	public function getError(): ?\Bitrix\Main\Error
	{
		return $this->error;
	}

	/**
	 * @return string|null
	 */
	public function getAuthor(): ?string
	{
		return $this->author;
	}

	/**
	 * @param string|null $author
	 * @return MessageData
	 */
	public function setAuthor(?string $author): MessageData
	{
		$this->author = $author;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getIntegrityState(): ?string
	{
		return $this->integrityState;
	}

	/**
	 * @param string|null $integrityState
	 * @return MessageData
	 */
	public function setIntegrityState(?string $integrityState): MessageData
	{
		$this->integrityState = $integrityState;
		return $this;
	}

	public function setSesUsername(?string $sesUsername): MessageData
	{
		$this->sesUsername = $sesUsername;

		return $this;
	}

	public function getSesUsername(): ?string
	{
		return $this->sesUsername;
	}

	public function setSesSign(?string $sesSign): MessageData
	{
		$this->sesSign = $sesSign;

		return $this;
	}

	public function getSesSign(): ?string
	{
		return $this->sesSign;
	}

	public function setProviderName(?string $providerName): MessageData
	{
		$this->providerName = $providerName;

		return $this;
	}

	public function getProviderName(): ?string
	{
		return $this->providerName;
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
