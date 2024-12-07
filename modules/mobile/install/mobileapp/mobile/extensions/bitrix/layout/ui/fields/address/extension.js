/**
 * @module layout/ui/fields/address
 */
jn.define('layout/ui/fields/address', (require, exports, module) => {

	const { BaseField } = require('layout/ui/fields/base');
	const { AddressEditorOpener, AddressEditorModes } = require('layout/ui/address-editor-opener');
	const { MapOpener, GeoPoint } = require('layout/ui/map-opener');
	const { location } = require('assets/common');
	const { throttle } = require('utils/function');
	const { Loc } = require('loc');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { AddressValueConverter } = require('layout/ui/fields/address/value-converter');
	const { stringify } = require('utils/string');

	/**
	 * @class AddressField
	 */
	class AddressField extends BaseField
	{
		constructor(props)
		{
			super(props);

			this.contextMenu = null;

			if (!this.isReadOnly())
			{
				this.customContentClickHandler = throttle(this.onAddressFieldContentClick, 500, this);
			}

			this.openEditor = this.openEditorHandler.bind(this);
		}

		prepareSingleValue(value)
		{
			if (!Array.isArray(value))
			{
				value = [value];
			}

			return value;
		}

		isEmptyValue(value)
		{
			return !Array.isArray(value) || value.length === 0 || !value[0];
		}

		getDeleteValue()
		{
			const currentAddress = AddressValueConverter.convertFromValue(this.getValue());

			return AddressValueConverter.convertToValue(
				{
					id: currentAddress.id,
					json: null,
					text: null,
					coords: [],
				}
			);
		}

		renderEditableContent()
		{
			return this.renderReadOnlyContent();
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'column',
					},
				},
				this.renderAddress(),
			);
		}

		renderAddress()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						marginTop: 4,
						alignItems: 'flex-start',
					},
					onClick: () => {
						this.contextMenu = new ContextMenu({
							actions: this.getContextMenuActions(),
							params: {
								showCancelButton: true,
							},
						});
						this.contextMenu.show(this.getParentWidget());
					},
					onLongClick: () => {
						const callback = this.getContentLongClickHandler();
						if (callback)
						{
							callback(
								AddressValueConverter.convertFromValue(this.getValue()).text
							);
						}
					},
				},
				Image({
					style: {
						marginRight: 10,
						height: 19,
						width: 22,
					},
					svg: {
						content: location(),
					},
				}),
				Text({
					style: {
						flexShrink: 2,
						fontSize: 14,
						color: AppTheme.colors.accentMainLinks,
					},
					text: jnComponent.convertHtmlEntities(
						AddressValueConverter.convertFromValue(this.getValue()).text
					),
				})
			);
		}

		/**
		 * @param {String} mode
		 */
		openEditorHandler(mode)
		{
			const addressEditorOpener = new AddressEditorOpener(this.getParentWidget());

			const addressValue = AddressValueConverter.convertFromValue(this.getValue());
			addressEditorOpener.open({
				address: addressValue.json,
				geoPoint: new GeoPoint({
					address: addressValue.text,
					coords: addressValue.coords
				}),
				mode,
				/**
				 * Each value of a multiple field must have its unique uid
				 */
				uid: Random.getString(),
				onAddressSelected: (address) => {
					const currentAddress = AddressValueConverter.convertFromValue(this.getValue());
					const newValue = AddressValueConverter.convertToValue({
						id: currentAddress.id,
						json: address.value,
						text: address.text,
						coords: address.coords,
					});
					this.handleChange(newValue);
				},
			});
		}

		getContextMenuActions()
		{
			const result = [];

			result.push({
				id: ContextMenuActions.view,
				title: Loc.getMessage('FIELDS_ADDRESS_CONTEXT_MENU_ACTION_VIEW'),
				sectionCode: 'main',
				data: {
					svgIcon: SvgIcons.view,
				},
				onClickCallback: () => {
					this.contextMenu.close(() => {
						this.openEditor(AddressEditorModes.view);
					});
				},
			});

			if (this.isEditable())
			{
				result.push({
					id: ContextMenuActions.edit,
					title: Loc.getMessage('FIELDS_ADDRESS_CONTEXT_MENU_ACTION_EDIT'),
					sectionCode: 'main',
					data: {
						svgIcon: SvgIcons.edit,
					},
					onClickCallback: () => {
						this.contextMenu.close(() => {
							this.openEditor(AddressEditorModes.edit);
						});
					},
				});

				result.push({
					id: ContextMenuActions.delete,
					title: Loc.getMessage('FIELDS_ADDRESS_CONTEXT_MENU_ACTION_DELETE'),
					sectionCode: 'delete',
					data: {
						svgIcon: SvgIcons.delete,
					},
					onClickCallback: () => {
						this.handleChange(this.getDeleteValue());
						this.contextMenu.close();
					},
				});
			}

			result.push({
				id: ContextMenuActions.map,
				title: Loc.getMessage('FIELDS_ADDRESS_CONTEXT_MENU_ACTION_MAP_APP'),
				sectionCode: 'main',
				data: {
					svgIcon: SvgIcons.map,
				},
				onClickCallback: () => {
					this.contextMenu.close(() => {
						const mapOpener = new MapOpener();
						const addressValue = AddressValueConverter.convertFromValue(this.getValue());

						return mapOpener.open({
							address: addressValue.text,
							coords: addressValue.coords,
						});
					});
				},
			});

			return result;
		}

		onAddressFieldContentClick()
		{
			if (this.isEmpty())
			{
				this.openEditor(AddressEditorModes.edit);
			}
		}

		handleAdditionalFocusActions()
		{
			this.removeFocus();

			return Promise.resolve();
		}

		canCopyValue()
		{
			return true;
		}

		prepareValueToCopy()
		{
			const [address] = this.getValue();

			return stringify(address);
		}

		copyMessage()
		{
			return Loc.getMessage('FIELDS_ADDRESS_VALUE_COPIED');
		}
	}

	const ContextMenuActions = {
		view: 'view',
		edit: 'edit',
		delete: 'delete',
		map: 'map',
	};

	const SvgIcons = {
		view: '<svg width="20" height="21" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M19.919 10.5001C19.919 15.9782 15.4781 20.4191 9.99999 20.4191C4.52188 20.4191 0.0809937 15.9782 0.0809937 10.5001C0.0809937 5.02194 4.52188 0.581055 9.99999 0.581055C15.4781 0.581055 19.919 5.02194 19.919 10.5001ZM11.4668 9.40107L7.79577 9.40163V10.4518H8.89788V14.9085H7.79577V16.0106H12.2042V14.9085H11.4668V9.40107ZM9.99999 7.65635C10.8562 7.65635 11.5502 6.96228 11.5502 6.1061C11.5502 5.24992 10.8562 4.55585 9.99999 4.55585C9.14381 4.55585 8.44974 5.24992 8.44974 6.1061C8.44974 6.96228 9.14381 7.65635 9.99999 7.65635Z" fill="#828B95"/></svg>',
		edit: '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M13.6309 0.305535C13.827 0.110098 14.1445 0.11119 14.3393 0.307971L17.2975 3.29735C17.4912 3.49313 17.4902 3.80873 17.2951 4.00318L5.891 15.3699L2.22937 11.6697L13.6309 0.305535ZM0.528867 16.6055C0.494242 16.7365 0.53134 16.875 0.625323 16.9714C0.72178 17.0679 0.860281 17.1049 0.991363 17.0679L5.08458 15.9651L1.63193 12.5135L0.528867 16.6055Z" fill="#828B95"/></svg>',
		map: '<svg width="20" height="19" viewBox="0 0 20 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.68161 1.10609C0.68161 0.66392 1.12819 0.361571 1.53873 0.525787L5.85849 2.25369V17.7843L1.07449 15.8707C0.837204 15.7758 0.68161 15.546 0.68161 15.2904V1.10609Z" fill="#828B95"/><path d="M7.41162 2.2536L12.5885 1.21823V16.7489L7.41162 17.7842V2.2536Z" fill="#828B95"/><path d="M18.9256 3.13183L14.1416 1.21823V16.7489L18.4614 18.4768C18.8719 18.641 19.3185 18.3386 19.3185 17.8965V3.71213C19.3185 3.45656 19.1629 3.22674 18.9256 3.13183Z" fill="#828B95"/></svg>',
		delete: '<svg width="15" height="19" viewBox="0 0 15 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.61456 0.371582H5.88811V1.74243H1.93521C1.10678 1.74243 0.435211 2.414 0.435211 3.24243V4.48502H14.0675V3.24243C14.0675 2.414 13.3959 1.74243 12.5675 1.74243H8.61456V0.371582ZM1.79843 5.8562H12.7042L11.6942 17.2859C11.6486 17.802 11.2163 18.1978 10.6981 18.1978H3.80454C3.28637 18.1978 2.85403 17.802 2.80842 17.2859L1.79843 5.8562Z" fill="#828B95"/></svg>',
	};

	module.exports = {
		AddressType: 'address',
		AddressField: (props) => new AddressField(props),
		AddressValueConverter,
	};
});
