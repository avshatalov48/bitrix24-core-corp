<?php
namespace Bitrix\Sign\Item\Fs;

class FileContent
{
	public ?string $data = null;

	public function __construct(
		?string $data = null
	) {
		$this->data = $data;
	}
}