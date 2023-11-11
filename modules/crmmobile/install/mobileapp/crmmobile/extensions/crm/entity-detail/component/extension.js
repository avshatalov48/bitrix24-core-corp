/**
 * @module crm/entity-detail/component
 */
jn.define('crm/entity-detail/component', (require, exports, module) => {
	const { ajaxErrorHandler } = require('crm/entity-detail/component/ajax-error-handler');
	const { analyticsProvider } = require('crm/entity-detail/component/analytics-provider');
	const { menuProvider } = require('crm/entity-detail/component/menu-provider');
	const { onCloseHandler } = require('crm/entity-detail/component/on-close-handler');
	const { headerProcessor } = require('crm/entity-detail/component/header-processor');
	const { rightButtonsProvider } = require('crm/entity-detail/component/right-buttons-provider');
	const { floatingButtonProvider } = require('crm/entity-detail/component/floating-button-provider');
	const { additionalButtonProvider } = require('crm/entity-detail/component/additional-button-provider');
	const { setAvailableTabs } = require('crm/entity-detail/component/set-available-tabs');
	const { listenTimelinePush } = require('crm/entity-detail/component/timeline-push-processor');
	const { customEvents } = require('crm/entity-detail/component/custom-events');
	const { globalEvents } = require('crm/entity-detail/component/global-events');
	const { getPaymentAutomationMenuItem } = require('crm/entity-detail/component/payment-automation-menu-item');
	const { getSmartActivityMenuItem } = require('crm/entity-detail/component/smart-activity-menu-item');
	const { onEntityModelReady } = require('crm/entity-detail/component/on-model-ready');
	const { getOpenLinesMenuItems } = require('crm/entity-detail/component/open-lines-menu-items');
	const { AhaMomentsManager } = require('crm/entity-detail/component/aha-moments-manager');

	module.exports = {
		menuProvider,
		ajaxErrorHandler,
		analyticsProvider,
		onCloseHandler,
		headerProcessor,
		rightButtonsProvider,
		floatingButtonProvider,
		setAvailableTabs,
		additionalButtonProvider,
		listenTimelinePush,
		customEvents,
		globalEvents,
		getSmartActivityMenuItem,
		getPaymentAutomationMenuItem,
		onEntityModelReady,
		getOpenLinesMenuItems,
		AhaMomentsManager,
	};
});
