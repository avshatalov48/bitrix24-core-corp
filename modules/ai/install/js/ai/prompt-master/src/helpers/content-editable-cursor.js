import { Dom } from 'main.core';

// eslint-disable-next-line sonarjs/cognitive-complexity
const createRangeWithPosition = (node: Node, targetPosition: number): Range => {
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
		else if (current.nodeType === Node.ELEMENT_NODE && current.childNodes.length === 0)
		{
			if (pos === targetPosition)
			{
				range.setStart(current, 0);
				range.setEnd(current, 0);

				return range;
			}
		}
		else if (current.childNodes && current.childNodes.length > 0)
		{
			if (current.nodeName === 'DIV' && current !== node && current !== node.childNodes[0])
			{
				pos += 1;
			}

			for (let i = current.childNodes.length - 1; i >= 0; i--)
			{
				stack.push(current.childNodes[i]);
			}
		}
	}

	range.setStart(node, node.childNodes.length);
	range.setEnd(node, node.childNodes.length);

	return range;
};

export const setCursorPosition = (node: Node, targetPosition: number): void => {
	const range = createRangeWithPosition(node, targetPosition);
	const selection = window.getSelection();
	selection.removeAllRanges();
	selection.addRange(range);
};

export const getCursorPosition = (node: HTMLElement): number => {
	const selection = window.getSelection();
	if (!selection.rangeCount)
	{
		return 0;
	}

	const range = selection.getRangeAt(0);
	const clonedRange = range.cloneRange();
	clonedRange.selectNodeContents(node);
	clonedRange.setEnd(range.endContainer, range.endOffset);

	let cursorPosition = clonedRange.toString().length;

	const div = document.createElement('div');
	Dom.append(clonedRange.cloneContents(), div);

	const lineBreakElements = div.querySelectorAll('div');

	cursorPosition += lineBreakElements.length;

	if (node.firstChild?.nodeName === 'DIV' && node.childNodes[1]?.nodeType !== Node.TEXT_NODE)
	{
		cursorPosition -= 1;
	}

	return cursorPosition;
};
