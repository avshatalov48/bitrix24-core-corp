/**
 * Implements base event object interface
 */
export default class BaseEvent
{
	constructor(
		options: {
			data: {[key: string]: any},
		} = {
			data: {},
		},
	)
	{
		this.type = '';
		this.isTrusted = true;
		this.data = options.data;
		this.defaultPrevented = false;
		this.immediatePropagationStopped = false;
	}

	/**
	 * Prevents default action
	 */
	preventDefault()
	{
		this.defaultPrevented = true;
	}

	/**
	 * Checks that is default action prevented
	 * @return {boolean}
	 */
	isDefaultPrevented(): boolean
	{
		return this.defaultPrevented;
	}

	/**
	 * Stops event immediate propagation
	 */
	stopImmediatePropagation()
	{
		this.immediatePropagationStopped = true;
	}

	/**
	 * Checks that is immediate propagation stopped
	 * @return {boolean}
	 */
	isImmediatePropagationStopped(): boolean
	{
		return this.immediatePropagationStopped;
	}
}