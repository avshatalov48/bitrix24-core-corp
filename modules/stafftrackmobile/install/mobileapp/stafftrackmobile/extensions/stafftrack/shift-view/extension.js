/**
 * @module stafftrack/shift-view
 */
jn.define('stafftrack/shift-view', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { PureComponent } = require('layout/pure-component');
	const { BottomSheet } = require('bottom-sheet');

	const { Color, Component, Indent, Corner } = require('tokens');
	const { Box } = require('ui-system/layout/box');
	const { Area } = require('ui-system/layout/area');
	const { H3, H4 } = require('ui-system/typography/heading');
	const { Text2, Text3 } = require('ui-system/typography/text');
	const { Card } = require('ui-system/layout/card');
	const { Button, ButtonSize, ButtonDesign, Icon } = require('ui-system/form/buttons/button');
	const { IconView } = require('ui-system/blocks/icon');

	const { DateHelper } = require('stafftrack/date-helper');
	const { MapView } = require('stafftrack/map');
	const { ScrollViewWithMaxHeight } = require('stafftrack/ui');

	const HEADER_HEIGHT = 60;
	const BASE_CARD_HEIGHT = 234 + Indent.L.toNumber();
	const CANCEL_CARD_HEIGHT = 140 + Indent.L.toNumber();
	const ARROW_SEPARATOR_HEIGHT = 24 + 2 * Indent.M.toNumber();
	const BOTTOM_PANEL = 20;

	class ShiftView extends PureComponent
	{
		/**
		 * @return {User}
		 */
		get user()
		{
			return this.props.user;
		}

		/**
		 * @return {ShiftModel}
		 */
		get shift()
		{
			return this.props.shift;
		}

		show(parentLayout = PageManager)
		{
			void new BottomSheet({ component: this })
				.setParentWidget(parentLayout)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.disableContentSwipe()
				.setMediumPositionHeight(this.getSheetHeight())
				.open()
			;
		}

		render()
		{
			return Box(
				{
					backgroundColor: Color.bgSecondary,
				},
				this.renderHeader(),
				this.renderContent(),
			);
		}

		renderHeader()
		{
			return Area(
				{
					isFirst: true,
					style: {
						flexDirection: 'row',
					},
				},
				View(
					{
						style: {
							flex: 1,
						},
					},
					H3({
						text: this.user.name,
						color: Color.base1,
						numberOfLines: 1,
						ellipsize: 'end',
					}),
				),
				View(
					{
						style: {
							paddingLeft: Indent.S.toNumber(),
						},
					},
					H4({
						text: DateHelper.formatDate(this.shift.shiftDate),
						color: Color.base4,
					}),
				),
			);
		}

		renderContent()
		{
			return Area(
				{
					isFirst: true,
				},
				!this.shift.isNotWorkingStatus() && this.renderBaseCard(),
				this.shift.isCancelStatus() && this.renderArrowSeparator(),
				this.shift.isCancelOrNotWorkingStatus() && this.renderCancelCard(),
			);
		}

		renderBaseCard()
		{
			return View(
				{
					style: {
						paddingBottom: this.shift.isCancelStatus() ? 0 : Indent.L.toNumber(),
					},
				},
				Card(
					{
						border: true,
						testId: 'stafftrack-shift-view-base-card',
					},
					this.renderBaseStatusBar(),
					this.renderLocation(),
				),
			);
		}

		renderBaseStatusBar()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Button({
					size: ButtonSize.M,
					design: ButtonDesign.FILLED,
					leftIcon: Icon.CHECK,
					leftIconColor: Color.baseWhiteFixed,
					backgroundColor: this.shift.isWorkingStatus()
						? Color.accentMainSuccess
						: Color.base6,
					style: {
						paddingRight: Indent.XL.toNumber(),
					},
					testId: 'stafftrack-shift-view-base-card-button',
				}),
				Text2({
					text: Loc.getMessage('M_STAFFTRACK_SHIFT_VIEW_START_DAY'),
					color: this.shift.isWorkingStatus()
						? Color.base1
						: Color.base3
					,
				}),
				this.renderSeparator(),
				this.renderBaseCardTime(),
			);
		}

		renderBaseCardTime()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				Type.isStringFilled(this.shift.getAddress()) && IconView({
					size: 24,
					icon: Icon.LOCATION,
					color: Color.accentMainPrimary,
				}),
				Text3({
					text: DateHelper.formatTime(this.shift.getDateCreate()),
					color: Color.accentMainPrimary,
				}),
			);
		}

		renderLocation()
		{
			return View(
				{
					style: {
						paddingTop: Indent.XL.toNumber(),
					},
				},
				new MapView({
					sendGeo: Type.isStringFilled(this.shift.getAddress()),
					readOnly: true,
					location: this.shift.getLocation(),
					geoImageUrl: this.shift.getGeoImageUrl(),
					address: this.shift.getAddress(),
				}),
			);
		}

		renderCancelCard()
		{
			return View(
				{
					style: {
						paddingBottom: Indent.L.toNumber(),
					},
				},
				Card(
					{
						border: true,
						testId: 'stafftrack-shift-view-cancel-card',
					},
					this.renderCancelStatusBar(),
					this.renderCancelReasonDescriptionContainer(),
				),
			);
		}

		renderCancelStatusBar()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingBottom: Indent.XL.toNumber(),
					},
				},
				Button({
					size: ButtonSize.M,
					design: ButtonDesign.FILLED,
					leftIcon: Icon.CROSS,
					leftIconColor: Color.baseWhiteFixed,
					backgroundColor: Color.accentMainWarning,
					testId: 'stafftrack-shift-view-cancel-card-button',
					style: {
						paddingRight: Indent.XL.toNumber(),
						borderRadius: Component.elementMCorner.toNumber(),
					},
				}),
				Text2({
					text: this.shift.isNotWorkingStatus()
						? Loc.getMessage('M_STAFFTRACK_SHIFT_VIEW_NOT_WORKING_DAY')
						: Loc.getMessage('M_STAFFTRACK_SHIFT_VIEW_CANCEL_DAY'),
					color: Color.base1,
				}),
				this.renderSeparator(),
				Text3({
					text: DateHelper.formatTime(this.shift.getDateCancel()),
					color: Color.accentMainWarning,
				}),
			);
		}

		renderCancelReasonDescriptionContainer()
		{
			return View(
				{
					style: {
						borderColor: Color.bgSeparatorPrimary.toHex(),
						borderRadius: Corner.M.toNumber(),
						borderWidth: 1,
						maxHeight: 76,
						paddingHorizontal: Indent.L.toNumber(),
						paddingVertical: Indent.M.toNumber(),
					},
				},
				this.renderCancelReasonDescription(),
			);
		}

		renderCancelReasonDescription()
		{
			return new ScrollViewWithMaxHeight({
				testId: 'stafftrack-shift-view-cancel-description',
				style: {
					minHeight: 20,
					maxHeight: 60,
				},
				renderContent: () => Text3({
					text: this.shift.getCancelReason(),
					color: Color.base2,
				}),
			});
		}

		renderSeparator()
		{
			return View(
				{
					style: {
						height: 1,
						flex: 1,
						backgroundColor: Color.bgSeparatorPrimary.toHex(),
						marginHorizontal: Indent.XL.toNumber(),
					},
				},
			);
		}

		renderArrowSeparator()
		{
			return View(
				{
					style: {
						paddingVertical: Indent.M.toNumber(),
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				IconView({
					size: 24,
					icon: Icon.ARROW_DOWN,
					color: Color.base4,
				}),
			);
		}

		getSheetHeight()
		{
			let baseHeight = HEADER_HEIGHT + BOTTOM_PANEL;

			if (this.shift.isWorkingStatus())
			{
				baseHeight += BASE_CARD_HEIGHT;
			}

			if (this.shift.isNotWorkingStatus())
			{
				baseHeight += CANCEL_CARD_HEIGHT;
			}

			if (this.shift.isCancelStatus())
			{
				baseHeight += BASE_CARD_HEIGHT + ARROW_SEPARATOR_HEIGHT + CANCEL_CARD_HEIGHT;
			}

			return baseHeight;
		}
	}

	module.exports = { ShiftView };
});
