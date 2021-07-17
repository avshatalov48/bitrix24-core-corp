import {Loc} from 'main.core';
import {Manager} from 'salescenter.manager';

export default {
	data()
	{
		return {
		};
	},
	methods: {
		connect()
		{
			const loader = new BX.Loader({size: 200});
			loader.show(document.body);

			BX.Salescenter.Manager.connect({
				no_redirect: 'Y',
				context: this.$root.$app.context,
			}).then(() =>
			{
				BX.Salescenter.Manager.loadConfig().then((result) =>
				{
					loader.hide();

					if(result.isSiteExists)
					{
						this.$root.$app.isSiteExists = result.isSiteExists;
						this.$root.$app.isOrderPublicUrlExists = true;
						this.$root.$app.orderPublicUrl = result.orderPublicUrl;
						this.$root.$app.isOrderPublicUrlAvailable = result.isOrderPublicUrlAvailable;
					}

					this.$emit('on-successfully-connected');
				});
			}).catch(function()
			{
				loader.hide();
			});
		},
		checkRecycle()
		{
			Manager.openConnectedSite(true);
		},
		openConnectedSite()
		{
			Manager.openConnectedSite();
		},
	},
	computed: {
		isOrderPageDeleted()
		{
			return (this.$root.$app.isSiteExists && !this.isOrderPublicUrlExists);
		},
		isOrderPublicUrlExists()
		{
			return this.$root.$app.isOrderPublicUrlExists;
		},
	},
	template: `
		<div class="salescenter-app-page-content salescenter-app-start-wrapper">
			<div class="ui-title-1 ui-text-center ui-color-medium" style="margin-bottom: 20px;">
				${Loc.getMessage('SALESCENTER_INFO_TEXT_TOP_2')}
			</div>
			<div class="ui-hr ui-mv-25"></div>
			<template v-if="isOrderPublicUrlExists">
				<div class="salescenter-title-5 ui-title-5 ui-text-center ui-color-medium">
					${Loc.getMessage('SALESCENTER_INFO_TEXT_BOTTOM_PUBLIC')}
				</div>
				<div style="padding-top: 5px;" class="ui-text-center">
					<div class="ui-btn ui-btn-primary ui-btn-lg" @click="openConnectedSite">
						${Loc.getMessage('SALESCENTER_INFO_PUBLIC')}
					</div>
				</div>
			</template>
			<template v-else-if="isOrderPageDeleted">
				<div class="salescenter-title-5 ui-title-5 ui-text-center ui-color-medium">
					${Loc.getMessage('SALESCENTER_INFO_ORDER_PAGE_DELETED')}
				</div>
				<div style="padding-top: 5px;" class="ui-text-center">
					<div
						@click="checkRecycle"
						class="ui-btn ui-btn-primary ui-btn-lg"
					>
						${Loc.getMessage('SALESCENTER_CHECK_RECYCLE')}
					</div>
				</div>
			</template>
			<template v-else>
				<div class="salescenter-title-5 ui-title-5 ui-text-center ui-color-medium">
					${Loc.getMessage('SALESCENTER_INFO_TEXT_BOTTOM_2')}
				</div>
				<div style="padding-top: 5px;" class="ui-text-center">
					<div
						@click="connect"
						class="ui-btn ui-btn-primary ui-btn-lg"
					>
						${Loc.getMessage('SALESCENTER_INFO_CREATE')}
					</div>
				</div>
				<div style="padding-top: 5px;" class="ui-text-center">
					<div
						@click="BX.Salescenter.Manager.openHowPayDealWorks(event)"
						class="ui-btn ui-btn-link ui-btn-lg"
					>
						${Loc.getMessage('SALESCENTER_HOW')}
					</div>
				</div>
			</template>
		</div>
	`,
}
