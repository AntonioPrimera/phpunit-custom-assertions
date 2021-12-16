<?php

namespace AntonioPrimera\Testing\Constraints;

use Illuminate\Support\Collection;
use PHPUnit\Framework\Constraint\Constraint;

class FilesExistConstraint extends Constraint
{
	protected Collection $missingFiles;
	
	protected function matches($other): bool
	{
		$this->missingFiles = Collection::wrap($other)->filter(function($filePath){
			return !file_exists($filePath);
		});
		
		return $this->missingFiles->isEmpty();
	}
	
	protected function failureDescription($other): string
	{
		return $this->toString();
	}
	
	public function toString(): string
	{
		return "following files exist:\n" . $this->missingFiles->implode("\n");
	}
}