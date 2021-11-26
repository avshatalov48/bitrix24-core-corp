import { Event, Loc, Runtime, Tag, Type } from 'main.core';
import { Layout } from "ui.sidepanel.layout";
import { LoginFactory } from 'seo.ads.login';
import { Conversion } from "./conversion";

export class FacebookConversion extends Conversion
{
	constructor(width : number = 800)
	{
		super(width);
		this.tag = Tag.render`<div/>`;
		BX.addCustomEvent(
			window,
			'seo-client-auth-result',
			(eventData) => {
				eventData.reload = false;
				this.slider?.reload();
			}
		);
	}

	getSliderId()
	{
		return this.code + ':conversion';
	}

	getSliderTitle()
	{
		switch (this.type)
		{
			case 'facebook.form':
				return Loc.getMessage('CRM_ADS_CONVERSION_FORM_SLIDER_TITLE');
			case 'facebook.lead':
				return Loc.getMessage('CRM_ADS_CONVERSION_LEAD_SLIDER_TITLE');
			case 'facebook.deal':
				return Loc.getMessage('CRM_ADS_CONVERSION_DEAL_SLIDER_TITLE');
			case 'facebook.payment':
				return Loc.getMessage('CRM_ADS_CONVERSION_PAYMENT_SLIDER_TITLE');
			default:
				return '';
		}
	}

	notify(message: string)
	{
		BX.UI.Notification.Center.notify({
			content: message
		});
	}

	saveConfiguration(data)
	{
		return BX.ajax.runAction('crm.ads.conversion.saveConfiguration', {
			data: {
				code: this.code,
				configuration: data
			}
		});
	}

	onItemEnable(id, switcher)
	{
		this.data.configuration.items = Type.isArray(this.data.configuration.items) ? this.data.configuration.items : [];
		if (!this.data.configuration.items.includes(id))
		{
			this.data.configuration.items.push(id);
		}

		this.saveConfiguration(this.data.configuration)
			.then((response) => {
				if (!response.data.success)
				{
					switcher.check(true,false);
					this.notify(Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SAVE'));
				}
			})
			.catch(() => {
					switcher.check(true,false);
					this.notify(Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SAVE'));
				}
			)
		;
	}

	onItemDisable(id, switcher)
	{
		this.data.configuration.items = Type.isArray(this.data.configuration.items) ? this.data.configuration.items : [];
		for (let i = 0; i < this.data.configuration.items.length; i++)
		{
			if (id == this.data.configuration.items[i])
			{
				this.data.configuration.items.splice(i, 1);
				break;
			}
		}

		this.saveConfiguration(this.data.configuration)
			.then((response) => {
				if (!response.data.success)
				{
					switcher.check(true,false);
					this.notify(Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SAVE'));
				}
			})
			.catch(() => {
					switcher.check(true,false);
					this.notify(Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SAVE'));
				}
			)
		;
	}

	login()
	{
		LoginFactory.getLoginObject({
			'TYPE' : 'facebook',
			'ENGINE_CODE' : 'business.facebook'
		}).login();
	}

	loadData()
	{
		return BX.ajax.runAction('crm.ads.conversion.load', {
			data: {
				type: this.code
			}
		});
	}

	logout()
	{
		this.slider?.showLoader();
		return BX.ajax.runAction(
			'crm.ads.conversion.logout',
			{
					data: {},
					analyticsLabel: {
						connect: "FBE",
						action: "disconnect",
						type: "disconnect"
					}
				}
			)
			.then(()=> {
				this.slider?.reload();
			})
			.catch(()=>{
				this.slider?.reload();
			})
	}

	getContentTitle()
	{
		return '';
	}

	render ()
	{
		this.tag.innerHTML = '';
		if (this.data && this.data.available)
		{
			this.tag.appendChild(Tag.render`
				<div class="ui-slider-section crm-ads-conversion-title-section">
					<span class="ui-icon ui-slider-icon ui-icon-service-fb">
						<i></i>
					</span>
					<div class="ui-slider-content-box">
						<div class="ui-slider-paragraph-2">
							${Loc.getMessage('CRM_ADS_CONVERSION_LOGIN_OPPORTUNITY_TITLE')}
						</div>
						<ul class="ui-slider-list crm-ads-conversion-features">
							<li class="ui-slider-list-item">
								<span class="ui-slider-list-text">
									${Loc.getMessage('CRM_ADS_CONVERSION_LOGIN_OPPORTUNITY_TEXT_LIST_1')}
								</span>
							</li>
							<li class="ui-slider-list-item">
								<span class="ui-slider-list-text">
									${Loc.getMessage('CRM_ADS_CONVERSION_LOGIN_OPPORTUNITY_TEXT_LIST_2')}
								</span>
							</li>
							<li class="ui-slider-list-item">
								<span class="ui-slider-list-text">
									${Loc.getMessage('CRM_ADS_CONVERSION_LOGIN_OPPORTUNITY_TEXT_LIST_3')}
								</span>
							</li>
							<li class="ui-slider-list-item">
								<span class="ui-slider-list-text">
									${Loc.getMessage('CRM_ADS_CONVERSION_LOGIN_OPPORTUNITY_TEXT_LIST_4')}
								</span>
							</li>
						</ul>
						<a 
							href="https://www.facebook.com/business/help/1292598407460746?id=1205376682832142" 
							class="ui-slider-link" 
							target="_blank"
							>
							${Loc.getMessage('CRM_ADS_CONVERSION_LOGIN_LINK')}
						</a>
					</div>
				</div>
			`);
			if (this.data.auth && this.data.profile)
			{
				this.tag.appendChild(Tag.render`
					<div class="ui-slider-section crm-ads-conversion-account-section">
						<div class="crm-ads-conversion-block">
							<div class="crm-ads-conversion-social crm-ads-conversion-social-facebook">
								<div class="crm-ads-conversion-social-avatar">
									<div 
										class="crm-ads-conversion-social-avatar-icon" 
										style="background-image: url(${Tag.safe`${this.data.profile.picture}`})"
										>
									</div>
								</div>
								<div class="crm-ads-conversion-social-user">
									<a
										${this.data.profile.url ? 'href="' + Tag.safe`${this.data.profile.url}` + '"' : ""}
										target="_top" 
										class="crm-ads-conversion-social-user-link" 
										>
										${Tag.safe`${this.data.profile.name}`}
									</a>
								</div>
								<div class="crm-ads-conversion-social-shutoff">
									<span 
										class="crm-ads-conversion-social-shutoff-link" 
										onclick="${this.logout.bind(this)}"
										>
										${Loc.getMessage('CRM_ADS_CONVERSION_LOGOUT')}
									</span>
								</div>
							</div>
						</div>
					</div>
				`);

				if (Type.isArray(this.data.items))
				{
					this.tag.appendChild(this.data.items.reduce(
						(tag, value) => {
							let itemNode = Tag.render`
								<div class="crm-ads-conversion-settings-block">
									<div class="crm-ads-conversion-settings-name-block">
										<span class="crm-ads-conversion-settings-name">${Tag.safe`${value.name}`}</span>
										<span class="crm-ads-conversion-settings-detail">
											${Loc.getMessage('CRM_ADS_CONVERSION_DETAIL')}
										</span>
									</div>
									<div class="crm-ads-conversion-settings-control">
										<span data-switcher-node=""/>
									</div>
								</div>
							`;
							let switcher = new BX.UI.Switcher({
								node: itemNode.querySelector('[data-switcher-node]'),
								checked: value.enable,
							});
							switcher.handlers = {
								checked: this.onItemDisable.bind(this,value.id,switcher),
								unchecked: this.onItemEnable.bind(this,value.id,switcher)
							};
							tag.appendChild(itemNode);
							return tag;
						},
						Tag.render`
						<div class="ui-slider-section crm-ads-conversion-settings">
							<div class="ui-slider-heading-3">${Tag.safe`${this.getScriptMessage()}`}</div>
						</div>`
					));
				}
			}
			else
			{
				this.tag.appendChild(Tag.render`
					<div class="ui-slider-section">
						<div class="ui-slider-content-box">
							<div class="ui-slider-heading-3">
								${Loc.getMessage('CRM_ADS_CONVERSION_LOGIN_CONNECT_TITLE')}
							</div>
							<div class="crm-ads-conversion-login-block">
								<div class="crm-ads-conversion-login-wrapper">
									<a
										class="webform-small-button webform-small-button-transparent"
										onclick="${this.login.bind(this)}"
										>
										${Loc.getMessage('CRM_ADS_CONVERSION_LOGIN')}
									</a>
									<span class="crm-ads-conversion-settings-detail">
										${Loc.getMessage('CRM_ADS_CONVERSION_LOGIN_OPPORTUNITY_TEXT_LIST_5')}
									</span>
								</div>
							</div>
						</div>
					</div>
				`);
			}
		}
		else
		{
			this.tag.appendChild(Tag.render`
				<div class="ui-slider-no-access">
					<div class="ui-slider-no-access-inner">
						<div class="ui-slider-no-access-title">
							${Loc.getMessage('CRM_ADS_CONVERSION_ERROR_TITLE_SERVICE_ERROR')}
						</div>
						<div class="ui-slider-no-access-subtitle">
							${
								this.errors.length > 0?
									this.errors
										.reduce(
											(unique, value) => {
												let item = Tag.safe`${this.getErrorMessage(value)}`;
												if (unique.includes(item))
												{
													unique.push(item)
												}
												return unique;
											},
											[]
										)
										.join('<br>') : 
									Loc.getMessage('CRM_ADS_CONVERSION_ERROR_MESSAGE_SERVICE_ERROR')
							}
						</div>
						<div class="ui-slider-no-access-img">
							<div class="ui-slider-no-access-img-inner"></div>
						</div>
					</div>
				</div>
			`);
		}
	}

	getErrorMessage(value)
	{
		if (Type.isObject(value) && value.code)
		{
			if (value.code === "permissions")
			{
				return Loc.getMessage('CRM_ADS_CONVERSION_ERROR_ACCESS_DENY');
			}

			if (value.code === "modules" && value.customData)
			{
				switch (value.customData.module)
				{
					case "seo":
						return Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SEO_NOT_INSTALLED');
					case "crm":
						return Loc.getMessage('CRM_ADS_CONVERSION_ERROR_CRM_NOT_INSTALLED');
					case "socialservices":
						return Loc.getMessage('CRM_ADS_CONVERSION_ERROR_SOCIAL_NOT_INSTALLED');
				}
			}
		}
		return Loc.getMessage('CRM_ADS_CONVERSION_ERROR_UNKNOWN_ERROR')
	}

	saveData(data)
	{
		if (data)
		{
			data.configuration = Type.isObject(data.configuration)? data.configuration : {};
			this.data = data;
		}
	}

	saveErrors(errors)
	{
		this.errors = Type.isArray(errors)? errors: [];
	}

	show() : void
	{
		this.slider = this.slider ?? (BX.SidePanel.Instance.open(this.getSliderId(),{
			contentCallback: () => {
				return Runtime.loadExtension('ui.sidepanel.layout').then(() => {
					return BX.UI.SidePanel.Layout.createContent({
							title: this.getContentTitle(),
							extensions:[ 'crm.ads.conversion', 'ui.forms','ui.switcher','seo.ads.login' ],
							design:{ section: false, margin: true },
							content: () =>
							{
								return this.loadData()
									.then((response) => {
										this.saveData(response.data);
										this.saveErrors(response.errors);
										this.render();
										return this.tag;
									})
									.catch((response) => {
										this.saveData(response.data);
										this.saveErrors(response.errors);
										this.render();

										if (((response.errors || [])[0] || {}).code === 100)
										{
											BX.UI.InfoHelper.show('crm_ad_conversion');
										}

										return this.tag;
									})
								;
							}
					});
				});
			},
			title: this.getSliderTitle(),
			width: (this.width ?? BX.SidePanel.Instance.getTopSlider()?.getWidth() ?? 800)
		})?
			BX.SidePanel.Instance.getSlider(this.getSliderId()) :
			null
		);
	}
}