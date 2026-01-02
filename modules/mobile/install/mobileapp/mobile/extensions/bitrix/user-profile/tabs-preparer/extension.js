/**
 * @module user-profile/tabs-preparer
 */
jn.define('user-profile/tabs-preparer', (require, exports, module) => {
	const { TabType } = require('user-profile/const');
	const { CommonTab } = require('user-profile/common-tab');
	const { ProfileFilesGrid } = require('disk/file-grid/profile-files');
	const { getState } = require('statemanager/redux/store');
	const { selectById } = require('statemanager/redux/slices/users/selector');
	const { Loc } = require('loc');

	/**
	 * @param {Array} tabs
	 * @param {string} selectedTabId
	 * @return {Array}
	 */
	function prepareTabs(tabs, selectedTabId)
	{
		return (
			tabs
				.filter((tab) => (
					tab.widget
					// eslint-disable-next-line no-undef
					|| availableComponents[tab.componentName]
					|| tab.component?.rootWidget?.name === 'web'
				))
				.map((tab) => ({
					id: tab.id,
					title: tab.title,
					active: tab.id === selectedTabId,
					component: tab.component,
					widget: tab.widget,
					params: tab.params,
				}))
		);
	}

	/**
	 * @param {Object} tabsWidget
	 * @param {number} ownerId
	 */
	function bindEvents(tabsWidget, ownerId)
	{
		tabsWidget.on('onTabSelected', (item, changed) => {
			if (!changed)
			{
				return;
			}

			let widgetTitle = Loc.getMessage('M_PROFILE_TITLE');
			if (item.id !== TabType.COMMON)
			{
				widgetTitle = selectById(getState(), ownerId)?.fullName ?? widgetTitle;
			}

			tabsWidget.setTitle({ text: widgetTitle }, true);
		});
	}

	/**
	 * @param {Object} tabsWidget
	 * @param {Object} params
	 */
	function initTabNestedWidgets(tabsWidget, params)
	{
		const nestedWidgets = tabsWidget.nestedWidgets();
		initCommonTabWidget(nestedWidgets, params, tabsWidget);
		initFilesTabWidget(nestedWidgets, params);
	}

	function initCommonTabWidget(nestedWidgets, params, tabsWidget)
	{
		if (nestedWidgets[TabType.COMMON])
		{
			const widget = nestedWidgets[TabType.COMMON];
			const widgetParams = params[TabType.COMMON];

			widget.showComponent(
				CommonTab({
					...widgetParams,
					parentWidget: widget,
					tabsWidget,
					closeIcon: params.closeIcon,
				}),
			);
		}
	}

	function initFilesTabWidget(nestedWidgets, params)
	{
		if (nestedWidgets[TabType.FILES])
		{
			const widget = nestedWidgets[TabType.FILES];
			const widgetParams = params[TabType.FILES];

			widget.showComponent(
				new ProfileFilesGrid({ ...widgetParams, parentWidget: widget }),
			);
		}
	}

	module.exports = {
		prepareTabs,
		bindEvents,
		initTabNestedWidgets,
	};
});
