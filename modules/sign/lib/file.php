<?php
namespace Bitrix\Sign;

use Bitrix\Main\Security\Random;
use Bitrix\Sign\Main\Application;

/**
 * @deprecated
 */
class File
{
	/**
	 * File id, if file was saved.
	 * @var int
	 */
	private $fileId = null;

	/**
	 * Current file's name.
	 * @var string|null
	 */
	private $name;

	/**
	 * Current file's path.
	 * @var string|null
	 */
	private $path;

	/**
	 * Current file's type.
	 * @var string|null
	 */
	private $type;

	/**
	 * File will be saved within this module.
	 * @var string
	 */
	private $module = 'sign';

	/**
	 * Current file's size.
	 * @var int
	 */
	private $size = 0;

	/**
	 * Current file's dimension.
	 * @var array [w, h]
	 */
	private $dimension = [null, null];

	/**
	 * Create file instance.
	 * @param array|int|string $file Base64 file [name, content] or $_FILE item or registered file id or absolute path to file.
	 */
	public function __construct($file)
	{
        $madeFileArray = false;

		if (($file['name'] ?? null) && ($file['content'] ?? null))
		{
			$tempPath = \CFile::getTempName(
				'',
				(
					mb_detect_encoding($file['name'], ['UTF-8'], true)
						? $this->extractFilename($file['name'])
						: Random::getString(12, true)
				) .".".$this->extractExtension($file['name'])
			);

			$fileIO = new \Bitrix\Main\IO\File($tempPath);
			$fileIO->putContents(base64_decode($file['content']));
			$file = \CFile::makeFileArray($tempPath);
			$madeFileArray = true;
			$this->path = $tempPath;

			if (!$file)
			{
				return;
			}
		}

		if (is_array($file))
		{
			if ($file['tmp_name'] ?? null)
			{
				if (!$madeFileArray && !is_uploaded_file($file['tmp_name']))
				{
					unset($file['tmp_name']);
				}
			}

			$this->name = $file['name'] ?? null;
			$this->path = $file['tmp_name'] ?? $this->path;
			$this->type = $file['type'] ?? null;
			$this->module = $file['module_id'] ?? $this->module;
			$this->size = $file['size'] ?? 0;
		}
		else if (is_integer($file))
		{
			$this->fileId = $file;
			$file = \CFile::makeFileArray($file);
			if (!$file)
			{
				return;
			}

			$this->name = $file['name'];
			$this->type = $file['type'];
			$this->size = $file['size'];
			$this->path = $file['tmp_name'];
		}
		else if (is_string($file))
		{
			$this->name = basename($file);
			$this->type = \CFile::getContentType($file, true);
			$this->size = filesize($file);
			$this->path = $file;
		}
	}

	/**
	 * Returns true, if file exists.
	 * @return bool
	 */
	public function isExist(): bool
	{
		if ($this->path !== null)
		{
			return file_exists($this->path);
		}
		return false;
	}

	/**
	 * Returns file's name if it was registered.
	 * @return int|null
	 */
	public function getId(): ?int
	{
		return $this->fileId;
	}

	/**
	 * Returns file's name.
	 * @return string|null
	 */
	public function getName(): ?string
	{
		return $this->name;
	}

	/**
	 * Returns file's extension.
	 * @return string|null
	 */
	public function getExtension(): ?string
	{
		return $this->extractExtension($this->name);
	}

	public function extractExtension($name): ?string
	{
		$parts = explode('.', $name);
		return is_array($parts) ? array_pop($parts) : null;
	}

	private function extractFilename($name): ?string
	{
		$parts = explode('.', $name);
		return is_array($parts) ? array_shift($parts) : null;
	}

	/**
	 * Sets new file name.
	 * @param string $name File name.
	 * @return void
	 */
	public function setName(string $name): void
	{
		$this->name = $name;
	}

	/**
	 * Sets new file module name.
	 * @param string $module Module name.
	 * @return void
	 */
	public function setModule(string $module): void
	{
		$this->module = $module;
	}

	/**
	 * Returns file's path.
	 * @return string|null
	 */
	public function getPath(): ?string
	{
		return $this->path;
	}

	/**
	 * Returns file's relative path.
	 * @return string|null
	 */
	public function getRelativePath(): ?string
	{
		if ($this->fileId)
		{
			return \CFile::getPath($this->fileId);
		}

		return mb_substr(
			$this->path,
			mb_strlen(Application::getServer()->getDocumentRoot())
		);
	}

	/**
	 * Returns file's uri path (only for saved file).
	 * @return string|null
	 */
	public function getUriPath(): ?string
	{
		static $host = null;

		if (!$host)
		{
			$protocol = Main\Application::isHttps() ? 'https://' : 'http://';
			$host = Config\Storage::instance()->getSelfHost();
			$host = $protocol . $host;
		}

		$src = $this->getRelativePath();
		if ($src)
		{
			$src = $host . $src;
		}

		return $src;
	}

	/**
	 * Returns file's content.
	 * @return string|null
	 */
	public function getContent(): ?string
	{
		return file_get_contents($this->path) ?: null;
	}

	/**
	 * Returns file's base64 encoded content.
	 * @return string|null
	 */
	public function getBase64Content(): ?string
	{
		$content = $this->getContent();
		return $content ? base64_encode($content) : null;
	}

	/**
	 * Returns file's type.
	 * @return string|null
	 */
	public function getType(): ?string
	{
		return $this->type;
	}

	/**
	 * Returns file's size.
	 * @return int
	 */
	public function getSize(): int
	{
		return (int)$this->size;
	}

	/**
	 * Returns picture width and height.
	 * @param int $index 0 - width, 1 - height.
	 * @return int
	 */
	private function getDimension(int $index): int
	{
		if (!$this->isExist())
		{
			return 0;
		}

		if ($this->dimension[$index] === null)
		{
			[$this->dimension[0], $this->dimension[1]] = getimagesize($this->path);
		}

		return $this->dimension[$index];
	}

	/**
	 * Returns picture width.
	 * @return int
	 */
	public function getWidth(): int
	{
		return $this->getDimension(0);
	}

	/**
	 * Returns picture height.
	 * @return int
	 */
	public function getHeight(): int
	{
		return $this->getDimension(1);
	}

	/**
	 * Returns true, if file is image.
	 * @return bool
	 */
	public function isImage(): bool
	{
		return $this->type === 'image/jpeg'
				|| $this->type === 'image/png'
				|| $this->type === 'image/webp'
				|| $this->type === 'image/gif';
	}

	/**
	 * Returns true, if file is PDF.
	 * @return bool
	 */
	public function isPdf(): bool
	{
		return $this->type === 'application/pdf';
	}

	/**
	 * Returns true, if file is MS Word.
	 * @return bool
	 */
	public function isDoc(): bool
	{
		return in_array($this->getExtension(), ['doc', 'docx', 'rtf', 'odt']);
	}

	/**
	 * Saves current file to database.
	 * If file was saved, return previously id.
	 * @return int|null
	 */
	public function save(): ?int
	{
		if ($this->fileId === null)
		{
			$this->fileId = \CFile::saveFile([
				'name' => $this->name,
				'tmp_name' => $this->path,
				'type' => $this->type,
				'size' => $this->getSize(),
				'MODULE_ID' => $this->module
			], $this->module);
		}

		return $this->fileId ?: null;
	}

	/**
	 * Deletes file from disk.
	 * @return bool
	 */
	public function unlink(): bool
	{
		if ($this->fileId)
		{
			\CFile::delete($this->fileId);
			return true;
		}

		return false;
	}

	/**
	 * Deletes file from disk.
	 * @param int $fileId File id.
	 * @return void
	 */
	public static function delete(int $fileId): void
	{
		\CFile::delete($fileId);
	}

	/**
	 * Makes copy of current file to another location.
	 * @param string $toPath Destination path.
	 * @return bool
	 */
	public function copy(string $toPath): bool
	{
		return copy($this->getPath(), $toPath);
	}

	/**
	 * Resizes current file.
	 * @param array $size Restricted size array [width, height].
	 * @return bool
	 */
	public function resizeProportional(array $size): bool
	{
		$file = $this->getArray();
		$resized = \CFile::resizeImage($file, $size, BX_RESIZE_IMAGE_PROPORTIONAL_ALT);

		if ($resized)
		{
			$this->path = $file['tmp_name'];
			$this->size = $file['size'];
			[$this->dimension[0], $this->dimension[1]] = getimagesize($this->path);
		}

		return $resized;
	}

	/**
	 * Returns file as $_FILE's data.
	 * @return array
	 */
	public function getArray(): array
	{
		return [
			'name' => $this->name,
			'tmp_name' => $this->path,
			'type' => $this->type,
			'size' => $this->getSize()
		];
	}

	public function setId(?int $id): static
	{
		$this->fileId = $id;
		return $this;
	}
}
