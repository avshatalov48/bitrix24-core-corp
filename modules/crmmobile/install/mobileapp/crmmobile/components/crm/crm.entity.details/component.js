(() => {
	const require = (ext) => jn.require(ext);

	const {
		ajaxErrorHandler,
		analyticsProvider,
		onCloseHandler,
		headerProcessor,
		rightButtonsProvider,
		additionalButtonProvider,
		floatingButtonProvider,
		setAvailableTabs,
		listenTimelinePush,
		customEvents,
		globalEvents,
		onEntityModelReady,
		menuProvider,
		AhaMomentsManager,
	} = require('crm/entity-detail/component');

	const { DetailToolbarFactory } = require('crm/entity-detail/toolbar');
	const { DetailCardComponent } = require('layout/ui/detail-card');

	DetailCardComponent
		.create(result.card)
		.setTestIdPrefix('CRM_ENTITY_DETAILS')
		.setAnalyticsProvider(analyticsProvider)
		.setMenuActionsProvider(menuProvider)
		.setTopToolbarFactory(DetailToolbarFactory)
		.setRightButtonsProvider(rightButtonsProvider)
		.setAdditionalElementsProvider(additionalButtonProvider)
		.setFloatingButtonProvider(floatingButtonProvider)
		.setAhaMomentsManager(AhaMomentsManager)
		.enableFloatingButton(true)
		.setAvailableTabsHandler(setAvailableTabs)
		.setAjaxErrorHandler(ajaxErrorHandler)
		.setOnCloseHandler(onCloseHandler)
		.setHeaderProcessor(headerProcessor)
		.onTabContentLoaded(listenTimelinePush)
		.setGlobalEvents(globalEvents)
		.setCustomEvents(customEvents)
		.onEntityModelReady(onEntityModelReady)
		.renderTo(layout)
	;
})();
