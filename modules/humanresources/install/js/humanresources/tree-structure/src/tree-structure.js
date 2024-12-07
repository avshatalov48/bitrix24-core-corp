import { Dom, Type, Tag } from 'main.core';

export class TreeStructure
{
	static renderTo(targetContainer: HTMLElement): void
	{
		if (Type.isDomNode(targetContainer))
		{
			const testNode = Tag.render`<div>Tree</div>`;
			Dom.append(testNode, targetContainer);
		}
	}
}