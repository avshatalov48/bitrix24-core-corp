<?php

namespace Bitrix\Voximplant\Routing;

use Bitrix\Main\ArgumentException;
use Bitrix\Voximplant\Call;

abstract class Node
{
	protected $id;
	/** @var Node */
	protected $next;

	public function __construct()
	{
		$this->id = uniqid();
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Should return action for this stage of the call, or false if call execution should be passed to the next action.
	 * @param Call $call
	 * @param bool $firstRun
	 * @return Action | false
	 */
	abstract public function getFirstAction(Call $call);

	/**
	 * Should return action for this stage of the call, or false if call execution should be passed to the next action.
	 * @param Call $call
	 * @param array $request
	 * @return Action | false
	 */
	abstract public function getNextAction(Call $call, array $request = []);

	/**
	 * @return Node
	 */
	public function getNext()
	{
		return $this->next;
	}

	/**
	 * @param Node $next
	 */
	public function setNext(Node $next)
	{
		$this->next = $next;
	}

	/**
	 * Inserts new next node between this and current next node.
	 *
	 * @param Node $next
	 */
	public function insertAfter(Node $next)
	{
		if($this->next instanceof Node && $next->next instanceof Node)
		{
			throw new ArgumentException("This node and inserted node can not both have 'next' property set.");
		}

		if($this->next instanceof Node)
		{
			$next->setNext($this->next);
		}

		$this->next = $next;
	}
}