<?php
namespace Multaparts\ApparaatpaginaRegistry\Infrastructure\Registry;

final class Wpdb_Registry_Reader implements Registry_Reader {
	private $db;
	private $tables;
	public function __construct( $db, Registry_Table_Names $tables ) { $this->db = $db; $this->tables = $tables; }
	public function is_ready() {
		foreach ( array( $this->tables->models, $this->tables->variants, $this->tables->links ) as $table ) {
			if ( $this->db->get_var( $this->db->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) { return false; }
		}
		return true;
	}
	public function find_model( $model_key ) {
		$sql = $this->db->prepare( "SELECT id, model_key, brand, commercial_type FROM {$this->tables->models} WHERE model_key = %s LIMIT 1", $model_key );
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
