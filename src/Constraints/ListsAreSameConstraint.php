<?php

namespace AntonioPrimera\Testing\Constraints;

use AntonioPrimera\Testing\Exceptions\ListComparisonException;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Constraint\Constraint;

class ListsAreSameConstraint extends Constraint
{
	protected array $differences = [];
	protected bool $strict;
	protected mixed $expected;
	
	public function __construct(mixed $expected, bool $strict)
	{
		$this->expected = $expected;
		$this->strict = $strict;
	}
	
	protected function matches($other): bool
	{
		try {
			return $this->listsEqual($this->expected, $other, $this->strict);
		} catch (ListComparisonException $exception) {
			$this->differences[] = $exception->getMessage();
			return false;
		}
	}
	
	protected function failureDescription($other): string
	{
		return $this->toString();
	}
	
	/**
	 * @inheritDoc
	 */
	public function toString(): string
	{
		return "two lists are same. Issues:\n" . Collection::wrap($this->differences)->implode("\n");
	}
	
	//--- Protected helpers -------------------------------------------------------------------------------------------
	
	protected function listsEqual($expected, $actual, bool $strict)
	{
		if (!$this->isCollectable($expected))
			throw new ListComparisonException("Expected value must be a list of items");
		
		if (!$this->isCollectable($actual))
			throw new ListComparisonException(
				"Actual value must be a list of items. Variable of type " . gettype($actual) . " given."
			);
		
		$expectedCollection = Collection::wrap($expected);
		$actualCollection = Collection::wrap($actual);
		
		if ($expectedCollection->count() !== $actualCollection->count())
			throw new ListComparisonException(
				"Lists have different item count.\n"
				. "\nExpected: " . $expectedCollection->count()
				. "\nActual: " . $actualCollection->count()
			);
		
		//split collections in indexed and associative arrays
		[$expectedIndexed, $expectedAssociative] = $expectedCollection->partition(function($item, $key){
			return is_int($key);
		});
		
		[$actualIndexed, $actualAssociative] = $actualCollection->partition(function($item, $key){
			return is_int($key);
		});
		
		//compare indexed and associative lists separately (the logic is completely different)
		return $this->associativeCollectionsEqual($expectedAssociative, $actualAssociative, $strict)
			&& $this->indexedCollectionsEqual($expectedIndexed, $actualIndexed, $strict);
	}
	
	/**
	 * @throws Exception
	 */
	protected function associativeCollectionsEqual(Collection $expected, Collection $actual, bool $strict): bool
	{
		foreach ($expected as $key => $item) {
			if (!$actual->has($key))
				throw new ListComparisonException(
					"Item with key '$key' missing in actual list"
				);
			
			if (!$this->itemsAreEqual($item, $actual->get($key), $strict))
				throw new ListComparisonException(
					"Actual item with key '$key' differs from expected item with same key."
					. "\nExpected: " . $this->exporter()->export($item)
					. "\nActual: " . $this->exporter()->export($actual->get($key))
				);
		}
		
		return true;
	}
	
	/**
	 * @throws Exception
	 */
	protected function indexedCollectionsEqual(Collection $expected, Collection $actual, bool $strict): bool
	{
		//go through all expected items
		foreach ($expected as $expectedItem) {
			//search the same item in the actual list
			$actualItemKey = $actual->search(function($item) use ($expectedItem, $strict) {
				return $this->itemsAreEqual($expectedItem, $item, $strict);
			});
			
			//if not found >>> lists are not the same
			if ($actualItemKey === false)
				throw new ListComparisonException(
					"Item missing from actual list: " . $this->exporter()->export($expectedItem)
				);
			
			//remove the item from the actual list, so it won't be matched again
			$actual->splice($actualItemKey, 1);
		}
		
		return true;
	}
	
	/**
	 * Compares 2 items
	 *
	 * Throws an exception if the 2 items could not be compared
	 *
	 * @param $expected
	 * @param $actual
	 * @param $strict
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function itemsAreEqual($expected, $actual, $strict): bool
	{
		if (is_null($expected))
			return is_null($actual);
		
		if (is_scalar($expected))
			return is_scalar($actual)
				&& (
					($strict && $actual === $expected)
					|| (!$strict && $actual == $expected)
				);
		
		//this must always go before collectable comparison (Models are collectables)
		if ($expected instanceof Model)
			return $actual instanceof Model
				&& $expected->is($actual);
		
		//arrays, collections and other lists
		if ($this->isCollectable($expected))
			return $this->listsEqual($expected, $actual, $strict);
		
		if (is_object($expected))
			return $this->_objectsEqual($expected, $actual, $strict);
		
		if (is_resource($expected))
			return $this->_resourcesEqual($expected, $actual, $strict);
		
		if (is_callable($expected))
			return $this->_callablesEqual($expected, $actual, $strict);
		
		throw new ListComparisonException(
			$this->comparisonErrorMessage("Could not compare the two items:", $expected, $actual, $strict)
		);
	}
	
	//--- Internal methods --------------------------------------------------------------------------------------------
	
	/**
	 * Check if the variable is a list, which can be
	 * wrapped into a corresponding Collection
	 *
	 * @param $variable
	 *
	 * @return bool
	 */
	protected function isCollectable($variable)
	{
		return is_iterable($variable)
			|| $variable instanceof Arrayable;
	}
	
	/**
	 * Compare two objects
	 *
	 * @param $expected
	 * @param $actual
	 * @param $strict
	 *
	 * @return bool
	 */
	protected function _objectsEqual($expected, $actual, $strict)
	{
		return is_object($expected) && is_object($actual)
			&& get_class($expected) === get_class($actual)
			&& (!$strict || (spl_object_id($expected) === spl_object_id($actual)));
	}
	
	/**
	 * Override this method if you need to do resource comparison
	 * This is an internal method - don't use it in your code!
	 *
	 * @param $expected
	 * @param $actual
	 * @param $strict
	 *
	 * @return bool
	 */
	protected function _resourcesEqual($expected, $actual, $strict)
	{
		//currently, no way to compare resources - override this if you have a better idea ;)
		return is_resource($expected) && is_resource($actual);
	}
	
	/**
	 * Override this method if you want to somehow compare your callables
	 *
	 * @param $expected
	 * @param $actual
	 * @param $strict
	 *
	 * @return bool
	 */
	protected function _callablesEqual($expected, $actual, $strict)
	{
		//currently, no way to compare callables - override this if you have a better idea ;)
		return is_callable($expected) && is_callable($actual);
	}
	
	protected function comparisonErrorMessage(string $message, mixed $expected, mixed $actual, bool $strict)
	{
		return $message . "\n\n"
			. "Expected (json encoded): \n"
			. json_encode($expected, JSON_PRETTY_PRINT) . "\n"
			. "Actual (json encoded): \n"
			. json_encode($actual, JSON_PRETTY_PRINT) . "\n"
			. "Strict mode: " . ($strict ? 'ON' : 'OFF') . "\n";
	}
}