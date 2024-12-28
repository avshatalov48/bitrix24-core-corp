/**
 * @module calendar/event-edit-form/layout/location-input
 */
jn.define('calendar/event-edit-form/layout/location-input', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent, Corner } = require('tokens');
	const { StringInput, InputDesign, InputMode, InputSize } = require('ui-system/form/inputs/string');
	const { Area } = require('ui-system/layout/area');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { Button, ButtonSize, ButtonDesign, Icon } = require('ui-system/form/buttons/button');
	const { BottomSheet } = require('bottom-sheet');
	const { getFeatureRestriction } = require('tariff-plan-restriction');
	const { Line } = require('utils/skeleton');

	const { LocationManager } = require('calendar/data-managers/location-manager');
	const { Selector } = require('calendar/event-edit-form/selector');
	const { State, observeState } = require('calendar/event-edit-form/state');
	const { FeatureId } = require('calendar/enums');

	const meetingIconEnabled = Application.getApiVersion() >= 56;

	/**
	 * @class LocationInput
	 */
	class LocationInput extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.checkLocationRestriction();

			void State.loadLocationAccessibility();

			this.refs = {
				input: null,
			};
		}

		componentDidMount()
		{
			this.refs.input?.focus();
		}

		checkLocationRestriction()
		{
			const { isRestricted } = getFeatureRestriction(FeatureId.CALENDAR_LOCATION);

			this.locationRestriction = isRestricted();
		}

		render()
		{
			return Box(
				{
					resizableByKeyboard: true,
					backgroundColor: Color.bgSecondary,
					safeArea: { bottom: true },
					footer: this.renderFooter(),
				},
				this.renderContent(),
			);
		}

		renderContent()
		{
			return Area(
				{
					excludePaddingSide: { bottom: true },
					style: {
						flex: 1,
						flexDirection: 'row',
					},
				},
				this.renderInput(),
				(this.props.isLoading && this.hasToShowSelectorButton()) && this.renderSkeletonButton(),
				(!this.props.isLoading && this.hasToShowSelectorButton()) && this.renderSelectLocationButton(),
			);
		}

		renderInput()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				StringInput({
					testId: 'calendar-event-edit-form-location-input',
					value: this.props.location,
					placeholder: this.getLocationPlaceholder(),
					onChange: this.onChangeHandler,
					onSubmit: this.onSubmitHandler,
					onErase: this.onEraseHandler,
					design: InputDesign.GREY,
					mode: InputMode.STROKE,
					size: InputSize.L,
					erase: true,
					forwardRef: this.#bindInputRef,
				}),
			);
		}

		#bindInputRef = (ref) => {
			this.refs.input = ref;
		};

		renderFooter()
		{
			return BoxFooter(
				{
					safeArea: Application.getPlatform() !== 'android',
					keyboardButton: {
						text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_READY'),
						onClick: this.onSubmitHandler,
						testId: 'calendar-event-edit-form-location-input-btn',
					},
				},
				Button({
					design: ButtonDesign.FILLED,
					size: ButtonSize.L,
					text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_READY'),
					stretched: true,
					onClick: this.onSubmitHandler,
					testId: 'calendar-event-edit-form-location-input-btn',
				}),
			);
		}

		renderSkeletonButton()
		{
			return View(
				{
					style: {
						marginLeft: Indent.M.toNumber(),
					},
				},
				Line(42, 42, 0, 0, Corner.M.toNumber()),
			);
		}

		renderSelectLocationButton()
		{
			return Button({
				testId: 'calendar-event-edit-form-location-select',
				leftIcon: meetingIconEnabled ? Icon.MEETING_POINT : Icon.LOCATION,
				size: ButtonSize.L,
				backgroundColor: this.locationRestriction ? Color.base7 : Color.accentSoftBlue2,
				design: this.locationRestriction ? ButtonDesign.PLAIN_NO_ACCENT : ButtonDesign.TINTED,
				disabled: false,
				onClick: this.openLocationSelector,
				style: {
					marginLeft: Indent.M.toNumber(),
				},
			});
		}

		getLocationPlaceholder()
		{
			return (this.locationRestriction || !this.hasToShowSelectorButton())
				? Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_LOCATION_INPUT_PLACEHOLDER_RESTRICTED')
				: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_LOCATION_INPUT_PLACEHOLDER')
			;
		}

		getSelectedLocationId()
		{
			return LocationManager.getLocationIdByName(this.props.location);
		}

		getSelectedCategoryId()
		{
			const locationId = LocationManager.getLocationIdByName(this.props.location);

			if (locationId)
			{
				return LocationManager.getLocation(locationId).getCategoryId();
			}

			return null;
		}

		openLocationSelector = () => {
			const { showRestriction } = getFeatureRestriction(FeatureId.CALENDAR_LOCATION);

			if (this.locationRestriction)
			{
				showRestriction({
					parentWidget: this.props.layoutWidget,
				});
			}
			else
			{
				Selector.openInContext({
					parentLayout: this.props.layoutWidget,
					items: LocationManager.getSortedLocations(),
					categories: LocationManager.getSortedCategories(Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_NO_CATEGORY')),
					selectedId: this.getSelectedLocationId(),
					selectedCategoryId: this.getSelectedCategoryId(),
					onItemClick: this.onLocationSelected,
					selectorTitle: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_LOCATION_SELECTOR_TITLE'),
					selectorIcon: meetingIconEnabled ? Icon.MEETING_POINT : Icon.LOCATION,
					reservedInfo: this.props.locationReservedInfo,
				});
			}
		};

		onChangeHandler = (locationValue) => {
			State.setLocation(locationValue);
		};

		onSubmitHandler = () => {
			this.props.layoutWidget.close();
		};

		onEraseHandler = () => {
			this.refs.input?.clear();
		};

		onLocationSelected = (locationId) => {
			const locationName = LocationManager.getLocation(locationId).getName();
			State.setLocation(locationName);
		};

		hasToShowSelectorButton()
		{
			return !env.isCollaber && !env.extranet;
		}

		static open(parentLayout = PageManager)
		{
			const component = (layoutWidget) => new this({
				layoutWidget,
			});

			void new BottomSheet({ component })
				.setParentWidget(parentLayout)
				.enableOnlyMediumPosition()
				.setMediumPositionPercent(65)
				.disableSwipe()
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.setTitleParams({
					text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_LOCATION'),
					type: 'wizard',
				})
				.open()
			;
		}
	}

	const mapStateToProps = (state) => ({
		isLoading: !state.hasLocationAccessibility,
		keyboardShown: state.keyboardShown,
		location: state.location,
		locationReservedInfo: state.locationReservedInfo,
	});

	module.exports = { LocationInput: observeState(LocationInput, mapStateToProps) };
});
