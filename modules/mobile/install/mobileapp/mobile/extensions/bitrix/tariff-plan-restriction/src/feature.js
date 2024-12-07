/**
 * @module tariff-plan-restriction/feature
 */
jn.define('tariff-plan-restriction/feature', (require, exports, module) => {
	const { PlanRestriction } = require('layout/ui/plan-restriction');
	const { Haptics } = require('haptics');
	const { Type } = require('type');
	const { AnalyticsEvent } = require('analytics');

	const store = require('statemanager/redux/store');
	const { selectFeatureRestrictions } = require('statemanager/redux/slices/tariff-plan-restrictions');

	const getFeatureRestriction = (featureId) => ({
		code: () => (selectFeatureRestrictions(store.getState(), featureId).code || ''),
		title: () => (selectFeatureRestrictions(store.getState(), featureId).title || ''),
		isRestricted: () => Boolean(selectFeatureRestrictions(store.getState(), featureId).isRestricted),
		isPromo: () => Boolean(selectFeatureRestrictions(store.getState(), featureId).isPromo),
		/**
		 * @param {object} [params={}]
		 * @param {boolean} [params.showInComponent=false]
		 * @param {PageManager} [params.parentWidget=PageManager]
		 * @param {function} [params.onHidden]
		 * @param {AnalyticsEvent|AnalyticsDTO} [params.analyticsData]
		 * @return {void|Promise}
		 */
		showRestriction: (params = {}) => {
			const { showInComponent = false, parentWidget = PageManager, onHidden, analyticsData } = params;
			const { code = '', title = '', isPromo = false } = selectFeatureRestrictions(store.getState(), featureId);
			const props = {
				isPromo,
				title,
				featureId: code,
			};

			if (Type.isObject(analyticsData))
			{
				if (analyticsData instanceof AnalyticsEvent)
				{
					props.analyticsData = analyticsData.exportToObject();
				}
				else
				{
					props.analyticsData = analyticsData;
				}
			}

			Haptics.notifyWarning();

			if (showInComponent)
			{
				return PlanRestriction.openComponent(props, parentWidget);
			}

			const openPromise = PlanRestriction.open(props, parentWidget);

			if (Type.isFunction(onHidden))
			{
				openPromise.then((layout) => layout.on('onViewHidden', () => onHidden())).catch(console.error);
			}

			return openPromise;
		},
	});

	module.exports = { getFeatureRestriction };
});
