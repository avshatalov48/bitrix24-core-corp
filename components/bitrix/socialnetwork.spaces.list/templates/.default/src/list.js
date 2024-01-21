import { Store } from './store/store';
import { LinkManager } from './util/link-manager';
import { BitrixVue, VueCreateAppResult } from 'ui.vue3';
import { BaseComponent } from './components/base-component';
import { RecentService } from './api/load/recent-service';
import { PullRequests } from './pull/pull-requests';
import { EventTypes } from './const/event';
import { Modes } from './const/mode';
import { RecentSearchService } from './api/load/recent-search-service';
import { BaseEvent } from 'main.core.events';
import { PULL as Pull } from 'pull.client';

import type { SpaceModel } from './model/space-model';
import type { InvitationModel } from './model/invitation-model';
import { Client } from './api/client';

type ListOptions = {
	recentSpaceIds: number[],
	spaces: SpaceModel[],
	invitationSpaceIds: number[],
	invitations: InvitationModel[],
	avatarColors: string[],
	selectedSpaceId: number,
	pathToUserSpace: string,
	pathToGroupSpace: string,
	filterMode: string,
	spacesListMode: string,
	canCreateGroup: boolean,
	currentUserId: number,
};

export class List
{
	#initialOptions:ListOptions;
	#target: HTMLElement;
	#application: VueCreateAppResult;

	constructor(options: ListOptions)
	{
		this.#initialOptions = options;
	}

	create(target: HTMLElement)
	{
		this.#target = target;
		this.#initLinkManager();
		this.#initServices();
		this.#createApplication();
	}

	#initLinkManager()
	{
		LinkManager.groupPath = this.#initialOptions.pathToGroupSpace;
		LinkManager.commonSpacePath = this.#initialOptions.pathToUserSpace;
	}

	#initServices()
	{
		RecentService.getInstance().setSelectedSpaceId(parseInt(this.#initialOptions.selectedSpaceId, 10));
	}

	#createApplication()
	{
		const recentSpaceIds = this.#initialOptions.recentSpaceIds;
		const invitationSpaceIds = this.#initialOptions.invitationSpaceIds;
		const invitations = this.#initialOptions.invitations;
		const spaces = this.#initialOptions.spaces;
		const avatarColors = this.#initialOptions.avatarColors;
		const selectedFilterModeType = this.#initialOptions.filterMode;
		const spacesListState = this.#initialOptions.spacesListMode;
		const canCreateGroup = this.#initialOptions.canCreateGroup;
		const currentUserId = this.#initialOptions.currentUserId;

		this.#application = BitrixVue.createApp(
			{
				name: 'SpacesList',
				props: {
					initialSpaces: Array,
					selectedFilterModeType: String,
					spacesListState: String,
					canCreateGroup: Boolean,
					recentSpaceIds: Array,
					invitations: Array,
					invitationSpaceIds: Array,
				},
				components: {
					BaseComponent,
				},
				methods: {
					castArrayValuesToInt(array: Array): Array
					{
						return array.map((value) => parseInt(value, 10));
					},
					subscribeToPull()
					{
						const pullRequests = new PullRequests();
						pullRequests.subscribe(EventTypes.pinChanged, this.pinChangedHandler);
						pullRequests.subscribe(EventTypes.updateCounters, this.updateCountersHandler);
						pullRequests.subscribe(EventTypes.changeSpace, this.updateSpaceData);
						pullRequests.subscribe(EventTypes.changeUserRole, this.updateSpaceUserData);
						pullRequests.subscribe(EventTypes.changeSubscription, this.updateSpaceUserData);
						Pull.subscribe(pullRequests);
					},
					pinChangedHandler(event)
					{
						this.pinSpace(event.getData().spaceId, event.getData().isPinned);
					},
					pinSpace(spaceId, isPinned)
					{
						this.$store.dispatch('pinSpace', { spaceId, isPinned });
					},
					updateCountersHandler(event: BaseEvent)
					{
						if (event.data.userId && parseInt(event.data.userId, 10) === currentUserId)
						{
							this.$store.dispatch('updateCounters', event.data);
						}
					},
					async updateSpaceData(event: BaseEvent)
					{
						if (event.data.spaceId)
						{
							const requestData = await Client.loadSpaceData(event.data.spaceId);
							this.$store.dispatch('updateSpaceData', requestData);
						}
					},
					async updateSpaceUserData(event: BaseEvent)
					{
						if (event.data.userId && parseInt(event.data.userId, 10) === currentUserId)
						{
							const requestData = await Client.loadSpaceData(event.data.spaceId);
							if (requestData.space)
							{
								this.$bitrix.eventEmitter.emit(`onSpaceUpdate_${requestData.space.id}`, requestData.space);
							}
							this.$store.dispatch('updateSpaceData', requestData);
						}
					},
				},
				beforeCreate(): void
				{
					this.$bitrix.Application.set(this);
				},
				created()
				{
					this.$store.dispatch('setSpaces', this.initialSpaces);
					this.$store.dispatch('setRecentSpaceIds', this.castArrayValuesToInt(this.recentSpaceIds));
					this.$store.dispatch('setInvitationSpaceIds', this.castArrayValuesToInt(this.invitationSpaceIds));
					this.$store.dispatch('setInvitations', this.invitations);
					this.$store.dispatch('setAvatarColors', avatarColors);

					this.$store.dispatch('setSelectedFilterModeType', this.selectedFilterModeType);
					this.$store.dispatch('setSpacesListState', this.spacesListState);

					this.$store.dispatch('setCanCreateGroup', this.canCreateGroup);
					this.subscribeToPull();
				},
				mounted()
				{
					this.$bitrix.eventEmitter.emit(EventTypes.showLoader, Modes.recentSearch);
					RecentSearchService.getInstance().loadSpaces().then((result) => {
						this.$store.dispatch('addSpacesToView', { mode: Modes.recentSearch, spaces: result });
						this.$bitrix.eventEmitter.emit(EventTypes.hideLoader, Modes.recentSearch);
					}).catch(() => {});
				},
				template: `
					<BaseComponent/>
				`,
			},
			{
				initialSpaces: spaces,
				selectedFilterModeType,
				spacesListState,
				canCreateGroup,
				recentSpaceIds,
				invitationSpaceIds,
				invitations,
			},
		);
		this.#application.use(Store);
		this.#application.mount(this.#target);
	}
}
