/**
 * @module settings/provider
 */
jn.define('settings/provider', (require, exports, module) => {
	/**
	 * @class SettingsProvider
	 */
	class SettingsProvider
	{
		/**
		 *
		 * @param {string} id
		 * @param {string} title
		 * @param {string} subtitle
		 */
		constructor(id, title, subtitle = '')
		{
			this.title = title;
			this.subtitle = subtitle;
			this.id = id;
			this.forms = {};
		}

		getSection()
		{
			// eslint-disable-next-line no-undef
			return new FormItem(
				this.id,
				// eslint-disable-next-line no-undef
				FormItemType.BUTTON,
				this.title,
				this.subtitle,
			)
				.setButtonTransition(true)
				.setCustomParam('providerId', this.id)
				.compile();
		}

		/**
		 * Handler of button tap
		 */
		onButtonTap(item)
		{
			// must be overridden in subclass
		}

		/**
		 * Handles the changes of settings
		 */
		onValueChanged(item)
		{
			// must be overridden in subclass
		}

		/**
		 * Handles the changes of form's
		 */
		onStateChanged(state, formId)
		{
			// must be overridden in subclass
		}

		/**
		 * Opens another one settings form
		 * @param data
		 * @param formId
		 * @param onReady
		 */
		openForm(data, formId, onReady = null)
		{
			data.titleParams = {
				text: data.title ?? '',
				type: 'section',
			};

			delete data.title;

			data.onReady = (obj) => {
				this.forms[formId] = obj;

				if (typeof onReady === 'function')
				{
					onReady(obj);
				}

				obj.setListener((event, data) => {
					if (event === 'onItemChanged')
					{
						if (data.type === 'button')
						{
							this.onButtonTap(data);
						}
						else
						{
							this.onValueChanged(data);
						}
					}
					else
					{
						this.onStateChanged(event, formId);
					}

					this.listener(event, data, formId);
				});
			};

			PageManager.openWidget('form', data);
		}

		/**
		 * Global event listener
		 * If you are going to override this method in subclass
		 * don't forget call super.listener()
		 * @param event
		 * @param data
		 * @param formId
		 */
		listener(event, data, formId)
		{
			if (event === 'onViewRemoved')
			{
				console.info(`SettingsProvider.listener: onViewRemoved - %c${formId}`, 'color: red; font-weight: bold');
				this.forms[formId] = null;
				delete this.forms[formId];
			}
		}
	}

	module.exports = { SettingsProvider };
});
