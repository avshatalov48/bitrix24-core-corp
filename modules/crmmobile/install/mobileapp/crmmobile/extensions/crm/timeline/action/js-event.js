/**
 * @module crm/timeline/action/js-event
 */
jn.define('crm/timeline/action/js-event', (require, exports, module) => {
	const { BaseTimelineAction } = require('crm/timeline/action/base');

	/** @type {typeof TimelineBaseController[]} */
	const controllers = Object.values(require('crm/timeline/controllers'));

	class JsEventAction extends BaseTimelineAction
	{
		constructor(props)
		{
			super(props);

			/** @type {TimelineBaseController[]} */
			this.controllers = controllers
				.filter((controllerClass) => controllerClass.isActionSupported(this.value))
				.map((controllerClass) => new controllerClass(this.source, this.entity, this.scheduler));
		}

		execute()
		{
			this.controllers.map((controller) => controller.onItemAction({
				action: this.value,
				actionParams: this.actionParams,
			}));
			this.sendAnalytics();
		}
	}

	module.exports = { JsEventAction };
});
