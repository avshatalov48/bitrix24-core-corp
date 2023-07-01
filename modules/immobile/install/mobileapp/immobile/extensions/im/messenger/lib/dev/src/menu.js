/**
 * @module im/messenger/lib/dev/menu
 */
jn.define('im/messenger/lib/dev/menu', (require, exports, module) => {

	const { ChatDialog } = require('im/messenger/lib/dev/chat-dialog');
	const { ChatDialogBenchmark } = require('im/messenger/lib/dev/chat-dialog-benchmark');
	const { BannerButton } = require('layout/ui/banners/banner-button');
	class DeveloperMenu extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.widget = null;
		}

		render()
		{
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

			return View(
				{},
				chatDialogVisualTest,
				chatDialogBenchmark,
			);
		}

		show()
		{
			PageManager.openWidget(
				'layout',
				{
					title: 'Messenger developer menu',
					onReady: layoutWidget =>
					{
						this.widget = layoutWidget;
						this.widget.showComponent(new DeveloperMenu());
					},
					onError: error => reject(error),
				});
		}
	}

	function showDeveloperMenu()
	{
		new DeveloperMenu().show();
	}

	window.messengerDev = {
		playground: {},
	}

	module.exports = {
		showDeveloperMenu,
	};
});
