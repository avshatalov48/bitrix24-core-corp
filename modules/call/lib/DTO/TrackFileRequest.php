<?php

namespace Bitrix\Call\DTO;

class TrackFileRequest extends Hydrator
{
	public string $callUuid = '';
	public int $trackId = 0;
	public string $type = '';
	public string $name = '';
	public string $url = '';
	public string $mime = '';
	public int $size = 0;
	public int $duration = 0;
}
