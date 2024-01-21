/**
 * @module im/messenger/lib/dev/menu
 */
jn.define('im/messenger/lib/dev/menu', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { LoggingSettings } = require('im/messenger/lib/dev/logging-settings');
	const { ChatDialog } = require('im/messenger/lib/dev/chat-dialog');
	const { ChatDialogBenchmark } = require('im/messenger/lib/dev/chat-dialog-benchmark');
	const { VuexManagerPlayground } = require('im/messenger/lib/dev/vuex-manager');
	const { BannerButton } = require('layout/ui/banners/banner-button');
	const { Playground } = require('im/messenger/lib/dev/playground');
	const { DialogSnippets } = require('im/messenger/lib/dev/dialog-snippets');
	class DeveloperMenu extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.widget = null;
		}

		render()
		{
			const logSettingsButton = BannerButton({
				title: 'Logging',
				description: 'LoggerManager settings',
				backgroundColor: AppTheme.colors.accentBrandBlue,
				onClick: () => {
					const loggingSettings = new LoggingSettings();
					loggingSettings.open();

					window.messengerDev.playground.loggingSettings = loggingSettings;
				},
			});

			const chatDialogVisualTest = BannerButton({
				title: 'All types of messages',
				description: 'Visual testing of all possible messages',
				backgroundColor: '#C3F2FF',
				onClick: () => {
					const chatDialogVisualTest = new ChatDialog();
					chatDialogVisualTest.open();

					window.messengerDev.playground.chatDialogVisualTest = chatDialogVisualTest;
				},
			});

			const chatDialogBenchmark = BannerButton({
				title: 'Scroll benchmark',
				description: 'An endless, random list of messages',
				backgroundColor: '#ffb3b9',
				onClick: () => {
					const chatDialogBenchmark = new ChatDialogBenchmark();
					chatDialogBenchmark.open();

					window.messengerDev.playground.chatDialogBenchmark = chatDialogBenchmark;
				},
			});

			const vuexPlaygroundButton = BannerButton({
				title: 'VuexManager',
				description: 'Store playground',
				backgroundColor: '#3eaf7c',
				onClick: () => {
					const vuexPlayground = new VuexManagerPlayground();
					vuexPlayground.open();

					window.messengerDev.playground.vuexPlayground = vuexPlayground;
				},
			});

			const playground = BannerButton({
				title: 'Playground',
				description: 'JN Layout playground',
				backgroundColor: '#dc65e0',
				onClick: () => {
					PageManager.openWidget(
						'layout',
						{
							title: 'Messenger Playground',
							onReady: (layoutWidget) => {
								layoutWidget.showComponent(new Playground());
							},
						},
					);
				},
			});

			const dialog = BannerButton({
				title: 'Dialog',
				description: 'Dialog snippets',
				backgroundColor: '#9b2d30',
				onClick: () => {
					PageManager.openWidget(
						'layout',
						{
							title: 'Messenger Playground',
							onReady: (layoutWidget) => {
								layoutWidget.showComponent(new DialogSnippets({}));
							},
						},
					);
				},
			});

			return View(
				{},
				logSettingsButton,
				// chatDialogVisualTest,
				// chatDialogBenchmark,
				vuexPlaygroundButton,
				playground,
				dialog,
			);
		}

		show()
		{
			PageManager.openWidget(
				'layout',
				{
					title: 'Messenger developer menu',
					onReady: (layoutWidget) => {
						this.widget = layoutWidget;
						this.widget.showComponent(new DeveloperMenu());
					},
					onError: (error) => reject(error),
				},
			);
		}
	}

	function showDeveloperMenu()
	{
		new DeveloperMenu().show();
	}

	window.messengerDev = {
		playground: {},
	};

	module.exports = {
		showDeveloperMenu,
	};
});
