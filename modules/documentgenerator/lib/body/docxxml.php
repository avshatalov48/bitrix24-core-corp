<?php

namespace Bitrix\DocumentGenerator\Body;

use Bitrix\DocumentGenerator\DataProvider;
use Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider;
use Bitrix\Main\Result;
use Bitrix\Main\Text\Encoding;

final class DocxXml extends Xml
{
	const EMPTY_IMAGE_PLACEHOLDER = '{__SystemEmptyImage}';

	const HTML_NODE_DISPLAY_HIDDEN = 'hidden';
	const HTML_NODE_DISPLAY_BLOCK = 'block';
	const HTML_NODE_DISPLAY_INLINE = 'inline';

	const HTML_NODE_FONT_NORMAL = 'normal';
	const HTML_NODE_FONT_BOLD = 'bold';
	const HTML_NODE_FONT_ITALIC = 'italic';
	const HTML_NODE_FONT_UNDERLINED = 'underlined';
	const HTML_NODE_FONT_DELETED = 'deleted';

	protected $arrayImageValues = [];

	/**
	 * Parse $content, process commands, fill values.
	 * Returns true on success, false on failure.
	 *
	 * @return Result
	 */
	public function process()
	{
		$result = new Result();

		$this->processArrays();
		$this->clearRowsWithoutValues();
		$imageData = $this->processImages();
		$result->setData(['imageData' => $imageData]);
		$this->content = $this->replacePlaceholders($this->content);

		return $result;
	}

	/**
	 * @return array
	 */
	protected function getNamespaces()
	{
		return array_merge(parent::getNamespaces(), [
			'w' => 'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
			'wp' => 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing',
			'a' => 'http://schemas.openxmlformats.org/drawingml/2006/main',
		]);
	}

	/**
	 * Normalizes content of the document, removing unnecessary tags between {}
	 */
	public function normalizeContent()
	{
		$this->initDomDocument();
		$bracketNodes = [];
		$nodes = $this->xpath->query('//w:t[text()[contains(.,"{")]]');
		foreach($nodes as $node)
		{
			$bracketNodes[] = $this->getParentParagraphNode($node, 4);
		}
		$nodes = $this->xpath->query('//w:t[text()[contains(.,"}")]]');
		foreach($nodes as $node)
		{
			$bracketNodes[] = $this->getParentParagraphNode($node, 4);
		}
		$bracketNodes = $this->getUniqueNodes($bracketNodes);
		foreach($bracketNodes as $bracketNode)
		{
			/** @var \DOMElement $bracketNode */
			$rowNodes = $bracketNode->getElementsByTagNameNS($this->getNamespaces()['w'], 'r');
			if($rowNodes)
			{
				$this->normalizeNodeList($rowNodes, $bracketNode);
			}
		}
		$this->saveContent();
		$this->clearPlaceholdersInAttributes();
	}

	/**
	 * Clears all placeholders that inside attributes and are not images
	 */
	protected function clearPlaceholdersInAttributes()
	{
		$placeholdersToClear = [];
		$imagePlaceholders = $this->findImages();
		$allPlaceholders = $this->getPlaceholders();
		foreach($allPlaceholders as $placeholder)
		{
			$node = $this->findPlaceholderNode($placeholder);
			if(!$node && !isset($imagePlaceholders[$placeholder]))
			{
				$placeholdersToClear[$placeholder] = $placeholder;
			}
		}

		if(!empty($placeholdersToClear))
		{
			$this->content = preg_replace_callback(static::$valuesPattern, function($matches) use ($placeholdersToClear)
			{
				if($matches[2] && isset($placeholdersToClear[$matches[2]]))
				{
					return '';
				}

				return $matches[0];
			}, $this->content);
		}
	}

	/**
	 * Walk through $nodeList, finds start node and text with all placeholdersþ
	 *
	 * @param \DOMNodeList $nodeList
	 * @param \DOMElement|null $parentNode
	 */
	protected function normalizeNodeList(\DOMNodeList $nodeList, \DOMElement $parentNode)
	{
		$deleteNodes = [];
		$startNode = $endNode = false;
		$text = '';
		foreach($nodeList as $node)
		{
			$startNodeFound = false;
			/** @var \DOMElement $node */
			if(!$startNode && strpos($node->textContent, '{') !== false)
			{
				$startNode = $node;
				$startNodeFound = true;
			}
			if($startNode && !$endNode)
			{
				$text .= $node->textContent;
				if(!$startNodeFound)
				{
					$deleteNodes[] = $node;
				}
				$lastClosedBracketPosition = strrpos($text, '}');
				$lastOpenBracketPosition = strrpos($text, '{');
				if($lastClosedBracketPosition === false ||
					(
						$lastOpenBracketPosition !== false &&
						$lastOpenBracketPosition > $lastClosedBracketPosition)
				)
				{
					continue;
				}
			}
			$closedBracketsFound = substr_count($node->textContent, '}');
			if($startNode && !$endNode && $closedBracketsFound > 0)
			{
				$endNode = $node;
			}
			if($startNode && $endNode)
			{
				$this->refillTextNode($startNode, $text);
				if($parentNode)
				{
					$parentNode->normalize();
				}
				$startNode = $endNode = false;
				$text = '';
			}
		}
		foreach($deleteNodes as $deleteNode)
		{
			/** @var \DOMElement $deleteNode */
			if($deleteNode->parentNode)
			{
				$deleteNode->parentNode->removeChild($deleteNode);
			}
		}
	}

	/**
	 * Change text nodes content
	 *
	 * @param \DOMElement $rowNode
	 * @param $textContent
	 */
	protected function refillTextNode(\DOMElement $rowNode, $textContent)
	{
		$textNodes = $rowNode->getElementsByTagNameNS($this->getNamespaces()['w'], 't');
		if($textNodes->length == 0)
		{
			return;
		}
		if($textNodes->length == 1)
		{
			$node = $textNodes->item(0);
			$node->nodeValue = $textContent;
			return;
		}
		$deleteNodes = [];
		$startNode = false;
		foreach($textNodes as $node)
		{
			$startNodeFound = false;
			if(!$startNode && strpos($node->textContent, '{') !== false)
			{
				$startNode = $node;
				$startNodeFound = true;
			}
			if(!$startNodeFound)
			{
				$deleteNodes[] = $node;
			}
		}
		if($startNode)
		{
			$startNode->nodeValue = $textContent;
		}
		foreach($deleteNodes as $deleteNode)
		{
			/** @var \DOMElement $deleteNode */
			if($deleteNode->parentNode)
			{
				$deleteNode->parentNode->removeChild($deleteNode);
			}
		}
	}

	/**
	 * Finds first node that contains {$placeholder} text.
	 *
	 * @param $placeholder
	 * @param \DOMXPath|null $xpath
	 * @return bool|\DOMElement
	 */
	protected function findPlaceholderNode($placeholder, \DOMXPath $xpath = null)
	{
		$nodes = $this->findPlaceholderNodes($placeholder, $xpath);
		if($nodes->length > 0)
		{
			return $nodes->item(0);
		}

		return false;
	}

	/**
	 * Finds all nodes that contains {$placeholder} text.
	 *
	 * @param $placeholder
	 * @param \DOMXPath|null $xpath
	 * @return \DOMNodeList
	 */
	protected function findPlaceholderNodes($placeholder, \DOMXPath $xpath = null)
	{
		if(!$xpath)
		{
			$xpath = $this->xpath;
		}
		return $xpath->query('//w:t[text()[contains(.,"{'.$placeholder.'")]]');
	}

	/**
	 * Finds first parent paragraph (<w:p>) node.
	 *
	 * @param \DOMNode $node
	 * @param int $maxLevels
	 * @return bool|\DOMNode
	 */
	protected function getParentParagraphNode(\DOMNode $node, $maxLevels = 10)
	{
		return $this->getParentNodeType($node, ['w:p'], $maxLevels);
	}

	/**
	 * Finds first table row (<w:tr>) node.
	 *
	 * @param \DOMNode $node
	 * @param int $maxLevels
	 * @return bool|\DOMNode
	 */
	protected function getParentTableRowNode(\DOMNode $node, $maxLevels = 10)
	{
		return $this->getParentNodeType($node, ['w:tr'], $maxLevels);
	}

	/**
	 * Finds first parent with nodeName from $nodeNames list.
	 *
	 * @param \DOMNode $node
	 * @param array $nodeNames
	 * @param int $maxLevels
	 * @return bool|\DOMNode
	 */
	protected function getParentNodeType(\DOMNode $node, array $nodeNames, $maxLevels = 10)
	{
		while($maxLevels-- > 0)
		{
			if($node->nodeName == 'w:body')
			{
				break;
			}
			elseif(in_array($node->nodeName, $nodeNames))
			{
				return $node;
			}
			$node = $node->parentNode;
		}

		return false;
	}

	/**
	 * Finds arrays in $this->values.
	 * For these values tries to find .BLOCK_START and .BLOCK_END marks.
	 * All content between them considered as block for multiplying.
	 * Such block fills with values for each row in array and inserts into $this->document.
	 * Start, end marks and original nodes are being deleted.
	 */
	protected function processArrays()
	{
		foreach($this->values as $placeholder => $list)
		{
			if($list instanceof ArrayDataProvider)
			{
				$this->initDomDocument();
				$block = $this->collectMultiplyNodes($placeholder);
				while(isset($block['content']) && !empty($block['content']))
				{
					$list->rewind();
					foreach($list as $index => $value)
					{
						foreach($block['nodes'] as $key => $node)
						{
							/** @var \DOMElement $node */
							$content = $block['content'][$key];
							$fieldNames = static::matchFieldNames($content);
							$values = $this->getValuesForMultiplyingBlock($placeholder, $list, $value, $fieldNames);
							$values = array_merge($this->values, $values);
							$blockContent = preg_replace_callback(static::$valuesPattern, function($matches) use ($values)
							{
								if($matches[2] && array_key_exists($matches[2], $values))
								{
									// multiply images
									if($this->isImageValue($matches[2], $values))
									{
										if($values[$matches[2]])
										{
											// in case someone inserted image placeholder as text - prevent looping
											$placeholder = $matches[1];
											if(!$matches[3])
											{
												$placeholder .= '~';
											}
											$placeholder .= static::DO_NOT_INSERT_VALUE_MODIFIER;
											return '{'.$placeholder.'}';
										}
										else
										{
											return static::EMPTY_IMAGE_PLACEHOLDER;
										}
									}
									return $this->printValue($values[$matches[2]], $matches[2], $matches[3]);
								}
								return '';
							}, $content);
							$innerXml = new DocxXml($blockContent);
							$imageData = $innerXml->findImages(true);
							foreach($imageData as $imagePlaceholder => $data)
							{
								if($this->isImageValue($imagePlaceholder, $values))
								{
									foreach($data['innerIDs'] as $id)
									{
										$this->arrayImageValues['values'][$id] = $values[$imagePlaceholder];
										$this->arrayImageValues['originalId'][$id] = $data['originalId'][$id];
									}
								}
							}
							$nodeToLoad = $block['nodes'][count($block['nodes']) - 1];
							$blockDocument = $innerXml->getDomDocument();
							foreach($blockDocument->childNodes as $blockNode)
							{
								$blockNode = $this->document->importNode($blockNode, true);
								$nodeToLoad->parentNode->insertBefore($blockNode, $nodeToLoad);
							}
						}
					}
					if(isset($block['startNode']))
					{
						$block['startNode']->parentNode->removeChild($block['startNode']);
					}
					foreach($block['nodes'] as $node)
					{
						$node->parentNode->removeChild($node);
					}
					if(isset($block['endNode']) && $block['endNode'])
					{
						$block['endNode']->parentNode->removeChild($block['endNode']);
					}
					$block = $this->collectMultiplyNodes($placeholder);
				}
				$this->saveContent();
			}
		}
	}

	/**
	 * Finds {$placeholder.START_BLOCK} node.
	 * Collect all nodes after this while last node does not contain {$placeholder.END_BLOCK}
	 * Returns array with startNode, content nodes and endNode.
	 *
	 * @param $placeholder
	 * @return array
	 */
	protected function collectMultiplyNodes($placeholder)
	{
		$result = [];
		$startNode = $this->findPlaceholderNode($placeholder.'.'.static::BLOCK_START_PLACEHOLDER);
		if(!$startNode)
		{
			// try to find by magic
			$tableRowNodes = [];
			$linkedPlaceholders = $this->getLinkedPlaceholders($placeholder);
			foreach($linkedPlaceholders as $linkedPlaceholder)
			{
				$linkedPlaceholderNodes = $this->findPlaceholderNodes($linkedPlaceholder);
				foreach($linkedPlaceholderNodes as $node)
				{
					if(strpos($node->textContent, static::DO_NOT_INSERT_VALUE_MODIFIER) === false)
					{
						$tableRowNodes[] = $this->getParentTableRowNode($node);
					}
				}
			}
			$tableRowNodes = $this->getUniqueNodes($tableRowNodes);
			if(!empty($tableRowNodes))
			{
				/** @var \DOMElement[] $tableRowNodes */
				$result = [
					'content' => [$tableRowNodes[0]->C14N()],
					'nodes' => [$tableRowNodes[0]],
				];
			}
			return $result;
		}
		$startNode = $this->getParentParagraphNode($startNode);
		if(!$startNode)
		{
			return $result;
		}
		$nodes = [];
		$result['endNode'] = false;
		$result['startNode'] = $startNode;
		$maxTags = 20;
		$node = $startNode->nextSibling;
		while($maxTags-- > 0 && $node)
		{
			if(trim($node->nodeValue) == '{'.$placeholder.'.'.static::BLOCK_END_PLACEHOLDER.'}')
			{
				$result['endNode'] = $node;
				break;
			}
			$nodes[] = $node;
			$node = $node->nextSibling;
		}
		if(!$result['endNode'])
		{
			$nodes = [];
		}
		foreach($nodes as $node)
		{
			$result['content'][] = $node->C14N();
			$result['nodes'][] = $node;
		}

		return $result;
	}

	/**
	 * Generate array of values to change in multiplying block.
	 *
	 * @param string $placeholder
	 * @param ArrayDataProvider $list
	 * @param DataProvider|array $data
	 * @param array $fieldNames
	 * @return array
	 */
	protected function getValuesForMultiplyingBlock($placeholder, ArrayDataProvider $list, $data, array $fieldNames)
	{
		$values = [];
		foreach($fieldNames as $fullName)
		{
			list($providerName, $fieldName) = explode('.', $fullName);
			if($providerName == $placeholder && $fieldName)
			{
				$values[$fullName] = $list->getValue($fieldName);
			}
			else
			{
				$value = $this->values[$fullName];
				if(is_string($value))
				{
					$valueNameParts = explode('.', $value);
					if($valueNameParts[0] == $placeholder)
					{
						if($valueNameParts[1] == $list->getItemKey() && count($valueNameParts) > 2)
						{
							$name = implode('.', array_slice($valueNameParts, 2));
							if($data instanceof DataProvider)
							{
								$value = $data->getValue($name);
							}
							elseif(is_array($data))
							{
								$value = $data[$name];
							}
						}
						else
						{
							$value = $list->getValue($valueNameParts[1]);
						}
					}
				}
				$values[$fullName] = $value;
			}
		}

		return $values;
	}

	/**
	 * Delete table rows for empty values.
	 */
	protected function clearRowsWithoutValues()
	{
		$fieldsToHide = [static::EMPTY_IMAGE_PLACEHOLDER];
		$fields = $this->getFields();
		foreach($fields as $placeholder => $field)
		{
			if(
				($field['TYPE'] == DataProvider::FIELD_TYPE_IMAGE || $field['TYPE'] == DataProvider::FIELD_TYPE_STAMP) ||
				(isset($field['OPTIONS']) && isset($field['OPTIONS']['IS_ARRAY']) && $field['OPTIONS']['IS_ARRAY'] === true)
			)
			{
				$fieldsToHide[] = $placeholder;
			}
		}
		$fieldsToHide = array_unique($fieldsToHide);
		if(!empty($fieldsToHide))
		{
			$nodesToDelete = [];
			$this->initDomDocument();
			foreach($fieldsToHide as $placeholder)
			{
				if($fields[$placeholder]['HIDE_ROW'] == 'Y' && ($this->values[$placeholder] === null || $this->values[$placeholder] === ''))
				{
					$nodes = $this->findPlaceholderNodes($placeholder);
					foreach($nodes as $node)
					{
						$parentRow = $this->getParentTableRowNode($node, 5);
						if($parentRow)
						{
							$nodesToDelete[] = $parentRow;
						}
						else
						{
							$parentRow = $this->getParentParagraphNode($node, 3);
							if($parentRow)
							{
								$nodesToDelete[] = $parentRow;
							}
						}
					}
				}
			}
			$nodesToDelete = $this->getUniqueNodes($nodesToDelete);
			foreach($nodesToDelete as $node)
			{
				$node->parentNode->removeChild($node);
			}
			$this->saveContent();
		}
	}

	/**
	 * Returns array where key is a placeholder and value is an array of image ids.
	 *
	 * @return array
	 */
	protected function processImages()
	{
		$imageData = $this->findImages();
		foreach($imageData as $placeholder => &$image)
		{
			if(!isset($this->values[$placeholder]) || empty($this->values[$placeholder]) || $this->values[$placeholder] == ' ')
			{
				foreach($image['drawingNode'] as $key => $node)
				{
					/** @var \DOMNode $node */
					$node->parentNode->removeChild($node);
					unset($image['drawingNode'][$key]);
				}
			}
		}
		$this->saveContent();

		return $imageData;
	}

	/**
	 * Get all drawing nodes marked with placeholders.
	 * If $generateNewImageIds is true - will replace relation ids to new values.
	 *
	 * @param bool $generateNewImageIds
	 * @param \DOMNode|null $contextNode
	 * @return array
	 */
	public function findImages($generateNewImageIds = false, \DOMNode $contextNode = null)
	{
		$this->initDomDocument();
		if($contextNode)
		{
			$imageDescriptions = $this->xpath->query('//w:drawing//wp:docPr', $contextNode);
		}
		else
		{
			$imageDescriptions = $this->xpath->query('//w:drawing//wp:docPr');
		}
		$placeholders = [];
		foreach($imageDescriptions as $description)
		{
			/** @var \DOMElement $description */
			if($description->hasAttributes())
			{
				$name = $description->attributes->getNamedItem('name');
				$descr = $description->attributes->getNamedItem('descr');
				$placeholder = null;
				if($descr)
				{
					$placeholder = static::getCodeFromPlaceholder($descr->nodeValue);
				}
				if(!$placeholder && $name)
				{
					$placeholder = static::getCodeFromPlaceholder($name->nodeValue);
				}
				if($placeholder)
				{
					if(!isset($placeholders[$placeholder]))
					{
						$placeholders[$placeholder] = [
							'drawingNode' => [],
							'innerIDs' => [],
						];
					}
					$placeholders[$placeholder]['drawingNode'][] = $description->parentNode->parentNode;
					$embeds = $description->parentNode->getElementsByTagNameNS($this->getNamespaces()['a'], 'blip');
					if($embeds->length > 0)
					{
						/** @var \DOMNode $embed */
						$embed = $embeds[0];
						if($innerImageId = $embed->attributes->getNamedItemNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'embed'))
						{
							/** @var \DOMAttr $innerImageId */
							$imageId = $innerImageId->value;
							if($generateNewImageIds)
							{
								$newImageId = 'rId' . rand(5000, 100000);
								$placeholders[$placeholder]['originalId'][$newImageId] = $imageId;
								$imageId = $innerImageId->value = $newImageId;
							}
							if(!in_array($imageId, $placeholders[$placeholder]['innerIDs']))
							{
								$placeholders[$placeholder]['innerIDs'][] = $imageId;
								if(isset($this->arrayImageValues['values'][$imageId]))
								{
									$placeholders[$placeholder]['values'][$imageId] = $this->arrayImageValues['values'][$imageId];
									$placeholders[$placeholder]['originalId'][$imageId] = $this->arrayImageValues['originalId'][$imageId];
								}
							}
						}
					}
				}
			}
		}

		if(!empty($placeholders) && $generateNewImageIds)
		{
			$this->saveContent();
		}

		return $placeholders;
	}

	/**
	 * @param mixed $value
	 * @param string $placeholder
	 * @param string $modifier
	 * @return string
	 */
	protected function printValue($value, $placeholder, $modifier = '')
	{
		$value = parent::printValue($value, $placeholder, $modifier);
		if(empty($value))
		{
			return $value;
		}
		if (ToUpper(SITE_CHARSET) !== 'UTF-8')
		{
			if(is_array($value) || is_object($value))
			{
				$value = '';
			}
			elseif(Encoding::detectUtf8($value))
			{
				// do nothing
			}
			else
			{
				$value = Encoding::convertEncoding($value, SITE_CHARSET, 'UTF-8');
			}
		}
		if(is_string($value))
		{
			if($this->isImageValue($placeholder, $this->values))
			{
				return '';
			}
			else
			{
				if($this->isHtml($value))
				{
					$value = $this->htmlToXml($value);
				}
				else
				{
					$value = $this->prepareTextValue($value);
				}
			}
		}

		return $value;
	}

	/**
	 * @param string $placeholder
	 * @param array $values
	 * @param array|null $fields
	 * @return bool
	 */
	protected function isImageValue($placeholder, array $values, array $fields = null)
	{
		if(!$fields)
		{
			$fields = $this->fields;
		}

		return (
			array_key_exists($placeholder, $values) &&
			isset($fields[$placeholder]) &&
			isset($fields[$placeholder]['TYPE']) &&
			($fields[$placeholder]['TYPE'] === DataProvider::FIELD_TYPE_IMAGE || $fields[$placeholder]['TYPE'] === DataProvider::FIELD_TYPE_STAMP)
		);
	}

	/**
	 * @param string $text
	 * @param bool $saveBreakLines
	 * @return string
	 */
	protected function prepareTextValue($text, $saveBreakLines = false)
	{
		if($saveBreakLines)
		{
			$text = str_replace(PHP_EOL, '{__SystemBreakLine}', $text);
		}
		$text = html_entity_decode($text);
		$text = strtr(
			$text,
			array(
				'<' => '&lt;',
				'>' => '&gt;',
				'"' => '&quot;',
				'\'' => '&apos;',
				'&' => '&amp;',
			)
		);
		$text = preg_replace('/[\x01-\x08\x0B-\x0C\x0E-\x1F]/', '', $text);
		if($saveBreakLines)
		{
			$text = str_replace('{__SystemBreakLine}', '</w:t><w:br/><w:t>', $text);
		}
		return $text;
	}

	/**
	 * @param $string
	 * @return bool
	 */
	protected function isHtml($string)
	{
		return (preg_match('/<\s?[^\>]*\/?\s?>/i', $string) != false);
	}

	/**
	 * Converts html to xml with the same rendering.
	 *
	 * @param string $html
	 * @return string
	 */
	protected function htmlToXml($html)
	{
		$htmlDocument = new \Bitrix\Main\Web\DOM\Document();
		$htmlDocument->loadHTML($html);
		$result = $this->htmlNodeToXml($htmlDocument);
		if(!empty($result))
		{
			$result = '</w:t></w:r>'.$result.'<w:r><w:t>';
		}
		return $result;
	}

	/**
	 * Returns true if this node should be rendered.
	 *
	 * @param \Bitrix\Main\Web\DOM\Node $node
	 * @return bool
	 */
	protected function isDisplayableNode(\Bitrix\Main\Web\DOM\Node $node)
	{
		$hiddenNodeNames = [
			'#comment' => true, 'STYLE' => true, 'SCRIPT' => true,
		];
		if(isset($hiddenNodeNames[$node->getNodeName()]))
		{
			return false;
		}

		return true;
	}

	/**
	 * Returns array with rendering properties - display type and font params.
	 *
	 * @param \Bitrix\Main\Web\DOM\Node $node
	 * @return array
	 */
	protected function getDisplayProperties(\Bitrix\Main\Web\DOM\Node $node)
	{
		$result = ['display' => 'inline', 'font' => [],];
		if(!$this->isDisplayableNode($node))
		{
			$result['display'] = static::HTML_NODE_DISPLAY_HIDDEN;
			return $result;
		}
		if($node instanceof \Bitrix\Main\Web\DOM\Element)
		{
			$styles = $node->getStyle();
			$display = false;
			$font = [];
			if($styles)
			{
				$stylePairs = explode(';', $styles);
				foreach($stylePairs as $pair)
				{
					list($name, $value) = explode(':', $pair);
					if($name && $value)
					{
						if($name == 'display')
						{
							if($value == 'none')
							{
								$display = static::HTML_NODE_DISPLAY_HIDDEN;
							}
							elseif($value == 'block')
							{
								$display = static::HTML_NODE_DISPLAY_BLOCK;
							}
						}
						elseif($name == 'font-weight')
						{
							if(intval($value) > 500 || $value == 'bold')
							{
								$font[static::HTML_NODE_FONT_BOLD] = static::HTML_NODE_FONT_BOLD;
							}
							elseif(intval($value) < 500 || $value == 'normal')
							{
								$font[static::HTML_NODE_FONT_NORMAL] = static::HTML_NODE_FONT_NORMAL;
							}
						}
						elseif($name == 'font-style' && $value == 'italic' || strpos($value, 'oblique') === 0)
						{
							$font[static::HTML_NODE_FONT_ITALIC] = static::HTML_NODE_FONT_ITALIC;
						}
						elseif($name == 'text-decoration' && strpos($value, 'underline') !== false)
						{
							$font[static::HTML_NODE_FONT_UNDERLINED] = static::HTML_NODE_FONT_UNDERLINED;
						}
					}
				}
			}
			if($display == static::HTML_NODE_DISPLAY_HIDDEN)
			{
				$result['display'] = $display;
				return $result;
			}
			if(!$display && $this->isBlockTag($node->getTagName()))
			{
				$display = static::HTML_NODE_DISPLAY_BLOCK;
			}
			if(!$display)
			{
				$display = static::HTML_NODE_DISPLAY_INLINE;
			}

			if(!isset($font[static::HTML_NODE_FONT_BOLD]) && $this->isBoldTag($node->getTagName()))
			{
				$font[static::HTML_NODE_FONT_BOLD] = static::HTML_NODE_FONT_BOLD;
			}
			if(!isset($font[static::HTML_NODE_FONT_ITALIC]) && $this->isItalicTag($node->getTagName()))
			{
				$font[static::HTML_NODE_FONT_ITALIC] = static::HTML_NODE_FONT_ITALIC;
			}
			if(!isset($font[static::HTML_NODE_FONT_UNDERLINED]) && $this->isUnderlinedTag($node->getTagName()))
			{
				$font[static::HTML_NODE_FONT_UNDERLINED] = static::HTML_NODE_FONT_UNDERLINED;
			}
			if($this->isDeletedTag($node->getTagName()))
			{
				$font[static::HTML_NODE_FONT_DELETED] = static::HTML_NODE_FONT_DELETED;
			}

			$result['display'] = $display;
			$result['font'] = $font;
		}

		return $result;
	}

	/**
	 * Recursively converts html node to xml.
	 *
	 * @param \Bitrix\Main\Web\DOM\Node $node
	 * @param array $context
	 * @return string
	 */
	protected function htmlNodeToXml(\Bitrix\Main\Web\DOM\Node $node, array &$context = [])
	{
		$result = '';

		$displayProperties = $this->getDisplayProperties($node);

		if(isset($displayProperties['display']) && $displayProperties['display'] == static::HTML_NODE_DISPLAY_HIDDEN)
		{
			return $result;
		}
		$nodes = $node->getChildNodes();
		$nodeName = strtolower($node->getNodeName());
		if($nodeName == 'ul')
		{
			$context['currentList'] = 'ul';
		}
		elseif($nodeName == 'ol')
		{
			$context['currentList'] = 'ol';
			$context['currentNumber'] = 1;
		}
		elseif($nodeName == 'li')
		{
			$context['showNumber'] = true;
		}

		if($displayProperties['display'] == static::HTML_NODE_DISPLAY_BLOCK)
		{
			$context['display'] = $displayProperties['display'];
		}
		if(!isset($context['font']) || !is_array($context['font']))
		{
			$context['font'] = [];
		}
		$context['font'] = array_merge($context['font'], $displayProperties['font']);
		/** @var \Bitrix\Main\Web\DOM\Node $childNode */
		foreach($nodes as $childNode)
		{
			$nodeValue = str_replace("\n", '', $childNode->getNodeValue());
			if($displayProperties['display'] == static::HTML_NODE_DISPLAY_BLOCK || $context['display'] == static::HTML_NODE_DISPLAY_BLOCK)
			{
				$nodeValue = trim($nodeValue);
			}
			$childNodeName = strtolower($childNode->getNodeName());
			if($childNodeName == 'br')
			{
				$result .= '<w:r>';
				$result .= '<w:br/>';
				$result .= '</w:r>';
			}
			elseif($childNode instanceof \Bitrix\Main\Web\DOM\Text && !empty($nodeValue))
			{
				$result .= '<w:r>';
				if($context['display'] == static::HTML_NODE_DISPLAY_BLOCK)
				{
					$result .= '<w:br/>';
					$context['display'] = $displayProperties['display'];
				}
				if(isset($context['showNumber']) && $context['showNumber'])
				{
					unset($context['showNumber']);
					if(isset($context['currentList']) && $context['currentList'] == 'ol' && isset($context['currentNumber']) && $context['currentNumber'] > 0)
					{
						$result .= '<w:t xml:space="preserve">';
						$result .= '    '.$context['currentNumber'].'. ';
						$context['currentNumber']++;
						$result .= '</w:t>';
						$result .= '</w:r>';
						$result .= '<w:r>';
					}
					elseif(isset($context['currentList']) && $context['currentList'] == 'ul')
					{
						$result .= '<w:t xml:space="preserve">';
						$result .= '     - ';
						$result .= '</w:t>';
						$result .= '</w:r>';
						$result .= '<w:r>';
					}
				}
				$result .= $this->addRowPropertiesTag($context['font']);
				$result .= '<w:t xml:space="preserve">';
				$result .= $this->prepareTextValue($childNode->getNodeValue());
				$result .= '</w:t>';
				$result .= '</w:r>';
			}
			else
			{
				$result .= $this->htmlNodeToXml($childNode, $context);
			}
		}

		if($nodeName == 'ul')
		{
			unset($context['currentList']);
		}
		elseif($nodeName == 'ol')
		{
			unset($context['currentList']);
			unset($context['currentNumber']);
		}
		elseif($nodeName == 'li')
		{
			unset($context['showNumber']);
		}
		$context['font'] = array_diff_assoc($context['font'], $displayProperties['font']);

		return $result;
	}

	/**
	 * Returns true if html-tag with this $tagName displays as block by default.
	 *
	 * @param string $tagName
	 * @return bool
	 */
	protected function isBlockTag($tagName)
	{
		$blockTagNames = [
			'address' => true, 'article' => true, 'aside' => true, 'blockquote' => true, 'details' => true,
			'dialog' => true, 'dd' => true, 'div' => true, 'dl' => true, 'dt' => true, 'fieldset' => true,
			'figcaption' => true, 'figure' => true, 'footer' => true, 'form' => true, 'h1' => true, 'h2' => true,
			'h3' => true, 'h4' => true, 'h5' => true, 'h6' => true, 'header' => true, 'hgroup' => true, 'hr' => true,
			'li' => true, 'main' => true, 'nav' => true, 'ol' => true, 'p' => true, 'pre' => true, 'section' => true, 'table' => true,
			'ul' => true,
		];

		return isset($blockTagNames[strtolower($tagName)]);
	}

	/**
	 * Returns true if html-tag with this $tagName has bold font-weight by default.
	 *
	 * @param string $tagName
	 * @return bool
	 */
	protected function isBoldTag($tagName)
	{
		$boldTagNames = [
			'b' => true, 'mark' => true, 'em' => true, 'strong' => true, 'h1' => true, 'h2' => true, 'h3' => true,
			'h4' => true, 'h5' => true, 'h6' => true,
		];

		return isset($boldTagNames[strtolower($tagName)]);
	}

	/**
	 * Returns true if html-tag with this $tagName has italic font-style by default.
	 *
	 * @param string $tagName
	 * @return bool
	 */
	protected function isItalicTag($tagName)
	{
		$italicTagNames = [
			'i' => true, 'cite' => true, 'dfn' => true,
		];

		return isset($italicTagNames[strtolower($tagName)]);
	}

	/**
	 * Returns true if html-tag with this $tagName has underlined font-decoration by default.
	 *
	 * @param string $tagName
	 * @return bool
	 */
	protected function isUnderlinedTag($tagName)
	{
		return strtolower($tagName) == 'u';
	}

	/**
	 * Returns true if html-tag with this $tagName renders as 'deleted' by default.
	 *
	 * @param string $tagName
	 * @return bool
	 */
	protected function isDeletedTag($tagName)
	{
		return strtolower($tagName) == 'del';
	}

	/**
	 * Returns row-properties xml-tag based on font properties.
	 *
	 * @param array $font
	 * @return string
	 */
	protected function addRowPropertiesTag(array $font)
	{
		$result = '<w:rPr>';
		$result .= '<w:rStyle w:val="Del" />';
		if(isset($font[static::HTML_NODE_FONT_BOLD]))
		{
			$result .= '<w:b/>';
		}
		if(isset($font[static::HTML_NODE_FONT_ITALIC]))
		{
			$result .= '<w:i/>';
		}
		if(isset($font[static::HTML_NODE_FONT_DELETED]))
		{
			$result .= '<w:strike/>';
		}
		if(isset($font[static::HTML_NODE_FONT_UNDERLINED]))
		{
			$result .= '<w:u w:val="single" />';
		}
		$result .= '</w:rPr>';

		return $result;
	}

//	/**
//	 * Generates simple spreadsheets for array values.
//	 *
//	 * @deprecated
//	 */
//	protected function generateSpreadsheets()
//	{
//		foreach($this->values as $placeholder => $value)
//		{
//			if(is_array($value))
//			{
//				$this->initDomDocument();
//				//$pageWidth = $this->getFirstPageWidth();
//				$spreadsheet = $this->generateSpreadsheet($value);
//				if(!$spreadsheet)
//				{
//					continue;
//				}
//				$spreadsheet = $this->document->importNode($spreadsheet, true);
//				$textNodes = $this->xpath->query('//w:t[text()="{'.$placeholder.'}"]');
//				foreach($textNodes as $node)
//				{
//					$node->parentNode->parentNode->parentNode->replaceChild($spreadsheet, $node->parentNode->parentNode);
//				}
//			}
//		}
//		$this->saveDomDocument();
//	}
//
//	/**
//	 * Parses properties of the first page and get width from there.
//	 * Width calculates as full width minus left and right margin.
//	 *
//	 * @return bool|string
//	 */
//	protected function getFirstPageWidth()
//	{
//		static $pageWidth = null;
//		if($pageWidth === null)
//		{
//			$pageWidth = false;
//			$pageSizes = $this->xpath->query('//w:sectPr//w:pgSz');
//			if($pageSizes->length > 0)
//			{
//				$pageSize = $pageSizes->item(0);
//				$width = $pageSize->attributes->getNamedItemNS($this->wordNamespaces['w'], 'w');
//				if($width)
//				{
//					$pageWidth = $width->nodeValue;
//				}
//			}
//			if($pageWidth > 0)
//			{
//				$pageMargins = $this->xpath->query('//w:sectPr//w:pgMar');
//				if($pageMargins->length > 0)
//				{
//					$pageMargin = $pageMargins->item(0);
//					$left = $pageMargin->attributes->getNamedItemNS($this->wordNamespaces['w'], 'left');
//					if($left)
//					{
//						$pageWidth -= $left->nodeValue;
//					}
//					$right = $pageMargin->attributes->getNamedItemNS($this->wordNamespaces['w'], 'right');
//					if($right)
//					{
//						$pageWidth -= $right->nodeValue;
//					}
//				}
//			}
//		}
//
//		return $pageWidth;
//	}
//
//	/**
//	 * @param array $data
//	 * @return bool|\DOMNode
//	 */
//	protected function generateSpreadsheet(array $data)
//	{
//		if(!isset($data['DATA']))
//		{
//			return false;
//		}
//		$content = '<w:document ';
//		foreach($this->wordNamespaces as $prefix => $namespaceUri)
//		{
//			$content .= 'xmlns:'.$prefix.'="'.$namespaceUri.'" ';
//		}
//		$content .= '><w:tbl>
//		<w:tblPr>
//			<w:tblW w:w="5000" w:type="pct" />
//            <w:jc w:val="left" />
//            <w:tblInd w:w="100" w:type="dxa" />
//			<w:tblBorders>
//                <w:top w:val="single" w:sz="2" w:space="0" w:color="000000" />
//                <w:left w:val="single" w:sz="2" w:space="0" w:color="000000" />
//                <w:bottom w:val="single" w:sz="2" w:space="0" w:color="000000" />
//                <w:insideH w:val="single" w:sz="2" w:space="0" w:color="000000" />
//            </w:tblBorders>
//            <w:tblCellMar>
//                <w:top w:w="0" w:type="dxa" />
//                <w:left w:w="100" w:type="dxa" />
//                <w:bottom w:w="0" w:type="dxa" />
//                <w:right w:w="100" w:type="dxa" />
//            </w:tblCellMar>
//		</w:tblPr>';
//		foreach($data['DATA'] as $row => $data)
//		{
//			if($row == 0)
//			{
//				$columns = count($data);
//				$columnWidth = floor(5000 / $columns);
//				$content .= '<w:tblGrid>';
//				$content .= str_repeat('<w:gridCol w:w="'.$columnWidth.'" />', $columns);
//				$content .= '</w:tblGrid>';
//			}
//			$content .= '<w:tr>';
//			foreach($data as $column)
//			{
//				$content .= '<w:tc>
//					<w:tcPr>
//						<w:tcW w:w="'.$columnWidth.'" w:type="dxa" />
//                        <w:tcBorders>
//                            <w:top w:val="single" w:sz="2" w:space="0" w:color="000000" />
//                            <w:left w:val="single" w:sz="2" w:space="0" w:color="000000" />
//                            <w:bottom w:val="single" w:sz="2" w:space="0" w:color="000000" />
//                            <w:insideH w:val="single" w:sz="2" w:space="0" w:color="000000" />
//                        </w:tcBorders>
//					</w:tcPr>
//                    <w:p>
//                        <w:pPr>
//                            <w:spacing w:before="20" w:after="20" w:lineRule="auto" />
//                            <w:ind w:start="20" w:end="20" w:firstLine="0" />
//                            <w:contextualSpacing w:val="false" />
//                        </w:pPr>
//                        <w:r>
//                            <w:t>'.$column.'</w:t>
//                        </w:r>
//                    </w:p>
//                </w:tc>';
//			}
//			$content .= '</w:tr>';
//		}
//		$content .= '</w:tbl>
//		</w:document>';
//
//		$spreadsheet = new \DOMDocument();
//		$spreadsheet->loadXML($content);
//		$spreadsheetNodes = $spreadsheet->getElementsByTagNameNS($this->wordNamespaces['w'], 'tbl');
//		return $spreadsheetNodes->item(0);
//	}
}