import { Reflection } from "main.core";

import { Factory } from "./factory";
import { TimestampConverter } from "./timestamp-converter";
import { TimezoneOffset } from "./dictionary/timezone-offset";
import { Format } from "./dictionary/format";

const namespace = Reflection.namespace('BX.Crm.DateTime');

namespace.Factory = Factory;
namespace.TimestampConverter = TimestampConverter;
namespace.Dictionary = {
	TimezoneOffset,
	Format,
};

export {
	Factory,
	TimestampConverter,
	TimezoneOffset,
	Format,
};
