<?php

use Krinkle\Toolbase\Html;
use Krinkle\Toolbase\KrToolBaseClass;
use Krinkle\Toolbase\LabsDB;

// phpcs:disable MediaWiki.Files.ClassMatchesFilename.NotMatch
class WikiInfoTool extends KrToolBaseClass {

	protected $settingsKeys = [];

	public function run() {
		global $kgBase, $I18N, $kgReq;

		$params = [
			'wikiids' => $kgReq->getVal( 'wikiids', '' ),
			'format' => $kgReq->getVal( 'format', '_tool' ),
			'callback' => $kgReq->getVal( 'callback', null ),
			'_tool' => $kgReq->getVal( '_tool', null ),
		];
		$results = null;

		if ( $params['wikiids'] ) {
			$results = $this->fetchResults( $params['wikiids'] );
			kfApiExport( $results, $params['format'], $params['callback'], '_tool' );
		}

		// If kfApiExport didn't terminate the request, that means this wasn't an API request.
		// Show the results in the web page instead.
		$resultsHtml = '';
		if ( $results ) {
			// List of links to API requests with different formats
			$permalinks = '<strong>' . $I18N->msg( 'formats-heading', [ 'escape' => 'html' ] ) . '</strong>';
			$permalinks .= '<ul class="nav nav-pills">';
			foreach ( kfApiFormats() as $data ) {
				$permalinks .= '<li><a href="' . htmlspecialchars( $kgBase->generatePermalink( $data['params'] + [
					'wikiids' => $params['wikiids'],
				] ) ) . '">' . "{$data['label']}</a></li>\n";
			}
			$permalinks .= '</ul>';
			$resultsHtml .= $permalinks;

			foreach ( $results as $result ) {
				$resultsHtml .= Html::element( 'h2', [ 'id' => 'output' ],
					$I18N->msg( 'output', [ 'variables' => [ $result['input'] ] ] )
				);
				if ( !$result['match'] ) {
					$resultsHtml .= '<p><em>' .
						$I18N->msg( 'no-matches', [
							'variables' => [ $result['input'] ],
							'escape' => 'html',
						] ) .
						'</em></p>';
				} else {
					$table = '<table class="table table-hover">';
					foreach ( $result['data'] as $type => $value ) {
						$table .= '<tr><th>' . $type . '</th><td>' . $value . '</td></tr>';

					}
					$table .= '</table>';

					$resultsHtml .= $table;
				}
			}
		}

		// Ajax view update
		if ( $params['_tool'] === 'ajax' ) {
			header( 'Content-Type: text/html; charset=utf-8' );
			echo $resultsHtml;
			die;
		}

		// Full page view
		$kgBase->setLayout( 'header', [ 'captionText' => $I18N->msg( 'description' ) ] );
		$kgBase->addOut( '<div class="container">' );
		$this->showForm( $params );
		$kgBase->addOut( '<div id="ot-result">' . $resultsHtml . '</div>' );
		// Close wrapping container
		$kgBase->addOut( '</div>' );
	}

	protected function showForm( array $params ) {
		global $kgBase, $I18N;
		$kgBase->addOut(
			'<form class="form-horizontal" role="form" id="ot-form" method="get">'
			. '<fieldset>'
			. Html::element( 'legend', [], $I18N->msg( 'form-legend-settings', 'krinkle' ) )
			. '<div class="form-group">'
			. Html::element( 'label', [
				'for' => 'ot-form-wikiids',
				'class' => 'control-label col-sm-2',
			], $I18N->msg( 'label-wikiids' ) )
				. '<div class="col-sm-10">'
				. Html::element( 'input', [
					'type' => 'text',
					'name' => 'wikiids',
					'id' => 'ot-form-wikiids',
					'class' => 'form-control',
					'value' => $params['wikiids']
				] )
				. '<p class="help-block">'
					. 'commonswiki, nl, enwiki_p, de.wikipedia, '
					. 'http://meta.wikimedia.org, http://wikisource.org/?diff=3, '
					. $I18N->msg( 'etc', [ 'domain' => 'general', 'escape' => 'html' ] )
					. '</p>'
				. '</div>'
			. '</div>'
			. '<div class="form-group">'
				. '<div class="col-sm-offset-2 col-sm-5">'
				. Html::element( 'button', [
					'class' => 'btn btn-primary',
					'id' => 'ot-form-submit',
				], $I18N->msg( 'form-submit', 'general' ) )
				. '</div>'
			. '</div>'
			. '</fieldset>'
			. '</form>'
		);
	}

	protected function fetchResults( $wikiids ) {
		$wikiids = explode( '|', $wikiids );
		$result = [];
		foreach ( $wikiids as $wikiid ) {
			$result[ $wikiid ] = $this->fetchResult( $wikiid );
		}

		return $result;
	}

	protected function fetchResult( $wikiid ) {
		$normalised = self::cleanWikiID( $wikiid );
		$result = null;

		if ( $normalised ) {
			// Note: Make separate queries so that we can prioritise preferred
			// match order:
			// - "nl" should match "dbname=nlwiki",
			// - "dbname=nlwiktionary",
			// - "url=http://nl.wikipedia.org"
			// - "url=http://something-else.nl.org"
			$rows = LabsDB::query( LabsDB::getMetaDB(),
				'SELECT dbname, lang, name, family, url
				FROM wiki
				WHERE dbname LIKE :namewiki
				LIMIT 1',
				[
					':namewiki' => "{$normalised}wiki%",
				]
			);
			if ( !$rows ) {
				$rows = LabsDB::query( LabsDB::getMetaDB(),
					'SELECT dbname, lang, name, family, url
					FROM wiki
					WHERE dbname LIKE :nameany
					LIMIT 1',
					[
						':nameany' => "{$normalised}%",
					]
				);
			}
			if ( !$rows ) {
				$rows = LabsDB::query( LabsDB::getMetaDB(),
					'SELECT dbname, lang, name, family, url
					FROM wiki
					WHERE url LIKE :urlany
					LIMIT 1',
					[
						':urlany' => "%/{$normalised}%",
					]
				);
			}
			if ( !$rows ) {
				$rows = LabsDB::query( LabsDB::getMetaDB(),
					'SELECT dbname, lang, name, family, url
					FROM wiki
					WHERE url LIKE :anyurlany
					LIMIT 1',
					[
						':anyurlany' => "%{$normalised}%",
					]
				);
			}

			if ( isset( $rows[0] ) ) {
				$dbinfo = $rows[0];
				$result = [
					'dbname' => $dbinfo['dbname'],
					'lang' => $dbinfo['lang'],
					'name' => $dbinfo['name'],
					'family' => $dbinfo['family'],
					'server' => preg_replace( '/^http:/', '', $dbinfo['url'] ),
					'servername' => preg_replace( '/^https?:\/\//', '', $dbinfo['url'] ),
					'canonicalserver' => $dbinfo['url'],
					'scriptpath' => '/w',
					'apiurl' => $dbinfo['url'] . '/w/api.php',
				];
			}
		}

		return [
			'input' => $wikiid,
			'search' => $normalised,
			'match' => ( $result !== null ),
			'data' => $result,
		];
	}

	protected static function cleanWikiID( $wikiid ) {
		$clean = strtolower( trim( $wikiid ) );
		// While prepared statements ensure security, it doesn't escape LIKE syntax
		$clean = str_replace( '%', '\%', $clean );

		if ( substr( $clean, 0, 4 ) == 'http' && strpos( $clean, '://' ) ) {
			$clean = parse_url( $clean . '/', PHP_URL_HOST );

		} elseif ( substr( $clean, -1 ) == '/' ) {
			$clean = parse_url( 'http://' . $clean, PHP_URL_HOST );
		}

		return $clean ?: false;
	}
}
