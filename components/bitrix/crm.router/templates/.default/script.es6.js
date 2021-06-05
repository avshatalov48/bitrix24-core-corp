import { Reflection, Runtime, Type } from "main.core";

const namespace = Reflection.namespace('BX.Crm.Component');

/**
 * @memberOf BX.Crm.Component
 */
class Router
{
	/**
	 * @public
	 * @param roots
	 * @param rules
	 */
	static bindAnchors(roots: string[], rules: BX.SidePanel.Rule[]): void
	{
		const preparedRules: BX.SidePanel.Rule[] = [];

		rules.forEach(rule => {
			preparedRules.push(this.prependRootsToRuleConditions(roots, rule));
		});

		BX.SidePanel.Instance.bindAnchors({rules: preparedRules});
	}

	/**
	 * @protected
	 * @param roots
	 * @param rule
	 * @return {BX.SidePanel.Rule}
	 */
	static prependRootsToRuleConditions(roots: string[], rule: BX.SidePanel.Rule): BX.SidePanel.Rule
	{
		// Don't change the received object to avoid problems
		const localRule: BX.SidePanel.Rule = Runtime.clone(rule);

		if (!Type.isArrayFilled(localRule.condition))
		{
			return localRule;
		}

		const modifiedConditions = [];
		localRule.condition.forEach((condition: string|RegExp) => {
			if (Type.isRegExp(condition))
			{
				condition = condition.toString();
			}

			roots.forEach(root => {
				modifiedConditions.push(root + condition);
			});
		});

		localRule.condition = modifiedConditions;

		return localRule;
	}
}

namespace.Router = Router;
