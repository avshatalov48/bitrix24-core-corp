import {MessengerStoreActions} from "../../core/types/store";

export interface MutationPayload<TData extends PayloadData, TActions extends string = ''> {
    actionName: TActions,
    actionUuid?: string,
    data: TData,
}

export interface PayloadData {
}

export interface MessengerStore<TMessengerModel extends MessengerModel> {
    commit<TAction, TData>(mutationName: string, payload: MutationPayload<TData, TAction>);
    dispatch(actionName: string, params?: any) : Promise<any>;
    getters: Record<string, function>;
    rootGetters: any
    rootState: object;
    state: ReturnType<TMessengerModel['state']>;
}

export interface MessengerModel<TCollection = {}>
{
    namespaced: boolean,
    state: () => TCollection,
    getters: Record<string, ((state: TCollection) => function) | ((state: TCollection, getters: Record<string, function>) => function)>
    actions: Record<string, (store: MessengerStore<MessengerModel<TCollection>>, payload: any) => void>
    mutations: Record<string, (state: TCollection, payload: MutationPayload) => void>
}