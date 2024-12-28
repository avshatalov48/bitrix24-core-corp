/**
 * @module user/profile/src/profile
 */
jn.define('user/profile/src/profile', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { Alert, ButtonType } = require('alert');
	const { Loc } = require('loc');

	class Profile
	{
		constructor(userId = 0, form, items = [], sections = [])
		{
			this.form = form;
			this.isBackdrop = false;
			this.userId = userId;
			this.formFields = items;
			this.formSections = sections;
			this.fieldsValues = [];
			this.canUseTelephony = false;
			this.loadPlaceholder();
		}

		init()
		{
			this.form.setListener((event, data) => this.listener(event, data));
			this.load();
		}

		loadPlaceholder()
		{
			BX.onViewLoaded(() => this.form.setItems(this.formFields, this.formSections));
		}

		load()
		{
			this.request().then(() => this.render()).catch((e) => this.error(e));
		}

		request()
		{
			return new Promise((resolve, reject) => {
				BX.rest.callBatch({
					formData: ['mobile.user.get', { filter: { ID: this.userId }, image_resize: 'small' }],
					formStructure: ['mobile.form.profile'],
					canUseTelephony: ['mobile.user.canUseTelephony'],
				}, async (response) => {
					if (response.formStructure.error()
						|| response.formData.error())
					{
						reject(response);

						return;
					}

					if (response.formData.answer.result.length === 0)
					{
						await this.showNoPermissionsAlert();
						this.form.close();
						resolve();

						return;
					}

					this.formFields = response.formStructure.answer.result.fields;
					this.formSections = response.formStructure.answer.result.sections;
					this.fieldsValues = response.formData.answer.result[0];
					this.canUseTelephony = response.canUseTelephony.answer.result;
					resolve();
				});
			});
		}

		showNoPermissionsAlert = async () => {
			const title = Loc.getMessage('PROFILE_PERMISSIONS_ALERT_TITLE');

			return new Promise((resolve) => {
				Haptics.impactLight();
				Alert.confirm(
					title,
					null,
					[
						{
							type: ButtonType.DEFAULT,
							onPress: resolve,
						},
					],
				);
			});
		};

		render()
		{
			// should override
		}

		listener(event, data)
		{
			if (this[event] && typeof this[event] === 'function')
			{
				this[event].apply(this, [data]);
			}
		}

		error(message)
		{
			console.error(message);
			let errorMessage = BX.message('SOMETHING_WENT_WRONG');
			if (message && typeof message === 'string')
			{
				errorMessage = message;
			}

			navigator.notification.alert(
				errorMessage,
				() => { /* form.back(); */
				},
				BX.message('ERROR'),
				'OK',
			);
		}
	}

	module.exports = { Profile };
});
