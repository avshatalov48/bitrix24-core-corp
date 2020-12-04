// @flow
import {Type, Tag, Loc, Dom, Event, Runtime, Text} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';
import {Util} from "calendar.util";
import {Popup, PopupManager} from 'main.popup';
import {
	DateTimeControl,
	Location,
	Reminder,
	SectionSelector,
	UserPlannerSelector,
	ColorSelector,
	BusyUsersDialog
} from "calendar.controls";
import {Entry, EntryManager} from "calendar.entry";
import {CalendarSectionManager} from "calendar.calendarsection";
import {MobileSyncBanner} from "calendar.sync.interface";

export class CompactEventForm extends EventEmitter
{
	static VIEW_MODE = 'view';
	static EDIT_MODE = 'edit';
	STATE = {READY: 1, REQUEST: 2, ERROR: 3};
	zIndex = 1200;
	Z_INDEX_OFFSET = -1000;
	userSettings = '';
	DOM = {};
	mode;
	displayed = false;
	sections = [];
	sectionIndex = {};
	trackingUsersList = [];
	checkDataBeforeCloseMode = true;

	constructor(options = {})
	{
		super();
		this.setEventNamespace('BX.Calendar.CompactEventForm');
		this.userId = options.userId || Util.getCurrentUserId();
		this.type = options.type || 'user';
		this.ownerId = options.ownerId || this.userId;
		this.BX = Util.getBX();

		this.checkForChanges = Runtime.debounce(this.checkForChangesImmediately, 300, this);
		this.checkOutsideClickClose = this.checkOutsideClickClose.bind(this);
		this.outsideMouseDownClose = this.outsideMouseDownClose.bind(this);
		this.keyHandler = this.handleKeyPress.bind(this);
	}

	show(mode = CompactEventForm.EDIT_MODE, params ={})
	{
		this.setParams(params);
		this.setMode(mode);

		this.state = this.STATE.READY;

		this.popupId = 'compact-event-form-' + Math.round(Math.random() * 100000);

		if (this.popup)
		{
			this.popup.destroy();
		}

		this.popup = this.getPopup(params);

		// Small hack to use transparent titlebar to drag&drop popup
		Dom.addClass(this.popup.titleBar, 'calendar-add-popup-titlebar');
		Dom.removeClass(this.popup.popupContainer, 'popup-window-with-titlebar');
		Dom.removeClass(this.popup.closeIcon, 'popup-window-titlebar-close-icon');
		//Event.bind(document, "click", Util.applyHacksForPopupzIndex);
		Event.bind(document, "mousedown", this.outsideMouseDownClose);
		Event.bind(document, "mouseup", this.checkOutsideClickClose);
		Event.bind(document, "keydown", this.keyHandler);

		Event.bind(this.popup.popupContainer, 'transitionend', ()=>{Dom.removeClass(this.popup.popupContainer, 'calendar-simple-view-popup-show');});

		this.setFormValues();

		this.popup.show();

		this.checkDataBeforeCloseMode = true;
		if (this.canDo('edit') && this.DOM.titleInput && mode === CompactEventForm.EDIT_MODE)
		{
			this.DOM.titleInput.focus();
			this.DOM.titleInput.select();
		}

		//this.emit('onShow');
		this.displayed = true;
	}

	getPopup(params)
	{
		return new Popup(this.popupId,
			params.bindNode,
			{
				zIndex: this.zIndex + this.Z_INDEX_OFFSET,
				closeByEsc: true,
				offsetTop: 0,
				offsetLeft: 0,
				closeIcon: true,
				titleBar: true,
				draggable: true,
				resizable: false,
				lightShadow: true,
				className: 'calendar-simple-view-popup calendar-simple-view-popup-show',
				cacheable: false,
				content: this.getPopupContent(),
				buttons: this.getButtons(),
				events: {
					onPopupClose: this.close.bind(this)
				},
			});
	}

	isShown()
	{
		return this.displayed;
	}

	close()
	{
		if (this.getMode() === CompactEventForm.EDIT_MODE
			&& this.formDataChanged()
			&& this.checkDataBeforeCloseMode
			&& !confirm(BX.message('EC_SAVE_ENTRY_CONFIRM')))
		{
			return;
		}

		//Dom.addClass(this.popup.popupContainer, 'calendar-simple-view-popup-close');
		this.displayed = false;
		this.emit('onClose');
		//Event.unbind(document, "click", Util.applyHacksForPopupzIndex);
		Event.unbind(document, "mousedown", this.outsideMouseDownClose);
		Event.unbind(document, "mouseup", this.checkOutsideClickClose);
		Event.unbind(document, "keydown", this.keyHandler);

		//Dom.removeClass(popup.popupContainer, 'calendar-simple-view-popup-close');
		if (this.popup)
		{
			this.popup.destroy();
		}

		Util.closeAllPopups();

		// setTimeout(()=>{
		// 	const popup = this.popup;
		// 	(function(){
		// 		Dom.removeClass(popup.popupContainer, 'calendar-simple-view-popup-close');
		// 		if (popup)
		// 		{
		// 			popup.destroy();
		// 		}
		// 	})();
		// }, 200);
	}

	getPopupContent()
	{
		this.DOM.wrap = Tag.render`<div class="calendar-add-popup-wrap">
			${this.DOM.titleOuterWrap = Tag.render`
			<div class="calendar-field-container calendar-field-container-string-select">
				<div class="calendar-field-block">
					${this.getTitleControl()}
					${this.getColorControl()}
				</div>
			</div>`}
			<div class="calendar-field-container calendar-field-container-choice">
				${this.getSectionControl()}
			</div>
			
			${this.getDateTimeControl()}
			
			${this.getUserPlannerSelector()}
			
			<div class="calendar-field-container calendar-field-container-info">
				${this.getTypeInfoControl()}
				
					${this.getLocationControl()}
				
				${this.DOM.remindersOuterWrap = Tag.render`
				<div class="calendar-field-block">
					<div class="calendar-field-title">${Loc.getMessage('EC_REMIND_LABEL')}:</div>
					${this.createRemindersControl()}
				</div>`}
				${this.getRRuleInfoControl()}
				${this.getTimezoneInfoControl()}
			</div>
		</div>`;

		//this.DOM.loader = this.DOM.wrap.appendChild(Util.getLoader(50));

		return this.DOM.wrap;
	}

	getButtons()
	{
		let buttons = [];
		const mode = this.getMode();

		if (mode === CompactEventForm.EDIT_MODE)
		{
			buttons.push(
				new BX.UI.Button({
					text : (this.isNewEntry() ? Loc.getMessage('CALENDAR_EVENT_DO_ADD') : Loc.getMessage('CALENDAR_EVENT_DO_SAVE')),
					className: "ui-btn ui-btn-primary",
					events : {click : () => {
						this.checkDataBeforeCloseMode = false;
						this.save();
					}}
				})
			);

			buttons.push(
				new BX.UI.Button({
					text : Loc.getMessage('CALENDAR_EVENT_DO_CANCEL'),
					className: "ui-btn ui-btn-link",
					events : {click : () => {
						if (this.isNewEntry())
						{
							this.checkDataBeforeCloseMode = false;
							this.close();
						}
						else
						{
							this.setFormValues();

							if (this.userPlannerSelector)
							{
								this.userPlannerSelector.destroySelector();
							}

							this.setMode(CompactEventForm.VIEW_MODE);
							this.popup.setButtons(this.getButtons());
						}
					}}
				})
			);

			buttons.push(
				new BX.UI.Button({
					text : Loc.getMessage('CALENDAR_EVENT_FULL_FORM'),
					className: "ui-btn calendar-full-form-btn",
					events : {click : this.editEntryInSlider.bind(this)}
				})
			);
			//sideButton = true;

			// if (!this.isNewEntry() && this.canDo('delete'))
			// {
			// 	buttons.push(
			// 		new BX.UI.Button({
			// 			text : Loc.getMessage('CALENDAR_EVENT_DO_DELETE'),
			// 			className: "ui-btn ui-btn-link",
			// 			events : {click : ()=>{
			// 				EntryManager.deleteEntry(this.entry);
			// 			}}
			// 		})
			// 	);
			// }
		}
		else if(mode === CompactEventForm.VIEW_MODE)
		{
			if (this.entry.isMeeting()
				&& this.entry.getCurrentStatus() === 'Q')
			{
				buttons.push(
					new BX.UI.Button({
						className: "ui-btn ui-btn-primary",
						text : Loc.getMessage('EC_DESIDE_BUT_Y'),
						events : {click : ()=>{
								EntryManager.setMeetingStatus(this.entry, 'Y')
									.then(this.refreshMeetingStatus.bind(this));
							}}
					})
				);

				buttons.push(
					new BX.UI.Button({
						className: "ui-btn ui-btn-link",
						text : Loc.getMessage('EC_DESIDE_BUT_N'),
						events : {click : ()=>{
								EntryManager.setMeetingStatus(this.entry, 'N')
									.then(this.refreshMeetingStatus.bind(this));
							}}
					})
				);
			}

			buttons.push(
				new BX.UI.Button({
					className: `ui-btn ${this.entry.isMeeting() && this.entry.getCurrentStatus() === 'Q' ? 'ui-btn-link' : 'ui-btn-primary'}`,
					text : Loc.getMessage('CALENDAR_EVENT_DO_OPEN'),
					events : {click : ()=>{
							this.checkDataBeforeCloseMode = false;
							BX.Calendar.EntryManager.openViewSlider(this.entry.id,
								{
									entry: this.entry,
									type: this.type,
									ownerId: this.ownerId,
									userId: this.userId,
									from: this.entry.from,
									timezoneOffset: this.entry && this.entry.data ? this.entry.data.TZ_OFFSET_FROM : null
								}
							);
							this.close();
						}}
				})
			);

			if (this.entry.isMeeting()
				&& this.entry.getCurrentStatus() === 'N')
			{
				buttons.push(
					new BX.UI.Button({
						className: "ui-btn ui-btn-link",
						text : Loc.getMessage('EC_DESIDE_BUT_Y'),
						events : {click : ()=>{
								EntryManager.setMeetingStatus(this.entry, 'Y')
									.then(this.refreshMeetingStatus.bind(this));
							}}
					})
				);
			}

			if (this.entry.isMeeting()
				&& this.entry.getCurrentStatus() === 'Y')
			{
				buttons.push(
					new BX.UI.Button({
						className: "ui-btn ui-btn-link",
						text : Loc.getMessage('EC_DESIDE_BUT_N'),
						events : {click : () => {
								EntryManager.setMeetingStatus(this.entry, 'N')
									.then(this.refreshMeetingStatus.bind(this));
							}}
					})
				);
			}


			// if (!this.isNewEntry() && this.canDo('edit'))
			// {
			// 	buttons.push(
			// 		new BX.UI.Button({
			// 			text : Loc.getMessage('CALENDAR_EVENT_DO_EDIT')
			// 			//events : {click : this.save.bind(this)}
			// 		})
			// 	);
			// }

			if (!this.isNewEntry() && this.canDo('edit'))
			{
				buttons.push(
					new BX.UI.Button({
						text : Loc.getMessage('CALENDAR_EVENT_DO_EDIT'),
						className: "ui-btn ui-btn-link",
						events : {click : this.editEntryInSlider.bind(this)}
					})
				);
				//sideButton = true;
			}

			if (!this.isNewEntry() && this.canDo('delete'))
			{
				if (!this.entry.isMeeting()
					|| !this.entry.getCurrentStatus()
					|| this.entry.getCurrentStatus() === 'H')
				{
					buttons.push(
						new BX.UI.Button({
							text : Loc.getMessage('CALENDAR_EVENT_DO_DELETE'),
							className: "ui-btn ui-btn-link",
							events : {click : ()=>{
									EntryManager.deleteEntry(this.entry);
									EventEmitter.subscribeOnce('BX.Calendar.Entry:delete', ()=>{
										this.checkDataBeforeCloseMode = false;
										this.close();
									});

									if (!this.entry.wasEverRecursive())
									{
										this.close();
									}
								}}
						})
					);
				}
			}
		}

		// buttons.push(
		// 	new BX.UI.Button({
		// 		text : Loc.getMessage('CALENDAR_EVENT_DO_CANCEL'),
		// 		className: "ui-btn ui-btn-link",
		// 		//events : {click : this.save.bind(this)}
		// 	})
		// );

		// buttons.push(
		// 	new BX.UI.Button({
		// 		text : Loc.getMessage('CALENDAR_EVENT_FULL_FORM'),
		// 		className: "ui-btn ui-btn-link"
		// 		//events : {click : this.save.bind(this)}
		// 	})
		// );

		if (buttons.length > 2)
		{
			buttons[1].button.className = "ui-btn ui-btn-light-border";
		}

		setTimeout(()=>{
			if (this.popup && this.popup.popupContainer)
			{
				// if (sideButton)
				// {
				// 	Dom.addClass(this.popup.popupContainer, 'calendar-simple-view-popup-full');
				// }
				// else
				// {
				// 	Dom.removeClass(this.popup.popupContainer, 'calendar-simple-view-popup-full');
				// }
			}
		},0);

		return buttons;
	}

	refreshMeetingStatus()
	{
		this.emit('doRefresh');
		this.popup.setButtons(this.getButtons());
		if (this.userPlannerSelector)
		{
			this.userPlannerSelector.displayAttendees(this.entry.getAttendees());
		}
	}

	hideLoader()
	{
		if (Type.isDomNode(this.DOM.loader))
		{
			Dom.remove(this.DOM.loader);
			this.DOM.loader = null;
		}
	}

	showInEditMode(params = {})
	{
		return this.show(CompactEventForm.EDIT_MODE, params);
	}

	showInViewMode(params = {})
	{
		return this.show(CompactEventForm.VIEW_MODE, params);
	}

	setMode(mode)
	{
		if (mode === 'edit' || mode === 'view')
		{
			this.mode = mode;
		}
	}

	getMode()
	{
		return this.mode;
	}

	checkForChangesImmediately()
	{
		if (!this.isNewEntry()
			&& this.getMode() === CompactEventForm.VIEW_MODE
			&& this.formDataChanged())
		{
			this.setMode(CompactEventForm.EDIT_MODE);
			this.popup.setButtons(this.getButtons());
			//this.updateSetMeetingButtons();
		}
		else if (!this.isNewEntry()
			&& this.getMode() === CompactEventForm.EDIT_MODE
			&& !this.formDataChanged())
		{
			this.setMode(CompactEventForm.VIEW_MODE);
			this.popup.setButtons(this.getButtons());
			//this.updateSetMeetingButtons();
		}
		this.emitOnChange();
	}

	updateSetMeetingButtons()
	{
		const entry = this.getCurrentEntry();
		if (entry.isMeeting())
		{
			//const buttonsContainer = this.popup.buttonsContainer;
			// this.setStatusControl = new MeetingStatusControl({
			// 	wrap: buttonsContainer,
			// 	currentStatus: entry.getCurrentStatus()
			// });
			//
			// this.setStatusControl.subscribe('onSetStatus', (event) => {
			// 		if (event instanceof BaseEvent)
			// 		{
			// 			let data = event.getData();
			// 			EntryManager.setMeetingStatus(entry, data.status);
			// 		}
			// 	}
			// );
		}
	}

	getformDataChanges(excludes = [])
	{
		const entry = this.entry;
		let fields = [];

		// Name
		if (!excludes.includes('name')
			&& entry.name !== this.DOM.titleInput.value)
		{
			fields.push('name');
		}

		// Location
		if (!excludes.includes('location')
			&&
			this.locationSelector.getTextLocation(Location.parseStringValue(entry.getLocation()))
			!==
			this.locationSelector.getTextLocation(Location.parseStringValue(this.locationSelector.getTextValue()))
		)
		{
			fields.push('location');
		}

		// Date + time
		const dateTime = this.dateTimeControl.getValue();
		if (!excludes.includes('date&time')
			&&
			(entry.isFullDay() !== dateTime.fullDay
			|| dateTime.from.toString() !== entry.from.toString()
			|| dateTime.to.toString() !== entry.to.toString()))
		{
			fields.push('date&time');
		}

		// Notify
		if (!excludes.includes('notify')
			&&
			(!entry.isMeeting() || entry.getMeetingNotify())
			!== this.userPlannerSelector.getInformValue())
		{
			fields.push('notify');
		}

		// Section
		if (!excludes.includes('section')
			&&
			parseInt(entry.sectionId) !== parseInt(this.sectionValue))
		{
			fields.push('section');
		}

		// Access codes
		if (!excludes.includes('codes')
		&&
			this.userPlannerSelector.getEntityList().map((item)=>{return item.entityId + ':' + item.id}).join('|')
			!==
			entry.getAttendeesEntityList().map((item)=>{return item.entityId + ':' + item.id}).join('|')
		)
		{
			fields.push('codes');
		}

		return fields;
	}

	formDataChanged()
	{
		return this.getformDataChanges().length > 0;
	}

	setParams(params = {})
	{
		this.userId = params.userId || Util.getCurrentUserId();
		this.type = params.type || 'user';
		this.ownerId = params.ownerId ? params.ownerId : 0;
		if (this.type === 'user' && !this.ownerId)
		{
			this.ownerId = this.userId;
		}
		this.entry = EntryManager.getEntryInstance(params.entry, params.userIndex, {type: this.type, ownerId: this.ownerId});
		this.sectionValue = null;

		if (!this.entry.id
			&& Type.isPlainObject(params.entryTime)
			&& Type.isDate(params.entryTime.from)
			&& Type.isDate(params.entryTime.to)
		)
		{
			this.entry.setDateTimeValue(params.entryTime);
		}

		if (Type.isPlainObject(params.userSettings))
		{
			this.userSettings = params.userSettings;
		}

		this.locationFeatureEnabled = !!params.locationFeatureEnabled;
		this.locationList = params.locationList || [];
		this.iblockMeetingRoomList = params.iblockMeetingRoomList || [];

		this.setSections(params.sections, params.trackingUserList)
	}

	setSections(sections, trackingUsersList = [])
	{
		this.sections = sections;
		this.sectionIndex = {};
		this.trackingUsersList = trackingUsersList || [];

		if (Type.isArray(sections))
		{
			sections.forEach((value, ind) => {this.sectionIndex[parseInt(value.ID || value.id)] = ind;}, this);
		}
	}

	prepareData(params = {})
	{
		return new Promise((resolve) => {
				setTimeout(() => {
					resolve();
				}, 0);
		});
	}

	getTitleControl()
	{
		this.DOM.titleInput = Tag.render`
			<input class="calendar-field calendar-field-string" 
				value="" 
				placeholder="${Loc.getMessage('EC_ENTRY_NAME')}" 
				type="text" 
			/>
		`;

		Event.bind(this.DOM.titleInput, 'keyup', this.checkForChanges);
		Event.bind(this.DOM.titleInput, 'change', this.checkForChanges);

		return this.DOM.titleInput;
	}

	getColorControl()
	{
		this.DOM.colorSelect = Tag.render`<div class="calendar-field calendar-field-select calendar-field-tiny"></div>`;
		this.colorSelector = new ColorSelector({
			wrap: this.DOM.colorSelect,
			mode: 'selector'
		});

		this.colorSelector.subscribe('onChange', (event) => {
			if (event instanceof BaseEvent)
			{
				const color = event.getData().value;
				if (!this.isNewEntry()
					&& (this.canDo('edit')
						|| this.entry.getCurrentStatus() !== false))
				{
					this.BX.ajax.runAction('calendar.api.calendarajax.updateColor', {
						data: {
							entryId: this.entry.id,
							userId: this.userId,
							color: color
						}
					});
					this.entry.data.COLOR = color;

					this.emit('doRefresh');
					this.emitOnChange();
				}
			}
		});


		return this.DOM.colorSelect;
	}

	getSectionControl()
	{
		this.DOM.sectionSelectWrap = Tag.render`<div class="calendar-field-choice-calendar"></div>`;
		this.sectionSelector = new SectionSelector({
			outerWrap: this.DOM.sectionSelectWrap,
			defaultCalendarType: this.type,
			defaultOwnerId: this.ownerId,
			sectionList: this.sections,
			sectionGroupList: CalendarSectionManager.getSectionGroupList({
				type: this.type,
				ownerId: this.ownerId,
				userId: this.userId,
				trackingUsersList: this.trackingUsersList,
			}),
			mode: 'textselect',
			zIndex: this.zIndex,
			getCurrentSection: ()=>{
				let section = this.getCurrentSection();
				if (section)
				{
					return {
						id: section.id,
						name: section.name,
						color: section.color
					}
				}
				return false;
			},
			selectCallback: (sectionValue) => {
				if (sectionValue)
				{
					if (this.colorSelector )
					{
						this.colorSelector.setValue(sectionValue.color);
					}
					this.sectionValue = sectionValue.id;
					//this.entry.setSectionId(sectionValue.id);
					this.checkForChanges();
				}
			}
		});

		return this.DOM.sectionSelectWrap;
	}

	getDateTimeControl()
	{
		this.DOM.dateTimeWrap = Tag.render`<div class="calendar-field-container calendar-field-container-datetime"></div>`;

		this.dateTimeControl = new DateTimeControl(null, {
			showTimezone: false,
			outerWrap: this.DOM.dateTimeWrap,
			inlineEditMode: true
		});

		this.dateTimeControl.subscribe('onChange', (event) => {
			if (event instanceof BaseEvent)
			{
				let value = event.getData().value;
				if (this.remindersControl)
				{
					this.remindersControl.setFullDayMode(value.fullDay);
				}

				if (this.userPlannerSelector)
				{
					if (!this.userPlannerSelector.isPlannerDisplayed())
					{
						this.userPlannerSelector.showPlanner();
					}
					this.userPlannerSelector.setLocationValue(this.locationSelector.getTextValue());
					this.userPlannerSelector.setDateTime(value, true);
					this.userPlannerSelector.refreshPlanner();
				}
				this.checkForChanges();
			}
		});

		return this.DOM.dateTimeWrap;
	}

	getUserPlannerSelector()
	{
		this.DOM.userPlannerSelectorOuterWrap = Tag.render`<div>
			<div class="calendar-field-container calendar-field-container-members">
				${this.DOM.userSelectorWrap = Tag.render`
				<div class="calendar-field-block">
					<div class="calendar-members-selected">
						<span class="calendar-attendees-label"></span>
						<span class="calendar-attendees-list"></span>
						<span class="calendar-members-more">${Loc.getMessage('EC_ATTENDEES_MORE')}</span>
						<span class="calendar-members-change-link">${Loc.getMessage('EC_SEC_SLIDER_CHANGE')}</span>
					</div>
				</div>`}
				<span class="calendar-create-chat-link">${Loc.getMessage('EC_SEC_SLIDER_CREATE_CHAT_LINK')}</span>
				${this.DOM.informWrap = Tag.render`
				<div class="calendar-field-container-inform">
					<span class="calendar-field-container-inform-text">${Loc.getMessage('EC_NOTIFY_OPTION')}</span>
				</div>`}
			</div>
			<div class="calendar-user-selector-wrap"></div>
			<div class="calendar-add-popup-planner-wrap calendar-add-popup-show-planner">
				${this.DOM.plannerOuterWrap = Tag.render`
				<div class="calendar-planner-wrapper" style="height: 0">
				</div>`}
			</div>
		<div>`;

		this.userPlannerSelector = new UserPlannerSelector({
			outerWrap: this.DOM.userPlannerSelectorOuterWrap,
			wrap: this.DOM.userSelectorWrap,
			informWrap: this.DOM.informWrap,
			plannerOuterWrap: this.DOM.plannerOuterWrap,
			readOnlyMode: false,
			userId: this.userId,
			type: this.type,
			ownerId: this.ownerId,
			zIndex: this.zIndex + 10
		});

		this.userPlannerSelector.subscribe('onDateChange', this.handlePlannerSelectorChanges.bind(this));
		this.userPlannerSelector.subscribe('onNotifyChange', this.checkForChanges);
		this.userPlannerSelector.subscribe('onUserCodesChange', this.checkForChanges);
		this.userPlannerSelector.subscribe('onOpenChat', ()=>{
			EntryManager.openChatForEntry({
				entryId: this.entry.parentId,
				entry: this.entry
			});
		});

		return this.DOM.userPlannerSelectorOuterWrap;
	}

	getLocationControl()
	{
		this.DOM.locationWrap = Tag.render`<div class="calendar-field-place"></div>`;
		this.DOM.locationOuterWrap = Tag.render`<div class="calendar-field-block">
			<div class="calendar-field-title">${Loc.getMessage('EC_LOCATION_LABEL')}:</div>
			${this.DOM.locationWrap}
		</div>`;

		this.locationSelector = new Location(
			{
				wrap: this.DOM.locationWrap,
				richLocationEnabled: this.locationFeatureEnabled,
				locationList: this.locationList || [],
				iblockMeetingRoomList: this.iblockMeetingRoomList || [],
				inlineEditModeEnabled: true,
				onChangeCallback: () => {
					if (this.userPlannerSelector)
					{
						this.userPlannerSelector.setLocationValue(this.locationSelector.getTextValue());
						if (this.locationSelector.getValue().type !== undefined
							&& !this.userPlannerSelector.isPlannerDisplayed())
						{
							this.userPlannerSelector.showPlanner();
						}
						this.userPlannerSelector.refreshPlanner();
					}
					this.checkForChanges();
				}
			}
		);

		return this.DOM.locationOuterWrap;
	}

	createRemindersControl()
	{
		this.reminderValues = [];
		this.DOM.remindersWrap = Tag.render`<div class="calendar-text"></div>`;
		this.remindersControl = new Reminder({
			wrap: this.DOM.remindersWrap,
			zIndex: this.zIndex
		});

		this.remindersControl.subscribe('onChange', (event) => {
			if (event instanceof BaseEvent)
			{
				this.reminderValues = event.getData().values;
				if (!this.isNewEntry()
					&& (this.canDo('edit')
					|| this.entry.getCurrentStatus() !== false))
				{
					this.BX.ajax.runAction('calendar.api.calendarajax.updateReminders', {
						data: {
							entryId: this.entry.id,
							userId: this.userId,
							reminders: this.reminderValues
						}
					}).then((response) => {
						this.entry.data.REMIND = response.data.REMIND;
					});
				}
			}
		});

		return this.DOM.remindersWrap;
	}

	getTypeInfoControl()
	{
		this.DOM.typeInfoTitle = Tag.render`<div class="calendar-field-title"></div>`;
		this.DOM.typeInfoLink = Tag.render`<div class="calendar-field-link"></div>`;

		this.DOM.typeInfoWrap = Tag.render`
			<div class="calendar-field-block" style="display: none">
				${this.DOM.typeInfoTitle}
				${this.DOM.typeInfoLink}
			</div>
		`;
		return this.DOM.typeInfoWrap;
	}

	getRRuleInfoControl()
	{
		this.DOM.rruleInfo = Tag.render`<div class="calendar-text"></div>`;
		this.DOM.rruleInfoWrap = Tag.render`
			<div class="calendar-field-block" style="display: none">
				<div class="calendar-field-title">${Loc.getMessage('EC_REPEAT')}:</div>
				${this.DOM.rruleInfo}
			</div>
		`;
		return this.DOM.rruleInfoWrap;
	}

	getTimezoneInfoControl()
	{
		this.DOM.timezoneInfo = Tag.render`<div class="calendar-text"></div>`;
		this.DOM.timezoneInfoWrap = Tag.render`
			<div class="calendar-field-block" style="display: none">
				<div class="calendar-field-title">${Loc.getMessage('EC_TIMEZONE')}:</div>
				${this.DOM.timezoneInfo}
			</div>
		`;
		return this.DOM.timezoneInfoWrap;
	}


	isNewEntry()
	{
		return !this.entry.id;
	}

	canDo(action)
	{
		const section = this.getCurrentSection();
		//const userId = Util.getCurrentUserId();

		if (action === 'edit' || action === 'delete')
		{
			// if (this.entry.isMeeting() && userId === parseInt(this.entry.data.MEETING_HOST))
			// {
			// 	return true;
			// }

			if ((this.entry.isMeeting() && this.entry.id !== this.entry.parentId))
			{
				return false;
			}

			if (this.entry.isResourcebooking())
			{
				return false;
			}

			return section.canDo('edit');
		}

		if (action === 'view')
		{
			return section.canDo('view_time');
		}

		if (action === 'viewFull')
		{
			return section.canDo('view_full');
		}

		return true;
	}

	setFormValues()
	{
		let
			entry = this.entry,
			section = this.getCurrentSection(),
			readOnly = !this.canDo('edit');

		// Date time
		this.dateTimeControl.setValue({
			from: new Date(entry.from.getTime() - (parseInt(entry.data['~USER_OFFSET_FROM']) || 0) * 1000),
			to: new Date(entry.to.getTime() - (parseInt(entry.data['~USER_OFFSET_TO']) || 0) * 1000),
			fullDay: entry.fullDay,
			timezoneFrom: entry.getTimezoneFrom() || '',
			timezoneTo: entry.getTimezoneTo() || '',
			timezoneName: this.userSettings.timezoneName,
		});
		this.dateTimeControl.setInlineEditMode(this.isNewEntry() ? 'edit' : 'view');
		this.dateTimeControl.setViewMode(readOnly);

		// Title
		this.DOM.titleInput.value = entry.getName();

		if (readOnly)
		{
			if (this.entry.getCurrentStatus() === false)
			{
				this.DOM.titleInput.type = 'hidden'; // Hide input
				// Add label instead
				this.DOM.titleLabel = this.DOM.titleInput.parentNode.insertBefore(Tag.render`<span class="calendar-field calendar-field-string">${Text.encode(entry.getName())}</span>`, this.DOM.titleInput);
				Dom.addClass(this.DOM.titleOuterWrap, 'calendar-field-container-view');
			}
			else
			{
				this.DOM.titleInput.disabled = true;
			}
		}

		// Color
		this.colorSelector.setValue(entry.getColor() || section.color, false);
		this.colorSelector.setViewMode(readOnly && this.entry.getCurrentStatus() === false);

		// Section
		this.sectionValue = this.getCurrentSectionId();
		this.sectionSelector.updateValue();
		this.sectionSelector.setViewMode(readOnly);

		// Reminders
		this.remindersControl.setValue(entry.getReminders(), false);
		this.remindersControl.setViewMode(readOnly && this.entry.getCurrentStatus() === false);
		if (readOnly && this.entry.getCurrentStatus() === false)
		{
			this.DOM.remindersOuterWrap.style.display = 'none';
		}

		// Recurcion
		if (entry.isRecursive())
		{
			this.DOM.rruleInfoWrap.style = '';
			Dom.adjust(this.DOM.rruleInfo, {text: entry.getRRuleDescription()});
		}

		// Timezone
		const timezoneName = this.mode === 'view'
			? this.userSettings.timezoneName
			: entry.getTimezoneFrom()
		;

		if (Type.isStringFilled(entry.getTimezoneFrom())
			&& entry.getTimezoneFrom() !== this.userSettings.timezoneName
			&& !this.isNewEntry())
		{
			this.DOM.timezoneInfoWrap.style = '';
			Dom.adjust(this.DOM.timezoneInfo, {text: timezoneName});
		}

		// Location
		let location = entry.getLocation();
		if (readOnly && !location)
		{
			Dom.remove(this.DOM.locationOuterWrap);
		}
		else
		{
			this.locationSelector.setValue(entry.getLocation());
		}

		if ((this.userPlannerSelector
			&& (this.canDo('viewFull') || entry.getCurrentStatus() !== false)))
		{
			this.userPlannerSelector.setValue({
				attendeesEntityList: entry.getAttendeesEntityList(),
				location: location,
				attendees: entry.getAttendees(),
				notify: !entry.isMeeting() || entry.getMeetingNotify(),
				viewMode: this.getMode() === CompactEventForm.VIEW_MODE,
				entryId: entry.id
			});
			this.userPlannerSelector.setDateTime(this.dateTimeControl.getValue());
			this.userPlannerSelector.setViewMode(readOnly);
		}
		else
		{
			Dom.remove(this.DOM.userPlannerSelectorOuterWrap);
		}

		this.updateSetMeetingButtons();

		let hideInfoContainer = true;
		this.DOM.infoContainer = this.DOM.wrap.querySelector('.calendar-field-container-info');
		for(let i = 0; i <= this.DOM.infoContainer.childNodes.length; i++)
		{
			if (Type.isElementNode(this.DOM.infoContainer.childNodes[i])
				&& this.DOM.infoContainer.childNodes[i].style.display !== 'none')
			{
				hideInfoContainer = false;
			}
		}
		if (hideInfoContainer)
		{
			this.DOM.infoContainer.style.display = 'none';
		}
	}

	save(options = {})
	{
		if (this.state === this.STATE.REQUEST)
			return;

		const entry = this.getCurrentEntry();
		options = Type.isPlainObject(options) ? options : {};

		if (this.isNewEntry()
			&& this.userPlannerSelector.hasExternalEmailUsers()
			&& Util.checkEmailLimitationPopup()
			&& !options.emailLimitationDialogShown)
		{
			EntryManager.showEmailLimitationDialog({
				callback: (params) => {
					options.emailLimitationDialogShown = true;
					this.save(options);
				}
			});
			return false;
		}

		if (!this.userSettings.sendFromEmail
			&& this.userPlannerSelector.hasExternalEmailUsers())
		{
			EntryManager.showConfirmedEmailDialog({
				callback: (params) => {
					this.save(options);
				}
			});
			return false;
		}

		if (!this.isNewEntry()
			&& entry.isRecursive()
			&& !options.confirmed
			&& this.getformDataChanges(['section', 'notify']).length > 0)
		{
			EntryManager.showConfirmEditDialog({
				callback: (params) => {
					options.recursionMode = params.recursionMode;
					options.confirmed = true;
					this.save(options);
				}
			});
			return false;
		}

		if (!this.isNewEntry()
			&& entry.isMeeting()
			&& options.sendInvitesAgain === undefined
			&& this.getformDataChanges().includes('date&time')
			&& entry.getAttendees().find((item) => {return item.STATUS === 'N';})
		)
		{
			EntryManager.showReInviteUsersDialog({
				callback: (params) => {
					options.sendInvitesAgain = params.sendInvitesAgain;
					this.save(options);
				}
			});
			return false;
		}

		// Dom.addClass(this.DOM.saveBtn, this.BX.UI.Button.State.CLOCKING);
		// Dom.addClass(this.DOM.closeBtn, this.BX.UI.Button.State.DISABLED);

		const dateTime = this.dateTimeControl.getValue();
		const data = {
			id: entry.id,
			section: this.sectionValue,
			name: this.DOM.titleInput.value,
			reminder: this.remindersControl.getSelectedValues(),
			date_from: dateTime.fromDate,
			date_to: dateTime.toDate,
			skip_time: dateTime.fullDay ? 'Y' : 'N',
			time_from: dateTime.fromTime,
			time_to: dateTime.toTime,
			location: this.locationSelector.getTextValue(),
			tz_from: entry.getTimezoneFrom(),
			tz_to: entry.getTimezoneTo(),
			meeting_notify: this.userPlannerSelector.getInformValue() ? 'Y' : 'N',
			exclude_users: this.excludeUsers || [],
			attendeesEntityList: this.userPlannerSelector.getEntityList(),
			sendInvitesAgain: options.sendInvitesAgain ? 'Y' : 'N'
		};

		if (entry.id && entry.isRecursive())
		{
			data.EVENT_RRULE = entry.data.RRULE;
		}

		if (options.recursionMode)
		{
			data.rec_edit_mode = options.recursionMode;
			data.current_date_from = Util.formatDate(entry.from);
		}

		if (this.getCurrentSection().color.toLowerCase() !== this.colorSelector.getValue().toLowerCase())
		{
			data.color = this.colorSelector.getValue();
		}

		this.state = this.STATE.REQUEST;

		this.BX.ajax.runAction('calendar.api.calendarajax.editEntry', {
			data: data
		})
			.then((response) => {
				// Dom.removeClass(this.DOM.saveBtn, this.BX.UI.Button.State.CLOCKING);
				// Dom.removeClass(this.DOM.closeBtn, this.BX.UI.Button.State.DISABLED);
				this.state = this.STATE.READY;
				if (response.data.entryId)
				{
					if (entry.id)
					{
						EntryManager.showEditEntryNotification(response.data.entryId);
					}
					else
					{
						EntryManager.showNewEntryNotification(response.data.entryId);
					}
				}

				this.emit('onSave', new BaseEvent({
					data: {
						responseData: response.data,
						options: options
					}
				}));
				this.close();

				if (response.data.displayMobileBanner)
				{
					new MobileSyncBanner().showInPopup();
				}

				if (response.data.countEventWithEmailGuestAmount)
				{
					Util.setEventWithEmailGuestAmount(response.data.countEventWithEmailGuestAmount);
				}
			},
			(response) => {
				// Dom.removeClass(this.DOM.saveBtn, this.BX.UI.Button.State.CLOCKING);
				// Dom.removeClass(this.DOM.closeBtn, this.BX.UI.Button.State.DISABLED);
				if (response.data && Type.isPlainObject(response.data.busyUsersList))
				{
					this.handleBusyUsersError(response.data.busyUsersList);

					let errors = [];
					response.errors.forEach((error) => {
						if (error.code !== "edit_entry_user_busy")
						{
							errors.push(error);
						}
					});
					response.errors = errors;
				}

				if (response.errors && response.errors.length)
				{
					this.showError(response.errors);
				}

				this.state = this.STATE.ERROR;
			}
		);
	}

	handleBusyUsersError(busyUsers)
	{
		let
			users = [],
			userIds = [];

		for (let id in busyUsers)
		{
			if (busyUsers.hasOwnProperty(id))
			{
				users.push(busyUsers[id]);
				userIds.push(id);
			}
		}

		this.busyUsersDialog = new BusyUsersDialog();
		this.busyUsersDialog.subscribe('onSaveWithout', () => {
			this.excludeUsers = userIds.join(',');
			this.save();
		});

		this.busyUsersDialog.show({users: users});
	}

	handleKeyPress(e)
	{
		if(this.getMode() === CompactEventForm.EDIT_MODE
			&& e.keyCode === Util.getKeyCode('enter'))
		{
			this.save();
		}
		else if(e.keyCode === Util.getKeyCode('escape')
			&& this.couldBeClosedByEsc())
		{
			this.close();
		}
		else if(e.keyCode === Util.getKeyCode('delete')
			&& !this.isNewEntry()
			&& this.canDo('delete'))
		{
			let target = event.target || event.srcElement;
			if (Type.isDomNode(target) && target.tagName !== 'INPUT')
			{
				EntryManager.deleteEntry(this.entry);
				this.close();
			}
		}
	}

	getCurrentEntry()
	{
		return this.entry;
	}

	getCurrentSection()
	{
		let section = false;
		const sectionId = this.getCurrentSectionId();

		if (sectionId
			&& this.sectionIndex[sectionId] !== undefined
			&& this.sections[this.sectionIndex[sectionId]] !== undefined)
		{
			section = this.sections[this.sectionIndex[sectionId]];
		}
		return section;
	}
	getCurrentSectionId()
	{
		let sectionId = 0;
		if (this.sectionValue)
		{
			sectionId = this.sectionValue;
		}
		else
		{
			const entry = this.getCurrentEntry();
			if (entry instanceof Entry)
			{
				sectionId = parseInt(entry.sectionId);
			}

			if (!sectionId
				&& this.lastUsedSection
				&& this.sections[this.sectionIndex[parseInt(this.lastUsedSection)]])
			{
				sectionId = parseInt(this.lastUsedSection);
			}
			if (!sectionId && this.sections[0])
			{
				sectionId = parseInt(this.sections[0].ID);
			}
		}
		return sectionId;
	}

	handlePlannerSelectorChanges(event)
	{
		if (event instanceof BaseEvent)
		{
			let data = event.getData();
			// Date time
			this.dateTimeControl.setValue({
				from: data.dateFrom,
				to: data.dateTo
			});
			//this.checkLocationAccessibility();
		}
	}


	editEntryInSlider()
	{
		this.checkDataBeforeCloseMode = false;

		const dateTime = this.dateTimeControl.getValue();
		BX.Calendar.EntryManager.openEditSlider({
			entry: this.entry,
			type: this.type,
			ownerId: this.ownerId,
			userId: this.userId,
			formDataValue: {
				section: this.sectionValue,
				name: this.DOM.titleInput.value,
				reminder: this.remindersControl.getSelectedRawValues(),
				color: this.colorSelector.getValue(),
				from: dateTime.from,
				to: dateTime.to,
				fullDay: dateTime.fullDay,
				location: this.locationSelector.getTextValue(),
				meetingNotify: this.userPlannerSelector.getInformValue() ? 'Y' : 'N',
				attendeesEntityList: this.userPlannerSelector.getEntityList()
			}
		});
		this.close();
	}

	outsideMouseDownClose(event)
	{
		let target = event.target || event.srcElement;
		this.outsideMouseDown = !target.closest('div.popup-window');
	}

	checkOutsideClickClose(event)
	{
		let target = event.target || event.srcElement;
		this.outsideMouseUp = !target.closest('div.popup-window');
		if(this.couldBeClosedByEsc()
			&& this.outsideMouseDown
			&& this.outsideMouseUp
			&& (this.getMode() === CompactEventForm.VIEW_MODE
				|| !this.formDataChanged()
				|| this.isNewEntry())
		)
		{
			setTimeout(this.close.bind(this), 0);
		}
	}

	couldBeClosedByEsc()
	{
		return !PopupManager._popups.find((popup)=>{return popup && popup.getId() !== this.popupId && popup.isShown();});
	}

	emitOnChange()
	{
		this.emit('onChange', new BaseEvent({
			data: {
				form: this,
				entry: this.entry
			}
		}));
	}
}