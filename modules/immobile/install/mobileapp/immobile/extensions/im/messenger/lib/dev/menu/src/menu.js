/**
 * @module im/messenger/lib/dev/menu/menu
 */
jn.define('im/messenger/lib/dev/menu/menu', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { BannerButton } = require('layout/ui/banners/banner-button');

	const { MessengerParams } = require('im/messenger/lib/params');

	const { DeveloperSettingsMenu } = require('im/messenger/lib/dev/menu/developer-settings');
	const { LoggingSettingsSearch } = require('im/messenger/lib/dev/menu/logging/settings-search');
	const { LoggingSettings } = require('im/messenger/lib/dev/menu/logging/settings');
	const { ChatDialog } = require('im/messenger/lib/dev/menu/chat-dialog');
	const { ChatDialogBenchmark } = require('im/messenger/lib/dev/menu/chat-dialog-benchmark');
	const { VuexManagerPlayground } = require('im/messenger/lib/dev/menu/vuex-manager');
	const { Playground } = require('im/messenger/lib/dev/menu/playground');
	const { DialogSnippets } = require('im/messenger/lib/dev/menu/dialog-snippets');

	class DeveloperMenu extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.widget = null;
		}

		render()
		{
			return ScrollView(
				{},
				View(
					{},
					...this.getButtonsData(),
				),
			);
		}

		/**
		* @return {Array<LayoutComponent>}
		*/
		getButtonsData()
		{
			const developerSettingsButton = BannerButton({
				title: 'Dev',
				description: 'Developer settings',
				backgroundColor: AppTheme.colors.accentSoftBlue1,
				onClick: () => {
					const developerSettings = new DeveloperSettingsMenu();
					developerSettings.open();

					window.messengerDev.playground.developerSettings = developerSettings;
				},
			});

			const logSettingsSearchButton = BannerButton({
				title: 'LoggerManager with search',
				description: '',
				backgroundColor: AppTheme.colors.accentSoftBlue2,
				onClick: () => {
					const loggingSettingsSearch = new LoggingSettingsSearch();
					loggingSettingsSearch.open();

					window.messengerDev.playground.loggingSettingsSearch = loggingSettingsSearch;
				},
			});

			const logSettingsButton = BannerButton({
				title: 'LoggerManager form',
				description: '',
				backgroundColor: AppTheme.colors.accentSoftBlue2,
				onClick: () => {
					const loggingSettings = new LoggingSettings();
					loggingSettings.open();

					window.messengerDev.playground.loggingSettings = loggingSettings;
				},
			});

			const chatDialogVisualTest = BannerButton({
				title: 'All types of messages',
				description: 'Visual testing of all possible messages',
				backgroundColor: AppTheme.colors.accentSoftBlue1,
				onClick: () => {
					const chatDialogVisualTest = new ChatDialog();
					chatDialogVisualTest.open();

					window.messengerDev.playground.chatDialogVisualTest = chatDialogVisualTest;
				},
			});

			const chatDialogBenchmark = BannerButton({
				title: 'Scroll benchmark',
				description: 'An endless, random list of messages',
				backgroundColor: AppTheme.colors.accentSoftBlue1,
				onClick: () => {
					const chatDialogBenchmark = new ChatDialogBenchmark();
					chatDialogBenchmark.open();

					window.messengerDev.playground.chatDialogBenchmark = chatDialogBenchmark;
				},
			});

			const vuexPlaygroundButton = BannerButton({
				title: 'VuexManager',
				description: 'Store manager playground',
				backgroundColor: AppTheme.colors.accentSoftBlue1,
				onClick: () => {
					const vuexPlayground = new VuexManagerPlayground();
					vuexPlayground.open();

					window.messengerDev.playground.vuexPlayground = vuexPlayground;
				},
			});

			const playground = BannerButton({
				title: 'JN Layout',
				description: 'JN Layout playground',
				backgroundColor: AppTheme.colors.accentSoftBlue1,
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
				backgroundColor: AppTheme.colors.accentSoftBlue1,
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

			let unitTestDashboard = null;
			if (MessengerParams.get('ENABLE_DEV_WORKSPACE', 'N') === 'Y')
			{
				unitTestDashboard = BannerButton({
					title: 'Unit Tests',
					description: 'Messenger Tests',
					backgroundColor: AppTheme.colors.accentSoftBlue1,
					onClick: async () => {
						try
						{
							const result = await requireLazy('imdev:entry/unit-tests');
							const { UnitTestsEntry } = result;
							UnitTestsEntry.openTests();
						}
						catch (e)
						{
							console.error(e);
						}
					},
				});
			}

			const consoleButton = BannerButton({
				title: 'Console',
				description: 'for standalone debug',
				backgroundColor: AppTheme.colors.accentSoftBlue1,
				onClick: async () => {
					const { Console } = await requireLazy('im:messenger/lib/dev/tools');
					Console.open();
				},
			});

			return [
				developerSettingsButton,
				this.renderButtonSection([logSettingsSearchButton, logSettingsButton], 'Logging'),
				consoleButton,
				// chatDialogVisualTest,
				// chatDialogBenchmark,
				vuexPlaygroundButton,
				playground,
				dialog,
				unitTestDashboard,
			].map((button) => this.renderMenuItem(button));
		}

		renderMenuItem(item)
		{
			return View(
				{
					style: {
						padding: '1%',
					},
				},
				item,
			);
		}

		renderButtonSection(buttons, sectionName, nestingLevel = '4%')
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.accentSoftBlue1,
						paddingLeft: nestingLevel,
						borderRadius: 12,
					},
				},
				Text({
					style: {
						fontSize: 20,
						fontWeight: 'bold',
						marginTop: '3%',
						marginBottom: '3%',
					},
					text: sectionName,
					value: sectionName,
				}),
				...buttons,
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
