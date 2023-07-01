/**
 * @module crm/timeline/scheduler/providers/sms/clients-selector
 */
jn.define('crm/timeline/scheduler/providers/sms/clients-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');

	/**
	 * @class ClientsSelector
	 */
	class ClientsSelector extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				this.renderLabel(),
				this.renderClient(),
			);
		}

		renderLabel()
		{
			return Text({
				style: styles.label,
				text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_CLIENT'),
			});
		}

		renderClient()
		{
			if (this.props.showSkeleton)
			{
				return this.renderLine(250, 12, 4, 4);
			}

			let content;
			let onClick = () => {};

			const { name, phone, onOpenSelector } = this.props;

			if (Type.isStringFilled(name) && Type.isStringFilled(phone))
			{
				content = this.renderData({ name, phone });
				onClick = onOpenSelector;
			}
			else if (Type.isStringFilled(name))
			{
				content = this.renderEmptyPhone({ name });
			}
			else
			{
				content = this.renderEmpty();
			}

			return View(
				{
					style: styles.client,
					onClick,
				},
				...content,
			);
		}

		renderLine(width, height, marginTop = 0, marginBottom = 0)
		{
			const viewStyles = {
				width,
				height,
			};

			if (marginTop)
			{
				viewStyles.marginTop = marginTop;
			}

			if (marginBottom)
			{
				viewStyles.marginBottom = marginBottom;
			}

			const lineStyles = {
				width,
				height,
				borderRadius: height / 2,
				backgroundColor: '#DFE0E3',
			};

			return View(
				{ style: viewStyles },
				ShimmerView(
					{ animating: true },
					View({ style: lineStyles }),
				),
			);
		}

		renderData({ name, phone })
		{
			return [
				Text({
					numberOfLines: 1,
					ellipsize: 'end',
					style: styles.data,
					text: `${name}, `,
				}),
				View(
					{
						style: styles.phoneContainer,
					},
					Text({
						style: styles.data,
						text: phone,
					}),
					this.renderChevron(),
				),
			];
		}

		renderEmpty()
		{
			return [
				Text({
					style: styles.empty,
					text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_EMPTY_CLIENT'),
				}),
			];
		}

		renderEmptyPhone({ name })
		{
			return [
				View(
					{
						style: styles.clientWithEmptyPhone,
					},
					Text({
						numberOfLines: 1,
						ellipsize: 'end',
						style: styles.emptyPhoneData,
						text: `${name}`,
					}),
					this.renderChevron(),
				),
				View(
					{
						style: styles.addPhoneContainer,
					},
					BBCodeText({
						style: styles.addPhone,
						value: `[COLOR="#C48300"][URL="#"]${Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_ADD_PHONE_TO_CLIENT')}[/URL][/COLOR]`,
						onLinkClick: this.props.onAddPhone,
						linksUnderline: false,
					}),
				),
			];
		}

		renderChevron(clientWithEmptyPhone = false)
		{
			return Image({
				svg: {
					content: icons.chevron,
				},
				style: styles.chevron(clientWithEmptyPhone),
			});
		}
	}

	const icons = {
		chevron: '<svg width="10" height="6" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.3065 0.753906L5.66572 3.39469L5.00042 4.04969L4.34773 3.39469L1.70695 0.753906L0.775096 1.68576L5.00669 5.91735L9.23828 1.68576L8.3065 0.753906Z" fill="#2066B0"/></svg>',
	};

	const styles = {
		label: {
			fontSize: 14,
			color: '#828B95',
			marginBottom: 4,
		},
		client: {
			flexDirection: 'row',
			alignItems: 'center',
			flexWrap: 'wrap',
		},
		data: {
			fontSize: 16,
			color: '#0065A3',
		},
		clientWithEmptyPhone: {
			flexDirection: 'row',
			flexWrap: 'no-wrap',
			paddingRight: 10,
			alignItems: 'center',
		},
		phoneContainer: {
			flexDirection: 'row',
			flexWrap: 'no-wrap',
			alignItems: 'center',
		},
		chevron: (clientWithEmptyPhone) => {
			return {
				marginLeft: clientWithEmptyPhone ? -6 : 4,
				width: 10,
				height: 6,
			};
		},
		empty: {
			color: '#BDC1C6',
			fontSize: 16,
			marginLeft: 2,
		},
		emptyPhoneData: {
			fontSize: 16,
			color: '#0065A3',
			marginRight: 10,
		},
		addPhoneContainer: {
			borderBottomWidth: 1,
			borderBottomColor: '#C48300',
			borderStyle: 'dash',
			borderDashSegmentLength: 3,
			borderDashGapLength: 3,
		},
		addPhone: {
			fontSize: 16,
			color: '#C48300',
		},
	};

	module.exports = { ClientsSelector };
});
