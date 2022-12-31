import { ByLastActivityTime } from "./by-last-activity-time";
import { ById } from "./by-id";

const byLastActivityTimeTestSuite = new ByLastActivityTime();
byLastActivityTimeTestSuite.run();

const byIdTestSuite = new ById();
byIdTestSuite.run();
