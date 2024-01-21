/**
 * @module crm/timeline/scheduler/providers/go-to-chat/settings-block
 */
jn.define('crm/timeline/scheduler/providers/go-to-chat/settings-block', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { ClientsSelector } = require('crm/timeline/scheduler/providers/go-to-chat/clients-selector');
	const { ProvidersSelector } = require('crm/timeline/scheduler/providers/go-to-chat/providers-selector');

	/**
	 * @class SettingsBlock
	 */
	class SettingsBlock extends LayoutComponent
	{
		render()
		{
			const {
				layout,
				selectedClient,
				name,
				toPhoneId,
				communications,
				ownerInfo,
				typeId,
				channels,
				contactCenterUrl,
				currentChannelId,
				showShimmer,
				onChangeClientCallback: onPhoneSelectCallback,
				onChangeClientWithoutPhoneCallback: onClientWithoutPhoneSelectCallback,
				onChangeProviderCallback,
				onChangeProviderPhoneCallback,
				showAddPhoneToContactDrawer,
			} = this.props;

			return View(
				{
					style: {
						marginTop: 12,
						backgroundColor: AppTheme.colors.bgContentPrimary,
						borderRadius: 12,
						paddingTop: 16,
						paddingBottom: 18,
						paddingHorizontal: 18,
					},
				},
				new ClientsSelector({
					layout,
					selectedClient,
					name,
					toPhoneId,
					communications,
					ownerInfo,
					typeId,
					showShimmer,
					onPhoneSelectCallback,
					showAddPhoneToContactDrawer,
					onClientWithoutPhoneSelectCallback,
				}),
				this.canShowProviderSelector() && new ProvidersSelector({
					layout,
					channels,
					contactCenterUrl,
					currentChannelId,
					showShimmer,
					onChangeProviderCallback,
					onChangeProviderPhoneCallback,
				}),
			);
		}

		canShowProviderSelector()
		{
			const { currentChannelId, showShimmer } = this.props;

			return currentChannelId !== null || showShimmer;
		}
	}

	module.exports = { SettingsBlock };
});
