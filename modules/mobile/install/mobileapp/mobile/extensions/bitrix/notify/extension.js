(() => {
	include('InAppNotifier');

	const require = (ext) => jn.require(ext);

	const { md5 } = require('utils/hash');
	const { stringify } = require('utils/string');
	const AppTheme = require('apptheme');

	/**
	 * @class Notify
	 */
	class Notify
	{
		/**
		 * @param {String} message
		 * @param {String} title
		 * @param {Object?} options
		 * @param {String?} options.title
		 * @param {String?} options.message
		 * @param {String?} options.code
		 * @param {String?} options.imageUrl
		 * @param {String?} options.backgroundColor
		 * @param {Number?} options.time
		 * @param {Boolean?} options.blur
		 * @param {Object?} options.data
		 */
		static showMessage(message = '', title = '', options = {})
		{
			message = stringify(message);
			title = stringify(title);

			if (typeof InAppNotifier === 'undefined')
			{
				navigator.notification.alert(message, () => {}, title, 'OK');
			}
			else
			{
				InAppNotifier.showNotification({
					backgroundColor: AppTheme.colors.accentSoftElementBlue1,
					time: 2,
					blur: true,
					...options,
					message: message === '' ? undefined : message,
					title,
				});
			}
		}

		static showUniqueMessage(message = '', title = '', options = {})
		{
			let { code } = options;
			if (!code)
			{
				code = md5({ ...options, message, title });
			}

			this.showMessage(message, title, { ...options, code });
		}

		static showIndicatorSuccess(options = {}, delay = 0)
		{
			options.type = 'success';
			Notify.showIndicatorWithFallback(options, delay);
		}

		static showIndicatorLoading(options = {}, delay = 0)
		{
			options.type = 'loading';

			return Notify.showIndicator(options, delay);
		}

		static showIndicatorError(options, delay = 0)
		{
			options.type = 'error';
			Notify.showIndicatorWithFallback(options, delay);
		}

		static showIndicatorWithFallback(options = {}, delay = 0)
		{
			ifApi(
				29,
				() => Notify.showIndicator(options, delay),
			)
				.elseIf(
					options.fallbackText,
					() => {
						this.hideCurrentIndicator();
						Notify.showMessage(options.fallbackText, options.title);
					},
				);
		}

		static showIndicator(options = { type: 'loading' }, delay = 0)
		{
			const show = (resolve) => {
				dialogs.showLoadingIndicator(options);
				setTimeout(() => {
					resolve();
				}, 150);
			};

			return new Promise((resolve) => {
				if (delay > 0)
				{
					setTimeout(() => {
						show(resolve);
					}, delay);
				}
				else
				{
					show(resolve);
				}
			});
		}

		static hideCurrentIndicator()
		{
			dialogs.hideLoadingIndicator();
		}

		static alert(message, title = '', buttonLabel = 'OK', callback = () => {
		})
		{
			navigator.notification.alert(message, callback, title, buttonLabel);
		}
	}

	this.notify = Notify;
	this.Notify = Notify;
})();

/**
 * @module notify
 */
jn.define('notify', (require, exports, module) => {
	module.exports = {
		Notify: this.Notify,
	};
});
