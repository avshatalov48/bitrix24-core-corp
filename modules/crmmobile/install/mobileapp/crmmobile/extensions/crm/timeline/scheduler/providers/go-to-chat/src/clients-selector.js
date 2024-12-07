/**
 * @module crm/timeline/scheduler/providers/go-to-chat/clients-selector
 */
jn.define('crm/timeline/scheduler/providers/go-to-chat/clients-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { ContextMenu } = require('layout/ui/context-menu');
	const AppTheme = require('apptheme');
	const { CommunicationSelector } = require('crm/communication/communication-selector');
	const { Line } = require('utils/skeleton');

	/**
	 * @class ClientsSelector
	 */
	class ClientsSelector extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.showClientSelector = this.showClientSelector.bind(this);
			this.showSelector = this.showSelector.bind(this);
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
					this.props.showShimmer && Line(100, 11, 4, 3),
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
						value: `[C type=dot textColor=${AppTheme.colors.base3} lineColor=${AppTheme.colors.base3}][COLOR=${AppTheme.colors.base3}][URL="#"]${name}[/URL][/COLOR][/C]`,
						onLinkClick: this.showSelector,
						linksUnderline: false,
						numberOfLines: 1,
						ellipsize: 'end',
					}),
				),
				hasCommunicationsSelection && this.renderArrow(),
			);
		}

		showSelector()
		{
			if (this.getCountCommunicationsWithPhones() > 0)
			{
				this.showClientSelector();

				return;
			}

			this.showClientWithoutPhonesSelector();
		}

		hasCommunicationsSelection()
		{
			return (this.hasManyClientWithPhones() || this.hasOnlyManyClientsWithoutPhones());
		}

		hasManyClientWithPhones()
		{
			const withPhones = this.getCountCommunicationsWithPhones();

			return (withPhones > 1);
		}

		hasOnlyManyClientsWithoutPhones()
		{
			const { communications } = this.props;

			const total = communications.length;
			const withPhones = this.getCountCommunicationsWithPhones();

			return (total > 1 && total - withPhones > 1);
		}

		getCountCommunicationsWithPhones()
		{
			const { communications } = this.props;

			let clientsWithPhones = 0;
			communications.forEach((communication) => {
				if (Array.isArray(communication.phones) && communication.phones.length > 0)
				{
					clientsWithPhones++;
				}
			});

			return clientsWithPhones;
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
				value: `[C type=dot textColor=${AppTheme.colors.accentSoftElementOrange1} lineColor=${AppTheme.colors.accentSoftElementOrange1}][COLOR=${AppTheme.colors.accentMainWarning}][URL="#"]${addPhoneTitle}[/URL][/COLOR][/C]`,
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
				selectedId: selectedPhoneId,
				onSelectCallback: onPhoneSelectCallback,
			});
		}

		showClientWithoutPhonesSelector()
		{
			const menu = new ContextMenu(this.getMenuConfig());

			void menu.show(this.props.layout);
		}

		getMenuConfig()
		{
			return {
				testId: 'TimelineGoToChatClientWithoutPhonesSelector',
				actions: this.getClientWithoutPhonesMenuActions(),
				params: {
					shouldResizeContent: true,
					showCancelButton: true,
					title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_GTC_CLIENTS_SELECTOR_TITLE'),
				},
			};
		}

		getClientWithoutPhonesMenuActions()
		{
			const {
				communications,
				selectedClient: {
					entityTypeId,
					entityId,
				},
				onClientWithoutPhoneSelectCallback,
			} = this.props;

			const items = [];
			communications.forEach((communication) => {
				const {
					entityTypeId: communicationEntityTypeId,
					entityId: communicationEntityId,
					caption: title,
				} = communication;

				items.push({
					id: `client-${communicationEntityTypeId}-${communicationEntityId}`,
					title,
					isSelected: (
						entityTypeId === communicationEntityTypeId
						&& entityId === communicationEntityId
					),
					onClickCallback: () => onClientWithoutPhoneSelectCallback(communication),
				});
			});

			return items;
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
		arrow: `<svg width="10" height="6" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.3065 0.753906L5.66572 3.39469L5.00042 4.04969L4.34773 3.39469L1.70695 0.753906L0.775096 1.68576L5.00669 5.91735L9.23828 1.68576L8.3065 0.753906Z" fill="${AppTheme.colors.base5}"/></svg>`,
	};

	const styles = {
		container: {
			flexDirection: 'row',
			flexWrap: 'wrap',
		},
		label: {
			fontSize: 14,
			color: AppTheme.colors.base4,
			marginRight: 4,
		},
		clientOuterContainer: {
			flexDirection: 'row',
			alignItems: 'center',
			flexWrap: 'no-wrap',
			marginRight: 10,
		},
		clientContainer: (hasCommunicationsSelection) => ({
			marginRight: hasCommunicationsSelection ? 12 : 4,
			color: AppTheme.colors.base3,
		}),
		client: {
			fontSize: 14,
			color: AppTheme.colors.base3,
		},
		addPhone: {
			fontSize: 14,
			color: AppTheme.colors.accentMainWarning,
		},
		arrow: {
			width: 10,
			height: 6,
			marginLeft: -8,
		},
	};

	module.exports = { ClientsSelector };
});
