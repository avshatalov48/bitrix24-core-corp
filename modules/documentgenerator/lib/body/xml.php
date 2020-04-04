<?php

namespace Bitrix\DocumentGenerator\Body;

use Bitrix\DocumentGenerator\Body;

abstract class Xml extends Body
{
	/** @var \DOMDocument */
	protected $document;
	/** @var \DOMXPath */
	protected $xpath;

	/**
	 * @return array
	 */
	public function getPlaceholders()
	{
		$names = static::matchFieldNames($this->content);
		$names = array_unique($names);
		foreach($names as $key => $name)
		{
			if(substr($name, -(strlen(static::BLOCK_START_PLACEHOLDER) + 1)) == '.'.static::BLOCK_START_PLACEHOLDER)
			{
				unset($names[$key]);
			}
			if(substr($name, -(strlen(static::BLOCK_END_PLACEHOLDER) + 1)) == '.'.static::BLOCK_END_PLACEHOLDER)
			{
				unset($names[$key]);
			}
		}

		return $names;
	}

	/**
	 * @return string
	 */
	public function getFileExtension()
	{
		return 'xml';
	}

	/**
	 * @return string
	 */
	public function getFileMimeType()
	{
		return 'text/xml';
	}

	/**
	 * Construct $this->document and $this->xpath from actual content.
	 */
	protected function initDomDocument()
	{
		$this->document = new \DOMDocument();
		$this->document->loadXML($this->content);
		$this->xpath = new \DOMXPath($this->document);
		foreach($this->getNamespaces() as $prefix => $namespaceUri)
		{
			$this->xpath->registerNamespace($prefix, $namespaceUri);
		}
	}

	/**
	 * @return array
	 */
	protected function getNamespaces()
	{
		return [];
	}

	/**
	 * Returns array of unique nodes.
	 *
	 * @param array $nodes
	 * @return array
	 */
	protected function getUniqueNodes(array $nodes)
	{
		$result = $hashs = [];
		foreach($nodes as $node)
		{
			if(!is_object($node))
			{
				continue;
			}
			$hash = spl_object_hash($node);
			if(!isset($hashs[$hash]))
			{
				$hashs[$hash] = 1;
				$result[] = $node;
			}
		}

		return $result;
	}

	/**
	 * @return \DOMDocument
	 */
	public function getDomDocument()
	{
		return $this->document;
	}

	protected function saveContent()
	{
		$this->content = $this->document->saveXML();
	}
}