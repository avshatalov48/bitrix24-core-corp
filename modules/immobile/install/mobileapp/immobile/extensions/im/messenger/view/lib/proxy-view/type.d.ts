type eventHandler = () => any;
type eventName = string;
type EventHandlerCollection = Record<eventName, Array<eventHandler>>;
type EventWrappedHandlerCollection = Record<eventName, eventHandler>;
