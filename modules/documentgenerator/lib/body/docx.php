<?php

namespace Bitrix\DocumentGenerator\Body;

use Bitrix\Main\Error;
use Bitrix\Main\IO\File;
use Bitrix\Main\Result;
use Bitrix\Main\Security\Random;

final class Docx extends ZipDocument
{
	const PATH_DOCUMENT = 'word/document.xml';
	const PATH_NUMBERING = 'word/numbering.xml';
	const PATH_CONTENT_TYPES = '[Content_Types].xml';

	const REL_TYPE_IMAGE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image';
	const REL_TYPE_FOOTER = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer';
	const REL_TYPE_HEADER = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/header';
	const REL_TYPE_NUMBERING = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering';

	const ABSTRACT_ORDERED_NUMBERING_ID = '553';
	const ABSTRACT_UNORDERED_NUMBERING_ID = '554';

	/** @var \DOMDocument */
	protected $contentTypesDocument;
	protected $innerDocuments = [];
	protected $numbering = [];

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
					$documentData = $documentResult->getData();
					$this->addContentToZip($document->getContent(), $path);
					$this->replaceImages($data['relationships'], $documentData['imageData']);
					$this->addNumberings($documentData['numberingIds']);
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
		// take only the first numbering.xml - we will add only
		if(isset($this->innerDocuments[static::PATH_DOCUMENT]['relationships']['data'][static::REL_TYPE_NUMBERING]))
		{
			foreach($this->innerDocuments[static::PATH_DOCUMENT]['relationships']['data'][static::REL_TYPE_NUMBERING] as $relationship)
			{
				$this->numbering['documentPath'] = 'word/'.$relationship['target'];
				break;
			}
		}
		$this->contentTypesDocument = new \DOMDocument();
		$this->contentTypesDocument->loadXML($this->zip->getFromName(static::PATH_CONTENT_TYPES));
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
		$relFilesToDelete = $nodesToDelete = [];
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
				if($originalImageID)
				{
					$relFilesToDelete[] = 'word/'.$relData[static::REL_TYPE_IMAGE][$originalImageID]['target'];
				}
				if($originalNode)
				{
					$nodesToDelete[] = $originalNode;
				}
			}
			elseif(isset($this->values[$placeholder]) && !empty($this->values[$placeholder]))
			{
				$image = $this->getImage($this->values[$placeholder]);
				if($image && $image->isExists() && $image->isReadable())
				{
					$originalImageID = $originalNode = false;
					foreach($data['innerIDs'] as $imageID)
					{
						$originalImageID = $data['originalId'][$imageID];
						if(!isset($relData[static::REL_TYPE_IMAGE][$originalImageID]))
						{
							continue;
						}
						$originalNode = $relData[static::REL_TYPE_IMAGE][$originalImageID]['node'];
						if(!$originalNode->parentNode)
						{
							continue;
						}
						$newNode = clone $originalNode;
						$document->importNode($newNode);
						$originalNode->parentNode->insertBefore($newNode, $originalNode);
						$this->importImage($image, $newNode, $imageID);
						$this->addContentToZip($document->saveXML(), $relationshipsData['path']);
						$this->excludedPlaceholders[] = $placeholder;
					}
					if($originalImageID)
					{
						$relFilesToDelete[] = 'word/'.$relData[static::REL_TYPE_IMAGE][$originalImageID]['target'];
					}
					if($originalNode)
					{
						$nodesToDelete[] = $originalNode;
					}
				}
			}
			else
			{
				foreach($data['innerIDs'] as $imageID)
				{
					$relFilesToDelete[] = 'word/'.$data[static::REL_TYPE_IMAGE][$imageID]['target'];
				}
			}
		}
		$nodesToDelete = $this->getUniqueObjects($nodesToDelete);
		foreach($nodesToDelete as $node)
		{
			$node->parentNode->removeChild($node);
		}
		foreach($relFilesToDelete as $path)
		{
			$this->zip->deleteName($path);
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
		$newName = Random::getString(15).'.'.$image->getExtension();
		$this->zip->addFile($image->getPhysicalPath(), 'word/media/'.$newName);
		$relationshipNode->removeAttribute('Target');
		$relationshipNode->setAttribute('Target', 'media/'.$newName);
		if(is_string($newId) && !empty($newId))
		{
			$relationshipNode->removeAttribute('Id');
			$relationshipNode->setAttribute('Id', $newId);
		}
	}

	/**
	 * Creates file word/numbering.xml if there was not one. Adds Relationship record.
	 * Creates abstract numberings with fixed ids if there were none.
	 * Binds particular ids from $numberingIds with appropriate abstract numberings by type.
	 *
	 * @param array $numberingIds
	 */
	protected function addNumberings(array $numberingIds)
	{
		if(empty($numberingIds))
		{
			return;
		}
		if(!$this->numbering['documentPath'])
		{
			/** @var \DOMDocument $relationshipsDocument */
			$relationshipsDocument = $this->innerDocuments[static::PATH_DOCUMENT]['relationships']['document'];
			if(!$relationshipsDocument)
			{
				return;
			}
			$relationshipsNode = $relationshipsDocument->getElementsByTagName('Relationships')->item(0);
			if(!$relationshipsNode)
			{
				return;
			}
			$this->numbering['documentPath'] = static::PATH_NUMBERING;
			$this->addContentToZip($this->getEmptyNumberingXmlContent(), $this->numbering['documentPath']);
			DocxXml::appendXmlToNode(
				'<Relationship Id="rnId11111" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering" Target="numbering.xml"/>',
				$relationshipsDocument,
				$relationshipsNode
			);
			$this->addContentToZip($relationshipsDocument->saveXML(), $this->innerDocuments[static::PATH_DOCUMENT]['relationships']['path']);
			if(!$this->contentTypesDocument)
			{
				return;
			}
			$typesNode = $this->contentTypesDocument->getElementsByTagName('Types')->item(0);
			if(!$typesNode)
			{
				return;
			}
			DocxXml::appendXmlToNode(
				'<Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/>',
				$this->contentTypesDocument,
				$typesNode
			);
			$this->addContentToZip($this->contentTypesDocument->saveXML(), static::PATH_CONTENT_TYPES);
		}

		if(!$this->numbering['document'])
		{
			$numberingDocument = new \DOMDocument();
			$numberingDocument->loadXML($this->zip->getFromName($this->numbering['documentPath']));
			$this->numbering['document'] = $numberingDocument;
		}

		/** @var \DOMDocument $numberingDocument */
		$numberingDocument = $this->numbering['document'];
		$numberingNode = null;
		$numberingNodes = $numberingDocument->getElementsByTagNameNS(DocxXml::getNamespaces()['w'], 'numbering');
		if($numberingNodes)
		{
			$numberingNode = $numberingNodes->item(0);
		}

		if(!$numberingNode)
		{
			return;
		}

		if(!isset($this->numbering['abstractOrderedNumberingId']) || !isset($this->numbering['abstractUnorderedNumberingId']))
		{
			foreach($numberingDocument->getElementsByTagNameNS(DocxXml::getNamespaces()['w'], 'abstractNum') as $abstractNum)
			{
				/** @var \DOMElement $abstractNum */
				$abstractNumId = $abstractNum->attributes->getNamedItemNS(DocxXml::getNamespaces()['w'], 'abstractNumId');
				if($abstractNumId === static::ABSTRACT_ORDERED_NUMBERING_ID)
				{
					$this->numbering['abstractOrderedNumberingId'] = $abstractNumId;
				}
				elseif($abstractNumId === static::ABSTRACT_UNORDERED_NUMBERING_ID)
				{
					$this->numbering['abstractUnorderedNumberingId'] = $abstractNumId;
				}
			}
		}

		if(!isset($this->numbering['firstConcreteNumNode']))
		{
			$numNodes = $numberingDocument->getElementsByTagNameNS(DocxXml::getNamespaces()['w'], 'num');
			if($numNodes)
			{
				$this->numbering['firstConcreteNumNode'] = $numNodes->item(0);
			}
		}

		if(!isset($this->numbering['abstractOrderedNumberingId']))
		{
			if($this->numbering['firstConcreteNumNode'] && $this->numbering['firstConcreteNumNode'] instanceof \DOMNode)
			{
				DocxXml::insertXmlBeforeNode($this->getAbstractOrderedNumberingDescription(), $numberingDocument, $this->numbering['firstConcreteNumNode']);
			}
			else
			{
				DocxXml::appendXmlToNode($this->getAbstractOrderedNumberingDescription(), $numberingDocument, $numberingNode);
			}
			$this->numbering['abstractOrderedNumberingId'] = static::ABSTRACT_ORDERED_NUMBERING_ID;
		}

		if(!isset($this->numbering['abstractUnorderedNumberingId']))
		{
			if($this->numbering['firstConcreteNumNode'] && $this->numbering['firstConcreteNumNode'] instanceof \DOMNode)
			{
				DocxXml::insertXmlBeforeNode($this->getAbstractUnorderedNumberingDescription(), $numberingDocument, $this->numbering['firstConcreteNumNode']);
			}
			else
			{
				DocxXml::appendXmlToNode($this->getAbstractUnorderedNumberingDescription(), $numberingDocument, $numberingNode);
			}
			$this->numbering['abstractUnorderedNumberingId'] = static::ABSTRACT_UNORDERED_NUMBERING_ID;
		}

		foreach($numberingIds as $numbering)
		{
			if($numbering['type'] === DocxXml::NUMBERING_TYPE_ORDERED)
			{
				DocxXml::appendXmlToNode(
					'<w:num w:numId="'.$numbering['id'].'"><w:abstractNumId w:val="'.$this->numbering['abstractOrderedNumberingId'].'" /></w:num>',
					$numberingDocument,
					$numberingNode
				);
			}
			elseif($numbering['type'] === DocxXml::NUMBERING_TYPE_UNORDERED)
			{
				DocxXml::appendXmlToNode(
					'<w:num w:numId="'.$numbering['id'].'"><w:abstractNumId w:val="'.$this->numbering['abstractUnorderedNumberingId'].'" /></w:num>',
					$numberingDocument,
					$numberingNode
				);
			}
		}

		$this->addContentToZip($numberingDocument->saveXML(), $this->numbering['documentPath']);
	}

	/**
	 * @return string
	 */
	protected function getEmptyNumberingXmlContent()
	{
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
			'<w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:v="urn:schemas-microsoft-com:vml">'.
		'</w:numbering>';
	}

	/**
	 * @param $abstractNumberId
	 * @return string
	 */
	protected function getAbstractOrderedNumberingDescription($abstractNumberId = null)
	{
		if(!$abstractNumberId)
		{
			$abstractNumberId = static::ABSTRACT_ORDERED_NUMBERING_ID;
		}

		return '<w:abstractNum w:abstractNumId="'.$abstractNumberId.'">'.
        '<w:lvl w:ilvl="0">'.
            '<w:start w:val="1" />'.
            '<w:numFmt w:val="decimal" />'.
            '<w:lvlText w:val="%1." />'.
            '<w:lvlJc w:val="left" />'.
            '<w:pPr>'.
                '<w:tabs>'.
                    '<w:tab w:val="num" w:pos="720" />'.
                '</w:tabs>'.
                '<w:ind w:left="720" w:hanging="360" />'.
            '</w:pPr>'.
        '</w:lvl>'.
        '</w:abstractNum>';
	}

	/**
	 * @param $abstractNumberId
	 * @return string
	 */
	protected function getAbstractUnorderedNumberingDescription($abstractNumberId = null)
	{
		if(!$abstractNumberId)
		{
			$abstractNumberId = static::ABSTRACT_UNORDERED_NUMBERING_ID;
		}

		return '<w:abstractNum w:abstractNumId="'.$abstractNumberId.'">'.
        '<w:lvl w:ilvl="0">'.
            '<w:start w:val="1" />'.
            '<w:numFmt w:val="bullet" />'.
            '<w:lvlText w:val="-" />'.
            '<w:lvlJc w:val="left" />'.
            '<w:pPr>'.
                '<w:tabs>'.
                    '<w:tab w:val="num" w:pos="720" />'.
                '</w:tabs>'.
                '<w:ind w:left="720" w:hanging="360" />'.
            '</w:pPr>'.
         '</w:lvl>'.
        '</w:abstractNum>';
	}
}