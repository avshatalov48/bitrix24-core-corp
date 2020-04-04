<?php

namespace Bitrix\DocumentGenerator\Body;

use Bitrix\DocumentGenerator\Body;
use Bitrix\DocumentGenerator\Document;
use Bitrix\Main\Result;
use Bitrix\Main\Web\DOM\Node;
use Bitrix\DocumentGenerator\Nameable;

class Html extends Body implements Nameable
{
	protected $htmlDocument;

	/**
	 * @inheritdoc
	 */
	public function __construct($content, \Bitrix\Main\Web\DOM\Document $htmlDocument = null)
	{
		parent::__construct($content);
		if($htmlDocument)
		{
			$this->htmlDocument = $htmlDocument;
		}
		else
		{
			$this->htmlDocument = new \Bitrix\Main\Web\DOM\Document();
		}

		$this->htmlDocument->loadHTML($this->content);
	}

	/**
	 * @return string
	 */
	public function getFileExtension()
	{
		return 'html';
	}

	/**
	 * @inheritdoc
	 */
	public function getPlaceholders()
	{
		$names = static::matchFieldNames($this->content);

		$variableAttributes = [
			'condition', 'from', 'to',
		];
		$commandNodes = $this->htmlDocument->querySelectorAll('command');
		foreach($commandNodes as $nextCommand)
		{
			$nodeAttributes = static::getNodeAttributes($nextCommand);
			foreach($variableAttributes as $attribute)
			{
				if($nodeAttributes[$attribute])
				{
					$names[] = $nodeAttributes[$attribute];
				}
			}
		}

		return $names;
	}

	/**
	 * @inheritdoc
	 */
	public function process()
	{
		$result = new Result();

		while($nextCommand = $this->htmlDocument->querySelector('command'))
		{
			$this->processCommand($nextCommand);
		}

		$this->insertValues();

		$this->content = $this->htmlDocument->getInnerHTML();
		$result->setData(['CONTENT' => $this->content]);

		return $result;
	}

	/**
	 * Fill $content with field values
	 *
	 * @return void
	 */
	protected function insertValues()
	{
		$resultHtml = $this->replacePlaceholders($this->htmlDocument->getInnerHTML());

		$this->htmlDocument->setInnerHTML($resultHtml);
	}

	/**
	 * Process one <command> tag.
	 *
	 * @param Node $node
	 */
	protected function processCommand(Node $node)
	{
		$nodeAttributes = static::getNodeAttributes($node);
		$command = $nodeAttributes['name'];
		if($command == 'if')
		{
			$variableName = $nodeAttributes['condition'];
			$variableValue = $this->values[$variableName];
			if($variableValue)
			{
				$this->replaceCommand($node, $node->getInnerHTML());
			}
			else
			{
				$this->replaceCommand($node, '');
			}
		}
		elseif($command == 'foreach')
		{
			$variableName = $nodeAttributes['from'];
			$variableValue = $this->values[$variableName];
			$innerVariableName = $nodeAttributes['to'];
			if(is_array($variableValue) || $variableValue instanceof \Traversable)
			{
				$resultHtml = '';
				foreach($variableValue as $value)
				{
					$body = new static(['CONTENT' => $node->getInnerHTML()]);
					$document = new Document($body);
					$document->setValues([$innerVariableName => $value]);
					$resultHtml .= $document->render();
				}
				$this->replaceCommand($node, $resultHtml);
			}
			else
			{
				$this->replaceCommand($node, '');
			}
		}
		else
		{
			$this->replaceCommand($node, '');
		}
	}

	/**
	 * Returns array of noe attributes.
	 *
	 * @param Node $node
	 * @return array
	 */
	protected static function getNodeAttributes(Node $node)
	{
		$attributes = [];
		if($node->hasAttributes())
		{
			foreach($node->getAttributes() as $attr)
			{
				/* @var $attr Attr*/
				$attributes = array_merge($attributes, $attr->toArray());
			}
		}

		return $attributes;
	}

	/**
	 * Replace $node inner HTML with $html.
	 *
	 * @param Node $node
	 * @param string $html
	 */
	protected function replaceCommand(Node $node, $html)
	{
		$parentNodeHtml = $node->getParentNode()->getInnerHTML();
		$resultHtml = str_replace($node->getOuterHTML(), $html, $parentNodeHtml);
		$node->getParentNode()->setInnerHTML($resultHtml);
	}

	/**
	 * @return string
	 */
	public static function getLangName()
	{
		return 'Html';
	}

	/**
	 * @return string
	 */
	public function getFileMimeType()
	{
		return 'text/html';
	}
}