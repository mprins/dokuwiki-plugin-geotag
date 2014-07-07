<?php
/*
 * Copyright (c) 2011-2014 Mark C. Prins <mprins@users.sf.net>
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
if (! defined ( 'DOKU_INC' ))
	die ();

if (! defined ( 'DOKU_PLUGIN' ))
	define ( 'DOKU_PLUGIN', DOKU_INC . 'lib/plugins/' );
require_once (DOKU_PLUGIN . 'action.php');

/**
 * DokuWiki Plugin geotag (Action Component).
 *
 * @license BSD license
 * @author Mark C. Prins <mprins@users.sf.net>
 */
class action_plugin_geotag extends DokuWiki_Action_Plugin {

	/**
	 * Register for events.
	 *
	 * @param Doku_Event_Handler $controller
	 *        	DokuWiki's event controller object. Also available as global $EVENT_HANDLER
	 */
	public function register(Doku_Event_Handler $controller) {
		$controller->register_hook ( 'TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handle_metaheader_output' );
		$controller->register_hook ( 'IO_WIKIPAGE_WRITE', 'BEFORE', $this, 'ping_geourl', array () );
		if ($this->getConf ( 'toolbar_icon' )) {
			$controller->register_hook ( 'TOOLBAR_DEFINE', 'AFTER', $this, 'insert_button', array () );
		}
	}

	/**
	 * Retrieve metadata and add to the head of the page using appropriate meta tags.
	 *
	 * @param Doku_Event $event
	 *        	the DokuWiki event. $event->data is a two-dimensional
	 *        	array of all meta headers. The keys are meta, link and script.
	 * @param unknown_type $param
	 *
	 * @see http://www.dokuwiki.org/devel:event:tpl_metaheader_output
	 */
	public function handle_metaheader_output(Doku_Event &$event, $param) {
		global $ID;
		$title = p_get_metadata ( $ID, 'title', true );
		$geotags = p_get_metadata ( $ID, 'geo', true );
		$region = $geotags ['region'];
		$lat = $geotags ['lat'];
		$lon = $geotags ['lon'];
		$alt = $geotags ['alt'];
		$country = $geotags ['country'];
		$placename = $geotags ['placename'];
		$geohash = $geotags ['geohash'];

		if (! empty ( $region )) {
			$event->data ['meta'] [] = array (
					'name' => 'geo.region',
					'content' => $region
			);
		}
		if (! empty ( $placename )) {
			$event->data ['meta'] [] = array (
					'name' => 'geo.placename',
					'content' => $placename
			);
		}
		if (! (empty ( $lat ) && empty ( $lon ))) {
			if (! empty ( $alt )) {
				$event->data ['meta'] [] = array (
						'name' => 'geo.position',
						'content' => $lat . ';' . $lon . ';' . $alt
				);
			} else {
				$event->data ['meta'] [] = array (
						'name' => 'geo.position',
						'content' => $lat . ';' . $lon
				);
			}
		}
		if (! empty ( $country )) {
			$event->data ['meta'] [] = array (
					'name' => 'geo.country',
					'content' => $country
			);
		}
		if (! (empty ( $lat ) && empty ( $lon ))) {
			$event->data ['meta'] [] = array (
					'name' => "ICBM",
					'content' => $lat . ', ' . $lon
			);
			// icbm is generally useless without a DC.title,
			// so we copy that from title unless it's empty...
			// also specify the DC namespace
			if (! (empty ( $title ))) {
				$event->data ['link'] [] = array (
						'rel' => 'schema.DC',
						'href' => 'http://purl.org/dc/elements/1.1/'
				);
				$event->data ['meta'] [] = array (
						'name' => "DC.title",
						'content' => $title
				);
			}
		}
		if (! empty ( $geohash )) {
			$event->data ['meta'] [] = array (
					'name' => 'geo.geohash',
					'content' => $geohash
			);
		}
	}

	/**
	 * Ping the geourl webservice with the url of the for indexing, only if the page is new.
	 *
	 * @param Doku_Event $event
	 *        	the DokuWiki event
	 * @param mixed $param
	 *        	not used
	 */
	function ping_geourl(Doku_Event &$event, $param) {
		global $ID;
		// see: http://www.dokuwiki.org/devel:event:io_wikipage_write event data:
		// $data[0] – The raw arguments for io_saveFile as an array. Do not change file path.
		// $data[0][0] – the file path.
		// $data[0][1] – the content to be saved, and may be modified.
		// $data[1] – ns: The colon separated namespace path minus the trailing page name. (false if root ns)
		// $data[2] – page_name: The wiki page name.
		// $data[3] – rev: The page revision, false for current wiki pages.
		if (! $this->getConf ( 'geotag_pinggeourl' ))
			return false; // config says don't ping
		if ($event->data [3])
			return false; // old revision saved
		if (! $event->data [0] [1])
			return false; // file is empty
		if (@file_exists ( $event->data [0] [0] ))
			return false; // file not new
		if (p_get_metadata ( $ID, 'geo', true ))
			return false; // no geo metadata available, ping is useless

		$url = 'http://geourl.org/ping/?p=' . wl ( $ID, '', true );
		$http = new DokuHTTPClient ();
		$result = $http->get ( $url );
		// dbglog ( $result, "GeoURL Ping response for $url" );
		return $result;
	}

	/**
	 * Inserts the toolbar button.
	 *
	 * @param Doku_Event $event
	 *        	the DokuWiki event
	 */
	function insert_button(Doku_Event &$event, $param) {
		$event->data [] = array (
				'type' => 'format',
				'title' => $this->getLang ( 'toolbar_desc' ),
				'icon' => '../../plugins/geotag/images/geotag.png',
				'open' => '{{geotag>lat:',
				'sample' => '52.2345',
				'close' => ', lon:7.521, alt: , placename: , country: , region: }}'
		);
	}
}
