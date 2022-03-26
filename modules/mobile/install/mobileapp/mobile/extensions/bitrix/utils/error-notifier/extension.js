(() =>
{
	/**
	 * @class ErrorNotifier
	 */
	class ErrorNotifier
	{
		static showErrors(errors, options = {})
		{
			errors = Array.isArray(errors) ? errors : [];

			const {
				textBefore,
				addDefaultIfEmpty,
				defaultErrorText
			} = options;

			if (!errors.length && addDefaultIfEmpty === true)
			{
				errors.push({
					message: (defaultErrorText || BX.message('UTILS_ERROR_NOTIFIER_DEFAULT_ERROR'))
				});
			}

			return this.showError(
				(textBefore ? textBefore + '\n' : '')
				+ errors.map(error => error.message).join('\n')
			);
		}

		static showError(text)
		{
			return new Promise((resolve, reject) => {
				navigator.notification.alert(
					text,
					() => resolve(),
					''
				);
			});
		}
	}

	this.ErrorNotifier = ErrorNotifier;
})();
