<?php
use Bitrix\Main\Web\HttpClient;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CWebDavEditSkyDrive extends CWebDavEditDocBase
{
	public static $SCOPE = array(
		'wl.contacts_skydrive',
		'wl.skydrive_update',
		'wl.skydrive',
	);

	public function insertPermission(array $fileData)
	{
		return;
	}

	public function listPermission(array $fileData)
	{
		return;
	}

	public function publicFile(array $fileData)
	{
		$accessToken = $this->getAccessToken();
		$newFile = $this->createFile($fileData);
		if(!$newFile)
		{
			return array();
		}
//		$newFile['link'] = $this->getSharedEmbedLink($newFile['id'], $accessToken);
		$shared = $this->getSharedEditLink($newFile);
		$newFile['link'] = $shared['link'];

		return $newFile;
	}

	public function downloadFile(array $fileData)
	{
		$accessToken = $this->getAccessToken();
		$fileId = $fileData['id'];
		$mimeType = $fileData['mimeType'];

		@set_time_limit(0);

		$file = $this->getFile($fileData);
		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'version' => HttpClient::HTTP_1_1,
		));

		if(!($file['content'] = $http->get($file['source'])))
		{
			return false;
		}

		// error checking
		if ($http->getStatus() != "200")
		{
			return false;
		}
		CWebDavTools::convertFromUtf8($file['name']);

		$this->recoverExtensionInName($file, $mimeType);

		return $file;
	}

	public function removeFile(array $fileData)
	{
		$accessToken = $this->getAccessToken();
		$fileId = $fileData['id'];

		$http = new CHTTP();
		$http->http_timeout = 10;
		if(!$http->HTTPQuery('DELETE', "https://apis.live.net/v5.0/{$fileId}?access_token=" . urlencode($accessToken)))
		{
			return false;
		}

		// error checking
		if ($http->status != "200")
		{
			return false;
		}

		return true;
	}

	public function createFile(array $fileData)
	{
		$accessToken = $this->getAccessToken();
		$mimeType = $fileData['mimeType'];
		$fileSrc = $fileData['src'];
		$fileName = $fileData['name'];
		CWebDavTools::convertToUtf8($fileName);

		$fileSize = $fileData['size']? $fileData['size']: filesize($fileSrc);
		$content = file_get_contents($fileSrc);

		$http = new CHTTP();
		$http->http_timeout = 10;
		$fileName = urlencode($fileName);
		$arUrl = $http->ParseURL("https://apis.live.net/v5.0/me/skydrive/files/{$fileName}?access_token=" . urlencode($accessToken));
		if(!$http->Query('PUT', $arUrl['host'], $arUrl['port'], $arUrl['path_query'], $content, $arUrl['proto'], ''))
		{
			return false;
		}

		$this->checkHttpResponse($http);

		// error checking
		if ($http->status != '200' && $http->status != '201')
		{
			return false;
		}

		return json_decode($http->result, true);
	}

	public function createBlankFile(array $fileData)
	{
		return $this->publicFile($fileData);
	}

	public function getFile(array $fileData)
	{
		$accessToken = $this->getAccessToken();
		$fileId = $fileData['id'];

		$http = new CHTTP();
		$http->http_timeout = 10;
		if(!$http->GET("https://apis.live.net/v5.0/{$fileId}?access_token=".urlencode($accessToken)))
		{
			return false;
		}

		// error checking
		if ($http->status != "200")
		{
			return false;
		}

		return json_decode($http->result, true);
	}

	public function getSharedEditLink(array $fileData)
	{
		$accessToken = $this->getAccessToken();
		$fileId = $fileData['id'];

		$http = new CHTTP();
		$http->http_timeout = 10;
		if(!$http->GET("https://apis.live.net/v5.0/{$fileId}/shared_edit_link?access_token=".urlencode($accessToken)))
		{
			return false;
		}

		// error checking
		if ($http->status != "200")
		{
			return false;
		}
		return json_decode($http->result, true);
	}

	public function getSharedReadLink(array $fileData)
	{
		$accessToken = $this->getAccessToken();
		$fileId = $fileData['id'];

		$http = new CHTTP();
		$http->http_timeout = 10;
		if(!$http->GET("https://apis.live.net/v5.0/{$fileId}/shared_read_link?access_token=".urlencode($accessToken)))
		{
			return false;
		}

		// error checking
		if ($http->status != "200")
		{
			return false;
		}

		return json_decode($http->result, true);
	}

	public function getSharedEmbedLink(array $fileData)
	{
		$accessToken = $this->getAccessToken();
		$fileId = $fileData['id'];

		$http = new CHTTP();
		$http->http_timeout = 10;
		if(!$http->GET("https://apis.live.net/v5.0/{$fileId}/embed?access_token=".urlencode($accessToken)))
		{
			return false;
		}
		// error checking
		if ($http->status != "200")
		{
			return false;
		}

		$response = json_decode($http->result, true);
		if(preg_match('%src="(.*)"%iuU', $response['embed_html'], $m))
		{
			return $m[1];
		}

		return false;
	}
}