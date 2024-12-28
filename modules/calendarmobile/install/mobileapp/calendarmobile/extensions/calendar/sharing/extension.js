/**
 * @module calendar/sharing
 */
jn.define('calendar/sharing', (require, exports, module) => {
	const { SharingAjax } = require('calendar/ajax');
	const { ModelSharing, ModelSharingStatus, SharingContext } = require('calendar/model/sharing');

	/**
	 * @class Sharing
	 */
	class Sharing
	{
		constructor(props = {})
		{
			this.model = new ModelSharing(props.type);

			if (props.sharingInfo)
			{
				this.initFromProps(props.sharingInfo);
			}
		}

		getModel()
		{
			return this.model;
		}

		initFromProps(sharingInfo)
		{
			const data = {
				shortUrl: BX.prop.getString(sharingInfo, 'shortUrl', null),
				isEnabled: BX.prop.getBoolean(sharingInfo, 'isEnabled', false),
				userInfo: BX.prop.getObject(sharingInfo, 'userInfo', {}),
				settings: BX.prop.getObject(sharingInfo, 'settings', {}),
				options: BX.prop.getObject(sharingInfo, 'options', {}),
			};

			this.model.setFields(data);
		}

		init()
		{
			return new Promise((resolve) => {
				// eslint-disable-next-line promise/catch-or-return
				SharingAjax.isEnabled().then((response) => {
					if (!(response.errors && response.errors.length > 0))
					{
						const fields = this.resolveAjaxResponse(response);
						this.model.setFields(fields);
					}

					resolve(response);
				});
			});
		}

		initCrm(entityTypeId, entityId)
		{
			return new Promise((resolve) => {
				// eslint-disable-next-line promise/catch-or-return
				SharingAjax.initCrm({ entityTypeId, entityId }).then((response) => {
					if (!(response.errors && response.errors.length > 0))
					{
						const fields = this.resolveAjaxResponse(response);
						this.model.setFields(fields);
					}

					resolve(response);
				});
			});
		}

		on()
		{
			return new Promise((resolve) => {
				// eslint-disable-next-line promise/catch-or-return
				SharingAjax.enable().then((response) => {
					if (!(response.errors && response.errors.length > 0))
					{
						const fields = this.resolveAjaxResponse(response);
						this.model.setFields(fields);
					}

					resolve(response);
				});
			});
		}

		off()
		{
			return new Promise((resolve) => {
				// eslint-disable-next-line promise/catch-or-return
				SharingAjax.disable().then((response) => {
					if (!(response.errors && response.errors.length > 0))
					{
						const fields = this.resolveAjaxResponse(response);
						this.model.setFields(fields);
						this.model.clearLinks();
					}

					resolve(response);
				});
			});
		}

		isOn()
		{
			return this.model.getStatus() === ModelSharingStatus.ENABLE;
		}

		resolveAjaxResponse(response)
		{
			return response.data;
		}
	}

	module.exports = { Sharing, SharingContext };
});
