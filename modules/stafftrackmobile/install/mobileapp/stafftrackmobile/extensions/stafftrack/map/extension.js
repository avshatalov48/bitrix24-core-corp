/**
 * @module stafftrack/map
 */
jn.define('stafftrack/map', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Color, Corner, Indent } = require('tokens');
	const { downloadImages } = require('asset-manager');
	const { withCurrentDomain } = require('utils/url');
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { showToast } = require('toast');
	const { outline: { alert } } = require('assets/icons');
	const { Line } = require('utils/skeleton');
	const { Haptics } = require('haptics');
	const { confirmDefaultAction } = require('alert');

	const { Switcher, SwitcherMode } = require('ui-system/blocks/switcher');
	const { ChipButton, ChipButtonDesign, ChipButtonMode } = require('ui-system/blocks/chips/chip-button');
	const { Text5, Text4 } = require('ui-system/typography/text');
	const { Icon } = require('ui-system/blocks/icon');
	const { TextField } = require('ui-system/typography/text-field');

	const { LocationMenu } = require('stafftrack/map/location-menu');
	const { DisabledGeoAha } = require('stafftrack/map/disabled-geo-aha');
	const { DisabledGeoUserEnum } = require('stafftrack/map/disabled-geo-user-enum');
	const { ShiftAjax } = require('stafftrack/ajax');
	const { LocationEnum } = require('stafftrack/model/shift');
	const { Analytics } = require('stafftrack/analytics');
	const { SettingsManager } = require('stafftrack/data-managers/settings-manager');

	const imagesPath = '/bitrix/mobileapp/stafftrackmobile/extensions/stafftrack/map/images/';
	// eslint-disable-next-line no-undef
	const randomMapImage = `${imagesPath}blurred-map-${Random.getInt(1, 10)}.png`;
	void downloadImages([randomMapImage]);

	const locationList = jnExtensionData.get('stafftrack:map').locationList;

	const isIOS = Application.getPlatform() === 'ios';

	/**
	 * @class MapView
	 */
	class MapView extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.layoutWidget = this.props.layoutWidget || PageManager;

			this.state = {
				mapLoading: false,
				readOnly: this.props.readOnly || false,
				sendGeo: this.props.sendGeo,
				location: this.props.location || LocationEnum.OFFICE.getValue(),
				signedGeoImageUrl: null,
				signedAddressString: null,
				geoImageUrl: this.props.geoImageUrl || randomMapImage,
				addressString: this.props.address || null,
				customLocationRaw: this.props.customLocation || null,
				keyboardShow: false,
			};

			this.locationChipRef = null;
			this.locationMenu = null;
			this.textFieldRef = null;
			this.switcherRef = null;

			this.openSelector = this.openSelector.bind(this);
			this.onSwitcherClick = this.onSwitcherClick.bind(this);

			this.handleOnChange = this.handleOnChange.bind(this);
			this.handleOnBlur = this.handleOnBlur.bind(this);
			this.handleOnFocus = this.handleOnFocus.bind(this);

			if (Type.isStringFilled(this.props.geoImageUrl))
			{
				void downloadImages([this.props.geoImageUrl]);
			}
		}

		get isFirstHelpViewed()
		{
			return this.props.isFirstHelpViewed ?? true;
		}

		get isUserAdmin()
		{
			return this.props.userInfo?.isAdmin;
		}

		componentDidMount()
		{
			if (this.state.sendGeo === true)
			{
				this.handleUserGeo();
			}
		}

		componentDidUpdate(prevProps, prevState)
		{
			super.componentDidUpdate(prevProps, prevState);

			if (prevState.location !== this.state.location && this.isCustomLocationSelected())
			{
				this.textFieldRef?.focus();
			}
		}

		render()
		{
			return View(
				{},
				this.state.sendGeo && !this.state.mapLoading && this.renderMapContent(),
				!this.state.sendGeo && !this.state.mapLoading && this.renderLocationContent(),
				this.state.mapLoading && this.renderMapSkeleton(),
				!this.state.readOnly && this.renderSwitcher(),
			);
		}

		renderLocationContent()
		{
			return View(
				{},
				this.renderLocationImage(),
				this.isCustomLocationSelected() && this.renderLocationCustomInput(),
			);
		}

		renderLocationImage()
		{
			return View(
				{
					style: {
						position: 'relative',
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				this.renderRandomMapImage(),
				this.renderChip(),
			);
		}

		renderLocationCustomInput()
		{
			return View(
				{
					style: {
						borderColor: this.state.keyboardShow
							? Color.accentMainPrimary.toHex()
							: Color.bgSeparatorPrimary.toHex(),
						borderWidth: 1,
						borderRadius: Corner.M.toNumber(),
						marginTop: Indent.L.toNumber(),
						paddingHorizontal: Indent.L.toNumber(),
						paddingVertical: Indent.M.toNumber(),
					},
				},
				this.renderTextField(),
			);
		}

		renderTextField()
		{
			return TextField({
				testId: 'stafftrack-map-custom-location-input',
				value: this.state.customLocationRaw,
				placeholder: locationList[this.state.location].fullName,
				placeholderTextColor: Color.base4.toHex(),
				maxLength: 63,
				onChangeText: this.handleOnChange,
				onBlur: this.handleOnBlur,
				onFocus: this.handleOnFocus,
				ref: (ref) => {
					this.textFieldRef = ref;
				},
			});
		}

		blur()
		{
			this.textFieldRef?.blur();
		}

		handleOnChange(value)
		{
			this.state.customLocationRaw = value;
		}

		handleOnBlur()
		{
			this.setState({ keyboardShow: false });

			if (this.props.onBlurText)
			{
				this.props.onBlurText();
			}
		}

		handleOnFocus()
		{
			this.setState({ keyboardShow: true });

			if (this.props.onFocusText)
			{
				this.props.onFocusText();
			}
		}

		renderRandomMapImage()
		{
			return Image({
				testId: 'stafftrack-map-random-image',
				style: {
					width: '100%',
					height: 100,
					borderRadius: 4,
				},
				resizeMode: 'cover',
				uri: encodeURI(withCurrentDomain(randomMapImage)),
			});
		}

		renderChip()
		{
			return View(
				{
					style: {
						position: 'absolute',
						borderRadius: Corner.XL.toNumber(),
						marginHorizontal: Indent.L.toNumber(),
					},
					ref: (ref) => {
						this.locationChipRef = ref;
					},
				},
				ChipButton({
					icon: this.getLocationIconType(this.state.location),
					design: this.state.readOnly
						? ChipButtonDesign.GREY
						: ChipButtonDesign.BLACK,
					mode: ChipButtonMode.OUTLINE,
					dropdown: !this.state.readOnly,
					text: this.getLocationText(this.state.location),
					onClick: this.openSelector,
					style: {
						backgroundColor: Color.bgPrimary.toHex(),
					},
					testId: 'stafftrack-location-chip',
				}),
			);
		}

		renderMapContent()
		{
			return View(
				{},
				this.renderMapImage(),
				this.isGeoMode() && this.renderAddressString(),
			);
		}

		renderMapImage()
		{
			return View(
				{
					style: {
						position: 'relative',
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				Image({
					testId: 'stafftrack-map-image',
					style: {
						width: '100%',
						height: 100,
						borderRadius: 4,
					},
					resizeMode: 'cover',
					uri: encodeURI(withCurrentDomain(this.state.geoImageUrl)),
				}),
				this.isGeoMode() && this.renderPinIcon(),
			);
		}

		renderPinIcon()
		{
			return Image({
				style: {
					position: 'absolute',
					width: 17,
					height: 23,
				},
				svg: {
					content: pinIcon,
				},
			});
		}

		renderAddressString()
		{
			return View(
				{
					style: {
						marginTop: Indent.L.toNumber(),
					},
				},
				Text5({
					numberOfLines: 2,
					ellipsize: 'end',
					text: Loc.getMessage('M_STAFFTRACK_MAP_ADDRESS', {
						'#ADDRESS#': this.state.addressString,
					}),
					color: Color.base4,
					testId: 'stafftrack-map-address',
				}),
			);
		}

		renderSwitcher()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingTop: Indent.L.toNumber(),
					},
					onClick: this.onSwitcherClick,
					ref: (ref) => {
						this.switcherRef = ref;
					},
				},
				Switcher({
					onClick: this.onSwitcherClick,
					useState: false,
					mode: SwitcherMode.SOLID,
					checked: this.state.sendGeo,
					style: {
						marginRight: Indent.L.toNumber(),
					},
					testId: 'stafftrack-map-switcher',
				}),
				Text4({
					text: Loc.getMessage('M_STAFFTRACK_MAP_SEND_GEO'),
					color: Color.base3,
				}),
			);
		}

		renderMapSkeleton()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				this.renderMapImageSkeleton(),
			);
		}

		renderMapImageSkeleton()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				View(
					{
						style: {
							width: '100%',
						},
					},
					Line(null, 100, 0, 0, Corner.S.toNumber()),
				),
			);
		}

		openSelector()
		{
			if (this.state.readOnly)
			{
				return;
			}

			this.locationMenu ??= new LocationMenu({
				layoutWidget: this.layoutWidget,
				targetElementRef: this.locationChipRef,
				locationList,
				onItemSelected: (code) => {
					this.selectLocationItem(code);
				},
			});

			this.locationMenu.show(this.locationChipRef);
		}

		selectLocationItem(code)
		{
			this.setState({
				location: code,
			});
		}

		getLocationText(value)
		{
			switch (value)
			{
				case LocationEnum.REMOTELY.getValue():
				case LocationEnum.OFFICE.getValue():
				case LocationEnum.HOME.getValue():
				case LocationEnum.OUTSIDE.getValue():
				case LocationEnum.CUSTOM.getValue():
				case LocationEnum.DELETED.getValue():
					return locationList[value].fullName;
				default:
					return value;
			}
		}

		getLocationIconType(value)
		{
			switch (value)
			{
				case LocationEnum.REMOTELY.getValue():
				case LocationEnum.DELETED.getValue():
					return Icon.EARTH;
				case LocationEnum.OFFICE.getValue():
					return Icon.COMPANY;
				case LocationEnum.HOME.getValue():
					return Icon.HOME;
				case LocationEnum.OUTSIDE.getValue():
					return Icon.MAP;
				default:
					return null;
			}
		}

		async handleUserGeo()
		{
			if (!Type.isNil(this.state.addressString) || this.state.readOnly)
			{
				return;
			}

			let mapRequested = true;
			clearTimeout(this.loaderTimeout);
			this.loaderTimeout = setTimeout(() => {
				if (mapRequested)
				{
					this.setState({ mapLoading: true });
				}
			}, 100);

			const deviceGeoPosition = await this.requestGeoPosition();

			if (Type.isObject(deviceGeoPosition) && deviceGeoPosition.code)
			{
				const { code, message } = deviceGeoPosition;

				if (this.isFirstHelpViewed === false && isIOS)
				{
					this.handleErrorGeoDefinition();
				}
				else if (code === 1)
				{
					this.handleDisabledGeo();
				}
				else
				{
					this.handleErrorGeoDefinition();
				}

				console.error(message);

				mapRequested = false;

				return;
			}

			const { latitude, longitude } = deviceGeoPosition;

			const geoInfo = await this.getGeoInfo(latitude, longitude);

			mapRequested = false;

			if (!Type.isNil(geoInfo.error))
			{
				if (geoInfo.error === 'Source is not specified')
				{
					this.handleErrorGeoDefinition(
						Loc.getMessage('M_STAFFTRACK_MAP_GEO_SOURCE_NOT_SPECIFIED'),
					);
				}
				else
				{
					this.handleErrorGeoDefinition();
				}

				return;
			}

			if (Type.isNil(geoInfo.geoImageUrl) || Type.isNil(geoInfo.addressString))
			{
				this.handleErrorGeoDefinition();

				return;
			}

			const { signedGeoImageUrl, signedAddressString, geoImageUrl, addressString } = geoInfo;

			this.setState({
				sendGeo: true,
				mapLoading: false,
				signedGeoImageUrl,
				signedAddressString,
				geoImageUrl,
				addressString,
			});
		}

		async requestGeoPosition()
		{
			try
			{
				return await device.getLocation({ accuracy: 'approximate' });
			}
			catch (error)
			{
				return error;
			}
		}

		async getGeoInfo(latitude, longitude)
		{
			const { data, errors } = await ShiftAjax.getGeoInfo({ latitude, longitude });

			if (!Type.isNil(errors) && Type.isArrayFilled(errors))
			{
				const { message } = errors[0];

				return {
					error: message,
				};
			}

			return data;
		}

		handleDisabledGeo()
		{
			confirmDefaultAction({
				title: Loc.getMessage('M_STAFFTRACK_MAP_GEO_DISABLED_TITLE'),
				description: Loc.getMessage('M_STAFFTRACK_MAP_GEO_DISABLED_DESCRIPTION'),
				actionButtonText: Loc.getMessage('M_STAFFTRACK_MAP_GEO_DISABLED_ACTION'),
				onAction: () => {
					Application.openSettings();
				},
			});

			Haptics.notifyWarning();

			this.setState({
				sendGeo: false,
				mapLoading: false,
			});
		}

		handleErrorGeoDefinition(toastMessage = Loc.getMessage('M_STAFFTRACK_MAP_GEO_ERROR_DEFINITION'))
		{
			showToast({
				message: toastMessage,
				svg: {
					content: alert(),
				},
				backgroundColor: Color.accentMainAlert.toHex(),
			});

			Haptics.notifyFailure();

			this.setState({
				sendGeo: false,
				mapLoading: false,
			});
		}

		onSwitcherClick()
		{
			if (this.state.mapLoading)
			{
				return;
			}

			if (!SettingsManager.isGeoEnabled())
			{
				const geoAha = new DisabledGeoAha({
					layoutWidget: this.layoutWidget,
					targetRef: this.switcherRef,
					type: this.isUserAdmin ? DisabledGeoUserEnum.ADMIN : DisabledGeoUserEnum.REGULAR,
				});

				geoAha.show();

				return;
			}

			const sendGeo = !this.state.sendGeo;

			Analytics.sendSetupGeo(sendGeo);

			Haptics.impactLight();

			if (sendGeo === true && Type.isNil(this.state.addressString))
			{
				void this.handleUserGeo();
			}
			else
			{
				this.setState({ sendGeo });
			}
		}

		getLocation()
		{
			return this.isGeoMode()
				? null
				: this.getLocationData()
			;
		}

		getAddress()
		{
			return this.isGeoMode()
				? this.state.signedAddressString
				: null
			;
		}

		getGeoImage()
		{
			return this.isGeoMode()
				? this.state.signedGeoImageUrl
				: randomMapImage
			;
		}

		isGeoMode()
		{
			return this.state.sendGeo && this.state.addressString;
		}

		isCustomLocationSelected()
		{
			return !this.state.sendGeo && this.state.location === LocationEnum.CUSTOM.getValue();
		}

		getLocationData()
		{
			return this.isCustomLocationSelected() ? this.state.customLocationRaw : this.state.location;
		}
	}

	const pinIcon = `<svg width="17" height="23" viewBox="0 0 17 23" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.70847 0.666992C4.38817 0.666992 0.916504 4.1373 0.916504 8.45896C0.916504 13.0204 5.546 19.3875 7.66813 22.0665C8.20579 22.7453 9.20591 22.7405 9.73875 22.058C11.8563 19.3456 16.5004 12.8914 16.5004 8.45896C16.5004 4.1373 13.0288 0.666992 8.70847 0.666992ZM8.70847 11.9996C6.72563 11.9996 5.16642 10.4418 5.16642 8.4576C5.16642 6.47476 6.72427 4.91555 8.70847 4.91555C10.6913 4.91555 12.2505 6.4734 12.2505 8.4576C12.2505 10.4418 10.6913 11.9996 8.70847 11.9996Z" fill="${Color.accentMainAlert.toHex()}"/></svg>`;

	module.exports = { MapView };
});
