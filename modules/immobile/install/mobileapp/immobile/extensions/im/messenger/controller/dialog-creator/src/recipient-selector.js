/**
 * @module im/messenger/controller/dialog-creator/recipient-selector
 */
jn.define('im/messenger/controller/dialog-creator/recipient-selector', (require, exports, module) => {

	const { Loc } = require('loc');
	const { DialogInfo } = require('im/messenger/controller/dialog-creator/dialog-info');
	const { RecipientSelectorView } = require('im/messenger/controller/dialog-creator/recipient-selector/view');
	const { Theme } = require('im/lib/theme');
	const { Analytics } = require('im/messenger/const');
	class RecipientSelector
	{

		static open({ userList = [], dialogDTO, analytics = {} }, parentLayout = null)
		{
			const widget = new RecipientSelector(userList, dialogDTO, parentLayout, analytics);
			widget.show();
		}

		constructor(userList, dialogDTO, parentLayout, analytics)
		{
			this.userList = userList || [];
			this.dialogDTO = dialogDTO;
			this.layout = parentLayout || null;
			this.analytics = analytics || {};

			this.view = new RecipientSelectorView({
				userList: userList,
			});
		}

		show()
		{
			const config = {
				title: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_RECIPIENT_SELECTOR_TITLE'),
				backgroundColor: Theme.isDesignSystemSupported ? Theme.colors.bgContentPrimary : Theme.colors.bgContentTertiary,
				onReady: layoutWidget =>
				{
					this.layout = layoutWidget;
					layoutWidget.showComponent(this.view);
				},
				onError: error => reject(error),
			};

			this.sendAnalyticsStartCreateChat();

			if (this.layout !== null)
			{
				this.layout.openWidget(
					'layout',
					config,
				).then(layoutWidget => {
					this.configureWidget(layoutWidget);
				});

				return;
			}

			PageManager.openWidget(
				'layout',
				config,
			).then(layoutWidget => {
				this.configureWidget(layoutWidget);
			});
		}

		configureWidget(widget)
		{
			widget.setRightButtons([
				{
					id: "next",
					name: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_BUTTON_NEXT'),
					callback: () => {

						this.setRecipientList()

						if(this.dialogDTO.getRecipientList().length === 0)
						{
							return;
						}

						DialogInfo.open(
							{
								dialogDTO: this.dialogDTO,
								userList: this.userList,
							},
							this.layout
						);
					},
					color: Theme.isDesignSystemSupported ? Theme.colors.accentMainLink : Theme.colors.accentMainLinks,
				},
			]);
		}

		setRecipientList()
		{
			this.dialogDTO.setRecipientList(this.view.getSelectedItems());
		}

		sendAnalyticsStartCreateChat()
		{
			try
			{
				const analytics = this.analytics
					.setTool(Analytics.Tool.im)
					.setCategory(Analytics.Category.chat)
					.setEvent(Analytics.Event.clickCreateNew)
					.setType(Analytics.Type.chat)
					.setSection(Analytics.Section.chatTab);

				analytics.send();
			}
			catch (e)
			{
				console.error(`${this.constructor.name}.sendAnalyticsStartCreateChat.catch:`, e);
			}
		}
	}

	module.exports = { RecipientSelector };
});