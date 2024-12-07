/**
 * @module im/messenger/db/model-writer/vuex
 */
jn.define('im/messenger/db/model-writer/vuex', (require, exports, module) => {
	const { RecentWriter } = require('im/messenger/db/model-writer/vuex/recent');
	const { DialogWriter } = require('im/messenger/db/model-writer/vuex/dialog');
	const { UserWriter } = require('im/messenger/db/model-writer/vuex/user');
	const { FileWriter } = require('im/messenger/db/model-writer/vuex/file');
	const { ReactionWriter } = require('im/messenger/db/model-writer/vuex/reaction');
	const { MessageWriter } = require('im/messenger/db/model-writer/vuex/message');
	const { TempMessageWriter } = require('im/messenger/db/model-writer/vuex/temp-message');
	const { QueueWriter } = require('im/messenger/db/model-writer/vuex/queue');
	const { PinMessageWriter } = require('im/messenger/db/model-writer/vuex/pin-message');
	const { ApplicationWriter } = require('im/messenger/db/model-writer/vuex/application');
	const { CopilotWriter } = require('im/messenger/db/model-writer/vuex/copilot');
	const { SidebarFileWriter } = require('im/messenger/db/model-writer/vuex/sidebar/file');

	class VuexModelWriter
	{
		/**
		 * @param options
		 * @param {MessengerCoreStoreManager} options.storeManager
		 * @param {{
		 *  option: OptionRepository,
		 *  recent: RecentRepository,
		 *  dialog: DialogRepository,
		 *  file: FileRepository,
		 *  user: UserRepository,
		 *  message: MessageRepository,
		 *  tempMessage: TempMessageRepository,
		 *  reaction: ReactionRepository,
		 *  sidebarFile: SidebarFileRepository
		 * }} options.repository
		 */
		constructor(options)
		{
			this.storeManager = options.storeManager;
			this.repository = options.repository;

			this.initWriters();
		}

		initWriters()
		{
			const writerOptions = {
				storeManager: this.storeManager,
				repository: this.repository,
			};

			this.recentWriter = new RecentWriter(writerOptions);
			this.dialogWriter = new DialogWriter(writerOptions);
			this.userWriter = new UserWriter(writerOptions);
			this.fileWriter = new FileWriter(writerOptions);
			this.reactionWriter = new ReactionWriter(writerOptions);
			this.messageWriter = new MessageWriter(writerOptions);
			this.tempMessageWriter = new TempMessageWriter(writerOptions);
			this.queueWriter = new QueueWriter(writerOptions);
			this.pinMessageWriter = new PinMessageWriter(writerOptions);
			this.applicationWriter = new ApplicationWriter(writerOptions);
			this.copilotWriter = new CopilotWriter(writerOptions);
			this.sidebarFileWriter = new SidebarFileWriter(writerOptions);
		}
	}

	module.exports = {
		VuexModelWriter,
	};
});
