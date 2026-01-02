export class Logger
{
	#prefix: string;

	constructor(prefix: string)
	{
		this.#prefix = prefix;
	}

	warn(message: string, ...params): void
	{
		// eslint-disable-next-line no-console
		console.warn(this.#format(message), ...params);
	}

	error(message: string, ...params): void
	{
		// eslint-disable-next-line no-console
		console.error(this.#format(message), ...params);
	}

	#format(message: string): string
	{
		return `${this.#prefix}: ${message}`;
	}
}

export const logger = new Logger('crm.item-details-component.pagetitle');
