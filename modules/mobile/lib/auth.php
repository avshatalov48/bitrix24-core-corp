<?php

namespace Bitrix\Mobile;

class Auth
{
	public static function setNotAuthorizedHeaders()
	{
		header("HTTP/1.0 401 Not Authorized");
		header('WWW-Authenticate: Basic realm="Bitrix24"');
		header("Content-Type: application/x-javascript");
		header("BX-Authorize: ".bitrix_sessid());
	}

	public static function getNotAuthorizedResponse()
	{
		return [
			"status" => "failed",
			"bitrix_sessid"=>bitrix_sessid()
		];
	}


}