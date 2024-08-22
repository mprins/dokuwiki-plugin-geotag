<?php

/*
 * Copyright (c) 2011 Mark C. Prins <mprins@users.sf.net>
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

use dokuwiki\Extension\SyntaxPlugin;
use geoPHP\Geometry\Point;

/**
 * DokuWiki Plugin geotag (Syntax Component).
 *
 * Handles the rendering part of the geotag plugin.
 *
 * @license BSD license
 * @author  Mark C. Prins <mprins@users.sf.net>
 */
class syntax_plugin_geotag_geotag extends SyntaxPlugin
{
    /**
     *
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    final public function getType(): string
    {
        return 'substition';
    }

    /**
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    final public function getPType(): string
    {
        return 'block';
    }

    /**
     *
     * @see Doku_Parser_Mode::getSort()
     */
    final public function getSort(): int
    {
        return 305;
    }

    /**
     *
     * @see Doku_Parser_Mode::connectTo()
     */
    final public function connectTo($mode): void
    {
        $this->Lexer->addSpecialPattern('\{\{geotag>.*?\}\}', $mode, 'plugin_geotag_geotag');
    }

    /**
     *
     * @see DokuWiki_Syntax_Plugin::handle()
     */
    final public function handle($match, $state, $pos, Doku_Handler $handler): array
    {
        $tags = trim(substr($match, 9, -2));
        // parse geotag content
        $lat = $this->parseNumericParameter("lat", $tags);
        $lon = $this->parseNumericParameter("lon", $tags);
        $alt = $this->parseNumericParameter("alt", $tags);
        preg_match("/(region[:|=][\p{L}\s\w'-]*)/u", $tags, $region);
        preg_match("/(placename[:|=][\p{L}\s\w'-]*)/u", $tags, $placename);
        preg_match("/(country[:|=][\p{L}\s\w'-]*)/u", $tags, $country);
        preg_match("(hide|unhide)", $tags, $hide);

        $showlocation = $this->getConf('geotag_location_prefix');
        if ($this->getConf('geotag_showlocation')) {
            $showlocation = trim(substr($placename [0], 10));
            if ($showlocation === '') {
                $showlocation = $this->getConf('geotag_location_prefix');
            }
        }
        // read config for system setting
        $style = '';
        if ($this->getConf('geotag_hide')) {
            $style = ' style="display: none;"';
        }
        // override config for the current tag
        if (array_key_exists(0, $hide) && trim($hide [0]) === 'hide') {
            $style = ' style="display: none;"';
        } elseif (array_key_exists(0, $hide) && trim($hide [0]) === 'unhide') {
            $style = '';
        }
        return [
            hsc($lat),
            hsc($lon),
            hsc($alt),
            $this->geohash($lat, $lon),
            hsc(trim(substr(($region[0] ?? ''), 7))),
            hsc(trim(substr(($placename[0] ?? ''), 10))),
            hsc(trim(substr(($country [0] ?? ''), 8))),
            hsc($showlocation), $style
        ];
    }

    /**
     * parses numeric parameter with given name
     *
     * @param string $name name of the parameter
     * @param string $input text to consume
     * @return string parameter values as numeric string or empty string if nothing is found
     */
    private function parseNumericParameter(string $name, string $input): string
    {
        $output = '';
        $pattern = "/" . $name . "\s*[:=]\s*(-?\d*\.?\d*)/";
        if (preg_match($pattern, $input, $matches)) {
            $output = $matches[1];
        }
        return $output;
    }

    /**
     * Calculate the geohash for this lat/lon pair.
     *
     * @param float $lat
     * @param float $lon
     * @return mixed|string
     * @throws Exception
     */
    private function geohash(float $lat, float $lon)
    {
        if (($geophp = plugin_load('helper', 'geophp')) === null) {
            return "";
        }

        return (new Point($lon, $lat))->out('geohash');
    }

    /**
     *
     * @see DokuWiki_Syntax_Plugin::render()
     */
    final public function render($format, Doku_Renderer $renderer, $data): bool
    {
        if ($data === false) {
            return false;
        }
        [$lat, $lon, $alt, $geohash, $region, $placename, $country, $showlocation, $style] = $data;
        $ddlat = $lat;
        $ddlon = $lon;
        if ($this->getConf('displayformat') === 'DMS') {
            $lat = $this->convertLat($lat);
            $lon = $this->convertLon($lon);
        } else {
            $lat .= 'º';
            $lon .= 'º';
        }

        if ($format === 'xhtml') {
            if ($this->getConf('geotag_prevent_microformat_render')) {
                return true;
            }
            $searchPre = '';
            $searchPost = '';
            if ($this->getConf('geotag_showsearch')) {
                if (($spHelper = plugin_load('helper', 'spatialhelper_search')) !== null) {
                    $title = $this->getLang('findnearby') . '&nbsp;' . $placename;
                    $url = wl(
                        getID(),
                        ['do' => 'findnearby', 'lat' => $ddlat, 'lon' => $ddlon]
                    );
                    $searchPre = '<a href="' . $url . '" title="' . $title . '">';
                    $searchPost = '<span class="a11y">' . $title . '</span></a>';
                }
            }

            // render geotag microformat/schema.org microdata
            $renderer->doc .= '<span class="geotagPrint">' . $this->getLang('geotag_desc') . '</span>';
            $renderer->doc .= '<div class="h-geo geo"' . $style . ' title="' . $this->getLang('geotag_desc')
                . $placename . '" itemscope itemtype="https://schema.org/Place">';
            $renderer->doc .= '<span itemprop="name">' . $showlocation . '</span>:&nbsp;' . $searchPre;
            $renderer->doc .= '<span itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">';
            $renderer->doc .= '<span class="p-latitude latitude" itemprop="latitude" data-latitude="' . $ddlat . '">'
                . $lat . '</span>;';
            $renderer->doc .= '<span class="p-longitude longitude" itemprop="longitude" data-longitude="' . $ddlon
                . '">' . $lon . '</span>';
            if (!empty($alt)) {
                $renderer->doc .= ', <span class="p-altitude altitude" itemprop="elevation" data-altitude="' . $alt
                    . '">' . $alt . 'm</span>';
            }
            $renderer->doc .= '</span>' . $searchPost . '</div>' . DOKU_LF;
            return true;
        } elseif ($format === 'metadata') {
            // render metadata (our action plugin will put it in the page head)
            $renderer->meta ['geo'] ['lat'] = $ddlat;
            $renderer->meta ['geo'] ['lon'] = $ddlon;
            $renderer->meta ['geo'] ['placename'] = $placename;
            $renderer->meta ['geo'] ['region'] = $region;
            $renderer->meta ['geo'] ['country'] = $country;
            $renderer->meta ['geo'] ['geohash'] = $geohash;
            if (!empty($alt)) {
                $renderer->meta ['geo'] ['alt'] = $alt;
            }
            return true;
        } elseif ($format === 'odt') {
            if (!empty($alt)) {
                $alt = ', ' . $alt . 'm';
            }
            $renderer->p_open();
            $renderer->_odtAddImage(DOKU_PLUGIN . 'geotag/images/geotag.png', null, null, 'left', '');
            $renderer->cdata($this->getLang('geotag_desc') . ' ' . $placename);
            $renderer->monospace_open();
            $renderer->cdata($lat . ';' . $lon . $alt);
            $renderer->monospace_close();
            $renderer->p_close();
            return true;
        } else {
            return false;
        }
    }

    /**
     * convert latitude in decimal degrees to DMS+hemisphere.
     *
     * @param float $decimaldegrees
     * @return string
     * @todo move this into a shared library
     */
    private function convertLat(float $decimaldegrees): string
    {
        if (strpos($decimaldegrees, '-') !== false) {
            $latPos = "S";
        } else {
            $latPos = "N";
        }
        $dms = $this->convertDDtoDMS(abs($decimaldegrees));
        return hsc($dms . $latPos);
    }

    /**
     * Convert decimal degrees to degrees, minutes, seconds format
     *
     * @param float $decimaldegrees
     * @return string dms
     * @todo move this into a shared library
     */
    private function convertDDtoDMS(float $decimaldegrees): string
    {
        $dms = floor($decimaldegrees);
        $secs = ($decimaldegrees - $dms) * 3600;
        $min = floor($secs / 60);
        $sec = round($secs - ($min * 60), 3);
        $dms .= 'º' . $min . '\'' . $sec . '"';
        return $dms;
    }

    /**
     * convert longitude in decimal degrees to DMS+hemisphere.
     *
     * @param float $decimaldegrees
     * @todo move this into a shared library
     */
    private function convertLon(float $decimaldegrees): string
    {
        if (strpos($decimaldegrees, '-') !== false) {
            $lonPos = "W";
        } else {
            $lonPos = "E";
        }
        $dms = $this->convertDDtoDMS(abs($decimaldegrees));
        return hsc($dms . $lonPos);
    }
}
