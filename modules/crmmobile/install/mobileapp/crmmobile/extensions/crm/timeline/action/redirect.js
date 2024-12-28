/**
 * @module crm/timeline/action/redirect
 */
jn.define('crm/timeline/action/redirect', (require, exports, module) => {
	const { BaseTimelineAction } = require('crm/timeline/action/base');
	const { inAppUrl } = require('in-app-url');
	const { Loc } = require('loc');

	class RedirectAction extends BaseTimelineAction
	{
		execute()
		{
			if (this.entity.detailPageUrl === this.value)
			{
				return;
			}

			inAppUrl.open(this.value, this.getActionParams(), (url) => {
				qrauth.open({
					title: Loc.getMessage('CRM_TIMELINE_DESKTOP_VERSION'),
					redirectUrl: url.toString(),
					analyticsSection: 'crm',
				});
			});

			this.sendAnalytics();
		}

		getActionParams()
		{
			const route = inAppUrl.findRoute(this.value);

			if (route && route.hasName('crm:user'))
			{
				this.actionParams.backdrop = true;
			}

			return this.actionParams;
		}
	}

	module.exports = { RedirectAction };
});
