import { Type } from 'main.core';

export function hide(element: any): void
{
	if (Type.isElementNode(element))
	{
		BX.hide(element);
	}
}

export function show(element: any): void
{
	if (Type.isElementNode(element))
	{
		BX.show(element);
	}
}
