import { ajax, Http } from 'main.core';
import { sendData } from 'ui.analytics';
import type { AnalyticsOptions } from 'ui.analytics';

export type AnalyticContext = {
	isAdmin: ?boolean,
	locationName: ?string,
	isBitrix24: boolean,
	analyticContext: ?string,
}

export class Analytic
{
	#eventList: Array = [];
	#tool: string = 'settings';
	#context: ?AnalyticContext = null;

	constructor(context: ?AnalyticContext = null)
	{
		this.#context = context;
	}

	addEvent(eventType: string, eventData: AnalyticsOptions): void
	{
		if (this.#context.isBitrix24)
		{
			this.#eventList[eventType] = eventData;
		}
	}

	send(): void
	{
		if (!this.#context.isBitrix24)
		{
			return;
		}

		if (Object.keys(this.#eventList).length > 0)
		{
			ajax.runComponentAction(
				'bitrix:intranet.settings',
				'analytic',
				{
					mode: 'class',
					data: {data: this.#eventList},
				},
			).then(()=>{});
		}

		this.#eventList = [];
	}

	addEventOpenSettings(): void
	{
		const options = {
			event: AnalyticSettingsEvent.OPEN,
			tool: this.#tool,
			category: 'slider',
			p1: this.#context?.isAdmin !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
			c_section: this.#context?.analyticContext ?? '',
			c_element: this.#context?.locationName,
		};

		sendData(options);
		//this.addEvent(AnalyticSettingsEvent.OPEN, options);
	}

	addEventOpenTariffSelector(fieldName: string): void
	{
		const options = {
			event: 'open_tariff',
			tool: this.#tool,
			category: fieldName,
			p1: this.#context?.isAdmin !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
		};

		this.addEvent(fieldName + '_open_tariff', options);
	}

	addEventOpenHint(fieldName: string): void
	{
		const options = {
			event: 'open_hint',
			tool: this.#tool,
			category: fieldName,
			p1: this.#context?.isAdmin !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
		};

		this.addEvent(fieldName + '_open_hint', options);
	}

	addEventStartPagePage(page: string): void
	{
		const options = {
			event: AnalyticSettingsEvent.START_PAGE,
			tool: this.#tool,
			category: page,
			p1: this.#context?.isAdmin !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
		};

		sendData(options);
	}

	addEventChangePage(page: string): void
	{
		const options = {
			event: AnalyticSettingsEvent.VIEW,
			tool: this.#tool,
			category: page,
			p1: this.#context?.isAdmin !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
		};

		sendData(options);
	}

	addEventToggleTools(toolName: string, state: boolean): void
	{
		const event = 'onoff_tools';

		const options = {
			event: event,
			tool: this.#tool,
			category: 'tools',
			type: toolName,
			c_element: this.#context?.locationName,
			p1: this.#context?.isAdmin !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
			p2: state ? AnalyticSettingsTurnState.ON : AnalyticSettingsTurnState.OFF,
		};

		this.addEvent('tools' + toolName + '_' + event, options);
	}

	addEventToggle2fa(state: boolean): void
	{
		const event = '2fa_onoff';

		const options = {
			event: event,
			tool: this.#tool,
			category: 'security',
			p1: this.#context?.isAdmin !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
			p2: state ? AnalyticSettingsTurnState.ON : AnalyticSettingsTurnState.OFF,
		};

		this.addEvent('security_' + event, options);
	}

	addEventConfigPortal(event: string): void
	{
		const options = {
			event: event,
			tool: this.#tool,
			category: AnalyticSettingsCategory.PORTAL,
		};

		this.addEvent('portal_' + event, options);
	}

	addEventChangeTheme(themeId: string): void
	{
		const regex = /custom_\d+/;
		const preparedThemeId = regex.test(themeId) ? 'themeName_custom' : 'themeName_' + themeId;
		const options = {
			event: AnalyticSettingsEvent.CHANGE_PORTAL_THEME,
			tool: this.#tool,
			category: AnalyticSettingsCategory.PORTAL,
			type: AnalyticSettingsType.COMMON,
			c_section: AnalyticSettingsSection.SETTINGS,
			p1: preparedThemeId,
		};

		this.addEvent('portal_' + AnalyticSettingsEvent.CHANGE_PORTAL_THEME, options);
	}

	addEventConfigEmployee(event: string, state: boolean): void
	{
		const options = {
			event: event,
			tool: this.#tool,
			category: 'employee',
			p1: this.#context?.isAdmin !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
			p2: state ? AnalyticSettingsTurnState.ON : AnalyticSettingsTurnState.OFF,
		};

		this.addEvent('employee_' + event, options);
	}

	addEventConfigConfiguration(event: string, state: boolean): void
	{
		const options = {
			event: event,
			tool: this.#tool,
			category: 'configuration',
			p1: this.#context?.isAdmin !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
			p2: state ? AnalyticSettingsTurnState.ON : AnalyticSettingsTurnState.OFF,
		};

		this.addEvent('configuration_' + event, options);
	}

	addEventConfigRequisite(event: string): void
	{
		const options = {
			event: event,
			tool: this.#tool,
			category: 'requisite',
			c_element: this.#context?.locationName,
			p1: this.#context?.isAdmin !== false ? AnalyticSettingsUserRole.ADMIN : AnalyticSettingsUserRole.NOT_ADMIN,
		};

		sendData(options);
	}
}

export class AnalyticSettingsCategory
{
	static TOOLS = 'tools';
	static SECURITY = 'security';
	static AI = 'ai';
	static PORTAL = 'portal';
	static EMPLOYEE = 'employee';
	static COMMUNICATION = 'communication';
	static REQUISITE = 'requisite';
	static SCHEDULE = 'schedule';
	static CONFIGURATION = 'configuration';
}

export class AnalyticSettingsEvent
{
	static OPEN = 'open_setting';
	static START_PAGE = 'start_page';
	static VIEW = 'view';
	static TFA = '2fa_onoff';
	static CHANGE_PORTAL_NAME = 'change_portal_name';
	static CHANGE_PORTAL_LOGO = 'change_portal_logo';
	static CHANGE_PORTAL_SITE = 'change_portal_site';
	static CHANGE_PORTAL_THEME = 'change_portal_theme';
	static CHANGE_MARKET = 'change_market';
	static CHANGE_PAY_TARIFF = 'change_pay_tariff';
	static CREATE_CARD = 'create_vizitka';
	static EDIT_CARD = 'edit_vizitka';
	static COPY_LINK_CARD = 'copylink_vizitka';
	static OPEN_ADD_COMPANY = 'open_add_company';
	static CHANGE_QUICK_REG = 'change_quick_reg';
	static CHANGE_REG_ALL = 'change_reg_all';
	static CHANGE_EXTRANET_INVITE = 'change_extranet_invite';
}

export class AnalyticSettingsSection
{
	static SETTINGS = 'settings';
}

export class AnalyticSettingsType
{
	static COMMON = 'common';
}

export class AnalyticSettingsUserRole
{
	static ADMIN = 'isAdmin_Y';
	static NOT_ADMIN = 'isAdmin_N';
}

export class AnalyticSettingsTurnState
{
	static ON = 'turn_on';
	static OFF = 'turn_off';
}