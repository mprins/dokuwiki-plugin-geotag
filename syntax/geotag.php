<?php
/*
 * Copyright (c) 2011-2013 Mark C. Prins <mprins@users.sf.net>
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


if (!defined('DOKU_INC')) die();

if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'syntax.php');
/**
 * DokuWiki Plugin geotag (Syntax Component). 
 * Handles the rendering part of the geotag plugin.
 *
 * @license BSD license
 * @author  Mark C. Prins <mprins@users.sf.net>
 */
class syntax_plugin_geotag_geotag extends DokuWiki_Syntax_Plugin {
	/**
	 * (non-PHPdoc)
	 * @see DokuWiki_Syntax_Plugin::getType()
	 */
	public function getType() { return 'substition'; }

	/**
	 * (non-PHPdoc)
	 * @see DokuWiki_Syntax_Plugin::getPType()
	 */
	public function getPType() { return 'block'; }

	/**
	 * (non-PHPdoc)
	 * @see Doku_Parser_Mode::getSort()
	 */
	public function getSort() { return 305; }

	/**
	 * (non-PHPdoc)
	 * @see Doku_Parser_Mode::connectTo()
	 */
	public function connectTo($mode) {
		$this->Lexer->addSpecialPattern('\{\{geotag>.*?\}\}',$mode,'plugin_geotag_geotag');
	}

	/**
	 * (non-PHPdoc)
	 * @see DokuWiki_Syntax_Plugin::handle()
	 */
	public function handle($match, $state, $pos, &$handler){
		$tags = trim(substr($match, 9, -2));
		// parse geotag content
		preg_match("(lat[:|=]\d*\.\d*)",$tags,$lat);
		preg_match("(lon[:|=]\d*\.\d*)",$tags,$lon);
		preg_match("(region[:|=][a-zA-Z\s\w'-]*)",$tags,$region);
		preg_match("(placename[:|=][a-zA-Z\s\w'-]*)",$tags,$placename);
		preg_match("(country[:|=][a-zA-Z\s\w'-]*)",$tags,$country);
		preg_match("(hide|unhide)",$tags,$hide);

		$showlocation=$this->getConf('geotag_location_prefix');
		if ($this->getConf('geotag_showlocation')) {
			$showlocation=trim(substr($placename[0],10));
			if (strlen($showlocation)>0) {
				$showlocation .=': ';
			}
		}
		// read config for system setting
		$style='';
		if ($this->getConf('geotag_hide')) {
			$style=' style="display: none;"';
		}
		// override config for the current tag
		if (trim($hide[0])=='hide'){
			$style=' style="display: none;"';
		} elseif(trim($hide[0])=='unhide'){
			$style='';
		}

		$data = array(
			trim(substr($lat[0],4)),
			trim(substr($lon[0],4)),
			$this->_geohash(substr($lat[0],4), substr($lon[0],4)),
			trim(substr($region[0],7)),
			trim(substr($placename[0],10)),
			trim(substr($country[0],8)),
			$showlocation,
			$style,
		);
		return $data;
	}

	/**
	 * (non-PHPdoc)
	 * @see DokuWiki_Syntax_Plugin::render()
	 */
	public function render($mode, &$renderer, $data) {
		if ($data === false) return false;
		list ($lat, $lon, $geohash, $region, $placename, $country, $showlocation, $style) = $data;
		if ($mode == 'xhtml') {
			if ($this->getConf('geotag_prevent_microformat_render')) {
				// config says no microformat rendering
				return true;
			}
			// render geotag microformat
			$renderer->doc .= '<span class="geotagPrint">'.
					$this->getLang('geotag_desc').'</span><div class="geo"'.
					$style.' title="'.$this->getLang('geotag_desc').$placename.'">'.
					$showlocation.'<span class="latitude">'.
					$lat.'</span>;<span class="longitude">'.
					$lon.'</span></div>'.DOKU_LF;
			return true;
		} elseif ($mode == 'metadata') {
			// render metadata (action plugin will put it in the page head)
			$renderer->meta['geo']['lat'] = $lat;
			$renderer->meta['geo']['lon'] = $lon;
			$renderer->meta['geo']['placename'] = $placename;
			$renderer->meta['geo']['region'] = $region;
			$renderer->meta['geo']['country'] = $country;
			$renderer->meta['geo']['geohash'] = $geohash;
			return true;
		} elseif ($mode=='odt'){
			$renderer->p_open();
			$renderer->_odtAddImage(DOKU_PLUGIN.'geotag/images/geotag.png',null, null, 'left','');
			$renderer->doc .= '<text:span>'.$this->getLang('geotag_desc').' '.$placename.': </text:span>';
			$renderer->monospace_open();
			$renderer->doc .= $lat.';'.$lon;
			$renderer->monospace_close();
			$renderer->p_close();
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
	private function _geohash($lat, $lon){
		if (!$geophp = &plugin_load('helper', 'geophp')){
			dbglog($geophp,'syntax_plugin_geotag_geotag::_geohash: geophp plugin is not available.');
			return "";
		}
		$_lat = floatval($lat);
		$_lon = floatval($lon);
		$geometry = new Point($_lon,$_lat);
		//dbglog($geometry, 'geometry to calculate geohash from..');
		return $geometry->out('geohash');
	}
}
