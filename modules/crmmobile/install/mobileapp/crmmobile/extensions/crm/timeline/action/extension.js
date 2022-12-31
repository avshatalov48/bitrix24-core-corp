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
		static make({ type, value, actionParams, source, entity })
		{
			const factory = this;
			const props = { value, actionParams, source, entity, factory };

			if (SupportedActions[type])
			{
				return new SupportedActions[type](props);
			}

			return new NullAction(props);
		}

		static execute({ type, value, actionParams, source, entity })
		{
			const action = TimelineAction.make({ type, value, actionParams, source, entity });
			return action.execute();
		}
    }

    module.exports = { TimelineAction, SupportedActions };

});
