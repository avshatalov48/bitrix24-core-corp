import type { AnalyticsOptions } from 'ui.analytics';

export class Context
{
	#options: Partial<AnalyticsOptions> = {};

	constructor(options: Partial<AnalyticsOptions> = {})
	{
		this.#options = options;
	}

	update(options: Partial<AnalyticsOptions>): void
	{
		this.#options = { ...this.#options, ...options };
	}

	getOptions(): Partial<AnalyticsOptions>
	{
		return this.#options;
	}
}
