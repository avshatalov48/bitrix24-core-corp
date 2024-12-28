<?php

namespace Bitrix\Sign\Repository;

use Bitrix\Main;
use Bitrix\Main\Web\MimeType;
use Bitrix\Sign\Item;

class FileRepository
{
	private const MODULE_ID = 'sign';

	public function __construct(
		private string $path,
	)
	{
	}

	public function put(Item\Fs\File $file): Main\Result
	{
		$result = new Main\Result();
		if ($file->content->data === null)
		{
			return $result->addError(new Main\Error('Can not save file. Content data is not set.'));
		}

		if (Main\IO\Path::getName($file->name) !== $file->name)
		{
			return $result->addError(new Main\Error('Can not save file. Wrong file name.'));
		}

		if (!Main\IO\Path::validateFilename($file->name))
		{
			return $result->addError(new Main\Error('Can not save file. Invalid file name format.'));
		}

		$documentRoot = Main\Application::getDocumentRoot();
		if ($documentRoot === null)
		{
			return $result->addError(new Main\Error('Can not save file. Invalid document root.'));
		}

		$fileDir = $this->trimSlashes($file->dir);
		$paths[] = $documentRoot;
		$paths[] = $this->trimSlashes(Main\Config\Option::get('main', 'upload_dir', 'upload'));
		$paths[] = $this->trimSlashes($this->path);
		$paths[] = $fileDir;
		$paths[] = $file->name;
		$path = implode('/', array_filter($paths));

		if (!preg_match('/^((?!\.\/).)*$/', $path))
		{
			return $result->addError(new Main\Error('Can not save file. You can use only absolute path.'));
		}

		if (!Main\IO\Path::validate($path))
		{
			return $result->addError(new Main\Error('Can not save file. Invalid path: ' . $path));
		}

		if (Main\IO\File::isFileExists($path))
		{
			return $result->addError(new Main\Error('Can not save file. File already exists in filesystem.'));
		}

		$type = $file->type ?: self::getMimeTypeByFileName($file->name);
		$type = MimeType::normalize($type);

		$id = (int)\CFile::SaveFile(
			arFile: [
				'MODULE_ID' => self::MODULE_ID,
				'name' => $file->name,
				'type' => $type,
				'content' => $file->content->data,
			],
			strSavePath: $this->path,
			dirAdd: $fileDir,
		);
		if ($id)
		{
			$file->type = $type;
			$file->id = $id;
			return $result;
		}

		return $result->addError(new Main\Error('Can not save file'));
	}

	private function trimSlashes(string $path): string
	{
		$path = rtrim($path, '/');

		return trim($path, '/');
	}

	public function copyById(int $id): ?Item\Fs\File
	{
		$newId = \CFile::CloneFile($id);
		if (!$newId)
		{
			return null;
		}
		return $this->getById($newId);
	}

	public function deleteById(int $id): Main\Result
	{
		\CFile::delete($id);
		return new Main\Result;
	}

	public function getFileSrc(int $id): ?string
	{
		$fileArray = \CFile::GetFileArray($id);
		if($fileArray === false)
		{
			return null;
		}

		return $fileArray['SRC'] ?? null;
	}

	public function getById(int $id, bool $readContent = false): ?Item\Fs\File
	{
		$fileData = \CFile::getFileArray($id);
		if (!$fileData)
		{
			return null;
		}

		$dir = $fileData['SUBDIR'];
		if (mb_strpos($dir, $this->path) === 0)
		{
			$dir = mb_substr($dir, mb_strlen($this->path) + 1);
		}

		$file = new Item\Fs\File(
			name: $fileData['FILE_NAME'],
			dir: $dir,
			type: $fileData['CONTENT_TYPE'],
			id: $id,
			isImage: MimeType::isImage($fileData['CONTENT_TYPE']),
		);

		if ($readContent)
		{
			$this->readContent($file);
		}

		return $file;
	}

	public function readContent(Item\Fs\File $file): Item\Fs\FileContent
	{
		$path = \CFile::MakeFileArray($file->id)['tmp_name'] ?? '';
		if (!$path || !Main\IO\File::isFileExists($path))
		{
			return $file->content;
		}

		$data = Main\IO\File::getFileContents($path);
		$file->content->data = ($data === null || $data === false) ? '' : $data;
		return $file->content;
	}

	public function list(int ...$ids): Item\Fs\FileCollection
	{
		$collection = new Item\Fs\FileCollection();
		foreach ($ids as $id)
		{
			$file = $this->getById($id);
			if ($file)
			{
				$collection->addItem($file);
			}
		}

		return $collection;
	}

	protected static function getMimeTypeByFileName($fileName): string
	{
		$extension = mb_strtolower(getFileExtension($fileName));
		$list = MimeType::getMimeTypeList();
		if (isset($list[$extension]))
		{
			return $list[$extension];
		}

		return 'text/plain';
	}
}
