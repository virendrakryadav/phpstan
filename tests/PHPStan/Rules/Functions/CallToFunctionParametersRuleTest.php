<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\RuleLevelHelper;

class CallToFunctionParametersRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		$broker = $this->createBroker();
		return new CallToFunctionParametersRule(
			$broker,
			new FunctionCallParametersCheck(new RuleLevelHelper($broker, true, false, true), true, true)
		);
	}

	public function testCallToFunctionWithoutParameters(): void
	{
		require_once __DIR__ . '/data/existing-function-definition.php';
		$this->analyse([__DIR__ . '/data/existing-function.php'], []);
	}

	public function testCallToFunctionWithIncorrectParameters(): void
	{
		require_once __DIR__ . '/data/incorrect-call-to-function-definition.php';
		$this->analyse([__DIR__ . '/data/incorrect-call-to-function.php'], [
			[
				'Function IncorrectCallToFunction\foo invoked with 1 parameter, 2 required.',
				5,
			],
			[
				'Function IncorrectCallToFunction\foo invoked with 3 parameters, 2 required.',
				7,
			],
			[
				'Parameter #1 $foo of function IncorrectCallToFunction\bar expects int, string given.',
				14,
			],
		]);
	}

	public function testCallToFunctionWithOptionalParameters(): void
	{
		require_once __DIR__ . '/data/call-to-function-with-optional-parameters-definition.php';
		$this->analyse([__DIR__ . '/data/call-to-function-with-optional-parameters.php'], [
			[
				'Function CallToFunctionWithOptionalParameters\foo invoked with 3 parameters, 1-2 required.',
				9,
			],
			[
				'Parameter #1 $object of function get_class expects object, null given.',
				12,
			],
			[
				'Parameter #1 $object of function get_class expects object, object|null given.',
				16,
			],
		]);
	}

	public function testCallToFunctionWithDynamicParameters(): void
	{
		require_once __DIR__ . '/data/function-with-variadic-parameters-definition.php';
		$this->analyse([__DIR__ . '/data/function-with-variadic-parameters.php'], [
			[
				'Function FunctionWithVariadicParameters\foo invoked with 0 parameters, at least 1 required.',
				6,
			],
			[
				'Parameter #3 ...$foo of function FunctionWithVariadicParameters\foo expects int, null given.',
				12,
			],
			[
				'Function FunctionWithVariadicParameters\bar invoked with 0 parameters, at least 1 required.',
				14,
			],
		]);
	}

	public function testCallToFunctionWithNullableDynamicParameters(): void
	{
		require_once __DIR__ . '/data/function-with-nullable-variadic-parameters-definition.php';
		$this->analyse([__DIR__ . '/data/function-with-nullable-variadic-parameters.php'], [
			[
				'Function FunctionWithNullableVariadicParameters\foo invoked with 0 parameters, at least 1 required.',
				6,
			],
		]);
	}

	public function testCallToFunctionWithDynamicIterableParameters(): void
	{
		require_once __DIR__ . '/data/function-with-variadic-parameters-definition.php';
		$this->analyse([__DIR__ . '/data/function-with-variadic-parameters-7.1.php'], [
			[
				'Parameter #2 ...$foo of function FunctionWithVariadicParameters\foo expects array<int, int>, iterable<string> given.',
				16,
			],
		]);
	}

	public function testCallToArrayUnique(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-array-unique.php'], [
			[
				'Function array_unique invoked with 3 parameters, 1-2 required.',
				3,
			],
		]);
	}

	public function testCallToArrayMapVariadic(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-array-map-unique.php'], []);
	}

	public function testCallToWeirdFunctions(): void
	{
		$this->analyse([__DIR__ . '/data/call-to-weird-functions.php'], [
			[
				'Function implode invoked with 0 parameters, 1-2 required.',
				3,
			],
			[
				'Function implode invoked with 3 parameters, 1-2 required.',
				6,
			],
			[
				'Function strtok invoked with 0 parameters, 1-2 required.',
				8,
			],
			[
				'Function strtok invoked with 3 parameters, 1-2 required.',
				11,
			],
			[
				'Function fputcsv invoked with 1 parameter, 2-5 required.',
				13,
			],
			[
				'Function imagepng invoked with 0 parameters, 1-4 required.',
				16,
			],
			[
				'Function imagepng invoked with 5 parameters, 1-4 required.',
				19,
			],
			[
				'Function locale_get_display_language invoked with 3 parameters, 1-2 required.',
				30,
			],
			[
				'Function mysqli_fetch_all invoked with 0 parameters, 1-2 required.',
				32,
			],
			[
				'Function mysqli_fetch_all invoked with 3 parameters, 1-2 required.',
				35,
			],
			[
				'Function openssl_open invoked with 7 parameters, 4-6 required.',
				37,
			],
			[
				'Function openssl_x509_parse invoked with 3 parameters, 1-2 required.',
				41,
			],
		]);
	}

	/**
	 * @requires PHP 7.1.1
	 */
	public function testUnpackOnAfter711(): void
	{
		$this->analyse([__DIR__ . '/data/unpack.php'], [
			[
				'Function unpack invoked with 0 parameters, 2-3 required.',
				3,
			],
		]);
	}

	public function testUnpackOnBefore711(): void
	{
		if (PHP_VERSION_ID >= 70101) {
			$this->markTestSkipped('This test requires PHP < 7.1.1');
		}
		$this->analyse([__DIR__ . '/data/unpack.php'], [
			[
				'Function unpack invoked with 0 parameters, 2 required.',
				3,
			],
			[
				'Function unpack invoked with 3 parameters, 2 required.',
				4,
			],
		]);
	}

	public function testPassingNonVariableToParameterPassedByReference(): void
	{
		require_once __DIR__ . '/data/passed-by-reference.php';
		$this->analyse([__DIR__ . '/data/passed-by-reference.php'], [
			[
				'Parameter #1 $foo of function PassedByReference\foo is passed by reference, so it expects variables only.',
				32,
			],
			[
				'Parameter #1 $foo of function PassedByReference\foo is passed by reference, so it expects variables only.',
				33,
			],
		]);
	}

	public function testVariableIsNotNullAfterSeriesOfConditions(): void
	{
		require_once __DIR__ . '/data/variable-is-not-null-after-conditions.php';
		$this->analyse([__DIR__ . '/data/variable-is-not-null-after-conditions.php'], []);
	}

	public function testUnionIterableTypeShouldAcceptTypeFromOtherTypes(): void
	{
		require_once __DIR__ . '/data/union-iterable-type-issue.php';
		$this->analyse([__DIR__ . '/data/union-iterable-type-issue.php'], []);
	}

	public function testCallToFunctionInForeachCondition(): void
	{
		require_once __DIR__ . '/data/foreach-condition.php';
		$this->analyse([__DIR__ . '/data/foreach-condition.php'], [
			[
				'Parameter #1 $i of function CallToFunctionInForeachCondition\takesString expects string, int(1)|int(2)|int(3) given.',
				20,
			],
		]);
	}

	public function testCallToFunctionInDoWhileLoop(): void
	{
		require_once __DIR__ . '/data/do-while-loop.php';
		$this->analyse([__DIR__ . '/data/do-while-loop.php'], [
			[
				'Parameter #1 $object of function CallToFunctionDoWhileLoop\requireStdClass expects stdClass, stdClass|null given.',
				18,
			],
		]);
	}

}
