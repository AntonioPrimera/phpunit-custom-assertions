<?php

namespace AntonioPrimera\Testing;

use AntonioPrimera\Testing\Constraints\ListsAreSameConstraint;
use function PHPUnit\Framework\assertIsArray;

trait ArrayAssertions
{
	use BaseAssertions;
	
	/**
	 * Asserts that 2 arrays are the same. For Indexed arrays, the keys are not
	 * relevant, so the items can be in any order.
	 *
	 * Caveats:
	 *    - objects other than Eloquent Models, Arrayable and Collection instances
	 * 		can not be compared well at this point
	 * 	  - callbacks can not be compared (just that they are callable)
	 *    - resources can not be compared (just that they are resources)
	 *
	 * @param mixed $expected
	 * @param mixed $actual
	 * @param bool  $strict
	 */
	public static function assertListsEqual(mixed $expected, mixed $actual, bool $strict = false)
	{
		\PHPUnit\Framework\assertThat($actual, new ListsAreSameConstraint($expected, $strict));
	}
	
	public static function assertArraysEqual(array $expected, mixed $actual, bool $strict = false)
	{
		assertIsArray($expected);
		assertIsArray($actual);
		self::assertListsEqual($expected, $actual, $strict);
	}
}