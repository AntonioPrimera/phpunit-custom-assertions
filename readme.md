# Antonio Primera's Custom PHPUnit Assertions

If you are a serious TDD Enthusiast, you probably got into situations where you needed special assertion methods.

This is a package where I tried to gather a few useful custom assertions for my PHPUnit Tests. This package relies
on Laravel's *Illuminate\Support* and *Illuminate\Contracts* and is primarily written for Laravel tests, but should
work fine also with a bare-bone PHPUnit environment.

For the moment, the following assertion categories are covered:

- File contents assertions
- File and Folder existence
- List comparison (arrays, iterables, Laravel Collections etc.)

## Installation

Import via Composer:

`composer require --dev antonioprimera/phpunit-custom-assertions`

Then just use the trait `AntonioPrimera\Testing\CustomAssertions` in your test cases.

## Usage

After including the trait `AntonioPrimera\Testing\CustomAssertions` in your test case, you can just call any of the
assertion methods included in this package, in your tests.

Here's a short example covering most of the assertions in this package:

```php
namespace AntonioPrimera\Testing\Tests\Unit;

use AntonioPrimera\Testing\CustomAssertions;
use PHPUnit\Framework\TestCase;

class FileAssertionsTest extends TestCase
{
	use CustomAssertions;
	
	/** @test */
	public function it_can_do_any_of_the_implemented_assertions()
	{
	    //you can call any of the assertions as instance methods, functions or static methods
	    $this->assertFileContains('CustomAssertions', __FILE__);
	    assertFileContains(['namespace', 'use'], __FILE__);
	    static::assertFileContentsEquals('abc', __FILE__, function($str) { return 'abc'; });
	    
	    //compares two lists
	    $this->assertListsEqual(['a', 1, [true, null, collect([0, 5])]], [[null, true, [5,0]], 1,'a']);
	    
	    //the following assertion is expected to fail, containing the messages given as parameters
	    $this->expectAssertionToFail(__FILE__ . '.js');
	    $this->assertFilesExist([__FILE__, __FILE__ . '.js']);
	}
}
```

Because the assertions are written based on basic PHPUnit Framework Constraints, all assertions can be called
as static methods:

```php
static::assertFileContains($string, $filePath);
```

as instance methods:

```php
$this->assertFileContains($string, $filePath);
```

or as functions:

```php
assertFileContains($string, $filePath);
```

## Available assertions

### Files and folders

#### assertFileContains(string | array $subString, string $filePath, [callable $processor])

This assertion checks whether a file at the given path **$filePath** contains a string **$subString**.

For ease of use, you can check that the file contains several strings, by providing an array of strings
as the first argument (**$substring**).

```php
$this->assertFileContains('namespace', $filePath);
$this->assertFileContains(['namespace', 'class'], $filePath);
```

If you want to process the file contents and the strings, before making the checks, you can provide a
callable string processor (a function or a method) as the third argument. The callable receives a string
as its argument (the file contents and each of the string to compare) and returns a processed strings, which
will be used to make the actual comparison.

For example, if you want to remove all whitespaces (space, tabs, new lines) before comparing, you could use
a function like in the example below. This can be very useful if you don't care about the formatting of the
file contents.

```php
$this->assertFileContains(
    'namespace App\\Models; class User{ }',
    $userModelFilePath,
    function($str) {
        return str_replace([' ', "\t", "\n"], '', $str);
    } 
);
```

Because this function is so useful, I added it into a public static method **removeWhiteSpaces**, so you
don't have to write it yourself evey time. This method returns the processor function. You can use it
like this:

```php
assertFileContains($substr, $filePath, static::removeWhiteSpaces());
```

#### assertFileContentsEquals(string $expected, string $path, [callable $processor])

This assertion works much like **assertFileContains(...)** but can only receive a single string as its first
argument and will check that the given string is identical with the entire contents of the file. A processor
function / callable can also be provided, exactly like in the previous assertion.

#### assertFilesExist(string | array $filePaths)

This works similar to the original file existence assertion, but it allows you to provide one or more
file paths (string | array) and in case of failure it will tell you which of the files don't exist.

#### assertFoldersExist(string | array $folderPaths)

This works similar to **assertDirectoryExists** but the assertion has a bette name **Folders** instead of
**Directories** (seriously, who calls them Directories?) enabling you to provide one or more folder
paths (string | array) and in case of failure it will tell you which of the folders don't exist.

### Lists / Arrays / Collections

#### assertListsEqual($expected, $actual, [bool $strict = false])

This assertion will do its best to check whether the actual list is the same as the expected list.

The **$expected** and **$actual** parameters can be any iterable type - hence the generic term list:

- arrays
- any object which is [iterable (see php docs)](https://www.php.net/manual/en/language.types.iterable.php)
- objects implementing **Illuminate\Contracts\Support** (e.g. Laravel Collections)

This will try to compare associative lists (with string keys), indexed lists (with numeric keys), as well
as mixed lists (having both string and int keys), ignoring the order in which the indexed items are present.

This assertion will compare also deep / nested lists - this is particularly challenging for nested indexed
lists.

To do a strict comparison, set the **$strict** flag to true. Please be aware that **NULL** values are compared
separately, so they must match another **NULL** value, regardless of $strict, but anything else, like
booleans and their numeric counterparts 0 and 1 will be inter-matched in non-strict mode and can provide
false positive assertions.

There are currently a few caveats (they can be solved, but does anybody need this? is it worth the effort?):

1. Objects, other than lists (Collections / iterable list instances) and Eloquent Models (it's a Laravel thingy),
can not be compared properly:
   1. in strict mode the objects must be references to the same instance (spl_ids are compared)
   2. in non-strict mode only the object classes are compared
2. Callables can't be compared - only that the actual list contains the same number of callables
3. Resources can't be compared - only that the actual list contains the same number of resources

```php
$this->assertListsEqual(['a', 1, [true, null, collect([0, 5])]], [[null, true, [5, 0]], 1, 'a']);

//this will succeed in non-strict mode, but will fail in strict mode
$this->assertListsEqual(['a', 1, [1, null, collect([0, 5])]], [[null, true, [5, false]], 1, 'a']);
```

#### assertArraysEqual($expected, $actual, [bool $strict = false])

This uses the previous (assertListEqual) assertion, but checks that the 2 lists are actual arrays.


### Helpers

#### expectAssertionToFail(...$messages)

This helper works like an assertion, and it expects that the next (or one of the next) assertion fails.
If a message or a list of messages is provided, it also checks that the failure messages contain the
given strings.

Because of the way PHPUnit works, a test ends at the first failed assertion, so whatever assertions you
write after a failing assertion, they will be ignored (there are ways around it but this is a good
challenge for your creativity or StackOverflow searching skills). Having said this, it is recommended
that this method is used just before the failing assertion, at the end of a test.

e.g.

```php
public function test_that_something_does_not_work()
{
    //successful assertion
    $this->assertFoldersExist([__DIR__, app_path(), resource_path()]);
    
    //expect the next assertion to fail and the failure message to contain the 2 missing folder paths
    $this->expectAssertionToFail(app_path('random/folder'), resource_path('nix/da'));
    //failing assertion
    $this->assertFoldersExist([__DIR__, app_path('random/folder'), resource_path('nix/da')]);
}
```

## Tests

**At the moment: 18 tests, 65 assertions.**

This package is thoroughly tested and running smoothly.

## Future development / Contribution / Support

I plan to add here new assertions as soon as I develop them for my own projects. If you like this package,
you can also check out my other open source packages (mostly for Laravel).

This package is open source, so it welcomes any contribution. If you want to contribute with enhancements,
new functionality or major changes, before putting any work in, please contact me and let me know what
your needs are and what changes you plan to make.