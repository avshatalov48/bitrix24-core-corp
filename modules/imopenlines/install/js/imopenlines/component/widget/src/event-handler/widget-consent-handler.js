import { EventEmitter } from "main.core.events";
import { WidgetEventType, FormType, RestMethod } from "../const";
import { DeviceType, EventType } from "im.const";

export class WidgetConsentHandler
{
	store: Object = null;
	restClient: Object = null;
	application: Object = null;

	constructor($Bitrix)
	{
		this.store = $Bitrix.Data.get('controller').store;
		this.restClient = $Bitrix.RestClient.get();
		this.application = $Bitrix.Application.get();

		this.subscribeToEvents();
	}

	subscribeToEvents()
	{
		this.showConsentHandler = this.onShowConsent.bind(this);
		this.acceptConsentHandler = this.onAcceptConsent.bind(this);
		this.declineConsentHandler = this.onDeclineConsent.bind(this);
		EventEmitter.subscribe(WidgetEventType.showConsent, this.showConsentHandler);
		EventEmitter.subscribe(WidgetEventType.acceptConsent, this.acceptConsentHandler);
		EventEmitter.subscribe(WidgetEventType.declineConsent, this.declineConsentHandler);
	}

	onShowConsent()
	{
		this.showConsent();
	}

	onAcceptConsent()
	{
		this.acceptConsent();
	}

	onDeclineConsent()
	{
		this.declineConsent();
	}

	showConsent()
	{
		this.store.commit('widget/common', {showConsent: true});
	}

	hideConsent()
	{
		this.store.commit('widget/common', {showConsent: false});
	}

	acceptConsent()
	{
		this.hideConsent();

		this.sendConsentDecision(true);

		EventEmitter.emit(WidgetEventType.consentAccepted);

		if (this.getWidgetModel().common.showForm === FormType.none)
		{
			EventEmitter.emit(EventType.textarea.setFocus);
		}
	}

	declineConsent()
	{
		EventEmitter.emit(WidgetEventType.hideForm);
		this.hideConsent();
		this.sendConsentDecision(false);

		EventEmitter.emit(WidgetEventType.consentDeclined);

		if (this.getApplicationModel().device.type !== DeviceType.mobile)
		{
			EventEmitter.emit(EventType.textarea.setFocus);
		}
	}

	sendConsentDecision(result)
	{
		this.store.commit('widget/dialog', {userConsent: result});

		if (result && this.application.isUserRegistered())
		{
			this.restClient.callMethod(RestMethod.widgetUserConsentApply, {
				config_id: this.getWidgetModel().common.configId,
				consent_url: location.href
			});
		}
	}

	getWidgetModel()
	{
		return this.store.state.widget;
	}

	getApplicationModel()
	{
		return this.store.state.application;
	}

	destroy()
	{
		EventEmitter.unsubscribe(WidgetEventType.showConsent, this.showConsentHandler);
		EventEmitter.unsubscribe(WidgetEventType.acceptConsent, this.acceptConsentHandler);
		EventEmitter.unsubscribe(WidgetEventType.declineConsent, this.declineConsentHandler);
	}
}