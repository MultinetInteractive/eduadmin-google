<?php
defined( 'ABSPATH' ) or die( 'This plugin must be run within the scope of WordPress.' );

if ( ! class_exists( 'EDU_Google' ) ) {
	class EDU_Google extends EDU_Integration {
		/**
		 * Constructor
		 */
		public function __construct() {
			parent::__construct();

			$this->id          = 'eduadmin-google';
			$this->displayName = __( 'Google Analytics / Tag Manager', 'eduadmin-google' );
			$this->description = __( 'Plugin to enable more advanced Google Analytics / Tag Manager integration', 'eduadmin-google' );
			$this->type        = 'plugin';

			$this->init_form_fields();
			$this->init_settings();

			add_action( 'wp_head', array( $this, 'add_gtag_script' ) );

			add_action( 'eduadmin-list-course-view', array( $this, 'track_list_course_view' ) );
			add_action( 'eduadmin-list-event-view', array( $this, 'track_list_event_view' ) );
			add_action( 'eduadmin-detail-view', array( $this, 'track_detail_view' ) );
			add_action( 'eduadmin-programme-detail-view', array( $this, 'track_programme_detail_view' ) );
			add_action( 'eduadmin-bookingform-view', array( $this, 'track_booking_view' ) );
			add_action( 'eduadmin-programme-bookingform-view', array( $this, 'track_programme_booking_view' ) );
			add_action( 'eduadmin-booking-completed', array( $this, 'track_booking_completed' ) );

			add_shortcode( 'eduadmin-google-testpage', array( $this, 'test_page' ) );
		}

		/**
		 * Initializes the settings fields for the plugin
		 * @return void
		 */
		public function init_form_fields() {
			$this->setting_fields = [
				'enabled'            => [
					'title'       => __( 'Enabled', 'eduadmin-google' ),
					'type'        => 'checkbox',
					'description' => __( 'Enable Google Analytics / Tag Manager integration', 'eduadmin-google' ),
					'default'     => 'no',
				],
				'google-tag-manager' => [
					'title'       => __( 'Google Tag Manager ID', 'eduadmin-google' ),
					'type'        => 'text',
					'description' => __( 'The ID of the Google Tag Manager', 'eduadmin-google' ),
					'default'     => '',
				],
				'google-tag'         => [
					'title'       => __( 'Google Tag ID', 'eduadmin-google' ),
					'type'        => 'text',
					'description' => __( 'The ID of the Google Tag', 'eduadmin-google' ),
					'default'     => '',
				],
			];
		}

		public function add_gtag_script() {
			if ( 'no' === $this->get_option( 'enabled', 'no' ) ) {
				return;
			}

			if ( ! empty( $this->get_option( 'google-tag-manager' ) ) ) {
				?>
                <!-- Google Tag Manager -->
                <script>(function (w, d, s, l, i) {
                        w[l] = w[l] || [];
                        w[l].push({
                            'gtm.start':
                                new Date().getTime(), event: 'gtm.js'
                        });
                        var f = d.getElementsByTagName(s)[0],
                            j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
                        j.async = true;
                        j.src =
                            'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
                        f.parentNode.insertBefore(j, f);
                    })(window, document, 'script', 'eduAdminDataLayer', '<?php echo esc_js( $this->get_option( 'google-tag-manager' ) ); ?>');</script>
                <!-- End Google Tag Manager -->
				<?php
			}

			if ( ! empty( $this->get_option( 'google-tag' ) ) ) {
				$currency = EDU()->get_option( 'eduadmin-currency', 'SEK' );
				?>
                <script async
                        src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $this->get_option( 'google-tag' ) ); ?>&l=eduAdminDataLayer"></script>
                <script>
                    window.eduAdminDataLayer = window.eduAdminDataLayer || [];

                    function gtag() {
                        eduAdminDataLayer.push(arguments);
                    }

                    gtag('js', new Date());
                    gtag('config', '<?php echo esc_js( $this->get_option( 'google-tag' ) ); ?>', {
                        'currency': '<?php echo esc_js( $currency ); ?>',
                    });
                </script>
				<?php
			}
		}

		public function is_configured_and_enabled() {
			if ( 'no' === $this->get_option( 'enabled', 'no' ) ) {
				return false;
			}

			if ( empty( $this->get_option( 'google-tag' ) ) && empty( $this->get_option( 'google-tag-manager' ) ) ) {
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

				$gtag_items = $this->getItemsCategorized( $course["Categories"], $course_item, $gtag_items );
			}

			if ( count( $gtag_items ) > 0 ) {
				?>
                <script type="text/javascript">gtag('event', 'view_item_list', {
                        'item_list_id': 'course_list',
                        'item_list_name': 'Course list',
                        'items': <?php echo json_encode( $gtag_items, JSON_PRETTY_PRINT ); ?> });</script>
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

				$gtag_items = $this->getItemsCategorized( $course["Categories"], $course_item, $gtag_items );
			}

			if ( count( $gtag_items ) > 0 ) {
				?>
                <script type="text/javascript">gtag('event', 'view_item_list', {
                        'item_list_id': 'event_list',
                        'item_list_name': 'Event list',
                        'items': <?php echo json_encode( $gtag_items, JSON_PRETTY_PRINT ); ?> });</script>
				<?php
			}
		}

		public function track_detail_view( $course_template ) {
			if ( ! $this->is_configured_and_enabled() ) {
				return;
			}

			$gtag_items = [];

			$course_item = [
				'item_id'   => 'CTI_' . $course_template["CourseTemplateId"],
				'item_name' => $course_template["CourseName"],
			];

			$gtag_items = $this->getItemsCategorized( $course_template["Categories"], $course_item, $gtag_items );

			if ( count( $gtag_items ) > 0 ) {
				?>
                <script type="text/javascript">gtag('event', 'view_item', {
                        'items': <?php echo json_encode( $gtag_items, JSON_PRETTY_PRINT ); ?> });</script>
				<?php
			}
		}

		public function track_programme_detail_view( $programme ) {
			if ( ! $this->is_configured_and_enabled() ) {
				return;
			}

			$gtag_items = [
				[
					'item_id'   => 'PI_' . $programme["ProgrammeId"],
					'item_name' => $programme["ProgrammeName"],
				]
			];

			if ( count( $gtag_items ) > 0 ) {
				?>
                <script type="text/javascript">gtag('event', 'view_item', {
                        'items': <?php echo json_encode( $gtag_items, JSON_PRETTY_PRINT ); ?> });</script>
				<?php
			}
		}

		public function track_booking_view( $course_template ) {
			if ( ! $this->is_configured_and_enabled() ) {
				return;
			}

			$gtag_items = [];

			$course_item = [
				'item_id'   => 'CTI_' . $course_template["CourseTemplateId"],
				'item_name' => $course_template["CourseName"],
			];

			$gtag_items = $this->getItemsCategorized( $course_template["Categories"], $course_item, $gtag_items );

			if ( count( $gtag_items ) > 0 ) {
				?>
                <script type="text/javascript">gtag('event', 'begin_checkout', {
                        'items': <?php echo json_encode( $gtag_items, JSON_PRETTY_PRINT ); ?> });</script>
				<?php
			}
		}

		public function track_programme_booking_view( $programme ) {
			if ( ! $this->is_configured_and_enabled() ) {
				return;
			}

			$gtag_items = [
				[
					'item_id'   => 'PI_' . $programme["ProgrammeId"],
					'item_name' => $programme["ProgrammeName"],
				]
			];

			if ( count( $gtag_items ) > 0 ) {
				?>
                <script type="text/javascript">gtag('event', 'begin_checkout', {
                        'items': <?php echo json_encode( $gtag_items, JSON_PRETTY_PRINT ); ?> });</script>
				<?php
			}
		}

		public function track_booking_completed( $booking_info ) {
			if ( ! $this->is_configured_and_enabled() ) {
				return;
			}

			$currency = EDU()->get_option( 'eduadmin-currency', 'SEK' );

			$event_info     = null;
			$transaction_id = null;
			$is_programme   = false;

			if ( key_exists( 'BookingId', $booking_info ) ) {
				$event_info     = EDUAPI()->OData->Events->GetItem( $booking_info['EventId'] );
				$transaction_id = "B_" . $booking_info['BookingId'];
			} else if ( key_exists( 'ProgrammeBookingId', $booking_info ) ) {
				$event_info     = EDUAPI()->OData->ProgrammeStarts->GetItem( $booking_info['ProgrammeStartId'] );
				$transaction_id = "P_" . $booking_info['ProgrammeBookingId'];
				$is_programme   = true;
			}

			$order_rows = [];

			if ( ! $is_programme ) {
				$row = [
					'item_number' => 'EV_' . $event_info['CourseTemplateId'],
					'item_name'   => $event_info['EventName'],
					'quantity'    => 1,
					'price'       => 0,
					'discount'    => 0,
				];

			} else {
				$row = [
					'item_number' => 'PSI_' . $event_info['ProgrammeStartId'],
					'item_name'   => $event_info['ProgrammeStartName'],
					'quantity'    => 1,
					'price'       => 0,
					'discount'    => 0,
				];

			}

			$order_rows[] = $row;

			foreach ( $booking_info['OrderRows'] as $order_row ) {
				$row = [
					'item_number' => 'OR_' . $order_row['OrderRowId'],
					'item_name'   => $order_row['Description'],
					'quantity'    => $order_row['Quantity'],
					'price'       => $order_row['TotalPriceIncDiscount'] / $order_row['Quantity'],
					'discount'    => ( ( $order_row['DiscountPercent'] / 100 ) * $order_row['TotalPrice'] ) / $order_row['Quantity'],
				];

				$order_rows[] = $row;
			}

			if ( count( $order_rows ) > 0 ) {
				?>
                <script type="text/javascript">gtag('event', 'purchase', {
                        'transaction_id': '<?php echo esc_js( $transaction_id ); ?>',
                        'currency': '<?php echo esc_js( $currency ); ?>',
                        'value': <?php echo esc_js( $booking_info["TotalPriceExVat"] ); ?>,
                        'tax': <?php echo esc_js( $booking_info["VatSum"] ); ?>,
                        'items': <?php echo json_encode( $order_rows, JSON_PRETTY_PRINT ); ?>
                    });</script>
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
				return __( 'No booking with that ID was found.', 'eduadmin-google' );
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
	}
}
