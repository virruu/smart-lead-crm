<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Smart_Lead_CRM_Loader {

	protected $actions = array();
	protected $filters = array();

	public function add_action( $hook, $cb, $pri = 10, $args = 1 ) {
		$this->actions[] = compact( 'hook', 'cb', 'pri', 'args' );
		return $this;
	}

	public function add_filter( $hook, $cb, $pri = 10, $args = 1 ) {
		$this->filters[] = compact( 'hook', 'cb', 'pri', 'args' );
		return $this;
	}

	public function run() {
		foreach ( $this->filters as $h ) {
			add_filter( $h['hook'], $h['cb'], $h['pri'], $h['args'] );
		}
		foreach ( $this->actions as $h ) {
			add_action( $h['hook'], $h['cb'], $h['pri'], $h['args'] );
		}
	}
}
