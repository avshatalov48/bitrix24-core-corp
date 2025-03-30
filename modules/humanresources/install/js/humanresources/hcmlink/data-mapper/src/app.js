import { Page } from './components/page';
import { Loader } from './components/loader';
import { StateScreen } from './components/state-screen';
import { EventList, Status } from './types';
import { Api } from 'humanresources.hcmlink.api';
import { Toolbar } from './components/toolbar';

import { Type } from 'main.core';
import { EventEmitter } from 'main.core.events';

import './styles/mapper.css';

export const HumanresourcesHcmlinkMapper = {
	name: 'HumanresourcesHcmlinkMapper',

	props: {
		companyId: {
			required: true,
			type: Number,
		},
		mode: {
			required: true,
			type: String,
		},
		userIdCollection: {
			required: true,
			type: Array,
		},
		toolbarContainer: {
			required: true,
			type: String,
		},
		api: {
			required: true,
			type: Api,
		},
	},

	components: {
		Page,
		Loader,
		StateScreen,
		Toolbar,
	},

	data(): Object
	{
		return {
			loading: false,
			isHideInfoAlert: true,
			pageCount: 0,
			mappingEntityCollection: [],
			userMappingSet: {},
			isJobResolved: false,
			isDone: false,
			countAllPersonsForMap: 0,
			countMappedPersons: 0,
			countUnmappedPersons: 0,
			isReadyToolbar: false,
			mappedUserIds: [],
			searchName: null,
			searchActive: false,
			lastJobFinishedAt: null,
		};
	},

	created(): void
	{
		this.jobId = null;
		this.updateJobStatusInterval = null;
		this.forceSyncPointer = this.forceSync.bind(this); // for correct sub/unsub
		this.pullUnsubscrubeCallback = null; // BX.PULL unsubscribe function

		this.createUpdateEmployeeListJob();

		EventEmitter.subscribe(EventList.HR_DATA_MAPPER_FORCE_SYNC, this.forceSyncPointer);
	},

	computed: {
		isJobPending(): boolean {
			return !this.isJobResolved && !this.isDone;
		},
		isMappingReady(): boolean {
			return this.isJobResolved && !this.isDone;
		},
		isMappingDone(): boolean {
			return this.isJobResolved && this.isDone;
		},
		isSearchEmpty(): boolean {
			return this.mappingEntityCollection.length === 0 && Boolean(this.searchName);
		},
		completedStatus(): string
		{
			return this.mode === 'direct' ? Status.done : Status.salaryDone;
		},
	},

	watch: {
		pageCount(): void
		{
			this.loadConfig();
		},
		isMappingReady(value): void
		{
			if (value)
			{
				this.footerDisplay(true);
			}
		},
		isMappingDone(value): void
		{
			if (value)
			{
				this.footerDisplay(false);
			}
		},
		isSearchEmpty(value): void
		{
			this.footerDisplay(!value);
		},
	},

	mounted(): void
	{
		this.countAllPersonsForMap = this.userIdCollection.length;
		this.$nextTick(() => {
			this.isReadyToolbar = true;
		});
	},

	unmounted(): void
	{
		this.clearJobListeners();
		EventEmitter.unsubscribe(EventList.HR_DATA_MAPPER_FORCE_SYNC, this.forceSyncPointer);
	},

	methods: {
		// <editor-fold desc="External functions. Called by Mapper">
		prepareNextUsers(): void
		{
			this.$Bitrix.eventEmitter.emit(EventList.HR_DATA_MAPPER_DATA_WAS_SAVED);
			this.searchName = null;
			this.userMappingSet = {};
			this.pageCount++;
		},
		getUserMappingSet(): Set
		{
			return this.userMappingSet;
		},
		// </editor-fold>
		onCreateLink(options): void
		{
			this.userMappingSet[options.userId] = options;
		},
		onRemoveLink(options): void
		{
			if (this.userMappingSet[options.userId] !== undefined)
			{
				delete this.userMappingSet[options.userId];
			}
		},
		onCloseAlert(): void
		{
			this.api.closeInfoAlert();
		},
		onCompleteMapping(): void
		{
			this.api.createCompleteMappingEmployeeListJob({
				companyId: this.companyId,
			});
		},
		onSearchPersonName(query): void
		{
			if (!this.isDone && query !== this.searchName)
			{
				this.searchName = query || null;
				this.userMappingSet = {};
				this.searchActive = Boolean(query);
				this.loadConfig();
			}
		},
		/**
		 * On abort sync from state screen
		 *
		 * @returns {Promise<void>}
		 */
		async onAbortSync(): Promise<void>
		{
			this.loading = true;
			const jobData = await this.api.getLastJob({ companyId: this.companyId });
			await this.syncJobDone(jobData);
		},
		async loadConfig(): Promise<void>
		{
			this.loading = true;

			const {
				items,
				countMappedPersons,
				countUnmappedPersons,
				isHideInfoAlert,
				mappedUserIds,
			} = await this.api.loadMapperConfig({
				companyId: this.companyId,
				userIds: this.userIdCollection,
				mode: this.mode,
				searchName: this.searchName,
			});

			this.isHideInfoAlert = isHideInfoAlert;
			this.countUnmappedPersons = countUnmappedPersons;
			this.countMappedPersons = countMappedPersons;
			this.mappingEntityCollection = Type.isArray(items) ? items : [];
			this.mappedUserIds = mappedUserIds;

			this.isDone = this.mappingEntityCollection.length === 0 && !this.searchName;

			this.loading = false;
		},
		async forceSync(): void
		{
			this.$Bitrix.eventEmitter.emit(EventList.HR_DATA_MAPPER_CLEAR_SEARCH_INPUT);
			this.searchName = null;
			this.footerDisplay(false);
			this.isJobResolved = false;
			await this.api.cancelJob({ jobId: this.jobId, companyId: this.companyId });
			this.createUpdateEmployeeListJob(true);
		},
		async syncJobDone(jobData: Object)
		{
			this.clearJobListeners();
			this.lastJobFinishedAt = jobData.finishedAt ? new Date(jobData.finishedAt) : null;
			await this.loadConfig();
			this.isJobResolved = true;
		},
		async createUpdateEmployeeListJob(isForced = false): Promise<void>
		{
			this.footerDisplay(false);
			this.isJobResolved = false;

			const data = await this.api.createUpdateEmployeeListJob({
				companyId: this.companyId,
				isForced,
			});
			this.jobId = data.jobId;

			if (data.status === 3)
			{
				// if we got a job with status 'DONE', load data immediately
				await this.syncJobDone(data);

				return;
			}
			this.clearJobListeners();

			this.updateJobStatusInterval = setInterval(this.updateJobStatus.bind(this), 30000);

			if (BX.PULL)
			{
				this.pullUnsubscrubeCallback = BX.PULL.subscribe({
					type: BX.PullClient.SubscriptionType.Server,
					moduleId: 'humanresources',
					command: 'external_employee_list_updated',
					callback: async function(params: { jobId: number, status: string }): Promise<void> {
						if ((params.jobId === this.jobId))
						{
							await this.processJobStatus(params);
						}
					}.bind(this),
				});
				BX.PULL.extendWatch('humanresources_person_mapping');
			}
		},
		async updateJobStatus(): Promise<void>
		{
			const { params } = await this.api.getJobStatus({ jobId: this.jobId });

			await this.processJobStatus(params);
		},
		footerDisplay(show: boolean): void
		{
			EventEmitter.emit(EventList.HR_DATA_MAPPER_FOOTER_DISPLAY, show);
		},
		async processJobStatus(params)
		{
			if (params.status === 3)
			{
				// load data if job is complete
				await this.syncJobDone(params);
			}
			else if (params.status === 5 || params.status === 4)
			{
				// make a new job if last job was canceled or expired
				this.clearJobListeners();
				this.jobId = null;
				this.createUpdateEmployeeListJob();
			}
		},
		clearJobListeners()
		{
			clearInterval(this.updateJobStatusInterval);
			if (this.pullUnsubscrubeCallback)
			{
				this.pullUnsubscrubeCallback();
			}
			this.pullUnsubscrubeCallback = null;
		},
	},

	template: `
		<Teleport v-if="isReadyToolbar" :to="toolbarContainer">
			<Toolbar
				:isMappingReady="isMappingReady"
				:countAllPersonsForMap=countAllPersonsForMap
				:countMappedPersons=countMappedPersons
				:countUnmappedPersons=countUnmappedPersons
				:lastJobFinishedAt=lastJobFinishedAt
				:mode=mode
			/>
		</Teleport>
		<template v-if="isJobPending">
			<StateScreen
				:status="loading ? 'loading' : 'pending'"
				:mode=mode
				@abortSync="onAbortSync"
			></StateScreen>
		</template>
		<template v-if="isMappingReady">
			<Loader v-if="loading"></Loader>
			<Page
				:dataLoading=loading
				:collection=mappingEntityCollection
				:mappedUserIds=mappedUserIds
				:searchActive=searchActive
				:config="{ mode, isHideInfoAlert, companyId }"
				@createLink="onCreateLink"
				@removeLink="onRemoveLink"
				@closeAlert="onCloseAlert"
				@search="onSearchPersonName"
			></Page>
		</template>
		<template v-if="isMappingDone">
			<StateScreen
				:status=completedStatus
				:mode=mode
				@completeMapping='onCompleteMapping'
			></StateScreen>
		</template>
	`,
};
