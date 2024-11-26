<?php

namespace Autoblue\Models;

class ConnectedAccount {
	private $did;
	private $access_jwt;
	private $refresh_jwt;
	private $meta = [];

	public function __construct( $data ) {
		$this->did         = $data['did'];
		$this->access_jwt  = $data['access_jwt'];
		$this->refresh_jwt = $data['refresh_jwt'];
		$this->meta        = $data['meta'];
	}

	public function get_did() {
		return $this->did;
	}

	public function get_access_jwt() {
		return $this->access_jwt;
	}

	public function get_refresh_jwt() {
		return $this->refresh_jwt;
	}

	public function get_meta() {
		return $this->meta;
	}

	public function get_handle() {
		return $this->meta['handle'] ?? '';
	}

	public function get_name() {
		return $this->meta['name'] ?? '';
	}

	public function get_avatar() {
		return $this->meta['avatar'] ?? '';
	}

	/**
	 * Convert the account to an array.
	 *
	 * @return array
	 */
	public function to_array( $include_sensitive = false ) {
		$data = [
			'did'  => $this->did,
			'meta' => $this->meta,
		];

		if ( $include_sensitive ) {
			$data['access_jwt']  = $this->access_jwt;
			$data['refresh_jwt'] = $this->refresh_jwt;
		}

		return $data;
	}
}
