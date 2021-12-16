<?php
namespace AntonioPrimera\Testing\Tests\Unit;

use AntonioPrimera\Testing\FileAssertions;
use AntonioPrimera\Testing\Tests\TestCase;

class FileAssertionsTest extends TestCase
{
	use FileAssertions;
	
	protected string $sampleFilePath = __DIR__ . '/../TestContext/sampleFile.txt';
	
	protected function setUp(): void
	{
		parent::setUp();
		
		if (file_exists($this->sampleFilePath))
			unlink($this->sampleFilePath);
	}
	
	protected function tearDown(): void
	{
		parent::tearDown();
		if (file_exists($this->sampleFilePath))
			unlink($this->sampleFilePath);
	}
	
	//--- File contents tests -----------------------------------------------------------------------------------------
	
	/** @test */
	public function it_can_assert_that_a_file_contents_is_exactly_the_same_without_stripping_whitespaces()
	{
		$this->createSampleFile("abc\tdef\nghi \t jkl");
		$this->assertFileContentsEquals("abc\tdef\nghi \t jkl", $this->sampleFilePath);
		
		$this->expectAssertionToFail();
		$this->assertFileContentsEquals("abc\tdef\nghi \tjkl", $this->sampleFilePath);
	}
	
	/** @test */
	public function it_can_assert_that_a_file_contents_is_the_same_with_a_given_processor()
	{
		$processor = function($string) {
			return str_replace(["\t", "\n"], '', $string);
		};
		
		$this->createSampleFile("abc\tdef\nghi \t jkl");
		$this->assertFileContentsEquals("ab\ncdef\t\t\t\nghi  jkl", $this->sampleFilePath, $processor);
		
		$this->expectAssertionToFail();
		$this->assertFileContentsEquals("abctdef\n\tghi jkl", $this->sampleFilePath, $processor);
	}
	
	/** @test */
	public function it_can_assert_that_a_file_contains_a_string()
	{
		$this->createSampleFile([
			'<?php',
			'namespace My\\Namespace;',
			'',
			'class MyClass',
			'{',
			"\tuse FileAssertions;",
			'}',
		]);
		
		$this->assertFileContains("namespace My\\Namespace;", $this->sampleFilePath);
		
		$this->expectAssertionToFail();
		$this->assertFileContains("class MyClass ", $this->sampleFilePath);
	}
	
	/** @test */
	public function it_can_assert_that_a_file_contains_one_or_multiple_strings()
	{
		$this->createSampleFile([
			'<?php',
			'namespace My\\Namespace;',
			'',
			'class MyClass',
			'{',
			"\tuse FileAssertions;",
			'}',
		]);
		
		$this->assertFileContains("namespace My\\Namespace;", $this->sampleFilePath);
		$this->assertFileContains(
			[
				"\n}",
				"namespace My\\Namespace;\n",
				'class MyClass',
				'FileAssert',
			],
			$this->sampleFilePath
		);
		
		$this->expectAssertionToFail();
		$this->assertFileContains(
			[
				'|',
				'namespace My\\Namespace;',
			],
			$this->sampleFilePath
		);
	}
	
	/** @test */
	public function it_can_assert_that_a_file_contains_strings_with_a_processing_function()
	{
		$this->createSampleFile('Writing tests for testing my test-methods');
		$expected = "Writingtesttest tests fortesttest ing my -methods";
		$processor = function($string) {
			return str_replace('test', '', $string);
		};
		
		$this->assertFileContains(
			$expected,
			$this->sampleFilePath,
			$processor
		);
	}
	
	//--- File existence tests ----------------------------------------------------------------------------------------
	
	/** @test */
	public function it_can_assert_that_a_file_or_a_list_of_files_exist()
	{
		$this->createSampleFile('abc');
		$this->assertFilesExist($this->sampleFilePath);
		$this->assertFilesExist([$this->sampleFilePath, __DIR__ . '/../TestCase.php', __FILE__]);
		
		$this->expectAssertionToFail($this->sampleFilePath . '.php');
		$this->assertFilesExist([$this->sampleFilePath . '.php', __FILE__,]);
	}
	
	//--- Folders existence tests -------------------------------------------------------------------------------------
	
	/** @test */
	public function it_can_assert_that_a_folder_or_a_list_of_folders_exist()
	{
		$this->assertFoldersExist(__DIR__);
		$this->assertFoldersExist([__DIR__, app_path(), resource_path()]);
		
		$this->expectAssertionToFail(app_path('john'), resource_path('jane'));
		$this->assertFoldersExist([__DIR__, app_path('john'), resource_path('jane')]);
	}
	
	//--- Protected helpers -------------------------------------------------------------------------------------------
	
	protected function createSampleFile(string | array $content)
	{
		$stringContent = is_string($content) ? $content : implode("\n", $content);
		file_put_contents($this->sampleFilePath, $stringContent);
	}
}