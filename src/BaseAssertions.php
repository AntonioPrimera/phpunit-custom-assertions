<?php

namespace AntonioPrimera\Testing;

use Illuminate\Support\Collection;
use PHPUnit\Framework\ExpectationFailedException;

trait BaseAssertions
{
	/**
	 * Expect one of the next assertions to fail. Anything beyond the failed assertion will
	 * not be run. It is recommended to only run a single failing assertion after this
	 * expectation method, at the end of your test, so it is clear what fails.
	 *
	 * If $messages is provided (string | array) this method also expects the failure
	 * message to contain all given messages (as substrings).
	 */
	public function expectAssertionToFail(...$messages)
	{
		$this->expectException(ExpectationFailedException::class);
		
		foreach (Collection::wrap($messages)->flatten() as $message)
			$this->expectExceptionMessage($message);
	}
}