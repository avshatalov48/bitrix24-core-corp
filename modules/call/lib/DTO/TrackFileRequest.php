<?php

namespace Bitrix\Call\DTO;

class TrackFileRequest
{
	public string $callUuid = '';
	public int $trackId = 0;
	public string $type = '';
	public string $name = '';
	public string $url = '';
	public string $mime = '';
	public int $size = 0;
	public int $duration = 0;

	public function __construct(?array $fields = null)
	{
		if ($fields !== null)
		{
			$this->hydrate($fields);
		}
	}

	public function hydrate(array $fields): self
	{
		if (isset($fields['callUuid']))
		{
			$this->callUuid = $fields['callUuid'];
		}
		if (isset($fields['trackId']))
		{
			$this->trackId = (int)$fields['trackId'];
		}
		if (
			isset($fields['type'])
			&& in_array($fields['type'], [\Bitrix\Call\Track::TYPE_TRACK_PACK, \Bitrix\Call\Track::TYPE_RECORD], true)
		)
		{
			$this->type = $fields['type'];
		}
		if (isset($fields['name']))
		{
			$this->name = $fields['name'];
		}
		if (isset($fields['url']))
		{
			$this->url = $fields['url'];
		}
		if (isset($fields['mime']))
		{
			$this->mime = $fields['mime'];
		}
		if (isset($fields['size']))
		{
			$this->size = (int)$fields['size'];
		}
		if (isset($fields['duration']))
		{
			$this->duration = (int)$fields['duration'];
		}
		return $this;
	}
}