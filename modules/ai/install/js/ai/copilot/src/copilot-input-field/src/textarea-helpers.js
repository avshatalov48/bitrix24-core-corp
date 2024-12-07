function createRangeWithPosition(node: Node, targetPosition: number): Range
{
	const range = document.createRange();
	range.selectNode(node);
	range.setStart(node, 0);

	let pos = 0;
	const stack = [node];
	while (stack.length > 0)
	{
		const current = stack.pop();

		if (current.nodeType === Node.TEXT_NODE)
		{
			const len = current.textContent.length;
			if (pos + len >= targetPosition)
			{
				range.setStart(current, targetPosition - pos);
				range.setEnd(current, targetPosition - pos);

				return range;
			}
			pos += len;
		}
		else if (current.childNodes && current.childNodes.length > 0)
		{
			for (let i = current.childNodes.length - 1; i >= 0; i--)
			{
				stack.push(current.childNodes[i]);
			}
		}
	}

	range.setStart(node, node.childNodes.length);
	range.setEnd(node, node.childNodes.length);

	return range;
}

export function setCursorPosition(node: Node, targetPosition: number): void
{
	const range = createRangeWithPosition(node, targetPosition);
	const selection = window.getSelection();
	selection.removeAllRanges();
	selection.addRange(range);
}

export function getCursorPosition(node: Node): number
{
	const selection = window.getSelection();
	const range = selection.getRangeAt(0);
	const clonedRange = range.cloneRange();
	clonedRange.selectNodeContents(node);
	clonedRange.setEnd(range.endContainer, range.endOffset);

	return clonedRange.toString().length;
}
