/**
 * @module calendar/ajax/sync
 */
jn.define('calendar/ajax/sync', (require, exports, module) => {
	const { BaseAjax } = require('calendar/ajax/base');

	const SyncActions = {
		GET_SYNC_INFO: 'getSyncInfo',
		GET_SECTIONS_FOR_PROVIDER: 'getSectionsForProvider',
		CHANGE_SECTION_STATUS: 'changeSectionStatus',
		DEACTIVATE_CONNECTION: 'deactivateConnection',
		CREATE_GOOGLE_CONNECTION: 'createGoogleConnection',
		CREATE_OFFICE365_CONNECTION: 'createOffice365Connection',
		CREATE_ICLOUD_CONNECTION: 'createIcloudConnection',
		SYNC_ICLOUD_CONNECTION: 'syncIcloudConnection',
		CLEAR_SUCCESSFUL_CONNECTION_NOTIFIER: 'clearSuccessfulConnectionNotifier',
		UPDATE_CONNECTIONS: 'updateConnections',
		GET_CONNECTION_LINK: 'getConnectionLink',
	};

	/**
	 * @class SharingAjax
	 */
	class SyncAjax extends BaseAjax
	{
		getEndpoint()
		{
			return 'calendarmobile.sync';
		}

		/**
		 * @return {Promise<Object, void>}
		 */
		getSyncInfo()
		{
			return this.fetch(SyncActions.GET_SYNC_INFO);
		}

		/**
		 * @param connectionId
		 * @param type
		 * @returns {Promise<Object, void>}
		 */
		getSectionsForProvider(connectionId, type)
		{
			return this.fetch(SyncActions.GET_SECTIONS_FOR_PROVIDER, {
				connectionId,
				type,
			});
		}

		/**
		 * @param sectionId
		 * @param status
		 * @returns {Promise<Object, void>}
		 */
		changeSectionStatus(sectionId, status)
		{
			return this.fetch(SyncActions.CHANGE_SECTION_STATUS, {
				sectionId,
				status,
			});
		}

		/**
		 * @param connectionId
		 * @returns {Promise<Object, void>}
		 */
		deactivateConnection(connectionId)
		{
			return this.fetch(SyncActions.DEACTIVATE_CONNECTION, {
				connectionId,
			});
		}

		/**
		 *
		 * @returns {Promise<Object, void>}
		 */
		createGoogleConnection()
		{
			return this.fetch(SyncActions.CREATE_GOOGLE_CONNECTION);
		}

		/**
		 *
		 * @returns {Promise<Object, void>}
		 */
		createOffice365Connection()
		{
			return this.fetch(SyncActions.CREATE_OFFICE365_CONNECTION);
		}

		/**
		 *
		 * @param appleId
		 * @param appPassword
		 * @returns {Promise<Object, void>}
		 */
		createIcloudConnection(appleId, appPassword)
		{
			return this.fetch(SyncActions.CREATE_ICLOUD_CONNECTION, {
				appleId,
				appPassword,
			});
		}

		/**
		 *
		 * @param connectionId
		 * @returns {Promise<Object, void>}
		 */
		syncIcloudConnection(connectionId)
		{
			return this.fetch(SyncActions.SYNC_ICLOUD_CONNECTION, {
				connectionId,
			});
		}

		/**
		 *
		 * @param type
		 * @returns {Promise<Object, void>}
		 */
		clearSuccessfulConnectionNotifier(type)
		{
			return this.fetch(SyncActions.CLEAR_SUCCESSFUL_CONNECTION_NOTIFIER, {
				type,
			});
		}

		/**
		 *
		 * @returns {Promise<Object, void>}
		 */
		updateConnections()
		{
			return this.fetch(SyncActions.UPDATE_CONNECTIONS);
		}

		/**
		 *
		 * @param type
		 * @returns {Promise<Object, void>}
		 */
		getConnectionLink(type)
		{
			return this.fetch(SyncActions.GET_CONNECTION_LINK, {
				type,
			});
		}
	}

	module.exports = {
		SyncAjax: new SyncAjax(),
	};
});
