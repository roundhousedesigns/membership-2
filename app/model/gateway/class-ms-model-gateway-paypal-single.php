<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 * 
 * This program is free software; you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License, version 2, as  
 * published by the Free Software Foundation.                           
 *
 * This program is distributed in the hope that it will be useful,      
 * but WITHOUT ANY WARRANTY; without even the implied warranty of       
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        
 * GNU General Public License for more details.                         
 *
 * You should have received a copy of the GNU General Public License    
 * along with this program; if not, write to the Free Software          
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               
 * MA 02110-1301 USA                                                    
 *
*/

class MS_Model_Gateway_Paypal_Single extends MS_Model_Gateway {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected static $instance;
	
	protected $id = self::GATEWAY_PAYPAL_SINGLE;
	
	const STATUS_FAILED = 'failed';
	
	const STATUS_REVERSED = 'reversed';
	
	const STATUS_REFUNDED = 'refunded';
	
	const STATUS_PENDING = 'pending';
	
	const STATUS_DISPUTE = 'dispute';
	
	const STATUS_DENIED = 'denied';
	
	protected $name = 'PayPal Single Gateway';
	
	protected $description = 'PayPal for single payments (not recurring).';
	
	protected $manual_payment = true;
	
	protected $pro_rate = true;
	
	protected $paypal_email;
	
	protected $paypal_site;
	
	protected $mode;
	
	public function after_load() {
		parent::after_load();
		if( $this->active ) {
			$this->add_filter( 'ms_model_invoice_get_status', 'gateway_custom_status' );
		}
	}
	
	public function gateway_custom_status( $status ) {
		$paypal_status = array(
			self::STATUS_FAILED => __( 'Failed', MS_TEXT_DOMAIN ),
			self::STATUS_REVERSED => __( 'Reversed', MS_TEXT_DOMAIN ),
			self::STATUS_REFUNDED => __( 'Refunded', MS_TEXT_DOMAIN ),
			self::STATUS_PENDING => __( 'Pending', MS_TEXT_DOMAIN ),
			self::STATUS_DISPUTE => __( 'Dispute', MS_TEXT_DOMAIN ),
			self::STATUS_DENIED => __( 'Denied', MS_TEXT_DOMAIN ),
		);
		
		return array_merge( $status, $paypal_status );
	}
	
	public function purchase_button( $ms_relationship ) {
		$membership = $ms_relationship->get_membership();
		if( 0 == $membership->price ) {
			return;
		}
		
		$invoice = $ms_relationship->get_current_invoice();
		$fields = array(
				'business' => array(
						'id' => 'business',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->paypal_email,
				),
				'cmd' => array(
						'id' => 'cmd',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => '_xclick',
				),
				'item_number' => array(
						'id' => 'item_number',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $ms_relationship->membership_id,
				),
				'item_name' => array(
						'id' => 'item_name',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $invoice->name,
				),
				'amount' => array(
						'id' => 'amount',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $invoice->total,
				),
				'currency_code' => array(
						'id' => 'currency_code',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $invoice->currency,
				),
				'return' => array(
						'id' => 'return',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_WELCOME ) ),
				),
				'cancel_return' => array(
						'id' => 'cancel_return',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_MEMBERSHIPS ) ),
				),
				'notify_url' => array(
						'id' => 'notify_url',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->get_return_url(),
				),
				'lc' => array(
						'id' => 'lc',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->paypal_site,
				),
				'custom' => array(
						'id' => 'custom',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $invoice->id,
				),
		);
		
		/** Don't send to paypal if free */
		if( 0 == $invoice->total ) {
			$fields = array(
					'gateway' => array(
							'id' => 'gateway',
							'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
							'value' => $this->id,
					),
					'ms_relationship_id' => array(
							'id' => 'ms_relationship_id',
							'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
							'value' => $ms_relationship->id,
					),
					'step' => array(
							'id' => 'step',
							'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
							'value' => 'process_purchase',
					),
			);
			$action = null;
		}
		else {
			if( self::MODE_LIVE == $this->mode ) {
				$action = 'https://www.paypal.com/cgi-bin/webscr';
			} 
			else {
				$action = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
			}
		}
		
		if( ! empty( $this->pay_button_url ) && strpos( $this->pay_button_url, 'http' ) !== 0 ) {
			$fields['submit'] = array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' => $this->pay_button_url,
			);
		}
		else {
			$fields['submit'] = array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_IMAGE,
					'value' =>  $this->pay_button_url ? $this->pay_button_url : 'https://www.paypalobjects.com/en_US/i/btn/x-click-but06.gif',
					'alt' => __( 'PayPal - The safer, easier way to pay online', MS_TEXT_DOMAIN ),
			);
		}
		
		?>
			<tr>
				<td class='ms-buy-now-column' colspan='2' >
					<form action="<?php echo $action;?>" method="post">
						<?php wp_nonce_field( "{$this->id}_{$ms_relationship->id}" ); ?>
						<?php 
							foreach( $fields as $field ) {
								MS_Helper_Html::html_input( $field ); 
							}
						?>
						<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >
					</form>
				</td>
			</tr>
		<?php
	}
	
	public function handle_return() {

		if( ( isset($_POST['payment_status'] ) || isset( $_POST['txn_type'] ) ) && ! empty( $_POST['custom'] ) ) {
			if( self::MODE_LIVE == $this->mode ) {
				$domain = 'https://www.paypal.com';
			}
			else {
				$domain = 'https://www.sandbox.paypal.com';
			}
						
			/** Paypal post authenticity verification */
			$ipn_data = (array) stripslashes_deep( $_POST );
			$ipn_data['cmd'] = '_notify-validate';
			$response = wp_remote_post( "$domain/cgi-bin/webscr", array(
					'timeout' => 60,
					'sslverify' => false,
					'httpversion' => '1.1',
					'body' => $ipn_data,
			) );
		
			if ( ! is_wp_error( $response ) && 200 == $response['response']['code'] && ! empty( $response['body'] ) && "VERIFIED" == $response['body'] ) {
				MS_Helper_Debug::log( 'PayPal Transaction Verified' );
			} 
			else {
				$error = 'Response Error: Unexpected transaction response';
				MS_Helper_Debug::log( $error );
				MS_Helper_Debug::log( $response );
				echo $error;
				exit;
			}
		
			$new_status = false;
			$invoice = MS_Model_Invoice::load( $_POST['custom'] );
			$ms_relationship = MS_Model_Membership_Relationship::load( $invoice->ms_relationship_id );
			$membership = $ms_relationship->get_membership();
			$member = MS_Model_Member::load( $ms_relationship->user_id );
			
			$external_id = $_POST['txn_id'];
			$amount = $_POST['mc_gross'];
			$currency = $_POST['mc_currency'];
			$status = null;
			$notes = null;
			
			/** Process PayPal response */
			switch ( $_POST['payment_status'] ) {
				/** Successful payment */
				case 'Completed':
				case 'Processed':
					$status = MS_Model_Invoice::STATUS_PAID;
					break;
				case 'Reversed':
					$notes = __('Last transaction has been reversed. Reason: Payment has been reversed (charge back). ', MS_TEXT_DOMAIN );
					$status = self::STATUS_REVERSED;
					break;
				case 'Refunded':
					$notes = __( 'Last transaction has been reversed. Reason: Payment has been refunded', MS_TEXT_DOMAIN );
					$status = self::STATUS_REFUNDED;
					break;
				case 'Denied':
					$notes = __( 'Last transaction has been reversed. Reason: Payment Denied', MS_TEXT_DOMAIN );
					$status = self::STATUS_DENIED;
					break;
				case 'Pending':
					$pending_str = array(
						'address' => __( 'Customer did not include a confirmed shipping address', MS_TEXT_DOMAIN ),
						'authorization' => __( 'Funds not captured yet', MS_TEXT_DOMAIN ),
						'echeck' => __( 'eCheck that has not cleared yet', MS_TEXT_DOMAIN ),
						'intl' => __( 'Payment waiting for aproval by service provider', MS_TEXT_DOMAIN ),
						'multi-currency' => __( 'Payment waiting for service provider to handle multi-currency process', MS_TEXT_DOMAIN ),
						'unilateral' => __( 'Customer did not register or confirm his/her email yet', MS_TEXT_DOMAIN ),
						'upgrade' => __( 'Waiting for service provider to upgrade the PayPal account', MS_TEXT_DOMAIN ),
						'verify' => __( 'Waiting for service provider to verify his/her PayPal account', MS_TEXT_DOMAIN ),
						'*' => ''
					);
					$reason = $_POST['pending_reason'];
					$notes = __( 'Last transaction is pending. Reason: ', MS_TEXT_DOMAIN ) . ( isset($pending_str[$reason] ) ? $pending_str[$reason] : $pending_str['*'] );
					$status = self::STATUS_PENDING;
					break;
		
				default:
				case 'Partially-Refunded':
				case 'In-Progress':
					break;
			}
			
			if( 'new_case' == $_POST['txn_type'] && 'dispute' == $_POST['case_type'] ) {
				$status = self::STATUS_DISPUTE;
			}
			
			if( empty( $invoice ) ) {
				$invoice = $ms_relationship->get_current_invoice();
			}
			$invoice->external_id = $external_id;
			if( ! empty( $notes ) ) {
				$invoice->add_notes( $notes );
			}
			$invoice->gateway_id = $this->id;
			$invoice->save();
				
			if( ! empty( $status ) ) {
				$invoice->status = $status;
				$invoice->save();
				$this->process_transaction( $invoice );
			}

			do_action( "ms_model_gateway_paypal_single_payment_processed_{$status}", $invoice, $ms_relationship );
		} 
		else {
			// Did not find expected POST variables. Possible access attempt from a non PayPal site.
			header('Status: 404 Not Found');
			$notes = __( 'Error: Missing POST variables. Identification is not possible.', MS_TEXT_DOMAIN );
			MS_Helper_Debug::log( $notes );
			exit;
		}
	}
	
	public function get_paypal_sites() {
		return apply_filters( 'ms_model_gateway_paylpay_standard_get_paypal_sites', array(
		          'AU'	=> __( 'Australia', MS_TEXT_DOMAIN ),
		          'AT'	=> __( 'Austria', MS_TEXT_DOMAIN ),
		          'BE'	=> __( 'Belgium', MS_TEXT_DOMAIN ),
		          'CA'	=> __( 'Canada', MS_TEXT_DOMAIN ),
		          'CN'	=> __( 'China', MS_TEXT_DOMAIN ),
		          'FR'	=> __( 'France', MS_TEXT_DOMAIN ),
		          'DE'	=> __( 'Germany', MS_TEXT_DOMAIN ),
		          'HK'	=> __( 'Hong Kong', MS_TEXT_DOMAIN ),
		          'IT'	=> __( 'Italy', MS_TEXT_DOMAIN ),
				  'jp_JP' => __( 'Japan',MS_TEXT_DOMAIN ),
		          'MX'	=> __( 'Mexico', MS_TEXT_DOMAIN ),
		          'NL'	=> __( 'Netherlands', MS_TEXT_DOMAIN ),
				  'NZ'	=> __( 'New Zealand', MS_TEXT_DOMAIN ),
		          'PL'	=> __( 'Poland', MS_TEXT_DOMAIN ),
		          'SG'	=> __( 'Singapore', MS_TEXT_DOMAIN ),
		          'ES'	=> __( 'Spain', MS_TEXT_DOMAIN ),
		          'SE'	=> __( 'Sweden', MS_TEXT_DOMAIN ),
		          'CH'	=> __( 'Switzerland', MS_TEXT_DOMAIN ),
		          'GB'	=> __( 'United Kingdom', MS_TEXT_DOMAIN ),
		          'US'	=> __( 'United States', MS_TEXT_DOMAIN ),
			)
		);
	}
	
	/**
	 * Process transaction.
	 *
	 * Process transaction status change related to this membership relationship.
	 * Change status accordinly to transaction status.
	 *
	 * @param MS_Model_Invoice $invoice The Transaction.
	 */
	public function process_transaction( $invoice ) {
		$ms_relationship = MS_Model_Membership_Relationship::load( $invoice->ms_relationship_id );
		$member = MS_Model_Member::load( $invoice->user_id );
		switch( $invoice->status ) {
			case self::STATUS_REVERSED:
			case self::STATUS_REFUNDED:
			case self::STATUS_DISPUTE:
				$ms_relationship->status = MS_Model_Membership_Relationship::STATUS_DEACTIVATED;
				$member->active = false;
				break;
			default:
				parent::process_transaction( $invoice );
				break;
		}
		$member->save();
		$ms_relationship->gateway_id = $invoice->gateway_id;
		$ms_relationship->save();
	}
	
	/**
	 * Validate specific property before set.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param string $name The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch( $property ) {
				case 'paypal_site':
					if( array_key_exists( $value, self::get_paypal_sites() ) ) {
						$this->$property = $value;
					}
					break;
				case 'paypal_email':
					if( is_email( $value ) ) {
						$this->$property = $value;
					}
					break;
				default:
					parent::__set( $property, $value );
					break;
			}
		}
	}
	
}