<?php
namespace Bitrix\DocumentGenerator\Integration\Numerator;

interface DocumentNumerable
{
	public function getClientId();
	public function getSelfCompanyId();
	public function getSelfId();
}