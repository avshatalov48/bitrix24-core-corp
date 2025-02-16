import { Loc } from 'main.core';
import { Core } from 'booking.core';
import type { IStep } from './types';

/**
 * @abstract
 */
export class Step implements IStep
{
	hidden = false;

	constructor(hidden = false)
	{
		this.hidden = hidden;
	}

	get store(): Store | null | undefined
	{
		return this.getStore();
	}

	/**
	 * @protected
	 */
	getStore(): Store | null
	{
		return Core.getStore();
	}

	get labelNext(): string
	{
		return Loc.getMessage('BRCW_BUTTON_CONTINUE');
	}

	get labelBack(): string
	{
		return Loc.getMessage('BRCW_BUTTON_CANCEL');
	}

	async next()
	{
		await this.store.dispatch('resource-creation-wizard/nextStep');
	}

	async back()
	{
		await this.store.dispatch('resource-creation-wizard/prevStep');
	}
}
