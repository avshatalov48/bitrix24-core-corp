import { Tag, Type, Loc, Dom, Event, Text} from 'main.core';
import { RoomsManager } from 'calendar.roomsmanager';
import { Util } from 'calendar.util';

export class Location
{
	static locationList = [];
	static meetingRoomList = [];
	static currentRoomCapacity = 0;
	static accessibility = [];
	static DAY_LENGTH = 86400000;
	datesRange = [];
	viewMode = false;

	constructor(params)
	{
		this.params = params;
		this.id = params.id || 'location-' + Math.round(Math.random() * 1000000);
		this.zIndex = params.zIndex || 3100;

		this.DOM = {
			wrapNode: params.wrap
		};
		this.roomsManager = params.roomsManager || null;
		this.locationAccess = params.locationAccess || false;
		this.disabled = !params.richLocationEnabled;
		this.value = {type: '', text: '', value: ''};
		this.inlineEditModeEnabled = params.inlineEditModeEnabled;
		this.meetingRooms = params.iblockMeetingRoomList || [];
		Location.setMeetingRoomList(params.iblockMeetingRoomList);
		Location.setLocationList(params.locationList);
		if (!this.disabled)
		{
			this.default = this.setDefaultRoom(params.locationList) || '';
		}
		this.create();
		this.setViewMode(params.viewMode === true)
	}

	create()
	{
		this.DOM.inputWrap = this.DOM.wrapNode.appendChild(Tag.render`
			<div class="calendar-field-block"></div>
		`)

		this.DOM.alertIconLocation = Tag.render`
			<div class="ui-alert-icon-danger calendar-location-alert-icon" data-hint-no-icon="Y" data-hint="${Loc.getMessage('EC_LOCATION_OVERFLOW')}">
			<i></i>
			</div>
		`;
		if (this.inlineEditModeEnabled)
		{
			this.DOM.inlineEditLinkWrap = this.DOM.wrapNode.appendChild(Tag.render`
				<div class="calendar-field-place-link">${this.DOM.inlineEditLink = Tag.render`
					<span class="calendar-text-link">${Loc.getMessage('EC_REMIND1_ADD')}</span>`}
				</div>`);
			this.DOM.inputWrap.style.display = 'none';
			Event.bind(this.DOM.inlineEditLinkWrap, 'click', this.displayInlineEditControls.bind(this));
		}

		if (this.disabled)
		{
			Dom.addClass(this.DOM.wrapNode, 'locked');
			this.DOM.inputWrap.appendChild(Dom.create('DIV', {
				props: {className: 'calendar-lock-icon'},
				events: {
					click: () => {
						top.BX.UI.InfoHelper.show('limit_office_calendar_location');
					}
				}
			}))
		}

		this.DOM.input = this.DOM.inputWrap.appendChild(Dom.create('INPUT', {
			attrs: {
				name: this.params.inputName || '',
				placeholder: Loc.getMessage('EC_LOCATION_PLACEHOLDER'),
				type: 'text',
				autocomplete: this.disabled ? 'on' : 'off',
			},
			props: {
				className: 'calendar-field calendar-field-select'
			},
			style: {
				paddingRight: 25 + 'px',
			}
		}));
	}

	setValues()
	{
		let
			menuItemList = [],
			selectedIndex = false,
			meetingRooms = Location.getMeetingRoomList(),
			locationList = Location.getLocationList();

		if (Type.isArray(meetingRooms))
		{
			meetingRooms.forEach(function(room)
			{
				room.ID = parseInt(room.ID);
				menuItemList.push({
					ID: room.ID,
					label: room.NAME,
					labelRaw: room.NAME,
					value: room.ID,
					capacity: 0,
					type: 'mr'
				});

				if (this.value.type === 'mr'
					&& parseInt(this.value.value) === room.ID)
				{
					selectedIndex = menuItemList.length - 1;
				}
			}, this);

			if (menuItemList.length > 0)
			{
				menuItemList.push({delimiter: true});
			}
		}

		if (Type.isArray(locationList))
		{
			if (locationList.length)
			{
				locationList.forEach(function(room)
				{
					room.ID = parseInt(room.ID);
					room.LOCATION_ID = parseInt(room.LOCATION_ID);
					menuItemList.push({
						ID: room.ID,
						LOCATION_ID: room.LOCATION_ID,
						label: room.NAME,
						capacity: parseInt(room.CAPACITY) || 0,
						color: room.COLOR,
						reserved: room.reserved || false,
						labelRaw: room.NAME,
						labelCapacity: this.getCapacityMessage(room.CAPACITY),
						value: room.ID,
						type: 'calendar'
					});

					if (this.value.type === 'calendar'
						&& parseInt(this.value.value) === parseInt(room.ID))
					{
						selectedIndex = menuItemList.length - 1;
					}
				}, this);

				if (this.locationAccess)
				{
					this.loadRoomSlider();
					menuItemList.push({delimiter: true});
					menuItemList.push({
						label: Loc.getMessage('EC_LOCATION_MEETING_ROOM_SET'),
						callback: this.openRoomsSlider.bind(this)
					});
				}
			}
			else
			{
				if (this.locationAccess)
				{
					this.loadRoomSlider();
					menuItemList.push({
						label: Loc.getMessage('EC_ADD_LOCATION'),
						callback: this.openRoomsSlider.bind(this)
					});
				}
			}
		}

		if (this.value)
		{
			this.DOM.input.value = this.value.str || '';
			if (this.value.type &&
				(this.value.str === this.getTextLocation(this.value) ||
					this.getTextLocation(this.value) === Loc.getMessage('EC_LOCATION_EMPTY')))
			{
				this.DOM.input.value = '';
				this.value = '';
			}
			for (const locationListElement of Location.locationList)
			{
				if (parseInt(locationListElement.ID) === this.value.room_id)
				{
					Location.setCurrentCapacity(parseInt(locationListElement.CAPACITY));
					break;
				}
			}
		}

		if (this.selectContol)
		{
			this.selectContol.destroy();
		}

		this.selectContol = new BX.Calendar.Controls.SelectInput({
			input: this.DOM.input,
			values: menuItemList,
			valueIndex: selectedIndex,
			zIndex: this.zIndex,
			disabled: this.disabled,
			minWidth: 300,
			onChangeCallback: BX.delegate(function()
			{
				let i, value = this.DOM.input.value;
				this.value = {text: value};
				for (i = 0; i < menuItemList.length; i++)
				{
					if (menuItemList[i].labelRaw === value)
					{
						this.value.type = menuItemList[i].type;
						this.value.value = menuItemList[i].value;
						Location.setCurrentCapacity(menuItemList[i].capacity)
						break;
					}
				}

				if (Type.isFunction(this.params.onChangeCallback))
				{
					this.params.onChangeCallback();
				}
			}, this)
		});
	}

	setViewMode(viewMode)
	{
		this.viewMode = viewMode;
		if (this.viewMode)
		{
			Dom.addClass(this.DOM.wrapNode, 'calendar-location-readonly')
		}
		else
		{
			Dom.removeClass(this.DOM.wrapNode, 'calendar-location-readonly')
		}
	}

	addCapacityAlert()
	{
		if (!Dom.hasClass(this.DOM.input, 'calendar-field-location-select-border'))
		{
			Dom.addClass(this.DOM.input, 'calendar-field-location-select-border');
		}
		if (Type.isDomNode(this.DOM.alertIconLocation))
		{
			Util.initHintNode(this.DOM.alertIconLocation);
		}
		setTimeout(() => {
			this.DOM.inputWrap.appendChild(this.DOM.alertIconLocation)
		}, 200);
	}

	removeCapacityAlert()
	{
		if (Dom.hasClass(this.DOM.input, 'calendar-field-location-select-border'))
		{
			Dom.removeClass(this.DOM.input, 'calendar-field-location-select-border');
		}
		if (this.DOM.alertIconLocation.parentNode === this.DOM.inputWrap)
		{
			this.DOM.inputWrap.removeChild(this.DOM.alertIconLocation);
		}
	}

	getCapacityMessage(capacity)
	{
		let suffix;
		if ((capacity % 100 > 10) && (capacity % 100 < 20))
		{
			suffix = 5;
		}
		else
		{
			suffix = capacity % 10;
		}
		return Loc.getMessage('EC_LOCATION_CAPACITY_' + suffix, {'#NUM#': capacity})
	}
	
	checkLocationAccessibility(params)
	{
		this.getLocationAccessibility(params.from, params.to)
		.then(()=> {
			let eventTsFrom;
			let eventTsTo;
			let fromTs = params.from.getTime();
			let toTs = params.to.getTime();
			if (params.fullDay)
			{
				toTs += Location.DAY_LENGTH;
			}
			
			for (const index in Location.locationList)
			{
				Location.locationList[index].reserved = false;
				let roomId = Location.locationList[index].ID;
				for (const date of this.datesRange)
				{
					if (Type.isUndefined(Location.accessibility[date][roomId]))
					{
						continue;
					}
					
					for (const event of Location.accessibility[date][roomId])
					{
						if (parseInt(event.PARENT_ID) === parseInt(params.currentEventId))
						{
							continue;
						}
						
						eventTsFrom = Util.parseDate(event.DATE_FROM).getTime();
						eventTsTo = Util.parseDate(event.DATE_TO).getTime();
						if (event.DT_SKIP_TIME !== 'Y')
						{
							eventTsFrom -= event['~USER_OFFSET_FROM'] * 1000;
							eventTsTo -= event['~USER_OFFSET_TO'] * 1000;
						}
						else
						{
							eventTsTo += Location.DAY_LENGTH;
						}
						
						if (eventTsFrom < toTs && eventTsTo > fromTs)
						{
							Location.locationList[index].reserved = true;
							break;
						}
					}
					if (Location.locationList[index].reserved)
					{
						break;
					}
				}
			}
			
			this.setValues();
		});
	}
	
	getLocationAccessibility(from, to)
	{
		return new Promise((resolve) => {
			this.datesRange = Location.getDatesRange(from, to);
			let isCheckedAccessibility = true;
			
			for (let date of this.datesRange)
			{
				if (Type.isUndefined(Location.accessibility[date]))
				{
					isCheckedAccessibility = false;
					break;
				}
			}
			
			if (!isCheckedAccessibility)
			{
				BX.ajax.runAction('calendar.api.locationajax.getLocationAccessibility', {
					data: {
						datesRange: this.datesRange,
						locationList: Location.locationList,
					}
				}).then(
					(response) => {
						for (let date of this.datesRange)
						{
							Location.accessibility[date] = response.data[date];
						}
						resolve(Location.accessibility, this.datesRange);
					},
					(response) => {
						resolve(response.errors);
					}
				);
			}
			else
			{
				resolve(Location.accessibility, this.datesRange);
			}
		});
	}
	
	static handlePull(params)
	{
		if (!params.fields.DATE_FROM || !params.fields.DATE_TO)
		{
			return;
		}
		let dateFrom = Util.parseDate(params.fields.DATE_FROM);
		let dateTo = Util.parseDate(params.fields.DATE_TO);
		let datesRange = Location.getDatesRange(dateFrom, dateTo);
		
		for (let date of datesRange)
		{
			if (Location.accessibility[date])
			{
				delete Location.accessibility[date];
			}
		}
	}
	
	loadRoomSlider()
	{
		if (!this.roomsManagerFromDB)
		{
			this.getRoomsManager()
				.then(this.getRoomsManagerData()
			);
		}
	}
	
	openRoomsSlider()
	{
		this.getRoomsInterface()
			.then(function(RoomsInterface) {
				if (!this.roomsInterface)
				{
					this.roomsInterface = new RoomsInterface(
						{
							calendarContext: null,
							readonly: false,
							roomsManager: this.roomsManagerFromDB,
							isConfigureList: true
						}
					);
				}
				this.roomsInterface.show();
			}.bind(this));
	}

	getTextValue(value)
	{
		if (!value)
		{
			value = this.value;
		}

		let res = value.str || value.text || '';
		if (value && value.type === 'mr')
		{
			res = 'ECMR_' + value.value + (value.mrevid ? '_' + value.mrevid : '');

		}
		else if (value && value.type === 'calendar')
		{
			res = 'calendar_' + value.value + (value.room_event_id ? '_' + value.room_event_id : '');
		}
		return res;
	}

	getValue()
	{
		return this.value;
	}

	setValue(value)
	{
		if (Type.isPlainObject(value))
		{
			this.value.text = value.text || '';
			this.value.type = value.type || '';
			this.value.value = value.value || '';
		}
		else
		{
			this.value = Location.parseStringValue(value);
		}

		this.setValues();

		if (this.inlineEditModeEnabled)
		{
			let textLocation = this.getTextLocation(this.value);
			this.DOM.inlineEditLink.innerHTML = Text.encode(textLocation || Loc.getMessage('EC_REMIND1_ADD'));
		}
	}

	// parseLocation
	static parseStringValue(str)
	{
		if (!Type.isString(str))
		{
			str = '';
		}

		let
			res = {
				type : false,
				value : false,
				str : str
			};

		if (str.substr(0, 5) === 'ECMR_')
		{
			res.type = 'mr';
			let value = str.split('_');
			if (value.length >= 2)
			{
				if (!isNaN(parseInt(value[1])) && parseInt(value[1]) > 0)
				{
					res.value = res.mrid = parseInt(value[1]);
				}

				if (!isNaN(parseInt(value[2])) && parseInt(value[2]) > 0)
				{
					res.mrevid = parseInt(value[2]);
				}
			}
		}
		else if (str.substr(0, 9) === 'calendar_')
		{
			res.type = 'calendar';
			let value = str.split('_');
			if (value.length >= 2)
			{
				if (!isNaN(parseInt(value[1])) && parseInt(value[1]) > 0)
				{
					res.value = res.room_id = parseInt(value[1]);
				}
				if (!isNaN(parseInt(value[2])) && parseInt(value[2]) > 0)
				{
					res.room_event_id = parseInt(value[2]);
				}
			}
		}

		return res;
	}

	getTextLocation(location)
	{
		let
			value = Type.isPlainObject(location) ? location : Location.parseStringValue(location),
			i, str = value.str;

		if (Type.isArray(this.meetingRooms) && value.type === 'mr')
		{
			str = Loc.getMessage('EC_LOCATION_EMPTY');
			for (i = 0; i < this.meetingRooms.length; i++)
			{
				if (parseInt(value.value) === parseInt(this.meetingRooms[i].ID))
				{
					str = this.meetingRooms[i].NAME;
					break;
				}
			}
		}

		if (Type.isArray(Location.locationList) && value.type === 'calendar')
		{
			str = Loc.getMessage('EC_LOCATION_EMPTY');
			for (i = 0; i < Location.locationList.length; i++)
			{
				if (parseInt(value.value) === parseInt(Location.locationList[i].ID))
				{
					str = Location.locationList[i].NAME;
					break;
				}
			}
		}

		return str;
	}

	static setLocationList(locationList)
	{
		if (Type.isArray(locationList))
		{
			Location.locationList = locationList;
			this.sortLocationList();
		}
	}

	static sortLocationList()
	{
		Location.locationList.sort((a,b) => {
			if (a.NAME.toLowerCase() > b.NAME.toLowerCase())
			{
				return 1;
			}
			if (a.NAME.toLowerCase() < b.NAME.toLowerCase())
			{
				return -1;
			}
			return 0;
		})
	}

	static getLocationList()
	{
		return Location.locationList;
	}

	static setMeetingRoomList(meetingRoomList)
	{
		if (Type.isArray(meetingRoomList))
		{
			Location.meetingRoomList = meetingRoomList;
		}
	}
	
	static getMeetingRoomList()
	{
		return Location.meetingRoomList;
	}
	
	static setLocationAccessibility(accessibility)
	{
		Location.accessibility = accessibility;
	}
	
	static getLocationAccessibility()
	{
		return Location.accessibility;
	}
	
	static setCurrentCapacity(capacity)
	{
		Location.currentRoomCapacity = capacity;
	}

	static getCurrentCapacity()
	{
		return Location.currentRoomCapacity || 0;
	}
	
	displayInlineEditControls()
	{
		this.DOM.inlineEditLinkWrap.style.display = 'none';
		this.DOM.inputWrap.style.display = '';
	}

	setDefaultRoom(locationList)
	{
		if (this.roomsManager && !RoomsManager.isEmpty(locationList))
		{
			this.activeRooms = this.roomsManager.getRoomsInfo().active;
			if (!RoomsManager.isEmpty(this.activeRooms))
			{
				const activeRoomId = this.activeRooms[0];
				for (const locationListElement of locationList)
				{
					if (parseInt(locationListElement.ID) === activeRoomId)
					{
						Location.setCurrentCapacity(parseInt(locationListElement.CAPACITY));
						return 'calendar_' + activeRoomId;
					}
				}
			}
			else
			{
				Location.setCurrentCapacity(parseInt(locationList[0].CAPACITY));
				return 'calendar_' + locationList[0].ID;
			}
		}
		else
		{
			return '';
		}
	}

	getRoomsInterface()
	{
		return new Promise((resolve) => {
			const bx = BX.Calendar.Util.getBX();
			const extensionName = 'calendar.rooms';
			bx.Runtime.loadExtension(extensionName)
				.then(() =>
					{
						if (bx.Calendar.Rooms.RoomsInterface)
						{
							resolve(bx.Calendar.Rooms.RoomsInterface);
						}
						else
						{
							console.error('Extension ' + extensionName + ' not found');
							resolve(bx.Calendar.Rooms.RoomsInterface);
						}
					}
				);
		});
	}

	getRoomsManager()
	{
		return new Promise((resolve) => {
			const bx = BX.Calendar.Util.getBX();
			const extensionName = 'calendar.roomsmanager';
			bx.Runtime.loadExtension(extensionName)
				.then(() =>
					{
						if (bx.Calendar.RoomsManager)
						{
							resolve(bx.Calendar.RoomsManager);
						}
						else
						{
							console.error('Extension ' + extensionName + ' not found');
							resolve(bx.Calendar.RoomsManager);
						}
					}
				);
		});
	}

	getRoomsManagerData()
	{
		return new Promise((resolve) => {
			BX.ajax.runAction('calendar.api.locationajax.getRoomsManagerData')
				.then((response) => {

						this.roomsManagerFromDB = new RoomsManager(
							{
								sections: response.data.sections,
								rooms: response.data.rooms
							},
							{
								locationAccess: response.data.config.locationAccess,
								hiddenSections: response.data.config.hiddenSections,
								type: response.data.config.type,
								ownerId: response.data.config.ownerId,
								userId: response.data.config.userId,
								new_section_access: response.data.config.defaultSectionAccess,
								sectionAccessTasks: response.data.config.sectionAccessTasks,
								showTasks: response.data.config.showTasks,
								locationContext: this //for updating list of locations in event creation menu
							}
						)
						resolve(response.data);
					},
					// Failure
					(response) => {
						console.error('Extension not found');
						resolve(response.data);
					}
				);
		});
	}
	
	static getDateInFormat(date)
	{
		return ('0' + date.getDate()).slice(-2) + '.'
			+ ('0' + (date.getMonth() + 1)).slice(-2) + '.'
			+ date.getFullYear()
	}
	
	static getDatesRange(from, to)
	{
		let fromDate = new Date(from);
		let toDate = new Date(to);
		let startDate = fromDate.setHours(0, 0, 0, 0);
		let finishDate = toDate.setHours(0, 0, 0, 0);
		let result = [];
		while (startDate <= finishDate)
		{
			result.push(Location.getDateInFormat(new Date(startDate)));
			startDate += Location.DAY_LENGTH;
		}
		
		return result;
	}
}
