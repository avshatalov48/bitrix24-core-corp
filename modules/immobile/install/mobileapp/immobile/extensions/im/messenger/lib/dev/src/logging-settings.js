/* eslint-disable no-console */
/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/lib/dev/logging-settings
 */
jn.define('im/messenger/lib/dev/logging-settings', (require, exports, module) => {
	const { Type } = require('type');
	const AppTheme = require('apptheme');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { Logger, LogType } = require('utils/logger');

	class LoggingSettings
	{
		constructor()
		{
			this.titleParams = {
				text: 'LoggerManager settings',
				detailText: '',
				imageColor: AppTheme.colors.accentBrandBlue,
				useLetterImage: true,
			};

			this.loggerManager = LoggerManager.getInstance();
			this.form = null;
		}

		open()
		{
			PageManager.openWidget(
				'form',
				{
					titleParams: this.titleParams,
				},
			)
				.then(this.onWidgetReady.bind(this))
				.catch((error) => {
					console.error(error);
				})
			;
		}

		onWidgetReady(form)
		{
			this.form = form;
			this.render();
			this.form.setListener((event, data) => {
				if (event !== 'onItemChanged')
				{
					return;
				}

				const {
					id,
					value,
				} = data;

				const name = id;
				if (value === true)
				{
					console.log('Logger', name, 'enabled');
					const supportedTypes = Logger.getSupportedLogTypes();
					supportedTypes.forEach((type) => {
						this.loggerManager.getLogger(name).enable(type);
					});
				}
				else
				{
					console.log('Logger', name, 'disabled');
					const supportedTypes = Logger.getSupportedLogTypes();
					supportedTypes.forEach((type) => {
						if ([LogType.ERROR, LogType.TRACE].includes(type))
						{
							return;
						}

						this.loggerManager.getLogger(name).disable(type);
					});
				}
			});
		}

		render()
		{
			let sections = [];
			const checkboxList = [];
			this.loggerManager.loggerCollection.forEach((logger, name) => {
				const groupSplit = name.split('--');
				const isGroupLogger = groupSplit.length === 2 && Type.isStringFilled(groupSplit[0]);
				let displayedName = name;
				let sectionCode = name;
				if (isGroupLogger)
				{
					sectionCode = name.split('--')[0];
					displayedName = name.split('--')[1];
				}

				checkboxList.push({
					id: `${name}`,
					type: 'switch',
					title: displayedName,
					sectionCode: sectionCode,
					value: logger.enabledLogTypes.has('log'),
				});

				sections.push({
					id: sectionCode,
					title: sectionCode,
				});
			});

			sections.sort((a, b) => {
				if (a.title.toLowerCase() < b.title.toLowerCase())
				{
					return -1;
				}

				if (a.title.toLowerCase() > b.title.toLowerCase())
				{
					return 1;
				}

				return 0;
			});

			checkboxList.sort((a, b) => {
				if (a.title.toLowerCase() < b.title.toLowerCase())
				{
					return -1;
				}

				if (a.title.toLowerCase() > b.title.toLowerCase())
				{
					return 1;
				}

				return 0;
			});

			console.log(sections, checkboxList);
			this.form.setItems(checkboxList, sections);
		}
	}

	module.exports = {
		LoggingSettings,
	};
});
