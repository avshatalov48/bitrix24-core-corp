/**
 * @module crm/timeline/scheduler/providers/go-to-chat/settings-block
 */
jn.define('crm/timeline/scheduler/providers/go-to-chat/settings-block', (require, exports, module) => {
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
				onChangeProviderCallback,
				onChangeProviderPhoneCallback,
				showAddPhoneToContactDrawer,
			} = this.props;

			return View(
				{
					style: {
						marginTop: 12,
						backgroundColor: '#ffffff',
						borderRadius: 12,
						paddingTop: 16,
						paddingBottom: 18,
						paddingHorizontal: 18,
					},
				},
				new ClientsSelector({
					layout,
					name,
					toPhoneId,
					communications,
					ownerInfo,
					typeId,
					showShimmer,
					onPhoneSelectCallback,
					showAddPhoneToContactDrawer,
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
