<?php

namespace Bitrix\DocumentGenerator\Body;

use Bitrix\DocumentGenerator\Value;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\IO\File;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Security\Random;

/**
 * @todo remove all try catch around loadXml when there are no new errors in log
 */
class Docx extends ZipDocument
{
	protected const PATH_DOCUMENT = 'word/document.xml';
	protected const PATH_NUMBERING = 'word/numbering.xml';
	protected const PATH_CONTENT_TYPES = '[Content_Types].xml';

	protected const REL_TYPE_IMAGE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image';
	protected const REL_TYPE_FOOTER = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer';
	protected const REL_TYPE_HEADER = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/header';
	protected const REL_TYPE_NUMBERING = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering';

	public const ABSTRACT_ORDERED_NUMBERING_ID = '553';
	public const ABSTRACT_UNORDERED_NUMBERING_ID = '554';

	/** @var \DOMDocument */
	protected $contentTypesDocument;
	protected $innerDocuments = [];
	protected $numbering = [];

	/**
	 * @return string
	 */
	public function getFileExtension(): string
	{
		return 'docx';
	}

	/**
	 * @return string
	 */
	public function getFileMimeType(): string
	{
		return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
	}

	/**
	 * @return bool
	 */
	public function isFileProcessable(): bool
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
	public function process(): Result
	{
		$result = new Result();

		if (!$this->isFileProcessable())
		{
			return $result->addError(
				new Error(Loc::getMessage('DOCGEN_BODY_DOCX_ERROR_FILE_IS_NOT_PROCESSABLE'), 'FILE_NOT_PROCESSABLE')
			);
		}

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
	public function getPlaceholders(): array
	{
		$placeholders = [];

		if($this->isFileProcessable() && $this->open() === true)
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
	public function normalizeContent(): void
	{
		if($this->isFileProcessable() && $this->open() === true)
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

	/**
	 * @return DocxXml
	 */
	protected function getXmlClassName(): string
	{
		return DocxXml::class;
	}

	protected function fillInnerDocuments(): void
	{
		$xmlClassName = $this->getXmlClassName();
		$this->innerDocuments[static::PATH_DOCUMENT] = [
			'relationships' => $this->parseRelationships(static::PATH_DOCUMENT),
			'document' => (new $xmlClassName($this->zip->getFromName(static::PATH_DOCUMENT)))->setValues($this->values)->setFields($this->fields),
		];
		if(isset($this->innerDocuments[static::PATH_DOCUMENT]['relationships']['data'][static::REL_TYPE_FOOTER]))
		{
			foreach($this->innerDocuments[static::PATH_DOCUMENT]['relationships']['data'][static::REL_TYPE_FOOTER] as $relationship)
			{
				$documentPath = 'word/'.$relationship['target'];
				$this->innerDocuments[$documentPath] = [
					'relationships' => $this->parseRelationships($documentPath),
					'document' => (new $xmlClassName($this->zip->getFromName($documentPath)))->setValues($this->values)->setFields($this->fields),
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
					'document' => (new $xmlClassName($this->zip->getFromName($documentPath)))->setValues($this->values)->setFields($this->fields),
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
		try
		{
			$this->contentTypesDocument->loadXML($this->zip->getFromName(static::PATH_CONTENT_TYPES));
		}
		catch (\ValueError $emptyArgumentError)
		{
			Application::getInstance()->getExceptionHandler()->writeToLog($emptyArgumentError);
		}
	}

	/**
	 * @param string $documentPath
	 * @return string
	 */
	protected function getRelationshipPath(string $documentPath): string
	{
		$documentPath = mb_substr($documentPath, 5);

		return 'word/_rels/'.$documentPath.'.rels';
	}

	/**
	 * Parses relationships file on $path and returns data.
	 * @param string $documentPath
	 * @return array
	 */
	protected function parseRelationships(string $documentPath): array
	{
		$relationshipPath = $this->getRelationshipPath($documentPath);
		$relationshipsContent = $this->zip->getFromName($relationshipPath);
		$relationshipsData = [];
		$relationshipsDocument = new \DOMDocument();
		if(!empty($relationshipsContent))
		{
			try
			{
				$relationshipsDocument->loadXML($relationshipsContent);
			}
			catch (\ValueError $emptyArgumentError)
			{
				Application::getInstance()->getExceptionHandler()->writeToLog($emptyArgumentError);
			}
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

		return [
			'data' => $relationshipsData,
			'document' => $relationshipsDocument,
			'path' => $relationshipPath,
		];
	}

	/**
	 * @param array $relationshipsData
	 * @param array $imageData
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	protected function replaceImages(array $relationshipsData, array $imageData = []): void
	{
		$isDocumentChanged = false;
		$relData = $relationshipsData['data'];
		/** @var \DOMDocument $document */
		$document = $relationshipsData['document'];
		$relFilesToDelete = $nodesToDelete = [];
		foreach ($imageData as $fieldName => $data)
		{
			$isDeleteImages = true;
			if (!isset($data['innerIDs']))
			{
				continue;
			}
			if (isset($data['values']) && is_array($data['values']))
			{
				$isDeleteImages = false;
				// these are new images created from array values
				// copy original relationship data, fill data
				// delete original node
				$originalImageID = $originalNode = false;
				foreach ($data['values'] as $imageID => $path)
				{
					$originalImageID = $data['originalId'][$imageID];
					if (!isset($relData[static::REL_TYPE_IMAGE][$originalImageID]))
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
						$isDocumentChanged = true;
						$this->excludedPlaceholders[] = $fieldName;
					}
				}
				if ($originalImageID)
				{
					$relFilesToDelete[] = 'word/'.$relData[static::REL_TYPE_IMAGE][$originalImageID]['target'];
				}
				if ($originalNode)
				{
					$nodesToDelete[] = $originalNode;
				}
			}
			if (isset($this->values[$fieldName]) && !empty(trim($this->values[$fieldName])))
			{
				$isDeleteImages = false;
				$image = $this->getImage($this->values[$fieldName]);
				if (!$image && $this->isArrayValue($this->values[$fieldName], $fieldName))
				{
					$placeholder = $data['placeholder'] ?? '';
					$modifier = static::getModifierFromPlaceholder($placeholder);
					$modifierData = Value::parseModifier($modifier);
					$index = (int) $modifierData[static::ARRAY_INDEX_MODIFIER];
					$value = $this->values[$fieldName];
					$valueNameParts = explode('.', $value);
					$name = implode('.', array_slice($valueNameParts, 2));
					$arrayProvider = $this->values[$valueNameParts[0]];
					$image = $this->getImage($this->printArrayValueByIndex($arrayProvider, $fieldName, $name, $index, $modifier));
				}
				if ($image && $image->isExists() && $image->isReadable())
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
						$isDocumentChanged = true;
						$this->excludedPlaceholders[] = $fieldName;
					}
					if ($originalImageID)
					{
						$relFilesToDelete[] = 'word/'.$relData[static::REL_TYPE_IMAGE][$originalImageID]['target'];
					}
					if ($originalNode)
					{
						$nodesToDelete[] = $originalNode;
					}
				}
			}
			if ($isDeleteImages)
			{
				foreach($data['innerIDs'] as $imageID)
				{
					$relFilesToDelete[] = 'word/'.$data[static::REL_TYPE_IMAGE][$imageID]['target'];
				}
				$originalImageID = $data['originalId'][$imageID];
				if(isset($relData[static::REL_TYPE_IMAGE][$originalImageID]))
				{
					$nodesToDelete[] = $relData[static::REL_TYPE_IMAGE][$originalImageID]['node'];
				}
			}
		}
		$nodesToDelete = $this->getUniqueObjects($nodesToDelete);
		foreach($nodesToDelete as $node)
		{
			$node->parentNode->removeChild($node);
			$isDocumentChanged = true;
		}
		if($isDocumentChanged)
		{
			$this->addContentToZip($document->saveXML(), $relationshipsData['path']);
		}
		foreach($relFilesToDelete as $path)
		{
			$this->zip->deleteName($path);
		}
	}

	/**
	 * @param string $path
	 * @return File|null
	 */
	protected function getImage($path): ?File
	{
		if(!is_string($path) || empty($path))
		{
			return null;
		}
		$localPath = false;
		$fileArray = \CFile::MakeFileArray($path);
		if($fileArray && $fileArray['tmp_name'])
		{
			$localPath = \CBXVirtualIo::getInstance()->getLogicalName($fileArray['tmp_name']);
		}
		if($localPath)
		{
			$file = new File($localPath);
			if($this->getMimeType($file))
			{
				return $file;
			}
		}

		return null;
	}

	/**
	 * @param File $image
	 * @param \DOMElement $relationshipNode
	 * @param string $newId
	 */
	protected function importImage(File $image, \DOMElement $relationshipNode, string $newId = ''): void
	{
		$mimeType = $this->getMimeType($image);
		$extension = $image->getExtension() ?: $this->getPrintableMimeTypes()[$mimeType] ?? '';
		$newName = Random::getString(15).'.'.$extension;
		$this->zip->addFile($image->getPhysicalPath(), 'word/media/'.$newName);
		$relationshipNode->removeAttribute('Target');
		$relationshipNode->setAttribute('Target', 'media/'.$newName);
		if(is_string($newId) && !empty($newId))
		{
			$relationshipNode->removeAttribute('Id');
			$relationshipNode->setAttribute('Id', $newId);
		}

		$this->addRecordToContentTypes([
			'path' => '/word/media/' . $newName,
			'type' => $mimeType,
		]);
	}

	protected function getPrintableMimeTypes(): array
	{
		return [
			'image/jpeg' => 'jpeg',
			'image/png'  => 'png',
			'image/bmp'  => 'bmp',
			'image/gif'  => 'gif',
			'application/pdf' => 'pdf',
		];
	}

	protected function getMimeType(File $file): ?string
	{
		$types = $this->getPrintableMimeTypes();

		$mimeType = $file->getContentType();
		if(isset($types[$mimeType]))
		{
			return $mimeType;
		}

		$extension = $file->getExtension();
		if(!empty($extension))
		{
			$types = array_flip($types);
			if(isset($types[$extension]))
			{
				return $types[$extension];
			}
		}

		return null;
	}

	/**
	 * Creates file word/numbering.xml if there was not one. Adds Relationship record.
	 * Creates abstract numberings with fixed ids if there were none.
	 * Binds particular ids from $numberingIds with appropriate abstract numberings by type.
	 *
	 * @param array $numberingIds
	 */
	protected function addNumberings(array $numberingIds): void
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
			$this->addRecordToContentTypes([
				'path' => '/word/numbering.xml',
				'type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml',
			]);
		}

		if(!$this->numbering['document'])
		{
			$numberingContent = $this->zip->getFromName($this->numbering['documentPath']);
			if (empty($numberingContent))
			{
				return;
			}

			$numberingDocument = new \DOMDocument();
			try
			{
				$numberingDocument->loadXML($numberingContent);
			}
			catch (\ValueError $emptyArgumentError)
			{
				Application::getInstance()->getExceptionHandler()->writeToLog($emptyArgumentError);
			}
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
	protected function getEmptyNumberingXmlContent(): string
	{
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
			'<w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:v="urn:schemas-microsoft-com:vml">'.
		'</w:numbering>';
	}

	/**
	 * @param $abstractNumberId
	 * @return string
	 */
	protected function getAbstractOrderedNumberingDescription(string $abstractNumberId = null): string
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
	protected function getAbstractUnorderedNumberingDescription(string $abstractNumberId = null): string
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

	protected function addRecordToContentTypes(array $attributes): void
	{
		if($this->contentTypesDocument)
		{
			$typesNode = $this->contentTypesDocument->getElementsByTagName('Types')->item(0);
			if(!$typesNode)
			{
				return;
			}
			DocxXml::appendXmlToNode(
				'<Override PartName="' . $attributes['path'] . '" ContentType="' . $attributes['type'] . '"/>',
				$this->contentTypesDocument,
				$typesNode
			);
			$this->addContentToZip($this->contentTypesDocument->saveXML(), static::PATH_CONTENT_TYPES);
		}
	}
}
