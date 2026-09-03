<?php

use XcVm\Core\Database\Database;
use XcVm\Core\Database\QueryHelper;
use PHPUnit\Framework\TestCase;

/**
 * @covers QueryHelper
 */
final class QueryHelperTest extends TestCase {
	private function metadataDb(array $rows) {
		return new class($rows) {
			private $rows;

			public function __construct(array $rows) {
				$this->rows = $rows;
			}

			public function query($query, ...$arguments) {
				return true;
			}

			public function get_rows() {
				return $this->rows;
			}
		};
	}

	public function testPrepareColumnSanitizesToSafeIdentifier() {
		$this->assertSame('foobar23', QueryHelper::prepareColumn('Foo Bar!23'));
		$this->assertSame('user_id', QueryHelper::prepareColumn('user_id'));
		$this->assertSame('droptable', QueryHelper::prepareColumn('drop;table'));
	}

	public function testPrepareArrayBuildsColumnsPlaceholdersAndData() {
		$result = QueryHelper::prepareArray(array('name' => 'x', 'age' => 5));

		$this->assertSame('`name`,`age`', $result['columns']);
		$this->assertSame('?,?', $result['placeholder']);
		$this->assertSame(array('x', 5), $result['data']);
		$this->assertSame('`name` = ?,`age` = ?', $result['update']);
	}

	public function testPrepareArrayJsonEncodesArrayValues() {
		$result = QueryHelper::prepareArray(array('tags' => array(1, 2, 3)));
		$this->assertSame('[1,2,3]', $result['data'][0]);
	}

	public function testPrepareArrayNormalizesNullValues() {
		$result = QueryHelper::prepareArray(array('a' => null, 'b' => 'null'));
		$this->assertNull($result['data'][0]);
		$this->assertNull($result['data'][1]);
	}

	public function testPrepareArraySanitizesColumnNames() {
		$result = QueryHelper::prepareArray(array('na me!' => 'v'));
		$this->assertSame('`name`', $result['columns']);
	}

	public function testVerifyPostTableOmitsMissingCurrentTimestampDefault() {
		global $db;
		$db = $this->metadataDb(array(
			array('column_name' => 'name', 'column_default' => null, 'is_nullable' => 'YES', 'data_type' => 'varchar'),
			array('column_name' => 'enabled', 'column_default' => '1', 'is_nullable' => 'YES', 'data_type' => 'int'),
			array('column_name' => 'updated', 'column_default' => 'current_timestamp()', 'is_nullable' => 'YES', 'data_type' => 'timestamp'),
		));

		$this->assertSame(
			array('name' => 'example', 'enabled' => '1'),
			QueryHelper::verifyPostTable('lines', array('name' => 'example'))
		);
	}

	public function testVerifyPostTablePreservesExplicitTimestampValue() {
		global $db;
		$db = $this->metadataDb(array(
			array('column_name' => 'updated', 'column_default' => 'current_timestamp()', 'is_nullable' => 'YES', 'data_type' => 'timestamp'),
		));

		$this->assertSame(
			array('updated' => '2026-08-30 22:00:00'),
			QueryHelper::verifyPostTable('lines', array('updated' => '2026-08-30 22:00:00'))
		);
	}
}
