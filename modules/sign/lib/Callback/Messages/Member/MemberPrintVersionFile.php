<?php

namespace Bitrix\Sign\Callback\Messages\Member;

use Bitrix\Sign\Callback;

class MemberPrintVersionFile extends Callback\Message
{
	public const Type = 'memberPrintVersionFile';

	/**
	 * @var array{
	 *     documentUid: string,
	 *     memberUid: string,
	 *     filePath: string,
	 *     file: array{content: string, name: ?string, contentType: string}
	 * }
	 */
	protected array $data = [
		'documentUid' => '',
		'memberUid' => '',
		'filePath' => '',
		'file' => [
			'content' => '',
			'name' => '',
			'contentType' => '',
		],
	];

	public function setFilePath(string $filePath): self
	{
		$this->data['filePath'] = $filePath;

		return $this;
	}

	public function getFilePath(): string
	{
		return $this->data['filePath'];
	}

	public function setFileName(string $fileName): static
	{
		$this->data['file']['name'] = $fileName;
		return $this;
	}

	public function getDocumentUid(): string
	{
		return $this->data['documentUid'];
	}

	public function setDocumentUid(string $documentUid): self
	{
		$this->data['documentUid'] = $documentUid;
		return $this;
	}

	public function getMemberUid(): string
	{
		return $this->data['memberUid'];
	}

	public function setMemberUid(string $memberUid): self
	{
		$this->data['memberUid'] = $memberUid;
		return $this;
	}
}

