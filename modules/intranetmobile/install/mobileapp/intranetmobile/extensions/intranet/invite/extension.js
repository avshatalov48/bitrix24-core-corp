/**
 * @module intranet/invite
 */
jn.define('intranet/invite', (require, exports, module) => {
	const { Type } = require('type');
	const { Notify } = require('notify');
	const { Alert } = require('alert');
	const { IntranetInviteAnalytics } = require('intranet/invite/src/analytics');

	/**
	 * @class IntranetInvite
	 */
	class IntranetInvite
	{
		constructor(props)
		{
			this.inviteWidget = props.inviteWidget;
			this.originator = props.originator;
			this.disableAdminConfirm = props.disableAdminConfirm;
			this.registerUrl = props.registerUrl;
			this.adminConfirm = props.adminConfirm;
			this.onInviteSentHandler = props.onInviteSentHandler;
			this.rootStructureSectionId = props.rootStructureSectionId;
			this.analytics = props.analytics;
			this.onViewHiddenWithoutInvitingHandler = props.onViewHiddenWithoutInvitingHandler;
			this.invitedUsers = [];
			this.subscribeOnEvents();
		}

		subscribeOnEvents()
		{
			this.inviteWidget.on('onSendInvite', this.onSendInvite.bind(this));
			this.inviteWidget.on('onUpdateLink', this.onUpdateLink.bind(this));
			this.inviteWidget.on('onAdminConfirm', this.onAdminConfirm.bind(this));
			this.inviteWidget.on('onShareLink', this.onShareLink.bind(this));
			this.inviteWidget.on('onAllowContactListAccess', this.onAllowContactListAccess.bind(this));
			this.inviteWidget.on('onCopyLink', this.onCopyLink.bind(this));
			this.inviteWidget.on('onHelpInvite', this.onHelpInvite.bind(this));
			this.inviteWidget.on('onHelpLink', this.onHelpLink.bind(this));
			this.inviteWidget.on('onViewHidden', this.onViewHidden.bind(this));
		}

		onViewHidden()
		{
			if (this.onViewHiddenWithoutInvitingHandler && this.invitedUsers.length === 0)
			{
				this.onViewHiddenWithoutInvitingHandler();
			}
		}

		onCopyLink()
		{
			this.analytics.sendCopyLinkEvent();
		}

		onAllowContactListAccess()
		{
			this.analytics.sendAllowContactsEvent();
		}

		getInvitePreparedParams(params)
		{
			const preparedParams = {
				validParams: true,
			};
			if (
				!Type.isObject(params)
				|| !Array.isArray(params.recipients)
			)
			{
				preparedParams.validParams = false;
				preparedParams.error = new Error('invalid params');

				return preparedParams;
			}

			preparedParams.countryCodeList = [];
			preparedParams.phoneList = [];
			params.recipients.forEach((item, index) => {
				preparedParams.countryCodeList.push(item.countryCode);
				preparedParams.phoneList.push(item.phone);
			});

			if (preparedParams.phoneList.length <= 0)
			{
				preparedParams.validParams = false;
				preparedParams.error = new Error('phoneList is empty');

				return preparedParams;
			}

			return preparedParams;
		}

		sendInvite(params)
		{
			return new Promise((resolve, reject) => {
				const {
					validParams,
					phoneList,
					countryCodeList,
					error,
				} = this.getInvitePreparedParams(params);

				if (!validParams)
				{
					reject(error);

					return;
				}

				const multipleInvitation = phoneList.length > 1;
				this.analytics.sendSelectFromContactListEvent(multipleInvitation);

				Notify.showIndicatorLoading();
				BX.ajax.runAction('intranet.invite.register', {
					data: {
						fields: {
							PHONE: phoneList,
							PHONE_COUNTRY: countryCodeList,
							DEPARTMENT_ID: this.rootStructureSectionId,
							CONTEXT: 'mobile',
						},
					},
				}).then((response) => {
					const recipientIds = response?.data?.userIdList;
					const errors = response.errors;
					if (errors && errors.length > 0)
					{
						this.analytics.sendInvitationFailedEvent(multipleInvitation, recipientIds);
						Notify.hideCurrentIndicator();
						Alert.alert('', IntranetInvite.getAjaxErrorText(errors));
						reject(response);
					}
					else
					{
						this.analytics.sendInvitationSuccessEvent(recipientIds);
						Notify.showIndicatorSuccess({ hideAfter: 2000 });
						resolve(response);
					}
				}).catch((response) => {
					const recipientIds = response?.data?.userIdList;
					this.analytics.sendInvitationFailedEvent(multipleInvitation, recipientIds);
					Notify.hideCurrentIndicator();
					const errors = response.errors;
					if (errors && errors.length > 0)
					{
						Alert.alert('', IntranetInvite.getAjaxErrorText(errors));
					}
					reject(response);
				});
			});
		}

		onSendInvite(params) {
			if (
				!Type.isObject(params)
				|| !Array.isArray(params.recipients)
			)
			{
				return;
			}

			this.sendInvite(params).then((response) => {
				if (params.recipients.length === response.data.userIdList.length
					&& params.recipients.length === response.data.convertedPhoneNumbers.length)
				{
					this.invitedUsers = params.recipients.map((recipient, index) => ({
						...recipient,
						id: response.data.userIdList[index],
						phone: response.data.convertedPhoneNumbers[index],
					}));
					this.inviteWidget.close(() => {
						if (this.onInviteSentHandler)
						{
							this.onInviteSentHandler(this.invitedUsers);
						}
					});
				}
				else
				{
					console.error('Incorrect data in server response');
				}
			})
				.catch(console.error);
		}

		onShareLink()
		{
			this.analytics.sendShareLinkEvent(this.adminConfirm);
			BX.ajax.runAction('intranet.invite.copyRegisterUrl', {
				data: {},
			})
				.then((response) => {})
				.catch(console.error);
		}

		onHelpInvite()
		{
			Application.openHelpArticle('mh_invite_user', 'invite_user');
		}

		onHelpLink()
		{
			Application.openHelpArticle('mh_invite_user', 'copy_link');
		}

		onUpdateLink()
		{
			BX.ajax.runAction('intranet.invite.setRegisterSettings', {
				data: {
					params: {
						SECRET: Utils.getRandom(8),
					},
				},
			}).then((response) => {
				const errors = response.data.errors;
				if (errors && errors.length > 0)
				{
					Notify.showIndicatorError({
						hideAfter: 10000,
						onTap: Notify.hideCurrentIndicator,
						text: IntranetInvite.getAjaxErrorText(errors),
					});
				}
				else
				{
					this.initRegisterUrl({
						callback: function(value) {
							this.updateLink(value);
						}.bind(this.inviteWidget),
					});
				}
			}).catch((response) => {
				const errors = response.errors;
				if (errors && errors.length > 0)
				{
					Notify.showIndicatorError({
						hideAfter: 2000,
						text: IntranetInvite.getAjaxErrorText(errors),
					});
				}
			});
		}

		onAdminConfirm(value)
		{
			if (this.disableAdminConfirm)
			{
				this.inviteWidget.setAdminConfirm(!value);
				this.setAdminConfirm(!value);

				return;
			}
			this.setAdminConfirm(value);

			BX.ajax.runAction('intranet.invite.setRegisterSettings', {
				data: {
					params: {
						CONFIRM: (value ? 'Y' : 'N'),
					},
				},
			}).then((response) => {
				const errors = response.data.errors;

				if (
					(errors && errors.length > 0)
					|| response.data.result !== 'success'
				)
				{
					this.inviteWidget.setAdminConfirm(!value);
					this.setAdminConfirm(!value);
				}
				else
				{
					// because object property doesn't set when chaning flag in the form
					this.inviteWidget.setAdminConfirm(value);
				}

				if (errors && errors.length > 0)
				{
					Notify.showIndicatorError({
						hideAfter: 10000,
						onTap: Notify.hideCurrentIndicator,
						text: IntranetInvite.getAjaxErrorText(errors),
					});
				}
			}).catch((response) => {
				this.inviteWidget.setAdminConfirm(!value);
				this.setAdminConfirm(!value);

				const errors = response.errors;
				if (errors && errors.length > 0)
				{
					Notify.showIndicatorError({
						hideAfter: 2000,
						text: IntranetInvite.getAjaxErrorText(errors),
					});
				}
			});
		}

		static getAjaxErrorText(errors)
		{
			return errors.map((errorMessage) => {
				if (errorMessage.message)
				{
					return errorMessage.message.replace('<br/>:', '\n').replace('<br/>', '\n');
				}

				return errorMessage.replace('<br/>:', '\n').replace('<br/>', '\n');
			}).filter((errorMessage) => {
				return errorMessage.length > 0;
			}).join('\n');
		}

		initRegisterUrl(params)
		{
			BX.ajax.runAction('intranet.invite.getRegisterUrl', {
				data: {},
			}).then((response) => {
				if (response.status === 'success')
				{
					this.setRegisterUrl(response.data.result);
					if (Type.isObject(params) && Type.isFunction(params.callback))
					{
						params.callback(response.data.result);
					}
				}
			}).catch(console.error);
		}

		setRegisterUrl(value)
		{
			if (Type.isStringFilled(value))
			{
				this.registerUrl = value;
			}
		}

		setAdminConfirm(value)
		{
			if (Type.isBoolean(value))
			{
				this.adminConfirm = value;
			}
		}

		router(eventName, eventResult)
		{
			if (this.handlersList[eventName])
			{
				this.handlersList[eventName].apply(this, [eventResult]);
			}
			else if (this.debug)
			{
				console.info(`IntranetInviteInterface.event.router: skipped event - ${eventName} ${JSON.stringify(eventResult)}`);
			}
		}
	}

	module.exports = { IntranetInvite, IntranetInviteAnalytics };
});
