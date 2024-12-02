<?php

namespace Autoblue\Logging;

use Monolog\Logger;
use Monolog\Handler\HandlerInterface;

class Log {
	private Logger $logger;

	public function __construct( ?HandlerInterface $handler = null ) {
		global $wpdb;

		$this->logger = new Logger( 'autoblue' );
		$this->logger->pushHandler(
			$handler ?? new DatabaseHandler( $wpdb )
		);
	}

	public function error( string $message, array $context = [] ): void {
		$this->logger->error( $message, $context );
	}

	public function warning( string $message, array $context = [] ): void {
		$this->logger->warning( $message, $context );
	}

	public function info( string $message, array $context = [] ): void {
		$this->logger->info( $message, $context );
	}

	public function debug( string $message, array $context = [] ): void {
		$this->logger->debug( $message, $context );
	}
}
