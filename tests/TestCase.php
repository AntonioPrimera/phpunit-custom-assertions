<?php

namespace AntonioPrimera\Testing\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

class TestCase extends \Orchestra\Testbench\TestCase
{
	//use RefreshDatabase;

	protected function getPackageProviders($app)
	{
		return [
			// \Your\Package\Namespace\YourServiceProvider::class,
		];
	}
}