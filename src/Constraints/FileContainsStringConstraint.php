<?php

namespace AntonioPrimera\Testing\Constraints;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\Constraint\Constraint;

class FileContainsStringConstraint extends Constraint
{
	protected bool $fileExists;
	protected string $filePath;
	
	protected bool $exactMatch;
	
	/**
	 * @var callable|null
	 */
	protected $processor;
	
	protected Collection $missingStrings;
	
	public function __construct(string $filePath, bool $exactMatch = false, ?callable $processor = null)
	{
		$this->filePath = $filePath;
		$this->exactMatch = $exactMatch;
		$this->processor = $processor;
	}
	
	protected function matches($other): bool
	{
		$this->fileExists = file_exists($this->filePath);
		
		if (!$this->fileExists)
			return false;
		
		$processedFileContents = $this->processString(file_get_contents($this->filePath));
		$processedStrings = Collection::wrap($other)
			->map(function($string) {
				return $this->processString($string);
			});
		
		if ($this->exactMatch)
			return $this->matchExactly($processedFileContents, $processedStrings);
		
		$this->missingStrings = $processedStrings->filter(function($string) use ($processedFileContents) {
			return strpos($processedFileContents, $string) === false;
		});
		
		return $this->missingStrings->isEmpty();
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
		if (!$this->fileExists)
			return "file $this->filePath contains the required string(s) because the file doesn't exist.";
		
		if ($this->exactMatch)
			return "file contents is " . $this->exporter()->export($this->missingStrings->first());
		
		return "file contains the following strings:\n" . $this->missingStrings->implode("\n");
	}
	
	//--- Protected helpers -------------------------------------------------------------------------------------------
	
	protected function processString($string)
	{
		$result = is_callable($this->processor)
			? call_user_func($this->processor, $string)
			: $string;
		
		if (!is_string($result))
			throw new InvalidArgumentException("The processor must be a callable returning a string");
		
		return $result;
	}
	
	protected function matchExactly(string $fileContents, Collection $processedStrings)
	{
		$string = $processedStrings->first();
		
		if ($fileContents !== $string) {
			$this->missingStrings = collect($string);
			return false;
		}
		
		return true;
	}
}