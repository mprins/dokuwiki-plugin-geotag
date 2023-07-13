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
class syntax_plugin_geotag_test extends DokuWikiTest
{

    protected $pluginsEnabled = array('geotag');

    /**
     * copy data and add pages to the index.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        global $conf;
        $conf['allowdebug'] = 1;

        TestUtils::rcopy(TMP_DIR, __DIR__ . '/data/');
    }

    final public function setUp(): void
    {
        parent::setUp();

        global $conf;
        $conf['allowdebug'] = 1;
        $conf['cachetime']  = -1;

        $data = array();
        search($data, $conf['datadir'], 'search_allpages', array('skipacl' => true));

        $verbose = false;
        $force   = false;
        foreach ($data as $val) {
            idx_addPage($val['id'], $verbose, $force);
        }
    }

    final public function test_geotag(): void
    {
        $request  = new TestRequest();
        $response = $request->get(array('id' => 'minimalgeotag'), '/doku.php');

        $this->assertEquals(
            'minimalgeotag',
            $response->queryHTML('meta[name="keywords"]')->attr('content')
        );
        $this->assertEquals(
            '51.565696;5.324596',
            $response->queryHTML('meta[name="geo.position"]')->attr('content')
        );
        $this->assertEquals(
            '51.565696, 5.324596',
            $response->queryHTML('meta[name="ICBM"]')->attr('content')
        );

        $this->assertNotFalse(
            strpos($response->getContent(), 'Geotag (location) for:'),
            '"Geotag (location) for:" was not in the output'
        );
    }

    final public function test_fullgeotag(): void
    {
        $request  = new TestRequest();
        $response = $request->get(array('id' => 'fullgeotag'), '/doku.php');

        $this->assertEquals(
            'fullgeotag',
            $response->queryHTML('meta[name="keywords"]')->attr('content')
        );
        $this->assertEquals(
            '52.132633;5.291266;9',
            $response->queryHTML('meta[name="geo.position"]')->attr('content')
        );
        $this->assertEquals(
            '52.132633, 5.291266',
            $response->queryHTML('meta[name="ICBM"]')->attr('content')
        );

        $this->assertNotFalse(
            strpos($response->getContent(), 'Geotag (location) for:'),
            '"Geotag (location) for:" was not in the output'
        );
    }

    final public function test_fullgeotagnegativecoords(): void
    {
        $request  = new TestRequest();
        $response = $request->get(array('id' => 'fullgeotagnegativecoords'), '/doku.php');

        $this->assertEquals(
            'fullgeotagnegativecoords',
            $response->queryHTML('meta[name="keywords"]')->attr('content')
        );
        $this->assertEquals(
            '-52.132633;-5.291266;-9',
            $response->queryHTML('meta[name="geo.position"]')->attr('content')
        );
        $this->assertEquals(
            '-52.132633, -5.291266',
            $response->queryHTML('meta[name="ICBM"]')->attr('content')
        );

        $this->assertNotFalse(
            strpos($response->getContent(), 'Geotag (location) for:'),
            '"Geotag (location) for:" was not in the output'
        );
    }

    final public function test_nogeotag(): void
    {
        $request  = new TestRequest();
        $response = $request->get(array('id' => 'nogeotag'), '/doku.php');

        $this->assertEquals(
            'nogeotag',
            $response->queryHTML('meta[name="keywords"]')->attr('content')
        );
        $this->assertEquals(
            null,
            $response->queryHTML('meta[name="geo.position"]')->attr('content')
        );
        $this->assertEquals(
            null,
            $response->queryHTML('meta[name="ICBM"]')->attr('content')
        );

        $this->assertFalse(
            strpos($response->getContent(), 'Geotag (location) for:'),
            '"Geotag (location) for:" should not be in the output'
        );
    }
}
