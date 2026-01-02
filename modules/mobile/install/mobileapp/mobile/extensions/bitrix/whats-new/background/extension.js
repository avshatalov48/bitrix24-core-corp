(() => {
	const require = (ext) => jn.require(ext);
	const extensionData = jnExtensionData.get('whats-new/background');
	const isWhatsNewFeatureEnabled = extensionData?.isWhatsNewFeatureEnabled;

	const { WhatsNewManager } = require('whats-new');

	const API_VERSION = Application.getApiVersion();
	const MINIMAL_API_VERSION = 60;

	if (isWhatsNewFeatureEnabled && API_VERSION >= MINIMAL_API_VERSION)
	{
		WhatsNewManager.init();
	}
})();
