<?php
/*
 * Copyright (c) 2011 Mark C. Prins <mc.prins@gmail.com>
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
 * DokuWiki Plugin geotag (Action Component)
 *
 * @license BSD
 * @author  Mark C. Prins <mc.prins@gmail.com>
 */
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'action.php';

class action_plugin_geotag extends DokuWiki_Action_Plugin {

	/**
	 * Register for events.
	 *
	 * @param $controller DokuWiki's event controller object. Also available as global $EVENT_HANDLER
	 */
	public function register(Doku_Event_Handler &$controller) {
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handle_metaheader_output');
		$controller->register_hook('IO_WIKIPAGE_WRITE', 'BEFORE', $this, 'ping_geourl', array());
	}

	/**
	 * retrieve metadata and add to the head of the page using appropriate meta tags.
	 * @param Doku_Event $event the DokuWiki event
	 * @param unknown_type $param
	 */
	public function handle_metaheader_output(Doku_Event &$event, $param) {
		/* 
		 * see: http://www.dokuwiki.org/devel:event:tpl_metaheader_output
		 * $data is a two-dimensional array of all meta headers. The keys are meta, link and script.
		 */
		global $ID;
		$title = p_get_metadata($ID,'title',true);
		$geotags = p_get_metadata($ID,'geo',true);
		$region=$geotags['region'];
		$lat=$geotags['lat'];
		$lon=$geotags['lon'];
		$country=$geotags['country'];
		$placename=$geotags['placename'];

		if (!empty($region)) {$event->data['meta'][] = array('name' => 'geo.region','content' => $region,);}
		if (!empty($placename)) {$event->data['meta'][] = array('name' => 'geo.placename','content' => $placename,);}
		if (!(empty($lat)&&empty($lon))) {$event->data['meta'][] = array('name' => 'geo.position','content' => $lat.';'.$lon,);}
		if (!empty($country)) {$event->data['meta'][] = array('name' => 'geo.country','content' => $country,);}
		if (!(empty($lat)&&empty($lon))) {$event->data['meta'][] = array('name' => "ICBM",'content' => $lat.', '.$lon,);}
		// icbm is generally useless without a dc.title, so we copy that from title
		if (!(empty($title))) {$event->data['meta'][] = array('name' => "DC.title",'content' => $title);}
	}

	/**
	 * Ping the geourl webservice with th eurl of the for indexing.
	 * @param Doku_Event $event the DokuWiki event
	 * @param array $param
	 */
	function ping_geourl(Doku_Event &$event, $param) {
		/*
		 * see: http://www.dokuwiki.org/devel:event:io_wikipage_write
		 * $data[0] – The raw arguments for io_saveFile as an array. Do not change file path.
		 * $data[0][0] – the file path.
		 * $data[0][1] – the content to be saved, and may be modified.
		 * $data[1] – ns: The colon separated namespace path minus the trailing page name. (false if root ns)
		 * $data[2] – page_name: The wiki page name.
		 * $data[3] – rev: The page revision, false for current wiki pages.
		 */
		if (!$this->getConf('geotag_pinggeourl')) return false; // config says don't ping
		if ($event->data[3]) return false;                   // old revision saved
		if (@file_exists($event->data[0][0])) return false;  // file not new
		if (!$event->data[0][1]) return false;               // file is empty
		if (p_get_metadata($ID,'geo',true)) return false; // no geo metadata available

		$request = 'p='.DOKU_URL;
		$url = 'http://geourl.org/ping/';
		$header[] = 'Host: geourl.org';
		$header[] = 'Content-type: text/html';
		$header[] = 'Content-length: '.strlen($request);

		$http = new DokuHTTPClient();
		return $http->get($url, $request);
	}
}
