import { Type } from 'main.core';

export type ComponentOptions = {
	componentName: string,
	componentProps: Object,
};

export class Component
{
	#componentName: string;
	#componentProps: Object;

	constructor(options: ComponentOptions)
	{
		if (!Type.isStringFilled(options.componentName))
		{
			throw new RangeError('BX.Crm.MiniCard.Component: options.componentName must be a string filled');
		}

		if (!Type.isPlainObject(options.componentProps))
		{
			throw new RangeError('BX.Crm.MiniCard.Component: options.componentProps must be a plain object');
		}

		this.#componentName = options.componentName;
		this.#componentProps = options.componentProps;
	}

	componentName(): string
	{
		return this.#componentName;
	}

	componentProps(): Object
	{
		return this.#componentProps;
	}
}
