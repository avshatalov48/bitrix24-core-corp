/**
 * @module crm/timeline/action
 */
jn.define('crm/timeline/action', (require, exports, module) => {
	const { JsEventAction } = require('crm/timeline/action/js-event');
	const { AjaxAction } = require('crm/timeline/action/ajax');
	const { RedirectAction } = require('crm/timeline/action/redirect');
	const { ShowMenuAction } = require('crm/timeline/action/show-menu');
	const { NullAction } = require('crm/timeline/action/null-action');

	const SupportedActions = {
		jsEvent: JsEventAction,
		runAjaxAction: AjaxAction,
		redirect: RedirectAction,
		showMenu: ShowMenuAction,
	};

	/**
	 * @class TimelineAction
	 */
	class TimelineAction
	{
		static make(params = {})
		{
			const { type } = params;
			const factory = this;
			const props = { ...params, factory };

			if (SupportedActions[type])
			{
				return new SupportedActions[type](props);
			}

			return new NullAction(props);
		}

		static execute(params = {})
		{
			const action = TimelineAction.make(params);

			return action.execute();
		}
	}

	module.exports = { TimelineAction, SupportedActions };
});
