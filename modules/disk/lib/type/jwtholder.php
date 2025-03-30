<?php

namespace Bitrix\Disk\Type;

interface JwtHolder
{

	public function setJwtData(?object $data);
	public function getJwtData(): ?object;

}