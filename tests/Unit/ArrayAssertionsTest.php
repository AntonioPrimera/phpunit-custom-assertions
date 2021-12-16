<?php
namespace AntonioPrimera\Testing\Tests\Unit;

use AntonioPrimera\Testing\ArrayAssertions;
use AntonioPrimera\Testing\Tests\TestCase;
use Illuminate\Console\Command;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Action;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ArrayAssertionsTest extends TestCase
{
	use ArrayAssertions;
	
	/** @test */
	public function abc()
	{
		$this->assertListsEqual(['a', 1, [1, null, collect([0, 5])]], [[null, true, [5,false]], 1,'a']);
	}
	
	/** @test */
	public function it_can_assert_that_two_flat_indexed_arrays_are_same()
	{
		$this->assertArraysEqual(
			['item1', 'item2'],
			['item2', 'item1']
		);
		
		$this->expectAssertionToFail();
		$this->assertArraysEqual(
			['item1', 'item2'],
			['item1', 'item3']
		);
	}
	
	/** @test */
	public function it_can_assert_that_two_deep_indexed_arrays_are_same()
	{
		$this->assertArraysEqual(
			['item1', 'item2', ['index31', ['index321', 'index322']]],
			[['index31', ['index322', 'index321']], 'item1', 'item2'],
		);
		
		$this->expectAssertionToFail();
		$this->assertArraysEqual(
			['item1', 'item2', ['index31', ['index321', 'index323']]],
			[['index31', ['index322', 'index321']], 'item1', 'item2'],
		);
	}
	
	/** @test */
	public function it_can_assert_that_two_deep_lists_with_mixed_items_are_same()
	{
		$list1 = ['item1', 1, true, null, false, collect(['index31', ['index321', 'index322']]), [1, false]];
		$list2 = collect([collect(['index31', ['index322', 'index321']]), null, 'item1', [1, false], true, 1, false]);
		$list3 = ['item1', 1, true, null, false, collect(['index31', ['index321', 'index322']]), [1, true]];
		
		$this->assertListsEqual($list1, $list2);
		
		$this->expectAssertionToFail();
		$this->assertListsEqual($list2, $list3);
	}
	
	/** @test */
	public function it_can_not_compare_the_contents_of_callable_functions_but_will_check_same_number_of_callables()
	{
		$callable1 = function(){ return true; };
		$callable2 = function(){ return false; };
		$callable3 = function() { return '!'; };
		
		$this->assertListsEqual(
			[1, 'abc', $callable1],
			collect([$callable2, 1, 'abc'])
		);
		
		$this->assertListsEqual(
			[$callable1, $callable2],
			[$callable2, $callable3]
		);
		
		$this->expectAssertionToFail();
		$this->assertListsEqual(
			[$callable1, $callable2],
			[1, $callable2]
		);
	}
	
	/** @test */
	public function it_can_not_compare_objects_but_it_will_compare_their_classes()
	{
		$command = new Command();
		$action1 = new Action('omg', 'https://antonio.primera-lanoy.com');
		$action2 = new Action('wtf', 'https://antonio.primeralanoy.com');
		
		$notification = new Notification();
		
		$this->assertListsEqual(
			[$command, $action1],
			[$action1, $command]
		);
		
		$this->assertListsEqual(
			[$command, $action1],
			[$action2, $command]
		);
		
		$this->expectAssertionToFail();
		$this->assertListsEqual(
			[$action1, $notification],
			[$action1, $command]
		);
	}
	
	/** @test */
	public function non_strict_mode_will_not_care_about_numeric_types()
	{
		$this->assertListsEqual(['0', 1], ['1', 0], false);
		$this->expectAssertionToFail();
		$this->assertListsEqual(['0', 1], ['1', 0], true);
	}
	
	/** @test */
	public function non_strict_mode_will_not_care_about_boolean_and_truthy_and_falsy_values()
	{
		$this->assertListsEqual([0, 1], [true, false], false);
		$this->expectAssertionToFail();
		$this->assertListsEqual([0, 1], [true, false], true);
	}
	
	/** @test */
	public function strict_mode_checks_for_objects_with_same_spl_id()
	{
		$command = new Command();
		$action1 = new Action('omg', 'https://antonio.primera-lanoy.com');
		$action2 = new Action('wtf', 'https://antonio.primeralanoy.com');
		
		$this->assertListsEqual([$command, $action1], [$command, $action2], false);
		
		$this->expectAssertionToFail();
		$this->assertListsEqual([$command, $action1], [$command, $action2], true);
	}
	
	/** @test */
	public function collections_are_not_compared_as_objects_but_as_lists()
	{
		$collection1 = Collection::wrap([1, false, ['ab', 'cd']]);
		$collection2 = Collection::wrap([['ab', 'cd'], 1, false]);
		
		$this->assertListsEqual($collection1, $collection2, false);
		$this->assertListsEqual($collection1, $collection2, true);
	}
	
	/** @test */
	public function eloquent_models_are_compared_different_than_objects()
	{
		$model1 = new User();
		$model2 = new User();
		$model3 = new User();
		
		$model1->id = 15;
		$model2->id = 12;
		$model3->id = 15;
		
		$this->assertTrue($model1->is($model3));
		$this->assertFalse($model1->is($model2));
		
		$this->assertListsEqual([$model1, $model2], [$model2, $model3], false);
		$this->assertListsEqual([$model1, $model2], [$model2, $model3], true);
	}
}