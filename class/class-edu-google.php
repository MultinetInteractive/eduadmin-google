<?php
defined( 'ABSPATH' ) or die( 'This plugin must be run within the scope of WordPress.' );

if ( ! class_exists( 'EDUGTAG_Google' ) ) {
	class EDUGTAG_Google extends EDU_Integration {
		/**
		 * Constructor
		 */
		public function __construct() {
			parent::__construct();

			$this->id          = 'eduadmin-analytics';
			$this->displayName = __( 'Google Analytics / Tag Manager', 'eduadmin-analytics' );
			$this->description = __( 'Plugin to enable more advanced Google Analytics / Tag Manager integration', 'eduadmin-analytics' );
			$this->type        = 'plugin';

			$this->init_form_fields();
			$this->init_settings();

			add_action( 'eduadmin-list-course-view', array( $this, 'track_list_course_view' ) );
			add_action( 'eduadmin-list-event-view', array( $this, 'track_list_event_view' ) );
			add_action( 'eduadmin-detail-view', array( $this, 'track_detail_view' ) );
			add_action( 'eduadmin-programme-detail-view', array( $this, 'track_programme_detail_view' ) );
			add_action( 'eduadmin-bookingform-view', array( $this, 'track_booking_view' ) );
			add_action( 'eduadmin-programme-bookingform-view', array( $this, 'track_programme_booking_view' ) );
			add_action( 'eduadmin-booking-completed', array( $this, 'track_booking_completed' ) );

			add_shortcode( 'eduadmin-analytics-testpage', array( $this, 'test_page' ) );
		}

		/**
		 * Initializes the settings fields for the plugin
		 * @return void
		 */
		public function init_form_fields() {
			$this->setting_fields = [
				'enabled' => [
					'title'       => __( 'Enabled', 'eduadmin-analytics' ),
					'type'        => 'checkbox',
					'description' => __( 'Enable Google Analytics / Tag Manager integration', 'eduadmin-analytics' ),
					'default'     => 'no',
				],
			];
		}

		public function is_configured_and_enabled() {
			if ( 'no' === $this->get_option( 'enabled', 'no' ) ) {
				return false;
			}

			return true;
		}

		public function track_list_course_view( $courses ) {
			if ( ! $this->is_configured_and_enabled() ) {
				return;
			}

			$gtag_items = [];

			foreach ( $courses as $course ) {
				$course_item = [
					'item_id'   => 'CTI_' . $course["CourseTemplateId"],
					'item_name' => $course["CourseName"],
				];

				if ( isset( $course['Events'] ) ) {
					foreach ( $course['Events'] as $event ) {
						$course_item = $this->getItemsPriced( $event['PriceNames'], $course_item );
					}
				}

				if ( ! isset( $course_item['price'] ) ) {
					$course_item = $this->getItemsPriced( $course['PriceNames'], $course_item );
				}
				$gtag_items = $this->getItemsCategorized( $course["Categories"], $course_item, $gtag_items );
			}

			if ( count( $gtag_items ) > 0 ) {
				?>
                <script type="text/javascript">if (gtag) {
                        gtag('event', 'view_item_list', {
                            'item_list_id': 'course_list',
                            'item_list_name': 'Course list',
                            'items': <?php echo wp_json_encode( $gtag_items, JSON_PRETTY_PRINT ); ?> });
                    }
                </script>
				<?php
			}
		}

		public function track_list_event_view( $events ) {
			if ( ! $this->is_configured_and_enabled() ) {
				return;
			}

			$gtag_items = [];

			foreach ( $events as $course ) {
				$course_item = [
					'item_id'   => 'EV_' . $course["CourseTemplateId"],
					'item_name' => $course["CourseName"],
				];

				$course_item = $this->getItemsPriced( $course['PriceNames'], $course_item );

				if ( ! isset( $course_item['price'] ) ) {
					$course_item = $this->getItemsPriced( $course['CourseTemplate']['PriceNames'], $course_item );
				}

				$gtag_items = $this->getItemsCategorized( $course["Categories"], $course_item, $gtag_items );
			}

			if ( count( $gtag_items ) > 0 ) {
				?>
                <script type="text/javascript">if (gtag) {
                        gtag('event', 'view_item_list', {
                            'item_list_id': 'event_list',
                            'item_list_name': 'Event list',
                            'items': <?php echo wp_json_encode( $gtag_items, JSON_PRETTY_PRINT ); ?> });
                    }</script>
				<?php
			}
		}

		public function track_detail_view( $course_template ) {
			if ( ! $this->is_configured_and_enabled() ) {
				return;
			}

			$currency = EDU()->get_option( 'eduadmin-currency', 'SEK' );

			$gtag_items = [];

			$course_item = [
				'item_id'   => 'CTI_' . $course_template["CourseTemplateId"],
				'item_name' => $course_template["CourseName"],
			];

			foreach ( $course_template['Events'] as $event ) {
				$course_item = $this->getItemsPriced( $event['PriceNames'], $course_item );
			}

			if ( ! isset( $course_item['price'] ) ) {
				$course_item = $this->getItemsPriced( $course_template['PriceNames'], $course_item );
			}

			$gtag_items = $this->getItemsCategorized( $course_template["Categories"], $course_item, $gtag_items );

			if ( count( $gtag_items ) > 0 ) {
				?>
                <script type="text/javascript">if (gtag) {
                        gtag('event', 'view_item', {
                            'currency': '<?php echo esc_js( $currency ); ?>',
                            'items': <?php echo wp_json_encode( $gtag_items, JSON_PRETTY_PRINT ); ?> });
                    }</script>
				<?php
			}
		}

		public function track_programme_detail_view( $programme ) {
			if ( ! $this->is_configured_and_enabled() ) {
				return;
			}

			$currency = EDU()->get_option( 'eduadmin-currency', 'SEK' );

			$gtag_items = [];

			$course_item = [
				'item_id'   => 'PI_' . $programme["ProgrammeId"],
				'item_name' => $programme["ProgrammeName"],
			];

			if ( ! isset( $course_item['price'] ) ) {
				$course_item = $this->getItemsPriced( $programme['PriceNames'], $course_item );
			}

			$gtag_items[] = $course_item;

			if ( count( $gtag_items ) > 0 ) {
				?>
                <script type="text/javascript">if (gtag) {
                        gtag('event', 'view_item', {
                            'currency': '<?php echo esc_js( $currency ); ?>',
                            'items': <?php echo wp_json_encode( $gtag_items, JSON_PRETTY_PRINT ); ?> });
                    }</script>
				<?php
			}
		}

		public function track_booking_view( $course_template ) {
			if ( ! $this->is_configured_and_enabled() ) {
				return;
			}

			$currency = EDU()->get_option( 'eduadmin-currency', 'SEK' );

			$gtag_items = [];

			$course_item = [
				'item_id'   => 'CTI_' . $course_template["CourseTemplateId"],
				'item_name' => $course_template["CourseName"],
			];

			foreach ( $course_template['Events'] as $event ) {
				$course_item = $this->getItemsPriced( $event['PriceNames'], $course_item );
			}

			if ( ! isset( $course_item['price'] ) ) {
				$course_item = $this->getItemsPriced( $course_template['PriceNames'], $course_item );
			}

			$gtag_items = $this->getItemsCategorized( $course_template["Categories"], $course_item, $gtag_items );

			if ( count( $gtag_items ) > 0 ) {
				?>
                <script type="text/javascript">if (gtag) {
                        gtag('event', 'begin_checkout', {
                            'currency': '<?php echo esc_js( $currency ); ?>',
                            'items': <?php echo wp_json_encode( $gtag_items, JSON_PRETTY_PRINT ); ?> });
                    }</script>
				<?php
			}
		}

		public function track_programme_booking_view( $programme ) {
			if ( ! $this->is_configured_and_enabled() ) {
				return;
			}

			$currency = EDU()->get_option( 'eduadmin-currency', 'SEK' );

			$gtag_items = [];

			$course_item = [
				'item_id'   => 'PI_' . $programme["ProgrammeId"],
				'item_name' => $programme["ProgrammeName"],
			];

			if ( ! isset( $course_item['price'] ) ) {
				$course_item = $this->getItemsPriced( $programme['PriceNames'], $course_item );
			}

			$gtag_items[] = $course_item;

			if ( count( $gtag_items ) > 0 ) {
				?>
                <script type="text/javascript">if (gtag) {
                        gtag('event', 'begin_checkout', {
                            'currency': '<?php echo esc_js( $currency ); ?>',
                            'items': <?php echo wp_json_encode( $gtag_items, JSON_PRETTY_PRINT ); ?> });
                    }</script>
				<?php
			}
		}

		public function track_booking_completed( $booking_info ) {
			if ( ! $this->is_configured_and_enabled() ) {
				return;
			}

			$currency = EDU()->get_option( 'eduadmin-currency', 'SEK' );

			$transaction_id = null;

			if ( key_exists( 'BookingId', $booking_info ) ) {
				$transaction_id = "B_" . $booking_info['BookingId'];
			} else if ( key_exists( 'ProgrammeBookingId', $booking_info ) ) {
				$transaction_id = "P_" . $booking_info['ProgrammeBookingId'];
			}

			$order_rows = [];

			foreach ( $booking_info['OrderRows'] as $order_row ) {
				$row = [
					'item_number' => isset( $order_row['ItemNumber'] ) ? $order_row['ItemNumber'] : 'OR_' . $order_row['OrderRowId'],
					'item_name'   => $order_row['Description'],
					'quantity'    => $order_row['Quantity'],
					'price'       => $order_row['TotalPriceIncDiscount'] / $order_row['Quantity'],
					'discount'    => ( ( $order_row['DiscountPercent'] / 100 ) * $order_row['TotalPrice'] ) / $order_row['Quantity'],
				];

				$order_rows[] = $row;
			}

			if ( count( $order_rows ) > 0 ) {
				?>
                <script type="text/javascript">if (gtag) {
                        gtag('event', 'purchase', {
                            'transaction_id': '<?php echo esc_js( $transaction_id ); ?>',
                            'currency': '<?php echo esc_js( $currency ); ?>',
                            'value': <?php echo esc_js( $booking_info["TotalPriceExVat"] ); ?>,
                            'tax': <?php echo esc_js( $booking_info["VatSum"] ); ?>,
                            'items': <?php echo wp_json_encode( $order_rows, JSON_PRETTY_PRINT ); ?>
                        });
                    }</script>
				<?php
			}
		}

		/**
		 * Test page that outputs the javascript that will be used to send data to Google Analytics / Tag Manager
		 *
		 * @param $attributes
		 *
		 * @return string
		 */
		public function test_page( $attributes ): string {
			if ( ! $this->is_configured_and_enabled() ) {
				return "Plugin not configured, missing Google Tag Manager ID or Google Tag ID (or not enabled)";
			}

			$attributes = shortcode_atts(
				[
					'bookingid'          => 0,
					'programmebookingid' => 0
				],
				normalize_empty_atts(
					$attributes
				),
				'test_page'
			);

			$event_booking = null;

			if ( $attributes['bookingid'] > 0 ) {
				$event_booking = EDUAPI()->OData->Bookings->GetItem(
					$attributes['bookingid'],
					null,
					'Customer($select=CustomerId;),ContactPerson($select=PersonId;),OrderRows',
					false
				);
			} elseif ( $attributes['programmebookingid'] > 0 ) {
				$event_booking = EDUAPI()->OData->ProgrammeBookings->GetItem(
					$attributes['programmebookingid'],
					null,
					'Customer($select=CustomerId;),ContactPerson($select=PersonId;),OrderRows',
					false
				);
			}

			if ( $event_booking ) {
				$_customer = EDUAPI()->OData->Customers->GetItem(
					$event_booking['Customer']['CustomerId'],
					null,
					"BillingInfo",
					false
				);

				$_contact = EDUAPI()->OData->Persons->GetItem(
					$event_booking['ContactPerson']['PersonId'],
					null,
					null,
					false
				);

				$ebi = new EduAdmin_BookingInfo( $event_booking, $_customer, $_contact );

				if ( $ebi->EventBooking ) {
					unset( $ebi->EventBooking["@curl"] );
					unset( $ebi->EventBooking["@headers"] );

					unset( $ebi->Customer["@curl"] );
					unset( $ebi->Customer["@headers"] );

					unset( $ebi->Contact["@curl"] );
					unset( $ebi->Contact["@headers"] );
				}

				do_action( 'eduadmin-booking-completed', $ebi->EventBooking );

				return '<pre>' . print_r( $ebi, true ) . '</pre>';
			} else {
				return __( 'No booking with that ID was found.', 'eduadmin-analytics' );
			}

		}

		/**
		 * @param $categories
		 * @param array $course_item
		 * @param array $gtag_items
		 *
		 * @return array
		 */
		public function getItemsCategorized( $categories, array $course_item, array $gtag_items ): array {
			$cat_i = 1;

			foreach ( $categories as $category ) {
				if ( $cat_i == 1 ) {
					$course_item['item_category'] = $category["CategoryName"];
				} else {
					$course_item[ 'item_category' . $cat_i ] = $category["CategoryName"];
				}
				$cat_i ++;
			}

			$gtag_items[] = $course_item;

			return $gtag_items;
		}

		public function getItemsPriced( $price_names, array $course_item ): array {
			$lowest_price_name = null;

			if ( $price_names == null ) {
				return $course_item;
			}

			foreach ( $price_names as $price_name ) {
				if ( $lowest_price_name == null || $price_name['Price'] < $lowest_price_name['Price'] ) {
					$lowest_price_name = $price_name;
				}
			}

			$course_item['price'] = $lowest_price_name['Price'];

			return $course_item;
		}
	}
}
