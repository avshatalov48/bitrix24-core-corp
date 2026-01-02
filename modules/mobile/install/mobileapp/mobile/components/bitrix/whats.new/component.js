(() => {
	const require = (ext) => jn.require(ext);
	const { WhatsNewList } = require('layout/ui/whats-new');
	const { BackgroundUIManager } = require('background/ui-manager');
	const { selectNewCount, selectIsIdleStatus } = require('statemanager/redux/slices/whats-new');
	const store = require('statemanager/redux/store');

	BX.onViewLoaded(() => {
		const count = selectNewCount(store.getState());
		const isIdle = selectIsIdleStatus(store.getState());

		layout.showComponent(WhatsNewList({
			layout,
			hasUnreadNews: count > 0 || isIdle,
		}));

		layout.setListener((eventName) => {
			if (eventName === 'onViewHidden')
			{
				BackgroundUIManager.onCloseActiveComponent();
			}
		});
	});
})();
