(() => {
	const require = (ext) => jn.require(ext);

	const { Alert } = require('alert');
	const { PureComponent } = require('layout/pure-component');
	const { WorkflowList } = require('bizproc/workflow/list');
	const { Loc } = require('loc');
	const { inAppUrl } = require('in-app-url');

	class TabComponent extends PureComponent
	{
		TAB_ID = 'bizproc';
		isActive = true;
		inactiveTime;
		listRef;

		render()
		{
			return new WorkflowList({
				layout,
				ref: (ref) => {
					this.listRef = ref;
				},
			});
		}

		async componentDidMount()
		{
			BX.addCustomEvent('onAppActive', () => this.onAppActive());
			BX.addCustomEvent('onAppPaused', () => this.onAppPaused());

			BX.addCustomEvent('onTabsSelected', (tabId) => this.onTabsSelected(tabId));
			BX.addCustomEvent('onTabsReSelected', (tabId) => this.onTabsReSelected(tabId));

			let openUrl = BX.prop.getString(
				{ openUrl: BX.componentParameters.get('openUrl', null) },
				'openUrl',
				'',
			);
			if (openUrl !== '')
			{
				// remove hash
				if (openUrl.includes('#'))
				{
					openUrl = openUrl.slice(0, openUrl.indexOf('#'));
				}

				setTimeout(() => inAppUrl.open(openUrl), 300);
			}
		}

		onAppActive()
		{
			this.askToReload();
		}

		onAppPaused()
		{
			this.inactiveTime ??= Date.now();
		}

		onTabsSelected(tabId)
		{
			this.isActive = (tabId === this.TAB_ID);

			if (this.isActive)
			{
				this.askToReload();
			}
			else
			{
				this.inactiveTime ??= Date.now();
			}
		}

		onTabsReSelected(tabId)
		{
			if (this.isActive)
			{
				this.reload();
			}
		}

		askToReload()
		{
			if (this.isActive && this.inactiveTime)
			{
				const minutesPassed = Math.round((Date.now() - this.inactiveTime) / 60000);
				if (minutesPassed >= 30)
				{
					this.reload();
				}
			}

			if (this.isActive)
			{
				this.inactiveTime = null;
			}
		}

		reload()
		{
			if (this.listRef)
			{
				this.listRef.reload();
			}
		}
	}

	BX.onViewLoaded(() => {
		if (BX.componentParameters.get('setTitle', false) === true)
		{
			layout.setTitle({
				text: Loc.getMessage('BPMOBILE_TAB_WORKFLOW_LIST'),
				useLargeTitleMode: true,
			});
		}

		if (env.extranet)
		{
			Alert.alert(
				Loc.getMessage('BPMOBILE_TAB_WORKFLOW_LIST_ALERT_EXTRANET_ACCESS_DENIED_TITLE'),
				Loc.getMessage('BPMOBILE_TAB_WORKFLOW_LIST_ALERT_EXTRANET_ACCESS_DENIED_TEXT'),
				() => {
					layout.back();
					layout.close();
				},
				Loc.getMessage('BPMOBILE_TAB_WORKFLOW_LIST_ALERT_CONFIRM'),
			);

			return;
		}

		layout.showComponent(new TabComponent());
	});
})();
