/**
 * @module navigation-loader
 */
jn.define('navigation-loader', (require, exports, module) => {

	const instanceWeakMap = new WeakMap();

	const INFINITY_LOADING_TIMEOUT = 10000; // 10 seconds

	/**
	 * @class NavigationLoader
	 */
	class NavigationLoader
	{
		/**
		 * @private
		 * @param {JSStackNavigation} widget
		 */
		constructor(widget)
		{
			if (instanceWeakMap.has(widget))
			{
				throw new Error('An instance of the navigation loader for this widget has already been created. Use NavigationLoader.getInstance() instead.');
			}

			this.widget = widget;
			this.delay = 0;

			this.loadingCount = 0;
			this.loadingTimeout = 0;
		}

		/**
		 * @param {JSStackNavigation} widget
		 * @return {NavigationLoader}
		 */
		static getInstance(widget = layout)
		{
			if (!instanceWeakMap.has(widget))
			{
				instanceWeakMap.set(widget, new NavigationLoader(widget));
			}

			return instanceWeakMap.get(widget);
		}

		/**
		 * @param {Boolean} status
		 * @param {JSStackNavigation} widget
		 */
		static setLoading(status, widget = layout)
		{
			this.getInstance(widget).setLoading(status);
		}

		static show(widget = layout)
		{
			this.getInstance(widget).setLoading(true);
		}

		static hide(widget = layout)
		{
			this.getInstance(widget).setLoading(false);
		}

		setDelay(delay)
		{
			this.delay = delay;

			return this;
		}

		/**
		 * @param {Boolean} status
		 */
		setLoading(status)
		{
			if (status)
			{
				this.loadingCount++;
			}
			else
			{
				this.loadingCount--;
			}

			this.loadingCount = Math.max(this.loadingCount, 0);

			const changeTitle = (useProgress) => {
				this.widget.setTitle({ useProgress }, true);
			};

			if (status && this.loadingCount === 1)
			{
				if (this.delay)
				{
					clearTimeout(this.loadingTimeout);
					this.loadingTimeout = setTimeout(() => changeTitle(true), this.delay);
				}
				else
				{
					changeTitle(true);
				}
			}
			else if (this.loadingCount === 0)
			{
				if (this.delay)
				{
					clearTimeout(this.loadingTimeout);
				}

				changeTitle(false);
			}

			if (status && this.loadingCount > 0)
			{
				clearTimeout(this.preventInfinityLoaderTimeout);
				this.preventInfinityLoaderTimeout = setTimeout(() => changeTitle(false), INFINITY_LOADING_TIMEOUT + this.delay);
			}
		}

		show()
		{
			this.setLoading(true);
		}

		hide()
		{
			this.setLoading(false);
		}
	}

	module.exports = { NavigationLoader };
});
