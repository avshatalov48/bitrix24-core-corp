/**
 * @module calendar/event-edit-form/pages/about-page
 */
jn.define('calendar/event-edit-form/pages/about-page', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { Box } = require('ui-system/layout/box');
	const { Area } = require('ui-system/layout/area');
	const { Text4 } = require('ui-system/typography/text');
	const { Button, Icon, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');
	const { UIScrollView } = require('layout/ui/scroll-view');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');

	const { EventNameInput } = require('calendar/event-edit-form/layout/event-name-input');
	const { EventSettings } = require('calendar/event-edit-form/layout/event-settings');
	const { AttendeesSelector } = require('calendar/event-edit-form/layout/attendees-selector');
	const { SelectDateTimeButton } = require('calendar/event-edit-form/button/select-date-time');
	const { SectionInfo } = require('calendar/event-edit-form/layout/section-info');
	const { Description } = require('calendar/event-edit-form/layout/description');
	const { SaveEventContainer } = require('calendar/event-edit-form/layout/save-event-container');

	/**
	 * @class AboutPage
	 */
	class AboutPage extends LayoutComponent
	{
		get layout()
		{
			return this.props.layout;
		}

		get editAttendeesMode()
		{
			return this.props.editAttendeesMode;
		}

		render()
		{
			return Box(
				{
					resizableByKeyboard: true,
					backgroundColor: Color.bgSecondary,
					style: { flex: 1 },
					safeArea: { bottom: true },
					footer: this.renderBoxFooter(),
				},
				this.renderContent(),
			);
		}

		renderContent()
		{
			return UIScrollView(
				{
					style: {
						flex: 1,
					},
				},
				this.renderBaseFields(),
				!this.editAttendeesMode && this.renderDescription(),
				this.renderAttendeesField(),
			);
		}

		renderBaseFields()
		{
			return Area(
				{
					excludePaddingSide: {
						bottom: true,
					},
				},
				this.renderTitle(),
				this.renderSectionInfo(),
			);
		}

		renderTitle()
		{
			return View(
				{
					style: {
						marginVertical: Indent.S.toNumber(),
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				this.renderTitleInput(),
				!this.editAttendeesMode && this.renderTitleButton(),
			);
		}

		renderTitleInput()
		{
			return View(
				{
					style: {
						flex: 1,
						marginRight: Indent.XL2.toNumber(),
					},
				},
				new EventNameInput({ layout: this.layout }),
			);
		}

		renderTitleButton()
		{
			return Button({
				testId: 'calendar-event-edit-form-title-menu-button',
				leftIcon: Icon.MORE,
				leftIconColor: Color.base4,
				size: ButtonSize.L,
				design: ButtonDesign.PLAIN_NO_ACCENT,
				onClick: this.openEventSettings,
			});
		}

		renderSectionInfo()
		{
			return View(
				{
					style: {
						marginBottom: Indent.XL.toNumber(),
					},
				},
				new SectionInfo({ layout: this.layout }),
			);
		}

		renderDescription()
		{
			return Area(
				{
					title: Loc.getMessage('M_CALENDAR_EVENT_EDIT_DESCRIPTION_TITLE'),
				},
				new Description({ layout: this.layout }),
			);
		}

		renderAttendeesField()
		{
			return Area(
				{
					isFirst: !this.editAttendeesMode,
				},
				Text4({
					testId: 'calendar-event-edit-form-attendees-title',
					color: Color.base4,
					text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EVENT_ATTENDEES'),
				}),
				new AttendeesSelector({ layout: this.layout }),
			);
		}

		renderSelectDateTimeButton()
		{
			return new SelectDateTimeButton({ layout: this.layout });
		}

		renderSaveEventContainer()
		{
			return new SaveEventContainer({ layout: this.layout });
		}

		renderBoxFooter()
		{
			return BoxFooter(
				{
					safeArea: true,
					keyboardButton: {
						text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_READY'),
						testId: 'calendar-event-edit-form-event-name-input-btn',
						onClick: () => Keyboard.dismiss(),
					},
				},
				!this.editAttendeesMode && this.renderSelectDateTimeButton(),
				this.editAttendeesMode && this.renderSaveEventContainer(),
			);
		}

		openEventSettings = () => EventSettings.open(this.layout);
	}

	module.exports = { AboutPage };
});
