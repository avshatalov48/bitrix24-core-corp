<?php

namespace Bitrix\DocumentGenerator\Body;

use Bitrix\Main\Error;
use Bitrix\Main\IO\File;
use Bitrix\Main\Result;

final class Docx extends ZipDocument
{
	const PATH_DOCUMENT = 'word/document.xml';

	const REL_TYPE_IMAGE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image';
	const REL_TYPE_FOOTER = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer';
	const REL_TYPE_HEADER = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/header';

	protected $innerDocuments = [];

	/**
	 * @return string
	 */
	public function getFileExtension()
	{
		return 'docx';
	}

	/**
	 * @return string
	 */
	public function getFileMimeType()
	{
		return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
	}

	/**
	 * @return bool
	 */
	public function isFileProcessable()
	{
		if(parent::isFileProcessable())
		{
			return $this->zip->getFromName(static::PATH_DOCUMENT) !== false;
		}

		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function process()
	{
		$result = new Result();

		if($this->open() === true)
		{
			$this->fillInnerDocuments();
			foreach($this->innerDocuments as $path => $data)
			{
				/** @var DocxXml $document */
				$document = $data['document'];
				$documentResult = $document->process();
				if($documentResult->isSuccess())
				{
					$this->addContentToZip($document->getContent(), $path);
					$this->replaceImages($data['relationships'], $documentResult->getData()['imageData']);
				}
				else
				{
					$result->addErrors($documentResult->getErrors());
				}
			}
			$this->zip->close();
			$this->content = $this->file->getContents();
		}
		else
		{
			$result->addError(new Error('Cant open zip archive'));
		}

		return $result;
	}

	/**
	 * @inheritdoc
	 */
	public function getPlaceholders()
	{
		$placeholders = [];

		if($this->open() === true)
		{
			$this->fillInnerDocuments();
			foreach($this->innerDocuments as $path => $data)
			{
				$document = $data['document'];
				/** @var DocxXml $document */
				$placeholders += $document->getPlaceholders();
			}
		}

		return $placeholders;
	}

	/**
	 * Normalizes content of the document, removing unnecessary tags between {}
	 */
	public function normalizeContent()
	{
		if($this->open() === true)
		{
			$this->fillInnerDocuments();
			foreach($this->innerDocuments as $path => $data)
			{
				/** @var DocxXml $document */
				$document = $data['document'];
				$document->normalizeContent();
				$this->addContentToZip($document->getContent(), $path);
			}
			$this->zip->close();
			$this->content = $this->file->getContents();
		}
	}

	protected function fillInnerDocuments()
	{
		$this->innerDocuments[static::PATH_DOCUMENT] = [
			'relationships' => $this->parseRelationships(static::PATH_DOCUMENT),
			'document' => (new DocxXml($this->zip->getFromName(static::PATH_DOCUMENT)))->setValues($this->values)->setFields($this->fields),
		];
		if(isset($this->innerDocuments[static::PATH_DOCUMENT]['relationships']['data'][static::REL_TYPE_FOOTER]))
		{
			foreach($this->innerDocuments[static::PATH_DOCUMENT]['relationships']['data'][static::REL_TYPE_FOOTER] as $relationship)
			{
				$documentPath = 'word/'.$relationship['target'];
				$this->innerDocuments[$documentPath] = [
					'relationships' => $this->parseRelationships($documentPath),
					'document' => (new DocxXml($this->zip->getFromName($documentPath)))->setValues($this->values)->setFields($this->fields),
				];
			}
		}
		if(isset($this->innerDocuments[static::PATH_DOCUMENT]['relationships']['data'][static::REL_TYPE_HEADER]))
		{
			foreach($this->innerDocuments[static::PATH_DOCUMENT]['relationships']['data'][static::REL_TYPE_HEADER] as $relationship)
			{
				$documentPath = 'word/'.$relationship['target'];
				$this->innerDocuments[$documentPath] = [
					'relationships' => $this->parseRelationships($documentPath),
					'document' => (new DocxXml($this->zip->getFromName($documentPath)))->setValues($this->values)->setFields($this->fields),
				];
			}
		}
	}

	/**
	 * @param string $documentPath
	 * @return string
	 */
	protected function getRelationshipPath($documentPath)
	{
		$documentPath = substr($documentPath, 5);
		return 'word/_rels/'.$documentPath.'.rels';
	}

	/**
	 * Parses relationships file on $path and returns data.
	 * @param string $documentPath
	 * @return array
	 */
	protected function parseRelationships($documentPath)
	{
		$relationshipPath = $this->getRelationshipPath($documentPath);
		$relationshipsContent = $this->zip->getFromName($relationshipPath);
		$relationshipsData = [];
		$relationshipsDocument = new \DOMDocument();
		if(!empty($relationshipsContent))
		{
			$relationshipsDocument->loadXML($relationshipsContent);
			foreach($relationshipsDocument->getElementsByTagName('Relationship') as $relationship)
			{
				$id = $relationship->attributes->getNamedItem('Id');
				if($id)
				{
					$id = $id->value;
				}
				$target = $relationship->attributes->getNamedItem('Target');
				if($target)
				{
					$target = $target->value;
				}
				$type = $relationship->attributes->getNamedItem('Type');
				if($type)
				{
					$type = $type->value;
				}
				if($id && $target && $type)
				{
					$relationshipsData[$type][$id] = [
						'type' => $type,
						'id' => $id,
						'target' => $target,
						'node' => $relationship,
					];
				}
			}
		}

		$result = [
			'data' => $relationshipsData,
			'document' => $relationshipsDocument,
			'path' => $relationshipPath,
		];

		return $result;
	}

	/**
	 * @param array $relationshipsData
	 * @param array $imageData
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	protected function replaceImages(array $relationshipsData, array $imageData = [])
	{
		$relData = $relationshipsData['data'];
		/** @var \DOMDocument $document */
		$document = $relationshipsData['document'];
		foreach($imageData as $placeholder => $data)
		{
			if(!isset($data['innerIDs']))
			{
				continue;
			}
			if(isset($data['values']) && is_array($data['values']))
			{
				// these are new images created from array values
				// copy original relationship data, fill data
				// delete original node
				$originalImageID = $originalNode = false;
				foreach($data['values'] as $imageID => $path)
				{
					$originalImageID = $data['originalId'][$imageID];
					if(!isset($relData[static::REL_TYPE_IMAGE][$originalImageID]))
					{
						continue;
					}
					/** @var \DOMElement $originalNode */
					$originalNode = $relData[static::REL_TYPE_IMAGE][$originalImageID]['node'];
					$image = $this->getImage($path);
					if($image && $image->isExists() && $image->isReadable() && $originalNode->parentNode)
					{
						$newNode = clone $originalNode;
						$document->importNode($newNode);
						$originalNode->parentNode->insertBefore($newNode, $originalNode);
						$this->importImage($image, $newNode, $imageID);
						$this->addContentToZip($document->saveXML(), $relationshipsData['path']);
						$this->excludedPlaceholders[] = $placeholder;
					}
				}
				if($originalNode)
				{
					$path = 'word/'.$relData[static::REL_TYPE_IMAGE][$originalImageID]['target'];
					$this->zip->deleteName($path);
					$originalNode->parentNode->removeChild($originalNode);
				}
			}
			elseif(isset($this->values[$placeholder]) && !empty($this->values[$placeholder]))
			{
				$image = $this->getImage($this->values[$placeholder]);
				if($image && $image->isExists() && $image->isReadable())
				{
					foreach($data['innerIDs'] as $imageID)
					{
						if(!isset($relData[static::REL_TYPE_IMAGE][$imageID]))
						{
							continue;
						}
						// replace image
						$path = 'word/'.$relData[static::REL_TYPE_IMAGE][$imageID]['target'];
						$this->zip->deleteName($path);
						$this->importImage($image, $relData[static::REL_TYPE_IMAGE][$imageID]['node']);
						$this->addContentToZip($document->saveXML(), $relationshipsData['path']);
						$this->excludedPlaceholders[] = $placeholder;
					}
				}
			}
			else
			{
				foreach($data['innerIDs'] as $imageID)
				{
					$path = 'word/'.$data[static::REL_TYPE_IMAGE][$imageID]['target'];
					$this->zip->deleteName($path);
				}
			}
		}
	}

	/**
	 * @param string $path
	 * @return File|false
	 */
	protected function getImage($path)
	{
		if(!is_string($path) || empty($path))
		{
			return false;
		}
		$localPath = false;
		$fileArray = \CFile::MakeFileArray($path);
		if($fileArray && $fileArray['tmp_name'])
		{
			$localPath = \CBXVirtualIo::getInstance()->getLogicalName($fileArray['tmp_name']);
		}
		if($localPath)
		{
			return new File($localPath);
		}

		return false;
	}

	/**
	 * @param File $image
	 * @param \DOMElement $relationshipNode
	 * @param string $newId
	 */
	protected function importImage(File $image, \DOMElement $relationshipNode, $newId = '')
	{
		$newName = $image->getName();
		$this->zip->addFile($image->getPhysicalPath(), 'word/media/'.$newName);
		$relationshipNode->removeAttribute('Target');
		$relationshipNode->setAttribute('Target', 'media/'.$newName);
		if(is_string($newId) && !empty($newId))
		{
			$relationshipNode->removeAttribute('Id');
			$relationshipNode->setAttribute('Id', $newId);
		}
	}
}