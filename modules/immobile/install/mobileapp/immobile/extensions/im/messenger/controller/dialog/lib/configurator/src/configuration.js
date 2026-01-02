/**
 * @module im/messenger/controller/dialog/lib/configurator/configuration
 */
jn.define('im/messenger/controller/dialog/lib/configurator/configuration', (require, exports, module) => {
	const defaultConfig = {
		relatedEntity: {
			type: 'default',
			id: 0,
			customData: {},
		},
		header: {
			title: {
				params: {},
				controller: {
					extensionName: '',
					className: '',
				},
			},
			buttons: {
				controller: {
					extensionName: '',
					className: '',
				},
			},
		},
		sidebar: {
			enabled: true,
			params: {},
		},
		message: {
			contextMenu: {
				controller: {
					extensionName: '',
					className: '',
				},
			},
		},
	};

	const baseDialogConfig = {
		relatedEntity: defaultConfig.relatedEntity,
		header: {
			title: {
				params: {},
				controller: {
					extensionName: 'im:messenger/controller/dialog/lib/header/title/title',
					className: 'HeaderTitle',
				},
			},
			buttons: {
				controller: {
					extensionName: 'im:messenger/controller/dialog/lib/header/buttons/buttons',
					className: 'HeaderButtons',
				},
			},
		},
		sidebar: {
			enabled: true,
			params: {},
		},
		message: {
			contextMenu: {
				controller: {
					extensionName: 'im:messenger/controller/dialog/lib/message-menu',
					className: 'MessageMenu',
				},
			},
		},
	};

	module.exports = {
		defaultConfig,
		baseDialogConfig,
	};
});
