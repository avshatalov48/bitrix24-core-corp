<?
/*.
	require_module 'standard';
	require_module 'pcre';
	require_module 'hash';
	require_module 'bitrix_main';
	require_module 'bitrix_clouds_classes_storage_service';
	require_module 'bitrix_clouds_classes_storage_bucket';
.*/
IncludeModuleLangFile(__FILE__);

class CCloudStorageUpload
{
	protected /*.string.*/ $_filePath = "";
	protected /*.string.*/ $_ID = "";
	protected /*.CCloudStorageBucket.*/ $obBucket;
	protected /*.int.*/ $_max_retries = 3;
	protected /*.array[string]string.*/ $_cache = null;

	/**
	 * @param string $filePath
	 * @return void
	*/
	function __construct($filePath)
	{
		$this->_filePath = $filePath;
		$this->_ID = "1".substr(md5($filePath), 1);
	}

	/**
	 * @return array[string]string
	*/
	public function GetArray()
	{
		global $DB;

		if(!isset($this->_cache))
		{
			$rs = $DB->Query("
				SELECT *
				FROM b_clouds_file_upload
				WHERE ID = '".$this->_ID."'
			");
			$this->_cache = $rs->Fetch();
		}

		return $this->_cache;
	}

	/**
	 * @return bool
	*/
	public function isStarted()
	{
		return is_array($this->GetArray());
	}

	/**
	 * @return void
	*/
	public function Delete()
	{
		global $DB;
		//TODO: clean up temp files in Clodo
		$DB->Query("DELETE FROM b_clouds_file_upload WHERE ID = '".$this->_ID."'");
		unset($this->_cache);
	}

	/**
	 * @return void
	*/
	public function DeleteOld()
	{
		global $DB;
		$DB->Query("DELETE FROM b_clouds_file_upload WHERE TIMESTAMP_X < ".$DB->CharToDateFunction(ConvertTimeStamp(time()-24*60*60)));
	}

	/**
	 * @param int $bucket_id
	 * @param float $fileSize
	 * @param string $ContentType
	 * @return bool
	*/
	function Start($bucket_id, $fileSize, $ContentType = 'binary/octet-stream', $tmpFileName = false)
	{
		global $DB;

		if(is_object($bucket_id))
			$obBucket = $bucket_id;
		else
			$obBucket = new CCloudStorageBucket(intval($bucket_id));

		if(!$obBucket->Init())
			return false;

		if(!$this->isStarted())
		{
			$arUploadInfo = /*.(array[string]string).*/array();
			$bStarted = $obBucket->GetService()->InitiateMultipartUpload(
				$obBucket->GetBucketArray(),
				$arUploadInfo,
				$this->_filePath,
				$fileSize,
				$ContentType
			);
			if (!$bStarted && $obBucket->RenewToken())
			{
				$bStarted = $obBucket->GetService()->InitiateMultipartUpload(
					$obBucket->GetBucketArray(),
					$arUploadInfo,
					$this->_filePath,
					$fileSize,
					$ContentType
				);
			}

			if($bStarted)
			{
				$bAdded = $DB->Add("b_clouds_file_upload", array(
					"ID" => $this->_ID,
					"~TIMESTAMP_X" => $DB->CurrentTimeFunction(),
					"FILE_PATH" => $this->_filePath,
					"FILE_SIZE" => $fileSize,
					"TMP_FILE" => $tmpFileName,
					"BUCKET_ID" => intval($obBucket->ID),
					"PART_SIZE" => $obBucket->GetService()->GetMinUploadPartSize(),
					"PART_NO" => 0,
					"PART_FAIL_COUNTER" => 0,
					"NEXT_STEP" => serialize($arUploadInfo),
				), array("NEXT_STEP"));
				unset($this->_cache);

				return $bAdded !== false;
			}
		}

		return false;
	}

	/**
	 * @param string $data
	 * @return bool
	*/
	function Next($data, $obBucket = null)
	{
		global $APPLICATION;

		if($this->isStarted())
		{
			$ar = $this->GetArray();

			if($obBucket == null)
				$obBucket = new CCloudStorageBucket(intval($ar["BUCKET_ID"]));

			if(!$obBucket->Init())
			{
				$APPLICATION->ThrowException(GetMessage('CLO_STORAGE_UPLOAD_ERROR', array('#errno#'=>1)));
				return false;
			}

			$arUploadInfo = unserialize($ar["NEXT_STEP"]);
			$bSuccess = $obBucket->GetService()->UploadPart(
				$obBucket->GetBucketArray(),
				$arUploadInfo,
				$data
			);

			if (!$this->UpdateProgress($arUploadInfo, $bSuccess))
			{
				$APPLICATION->ThrowException(GetMessage('CLO_STORAGE_UPLOAD_ERROR', array('#errno#'=>2)));
				return false;
			}

			return $bSuccess;
		}

		return false;
	}

	/**
	 * @param string $data
	 * @param int $part_no
	 * @return bool
	*/
	function Part($data, $part_no, $obBucket = null)
	{
		global $APPLICATION;

		if($this->isStarted())
		{
			$ar = $this->GetArray();

			if($obBucket == null)
				$obBucket = new CCloudStorageBucket(intval($ar["BUCKET_ID"]));

			if(!$obBucket->Init())
			{
				$APPLICATION->ThrowException(GetMessage('CLO_STORAGE_UPLOAD_ERROR', array('#errno#'=>3)));
				return false;
			}

			$service = $obBucket->GetService();
			if (!is_callable(array($service, 'UploadPartNo')))
			{
				$APPLICATION->ThrowException(GetMessage('CLO_STORAGE_UPLOAD_ERROR', array('#errno#'=>4)));
				return false;
			}

			$arUploadInfo = unserialize($ar["NEXT_STEP"]);
			$bSuccess = $obBucket->GetService()->UploadPartNo(
				$obBucket->GetBucketArray(),
				$arUploadInfo,
				$data,
				$part_no
			);

			if (!$this->UpdateProgress($arUploadInfo, $bSuccess))
			{
				$APPLICATION->ThrowException(GetMessage('CLO_STORAGE_UPLOAD_ERROR', array('#errno#'=>5)));
				return false;
			}

			return $bSuccess;
		}

		return false;
	}

	/**
	 * @return bool
	*/
	function Finish($obBucket = null)
	{
		if($this->isStarted())
		{
			$ar = $this->GetArray();

			if($obBucket == null)
				$obBucket = new CCloudStorageBucket(intval($ar["BUCKET_ID"]));
			if(!$obBucket->Init())
				return false;

			$arUploadInfo = unserialize($ar["NEXT_STEP"]);
			$bSuccess = $obBucket->GetService()->CompleteMultipartUpload(
				$obBucket->GetBucketArray(),
				$arUploadInfo
			);

			if($bSuccess)
				$this->Delete();

			$this->DeleteOld();

			if ($bSuccess)
			{
				if ($obBucket->getQueueFlag())
				{
					CCloudFailover::queueCopy($obBucket, $this->_filePath);
				}

				foreach(GetModuleEvents("clouds", "OnAfterCompleteMultipartUpload", true) as $arEvent)
				{
					ExecuteModuleEventEx($arEvent, array($obBucket, array("size" => $ar["FILE_SIZE"]), $this->_filePath));
				}
			}
			
			return $bSuccess;
		}

		return false;
	}

	/**
	 * @return int
	*/
	function GetPartCount()
	{
		$ar = $this->GetArray();

		if(is_array($ar))
			return intval($ar["PART_NO"]);
		else
			return 0;
	}

	/**
	 * @return float
	*/
	function GetPos()
	{
		$ar = $this->GetArray();

		if(is_array($ar))
			return intval($ar["PART_NO"])*doubleval($ar["PART_SIZE"]);
		else
			return 0;
	}

	/**
	 * @return int
	*/
	function getPartSize()
	{
		$ar = $this->GetArray();

		if(is_array($ar))
			return intval($ar["PART_SIZE"]);
		else
			return 0;
	}

	/**
	 * @return bool
	*/
	function hasRetries()
	{
		$ar = $this->GetArray();
		return is_array($ar) && (intval($ar["PART_FAIL_COUNTER"]) < $this->_max_retries);
	}

	/**
	 * @return string
	*/
	function getTempFileName()
	{
		$ar = $this->GetArray();
		if(is_array($ar))
			return $ar["TMP_FILE"];
		else
			return "";
	}

	/**
	 * @param array $arUploadInfo
	 * @param boolean $bSuccess
	 * @return boolean
	*/
	protected function UpdateProgress($arUploadInfo, $bSuccess)
	{
		global $DB;

		if ($bSuccess)
		{
			$arFields = array(
				"NEXT_STEP" => serialize($arUploadInfo),
				"~PART_NO" => "PART_NO + 1",
				"PART_FAIL_COUNTER" => 0,
			);
			$arBinds = array(
				"NEXT_STEP" => $arFields["NEXT_STEP"],
			);
		}
		else
		{
			$arFields = array(
				"~PART_FAIL_COUNTER" => "PART_FAIL_COUNTER + 1",
			);
			$arBinds = array(
			);
		}

		$strUpdate = $DB->PrepareUpdate("b_clouds_file_upload", $arFields);
		if ($strUpdate != "")
		{
			$strSql = "UPDATE b_clouds_file_upload SET ".$strUpdate." WHERE ID = '".$this->_ID."'";
			if(!$DB->QueryBind($strSql, $arBinds))
			{
				unset($this->_cache);
				return false;
			}
		}

		unset($this->_cache);
		return true;
	}
	
}
?>