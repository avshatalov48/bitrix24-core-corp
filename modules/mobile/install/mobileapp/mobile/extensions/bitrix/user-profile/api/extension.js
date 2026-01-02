/**
 * @module user-profile/api
 */
jn.define('user-profile/api', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { TabType } = require('user-profile/const');
	const { ajaxPublicErrorHandler } = require('error');
	const { guid } = require('utils/guid');

	const cacheTtl = 86400;
	const sharedStorageKey = 'user-profile';
	const appSessionKey = 'appSessionId';

	const getCacheId = (ownerId, selectedTabId) => {
		return `user-profile-tabs-${env.userId}-${ownerId}-${selectedTabId}-${getAppSessionId()}`;
	};

	const getAppSessionId = () => {
		const sessionId = Application.sharedStorage(sharedStorageKey).get(appSessionKey);
		if (sessionId)
		{
			return sessionId;
		}

		const newSessionId = guid();
		Application.sharedStorage(sharedStorageKey).set(appSessionKey, newSessionId);

		return newSessionId;
	};

	const fetchTabs = ({
		ownerId,
		selectedTabId,
	}) => {
		return new Promise((resolve, reject) => {
			getTabsRunActionExecutor(ownerId, selectedTabId)
				.enableJson()
				.setHandler(resolve)
				.call(false)
				.catch(ajaxPublicErrorHandler(reject));
		});
	};

	const getTabsRunActionExecutor = (ownerId, selectedTabId = TabType.COMMON) => {
		const cacheId = getCacheId(ownerId, selectedTabId);

		return new RunActionExecutor(
			'mobile.Profile.getTabs',
			{
				ownerId,
				selectedTabId,
			},
		)
			.setCacheId(cacheId)
			.setCacheTtl(cacheTtl)
		;
	};

	const fetchNewProfileFeatureEnabled = () => {
		return new Promise((resolve, reject) => {
			const handler = (response) => {
				if (response.status === 'success')
				{
					resolve(response.data);

					return;
				}

				resolve(false);
			};

			new RunActionExecutor(
				'mobile.Profile.isNewProfileFeatureEnabled',
				{},
			)
				.enableJson()
				.setHandler(handler)
				.call(true)
				.catch(ajaxPublicErrorHandler(reject));
		});
	};

	module.exports = {
		fetchTabs,
		getTabsRunActionExecutor,
		fetchNewProfileFeatureEnabled,
	};
});
