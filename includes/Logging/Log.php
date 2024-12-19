<?php

namespace Autoblue\Logging;

use Monolog\Logger;
use Monolog\Handler\HandlerInterface;
use Monolog\Processor\PsrLogMessageProcessor;

class Log {
	private Logger $logger;

	public function __construct( ?HandlerInterface $handler = null ) {
		$this->logger = new Logger( 'autoblue' );
		$this->logger->pushProcessor( new PsrLogMessageProcessor() );
		$this->logger->pushProcessor( new WPProcessor() );
		$this->logger->pushHandler(
			$handler ?? new DatabaseHandler()
		);
	}

	/**
	 * @param string $message
	 * @param array<string, mixed> $context
	 */
	public function error( string $message, array $context = [] ): void {
		$this->logger->error( $message, $context );
	}

	/**
	 * @param string $message
	 * @param array<string, mixed> $context
	 */
	public function warning( string $message, array $context = [] ): void {
		$this->logger->warning( $message, $context );
	}

	/**
	 * @param string $message
	 * @param array<string, mixed> $context
	 */
	public function info( string $message, array $context = [] ): void {
		$this->logger->info( $message, $context );
	}

	/**
	 * @param string $message
	 * @param array<string, mixed> $context
	 */
	public function debug( string $message, array $context = [] ): void {
		$this->logger->debug( $message, $context );
	}

	/**
	 * Not a part of PSR-3, but useful for logging successful operations.
	 *
	 * Behind the scenes it's just an info log, with a success notice.
	 *
	 * @param string $message
	 * @param array<string, mixed> $context
	 */
	public function success( string $message, array $context = [] ): void {
		$this->logger->info( __( '[Success] ', 'autoblue' ) . $message, $context );
	}
}
