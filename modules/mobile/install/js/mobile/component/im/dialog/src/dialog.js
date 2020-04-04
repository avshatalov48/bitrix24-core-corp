import "./dialog.css";

/* region 00. Startup operations */
const GetObjectValues = function(source)
{
	const destination = [];
	for (let value in source)
	{
		if (source.hasOwnProperty(value))
		{
			destination.push(source[value]);
		}
	}
	return destination;
};
/* endregion 00. Startup operations */

/* region 01. Constants */

import {DeviceType, DeviceOrientation} from "im.const";
import {ApplicationModel, MessagesModel, DialoguesModel, UsersModel, FilesModel} from 'im.model';
import {ApplicationController} from 'im.controller';

const RestMethod = Object.freeze({
	pullServerTime: 'server.time', // TODO: method is not implemented
	pullConfigGet: 'pull.config.get', // TODO: method is not implemented

	imMessageAdd: 'im.message.add',
	imMessageUpdate: 'im.message.update', // TODO: method is not implemented
	imMessageDelete: 'im.message.delete', // TODO: method is  not implemented
	imMessageLike: 'im.message.like', // TODO: method is  not implemented
	imChatGet: 'im.chat.get',  // TODO: method is not implemented
	imChatSendTyping: 'im.chat.sendTyping',  // TODO: method is not implemented
	imDialogMessagesGet: 'im.dialog.messages.get',  // TODO: method is not implemented
	imDialogMessagesUnread: 'im.dialog.messages.unread',  // TODO: method is not implemented
	imDialogRead: 'im.dialog.read',  // TODO: method is not implemented

	diskFolderGet: 'im.disk.folder.get',  // TODO: method is not implemented
	diskFileUpload: 'disk.folder.uploadfile',  // TODO: method is not implemented
	diskFileCommit: 'im.disk.file.commit',  // TODO: method is not implemented
});
const RestMethodCheck = GetObjectValues(RestMethod);

/* endregion 01. Constants */

/* region 03. Dialog interface */
class MessengerDialog
{
	constructor(params = {})
	{
		this.restClient = null;
		this.pullClient = null;

		this.offline = false;

		this.dateFormat = null;

		this.messagesQueue = [];

		this.filesQueue = [];
		this.filesQueueIndex = 0;

		this.messageLastReadId = null;
		this.messageReadQueue = [];

		this.defaultMessageLimit = 20;
		this.requestMessageLimit = this.defaultMessageLimit;

		this.rootNode = document.body;

		this.template = null;

		/* TODO rewrite to IndexedDB */
		let serverVariables = BX.Messenger.LocalStorage.get(this.getSiteId(), 0, 'serverVariables', false);
		if (serverVariables)
		{
			this.addLocalize(serverVariables);
		}

		this.timer = new BX.Messenger.Timer();

		this.controller = new ApplicationController();

		let applicationVariables = {
			common: {
				siteId: this.getSiteId(),
				languageId: this.getLanguageId(),
			},
			device: {
				type: BX.Messenger.Utils.device.isMobile()? DeviceType.mobile: DeviceType.desktop,
				orientation: BX.Messenger.Utils.device.getOrientation(),
			},
			dialog: {
				messageLimit: this.defaultMessageLimit
			}
		};

		new BX.VuexBuilder()
			.addModel(ApplicationModel.create().setVariables(applicationVariables))
			.addModel(MessagesModel.create())
			.addModel(DialoguesModel.create().setVariables({host: this.getHost()}).useDatabase(false))
			.addModel(UsersModel.create().setVariables({host: this.getHost(), defaultName: BX.message('IM_MESSENGER_MESSAGE_USER_ANONYM')}).useDatabase(false))
			.addModel(FilesModel.create().setVariables({host: this.getHost()}).useDatabase(false))
			.setDatabaseConfig({
				type: BX.VuexBuilder.DatabaseType.indexedDb,
				siteId: this.getSiteId(),
			})
		.build(result => {

			this.store = result.store;
			this.storeCollector = result.builder;

			this.restClient = BX.rest;
			this.pullClient = BX.PULL;


			this.controller.setPrepareFilesBeforeSaveFunction(this.prepareFileData);

			this.requestData(this.getDialogId());
			this.attachTemplate();
		});
	}

	addLocalize(phrases)
	{
		if (typeof phrases !== "object")
		{
			return false;
		}

		for (let name in phrases)
		{
			if (phrases.hasOwnProperty(name))
			{
				BX.message[name] = phrases[name];
			}
		}

		return true;
	}

	getHost()
	{
		return location.protocol+'//'+location.host; // TODO read variables
	}

	getSiteId()
	{
		return 'default'; // TODO read variables
	}

	getDialogId()
	{
		return 'chat56'; // TODO read variables
	}

	getLanguageId()
	{
		return 'ru'; // TODO read variables
	}

	requestData()
	{

	}

	executeRestAnswer(type, result)
	{

	}

	prepareFileData(files)
	{
		if (true) // TODO change to right version if mobile implement protocol bxfiles://
		{
			return files;
		}

		return files.map(file =>
		{
			if (file.urlPreview)
			{
				file.urlPreview = file.urlPreview.replace('http://', 'bx://').replace('https://', 'bx://');
			}
			if (file.urlShow)
			{
				file.urlShow = file.urlShow.replace('http://', 'bx://').replace('https://', 'bx://');
			}
			if (file.urlDownload)
			{
				file.urlDownload = file.urlDownload.replace('http://', 'bx://').replace('https://', 'bx://');
			}

			return file;
		});
	}

	attachTemplate()
	{
		this.rootNode.innerHTML = '';

		const widgetContext = this;
		const restClient = this.restClient;
		const pullClient = this.pullClient;

		this.template = BX.Vue.create({
			el: this.rootNode.firstChild,
			store: this.store,
			template: '<bx-mobile-dialog/>',
			beforeCreate()
			{
				this.$bitrixWidget= widgetContext;
				this.$bitrixRestClient = restClient;
				this.$bitrixMessages = widgetContext.localize;
			}
		});

		return true;
	}

}

window.MessengerDialog = new MessengerDialog;