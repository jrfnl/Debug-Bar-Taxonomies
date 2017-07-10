<?php
/**
 * Debug Bar Taxonomies, a WordPress plugin.
 *
 * @package     WordPress\Plugins\Debug Bar Taxonomies
 * @author      Juliette Reinders Folmer <wpplugins_nospam@adviesenzo.nl>
 * @link        https://github.com/jrfnl/Debug-Bar-Taxonomies
 * @since       1.0
 * @version     1.1
 *
 * @copyright   2016-2017 Juliette Reinders Folmer
 * @license     http://creativecommons.org/licenses/GPL/2.0/ GNU General Public License, version 2 or higher
 */

// Avoid direct calls to this file.
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}


if ( ! class_exists( 'Debug_Bar_Taxonomies' ) && class_exists( 'Debug_Bar_Panel' ) ) {

	/**
	 * This class extends the functionality provided by the parent plugin "Debug Bar" by adding a
	 * panel showing information about the defined WP Taxonomies.
	 */
	class Debug_Bar_Taxonomies extends Debug_Bar_Panel {

		const STYLES_VERSION = '1.1';

		const NAME = 'debug-bar-taxonomies';


		/**
		 * Taxonomy names - used as column labels.
		 *
		 * @var array
		 */
		private $names = array();

		/**
		 * Custom Taxonomies, i.e. taxonomies which are not by default included in WP.
		 *
		 * @var array
		 */
		private $custom_tax = array();

		/**
		 * Standard taxonomy properties.
		 *
		 * @var array
		 */
		private $properties = array();

		/**
		 * Non-standard taxonomy properties.
		 *
		 * @var array
		 */
		private $custom_prop = array();

		/**
		 * Taxonomy labels.
		 *
		 * @var array
		 */
		private $labels = array();

		/**
		 * Taxonomy capabilities.
		 *
		 * @var array
		 */
		private $caps = array();

		/**
		 * Number of non-standard taxonomies registered.
		 *
		 * @var int
		 */
		private $count_ct = 0;

		/**
		 * Whether to repeat the row labels on the other side of the table.
		 *
		 * @var bool
		 */
		private $double = false;


		/**
		 * Constructor.
		 */
		public function init() {
			$this->load_textdomain( self::NAME );
			$this->title( __( 'Taxonomies', 'debug-bar-taxonomies' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}


		/**
		 * Load the plugin text strings.
		 *
		 * Compatible with use of the plugin in the must-use plugins directory.
		 *
		 * {@internal No longer needed since WP 4.6, though the language loading in
		 * WP 4.6 only looks at the `wp-content/languages/` directory and disregards
		 * any translations which may be included with the plugin.
		 * This is acceptable for plugins hosted on org, especially if the plugin
		 * is new and never shipped with it's own translations, but not when the plugin
		 * is hosted elsewhere.
		 * Can be removed if/when the minimum required version for this plugin is ever
		 * upped to 4.6. The `languages` directory can be removed in that case too.
		 * See: {@link https://core.trac.wordpress.org/ticket/34213} and
		 * {@link https://core.trac.wordpress.org/ticket/34114} }}
		 *
		 * @param string $domain Text domain to load.
		 */
		protected function load_textdomain( $domain ) {
			if ( function_exists( '_load_textdomain_just_in_time' ) ) {
				return;
			}

			if ( is_textdomain_loaded( $domain ) ) {
				return;
			}

			$lang_path = dirname( plugin_basename( __FILE__ ) ) . '/languages';
			if ( false === strpos( __FILE__, basename( WPMU_PLUGIN_DIR ) ) ) {
				load_plugin_textdomain( $domain, false, $lang_path );
			} else {
				load_muplugin_textdomain( $domain, $lang_path );
			}
		}


		/**
		 * Enqueue css file.
		 */
		public function enqueue_scripts() {
			$suffix = ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min' );
			wp_enqueue_style( self::NAME, plugins_url( 'css/debug-bar-taxonomies' . $suffix . '.css', __FILE__ ), array( 'debug-bar' ), self::STYLES_VERSION );
		}


		/**
		 * Should the tab be visible ?
		 * You can set conditions here so something will for instance only show on the front- or the
		 * back-end.
		 */
		public function prerender() {
			$this->set_visible( true );
		}


		/**
		 * Render the tab content.
		 */
		public function render() {

			$wp_taxonomies = $GLOBALS['wp_taxonomies'];
			$this->names   = array_keys( $wp_taxonomies );
			$count         = count( $wp_taxonomies );
			$this->double  = ( ( $count > 4 ) ? true : false );

			if ( ! class_exists( 'Debug_Bar_Pretty_Output' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'inc/debug-bar-pretty-output/class-debug-bar-pretty-output.php';
			}

			// Limit recursion depth if possible - method available since DBPO v1.4.
			if ( method_exists( 'Debug_Bar_Pretty_Output', 'limit_recursion' ) ) {
				Debug_Bar_Pretty_Output::limit_recursion( 2 );
			}


			echo '
		<h2><span>', esc_html__( 'Total Taxonomies:', 'debug-bar-taxonomies' ), '</span>', absint( $count ), '</h2>';

			if ( is_array( $wp_taxonomies ) && $count > 0 ) {

				$this->collect_info( $wp_taxonomies );

				if ( ! empty( $this->custom_tax ) ) {
					$this->count_ct = count( $this->custom_tax );
					echo '
		<h2><span>', esc_html__( 'Custom Taxonomies:', 'debug-bar-taxonomies' ), '</span>', absint( $this->count_ct ), '</h2>';
				}

				$this->render_standard_properties_table();
				$this->render_custom_properties_table();
				$this->render_capabilities_table();
				$this->render_labels_table();

			} else {
				echo '<p>', esc_html__( 'No taxonomies found.', 'debug-bar-taxonomies' ), '</p>';
			}

			unset( $names, $properties, $caps );

			// Unset recursion depth limit if possible - method available since DBPO v1.4.
			if ( method_exists( 'Debug_Bar_Pretty_Output', 'unset_recursion_limit' ) ) {
				Debug_Bar_Pretty_Output::unset_recursion_limit();
			}
		}


		/**
		 * Collect the necessary information from the $taxonomies array.
		 *
		 * @param array $taxonomies Registered taxonomies.
		 */
		private function collect_info( $taxonomies ) {
			foreach ( $taxonomies as $name => $tax_obj ) {
				$props = get_object_vars( $tax_obj );

				if ( ! empty( $props ) && is_array( $props ) ) {
					foreach ( $props as $key => $value ) {
						// Add to list of custom taxonomies.
						if ( '_builtin' === $key && true !== $value ) {
							$this->custom_tax[] = $name;
						}

						if ( is_object( $value ) && in_array( $key, array( 'cap', 'labels' ), true ) ) {
							$this->collect_caps_labels( $key, $name, $value );

						} else {
							// Standard properties.
							if ( property_exists( $taxonomies['category'], $key ) ) {
								$this->properties[ $key ][ $name ] = $value;

							} else {
								// Custom properties.
								$this->custom_prop[ $key ][ $name ] = $value;
							}
						}
					}
					unset( $key, $value );
				}
				unset( $props );
			}
			unset( $name, $tax_obj );
		}


		/**
		 * Collect the relevant information about capabilities and labels.
		 *
		 * @param string $key            Whether this is a capability object or a label object.
		 * @param string $name           Name of the taxonomy this object applies to.
		 * @param object $caps_or_labels A capabilities or label object.
		 */
		private function collect_caps_labels( $key, $name, $caps_or_labels ) {
			$object_vars = get_object_vars( $caps_or_labels );

			if ( ! empty( $object_vars ) && is_array( $object_vars ) ) {
				foreach ( $object_vars as $k => $v ) {
					if ( 'cap' === $key ) {
						$this->caps[ $v ][ $name ] = $v;

					} elseif ( 'labels' === $key ) {
						$this->labels[ $k ][ $name ] = $v;
					}
				}
				unset( $k, $v );
			}
		}


		/**
		 * Create the properties table for the standard properties.
		 */
		private function render_standard_properties_table() {
			if ( count( $this->properties ) > 0 ) {
				$this->render_property_table(
					$this->properties,
					$this->names,
					__( 'Standard Taxonomy Properties:', 'debug-bar-taxonomies' ),
					$this->double
				);
			}
		}


		/**
		 * Create the properties table for the custom properties.
		 */
		private function render_custom_properties_table() {
			if ( count( $this->custom_prop ) > 0 ) {
				$this->render_property_table(
					$this->custom_prop,
					$this->custom_tax,
					__( 'Custom Taxonomy Properties:', 'debug-bar-taxonomies' ),
					( ( $this->count_ct > 4 ) ? true : false )
				);
			}
		}


		/**
		 * Create the capabilities table.
		 */
		private function render_capabilities_table() {
			if ( count( $this->caps ) > 0 ) {
				$this->render_capability_table(
					$this->caps,
					$this->names,
					$this->double
				);
			}
		}


		/**
		 * Create the table for the defined labels.
		 */
		private function render_labels_table() {
			if ( count( $this->labels ) > 0 ) {
				$this->render_property_table(
					$this->labels,
					$this->names,
					__( 'Defined Labels:', 'debug-bar-taxonomies' ),
					$this->double
				);
			}
		}


		/**
		 * Create a property table for standard/custom properties.
		 *
		 * @param array  $properties Array of taxonomy properties.
		 * @param array  $names      Array of taxonomy names.
		 * @param string $table_name Translated name for this table.
		 * @param bool   $double     Whether or not to repeat the row labels at the end of the table.
		 */
		protected function render_property_table( $properties, $names, $table_name, $double ) {

			/* Create header row. */
			$header_row = '
		<tr>
			<th>' . esc_html__( 'Property', 'debug-bar-taxonomies' ) . '</th>';
			foreach ( $names as $name ) {
				$header_row .= '
			<th>' . esc_html( $name ) . '</th>';
			}
			unset( $name );
			if ( true === $double ) {
				$header_row .= '
			<th class="' . self::NAME . '-table-end">' . esc_html__( 'Property', 'debug-bar-taxonomies' ) . '</th>';
			}
			$header_row .= '
		</tr>';


			echo // WPCS: XSS ok.
			'
		<h3>', esc_html( $table_name ), '</h3>
		<table class="debug-bar-table ', self::NAME, '">
			<thead>
			', $header_row, '
			</thead>
			<tfoot>
			', $header_row, '
			</tfoot>
			<tbody>';
			unset( $header_row );


			/* Sort. */
			uksort( $properties, 'strnatcasecmp' );


			/* Output. */
			foreach ( $properties as $key => $value ) {
				echo '
			<tr>
				<th>', esc_html( $key ), '</th>';

				foreach ( $names as $name ) {
					echo '
				<td>';

					if ( isset( $value[ $name ] ) ) {
						if ( defined( 'Debug_Bar_Pretty_Output::VERSION' ) ) {
							echo Debug_Bar_Pretty_Output::get_output( $value[ $name ], '', true, '', true ); // WPCS: XSS ok.
						} else {
							// An old version of the pretty output class was loaded.
							Debug_Bar_Pretty_Output::output( $value[ $name ], '', true, '', true );
						}
					} else {
						echo '&nbsp;';
					}

					echo '
				</td>';
				}
				unset( $name );

				if ( true === $double ) {
					echo // WPCS: XSS ok.
					'
				<th class="', self::NAME, '-table-end">', esc_html( $key ), '</th>'; // WPCS: XSS ok.
				}

				echo '
			</tr>';
			}
			unset( $key, $value );

			echo '
			</tbody>
		</table>
	';
		}

		/**
		 * Create a capability table for standard/custom properties.
		 *
		 * @param array $caps   Array of taxonomy capabilities.
		 * @param array $names  Array of taxonomy names.
		 * @param bool  $double Whether or not to repeat the row labels at the end of the table.
		 */
		protected function render_capability_table( $caps, $names, $double ) {
			/* Create header row. */
			$header_row = '
			<tr>
				<th>' . esc_html__( 'Capability', 'debug-bar-taxonomies' ) . '</th>';
			foreach ( $names as $name ) {
				$header_row .= '
				<th>' . esc_html( $name ) . '</th>';
			}
			unset( $name );
			if ( true === $double ) {
				$header_row .= '
				<th>' . esc_html__( 'Capability', 'debug-bar-taxonomies' ) . '</th>';
			}
			$header_row .= '
			</tr>';


			echo // WPCS: XSS ok.
			'
		<h3>', esc_html__( 'Taxonomy Capabilities:', 'debug-bar-taxonomies' ), '</h3>
		<table class="debug-bar-table ', self::NAME, ' ', self::NAME, '-caps">
			<thead>
			', $header_row, '
			</thead>
			<tfoot>
			', $header_row, '
			</tfoot>
			<tbody>';
			unset( $header_row );


			/* Sort. */
			uksort( $caps, 'strnatcasecmp' );


			/* Output. */
			foreach ( $caps as $key => $value ) {
				echo '
			<tr>
				<th>', esc_html( $key ), '</th>';

				foreach ( $names as $name ) {
					$img = ( ( isset( $value[ $name ] ) ) ? 'check' : 'cross' );
					$alt = ( ( isset( $value[ $name ] ) ) ? esc_html__( 'Has capability', 'debug-bar-taxonomies' ) : esc_html__( 'Does not have capability', 'debug-bar-taxonomies' ) );

					echo '
				<td><img src="', esc_url( plugins_url( 'images/badge-circle-' . $img . '-16.png', __FILE__ ) ), '" width="16" height="16" alt="', esc_attr( $alt ), '" /></td>';
					unset( $img, $alt );
				}
				unset( $name );

				if ( true === $double ) {
					echo // WPCS: XSS ok.
					'
				<th class="', self::NAME, '-table-end">', esc_html( $key ), '</th>';
				}

				echo '
			</tr>';
			}
			unset( $key, $value );

			echo '
			</tbody>
		</table>
';
		}
	} // End of class Debug_Bar_Taxonomies.

} // End of if class_exists wrapper.
