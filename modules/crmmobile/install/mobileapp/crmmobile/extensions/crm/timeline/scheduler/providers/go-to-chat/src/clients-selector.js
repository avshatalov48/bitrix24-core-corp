/**
 * @module crm/timeline/scheduler/providers/go-to-chat/clients-selector
 */
jn.define('crm/timeline/scheduler/providers/go-to-chat/clients-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { CommunicationSelector } = require('crm/communication/communication-selector');
	const { line } = require('utils/skeleton');

	/**
	 * @class ClientsSelector
	 */
	class ClientsSelector extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.showClientSelector = this.showClientSelector.bind(this);
		}

		render()
		{
			return View(
				{},
				View(
					{
						style: styles.container,
					},
					this.renderLabel(),
					!this.props.showShimmer && this.renderClient(),
					!this.props.showShimmer && this.renderAddPhoneLink(),
					this.props.showShimmer && line(100, 11, 4, 3),
				),
			);
		}

		renderLabel()
		{
			return Text({
				style: styles.label,
				text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_GTC_CLIENT_SELECTOR_LABEL'),
			});
		}

		renderClient()
		{
			const { name } = this.props;
			const hasCommunicationsSelection = this.hasCommunicationsSelection();

			return View(
				{
					style: styles.clientOuterContainer,
				},
				View(
					{
						testId: 'TimelineGoToChatShowClientsSelector',
						style: styles.clientContainer(hasCommunicationsSelection),
					},
					BBCodeText({
						style: styles.client,
						value: `[COLOR="#828B95"][URL="#"]${name}[/URL][/COLOR]`,
						onLinkClick: this.showClientSelector,
						linksUnderline: false,
						numberOfLines: 1,
						ellipsize: 'end',
					}),
				),
				hasCommunicationsSelection && this.renderArrow(),
			);
		}

		hasCommunicationsSelection()
		{
			const { communications, ownerInfo, typeId } = this.props;

			return CommunicationSelector.hasActions({
				communications,
				ownerInfo,
				typeId,
			});
		}

		renderAddPhoneLink()
		{
			if (this.hasPhone())
			{
				return null;
			}

			const addPhoneTitle = Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_GTC_ADD_PHONE_TO_CLIENT');
			const { showAddPhoneToContactDrawer } = this.props;

			return BBCodeText({
				style: styles.addPhone,
				value: `[COLOR="#C48300"][URL="#"]${addPhoneTitle}[/URL][/COLOR]`,
				onLinkClick: showAddPhoneToContactDrawer,
				linksUnderline: false,
			});
		}

		hasPhone()
		{
			return Type.isStringFilled(this.props.toPhoneId);
		}

		showClientSelector()
		{
			const {
				layout,
				communications,
				ownerInfo,
				typeId,
				toPhoneId: selectedPhoneId,
				onPhoneSelectCallback,
			} = this.props;

			CommunicationSelector.show({
				layout,
				communications,
				ownerInfo,
				typeId,
				selectedPhoneId,
				onPhoneSelectCallback,
			});
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
			flexDirection: 'row',
			flexWrap: 'wrap',
		},
		label: {
			fontSize: 14,
			color: '#959ca4',
			marginRight: 4,
		},
		clientOuterContainer: {
			flexDirection: 'row',
			alignItems: 'center',
			flexWrap: 'no-wrap',
		},
		clientContainer: (hasCommunicationsSelection) => ({
			borderBottomWidth: 1,
			borderBottomColor: '#828b95',
			borderStyle: 'dash',
			borderDashSegmentLength: 3,
			borderDashGapLength: 3,
			marginRight: hasCommunicationsSelection ? 12 : 4,
		}),
		client: {
			fontSize: 14,
			color: '#828b95',
		},
		addPhone: {
			fontSize: 14,
			color: '#c48300',
			borderBottomWidth: 1,
			borderBottomColor: '#c48300',
			borderStyle: 'dash',
			borderDashSegmentLength: 3,
			borderDashGapLength: 3,
		},
		arrow: {
			width: 10,
			height: 6,
			marginLeft: -8,
		},
	};

	module.exports = { ClientsSelector };
});
