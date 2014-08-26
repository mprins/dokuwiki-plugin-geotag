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

require_once (DOKU_PLUGIN . 'syntax.php');
/**
 * DokuWiki Plugin geotag (Syntax Component).
 *
 * Handles the rendering part of the geotag plugin.
 *
 * @license BSD license
 * @author Mark C. Prins <mprins@users.sf.net>
 */
class syntax_plugin_geotag_geotag extends DokuWiki_Syntax_Plugin {
	/**
	 *
	 * @see DokuWiki_Syntax_Plugin::getType()
	 */
	public function getType() {
		return 'substition';
	}

	/**
	 *
	 * @see DokuWiki_Syntax_Plugin::getPType()
	 */
	public function getPType() {
		return 'block';
	}

	/**
	 *
	 * @see Doku_Parser_Mode::getSort()
	 */
	public function getSort() {
		return 305;
	}

	/**
	 *
	 * @see Doku_Parser_Mode::connectTo()
	 */
	public function connectTo($mode) {
		$this->Lexer->addSpecialPattern ( '\{\{geotag>.*?\}\}', $mode, 'plugin_geotag_geotag' );
	}

	/**
	 *
	 * @see DokuWiki_Syntax_Plugin::handle()
	 */
	public function handle($match, $state, $pos, Doku_Handler &$handler) {
		$tags = trim ( substr ( $match, 9, - 2 ) );
		// parse geotag content
		preg_match ( "(lat[:|=]\d*\.\d*)", $tags, $lat );
		preg_match ( "(lon[:|=]\d*\.\d*)", $tags, $lon );
		preg_match ( "(alt[:|=]\d*\.?\d*)", $tags, $alt );
		preg_match ( "(region[:|=][a-zA-Z\s\w'-]*)", $tags, $region );
		preg_match ( "(placename[:|=][a-zA-Z\s\w'-]*)", $tags, $placename );
		preg_match ( "(country[:|=][a-zA-Z\s\w'-]*)", $tags, $country );
		preg_match ( "(hide|unhide)", $tags, $hide );

		$showlocation = $this->getConf ( 'geotag_location_prefix' );
		if ($this->getConf ( 'geotag_showlocation' )) {
			$showlocation = trim ( substr ( $placename [0], 10 ) );
			if (strlen ( $showlocation ) < 1) {
				$showlocation = $this->getConf ( 'geotag_location_prefix' );
			}
		}
		// read config for system setting
		$style = '';
		if ($this->getConf ( 'geotag_hide' )) {
			$style = ' style="display: none;"';
		}
		// override config for the current tag
		if (trim ( $hide [0] ) == 'hide') {
			$style = ' style="display: none;"';
		} elseif (trim ( $hide [0] ) == 'unhide') {
			$style = '';
		}

		$data = array (
				hsc ( trim ( substr ( $lat [0], 4 ) ) ),
				hsc ( trim ( substr ( $lon [0], 4 ) ) ),
				hsc ( trim ( substr ( $alt [0], 4 ) ) ),
				$this->_geohash ( substr ( $lat [0], 4 ), substr ( $lon [0], 4 ) ),
				hsc ( trim ( substr ( $region [0], 7 ) ) ),
				hsc ( trim ( substr ( $placename [0], 10 ) ) ),
				hsc ( trim ( substr ( $country [0], 8 ) ) ),
				hsc ( $showlocation ),
				$style
		);
		return $data;
	}

	/**
	 *
	 * @see DokuWiki_Syntax_Plugin::render()
	 */
	public function render($mode, Doku_Renderer &$renderer, $data) {
		if ($data === false)
			return false;
		list ( $lat, $lon, $alt, $geohash, $region, $placename, $country, $showlocation, $style ) = $data;
		$ddlat = $lat;
		$ddlon = $lon;
		if ($this->getConf ( 'displayformat' ) === 'DMS') {
			$lat = $this->convertLat ( $lat );
			$lon = $this->convertLon ( $lon );
		} else {
			$lat .= 'º';
			$lon .= 'º';
		}

		if ($mode == 'xhtml') {
			if ($this->getConf ( 'geotag_prevent_microformat_render' )) {
				return true;
			}
			if ($this->getConf ( 'geotag_showsearch' )) {
				$searchPre = '';
				$searchPost = '';
				if ($spHelper = &plugin_load ( 'helper', 'spatialhelper_search' )) {
					$title = $this->getLang ( 'findnearby' ) . '&nbsp;' . $placename;
					$url = wl ( getID (), array (
							'do' => 'findnearby',
							'lat' => $ddlat,
							'lon' => $ddlon
					) );
					$searchPre = '<a href="' . $url . '" title="' . $title . '">';
					$searchPost = '<span class="a11y">' . $title . '</span></a>';
				}
			}

			// render geotag microformat/schema.org microdata
			$renderer->doc .= '<span class="geotagPrint">' . $this->getLang ( 'geotag_desc' ) . '</span>';
			$renderer->doc .= '<div class="h-geo geo"' . $style . ' title="' . $this->getLang ( 'geotag_desc' ) . $placename . '" itemscope itemtype="http://schema.org/Place">';
			$renderer->doc .= '<span itemprop="name">' . $showlocation . '</span>:&nbsp;' . $searchPre;
			$renderer->doc .= '<span itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinates">';
			$renderer->doc .= '<span class="p-latitude latitude" itemprop="latitude" content="' . $ddlat . '">' . $lat . '</span>;';
			$renderer->doc .= '<span class="p-longitude longitude" itemprop="longitude" content="' . $ddlon . '">' . $lon . '</span>';
			if (! empty ( $alt )) {
				$renderer->doc .= ', <span class="p-altitude altitude" itemprop="elevation" content="' . $alt . '">' . $alt . 'm</span>';
			}
			$renderer->doc .= $searchPost . '</span></div>' . DOKU_LF;
			return true;
		} elseif ($mode == 'metadata') {
			// render metadata (our action plugin will put it in the page head)
			$renderer->meta ['geo'] ['lat'] = $ddlat;
			$renderer->meta ['geo'] ['lon'] = $ddlon;
			$renderer->meta ['geo'] ['placename'] = $placename;
			$renderer->meta ['geo'] ['region'] = $region;
			$renderer->meta ['geo'] ['country'] = $country;
			$renderer->meta ['geo'] ['geohash'] = $geohash;
			$renderer->meta ['geo'] ['alt'] = $alt;
			return true;
		} elseif ($mode == 'odt') {
			if (! empty ( $alt ))
				$alt = ', ' . $alt . 'm';
			$renderer->p_open ();
			$renderer->_odtAddImage ( DOKU_PLUGIN . 'geotag/images/geotag.png', null, null, 'left', '' );
			$renderer->doc .= '<text:span>' . $this->getLang ( 'geotag_desc' ) . ' ' . $placename . ': </text:span>';
			$renderer->monospace_open ();
			$renderer->doc .= $lat . ';' . $lon . $alt;
			$renderer->monospace_close ();
			$renderer->p_close ();
			return true;
		}
		return false;
	}

	/**
	 * Calculate the geohash for this lat/lon pair.
	 *
	 * @param float $lat
	 * @param float $lon
	 */
	private function _geohash($lat, $lon) {
		if (! $geophp = &plugin_load ( 'helper', 'geophp' )) {
			dbglog ( $geophp, 'syntax_plugin_geotag_geotag::_geohash: geophp plugin is not available.' );
			return "";
		}
		$_lat = floatval ( $lat );
		$_lon = floatval ( $lon );
		$geometry = new Point ( $_lon, $_lat );
		return $geometry->out ( 'geohash' );
	}

	/**
	 * Convert decimal degrees to degrees, minutes, seconds format
	 *
	 * @todo move this into a shared library
	 * @param float $decimaldegrees
	 * @return string dms
	 */
	private function _convertDDtoDMS($decimaldegrees) {
		$dms = floor ( $decimaldegrees );
		$secs = ($decimaldegrees - $dms) * 3600;
		$min = floor ( $secs / 60 );
		$sec = round ( $secs - ($min * 60), 3 );
		$dms .= 'º' . $min . '\'' . $sec . '"';
		return $dms;
	}

	/**
	 * convert latitude in decimal degrees to DMS+hemisphere.
	 *
	 * @todo move this into a shared library
	 * @param float $decimaldegrees
	 * @return string
	 */
	private function convertLat($decimaldegrees) {
		if (strpos ( $decimaldegrees, '-' ) !== false) {
			$latPos = "S";
		} else {
			$latPos = "N";
		}
		$dms = $this->_convertDDtoDMS ( abs ( floatval ( $decimaldegrees ) ) );
		return hsc ( $dms . $latPos );
	}

	/**
	 * convert longitude in decimal degrees to DMS+hemisphere.
	 *
	 * @todo move this into a shared library
	 * @param float $decimaldegrees
	 * @return string
	 */
	private function convertLon($decimaldegrees) {
		if (strpos ( $decimaldegrees, '-' ) !== false) {
			$lonPos = "W";
		} else {
			$lonPos = "E";
		}
		$dms = $this->_convertDDtoDMS ( abs ( floatval ( $decimaldegrees ) ) );
		return hsc ( $dms . $lonPos );
	}
}
