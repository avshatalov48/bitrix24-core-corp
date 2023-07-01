/**
 * @module calendar/model/sharing
 */
jn.define('calendar/model/sharing', (require, exports, module) => {

	const { withCurrentDomain } = require('utils/url');

	const RestrictionStatus = {
		ENABLE: 'enable',
		DISABLE: 'disable',
	};

	const Status = {
		ENABLE: 'enable',
		DISABLE: 'disable',
		UNDEFINED: 'undefined',
	};

	const state = {
		status: Status.UNDEFINED,
		publicShortUrl: withCurrentDomain(),
		restrictionStatus: RestrictionStatus.DISABLE,
	};

	/**
	 * @class Sharing
	 */
	class Sharing
	{
		setFields(props)
		{
			const status = BX.prop.getString(props, 'status', null);
			const publicShortUrl = BX.prop.getString(props, 'publicShortUrl', null);
			const restrictionStatus = BX.prop.getString(props, 'restrictionStatus', null);

			const fields = this.validate({status, publicShortUrl, restrictionStatus});

			this.setStatus(fields.status);
			this.setPublicShortUrl(fields.publicShortUrl);
			this.setRestrictionStatus(fields.restrictionStatus);
		}

		getFieldsValues()
		{
			return {
				status: this.status,
				publicShortUrl: this.publicShortUrl,
				restrictionStatus: this.restrictionStatus,
			}
		}

		validate(props)
		{
			const result = {}
			result.status = props.status && Object.values(Status).includes(props.status)
				? props.status
				: state.status;

			result.restrictionStatus = props.restrictionStatus && Object.values(RestrictionStatus).includes(props.restrictionStatus)
				? props.restrictionStatus
				: state.restrictionStatus;

			result.publicShortUrl = props.publicShortUrl && props.publicShortUrl.length > 0
				? props.publicShortUrl
				: state.publicShortUrl;

			return result;
		}

		setStatus(value)
		{
			this.status = value.toString();
		}

		setRestrictionStatus(value)
		{
			this.restrictionStatus = value.toString();
		}

		setPublicShortUrl(value)
		{
			this.publicShortUrl = value.toString();
		}

		getStatus()
		{
			return this.status;
		}

		getRestrictionStatus()
		{
			return this.restrictionStatus;
		}

		getPublicShortUrl()
		{
			return this.publicShortUrl;
		}
	}

	module.exports = {
		ModelSharing: Sharing,
		ModelSharingStatus: Status,
		ModelRestrictionStatus: RestrictionStatus,
	};
});

