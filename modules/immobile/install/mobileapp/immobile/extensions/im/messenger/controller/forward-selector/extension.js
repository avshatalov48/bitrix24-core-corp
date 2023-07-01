/**
 * @module im/messenger/controller/forward-selector
 */
jn.define('im/messenger/controller/forward-selector', (require, exports, module) => {

	const { core } = require('im/messenger/core');
	const { SingleSelector } = require('im/messenger/lib/ui/selector');
	const { ChatTitle, ChatAvatar } = require('im/messenger/lib/element');
	const { FileField } = require('layout/ui/fields/file');
	const { DialogSelector } = require('im/messenger/controller/dialog-selector');

	class ForwardSelector
	{
		/**
		 *
		 * @param {object} options
		 * @param {Function} options.onItemSelected
		 * @param {Object} options.search
		 * @param {Object} options.search.entities
		 * @param {string} options.search.mode
		 */
		constructor(options = {})
		{
			this.options = options;
			this.store = core.getStore();
			this.emitter = new JNEventEmitter();
			this.selector = new SingleSelector(
				{
					itemList: this.getListData(),
					searchMode: this.options.search.mode,
					onSearchShow: () => this.searchShow(),
					onSearchClose: () => this.searchClose(),
					onChangeText: text => this.search(text),
					onItemSelected: itemData => this.options.onItemSelected(itemData)
				}
			)

			this.adapter = new BaseSelectorMessengerAdapter(this.selector);
			this.dialogSelector = new DialogSelector({
				view: this.adapter,
				entities: ['user'],
				onRecentResult: () => {}
			});

		}

		open()
		{
			PageManager.openWidget(
				"layout",
				{
					title: "forward selector",
					backdrop: {
						mediumPositionPercent: 75,
						horizontalSwipeAllowed: false,
					},

					onReady: layoutWidget =>
					{
						this.context = layoutWidget;
						layoutWidget.showComponent(this.selector);
					},
					onError: error => reject(error),
				}
			).then(widget => {
				if (this.options.search.mode === 'inline')
				{
					return;
				}
				widget.search.mode = 'bar';
				widget.setRightButtons([
					{
						type: "search",
						id: "search",
						name: 'search',
						callback:()=>{
							widget.search.show();
							this.selector.enableShadow();
							this.searchShow()
						}},
				])
				widget.search.on('cancel', () => {
					this.searchClose();
				})
				widget.search.on('textChanged', text => {
					this.search(text.text);
				})
			});
		}

		close()
		{
			if (this.context)
			{
				this.context.close();
			}
		}

		getListData()
		{
			const chats = this.store.getters['recentModel/getRecentPage'](1, 50);
			chats.shift();
			return chats.map((item, index) => {
				const chatTitle = ChatTitle.createFromDialogId(item.id);
				const chatAvatar = ChatAvatar.createFromDialogId(item.id);

				return {
					data: {
						id: item.id,
						title: chatTitle.getTitle(),
						subtitle: chatTitle.getDescription(),
						avatarUri: chatAvatar.getAvatarUrl(),
						avatarColor: item.color,
					},
					type: 'chats',
					selected: false,
					disable: false,
				};
			});
		}

		getButtons()
		{
			return [

			];
		}

		search(query)
		{
			if (query === '')
			{
				this.selector.showMainContent();
				return;
			}
			this.adapter.onUserTypeText({text: query})
		}

		searchShow()
		{
			this.dialogSelector.open();
			//const adapter = new JNSearchAdapter(this);
		}

		searchClose()
		{
			this.selector.showMainContent();
			this.selector.disableShadow();
		}

		on(eventName, eventHandler)
		{
			this.emitter.on(eventName, eventHandler);

			return this;
		}

		once(eventName, eventHandler)
		{
			this.emitter.once(eventName, eventHandler);

			return this;
		}

		off(eventName, eventHandler)
		{
			this.emitter.off(eventName, eventHandler);

			return this;
		}

		test()
		{


			return  FileField({
				title: 'File Title',
				value: this.state.file,
				config: {
					fileInfo: '123'
				},
			});
		}

	}

	module.exports = { ForwardSelector };
});