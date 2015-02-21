<?php
/**
 * @package \WPCASServerPlugin\Tests
 */

/**
 * @coversDefaultClass WPCASControllerProxyValidate
 */
class TestWPCASControllerProxyValidate extends WPCAS_UnitTestCase {

	private $server;

	/**
	 * Setup a test method for the WPCASServer class.
	 */
	function setUp() {
		parent::setUp();
		$this->server = new WPCASServer;
	}

	/**
	 * Finish a test method for the CASServer class.
	 */
	function tearDown() {
		parent::tearDown();
		unset( $this->server );
	}

	function test_interface () {
		$this->assertArrayHasKey( 'ICASServer', class_implements( $this->server ),
			'WPCASServer implements the ICASServer interface.' );
	}

	/**
	 * @runInSeparateProcess
	 * @covers ::proxyValidate
	 *
	 * @todo Test support for the optional 'pgtUrl' parameter.
	 * @todo Test support for the optional 'renew' parameter.
	 */
	function test_proxyValidate () {

		$this->assertTrue( is_callable( array( $this->server, 'proxyValidate' ) ),
			"'proxyValidate' method is callable." );

		$service = 'http://test/';

		/**
		 * No service.
		 */
		$args = array(
			'service' => '',
			'ticket'  => 'ticket',
			);

		$error = $this->server->proxyValidate( $args );

		$this->assertXPathMatch( 1, 'count(//cas:authenticationFailure)', $error,
			'Error if service not provided.' );

		$this->assertXPathMatch( WPCASRequestException::ERROR_INVALID_REQUEST, 'string(//cas:authenticationFailure[1]/@code)', $error,
			'INVALID_REQUEST error code if service not provided.' );

		/**
		 * No ticket.
		 */
		$args = array(
			'service' => $service,
			'ticket'  => '',
			);

		$error = $this->server->proxyValidate( $args );

		$this->assertXPathMatch( 1, 'count(//cas:authenticationFailure)', $error,
			'Error if ticket not provided.' );

		$this->assertXPathMatch( WPCASRequestException::ERROR_INVALID_REQUEST, 'string(//cas:authenticationFailure[1]/@code)', $error,
			'INVALID_REQUEST error code if ticket not provided.' );

		/**
		 * Invalid ticket.
		 */
		$args = array(
			'service' => $service,
			'ticket'  => 'bad-ticket',
			);

		$error = $this->server->proxyValidate( $args );

		$this->assertXPathMatch( 1, 'count(//cas:authenticationFailure)', $error,
			'Error on bad ticket.' );

		$this->assertXPathMatch( WPCASTicketException::ERROR_INVALID_TICKET, 'string(//cas:authenticationFailure[1]/@code)', $error,
			'INVALID_TICKET error code on bad ticket.' );

		/**
		 * Valid ticket.
		 */
		$user_id = $this->factory->user->create();

		wp_set_current_user( $user_id );

		try {
			$this->server->login( array( 'service' => $service ) );
		}
		catch (WPDieException $message) {
			parse_str( parse_url( $this->redirect_location, PHP_URL_QUERY ), $query );
		}

		$args = array(
			'service' => $service,
			'ticket'  => $query['ticket'],
			);

		$user = get_user_by( 'id', $user_id );

		$xml = $this->server->proxyValidate( $args );

		$this->assertXPathMatch( 1, 'count(//cas:authenticationSuccess)', $xml,
			'Successful validation.' );

		$this->assertXPathMatch( 1, 'count(//cas:authenticationSuccess/cas:user)', $xml,
			"Ticket validation response returns a user.");

		$this->assertXPathMatch( $user->user_login, 'string(//cas:authenticationSuccess[1]/cas:user[1])', $xml,
			"Ticket validation returns user login." );

		/**
		 * Do not enforce single-use tickets.
		 */

		WPCASServerPlugin::setOption( 'allow_ticket_reuse', 1 );

		$xml = $this->server->proxyValidate( $args );

		$this->assertXPathMatch( 1, 'count(//cas:authenticationSuccess)', $xml,
			'Settings allow ticket reuse.' );

		/**
		 * /proxyValidate may validate Proxy Tickets.
		 */
		$args = array(
			'service' => $service,
			'ticket'  => preg_replace( '@^' . WPCASTicket::TYPE_ST . '@', WPCASTicket::TYPE_PT, $query['ticket'] ),
			);

		$xml = $this->server->proxyValidate( $args );

		$this->assertXPathMatch( 1, 'count(//cas:authenticationSuccess)', $xml,
			"'proxyValidate' may validate proxy tickets." );

		/**
		 * Enforce single-use tickets.
		 */
		WPCASServerPlugin::setOption( 'allow_ticket_reuse', 0 );

		$args = array(
			'service' => $service,
			'ticket'  => $query['ticket'],
			);

		$error = $this->server->proxyValidate( $args );

		$this->assertXPathMatch( 1, 'count(//cas:authenticationFailure)', $error,
			"Settings do not allow ticket reuse." );

		$this->assertXPathMatch( WPCASTicketException::ERROR_INVALID_TICKET, 'string(//cas:authenticationFailure[1]/@code)', $error,
			'INVALID_TICKET error code on ticket reuse.' );
	}

}
