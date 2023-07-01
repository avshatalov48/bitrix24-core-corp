<?php

namespace Bitrix\DocumentGenerator\Body;

use Bitrix\DocumentGenerator\Body;
use Bitrix\Main\Application;

abstract class Xml extends Body
{
	/** @var \DOMDocument */
	protected $document;
	/** @var \DOMXPath */
	protected $xpath;

	public const DOCUMENT_NODE_NAME = 'document';
	public const BODY_NODE_NAME = 'body';

	/**
	 * @return array
	 */
	public function getPlaceholders()
	{
		$names = static::matchFieldNames($this->content);
		$names = array_unique($names);
		foreach($names as $key => $name)
		{
			if(
				mb_strpos($name, static::BLOCK_START_PLACEHOLDER)
				|| mb_strpos($name, static::BLOCK_END_PLACEHOLDER)
			)
			{
				unset($names[$key]);
			}
		}

		return $names;
	}

	/**
	 * Finds all nodes that contains {$placeholder} text.
	 *
	 * @param $placeholder
	 * @param \DOMXPath|null $xpath
	 * @return array
	 */
	protected function findPlaceholderNodes(string $placeholder, \DOMXPath $xpath = null): array
	{
		if(!$xpath)
		{
			$xpath = $this->xpath;
		}

		$result = [];
		$nodes = $xpath->query('//w:t[text()[contains(.,"{'.$placeholder.'")]]');
		foreach($nodes as $node)
		{
			/** @var \DOMElement $node */
			if(preg_match(static::$valuesPattern, $node->nodeValue))
			{
				$result[] = $node;
			}
		}

		return $result;
	}

	/**
	 * Finds first node that contains {$placeholder} text.
	 *
	 * @param $placeholder
	 * @param \DOMXPath|null $xpath
	 * @return bool|\DOMElement
	 */
	protected function findPlaceholderNode(string $placeholder, \DOMXPath $xpath = null): ?\DOMElement
	{
		$nodes = $this->findPlaceholderNodes($placeholder, $xpath);
		if(count($nodes) > 0)
		{
			return $nodes[0];
		}

		return null;
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
	 * @return string
	 */
	public static function getMainPrefix()
	{
		return '';
	}

	public function isFileProcessable()
	{
		return !empty($this->content);
	}

	/**
	 * Construct $this->document and $this->xpath from actual content.
	 */
	protected function initDomDocument()
	{
		$this->document = new \DOMDocument();
		try
		{
			$this->document->loadXML($this->content);
		}
		catch (\ValueError $emptyArgumentError)
		{
			Application::getInstance()->getExceptionHandler()->writeToLog($emptyArgumentError);
		}
		$this->xpath = new \DOMXPath($this->document);
		foreach(static::getNamespaces() as $prefix => $namespaceUri)
		{
			$this->xpath->registerNamespace($prefix, $namespaceUri);
		}
	}

	/**
	 * @return array
	 */
	public static function getNamespaces()
	{
		return [];
	}

	/**
	 * @return \DOMDocument
	 */
	public function getDomDocument()
	{
		return $this->document;
	}

	public function getXPath(): \DOMXPath
	{
		return $this->xpath;
	}

	protected function saveContent()
	{
		$this->content = $this->document->saveXML();
	}

	/**
	 * @return string
	 */
	abstract protected function getBreakLineTag();

	/**
	 * @param string $text
	 * @param bool $saveBreakLines
	 * @return string
	 */
	protected function prepareTextValue(string $text, bool $saveBreakLines = true): string
	{
		if($saveBreakLines)
		{
			$text = str_replace(PHP_EOL, '{__SystemBreakLine}', $text);
		}
		$text = html_entity_decode($text);
		$text = strtr(
			$text,
			[
				'<' => '&lt;',
				'>' => '&gt;',
				'"' => '&quot;',
				'&' => '&amp;',
			]
		);
		$text = preg_replace('/[\x01-\x08\x0B-\x0C\x0E-\x1F]/', '', $text);
		if($saveBreakLines)
		{
			$text = str_replace('{__SystemBreakLine}', $this->getBreakLineTag(), $text);
		}

		return $text;
	}

	/**
	 * @param string $prefix
	 * @param bool $insert
	 * @return string
	 */
	public static function getRandomId(string $prefix = '', bool $insert = false): string
	{
		static $randomIds = [];

		do
		{
			$number = random_int(200, 10000);
			$id = $prefix.$number;
			if(!isset($randomIds[$id]))
			{
				$randomIds[$id] = true;
				break;
			}
		}
		while(true);

		return $insert ? $id : $number;
	}

	/**
	 * @param string $xml
	 * @param \DOMDocument $document
	 * @param \DOMNode $node
	 */
	public static function appendXmlToNode(string $xml, \DOMDocument $document, \DOMNode $node)
	{
		$xml = static::getValidXmlWithContent($xml);
		$temporaryDocument = new \DOMDocument();
		try
		{
			$temporaryDocument->loadXML($xml);
		}
		catch (\ValueError $emptyArgumentError)
		{
			Application::getInstance()->getExceptionHandler()->writeToLog($emptyArgumentError);
		}
		$nodes = static::getDocumentContentNodes($temporaryDocument);
		foreach($nodes as $childNode)
		{
			$childNode = $document->importNode($childNode, true);
			$node->appendChild($childNode);
		}
	}

	/**
	 * @param string $xml
	 * @param \DOMDocument $document
	 * @param \DOMNode $node
	 */
	public static function insertXmlBeforeNode(string $xml, \DOMDocument $document, \DOMNode $node)
	{
		$xml = static::getValidXmlWithContent($xml);
		$temporaryDocument = new \DOMDocument();
		try
		{
			$temporaryDocument->loadXML($xml);
		}
		catch (\ValueError $emptyArgumentError)
		{
			Application::getInstance()->getExceptionHandler()->writeToLog($emptyArgumentError);
		}
		$nodes = static::getDocumentContentNodes($temporaryDocument);
		$refNode = null;
		if($node->parentNode)
		{
			$nodeToLoad = $node->parentNode;
			$refNode = $node;
		}
		else
		{
			$nodeToLoad = $node;
		}
		foreach($nodes as $childNode)
		{
			$childNode = $document->importNode($childNode, true);
			$nodeToLoad->insertBefore($childNode, $refNode);
		}
	}

	/**
	 * @param \DOMDocument $document
	 * @return \DOMNodeList
	 */
	public static function getDocumentContentNodes(\DOMDocument $document, string $prefix = '')
	{
		$bodyNodeName = static::getBodyNodeName($prefix);
		$node = $document;
		do
		{
			$node = $node->firstChild;
		}
		while($node->firstChild && $node->nodeName !== $bodyNodeName);

		if(!$node->firstChild)
		{
			return $node->parentNode->childNodes;
		}

		return $node->childNodes;
	}

	/**
	 * @param string $content
	 * @param string $mainPrefix
	 * @param array $namespaces
	 * @return string
	 */
	public static function getValidXmlWithContent(
		string $content = '',
		string $mainPrefix = '',
		array $namespaces = []
	): string
	{
		$documentNodeName = static::getDocumentNodeName($mainPrefix);
		$bodyNodeName = static::getBodyNodeName($mainPrefix);
		$namespaces = array_merge(static::getNamespaces(), $namespaces);
		if(mb_strpos($content, '<'.$documentNodeName) !== false)
		{
			// todo add attributes with namespaces to document node
			return $content;
		}

		$result = '<?xml version="1.0"?>' . PHP_EOL . '<'.$documentNodeName;

		foreach($namespaces as $prefix => $uri)
		{
			$result .= ' xmlns:'.$prefix.'="'.$uri.'"';
		}
		$result .= '>';
		$result .= '<'.$bodyNodeName.'>';
		$result .= $content;
		$result .= '</'.$bodyNodeName.'>';
		$result .= '</'.$documentNodeName.'>';

		return $result;
	}

	/**
	 * @param string $mainPrefix
	 * @return string
	 */
	public static function getDocumentNodeName(string $mainPrefix = ''): string
	{
		$documentNodeName = static::DOCUMENT_NODE_NAME;
		if(empty($mainPrefix))
		{
			$mainPrefix = static::getMainPrefix();
		}
		if(!empty($mainPrefix))
		{
			$documentNodeName = $mainPrefix.':'.$documentNodeName;
		}

		return $documentNodeName;
	}

	/**
	 * @param string $mainPrefix
	 * @return string
	 */
	public static function getBodyNodeName(string $mainPrefix = ''): string
	{
		$bodyNodeName = static::BODY_NODE_NAME;
		if(empty($mainPrefix))
		{
			$mainPrefix = static::getMainPrefix();
		}
		if(!empty($mainPrefix))
		{
			$bodyNodeName = $mainPrefix.':'.$bodyNodeName;
		}

		return $bodyNodeName;
	}
}
