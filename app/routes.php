<?php
	#$this->add('admin','admin');
	#$this->add('(.+)','admin/$1');
	$this->add('(.+)','index/index/$1');
	$this->add('files/get/(.+)','files/view/$1');
?>