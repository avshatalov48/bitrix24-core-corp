import {Runtime} from 'main.core';
import defaultOptions from './internal/default-options';

const optionsKey = Symbol('options');

export class Env
{
	static instance = null;

	static getInstance(): Env
	{
		return Env.instance || Env.createInstance();
	}

	static createInstance(options = {}): Env
	{
		Env.instance = new Env(options);
		window.top.BX.Landing.Env.instance = Env.instance;
		return Env.instance;
	}

	constructor(options = {})
	{
		this[optionsKey] = Object.seal(
			Runtime.merge(defaultOptions, options),
		);
	}

	getOptions(): {[key: string]: any}
	{
		return {...this[optionsKey]};
	}

	getType(): string
	{
		return this.getOptions().params.type;
	}
}