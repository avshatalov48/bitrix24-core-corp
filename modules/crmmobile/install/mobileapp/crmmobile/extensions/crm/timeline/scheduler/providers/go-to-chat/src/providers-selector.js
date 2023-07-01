/**
 * @module crm/timeline/scheduler/providers/go-to-chat/providers-selector
 */
jn.define('crm/timeline/scheduler/providers/go-to-chat/providers-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { SendersSelector } = require('crm/timeline/ui/senders-selector');
	const { line } = require('utils/skeleton');

	/**
	 * @class ProvidersSelector
	 */
	class ProvidersSelector extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.sendersSelector = null;

			this.showProviderSelector = this.show.bind(this);
		}

		show()
		{
			if (!this.sendersSelector)
			{
				const {
					currentChannelId,
					channels: senders,
					contactCenterUrl,
					onChangeProviderCallback: onChangeSenderCallback,
					onChangeProviderPhoneCallback: onChangePhoneCallback,
				} = this.props;

				const currentSender = this.findChannelById(currentChannelId);
				const currentPhoneId = currentSender.fromList[0].id;

				this.sendersSelector = new SendersSelector({
					currentPhoneId,
					currentSender,
					senders,
					contactCenterUrl,
					onChangeSenderCallback,
					onChangePhoneCallback,
				});
			}

			this.sendersSelector.show(this.props.layout);
		}

		findChannelById(id)
		{
			return this.props.channels.find((channel) => channel.id === id);
		}

		render()
		{
			return View(
				{
					style: styles.container,
				},
				this.renderLabel(),
				!this.props.showShimmer && this.renderProvider(),
				this.props.showShimmer && line(100, 11, 4, 3),
			);
		}

		renderLabel()
		{
			return Text({
				style: styles.label,
				text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_GTC_MESSENGER_SELECTOR_LABEL'),
			});
		}

		renderProvider()
		{
			return View(
				{
					style: styles.providerOuterContainer,
				},
				View(
					{
						testId: 'TimelineGoToChatShowProvidersSelector',
						style: styles.providerContainer,
					},
					BBCodeText({
						style: styles.provider,
						value: `[COLOR="#828B95"][URL="#"]${this.currentChannel.shortName}[/URL][/COLOR]`,
						onLinkClick: this.showProviderSelector,
						linksUnderline: false,
						numberOfLines: 1,
						ellipsize: 'end',
					}),
				),
				this.renderArrow(),
			);
		}

		get currentChannel()
		{
			const { channels, currentChannelId } = this.props;

			return channels.find((channel) => channel.id === currentChannelId);
		}

		renderArrow()
		{
			return Image({
				style: styles.arrow,
				svg: {
					content: icons.arrow,
				},
			});
		}
	}

	const icons = {
		arrow: '<svg width="10" height="6" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.3065 0.753906L5.66572 3.39469L5.00042 4.04969L4.34773 3.39469L1.70695 0.753906L0.775096 1.68576L5.00669 5.91735L9.23828 1.68576L8.3065 0.753906Z" fill="#BDC1C6"/></svg>',
	};

	const styles = {
		container: {
			marginTop: 7,
			flexDirection: 'row',
		},
		label: {
			fontSize: 14,
			color: '#959ca4',
		},
		arrow: {
			width: 10,
			height: 6,
			marginLeft: -8,
		},
		providerOuterContainer: {
			flexDirection: 'row',
			alignItems: 'center',
		},
		providerContainer: {
			borderBottomWidth: 1,
			borderBottomColor: '#828B95',
			borderStyle: 'dash',
			borderDashSegmentLength: 3,
			borderDashGapLength: 3,
			marginRight: 12,
		},
		provider: {
			fontSize: 14,
			color: '#828B95',
		},
	};

	module.exports = { ProvidersSelector };
});
