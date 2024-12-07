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
			void this.source.showLoader();
			Haptics.vibrate(5);

			BX.ajax.runAction(this.value, { data: this.actionParams })
				.then(() => {
					void this.source.hideLoader();
					Haptics.impactLight();
					this.sendAnalytics();
				})
				.catch((response) => this.#handleError(response));
		}

		#handleError(response)
		{
			// eslint-disable-next-line no-undef
			ErrorNotifier.showError(response.errors[0].message)
				.then(() => this.source.hideLoader())
				.then(() => this.source.refresh())
				.catch(console.error);

			Haptics.notifyFailure();
		}
	}

	module.exports = { AjaxAction };
});
