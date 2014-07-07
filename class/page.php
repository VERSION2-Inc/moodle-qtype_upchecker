<?php
namespace upchecker;

abstract class page {
	public $url;

	public function __construct($url) {
		$this->url = $url;


	}

	public abstract function execute();
}
