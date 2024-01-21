/**
 * @module calendar/model/sharing
 */
jn.define('calendar/model/sharing', (require, exports, module) => {
	const { withCurrentDomain } = require('utils/url');
	const { Settings } = require('calendar/model/sharing/settings');

	const Context = {
		CRM: 'crm',
		CALENDAR: 'calendar',
	};

	const ModelRestrictionStatus = {
		ENABLE: 'enable',
		DISABLE: 'disable',
	};

	const ModelSharingStatus = {
		ENABLE: 'enable',
		DISABLE: 'disable',
		UNDEFINED: 'undefined',
	};

	const state = {
		status: ModelSharingStatus.UNDEFINED,
		publicShortUrl: withCurrentDomain(),
		restrictionStatus: ModelRestrictionStatus.DISABLE,
		context: Context.CALENDAR,
	};

	/**
	 * @class ModelSharing
	 */
	class ModelSharing
	{
		/**
		 * @param context {string}
		 */
		constructor(context)
		{
			this.setContext(context);
		}

		setFields(props)
		{
			const { isEnabled, isRestriction, shortUrl, settings } = props;

			const status = (isEnabled === true)
				? ModelSharingStatus.ENABLE
				: ModelSharingStatus.DISABLE;

			const restrictionStatus = (isRestriction === true)
				? ModelRestrictionStatus.ENABLE
				: ModelRestrictionStatus.DISABLE;

			this.setStatus(status);
			this.setPublicShortUrl(shortUrl);
			this.setRestrictionStatus(restrictionStatus);
			this.setSettings(new Settings(settings));
		}

		getFieldsValues()
		{
			return {
				status: this.status,
				context: this.context,
				publicShortUrl: this.publicShortUrl,
				restrictionStatus: this.restrictionStatus,
				settings: this.settings,
			};
		}

		setStatus(value)
		{
			this.status = value && Object.values(ModelSharingStatus).includes(value)
				? value.toString()
				: state.status;
		}

		setContext(value)
		{
			this.context = value && Object.values(Context).includes(value)
				? value.toString()
				: state.context;
		}

		setRestrictionStatus(value)
		{
			this.restrictionStatus = value && Object.values(ModelRestrictionStatus).includes(value)
				? value.toString()
				: state.restrictionStatus;
		}

		setPublicShortUrl(value)
		{
			this.publicShortUrl = value && value.length > 0
				? value.toString()
				: state.publicShortUrl;
		}

		setSettings(settings)
		{
			this.settings = settings;
		}

		/**
		 * @returns {string}
		 */
		getStatus()
		{
			return this.status;
		}

		/**
		 * @returns {string}
		 */
		getContext()
		{
			return this.context;
		}

		/**
		 * @returns {Settings}
		 */
		getSettings()
		{
			return this.settings;
		}

		/**
		 * @returns {string}
		 */
		getRestrictionStatus()
		{
			return this.restrictionStatus;
		}

		/**
		 * @returns {string}
		 */
		getPublicShortUrl()
		{
			return this.publicShortUrl;
		}
	}

	module.exports = {
		ModelSharing,
		ModelSharingStatus,
		ModelRestrictionStatus,
		SharingContext: Context,
	};
});
