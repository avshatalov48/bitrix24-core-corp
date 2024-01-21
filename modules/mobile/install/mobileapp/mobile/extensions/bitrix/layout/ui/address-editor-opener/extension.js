/**
 * @module layout/ui/address-editor-opener
 */
jn.define('layout/ui/address-editor-opener', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { withCurrentDomain } = require('utils/url');
	const { Feature } = require('feature');

	/**
	 * @class AddressEditorOpener
	 */
	class AddressEditorOpener
	{
		constructor(parentWidget = null)
		{
			this.parentWidget = parentWidget || PageManager;
			this.editorWidget = null;
		}

		/**
		 * @public
		 * @param {?String} address
		 * @param {GeoPoint} geoPoint
		 * @param {?String} mode
		 * @param {?String} uid
		 * @param {?function} onAddressSelected
		 * @returns {Promise}
		 */
		async open({
					   address = null,
					   geoPoint = null,
					   mode = AddressEditorModes.edit,
					   uid = Random.getString(),
					   onAddressSelected = null,
				   })
		{
			const deviceGeoPosition = mode === AddressEditorModes.edit
				? await this.requestDeviceGeoPosition()
				: null
			;

			BX.addCustomEvent('Location::MobileAddressEditor::AddressSelected', (event) => {
				if (event.uid !== uid)
				{
					return;
				}

				if (typeof onAddressSelected === 'function')
				{
					onAddressSelected(event.address);
				}

				if (this.editorWidget)
				{
					this.editorWidget.close();
				}
			});

			const isGeoPointValid = geoPoint.hasAddress() || geoPoint.hasCoords();
			const editorUriParams = {
				address,
				geoPoint: isGeoPointValid ? JSON.stringify(geoPoint) : null,
				deviceGeoPosition: deviceGeoPosition ? JSON.stringify(deviceGeoPosition) : null,
				isEditable: mode === AddressEditorModes.edit ? 'Y' : 'N',
				uid,
			};
			const editorUri = `/mobile/location/address.php?${
				Object.keys(editorUriParams)
					.map((key) => {
						const value = editorUriParams[key];

						return `${key}=${value ? encodeURIComponent(editorUriParams[key]) : ''}`;
					})
					.join('&')}`;

			return new Promise((resolve) => {
				this.parentWidget.openWidget('web', {
					page: {
						url: withCurrentDomain(editorUri),
					},
					modal: true,
					titleParams: {
						text: this.getTitle({
							mode,
							address,
							isGeoPointValid,
						}),
					},
					leftButtons: [{
						svg: {
							content: SvgIcons.arrowBottom,
						},
						isCloseButton: true,
					}],
					backgroundColor: AppTheme.colors.bgPrimary,
					onReady: (widget) => {
						this.editorWidget = widget;
						widget.enableNavigationBarBorder(false);
						resolve();
					},
				});
			});
		}

		async requestDeviceGeoPosition()
		{
			return new Promise((resolve) => {
				if (!Feature.isGeoPositionSupported())
				{
					resolve(null);
				}

				device.getLocation({ accuracy: 'approximate' })
					.then((response) => resolve(response))
					.catch(() => resolve(null));
			});
		}

		/**
		 * @private
		 * @param {String} mode
		 * @param {?String} address
		 * @param {boolean} isGeoPointValid
		 * @returns {String}
		 */
		getTitle({ mode, address, isGeoPointValid })
		{
			if (mode === AddressEditorModes.view)
			{
				return Loc.getMessage('ADDRESS_EDITOR_OPENER_VIEW_ADDRESS');
			}

			if (mode === AddressEditorModes.edit)
			{
				if (address || isGeoPointValid)
				{
					return Loc.getMessage('ADDRESS_EDITOR_OPENER_EDIT_ADDRESS');
				}

				return Loc.getMessage('ADDRESS_EDITOR_OPENER_SPECIFY_ADDRESS');
			}

			return '';
		}
	}

	const SvgIcons = {
		arrowBottom: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.722 6.79175L10.9495 10.5643L9.99907 11.5L9.06666 10.5643L5.29411 6.79175L3.96289 8.12297L10.008 14.1681L16.0532 8.12297L14.722 6.79175Z" fill="#A8ADB4"/></svg>',
	};

	const AddressEditorModes = {
		view: 'view',
		edit: 'edit',
	};

	module.exports = {
		AddressEditorOpener,
		AddressEditorModes,
	};
});
