<?php

namespace AntonioPrimera\Testing;

use AntonioPrimera\Testing\Constraints\FileContainsStringConstraint;
use AntonioPrimera\Testing\Constraints\FilesExistConstraint;
use AntonioPrimera\Testing\Constraints\FoldersExistConstraint;
use Illuminate\Support\Arr;

trait FileAssertions
{
	use BaseAssertions;
	
	/**
	 * Asserts that a folder or an array of folders exists
	 *
	 * @param $folders
	 */
	public static function assertFoldersExist($folders)
	{
		\PHPUnit\Framework\assertThat($folders, new FoldersExistConstraint());
	}
	
	public static function assertFilesExist($files)
	{
		\PHPUnit\Framework\assertThat($files, new FilesExistConstraint());
	}
	
	/**
	 * Assert that the file contents at the given path matches exactly a given string.
	 * A processor callable can be provided to process the file contents and the
	 * given string before comparison (e.g. removing new lines and tabs)
	 *
	 * @param string        $expected  - the expected file contents
	 * @param string        $path      - the path to the file
	 * @param callable|null $processor - a callable receiving the file contents and each expected strings as argument
	 *                                 and returning a processed string (e.g. trimming / cleaning the strings)
	 */
	public static function assertFileContentsEquals(string $expected, string $path, ?callable $processor = null)
	{
		\PHPUnit\Framework\assertThat($expected, new FileContainsStringConstraint($path, true, $processor));
	}
	
	/**
	 * Assert that the file at the given path contains an expected string or a
	 * list of strings.
	 *
	 * @param mixed        $expected - string | array
	 * @param string        $path
	 * @param callable|null $processor
	 */
	public static function assertFileContains(mixed $expected, string $path, ?callable $processor = null)
	{
		\PHPUnit\Framework\assertThat($expected, new FileContainsStringConstraint($path, false, $processor));
	}
	
	//--- Public static helpers ---------------------------------------------------------------------------------------
	
	public static function removeWhiteSpaces(): callable
	{
		return function($string) {
			return str_replace(["\t", " ", "\n"], '', $string);
		};
	}
}