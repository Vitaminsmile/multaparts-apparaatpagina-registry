<?php
namespace Multaparts\ApparaatpaginaRegistry\Infrastructure\Registry;

final class Wpdb_Registry_Reader implements Registry_Reader {
	const EXPECTED_SCHEMA_VERSION = '1.0.0';
	const SCHEMA_VERSION_OPTION = 'psa_compatibility_registry_schema_version';
	const REQUIRED_COLUMNS = array(
		'models'   => array( 'id', 'model_key', 'brand', 'commercial_type' ),
		'variants' => array( 'id', 'device_model_id', 'machinecode' ),
		'links'    => array( 'product_id', 'device_model_id', 'device_variant_id' ),
	);

	private $db;
	private $tables;
	private $readiness;
	private $model_columns = array();
	public function __construct( $db, Registry_Table_Names $tables ) { $this->db = $db; $this->tables = $tables; }
	public function is_ready() {
		if ( null !== $this->readiness ) {
			return $this->readiness;
		}

		$version = get_option( self::SCHEMA_VERSION_OPTION, false );
		if ( false !== $version && '' !== $version && ! $this->is_compatible_version( (string) $version ) ) {
			return $this->readiness = false;
		}

		foreach ( self::REQUIRED_COLUMNS as $table_property => $required_columns ) {
			$table = $this->tables->{$table_property};
			$like  = method_exists( $this->db, 'esc_like' ) ? $this->db->esc_like( $table ) : addcslashes( $table, '_%\\' );
			if ( $this->db->get_var( $this->db->prepare( 'SHOW TABLES LIKE %s', $like ) ) !== $table ) {
				return $this->readiness = false;
			}

			$rows    = (array) $this->db->get_results( 'SHOW COLUMNS FROM `' . str_replace( '`', '``', $table ) . '`', ARRAY_A );
			$columns = array_column( $rows, 'Field' );
			if ( 'models' === $table_property ) {
				$this->model_columns = $columns;
			}
			if ( array_diff( $required_columns, $columns ) ) {
				return $this->readiness = false;
			}
		}

		return $this->readiness = true;
	}
	private function is_compatible_version( $version ) {
		return 0 === strpos( $version, '1.' ) && version_compare( $version, self::EXPECTED_SCHEMA_VERSION, '>=' );
	}
	public function find_model( $model_key ) {
		$this->is_ready();
		$display_column = in_array( 'display_model', $this->model_columns, true ) ? ', display_model' : '';
		$sql = $this->db->prepare( "SELECT id, model_key, brand, commercial_type{$display_column} FROM {$this->tables->models} WHERE model_key = %s LIMIT 1", $model_key );
		$row = $this->db->get_row( $sql, ARRAY_A );
		return $row ?: null;
	}
	public function known_variants( $model_id ) {
		$sql = $this->db->prepare( "SELECT id, machinecode FROM {$this->tables->variants} WHERE device_model_id = %d", $model_id );
		return array_map( static function ( $row ) { return array( 'id' => (int) $row['id'], 'machinecode' => $row['machinecode'] ); }, $this->db->get_results( $sql, ARRAY_A ) );
	}
	public function product_links( $model_id, array $statuses ) {
		$statuses = array_values( array_intersect( array( 'publish', 'private' ), $statuses ) );
		if ( ! $statuses ) { return array(); }
		$marks = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
		$args = array_merge( array( $model_id ), $statuses );
		$sql = $this->db->prepare(
			"SELECT l.product_id, MAX(l.device_variant_id IS NULL) AS model_only,
			 GROUP_CONCAT(DISTINCT l.device_variant_id ORDER BY l.device_variant_id) AS variant_ids,
			 p.post_status
			 FROM {$this->tables->links} l INNER JOIN {$this->db->posts} p ON p.ID = l.product_id
			 WHERE l.device_model_id = %d AND p.post_type = 'product' AND p.post_status IN ($marks)
			 GROUP BY l.product_id, p.post_status",
			$args
		);
		$rows = $this->db->get_results( $sql, ARRAY_A );
		return array_map( static function ( $row ) {
			$row['product_id'] = (int) $row['product_id'];
			$row['model_only'] = (bool) $row['model_only'];
			$row['variant_ids'] = $row['variant_ids'] ? array_map( 'intval', explode( ',', $row['variant_ids'] ) ) : array();
			return $row;
		}, $rows );
	}
}
