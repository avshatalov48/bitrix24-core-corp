(() => {
	const require = (ext) => jn.require(ext);
	// const { followup } = require('call/calls/followup');
	class CallBackgroundAction
	{
		static bindEvents()
		{
			BX.addCustomEvent('callbackground::call::followup', async (data, params = {}) => {
				const { Entry } = await requireLazy('tasks:entry');
				//
				//
				// followup();
				Entry.openTask({ data }, { params });
			});
		}

		constructor()
		{
			CallBackgroundAction.bindEvents();
		}
	}

	return new CallBackgroundAction();
})();
