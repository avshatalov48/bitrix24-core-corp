<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CWebDavEditDocGoogle extends CWebDavEditDocBase
{
	public static $SCOPE = 'https://www.googleapis.com/auth/drive';

	public function publicFile(array $fileData)
	{
		$newFile = $this->publicByResumableUpload($fileData, $lastStatus);
		if(!$newFile)
		{
			//retry upload, but not convert content
			if($lastStatus == '500')
			{
				$fileData['convert'] = false;
				$newFile = self::publicByResumableUpload($fileData, $lastStatus);
				return !$newFile? array() : $newFile;
			}
			return array();
		}
		//last signed user must delete file from google drive
		$this->insertPermission($newFile);

		return $newFile;
	}

	public function insertPermission(array $fileData)
	{
		$accessToken = $this->getAccessToken();
		$fileId = $fileData['id'];
		$http = new CHTTP();
		$http->http_timeout = 10;
		$arUrl = $http->ParseURL("https://www.googleapis.com/drive/v2/files/{$fileId}/permissions");
		$http->SetAdditionalHeaders(array(
			"Authorization" => "Bearer {$accessToken}",
		));
		$postFields = "{\"role\":\"writer\", \"type\":\"anyone\", \"withLink\":true, \"value\": null}";
		$postContentType = 'application/json; charset=UTF-8';
		if(!$http->Query('POST', $arUrl['host'], $arUrl['port'], $arUrl['path_query'], $postFields, $arUrl['proto'], $postContentType))
		{
			return false;
		}

		// access token expired, let's get a new one and try again
		if ($http->status == "401")
		{
			//todo: invalid credential response
			return false;
		}

		// error checking
		if ($http->status != "200")
		{
			return false;
		}

		return true;
	}

	public function listPermission(array $fileData)
	{
		$accessToken = $this->getAccessToken();
		$fileId = $fileData['id'];
		$http = new CHTTP();
		$http->http_timeout = 10;
		$http->SetAdditionalHeaders(array(
			"Authorization" => "Bearer {$accessToken}",
		));
		if(!$http->GET("https://www.googleapis.com/drive/v2/files/{$fileId}/permissions"))
		{
			return false;
		}

		// access token expired, let's get a new one and try again
		if ($http->status == "401")
		{
			//todo: invalid credential response
			return false;
		}

		// error checking
		if ($http->status != "200")
		{
			return false;
		}

		return $http->result;
	}

	public function publicByResumableUpload($fileData, &$lastStatus)
	{
		$accessToken = $this->getAccessToken();
		$convert = is_null($fileData['convert'])? true : (bool)$fileData['convert'];
		$mimeType = $fileData['mimeType'];
		$fileSrc = $fileData['src'];
		$fileSize = $fileData['size'] = $fileData['size']? $fileData['size']: filesize($fileSrc);
		$chunkSize = 40 * 256 * 1024; // Chunk size restriction: All chunks must be a multiple of 256 KB (256 x 1024 bytes) in size except for the final chunk that completes the upload
		$location = $this->createFile($fileData);
		if(!$location)
		{
			return false;
		}

		$lastResponseCode = false;
		$finalOutput = null;
		$lastRange = false;
		$transactionCounter = 0;
		$doExponentialBackoff = false;
		$exponentialBackoffCounter = 0;
		$response = array();
		while ($lastResponseCode === false || $lastResponseCode == '308')
		{
			$transactionCounter++;

			if ($doExponentialBackoff)
			{
				$sleepFor = pow(2, $exponentialBackoffCounter);
				sleep($sleepFor);
				usleep(rand(0, 1000));
				$exponentialBackoffCounter++;
				if ($exponentialBackoffCounter > 5)
				{
					$lastStatus = $response['code'];
					return false;
				}
			}

			// determining what range is next
			$rangeStart = 0;
			$rangeEnd   = min($chunkSize, $fileSize - 1);
			if ($lastRange !== false)
			{
				$lastRange  = explode('-', $lastRange);
				$rangeStart = (int)$lastRange[1] + 1;
				$rangeEnd   = min($rangeStart + $chunkSize, $fileSize - 1);
			}

			$http = new CHTTP();
			$http->http_timeout = 10;
			$arUrl = $http->ParseURL($location);
			$http->SetAdditionalHeaders(array(
				"Authorization" => "Bearer {$accessToken}",
				"Content-Length" => (string)($rangeEnd - $rangeStart + 1),
				"Content-Type" => $mimeType,
				"Content-Range" => "bytes {$rangeStart}-{$rangeEnd}/{$fileSize}",
			));
			$postContentType = '';
			$toSendContent = file_get_contents($fileSrc, false, null, $rangeStart, ($rangeEnd - $rangeStart + 1));
			if($http->Query('PUT', $arUrl['host'], $arUrl['port'], $arUrl['path_query'], $toSendContent, $arUrl['proto'], $postContentType))
			{
				$response['code'] = $http->status;
				$response['headers']['range'] = $http->headers['Range'];
			}

			$doExponentialBackoff = false;
			if (isset($response['code']))
			{
				// checking for expired credentials
				if ($response['code'] == "401")
				{ // todo: make sure that we also got an invalid credential response
					//$access_token       = get_access_token(true);
					$lastResponseCode = false;
				}
				else if ($response['code'] == "308")
				{
					$lastResponseCode = $response['code'];
					$lastRange = $response['headers']['range'];
					// todo: verify x-range-md5 header to be sure
					$exponentialBackoffCounter = 0;
				}
				else if ($response['code'] == "503")
				{ // Google's letting us know we should retry
					$doExponentialBackoff = true;
					$lastResponseCode     = false;
				}
				else
				{
					if ($response['code'] == "200")
					{ // we are done!
						$lastResponseCode = $response['code'];
					}
					else
					{
						$lastStatus = $response['code'];
						return false;
					}
				}
			}
			else
			{
				$doExponentialBackoff = true;
				$lastResponseCode     = false;
			}
		}

		if ($lastResponseCode != "200")
		{
			$lastStatus = $response['code'];
			return false;
		}
		$finalOutput = json_decode($http->result);

		return array('link' => $finalOutput->alternateLink, 'id' => $finalOutput->id);
	}

	public function downloadFile(array $fileData)
	{
		$accessToken = $this->getAccessToken();
		$id = $fileData['id'];
		$mimeType = $fileData['mimeType'];
		@set_time_limit(0);
		$http = new CHTTP();
		$http->http_timeout = 10;
		$http->SetAdditionalHeaders(array(
			"Authorization" => "Bearer {$accessToken}",
		));
		if(!$http->GET('https://www.googleapis.com/drive/v2/files/' . $id))
		{
			return false;
		}
		// access token expired, let's get a new one and try again
		if ($http->status == "401")
		{
			//todo: invalid credential response
			return false;
		}

		// error checking
		if ($http->status != "200")
		{
			return false;
		}

		$file = json_decode($http->result, true);
		$links = $file['exportLinks'];
		$link = empty($links[$mimeType])? '' : $links[$mimeType];
		if(!$link)
		{
			$link = $file['downloadUrl'];
		}

		$http = new CHTTP();
		$http->http_timeout = 10;
		$http->SetAdditionalHeaders(array(
			"Authorization" => "Bearer {$accessToken}",
		));
		if(!$http->GET($link))
		{
			return false;
		}
		$file['content'] = $http->result? $http->result:'';
		CWebDavTools::convertFromUtf8($file['title']);
		$file['name'] = $file['title'];

		$this->recoverExtensionInName($file, $mimeType);

		return $file;
	}

	public function removeFile(array $fileData)
	{
		$accessToken = $this->getAccessToken();
		$id = $fileData['id'];
		$http = new CHTTP();
		$http->http_timeout = 10;
		$arUrl = $http->ParseURL('https://www.googleapis.com/drive/v2/files/' . $id);
		$http->SetAdditionalHeaders(array(
			"Authorization" => "Bearer {$accessToken}",
		));
		$postFields = '';
		$postContentType = '';
		if(!$http->Query('DELETE', $arUrl['host'], $arUrl['port'], $arUrl['path_query'], $postFields, $arUrl['proto'], $postContentType))
		{
			return false;
		}

		// access token expired, let's get a new one and try again
		if ($http->status == "401")
		{
			//todo: invalid credential response
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
		$convert = is_null($fileData['convert'])? true : (bool)$fileData['convert'];

		$mimeType = $fileData['mimeType'];
		$fileSrc = $fileData['src'];
		$fileName = $fileData['name'];
		CWebDavTools::convertToUtf8($fileName);
		$fileSize = $fileData['size'];

		$http = new CHTTP();
		$http->http_timeout = 10;
		$arUrl = $http->ParseURL('https://www.googleapis.com/upload/drive/v2/files?uploadType=resumable&convert=' . ($convert? 'true':'false'));
		$http->SetAdditionalHeaders(array(
			"Authorization" => "Bearer {$accessToken}",
			"X-Upload-Content-Type" => $mimeType,
			"X-Upload-Content-Length" => $fileSize,
		));
		$postFields = "{\"title\":\"{$fileName}\"}";
		$postContentType = 'application/json; charset=UTF-8';
		if(!$http->Query('POST', $arUrl['host'], $arUrl['port'], $arUrl['path_query'], $postFields, $arUrl['proto'], $postContentType))
		{
			return false;
		}
		$location = $http->headers['Location'];

		$this->checkHttpResponse($http);

		// access token expired, let's get a new one and try again
		if ($http->status == "401")
		{
			//todo: invalid credential response
			return false;
		}

		// error checking
		if ($http->status != "200")
		{
			return false;
		}

		return $location;
	}

	public function createBlankFile(array $fileData)
	{
		$accessToken = $this->getAccessToken();

		$googleMimeType = $this->getInternalMimeTypeListByExtension(getFileExtension($fileData['name']));
		$fileName = getFileNameWithoutExtension($fileData['name']);
		CWebDavTools::convertToUtf8($fileName);

		if(!$googleMimeType)
		{
			return false;
		}

		$http = new CHTTP();
		$http->http_timeout = 10;
		$arUrl = $http->ParseURL('https://www.googleapis.com/drive/v2/files');
		$http->SetAdditionalHeaders(array(
			"Authorization" => "Bearer {$accessToken}",
			//"X-Upload-Content-Type" => $mimeType,
		));
		$postFields = "{\"title\":\"{$fileName}\",\"mimeType\":\"{$googleMimeType}\"}";
		$postContentType = 'application/json; charset=UTF-8';
		if(!$http->Query('POST', $arUrl['host'], $arUrl['port'], $arUrl['path_query'], $postFields, $arUrl['proto'], $postContentType))
		{
			return false;
		}

		$this->checkHttpResponse($http);

		// access token expired, let's get a new one and try again
		if ($http->status == "401")
		{
			//todo: invalid credential response
			return false;
		}

		// error checking
		if ($http->status != "200")
		{
			return false;
		}

		$finalOutput = json_decode($http->result);
		//last signed user must delete file from google drive
		$this->insertPermission(array('link' => $finalOutput->alternateLink, 'id' => $finalOutput->id));

		return array('link' => $finalOutput->alternateLink, 'id' => $finalOutput->id);
	}

	private function getInternalMimeTypeListByExtension($ext)
	{
		$ext = trim($ext, '.');
		$googleMimeTypes = array(
			'docx' => 'application/vnd.google-apps.document',
			'xlsx' => 'application/vnd.google-apps.spreadsheet',
			'pptx' => 'application/vnd.google-apps.presentation',
		);

		return isset($googleMimeTypes[$ext])? $googleMimeTypes[$ext] : null;
	}
}