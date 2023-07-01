export default class EntityCounterType
{
	// type ID
	static UNDEFINED = 0;
	static IDLE = 1;
	static PENDING = 2;
	static OVERDUE = 4;
	static INCOMING_CHANNEL = 8;
	static READY_TODO = 16;

	static CURRENT = 20 // READY_TODO|OVERDUE
	static ALL_DEADLINE_BASED = 7;  //IDLE|PENDING|OVERDUE


	static ALL = 31;  //IDLE|PENDING|OVERDUE|INCOMINGCHANNEL|READY_TODO

	// type name
	static IDLE_NAME  = 'IDLE';
	static PENDING_NAME = 'PENDING';
	static OVERDUE_NAME = 'OVERDUE';
	static READY_TODO_NAME = 'READYTODO';
	static CURRENT_NAME = 'CURRENT';
	static INCOMING_CHANNEL_NAME = 'INCOMINGCHANNEL';
	static ALL_DEADLINE_BASED_NAME = 'ALLDEADLINEBASED';
	static ALL_NAME = 'ALL';
}
