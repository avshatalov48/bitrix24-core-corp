;(function()
{
	BX.namespace("BX.Call.AI");

	const Analytics = BX.Call.Lib?.Analytics;

	BX.Call.AI.Tabs = {
		callId: null,
		tabsList: [],
		tabButtons: [],
		taskButtons: [],
		meetingButtons: [],
		mensionElements: [],
		userListAppInstance: null,
		audioPlayerInstance: null,
		playbackMarks: [],
		init: function()
		{
			this.tabsList = document.getElementsByClassName('bx-call-component-call-ai__tab-content');
			this.callId = document.getElementsByClassName('bx-call-component-call-ai')[0]?.dataset.callId;
			this.hideAllTabs();
			this.initEvents();
			this.initAudioPlayer();
			this.initUserList();
			this.initPlaybackMarks();
		},
		onTabClick: function(e)
		{
			const { tabId, tabName } = e.target.dataset;
			this.hideAllTabs();

			Analytics.getInstance().copilot.onOpenFollowUpTab({
				callId: this.callId,
				tabName,
			});

			for (let i = 0; i < this.tabButtons.length; i++) {
				this.tabButtons[i].className = this.tabButtons[i].className.replace(' --active-button', '');
			}

			document.getElementById(tabId).style.display = 'flex';
			e.currentTarget.className += ' --active-button';
		},
		hideAllTabs: function()
		{
			for (let i = 0; i < this.tabsList.length; i++) {
				this.tabsList[i].style.display = 'none';
			}
		},
		onCreateTaskClick: function(e)
		{
			Analytics.getInstance().copilot.onFollowUpCreateTaskClick({ callId: this.callId });

			const { userId, description, auditors } = e.target.dataset;
			const taskUrl = `/company/personal/user/${userId}/tasks/task/edit/0/`;

			BX.SidePanel.Instance.open(taskUrl, {
				requestMethod: 'post',
				requestParams: {
					AUDITORS: auditors,
					DESCRIPTION: description,
					RESPONSIBLE_ID: userId,
				},
				cacheable: false,
			});
		},
		onCreateMeetingClick: function(e)
		{
			Analytics.getInstance().copilot.onFollowUpCreateEventClick({ callId: this.callId });

			const { meetingDescription, meetingId, meetingIdType } = e.target.dataset;
			new (window.top.BX || window.BX).Calendar.SliderLoader(0, {
				entryDescription: meetingDescription,
				sliderId: meetingId,
				type: meetingIdType,
			}).show();
		},

		getTimeInSeconds: function(index)
		{
			const startTime = this.playbackMarks[index].innerText.split(/[-—–]/)[0];
			const [seconds, minutes, hours] = startTime.split(':').map(Number).reverse();

			return hours === undefined
				? minutes * 60 + seconds
				: hours * 3600 + minutes * 60 + seconds;
		},

		initEvents: function()
		{
			this.tabButtons = document.getElementsByClassName('bx-call-component-call-ai__tab-header-button');
			for (let i = 0; i < this.tabButtons.length; i++) {
				this.tabButtons[i].addEventListener('click', this.onTabClick.bind(this));
			}
			if (this.tabButtons.length)
			{
				this.tabButtons[0].click();
			}

			this.taskButtons = document.getElementsByClassName('bx-call-component-call-ai__task-button');
			for (let i = 0; i < this.taskButtons.length; i++) {
				this.taskButtons[i].addEventListener('click', this.onCreateTaskClick.bind(this));
			}

			this.meetingButtons = document.getElementsByClassName('bx-call-component-call-ai__meetings-button');
			for (let i = 0; i < this.meetingButtons.length; i++) {
				this.meetingButtons[i].addEventListener('click', this.onCreateMeetingClick.bind(this));
			}

			this.mensionElements = document.getElementsByClassName('bx-call-component-call-ai__user-mention');
			for (let i = 0; i < this.mensionElements.length; i++) {
				this.mensionElements[i].addEventListener('click', this.clickMension.bind(this));
			}

			const disclaimerLink = document.getElementsByClassName('bx-call-component-call-ai__disclaimer-link');
			disclaimerLink[0]?.addEventListener('click', this.clickDisclaimer.bind(this));
		},
		initAudioPlayer: function()
		{
			if (!BX.Vue3)
			{
				return;
			}

			const BitrixVue = BX.Vue3.BitrixVue;
			const audioRecordContainer = document.getElementsByClassName('bx-call-component-call-ai__call-audio-record');

			if (!audioRecordContainer.length)
			{
				return;
			}

			const { audioSrc } = audioRecordContainer[0].dataset;
			const callId = this.callId;

			if (!audioSrc)
			{
				return;
			}

			this.audioPlayerInstance = BitrixVue.createApp({
				components: {
					AudioPlayer: BX.Call.Component.Elements.AudioPlayer
				},
				data() {
					return {
						audioSrc,
						analyticsCallback: () => {
							Analytics.getInstance().copilot.onAIPlayRecord({ callId });
						},
					};
				},
				template: `
					<AudioPlayer :src="audioSrc" :analyticsCallback="analyticsCallback" ref="AudioPlayerRef"/>
				`,
			}).mount('.bx-call-component-call-ai__call-audio-record');
		},
		initUserList: function()
		{
			if (!BX.Vue3)
			{
				return;
			}

			const BitrixVue = BX.Vue3.BitrixVue;
			const userListContainer = document.getElementsByClassName('bx-call-component-call-ai-page__title-users-container');
			const { callId } = userListContainer[0].dataset;

			if (!callId)
			{
				return;
			}

			BitrixVue.createApp({
				components: {
					UserList: BX.Call.Component.UserList
				},
				data() {
					return {
						isLoadingUsers: true,
						callId,
						usersData: [],
					};
				},
				created()
				{
					BX.ajax.runComponentAction('bitrix:call.ai', 'getUsers', {
						mode: 'ajax',
						data: { callId }
					}).then(response => {
						this.isLoadingUsers = false;
						this.usersData = Object.values(response.data.users);
					}).catch(error => {
						this.isLoadingUsers = false;
					});
				},
				template: `
					<UserList :loading="isLoadingUsers" :usersData="usersData" />
				`,
			}).mount('.bx-call-component-call-ai-page__title-users-container');
		},
		initPlaybackMarks: function()
		{
			if (!this.audioPlayerInstance)
			{
				return;
			}

			this.playbackMarks = document.getElementsByClassName('bx-call-component-call-ai-decryption-block__time');
			for (let i = 0; i < this.playbackMarks.length; i++)
			{
				this.playbackMarks[i].addEventListener('click', () => {
					Analytics.getInstance().copilot.onAIRecordTimeCodeClick({ callId: this.callId });
					this.audioPlayerInstance.$refs.AudioPlayerRef.choosePlaybackTime(this.getTimeInSeconds(i));
				});
			}
		},
		clickMension: function(e)
		{
			const { userId } = e.target.dataset;

			if (!userId)
			{
				return;
			}

			BX.Messenger.Public.openChat(userId);
		},
		clickDisclaimer: function()
		{
			const infoHelper = top.BX.UI.InfoHelper;
			const ARTICLE_CODE = '20412666';

			if (!infoHelper)
			{
				return;
			}

			if (infoHelper.isOpen())
			{
				infoHelper.close()
			}

			infoHelper.show(ARTICLE_CODE);
		},
	};

	document.addEventListener('DOMContentLoaded', () => {
		BX.Call.AI.Tabs.init();
	});
})();
