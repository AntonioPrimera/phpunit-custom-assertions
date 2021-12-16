<?php

namespace AntonioPrimera\Testing\Constraints;

use Illuminate\Support\Collection;
use PHPUnit\Framework\Constraint\Constraint;

class FoldersExistConstraint extends Constraint
{
	protected Collection $missingFolders;
	
	protected function matches($other): bool
	{
		$this->missingFolders = Collection::wrap($other)->filter(function($folder){
			return !is_dir($folder);
		});
		
		return $this->missingFolders->isEmpty();
	}
	
	protected function failureDescription($other): string
	{
		return $this->toString();
	}
	
	public function toString(): string
	{
		return "following folders exist:\n" . $this->missingFolders->implode("\n");
	}
}