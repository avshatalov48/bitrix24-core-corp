import { BannerDispatcher as UIBannerDispatcher } from 'ui.banner-dispatcher';
import { LaunchItemCallback, LaunchItemOptions } from 'ui.auto-launch';
import { Type } from 'main.core';

export const Priority = {
	LOW: 'low',
	NORMAL: 'normal',
	HIGH: 'high',
	CRITICAL: 'critical',
};

export class BannerDispatcher
{
	#isBannerDispatcherDefined: boolean;

	constructor()
	{
		this.#isBannerDispatcherDefined = Type.isPlainObject(UIBannerDispatcher);
	}

	isAvailable(): boolean
	{
		return this.#isBannerDispatcherDefined;
	}

	toQueue(
		callback: LaunchItemCallback,
		priority: $Values<typeof Priority> = Priority.NORMAL,
		options: LaunchItemOptions = {},
	): boolean
	{
		if (!this.isAvailable())
		{
			callback(() => {});

			return false;
		}

		const bannerDispatcher = UIBannerDispatcher[priority];
		if (!this.#isCorrectBannerDispatcher(bannerDispatcher))
		{
			throw new RangeError('Priority property is invalid');
		}

		bannerDispatcher.toQueue(callback, options);

		return true;
	}

	#isCorrectBannerDispatcher(bannerDispatcher: any): boolean
	{
		return Type.isPlainObject(bannerDispatcher)
			&& Object.prototype.hasOwnProperty.call(bannerDispatcher, 'toQueue')
		;
	}
}
