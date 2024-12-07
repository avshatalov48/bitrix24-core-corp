<?php
namespace Bitrix\Sign;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Sign\Access\Model\UserModel;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService;
use Bitrix\Sign\Blank\Block;
use CCrmPerms;

Loc::loadMessages(__FILE__);

/**
 * @deprecated
 */
class Blank extends \Bitrix\Sign\Internal\BaseTable
{
	/**
	 * Allowed blank extensions.
	 */
	private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'rtf', 'odt'];

	/**
	 * Internal class.
	 * @var string
	 */
	public static $internalClass = 'BlankTable';

	/**
	 * Current blank data.
	 * @var array
	 */
	protected $data;

	/**
	 * Document, if we're within document context.
	 * @var Document
	 */
	private $document;

	/**
	 * Callbacks after set blocks to the blank.
	 * @var callable[]
	 */
	private $callbacksAfterSetBlocks = [];

	/**
	 * Class constructor.
	 * @param array $row Blank data.
	 */
	private function __construct(array $row)
	{
		$this->data = $row;
	}

	/**
	 * Sets document, if we're within document context.
	 * @param Document $document Document instance.
	 * @return void
	 */
	public function setDocument(Document $document): void
	{
		$this->document = $document;
	}

	/**
	 * Finds document by entity type and id. If not found, creates new document by entity type id.
	 * @param string $entityType Entity type.
	 * @param int|null $entityId Entity id (if not specified, creates new empty entity).
	 * @return Document|null
	 */
	public function createDocument(string $entityType, ?int $entityId = null): ?Document
	{
		return Document::createByBlank($this->data['ID'], $entityType, $entityId);
	}

	/**
	 * Copies current Blank to another one.
	 * @return self|null
	 */
	public function copyBlank(): ?self
	{
		$resBlankAdd = self::add(self::prepareRowForDuplicate($this->data));
		if ($resBlankAdd->isSuccess())
		{
			$newBlank = self::getById($resBlankAdd->getId());
			if ($newBlank)
			{
				$resBlock = Blank\Block::getList([
					'select' => [
						'*'
					],
					'filter' => [
						'BLANK_ID' => $this->data['ID']
					],
					'order' => [
						'ID' => 'asc'
					]
				]);
				while ($rowBlock = $resBlock->fetch())
				{
					$rowBlock = self::prepareRowForDuplicate($rowBlock);
					$rowBlock['BLANK_ID'] = $newBlank->getId();
					if (!$rowBlock['STYLE'])
					{
						unset($rowBlock['STYLE']);
					}

					$resBlockAdd = Blank\Block::add($rowBlock);
					if (!$resBlockAdd->isSuccess())
					{
						self::delete($resBlankAdd->getId())->isSuccess();
						Error::getInstance()->addFromResult($resBlockAdd);

						return null;
					}
				}

				return $newBlank;
			}
		}
		else
		{
			Error::getInstance()->addFromResult($resBlankAdd);
		}

		return null;
	}

	/**
	 * Deletes current Blank.
	 * @return bool
	 */
	public function unlink(): bool
	{
		return self::delete($this->data['ID'])->isSuccess();
	}

	/**
	 * Returns blank files.
	 * @return File[]
	 */
	public function getFiles(): array
	{
		$files = [];

		foreach ((array)$this->data['FILE_ID'] as $fileId)
		{
			$files[] = new File($fileId);
		}

		return $files;
	}

	/**
	 * Returns blank id.
	 * @return int
	 */
	public function getId(): int
	{
		return $this->data['ID'];
	}

	/**
	 * Returns true if current blank's file(s) is converted in right format.
	 * @return bool
	 */
	public function isConverted(): bool
	{
		return $this->data['CONVERTED'] === 'Y';
	}

	/**
	 * Return blank title
	 *
	 * @return string|null
	 */
	public function getTitle(): ?string
	{
		return $this->data['TITLE'];
	}

	/**
	 * Assigns block (not adds) with the blank and returns its data.
	 * @param array $blockData Block items with keys:
	 * - string code Block code.
	 * - int part Member part.
	 * - array data Block data.
	 * @return Blank\Block|null
	 */
	public function assignBlock(array $blockData): ?Blank\Block
	{
		[
			'code' => $code,
			'part' => $part,
			'data' => $data
		] = $blockData;

		$block = Blank\Block::getByCode($code);
		if ($block)
		{
			$block->setDocument($this->document);
			if (is_array($data))
			{
				$block->setPayload($data);
			}
			if ($part)
			{
				$block->setMemberPart($part);
			}

			return $block;
		}

		return null;
	}

	/**
	 * Adds block to the document and returns its data.
	 *
	 * @param array $blockData Block item with keys:
	 * - int id Block id (if exists).
	 * - string code Block code.
	 * - array position Block position within document (top / left / width / height).
	 * - array data Block data.
	 * @return Block|Result|null
	 */
	private function addBlock(array $blockData)
	{
		[
			'id' => $id,
			'code' => $code,
			'part' => $part,
			'position' => $position,
			'style' => $style,
			'data' => $data
		] = $blockData;

		$block = Blank\Block::getByCode($code);
		if ($block)
		{
			$block->setId($id);
			$block->setMemberPart($part);
			$block->setPosition($position);
			$block->setStyle($style);
			$block->setPayload($data);
			$block->setDocument($this->document);

			$fields = [
				'CODE' => $code,
				'BLANK_ID' => $this->data['ID'],
				'POSITION' => $position,
				'STYLE' => $style,
				'DATA' => $data,
				'PART' => $part
			];

			if ($id)
			{
				$res = Blank\Block::update($id, $fields);
			}
			else
			{
				$res = Blank\Block::add($fields);
			}

			if ($res->isSuccess())
			{
				$block->setId($res->getId());
				$block->setBlank($this);
				$block->wasUpdatedOnBlank();

				$checkResult = $block->checkBeforeSave();
				if (!$checkResult->isSuccess())
				{
					Blank\Block::delete($res->getId());
					return $checkResult;
				}

				return $block;
			}
		}

		return null;
	}

	/**
	 * Clears all blocks from current blank.
	 * @return bool
	 */
	public function clearBlocks(): bool
	{
		$res = Blank\Block::getList([
			'select' => [
				'ID'
			],
			'filter' => [
				'BLANK_ID' => $this->data['ID']
			]
		]);
		while ($row = $res->fetch())
		{
			$resAffected = Blank\Block::delete($row['ID']);
			if (!$resAffected->isSuccess())
			{
				Error::getInstance()->addFromResult($resAffected);
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns count of member parts (include my company).
	 * @return int
	 */
	public function getPartCount(): int
	{
		$res = Blank\Block::getList([
			'select' => [
				'PART'
			],
			'filter' => [
				'BLANK_ID' => $this->data['ID']
			],
			'order' => [
				'PART' => 'desc'
			],
			'limit' => 1
		]);
		if ($row = $res->fetch())
		{
			return $row['PART'];
		}

		return 0;
	}

	/**
	 * Returns blank's blocks.
	 * @return Blank\Block[]
	 */
	public function getBlocks(): array
	{
		$blocks = [];

		$res = Blank\Block::getList([
			'select' => [
				'*'
			],
			'filter' => [
				'BLANK_ID' => $this->data['ID']
			]
		]);
		while ($row = $res->fetch())
		{
			$block = Blank\Block::getByCode($row['CODE']);
			if ($block)
			{
				$block->setId($row['ID']);
				$block->setPosition($row['POSITION']);
				$block->setStyle($row['STYLE']);
				$block->setPayload($row['DATA']);
				$block->setMemberPart($row['PART']);
				$block->setDocument($this->document);

				$blocks[] = $block;
			}

			unset($block);
		}

		// sort by position within document
		usort($blocks, function($a, $b)
		{
			$topA = $a->getPosition()['top'] ?? 0;
			$leftA = $a->getPosition()['left'] ?? 0;
			$pageA = $a->getPosition()['page'] ?? 1;

			$topB = $b->getPosition()['top'] ?? 0;
			$leftB = $b->getPosition()['left'] ?? 0;
			$pageB = $b->getPosition()['page'] ?? 1;

			if ($pageA !== $pageB)
			{
				return $pageA > $pageB ? 1 : -1;
			}

			if ($topA !== $topB)
			{
				return $topA > $topB ? 1 : -1;
			}

			if ($leftA === $leftB)
			{
				return 0;
			}

			return $leftA > $leftB ? 1 : -1;
		});

		return $blocks;
	}

	/**
	 * Returns blank's blocks data.
	 * @return array
	 */
	public function getBlocksData(): array
	{
		$blocks = [];

		foreach ($this->getBlocks() as $block)
		{
			$blocks[] = $block->getData();
		}

		return $blocks;
	}

	/**
	 * Get Current Data array
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}

	/**
	 * Sets callback for run after all blocks added to the blank.
	 * @param string $uniqCode Unique callback code.
	 * @param callable $callback Callback.
	 * @return void
	 */
	public function setCallbackAfterSetBlock(string $uniqCode, callable $callback): void
	{
		$this->callbacksAfterSetBlocks[$uniqCode] = $callback;
	}

	/**
	 * update blocks by current blocks data
	 * @return Result
	 */
	public function updateBlocks(): Result
	{
		return $this->setBlocks($this->getBlocksData());
	}

	/**
	 * Sets blocks to the blank.
	 * @param array|null $blocks Array of blocks to save within blank.
	 * @see Blank::addBlock() for each block data format.
	 * @return bool
	 */
	public function setBlocks(?array $blocks = null): Result
	{
		$affectedBlocks = [];
		$result = new Result();
		// add blocks
		if ($blocks)
		{
			// add blocks
			foreach ($blocks as $blockData)
			{
				if (!$blockData)
				{
					continue;
				}
				$block = $this->addBlock($blockData);

				if (!$block)
				{
					continue;
				}

				if ($block instanceof Result)
				{
					return $block;
				}

				$affectedBlocks[] = $block->getId();
			}

			// executes callbacks
			foreach ($this->callbacksAfterSetBlocks as $callback)
			{
				$callback();
			}
		}

		// delete removed blocks
		foreach ($this->getBlocks() as $block)
		{
			if (!in_array($block->getId(), $affectedBlocks))
			{
				$block->remove();
			}
		}

		// send blocks to safe
		$blocks = [];
		foreach ($this->getBlocks() as $block)
		{
			$blocks[] = $block->getData(false);
		}
		if ($this->document)
		{
			$safeResponse = Proxy::sendCommand('document.setBlocksToBlank', [
				'hash' => $this->document->getHash(),
				'blocks' => $blocks
			]);

			return $safeResponse === true ? $result : $result->addError(
				new \Bitrix\Main\Error(
					Loc::getMessage('BLANK_BLOCK_SERVER_UPLOADING_FALSE'),
				'BLANK_BLOCK_SERVER_UPLOADING_FALSE'
				)
			);
		}

		return $result;
	}

	/**
	 * Returns count of document based on current blank.
	 * @return int
	 */
	public function getDocumentCount(): int
	{
		$res = Document::getList([
			'select' => [
				new \Bitrix\Main\Entity\ExpressionField(
					'CNT', 'COUNT(*)'
				)
			],
			'filter' => [
				'BLANK_ID' => $this->data['ID']
			]
		]);
		if ($row = $res->fetch())
		{
			return $row['CNT'];
		}

		return 0;
	}

	/**
	 * Adds one more file to the blank (only for blank from images).
	 * @param File[] $files File instances array.
	 * @return bool
	 */
	public function addFiles(array $files): bool
	{
		if (!$this->getFiles()[0]->isImage())
		{
			return false;
		}

		$maxImageFileSize = Option::get('sign', 'max_upload_image_size_kb');
		$totalSize = 0;

		foreach ($files as $file)
		{
			$totalSize += $file->getSize();
		}

		// TODO get limit from safe, or rollback changes when registering document
		if ($totalSize / 1024 > $maxImageFileSize)
		{
			Error::getInstance()->addError(
				'FILE_TOO_BIG',
				Loc::getMessage('SIGN_CORE_BLANK_ERROR_FILE_TOO_BIG', [
					'#SIZE#' => $maxImageFileSize
				])
			);
			return false;
		}

		$fileIds = (array)$this->data['FILE_ID'];

		$totalCount = count($fileIds) + count($files);
		// TODO get limit from safe, or rollback changes when registering document
		$pagesLimit = (int)Option::get('sign', 'max_count_pages_img');
		if ($totalCount > $pagesLimit)
		{
			Error::getInstance()->addError(
				'FILE_TOO_MANY_PAGES',
				Loc::getMessage('SIGN_FILE_BLANK_ERROR_TOO_MANY_PAGES', [
					'#COUNT#' => $pagesLimit
				])
			);
			return false;
		}

		foreach ($files as $file)
		{
			if ($file->isImage())
			{
				$fileIds[] = $file->save();
			}
		}

		return $this->setData([
			'FILE_ID' => $fileIds
		]);
	}

	/**
	 * Finds blank by id and returns its instance.
	 * @param int $id Blank id.
	 * @return self|null
	 */
	public static function getById(int $id): ?self
	{
		$res = self::getList([
			'select' => [
				'*'
			],
			'filter' => [
				'ID' => $id
			],
			'limit' => 1
		]);
		if ($row = $res->fetch())
		{
			return new static($row);
		}

		Error::getInstance()->addError(
			'NOT_FOUND',
			Loc::getMessage('SIGN_CORE_BLANK_ERROR_NOT_FOUND')
		);

		return null;
	}

	/**
	 * Creates blank instance by File.
	 * @param File $file File instance.
	 * @return static|null
	 */
	public static function createFromFile(File $file): ?self
	{
		$preparedFileResult = self::prepareFile($file);

		if (!$preparedFileResult->isSuccess())
		{
			Error::getInstance()->addFromResult($preparedFileResult);
			return null;
		}

		$res = self::add([
			'TITLE' => $file->getName(),
			'FILE_ID' => $file->getId()
		]);

		if ($res->isSuccess())
		{
			return self::getById($res->getId());
		}

		Error::getInstance()->addFromResult($res);
		return null;
	}

	public static function prepareFile(File $file): Result
	{
		$result = new Result();
		static $maxBlankFileSize = null;

		if ($maxBlankFileSize === null)
		{
			$maxBlankFileSize = $file->isImage()
				? Option::get('sign', 'max_upload_image_size_kb')
				: Option::get('sign', 'max_upload_doc_size_kb')
			;
		}

		if (!in_array($file->getExtension(), self::ALLOWED_EXTENSIONS, true))
		{
			$result->addError(new \Bitrix\Main\Error(
				Loc::getMessage('SIGN_CORE_BLANK_ERROR_NOT_ALLOWED_EXTENSIONS'),
				'NOT_ALLOWED_EXTENSIONS'
			));
			return $result;
		}

		// TODO get limit from safe, or rollback changes when registering document
		if ($file->getSize() / 1024 > $maxBlankFileSize)
		{
			$result->addError(new \Bitrix\Main\Error(
				Loc::getMessage('SIGN_CORE_BLANK_ERROR_FILE_TOO_BIG', [
					'#SIZE#' => $maxBlankFileSize
				]),
				'FILE_TOO_BIG'
			));
			return $result;
		}

		$file->save();
		$result->setData(['file'=>$file,]);

		return $result;
	}

	public static function getPublicList(int $limit = 10, int $offset = 0): array
	{
		if (!Loader::includeModule('crm'))
		{
			return [];
		}

		$blanks = [];

		$user = UserModel::createFromId(CurrentUser::get()->getId());
		$permission = (new RolePermissionService)->getValueForPermission(
			$user->getRoles(),
			SignPermissionDictionary::SIGN_TEMPLATES
		);
		$filter = [];
		$filter['=CREATED_BY_ID'] = null;

		if($permission === CCrmPerms::PERM_ALL || $user->isAdmin())
		{
			unset($filter['=CREATED_BY_ID']);
		}
		elseif ($permission === CCrmPerms::PERM_SUBDEPARTMENT)
		{
			unset($filter['=CREATED_BY_ID']);
			$filter['@CREATED_BY_ID'] = $user->getUserDepartmentMembers(true);
		}
		elseif ($permission === CCrmPerms::PERM_DEPARTMENT)
		{
			unset($filter['=CREATED_BY_ID']);
			$filter['@CREATED_BY_ID'] = $user->getUserDepartmentMembers();
		}
		elseif ($permission === CCrmPerms::PERM_SELF)
		{
			$filter['=CREATED_BY_ID'] = $user->getUserId();
		}

		$res = self::getList([
			'select' => [
				'*'
			],
			'order' => [
				'ID' => 'desc'
			],
			'limit' => $limit,
			'offset' => $offset,
			'filter' => $filter,
		]);
		while ($row = $res->fetch())
		{
			$blanks[] = $row;
		}

		return $blanks;
	}

}
