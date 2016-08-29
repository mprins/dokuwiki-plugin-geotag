<?php
/*
 * Copyright (c) 2016 Mark C. Prins <mprins@users.sf.net>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

/**
 * Syntax tests for the geotag plugin.
 *
 * @group plugin_geotag
 * @group plugins
 */
class syntax_plugin_geotag_test extends DokuWikiTest {

	protected $pluginsEnabled = array('geotag');

	/**
	 * copy data and add pages to the index.
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		global $conf;
		$conf['allowdebug'] = 1;

		TestUtils::rcopy(TMP_DIR, dirname(__FILE__).'/data/');

		dbglog("\nset up class syntax_plugin_geotag_test");
	}

	function setUp() {
		parent::setUp();

		global $conf;
		$conf['allowdebug'] = 1;
		$conf['cachetime'] = -1;

		$data = array();
		search($data, $conf['datadir'], 'search_allpages', array('skipacl' => true));

		//dbglog($data, "pages for indexing");

		$verbose = false;
		$force = false;
		foreach ($data as $val) {
			idx_addPage($val['id'], $verbose, $force);
		}
		if ($conf['allowdebug']) {
			touch(DOKU_TMP_DATA.'cache/debug.log');
		}
	}

	public function tearDown() {
		parent::tearDown();

		global $conf;
		// try to get the debug log after running the test, print and clear
		if ($conf['allowdebug']) {
			print "\n";
			readfile(DOKU_TMP_DATA.'cache/debug.log');
			unlink(DOKU_TMP_DATA.'cache/debug.log');
		}
	}

	public function test_geotag(){
		$request = new TestRequest();
		$response = $request->get(array('id'=>'minimalgeotag'), '/doku.php');

		$this->assertEquals('minimalgeotag',
						$response->queryHTML('meta[name="keywords"]')->attr('content'));
		$this->assertEquals('51.565696;5.324596',
						$response->queryHTML('meta[name="geo.position"]')->attr('content'));
		$this->assertEquals('51.565696, 5.324596',
						$response->queryHTML('meta[name="ICBM"]')->attr('content'));

		$this->assertTrue(
			strpos($response->getContent(), 'Geotag (location) for:') !== false,
			'"Geotag (location) for:" was not in the output'
		);
	}

	public function test_fullgeotag(){
		$request = new TestRequest();
		$response = $request->get(array('id'=>'fullgeotag'), '/doku.php');

		$this->assertEquals('fullgeotag',
						$response->queryHTML('meta[name="keywords"]')->attr('content'));
		$this->assertEquals('52.132633;5.291266;9',
						$response->queryHTML('meta[name="geo.position"]')->attr('content'));
		$this->assertEquals('52.132633, 5.291266',
						$response->queryHTML('meta[name="ICBM"]')->attr('content'));

		$this->assertTrue(
			strpos($response->getContent(), 'Geotag (location) for:') !== false,
			'"Geotag (location) for:" was not in the output'
		);
	}
}
