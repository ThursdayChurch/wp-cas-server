<?php
/**
 * Implements CAS server exception classes for the WP CAS Server plugin.
 *
 * @package \WPCASServerPlugin\Server
 * @version 1.1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'WPCASException' ) ) {
	/**
	 * Base CAS server exception.
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	class WPCASException extends Exception {

		/**
		 * Internal Error
		 */
		const ERROR_INTERNAL_ERROR  = 'INTERNAL_ERROR';

		/**
		 * Error code.
		 * @var string
		 */
		protected $casCode;

		/**
		 * Exception constructor.
		 *
		 * @param string $message Exception message.
		 * @param string $casCode CAS error code (default: "INTERNAL_ERROR").
		 */
		public function __construct( $message = '', $casCode = self::ERROR_INTERNAL_ERROR ) {
			parent::__construct( $message );

			$this->casCode = $casCode;
		}

		/**
		 * Generate a new exception instance from a WordPress error.
		 *
		 * @param  WP_Error       $error WordPress error.
		 *
		 * @return WPCASException        WordPress error as an exception.
		 */
		public static function fromError ( WP_Error $error ) {
			$code    = $error->get_error_code();
			$message = $error->get_error_message( $code );
			return new static ($message, $code);
		}

		/**
		 * Error code getter.
		 *
		 * @return string Associated error code.
		 */
		final public function getCASCode() {
			return $this->casCode;
		}

		/**
		 * Returns a WordPress error object based on the exception.
		 *
		 * @param  string   $slug Error slug, either `authenticationError` or `proxyError`.
		 *
		 * @return WP_Error       WordPress error.
		 */
		public function getErrorInstance () {
			return new WP_Error( $this->casCode, $this->message );
		}

	}
}
