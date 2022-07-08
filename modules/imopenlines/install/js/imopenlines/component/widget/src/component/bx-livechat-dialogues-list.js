import {BitrixVue} from 'ui.vue';
import {Vuex} from 'ui.vue.vuex';
import {RestMethod, WidgetEventType} from '../const';
import {EventEmitter} from 'main.core.events';

BitrixVue.component('bx-livechat-dialogues-list',
{
	data()
	{
		return {
			newChatMode: false,
			sessionList: [],
			isLoading: false,
			pagesLoaded: 0,
			hasMoreItemsToLoad: true,
			itemsPerPage: 25
		};
	},
	computed:
	{
		...Vuex.mapState({
			dialogues: state => state.dialogues
		})
	},
	mounted()
	{
		this.requestDialogList();
	},
	methods:
	{
		requestDialogList(offset = 0)
		{
			this.isLoading = true;

			const requestParams = {'CONFIG_ID': this.$Bitrix.Application.get().getConfigId()};
			if (offset > 0)
			{
				requestParams['OFFSET'] = offset;
			}

			return this.$Bitrix.Application.get().controller.restClient.callMethod(
				RestMethod.widgetDialogList,
				requestParams
			).then(result => {
				if (result.data().length === 0 || result.data().length < this.itemsPerPage)
				{
					this.hasMoreItemsToLoad = false;
				}
				this.pagesLoaded++;
				this.isLoading = false;
				this.sessionList = [...this.sessionList, ...this.prepareSessionList(result.data())];
			}).catch(error => {
				console.warn('error', error);
			});
		},
		prepareSessionList(sessionList)
		{
			return Object.values(sessionList).map(dialog => {
				return {
					chatId: dialog.chatId,
					dialogId: dialog.dialogId,
					name: `Dialog #${dialog.sessionId}`
				};
			});
		},

		openSession(event)
		{
			EventEmitter.emit(WidgetEventType.openSession, event);
		},

		startNewChat(event)
		{
			this.newChatMode = true;
			this.$emit('startNewChat', event);
		},

		isOneScreenRemaining(event)
		{
			return event.target.scrollTop + event.target.clientHeight >= event.target.scrollHeight - event.target.clientHeight;
		},

		onScroll(event)
		{
			if (this.isOneScreenRemaining(event))
			{
				if (this.isLoading || !this.hasMoreItemsToLoad)
				{
					return;
				}

				const offset = this.itemsPerPage * this.pagesLoaded;
				this.requestDialogList(offset);
			}
		}
	},
	// language=Vue
	template: `
	<div class="bx-livechat-help-container" style=" height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
		<div 
			style="margin-top: 25px;overflow-y: scroll;position:relative"
			:style="{marginBottom: newChatMode ? 0 : '10px'}"
			@scroll="onScroll"
		>
			<div
				v-for="session in sessionList"
				:key="session.chatId"
				class="bx-livechat-help-subtitle"
				@click="openSession({event: $event, session: session})"
				style="cursor: pointer; border: solid 1px black;border-radius: 10px;margin: 10px;padding: 5px;background-color: #0ae4ff">
				{{ session.name }}
			</div>
			<div v-if="isLoading" style="margin: 10px">Loading</div>
		</div>
		
		<div v-if="!newChatMode" style="margin-bottom: 10px;">
			<button 
				class="bx-livechat-btn" 
				style="background-color: rgb(23, 163, 234); border-radius: 5px;width: 150px;" 
				@click="startNewChat">
				Start new chat!
			</button>
		</div>
	</div>
	`
	}
);