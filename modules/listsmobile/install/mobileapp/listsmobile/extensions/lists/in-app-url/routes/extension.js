/**
 * @module lists/in-app-url/routes
 */
jn.define('lists/in-app-url/routes', (require, exports, module) => {
	const openElementDetails = (elementId, context) => {
		void requireLazy('lists:element-details')
			.then(({ ElementDetails }) => {
				if (ElementDetails)
				{
					ElementDetails.open(
						{
							elementId,
							title: context.widgetTitle || null,
							isEmbedded: false,
							isNeedShowSkeleton: true,
							interceptExit: true,
							uid: context.uid || null,
						},
						context.parentWidget || PageManager,
					);
				}
			})
		;
	};

	/**
	 * @param {InAppUrl} inAppUrl
	 */
	module.exports = (inAppUrl) => {
		inAppUrl
			.register(
				'/bizproc/processes/:iBlockId/element/:sectionId/:elementId/',
				({ elementId }, { context }) => {
					openElementDetails(elementId, context);
				},
			)
			.name('lists:processDetail')
		;
		inAppUrl
			.register(
				'/bizproc/processes/\\?livefeed=y&list_id=:iBlockId&element_id=:elementId',
				({ elementId }, { context }) => {
					openElementDetails(elementId, context);
				},
			)
			.name('lists:liveFeedProcessDetail')
		;
		inAppUrl
			.register(
				'/workgroups/group/:groupID/lists/:iBlockId/element/:sectionId/:elementId/',
				({ elementId }, { context }) => {
					openElementDetails(elementId, context);
				},
			)
			.name('lists:groupElementDetail')
		;
		inAppUrl
			.register(
				'/company/lists/:iBlockId/element/:sectionId/:elementId/',
				({ elementId }, { context }) => {
					openElementDetails(elementId, context);
				},
			)
			.name('lists:listElementDetail')
		;
	};
});
