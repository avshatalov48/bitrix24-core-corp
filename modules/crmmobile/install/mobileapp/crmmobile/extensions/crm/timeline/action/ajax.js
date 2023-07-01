/**
 * @module crm/timeline/action/ajax
 */
jn.define('crm/timeline/action/ajax', (require, exports, module) => {
	const { BaseTimelineAction } = require('crm/timeline/action/base');
	const { Haptics } = require('haptics');

	class AjaxAction extends BaseTimelineAction
	{
		execute()
		{
			this.source.showLoader();
			Haptics.vibrate(5);

			BX.ajax.runAction(this.value, { data: this.actionParams })
				.then(() => {
					this.source.hideLoader();
					Haptics.impactLight();
					this.sendAnalytics();
				})
				.catch((response) => {
					ErrorNotifier.showError(response.errors[0].message).finally(() => this.source.hideLoader());
					Haptics.notifyFailure();
				});
		}
	}

	module.exports = { AjaxAction };
});
