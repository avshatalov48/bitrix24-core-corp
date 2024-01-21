/**
 * @module calendar/sharing/analytics
 */
jn.define('calendar/sharing/analytics', (require, exports, module) => {
	/**
	 * @class Analytics
	 */
	class Analytics
	{
		static tool = 'calendar';
		static category = 'slots';

		/**
		 * @type {Map<string, AnalyticsContext>}
		 */
		static contexts = {
			calendar: 'calendar',
			crm: 'crm',
		};

		/**
		 * @type {Map<string, AnalyticsLinkType>}
		 */
		static linkTypes = {
			solo: 'solo',
			multiple: 'multiple',
		};

		static events = {
			form_open: 'form_open',
			setup: 'setup',
			adding_people: 'adding_people',
			link_created: 'link_created',
		};

		/**
		 * @type {Map<string, AnalyticsLinkCreateMethod>}
		 */
		static linkCreateMethods = {
			crm_send: 'crm_send',
			crm_copy: 'crm_copy',
			calendar_copy_main: 'calendar_copy_main',
			calendar_copy_list: 'calendar_copy_list',
		};

		/**
		 * @type {Map<string, AnalyticsRuleChange>}
		 */
		static ruleChanges = {
			custom_days: 'custom_days',
			custom_length: 'custom_length',
		};

		/**
		 * @param context {AnalyticsContext}
		 */
		static sendPopupOpened(context)
		{
			this.sendAnalytics(Analytics.events.form_open, {
				c_section: context,
			});
		}

		/**
		 * @param context {AnalyticsContext}
		 * @param changes {AnalyticsRuleChange[]}
		 */
		static sendRuleUpdated(context, changes)
		{
			for (const type of changes)
			{
				this.sendAnalytics(Analytics.events.setup, {
					type,
					c_section: context,
				});
			}
		}

		/**
		 * @param context {AnalyticsContext}
		 * @param peopleCount {number}
		 */
		static sendMembersAdded(context, peopleCount)
		{
			this.sendAnalytics(Analytics.events.adding_people, {
				c_section: context,
				p1: `peopleCount_${peopleCount}`,
			});
		}

		/**
		 * @param context {AnalyticsContext}
		 * @param type {AnalyticsLinkType}
		 * @param params {AnalyticsParams}
		 */
		static sendLinkCopied(context, type, params)
		{
			let method = Analytics.linkCreateMethods.calendar_copy_main;
			if (context === Analytics.contexts.crm)
			{
				method = Analytics.linkCreateMethods.crm_copy;
			}

			this.sendLinkCreated(context, type, method, params);
		}

		/**
		 * @param context {AnalyticsContext}
		 * @param params {AnalyticsParams}
		 */
		static sendLinkCopiedList(context, params)
		{
			const method = Analytics.linkCreateMethods.calendar_copy_list;

			this.sendLinkCreated(context, Analytics.linkTypes.multiple, method, params);
		}

		/**
		 * @param context {AnalyticsContext}
		 * @param type {AnalyticsLinkType}
		 * @param method {AnalyticsLinkCreateMethod}
		 * @param params {AnalyticsParams}
		 */
		static sendLinkCreated(
			context,
			type,
			method,
			params,
		)
		{
			const ruleChanges = {
				customDays: params.ruleChanges.includes(Analytics.ruleChanges.custom_days) ? 'Y' : 'N',
				customLength: params.ruleChanges.includes(Analytics.ruleChanges.custom_length) ? 'Y' : 'N',
			};

			this.sendAnalytics(Analytics.events.link_created, {
				type,
				c_section: context,
				c_element: method,
				p1: `peopleCount_${params.peopleCount}`,
				p2: `customDays_${ruleChanges.customDays}`,
				p3: `customLength_${ruleChanges.customLength}`,
			});
		}

		/**
		 * @param event {string}
		 * @param params {{c_section, c_element, type, p1, p2, p3}}
		 */
		static sendAnalytics(event, params)
		{
			const data = {
				tool: Analytics.tool,
				category: Analytics.category,
				event,
				...params,
			};

			const queryParams = [];
			for (const [key, value] of Object.entries(data))
			{
				queryParams.push(`st[${key}]=${encodeURIComponent(value)}`);
			}

			BX.ajax({
				method: 'GET',
				url: `/_analytics/?${queryParams.join('&')}`,
			});
		}
	}

	module.exports = { Analytics };
});
