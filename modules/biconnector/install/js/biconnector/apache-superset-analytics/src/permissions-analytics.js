import { ApacheSupersetAnalytics } from './apache-superset-analytics';

export const PermissionsAnalyticsSource = Object.freeze({
	permissionsSlider: 'permissions_slider',
	grid: 'grid',
});

export class PermissionsAnalytics
{
	static #categoryName = 'permissions';

	static #clickGroupCreateAction = 'click_create_group';
	static #clickGroupEditAction = 'click_edit_group';

	static #groupCreateAction = 'group_created';
	static #groupEditAction = 'group_edited';
	static #groupDeleteAction = 'group_deleted';

	static #groupDashboardListEditAction = 'group_dashboard_list_edited';
	static #groupScopeListEditAction = 'group_visibility_edited';
	static #groupDashboardScopeListEditAction = 'group_dashboard_visibility_edited';

	static #dashboardListParameter = 'dashboard';
	static #scopeListParameter = 'visibility';

	static sendGroupActionAnalytics(
		source: string,
		isNew: boolean,
		isDashboardListAdded: boolean,
		isScopeListAdded: boolean,
	): void
	{
		ApacheSupersetAnalytics.sendAnalytics(
			this.#categoryName,
			isNew ? this.#groupCreateAction : this.#groupEditAction,
			{
				c_sub_section: source,
				p3: this.#buildParameter(this.#dashboardListParameter, isDashboardListAdded),
				p4: this.#buildParameter(this.#scopeListParameter, isScopeListAdded),
			},
		);
	}

	static sendClickGroupActionAnalytics(
		source: string,
		isNew: boolean,
	): void
	{
		ApacheSupersetAnalytics.sendAnalytics(
			this.#categoryName,
			isNew ? this.#clickGroupCreateAction : this.#clickGroupEditAction,
			{
				c_sub_section: source,
			},
		);
	}

	static sendGroupScopeEditAnalytics(source: string): void
	{
		this.#sendSimpleEvent(this.#groupScopeListEditAction, source);
	}

	static sendGroupDashboardEditAnalytics(source: string): void
	{
		this.#sendSimpleEvent(this.#groupDashboardListEditAction, source);
	}

	static sendGroupDashboardScopeEditAnalytics(source: string): void
	{
		this.#sendSimpleEvent(this.#groupDashboardScopeListEditAction, source);
	}

	static sendGroupDeleteAnalytics(source: string): void
	{
		this.#sendSimpleEvent(this.#groupDeleteAction, source);
	}

	static #sendSimpleEvent(actionName: string, source: string): void
	{
		ApacheSupersetAnalytics.sendAnalytics(
			this.#categoryName,
			actionName,
			{
				c_sub_section: source,
			},
		);
	}

	static #buildParameter(parameterName: string, isActive: boolean): string
	{
		return `${parameterName}_${isActive ? 'Y' : 'N'}`;
	}
}
