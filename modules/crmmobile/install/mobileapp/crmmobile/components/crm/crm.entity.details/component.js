(() => {

	const {
		ajaxErrorHandler,
		onCloseHandler,
		headerProcessor,
		rightButtonsProvider,
		additionalButtonProvider,
		setAvailableTabs,
		listenTimelinePush,
		customEvents,
		globalEvents,
		onEntityModelReady,
		menuProvider,
	} = jn.require('crm/entity-detail/component');

	const { DetailToolbarFactory } = jn.require('crm/entity-detail/toolbar');

	UI.DetailCardComponent
		.create(result.card)
		.setTestIdPrefix('CRM_ENTITY_DETAILS')
		.setMenuActionsProvider(menuProvider)
		.setTopToolbarFactory(DetailToolbarFactory)
		.setRightButtonsProvider(rightButtonsProvider)
		.setAdditionalElementsProvider(additionalButtonProvider)
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
