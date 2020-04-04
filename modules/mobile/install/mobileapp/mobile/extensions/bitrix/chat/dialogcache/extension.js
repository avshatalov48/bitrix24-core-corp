"use strict";

/**
 * @requires module:chat/tables
 * @requires module:chat/dataconverter
 * @requires module:db
 * @module chat/dialogcache
 */

(function(){

	class ChatDialogCache
	{
		constructor(userId = 'default', languageId = 'en')
		{
			this.maxDialogStore = 250;
			this.maxMessageStore = 20;

			this.dialogs = new Map();

			this.dialogQueue = [];
			this.updateDialogQueue = [];

			this.messageQueue = [];
			this.updateMessageQueue = [];
			this.deleteMessageQueue = [];

			this.database = null;
		}

		openDatabase(userId = 'default', languageId = 'en')
		{
			this.database = new ReactDatabase(ChatDatabaseName, userId, languageId);
		}

		/**
		 *
		 * @param {ReactDatabase} database
		 */
		setDatabase(database)
		{
			this.database = database;
		}

		set dialogsLoaded(value)
		{
			if (value === false)
			{
				console.warn('ChatDialogCache.dialogsLoaded (property): this value dont accept value "false"');
				return false;
			}

			if (this._dialogsLoaded)
			{
				return false;
			}

			this._dialogsLoaded = true;

			this.dialogQueue.forEach(dialog => this.addDialog(dialog.id, dialog.params));
			this.dialogQueue = [];

			this.updateDialogQueue.forEach(dialog => this.updateDialog(dialog.id, dialog.params));
			this.updateDialogQueue = [];

			this.messageQueue.forEach(message => this.addMessage(message.dialogId, message.data));
			this.messageQueue = [];

			this.updateMessageQueue.forEach(message => this.updateMessage(message.dialogId, message.data));
			this.updateMessageQueue = [];

			this.deleteMessageQueue.forEach(message => this.deleteMessage(message.dialogId, message.messageId));
			this.deleteMessageQueue = [];
		}

		get dialogsLoaded()
		{
			return this._dialogsLoaded === true;
		}

		/**
		 * Get all dialogs storage with messages
		 *
		 * @param {boolean} ignoreCache - ignore cache while load data
		 * @returns {Promise}
		 */
		getStore(ignoreCache = false)
		{
			return new Promise((resolve, reject) =>
			{
				if (!this.database)
				{
					reject(false);
					return false;
				}

				let executeTime = new Date();

				if (this.dialogsLoaded && !ignoreCache)
				{
					console.info("ChatDialogCache.getStore: success ("+(new Date() - executeTime)+'ms)', this.dialogs);
					resolve(this.dialogs)
				}
				else
				{
					let loadDialogsCallback = () => {
						console.info("ChatDialogCache.getStore: success ("+(new Date() - executeTime)+'ms)', this.dialogs);
						resolve(this.dialogs);
					};
					this._loadDialogs().then(loadDialogsCallback).catch(loadDialogsCallback);
				}

				return true
			});
		}

		/**
		 * Get dialog with messages
		 *
		 * @param {number} dialogId
		 * @param {boolean} cache
		 * @returns {Promise.<Object|boolean>}
		 */
		getDialog(dialogId, cache = true)
		{
			return new Promise((resolve, reject) =>
			{
				if (!this.database)
				{
					reject(false);
					return true;
				}

				dialogId = ChatDialogCache.formatDialogId(dialogId);
				if (!dialogId)
				{
					console.warn("ChatDialogCache.getDialog: format dialogId is not correct");
					reject(false);
					return true;
				}

				let executeTime = new Date();

				if (this.dialogs.has(dialogId) && cache)
				{
					let result = this.dialogs.get(dialogId);
					console.info("ChatDialogCache.getDialog: success (" + (new Date() - executeTime) + 'ms)', result);
					resolve(result);
				}
				else
				{
					this._loadDialogs(dialogId).then(() => {
						let result = this.dialogs.get(dialogId);
						console.info("ChatDialogCache.getDialog: success (" + (new Date() - executeTime) + 'ms)', result);
						resolve(result);
					}).catch(() => {
						console.info("ChatDialogCache.getDialog: dialog not found (" + (new Date() - executeTime) + 'ms)');
						reject(false);
					})
				}
			});
		}


		/**
		 * Add dialog info
		 *
		 * @param dialogId
		 * @param {{options: {}, readList: {}, userList: {}, phoneList: {}, unreadList: []}} params
		 * @returns {boolean|null}
		 */
		addDialog(dialogId, params = {})
		{
			if (!this.database)
			{
				return null;
			}

			dialogId = ChatDialogCache.formatDialogId(dialogId);
			if (!dialogId)
			{
				console.warn("ChatDialogCache.adddDialog: format dialogId is not correct");
				return null;
			}

			params = Object.assign({}, params);

			if (!this.dialogsLoaded)
			{
				this.dialogQueue.push({dialogId, params});
				return true;
			}

			if (this.dialogs.has(dialogId))
			{
				let dialog = this.dialogs.get(dialogId);

				dialog.lastModified = new Date();

				if (params.readList)
				{
					dialog.readList = params.readList;
				}
				if (params.userList)
				{
					dialog.userList = params.userList;
				}
				if (params.phoneList)
				{
					dialog.phoneList = params.phoneList;
				}
				if (params.unreadList)
				{
					dialog.unreadList = params.unreadList;
				}
				if (params.options)
				{
					dialog.options = params.options;
				}
			}
			else
			{
				this.dialogs.set(dialogId, {
					id: dialogId,
					lastModified: new Date(),
					readList: params.readList || {},
					unreadList: params.unreadList || [],
					userList: params.userList || {},
					phoneList: params.phoneList || {},
					options: params.options || {},
					messages: new Map()
				});
			}

			return new Promise((resolve, reject) =>
			{
				this.database.table(ChatTables.dialogOptions).then(table =>
				{
					table.replace(
						this._getDialogFields(dialogId)
					).then(items => {
						resolve(true);
					}).catch(error => console.error(error));
				});
			});
		}

		/**
		 * Update dialog info
		 *
		 * @param dialogId
		 * @param {{options: {}, readList: {}, userList: {}, phoneList: {}, unreadList: []}} params
		 * @returns {boolean|null|Promise}
		 */
		updateDialog(dialogId, params)
		{
			if (!this.database)
			{
				return null;
			}

			dialogId = ChatDialogCache.formatDialogId(dialogId);
			if (!dialogId)
			{
				console.warn("ChatDialogCache.updateDialog: format dialogId is not correct");
				return null;
			}

			params = Object.assign({}, params);

			if (!this.dialogsLoaded)
			{
				this.updateDialogQueue.push({dialogId, params});
				return false;
			}

			if (!this.dialogs.has(dialogId))
			{
				return false;
			}

			let dialog = this.dialogs.get(dialogId);

			dialog.lastModified = new Date();

			if (params.readList)
			{
				dialog.readList = params.readList;
			}
			if (params.unreadList)
			{
				dialog.unreadList = params.unreadList;
			}
			if (params.userList)
			{
				dialog.userList = params.userList;
			}
			if (params.phoneList)
			{
				dialog.phoneList = params.phoneList;
			}
			if (params.options)
			{
				dialog.options = params.options;
			}

			return new Promise((resolve, reject) =>
			{
				this.database.table(ChatTables.dialogOptions).then(table =>
				{
					let updateFields = {};

					updateFields.lastModified = (new Date()).getTime();

					updateFields.lastModifiedAtom = (new Date()).toString();

					updateFields.options = Object.assign({}, dialog.options);

					if (dialog.readList)
					{
						updateFields.options.readList = dialog.readList;
					}
					if (dialog.unreadList)
					{
						updateFields.options.unreadList = dialog.unreadList;
					}
					if (dialog.userList)
					{
						updateFields.options.userList = dialog.userList;
					}
					if (dialog.phoneList)
					{
						updateFields.options.phoneList = dialog.phoneList;
					}

					table.update(dialogId.toString(), updateFields).then(items => {
						resolve(true);
					}).catch(error => console.error(error));
				});
			});
		}

		/**
		 * Save message for specified dialog
		 *
		 * @param dialogId
		 * @param {{message: {}, files: Map, users: Map}} data
		 * @returns {boolean|null}
		 */
		addMessage(dialogId, data)
		{
			if (!this.database)
			{
				return null;
			}

			dialogId = ChatDialogCache.formatDialogId(dialogId);
			if (!dialogId)
			{
				console.warn("ChatDialogCache.addMessage: format dialogId is not correct");
				return null;
			}

			data = Object.assign({}, data);

			if (!this.dialogsLoaded)
			{
				this.messageQueue.push({dialogId, data});
				return true;
			}

			if (!this.dialogs.has(dialogId))
			{
				this.dialogs.set(dialogId, {
					id: dialogId,
					lastModified: new Date(),
					readList: {},
					unreadList: [],
					userList: {},
					phoneList: {},
					options: {},
					messages: new Map()
				});
			}

			this.dialogs.get(dialogId).messages.set(data.message.id.toString(), data);

			if (typeof this.removeMessagesIfOverflowTimeout == 'undefined')
			{
				this.removeMessagesIfOverflowTimeout = {};
			}

			clearTimeout(this.removeMessagesIfOverflowTimeout[dialogId]);
			this.removeMessagesIfOverflowTimeout[dialogId] = setTimeout(() => {
				delete this.removeMessagesIfOverflowTimeout[dialogId];
				this._removeMessagesIfOverflow(dialogId).catch(() => {});
			}, 500);

			this._addMessageToDb(dialogId, data);

			return true;
		}

		/**
		 * Update message for specified dialog
		 *
		 * @param dialogId
		 * @param {{message: {}}} data
		 * @returns {boolean|null}
		 */
		updateMessage(dialogId, data)
		{
			if (!this.database)
			{
				return null;
			}

			dialogId = ChatDialogCache.formatDialogId(dialogId);
			if (!dialogId)
			{
				console.warn("ChatDialogCache.updateMessage: format dialogId is not correct");
				return null;
			}

			data = Object.assign({}, data);

			if (!this.dialogsLoaded)
			{
				this.updateMessageQueue.push({dialogId, data});
				return true;
			}

			if (!this.dialogs.has(dialogId))
			{
				return false;
			}

			if (!this.dialogs.get(dialogId).messages.has(data.message.id.toString()))
			{
				return false;
			}

			let messageId = data.message.id.toString();
			let element = this.dialogs.get(dialogId).messages.get(messageId);
			for (let name in data.message)
			{
				if (!data.message.hasOwnProperty(name))
				{
					continue;
				}
				element.message[name] = data.message[name];
			}

			this.dialogs.get(dialogId).messages.set(messageId, element);

			this._addMessageToDb(dialogId, element);

			return true;
		}

		/**
		 * Delete message for specified dialog
		 *
		 * @param dialogId
		 * @param {number} messageId
		 * @returns {boolean|null}
		 */
		deleteMessage(dialogId, messageId)
		{
			if (!this.database)
			{
				return null;
			}

			dialogId = ChatDialogCache.formatDialogId(dialogId);
			if (!dialogId)
			{
				console.warn("ChatDialogCache.deleteMessage: format dialogId is not correct");
				return null;
			}

			if (!this.dialogsLoaded)
			{
				this.deleteMessageQueue.push({dialogId, messageId});
				return true;
			}

			if (!this.dialogs.has(dialogId))
			{
				return false;
			}

			if (!this.dialogs.get(dialogId).messages.has(messageId.toString()))
			{
				return false;
			}

			this.dialogs.get(dialogId).messages.delete(messageId);

			this._deleteMessageFromDb(dialogId, messageId);

			return true;
		}

		/**
		 * Format data to message format
		 *
		 * @param {{message: {}, filesStore: {}, usersStore: {}, dropFileIfNotExists: false}} params
		 * @returns {{message: {}, files: Map, users: Map}}
		 */
		getMessageFormat(params)
		{
			let {message, files = {}, users = {}, dropFileIfNotExists = false} = params;

			let result = {
				message: {},
				files: new Map(),
				users: new Map()
			};

			result.message = message;

			if (result.message.params.FILE_ID)
			{
				let checkMessageFileList = false;
				result.message.params.FILE_ID.forEach((fileId, key) => {
					if (files[fileId])
					{
						fileId = parseInt(fileId);
						result.files.set(fileId, files[fileId]);
					}
					else if (dropFileIfNotExists)
					{
						checkMessageFileList = true;
						delete result.message.params.FILE_ID[key];
					}
				});
				if (checkMessageFileList)
				{
					let count = 0;
					result.message.params.FILE_ID.forEach(fileId => count++);
					if (count <= 0)
					{
						result.message.params.FILE_ID = [];
						if (!result.message.text)
						{
							result.message.text = '['+BX.message('IM_F_FILE')+']';
						}
					}
				}
			}

			let userId = parseInt(result.message.senderId);
			if (userId && users[userId])
			{
				result.users.set(userId, users[userId]);
			}

			return result;
		}

		/**
		 * Format data to update message format
		 *
		 * @param {{message: {}, filesStore: {}, usersStore: {}, dropFileIfNotExists: false}} params
		 * @returns {{message: {}, files: Map, users: Map}}
		 */
		getUpdateMessageFormat(params)
		{
			let {message, hasFiles = false, hasAttach = false} = params;

			let result = {};

			result.message = message;

			if (typeof params.message.text != 'undefined')
			{
				if (params.message.text.length <= 0)
				{
					if (hasFiles)
					{
						result.message.text = '['+BX.message('IM_F_FILE')+']';
					}
					else if (hasAttach)
					{
						result.message.text = '['+BX.message('IM_F_ATTACH')+']';
					}
					else
					{
						result.message.text = BX.message('IM_M_DELETED');
					}
				}
				else
				{
					result.message.text = params.message.text;
				}
			}

			return result;
		}

		/**
		 * Load all messages
		 * Private method, use: getStore() for all dialogs or getDialog() for specified dialogs
		 * @param {Array|Number|String} dialogId
		 * @returns {Promise.<boolean>}
		 * @private
		 */
		_loadDialogs(dialogId = [])
		{
			if (
				typeof dialogId == "number"
				|| typeof dialogId == "string"
			)
			{
				dialogId = [dialogId];
			}

			let lastDialog = new Promise((resolve, reject) =>
			{
				this.database.table(ChatTables.dialogOptions).then(table =>
				{
					let filter = null;
					if (dialogId.length > 0)
					{
						filter = "ID IN ('"+dialogId.join("', '")+"')";
					}
					table.get(filter, {lastModified: 'DESC'}).then(items =>
					{
						let result = {
							'select': [],
							'delete': [],
						};
						if (items.length <= 0)
						{

							dialogId.forEach(id =>
							{
								this.dialogs.set(id, {
									id: id,
									lastModified: new Date(),
									readList: {},
									unreadList: [],
									userList: {},
									phoneList: {},
									options: {},
									messages: new Map()
								});

								result.select.push(id);
							});

							resolve(result);
							return true;
						}

						let count = 0;
						items.forEach(data =>
						{
							if (count < this.maxDialogStore)
							{
								let options = data.OPTIONS? JSON.parse(data.OPTIONS): {};

								let readList = {};
								if (typeof options.readList !== 'undefined')
								{
									readList = Object.assign({}, options.readList);

									for (let userId in readList)
									{
										if (!readList.hasOwnProperty(userId))
										{
											continue;
										}
										if (readList[userId].date)
										{
											readList[userId].date = new Date(readList[userId].date);
										}
									}

									delete options.readList;
								}

								let userList = {};
								if (typeof options.userList !== 'undefined')
								{
									userList = Object.assign({}, options.userList);

									for (let userId in userList)
									{
										if (!userList.hasOwnProperty(userId))
										{
											continue;
										}

										userList[userId] = ChatDataConverter.getUserDataFormat(userList[userId]);
									}

									delete options.userList;
								}

								let phoneList = {};
								if (typeof options.phoneList !== 'undefined')
								{
									phoneList = Object.assign({}, options.phoneList);
									delete options.phoneList;
								}

								let unreadList = [];
								if (typeof options.unreadList != 'undefined')
								{
									unreadList = options.unreadList.map(item => item);
									delete options.unreadList;
								}

								this.dialogs.set(data.ID, {
									id: data.ID,
									lastModified: new Date(data.LASTMODIFIEDATOM),
									readList: readList,
									unreadList: unreadList,
									userList: userList,
									phoneList: phoneList,
									options: options,
									messages: new Map()
								});
								result.select.push(data.ID)
							}
							else
							{
								result.delete.push(data.ID)
							}
							count++;
						});

						resolve(result);
					}).catch(error => console.error(error));
				});
			});

			return new Promise((resolve, reject) =>
			{
				lastDialog.then(result =>
				{
					this._loadMessages(result.select).then(() => {
						resolve(true);
					}).catch(() => {
						resolve(true);
						console.info('ChatDialogCache._loadMessages: message not found', result.select);
					});

					if (result.delete > 0)
					{
						this.database.table(ChatTables.dialogMessages).then(table =>
						{
							table.delete("DIALOGID IN ('"+result.delete.join("', '")+"')").catch(error => console.error(error));
						});
					}
				});
			});
		}

		/**
		 * Get messages for specified dialog
		 * Private method, use: getDialog(dialogId)
		 *
		 * @param {Array|Number|String} dialogId
		 * @returns {Promise.<boolean>}
		 * @private
		 */
		_loadMessages(dialogId)
		{
			if (
				typeof dialogId == "number"
				|| typeof dialogId == "string"
			)
			{
				dialogId = [dialogId];
			}

			return new Promise((resolve, reject) =>
			{
				this.database.table(ChatTables.dialogMessages).then(table =>
				{
					table.get("DIALOGID IN ('"+dialogId.join("', '")+"')", {id: 'DESC'}).then(items =>
					{
						let deleteMessageList = [];

						if (items.length <= 0)
						{
							this.dialogsLoaded = true;
							reject(false);
							return true;
						}

						let count = {};
						let messagesByDialog = {};

						/**
						 * @var {{DIALOGID: string}} data
						 */
						items.forEach(data =>
						{
							if (!count[data.DIALOGID])
							{
								count[data.DIALOGID] = 0;
							}
							if (count[data.DIALOGID] < this.maxMessageStore)
							{
								if (!messagesByDialog[data.DIALOGID])
								{
									messagesByDialog[data.DIALOGID] = [];
								}

								messagesByDialog[data.DIALOGID].push(data);
							}
							else
							{
								deleteMessageList.push(data.ID)
							}

							count[data.DIALOGID]++;
						});

						for (let dialogId in messagesByDialog)
						{
							if (!messagesByDialog.hasOwnProperty(dialogId))
							{
								return true;
							}

							if (!this.dialogs.has(dialogId))
							{
								this.dialogs.set(dialogId, {
									id: dialogId,
									lastModified: new Date(),
									readList: {},
									unreadList: [],
									userList: {},
									phoneList: {},
									options: {},
									messages: new Map()
								});
							}

							messagesByDialog[dialogId].sort((resultOne, resultTwo) => {

								if (resultTwo.ID > resultOne.ID) {return -1;}
								else if (resultTwo.ID > resultOne.ID) {return 1;}
								else {return 0;}

							}).forEach(data => {

								data.VALUE = JSON.parse(data.VALUE);
								data.VALUE.users = new Map(data.VALUE.users);
								data.VALUE.files = new Map(data.VALUE.files);

								this.dialogs.get(dialogId).messages.set(data.ID, data.VALUE)
							});
						}

						this.dialogsLoaded = true;
						resolve(true);

						if (deleteMessageList.length > 0)
						{
							table.delete("ID IN ('"+deleteMessageList.join("', '")+"')").catch(error => console.error(error));
						}
					}).catch(error => console.error(error));
				});
			});
		}

		/**
		 * Save message to DB
		 * Private method, use: addMessage(dialogId, message)
		 *
		 * @returns {Promise.<Object>}
		 * @private
		 */
		_addMessageToDb(dialogId, data)
		{
			data = Object.assign({}, data);

			let executeTime = new Date();

			let dialogCache = new Promise((resolve, reject) =>
			{
				this.database.table(ChatTables.dialogOptions).then(table =>
				{
					table.replace(
						this._getDialogFields(dialogId)
					).then(items => {
						resolve(true);
					}).catch(error => console.error(error));
				});
			});

			return new Promise((resolve, reject) =>
			{
				dialogCache.then(result =>
				{
					this.database.table(ChatTables.dialogMessages).then(table =>
					{
						let files = [];
						data.files.forEach((file, id) => {
							files.push([id, file]);
						});
						data.files = files;

						let users = [];
						data.users.forEach((user, id) => {
							users.push([id, user]);
						});
						data.users = users;

						table.replace({
							id: data.message.id.toString(),
							dialogId : dialogId.toString(),
							value : data
						}).then(items => {
							resolve(true);
						}).catch(error => console.error(error));
					});
				});
			});
		}

		/**
		 * Delete message from DB
		 * Private method
		 *
		 * @returns {Promise.<Object>}
		 * @private
		 */
		_deleteMessageFromDb(dialogId, messageId)
		{
			let executeTime = new Date();

			let dialogCache = new Promise((resolve, reject) =>
			{
				this.database.table(ChatTables.dialogOptions).then(table =>
				{
					table.replace(
						this._getDialogFields(dialogId)
					).then(items => {
						resolve(true);
					}).catch(error => console.error(error));
				});
			});

			return new Promise((resolve, reject) =>
			{
				dialogCache.then(result =>
				{
					this.database.table(ChatTables.dialogMessages).then(table =>
					{
						let deleteMessageList = [];
						if (typeof messageId != 'object')
						{
							deleteMessageList = [messageId.toString()];
						}
						else
						{
							messageId.forEach(id => deleteMessageList.push(id.toString()));
						}

						table.delete("ID IN ('"+deleteMessageList.join("', '")+"')").then(() => {
							resolve(deleteMessageList);
						}).catch(error => console.error(error));
					});
				});
			});
		}

		/**
		 * Removing messages that have exceeded the limit
		 * Private method
		 *
		 * @param {number} dialogId
		 * @returns {Promise.<Array>}
		 * @private
		 */
		_removeMessagesIfOverflow(dialogId)
		{
			dialogId = ChatDialogCache.formatDialogId(dialogId);
			if (dialogId && this.dialogs.has(dialogId))
			{
				let dialog = this.dialogs.get(dialogId);
				dialog.messages = new Map(
					Array.from(dialog.messages).slice(this.maxMessageStore*-1)
				);
			}

			return new Promise((resolve, reject) =>
			{
				if (!dialogId)
				{
					reject(false);
					return true;
				}

				this.database.table(ChatTables.dialogMessages).then(table =>
				{
					table.get({dialogId}, {id: 'DESC'}).then(items =>
					{
						let deleteMessageList = [];

						if (items.length <= 0)
						{
							resolve(deleteMessageList);
							return true;
						}

						let count = 0;
						items.forEach(data =>
						{
							if (count >= this.maxDialogStore)
							{
								deleteMessageList.push(data.ID)
							}
							count++;
						});

						if (deleteMessageList.length > 0)
						{
							table.delete("ID IN ('"+deleteMessageList.join("', '")+"')").then(() => {
								resolve(deleteMessageList);
							}).catch(error => console.error(error));
						}
						else
						{
							resolve(deleteMessageList);
						}
					}).catch(error => console.error(error));
				});
			});
		}

		/**
		 * Get dialog fields for update
		 *
		 * @param dialogId
		 * @private
		 */
		_getDialogFields(dialogId)
		{
			if (!this.dialogs.has(dialogId))
			{
				this.dialogs.set(dialogId, {
					id: dialogId,
					lastModified: new Date(),
					readList: {},
					unreadList: [],
					userList: {},
					phoneList: {},
					options: {},
					messages: new Map()
				});
			}

			let dialog = this.dialogs.get(dialogId);

			let commitOptions = Object.assign({}, dialog.options);
			commitOptions.readList = dialog.readList;
			commitOptions.unreadList = dialog.unreadList;
			commitOptions.userList = dialog.userList;
			commitOptions.phoneList = dialog.phoneList;

			return {
				id: dialogId.toString(),
				lastModified : (new Date()).getTime(),
				lastModifiedAtom : (new Date()).toString(),
				options : JSON.stringify(commitOptions)
			};
		}

		/**
		 * Modify and check dialogId for DB format
		 *
		 * @param dialogId
		 * @returns {string|null}
		 */
		static formatDialogId(dialogId)
		{
			if (typeof dialogId == 'number' || /^[0-9]+$/.test(dialogId))
			{
				dialogId = dialogId.toString();
			}
			else if (typeof dialogId == 'string' && dialogId.startsWith('chat'))
			{
				dialogId = 'chat'+parseInt(dialogId.substr(4))
			}
			else
			{
				console.warn("ChatDialogCache.formatDialogId: format dialogId is not correct", dialogId);
				dialogId = null
			}

			return dialogId;
		}
	}
	window.ChatDialogCache = ChatDialogCache;
})();