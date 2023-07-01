/**
 * @module calendar/sharing
 */
jn.define('calendar/sharing', (require, exports, module) => {

	const { SharingAjax } = jn.require('calendar/ajax');
	const { ModelSharing, ModelSharingStatus, ModelRestrictionStatus } = jn.require('calendar/model/sharing');

	/**
	 * @class Sharing
	 */
	class Sharing
	{
		constructor()
		{
			this.model = new ModelSharing();
		}

		getModel()
		{
			return this.model;
		}

		init()
		{
			return new Promise((resolve) => {
				SharingAjax.isEnabled().then((response) => {
					if (response.errors && response.errors.length)
					{
						// do noting
					}
					else
					{
						const fields = this.resolveAjaxResponse(response);
						this.model.setFields(fields);
					}

					resolve(response)
				})
			});
		}

		on()
		{
			return new Promise((resolve) => {
				SharingAjax.enable().then((response) => {
					if (response.errors && response.errors.length)
					{
						// do noting
					}
					else
					{
						const {status, publicShortUrl} = this.resolveAjaxResponse(response);
						this.model.setFields({status, publicShortUrl});
					}

					resolve(response)
				})
			});
		}

		off()
		{
			return new Promise((resolve) => {
				SharingAjax.disable().then((response) => {
					if (response.errors && response.errors.length)
					{
						// do noting
					}
					else
					{
						const {status} = this.resolveAjaxResponse(response);
						this.model.setFields({status});
					}

					resolve(response)
				})
			});
		}

		isOn()
		{
			return this.model.getStatus() === ModelSharingStatus.ENABLE;
		}

		isRestriction()
		{
			return this.model.getRestrictionStatus() === ModelRestrictionStatus.ENABLE;
		}

		resolveAjaxResponse(response)
		{
			const result = {};
			result.publicShortUrl = BX.prop.getString(response.data.sharing, 'shortUrl', null);
			result.restrictionStatus = BX.prop.getString(response.data.sharing, 'isRestriction', true)
				? ModelRestrictionStatus.ENABLE
				: ModelRestrictionStatus.DISABLE
			;
			result.status = BX.prop.getBoolean(response.data.sharing, 'isEnabled', false)
				? ModelSharingStatus.ENABLE
				: ModelSharingStatus.DISABLE
			;
			return result
		}
	}

	module.exports = { Sharing };
});
