/**
 * @module crm/document/context-menu
 */
jn.define('crm/document/context-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { get } = require('utils/object');
	const { EntityDetailOpener } = require('crm/entity-detail/opener');
	const { TypeId } = require('crm/type');

	/**
	 * @class CrmDocumentContextMenu
	 */
	class CrmDocumentContextMenu
	{
		constructor(props = {})
		{
			this.props = props;
		}

		/**
		 * @return {boolean}
		 */
		get isQrCodeEnabled()
		{
			return get(this.props, 'document.qrCodeEnabled', false);
		}

		/**
		 * @return {boolean}
		 */
		get isStampsEnabled()
		{
			const stampsEnabled = get(this.props, 'document.stampsEnabled', false);

			return stampsEnabled && this.canToggleStamps;
		}

		/**
		 * @return {boolean}
		 */
		get canToggleQrCode()
		{
			return get(this.props, 'document.changeQrCodeEnabled', false);
		}

		/**
		 * @return {boolean}
		 */
		get canToggleStamps()
		{
			return get(this.props, 'document.changeStampsEnabled', false);
		}

		/**
		 * @return {boolean}
		 */
		get hasRequisites()
		{
			return Boolean(this.props.myCompanyRequisites || this.props.clientRequisites);
		}

		open()
		{
			const menu = dialogs.createPopupMenu();
			menu.setData(
				this.getItems().map(({ onClick, ...other }) => other),
				this.getSections(),
				(event, item) => {
					if (event === 'onItemSelected')
					{
						this.executeItemAction(item.id);
					}
				},
			);
			menu.show();
		}

		getItems()
		{
			const items = [
				{
					id: 'qr',
					sectionCode: 'documents',
					title: Loc.getMessage('M_CRM_DOCUMENT_CONTEXT_MENU_ITEM_QR_CODE_TITLE'),
					checked: this.isQrCodeEnabled,
					disable: !this.canToggleQrCode,
					onClick: () => {
						if (this.props.onChangeQrCode)
						{
							this.props.onChangeQrCode(!this.isQrCodeEnabled);
						}
					},
				},
				{
					id: 'stamps',
					sectionCode: 'documents',
					title: Loc.getMessage('M_CRM_DOCUMENT_CONTEXT_MENU_ITEM_STAMP_TITLE'),
					checked: this.isStampsEnabled,
					disabled: !this.canToggleStamps,
					onClick: () => {
						if (this.props.onChangeStamps)
						{
							this.props.onChangeStamps(!this.isStampsEnabled);
						}
					},
				},
			];

			if (this.props.myCompanyRequisites)
			{
				items.push({
					id: 'company',
					sectionCode: 'requisites',
					title: Loc.getMessage('M_CRM_DOCUMENT_CONTEXT_MENU_ITEM_COMPANY_TITLE'),
					iconUrl: this.getIcon('company'),
					onClick: () => this.openCompanyDetails(this.props.myCompanyRequisites),
				});
			}

			if (this.props.clientRequisites)
			{
				items.push({
					id: 'client',
					sectionCode: 'requisites',
					title: Loc.getMessage('M_CRM_DOCUMENT_CONTEXT_MENU_ITEM_CLIENT_TITLE'),
					iconUrl: this.getIcon(
						this.props.clientRequisites.entityTypeId === TypeId.Company ? 'company' : 'client',
					),
					onClick: () => this.openCompanyDetails(this.props.clientRequisites),
				});
			}

			return items;
		}

		getSections()
		{
			const sections = [
				{ id: 'documents', title: Loc.getMessage('M_CRM_DOCUMENT_CONTEXT_MENU_TITLE') },
			];
			if (this.hasRequisites)
			{
				sections.push({
					id: 'requisites',
					title: Loc.getMessage('M_CRM_DOCUMENT_CONTEXT_MENU_REQUISITES_TITLE'),
				});
			}
			return sections;
		}

		getIcon(code)
		{
			const allowedIcons = [
				'company',
				'client',
			];

			if (!allowedIcons.includes(code))
			{
				return '';
			}

			return `/bitrix/mobileapp/crmmobile/extensions/crm/document/context-menu/icons/${code}.png`;
		}

		executeItemAction(id)
		{
			const menuItem = this.getItems().find((item) => item.id === id);
			if (!menuItem)
			{
				return;
			}
			if (menuItem.onClick)
			{
				menuItem.onClick();
			}
		}

		openCompanyDetails({ entityId, entityTypeId, entityName })
		{
			const payload = { entityId, entityTypeId };
			const widgetParams = {};

			if (entityName)
			{
				widgetParams.titleParams = {
					text: entityName,
				};
			}

			EntityDetailOpener.open(payload, widgetParams, this.props.layoutWidget);
		}
	}

	module.exports = { CrmDocumentContextMenu };
});
