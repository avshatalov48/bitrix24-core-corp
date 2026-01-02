/**
 * @module im/messenger/lib/dev/menu/logging/settings-search
 */
jn.define('im/messenger/lib/dev/menu/logging/settings-search', (require, exports, module) => {
	const { LoggerListView } = require('im/messenger/lib/dev/menu/logging/list');

	class LoggingSettingsSearch
	{
		open()
		{
			PageManager.openWidget(
				'layout',
				{
					titleParams: {
						text: 'LoggerManager with search',
						detailText: '',
						imageColor: '#8593c2',
						useLetterImage: true,
					},
				},
			)
				.then((widget) => widget.showComponent(new LoggerListView()))
				.catch((error) => {
					console.error(error);
				})
			;
		}
	}

	module.exports = {
		LoggingSettingsSearch,
	};
});
