<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CWebDavTmpFile
{
	const TABLE_NAME = 'b_webdav_storage_tmp_file';

	public $id;
	public $name;
	public $filename;
	public $path;
	public $version;
	public $isCloud = 0;
	public $bucketId;
	public $height;
	public $width;

	private static $_columns = array(
		'IS_CLOUD' => true,
		'BUCKET_ID' => true,
		'NAME' => true,
		'FILENAME' => true,
		'PATH' => true,
		'ID' => true,
		'VERSION' => true,
		'WIDTH' => true,
		'HEIGHT' => true,
	);

	public static function getList(array $order = array(), array $filter = array())
	{
		$t = static::TABLE_NAME;
		$order = array_intersect_key($order, static::$_columns);
		$sqlWhere = self::buildWhereExpression($filter);

		$sqlOrder = '';
		if($order)
		{
			$sqlOrder = array();
			foreach ($order as $by => $ord)
			{
				$by = strtoupper($by);
				$sqlOrder[] = $by . ' ' . (strtoupper($ord) == 'DESC' ? 'DESC' : 'ASC');
			}
			unset($by);
			$sqlOrder = ' ORDER BY ' . implode(', ', $sqlOrder);
		}

		return static::getDb()->query("SELECT * FROM {$t} {$sqlWhere} {$sqlOrder}");
	}

	public static function deleteRows(array $filter)
	{
		$t = static::TABLE_NAME;
		$sqlWhere = self::buildWhereExpression($filter);

		return static::getDb()->query("DELETE FROM {$t} {$sqlWhere}");
	}

	protected function deleteRow()
	{
		$t = static::TABLE_NAME;
		$this->id = (int)$this->id;

		return $this->getDb()->query("DELETE FROM {$t} WHERE id = {$this->id}");
	}

	protected function deleteTmpFile()
	{
		if($this->existsFile())
		{
			unlink($this->getAbsolutePath());
		}
	}

	protected function deleteCloudFile()
	{
		if($this->isCloud && $this->bucketId)
		{
			$bucket = $this->getBucket();
			$bucket->deleteFile($this->path . '/' . $this->filename);
		}
	}

	/**
	 * @param array $where
	 * @return string
	 */
	private static function buildWhereExpression(array $where)
	{
		$where = array_intersect_key($where, array_merge(static::$_columns, array('IRRELEVANT' => true)));
		$sqlWhere = array();
		foreach ($where as $field => $value)
		{
			switch ($field)
			{
				case 'ID':
				case 'IS_CLOUD':
				case 'BUCKET_ID':
					$value      = (int)$value;
					$sqlWhere[] = $field . '=' . $value;
					break;
				case 'NAME':
				case 'FILENAME':
				case 'PATH':
					$value      = static::getDb()->forSql($value);
					$sqlWhere[] = $field . '=' . '\'' . $value . '\'';
					break;
				case "IRRELEVANT":
					$arSqlSearch[] = 'VERSION < ' . strtotime('yesterday');
					break;
				case 'VERSION':
					//todo version is long int
					$value      = (int)$value;
					$sqlWhere[] = $field . '>=' . $value;
					break;
			}
		}
		unset($value);

		if ($sqlWhere)
		{
			$sqlWhere = ' WHERE ' . implode(' AND ', $sqlWhere);

			return $sqlWhere;
		}
		return '';
	}

	protected function existsFile()
	{
		return file_exists($this->getAbsolutePath()) && is_file($this->getAbsolutePath());
	}

	public function delete()
	{
		if($this->deleteRow())
		{
			$this->deleteTmpFile();
			$this->deleteCloudFile();
			return true;
		}
		return false;
	}

	private function getAbsoluteCloudPath()
	{
		if($this->isCloud && $this->bucketId)
		{
			$bucket = $this->getBucket();
			return $bucket->getFileSRC($this->path . '/' . $this->filename);
		}
		return false;
	}

	public function getAbsolutePath()
	{
		if(($path = $this->getAbsoluteCloudPath()))
		{
			return $path;
		}
		return CTempFile::GetAbsoluteRoot() . '/' . $this->path;
	}

	public function getContent()
	{
		if(!$this->existsFile())
		{
			return false;
		}
		return file_get_contents($this->getAbsolutePath());
	}

	public static function getOne($name)
	{
		$query = static::getList(array(), array('NAME' => $name));

		return !empty($query)? $query->fetch() : false;
	}

	/**
	 * @param $name
	 * @return bool|CWebDavTmpFile
	 */
	public static function buildByName($name)
	{
		return static::buildFromRow(static::getOne($name));
	}

	protected static function buildFromRow($row)
	{
		if(empty($row) || (is_array($row) && !array_filter($row)))
		{
			return false;
		}
		/** @var CWebDavTmpFile $model  */
		$model = new static();
		//todo may path convert to 32 symbols hash (md5).
		$model->id = $row['ID'];
		$model->name = $row['NAME'];
		$model->filename = $row['FILENAME'];
		$model->path = $row['PATH'];
		$model->version = $row['VERSION'];
		$model->isCloud = $row['IS_CLOUD'];
		$model->bucketId = $row['BUCKET_ID'];
		$model->width = $row['WIDTH'];
		$model->height = $row['HEIGHT'];

		if(!$model->isCloud && !$model->bucketId && !$model->existsFile())
		{
			$model->deleteRow();

			return false;
		}

		return $model;
	}

	protected static function generatePath()
	{
		$tmpName = md5(mt_rand() . mt_rand());
		$dir = rtrim(CTempFile::GetDirectoryName(2, 'webdav'), '/') . '/';
		CheckDirPath($dir); //make folder recursive
		$pathItems = explode(CTempFile::GetAbsoluteRoot(), $dir . $tmpName);

		return array(array_pop($pathItems), $tmpName);
	}

	/**
	 * @param array $downloadedFile
	 * @return CWebDavTmpFile
	 * @throws WebDavTmpFileErrorException
	 */
	public static function buildFromDownloaded(array $downloadedFile)
	{
		/** @var CWebDavTmpFile $model  */
		$model = new static();
		$model->version = time();
		list($model->path, $model->name) = static::generatePath();

		if (($downloadedFile['error'] = intval($downloadedFile['error'])) > 0)
		{
			if ($downloadedFile['error'] < 3)
			{
				throw new WebDavTmpFileErrorException('UPLOAD_MAX_FILESIZE: ' . intval(ini_get('upload_max_filesize')));
			}
			else
			{
				throw new WebDavTmpFileErrorException('UPLOAD_ERROR ' . $downloadedFile['error']);
			}
		}
		else
		{
			//check permission? success download
		}
		if(!is_uploaded_file($downloadedFile['tmp_name']))
		{
			throw new WebDavTmpFileErrorException('UPLOAD_ERROR');
		}

		if(!move_uploaded_file($downloadedFile['tmp_name'], $model->getAbsolutePath()))
		{
			throw new WebDavTmpFileErrorException('Error in move');
		}

		return $model;
	}

	public function save()
	{
		$t = static::TABLE_NAME;
		list($cols, $vals) = static::getDb()->prepareInsert($t, array(
			'NAME' => $this->name,
			'FILENAME' => $this->filename,
			'PATH' => $this->path,
			'VERSION' => (int)$this->version,
			'IS_CLOUD' => (int)$this->isCloud,
			'BUCKET_ID' => (int)$this->bucketId,
			'WIDTH' => (int)$this->width,
			'HEIGHT' => (int)$this->height,
		));

		return $this->getDb()->query("INSERT INTO {$t} ({$cols}) VALUES({$vals})");
	}

	/**
	 * Append CWebDavTmpFile to another CWebDavTmpFile
	 * @param CWebDavTmpFile $file
	 * @param array          $params
	 * @throws WebDavTmpFileErrorException
	 * @return bool
	 */
	public function append(CWebDavTmpFile $file, array $params)
	{
		if(empty($params['endRange']) || empty($params['fileSize']))
		{
			throw new WebDavTmpFileErrorException('Wrong params endRange, fileSize');
		}
		if($this->isCloud)
		{
			return $this->internalAppendCloud($file, $params['startRange'], $params['endRange'], $params['fileSize']);
		}
		else
		{
			return $this->internalAppendNonCloud($file, $params['startRange'], $params['endRange'], $params['fileSize']);
		}
	}

	/**
	 * @param CWebDavTmpFile $file
	 * @param                $startRange
	 * @param                $endRange
	 * @param                $fileSize
	 * @return bool
	 * @throws WebDavTmpFileErrorException
	 */
	private function internalAppendCloud(CWebDavTmpFile $file, $startRange, $endRange, $fileSize)
	{
		$isLastChunk = ($endRange + 1) == $fileSize;
		if(!CModule::IncludeModule("clouds"))
		{
			throw new WebDavTmpFileErrorException('Could not include clouds module');
		}
		$bucket = $this->getBucket();

		if
		(
			($endRange - $startRange + 1) != $bucket->getService()->getMinUploadPartSize() && !$isLastChunk
		)
		{
			throw new WebDavTmpFileErrorException('Error in size chunk. Must be equals ' . $bucket->getService()->getMinUploadPartSize() . ' if not last chunk');
		}

		$upload = new CCloudStorageUpload($this->path . '/' . $this->filename);
		if(!$upload->isStarted())
		{
			if(!$upload->start($bucket->ID, $fileSize))
			{
				throw new WebDavTmpFileErrorException('Could not start cloud upload');
			}
		}
		$success = false;
		$fileContent = $file->getContent();
		if($fileContent === false)
		{
			throw new WebDavTmpFileErrorException('Could not get contents file');
		}
		$fails = 0;
		while ($upload->hasRetries())
		{
			if ($upload->next($fileContent))
			{
				$success = true;
				break;
			}
			$fails++;
		}
		if (!$success)
		{
			throw new WebDavTmpFileErrorException('Could not upload part of file for '.$fails.' times');
		}
		elseif($success && $isLastChunk)
		{
			if($upload->finish())
			{
				//we don't  inc and don't dec
				//$bucket->incFileCounter($fileSize);
			}
			else
			{
				throw new WebDavTmpFileErrorException('Could not finish resumable upload');
			}
			return true;
		}
		elseif($success)
		{
			return true;
		}
	}

	/**
	 * @param CWebDavTmpFile $file
	 * @param                $startRange
	 * @param                $endRange
	 * @param                $fileSize
	 * @return bool
	 * @throws WebDavTmpFileErrorException
	 */
	private function internalAppendNonCloud(CWebDavTmpFile $file, $startRange, $endRange, $fileSize)
	{
		if(!$this->existsFile())
		{
			throw new WebDavTmpFileErrorException('Append content. Not exists file (target)');
		}
		if(!$file->existsFile())
		{
			throw new WebDavTmpFileErrorException('Append content. Not exists file');
		}
		$fp = fopen($this->getAbsolutePath(), 'ab');
		if(!$fp)
		{
			throw new WebDavTmpFileErrorException('Could not open file');
		}
		$appendContent = $file->getContent();
		if($appendContent === false)
		{
			throw new WebDavTmpFileErrorException('Could not get contents file (target)');
		}
		if(fwrite($fp, $appendContent) === false)
		{
			throw new WebDavTmpFileErrorException('Error in fwrite (append)');
		}
		fclose($fp);

		return true;
	}

	/**
	 * @return CDatabase
	 */
	protected static function getDb()
	{
		global $DB;

		return $DB;
	}

	/**
	 * @return CCloudStorageBucket
	 * @throws WebDavTmpFileErrorException
	 */
	private function getBucket()
	{
		if(!CModule::IncludeModule("clouds"))
		{
			throw new WebDavTmpFileErrorException('Could not init bucket');
		}
		$bucket = new CCloudStorageBucket($this->bucketId);
		if (!$bucket->init())
		{
			throw new WebDavTmpFileErrorException('Could not init bucket');
		}

		return $bucket;
	}

	/**
	 * Delete expired tmp entries
	 * @return string
	 */
	public static function removeExpired()
	{
		$query = static::getList(array(), array(
			'IRRELEVANT' => true,
		));
		while($row = $query->fetch())
		{
			$model = static::buildFromRow($row);
			if(!$model)
			{
				continue;
			}

			if($model && $model->isCloud)
			{
				$model->delete();
			}
			else
			{
				//BX-TEMP will be clean by CTempFile
				$model->deleteRow();
			}
		}

		return "CWebDavTmpFile::removeExpired();";
	}
}
class WebDavTmpFileErrorException extends Exception
{}
