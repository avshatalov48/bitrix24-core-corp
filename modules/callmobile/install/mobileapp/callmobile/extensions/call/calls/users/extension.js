(function() {
	let UserModel = function(config) {
		this.data = {
			id: BX.prop.getInteger(config, 'id', 0),
			name: BX.prop.getString(config, 'name', ''),
			avatar: BX.prop.getString(config, 'avatar', ''),
			gender: BX.prop.getString(config, 'gender', ''),
			workPosition: BX.prop.getString(config, 'workPosition', ''),
			extranet: BX.prop.getBoolean(config, 'extranet', false),
			invited: BX.prop.getBoolean(config, 'invited', false),
			lastActivityDate: BX.prop.getString(config, 'lastActivityDate', ''),
			state: BX.prop.getString(config, 'state', BX.Call.UserState.Idle),
			talking: BX.prop.getBoolean(config, 'talking', false),
			cameraState: BX.prop.getBoolean(config, 'cameraState', false),
			videoPaused: BX.prop.getBoolean(config, 'videoPaused', false),
			microphoneState: BX.prop.getBoolean(config, 'microphoneState', true),
			screenState: BX.prop.getBoolean(config, 'screenState', false),
			floorRequestState: BX.prop.getBoolean(config, 'floorRequestState', false),
			localUser: BX.prop.getBoolean(config, 'localUser', false),
			centralUser: BX.prop.getBoolean(config, 'centralUser', false),
			pinned: BX.prop.getBoolean(config, 'pinned', false),
			presenter: BX.prop.getBoolean(config, 'presenter', false),
			order: BX.prop.getInteger(config, 'order', false),
		};

		for (var fieldName in this.data)
		{
			if (this.data.hasOwnProperty(fieldName))
			{
				Object.defineProperty(this, fieldName, {
					get: this._getField(fieldName).bind(this),
					set: this._setField(fieldName).bind(this),
				});
			}
		}

		this.onUpdate = {
			talking: this._onUpdateTalking.bind(this),
			state: this._onUpdateState.bind(this),
		};

		this.talkingStop = null;

		this.eventEmitter = new JNEventEmitter();
	};

	UserModel.Event = {
		Changed: 'changed',
	};

	UserModel.prototype._getField = function(fieldName) {
		return function() {
			return this.data[fieldName];
		};
	};

	UserModel.prototype._setField = function(fieldName) {
		return function(newValue) {
			var oldValue = this.data[fieldName];
			if (oldValue == newValue)
			{
				return;
			}
			this.data[fieldName] = newValue;

			if (this.onUpdate.hasOwnProperty(fieldName))
			{
				this.onUpdate[fieldName](newValue, oldValue);
			}

			this.eventEmitter.emit(UserModel.Event.Changed, [{
				user: this,
				fieldName: fieldName,
				oldValue: oldValue,
				newValue: newValue,
			}]);
		};
	};

	UserModel.prototype._onUpdateTalking = function(talking) {
		if (talking)
		{
			this.floorRequestState = false;
		}
		else
		{
			this.talkingStop = (new Date()).getTime();
		}
	};

	UserModel.prototype._onUpdateState = function(newValue) {
		if (newValue != BX.Call.UserState.Connected)
		{
			this.talking = false;
			this.screenState = false;
			this.floorRequestState = false;
		}
	};

	UserModel.prototype.wasTalkingAgo = function() {
		if (this.state != BX.Call.UserState.Connected)
		{
			return +Infinity;
		}
		if (this.talking)
		{
			return 0;
		}
		if (!this.talkingStop)
		{
			return +Infinity;
		}

		return ((new Date()).getTime() - this.talkingStop);
	};

	UserModel.prototype.getDescription = function() {
		if (this.data.invited && !this.data.last_activity_date)
		{
			return BX.message('MOBILE_CALL_INVITATION_NOT_ACCEPTED');
		}

		if (this.data.workPosition)
		{
			return this.data.workPosition;
		}

		if (this.data.extranet)
		{
			return BX.message('MOBILE_CALL_EXTRANET_USER');
		}

		return BX.message('MOBILE_CALL_EMPLOYEE');
	};

	UserModel.prototype.subscribe = function(event, handler) {
		this.eventEmitter.on(event, handler);
	};

	UserModel.prototype.unsubscribe = function(event, handler) {
		this.eventEmitter.off(event, handler);
	};

	var UserRegistry = function(config) {
		/** @var {UserModel[]} this.users */
		this.users = BX.prop.getArray(config, 'users', []);

		this.eventEmitter = new JNEventEmitter();
		this._sort();
	};

	UserRegistry.Event = {
		UserAdded: 'userAdded',
		UserChanged: 'userChanged',
	};

	UserRegistry.prototype.subscribe = function(eventName, handler) {
		this.eventEmitter.on(eventName, handler);
	};

	/**
	 *
	 * @param {int} userId
	 * @returns {UserModel|null}
	 */
	UserRegistry.prototype.get = function(userId) {
		for (var i = 0; i < this.users.length; i++)
		{
			if (this.users[i].id == userId)
			{
				return this.users[i];
			}
		}
		return null;
	};

	UserRegistry.prototype.push = function(user) {
		if (!(user instanceof UserModel))
		{
			throw Error('user should be instance of UserModel');
		}

		this.users.push(user);
		this._sort();
		user.subscribe(UserModel.Event.Changed, this._onUserChanged.bind(this));
		this.eventEmitter.emit(UserRegistry.Event.UserAdded, [{
			user: user,
		}]);
	};

	UserRegistry.prototype._onUserChanged = function(event) {
		this.eventEmitter.emit(UserRegistry.Event.UserChanged, [event.data]);
	};

	UserRegistry.prototype._sort = function() {
		this.users = this.users.sort(function(a, b) {
			return a.order - b.order;
		});
	};

	window.UserModel = UserModel;
	window.UserRegistry = UserRegistry;
})();