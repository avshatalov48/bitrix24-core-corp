"use strict";

if (typeof IntranetInvite != 'undefined' && typeof IntranetInvite.cleaner != 'undefined')
{
	IntranetInvite.cleaner();
}

var IntranetInvite = {
	event: {}
};

IntranetInvite.init = function()
{
	this.canInvite = false;
	this.registerUrl = '';
	this.rootStructureSectionId = 1;
	this.adminConfirm = false;

	if (this.isRecentComponent())
	{
		this.setRegisterUrl(BX.componentParameters.get('INTRANET_INVITATION_REGISTER_URL', ''));
	}
	else
	{
		this.initRegisterUrl();
	}

	BX.addCustomEvent("onSendInvite", this.onSendInvite.bind(this));
};

IntranetInvite.isRecentComponent = function()
{
	if (BX.componentParameters.get('COMPONENT_CODE') === "im.messenger")
	{
		return true;
	}

	if (BX.componentParameters.get('COMPONENT_CODE') === "im.recent")
	{
		return true;
	}

	if (BX.componentParameters.get('COMPONENT_CODE') === "im.openlines.recent")
	{
		return true;
	}

	return false;
};

IntranetInvite.setCanInvite = function(value)
{
	this.canInvite = !!value;
};

IntranetInvite.getCanInvite = function()
{
	return !!this.canInvite;
};

IntranetInvite.setRegisterUrl = function(value)
{
	if (Utils.isNotEmptyString(value))
	{
		this.registerUrl = value;
	}
};
IntranetInvite.getRegisterUrl = function()
{
	return this.registerUrl;
};

IntranetInvite.setAdminConfirm = function(value)
{
	this.adminConfirm = value;
};
IntranetInvite.getAdminConfirm = function()
{
	return this.adminConfirm;
};

IntranetInvite.getData = function(params)
{
	BX.ajax.runAction('intranet.invite.getData', {
		data: {}
	}).then((response) =>
	{
		if (response.status === 'success')
		{
			if (
				Utils.isNotEmptyObject(params)
				&& Utils.isFunction(params.callback)
			)
			{
				params.callback(response.data);
			}
		}
	}).catch((error) => {

	});
};

IntranetInvite.initRegisterUrl = function(params)
{
	BX.ajax.runAction('intranet.invite.getRegisterUrl', {
		data: {}
	}).then((response) =>
	{
		if (response.status === 'success')
		{
			this.setRegisterUrl(response.data.result);
			if (
				Utils.isNotEmptyObject(params)
				&& Utils.isFunction(params.callback)
			)
			{
				params.callback(response.data.result);
			}
		}
	}).catch((response) => {
	});
};

IntranetInvite.openRegisterSlider = function(params)
{
	let settings = {
		objectName: 'inviteComponent',
		link: (Utils.isNotEmptyObject(params) && Utils.isNotEmptyString(params.registerUrl) ? params.registerUrl : ''),
		adminConfirm: (Utils.isNotEmptyObject(params) ? !!params.adminConfirm : false),
		disableAdminConfirm: (Utils.isNotEmptyObject(params) ? !!params.disableAdminConfirm : false),
		rootStructureSectionId: (Utils.isNotEmptyObject(params) ? parseInt(params.rootStructureSectionId) : 0)
	};

	this.setAdminConfirm(settings.adminConfirm);
	this.setRegisterUrl(settings.link);

	this.rootStructureSectionId = settings.rootStructureSectionId;

	if (
		Utils.isNotEmptyObject(params)
		&& Utils.isNotEmptyString(params.sharingMessage)
	)
	{
		settings.sharingMessage = params.sharingMessage;
	}

	const componentConfig = {
		scriptPath: availableComponents["intranet.invite"].publicUrl,
		params: {
			ORIGINATOR: (Utils.isNotEmptyObject(params) && Utils.isNotEmptyString(params.originator) ? params.originator : ''),
			DISABLE_ADMIN_CONFIRM: settings.disableAdminConfirm,
		},
		componentCode: "invite",
		rootWidget: {
			name: 'invite',
			settings: settings,
		}
	};

	if (params.parentLayout)
	{
		PageManager.openComponent(
			'JSStackComponent',
			componentConfig,
			params.parentLayout
		);

		return;
	}

	PageManager.openComponent(
		'JSStackComponent',
		componentConfig,
	);
};

IntranetInvite.onSendInvite = function(params)
{
	if (typeof analytics != "undefined") {
		analytics.send("invite")
	}

	if (
		!Utils.isNotEmptyObject(params)
		|| !Array.isArray(params.recipients)
	)
	{
		return;
	}

	const phoneList = params.recipients.map(function(item) {
		return item.phone;
	});

	const countryCodeList = params.recipients.map(function(item) {
		return item.countryCode;
	});

	if (phoneList.length <= 0)
	{
		return;
	}

	Notify.showIndicatorLoading();

	BX.ajax.runAction('intranet.invite.register', {
		data: {
			fields: {
				PHONE: phoneList,
				PHONE_COUNTRY: countryCodeList,
				DEPARTMENT_ID: this.rootStructureSectionId,
				CONTEXT: 'mobile'
			}
		}
	}).then(response => {
		let errors = response.errors;
		if(errors && errors.length > 0)
		{
			Notify.showIndicatorError({
				type: 'error',
				hideAfter: 10000,
				onTap: Notify.hideCurrentIndicator,
				text: this.getAjaxErrorText(errors)
			});
		}
		else
		{
			Notify.showIndicatorSuccess({hideAfter: 2000});
		}
	}).catch(response => {
		let errors = response.errors;
		if(errors && errors.length > 0)
		{
			Notify.showIndicatorError({
				hideAfter: 2000,
				text: this.getAjaxErrorText(errors)
			});
		}
		else
		{
			Notify.hideCurrentIndicator();
		}
	});
};

IntranetInvite.cleaner = function()
{
	return true;
};

IntranetInvite.getAjaxErrorText = function(errors)
{
	return errors.map((errorMessage) => {
		if (errorMessage.message)
		{
			return errorMessage.message.replace("<br/>:","\n").replace("<br/>","\n");
		}
		else
		{
			return errorMessage.replace("<br/>:","\n").replace("<br/>","\n");
		}
	}).filter((errorMessage) => {
		return errorMessage.length > 0;
	}).join("\n");
};

if (!IntranetInvite.isRecentComponent())
{
	IntranetInvite.init();
}

IntranetInvite.event.init = function (params)
{
	this.inviteComponent = params.inviteComponent;
	this.originator = params.originator;
	this.disableAdminConfirm = !!params.disableAdminConfirm;

	this.inviteComponent.setListener(this.router.bind(this));

	this.handlersList = {
		onSendInvite: this.onSendInvite,
		onUpdateLink: this.onUpdateLink,
		onAdminConfirm: this.onAdminConfirm,
		onShareLink: this.onShareLink,
		onHelpInvite: this.onHelpInvite,
		onHelpLink: this.onHelpLink
	};

	let inviteCallback = function(data) {
		if (typeof data.adminConfirm != 'undefined')
		{
			IntranetInvite.setAdminConfirm(data.adminConfirm);
			this.setAdminConfirm(data.adminConfirm);
		}
		if (typeof data.registerUrl != 'undefined')
		{
			IntranetInvite.setRegisterUrl(data.registerUrl);
			this.updateLink(data.registerUrl);
		}
	}.bind(this.inviteComponent);

	IntranetInvite.getData({callback: inviteCallback});
};

IntranetInvite.event.router = function(eventName, eventResult)
{
	if (this.handlersList[eventName])
	{
		this.handlersList[eventName].apply(this, [eventResult])
	}
	else if (this.debug)
	{
		console.info('IntranetInviteInterface.event.router: skipped event - '+eventName+' '+JSON.stringify(eventResult));
	}
};

IntranetInvite.event.onSendInvite = function(params)
{
	if (
		!Utils.isNotEmptyObject(params)
		|| !Array.isArray(params.recipients)
	)
	{
		return;
	}

	this.inviteComponent.close(function() {
		if (Utils.isNotEmptyString(this.originator))
		{
			BX.postComponentEvent("onSendInvite", [
				params
			], this.originator);
		}
	}.bind(this));
};

IntranetInvite.event.onUpdateLink = function()
{
	BX.ajax.runAction('intranet.invite.setRegisterSettings', {
		data: {
			params: {
				SECRET: Utils.getRandom(8)
			}
		}
	}).then(response => {
		let errors = response.data.errors;
		if(errors && errors.length > 0)
		{
			Notify.showIndicatorError({
				hideAfter: 10000,
				onTap: Notify.hideCurrentIndicator,
				text: this.getAjaxErrorText(errors)
			});
		}
		else
		{
			IntranetInvite.initRegisterUrl({
				callback: function(value) {
					this.updateLink(value);
				}.bind(this.inviteComponent)
			});
		}
	}).catch(response => {
		let errors = response.errors;
		if(errors && errors.length > 0)
		{
			Notify.showIndicatorError({
				hideAfter: 2000,
				text: this.getAjaxErrorText(errors)
			});
		}
	});
};

IntranetInvite.event.onAdminConfirm = function(value)
{
	if (this.disableAdminConfirm)
	{
		this.inviteComponent.setAdminConfirm(!value);
		return;
	}

	BX.ajax.runAction('intranet.invite.setRegisterSettings', {
		data: {
			params: {
				CONFIRM: (value ? 'Y' : 'N')
			}
		}
	}).then(response => {
		let errors = response.data.errors;

		if (
			(errors && errors.length > 0)
			|| response.data.result != 'success'
		)
		{
			this.inviteComponent.setAdminConfirm(!value);
		}
		else
		{
			this.inviteComponent.setAdminConfirm(value); // because object property doesn't set when chaning flag in the form
		}

		if(errors && errors.length > 0)
		{
			Notify.showIndicatorError({
				hideAfter: 10000,
				onTap: Notify.hideCurrentIndicator,
				text: this.getAjaxErrorText(errors)
			});
		}
	}).catch(response => {
		this.inviteComponent.setAdminConfirm(!value);

		let errors = response.errors;
		if(errors && errors.length > 0)
		{
			Notify.showIndicatorError({
				hideAfter: 2000,
				text: this.getAjaxErrorText(errors)
			});
		}
	});
};

IntranetInvite.event.onShareLink = function(params)
{
	BX.ajax.runAction('intranet.invite.copyRegisterUrl', {
		data: {
		}
	}).then(response => {
	}).catch(response => {

	});
};

IntranetInvite.event.onHelpInvite = function (params)
{
	if (Application.getApiVersion() >= 35)
	{
		Application.openHelpArticle("mh_invite_user", "invite_user")
	}
};
IntranetInvite.event.onHelpLink = function (params)
{
	if (Application.getApiVersion() >= 35)
	{
		Application.openHelpArticle("mh_invite_user", "copy_link")
	}
};