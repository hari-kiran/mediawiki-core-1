<?php
/**
 * Implements Special:ExpandTemplates
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup SpecialPage
 */

/**
 * A special page that expands submitted templates, parser functions,
 * and variables, allowing easier debugging of these.
 *
 * @ingroup SpecialPage
 */
class SpecialExpandTemplates extends SpecialPage {

	/** @var boolean whether or not to show the XML parse tree */
	protected $generateXML;

	/** @var boolean whether or not to remove comments in the expanded wikitext */
	protected $removeComments;

	/** @var boolean whether or not to remove <nowiki> tags in the expanded wikitext */
	protected $removeNowiki;

	/** @var maximum size in bytes to include. 50MB allows fixing those huge pages */
	const MAX_INCLUDE_SIZE = 50000000;

	function __construct() {
		parent::__construct( 'ExpandTemplates' );
	}

	/**
	 * Show the special page
	 */
	function execute( $subpage ) {
		global $wgParser, $wgUseTidy, $wgAlwaysUseTidy;

		$this->setHeaders();

		$request = $this->getRequest();
		$titleStr = $request->getText( 'wpContextTitle' );
		$title = Title::newFromText( $titleStr );

		if ( !$title ) {
			$title = $this->getTitle();
		}
		$input = $request->getText( 'wpInput' );
		$this->generateXML = $request->getBool( 'wpGenerateXml' );

		if ( strlen( $input ) ) {
			$this->removeComments = $request->getBool( 'wpRemoveComments', false );
			$this->removeNowiki = $request->getBool( 'wpRemoveNowiki', false );
			$options = ParserOptions::newFromContext( $this->getContext() );
			$options->setRemoveComments( $this->removeComments );
			$options->setTidy( true );
			$options->setMaxIncludeSize( self::MAX_INCLUDE_SIZE );

			if ( $this->generateXML ) {
				$wgParser->startExternalParse( $title, $options, OT_PREPROCESS );
				$dom = $wgParser->preprocessToDom( $input );

				if ( method_exists( $dom, 'saveXML' ) ) {
					$xml = $dom->saveXML();
				} else {
					$xml = $dom->__toString();
				}
			}

			$output = $wgParser->preprocess( $input, $title, $options );
		} else {
			$this->removeComments = $request->getBool( 'wpRemoveComments', true );
			$this->removeNowiki = $request->getBool( 'wpRemoveNowiki', false );
			$output = false;
		}

		$out = $this->getOutput();
		$out->addWikiMsg( 'expand_templates_intro' );
		$out->addHTML( $this->makeForm( $titleStr, $input ) );

		if ( $output !== false ) {
			if ( $this->generateXML && strlen( $output ) > 0 ) {
				$out->addHTML( $this->makeOutput( $xml, 'expand_templates_xml_output' ) );
			}

			$tmp = $this->makeOutput( $output );

			if ( $this->removeNowiki ) {
				$tmp = preg_replace(
					array( '_&lt;nowiki&gt;_', '_&lt;/nowiki&gt;_', '_&lt;nowiki */&gt;_' ),
					'',
					$tmp
				);
			}

			if ( ( $wgUseTidy && $options->getTidy() ) || $wgAlwaysUseTidy ) {
				$tmp = MWTidy::tidy( $tmp );
			}

			$out->addHTML( $tmp );
			$this->showHtmlPreview( $title, $output, $out );
		}

	}

	/**
	 * Generate a form allowing users to enter information
	 *
	 * @param string $title Value for context title field
	 * @param string $input Value for input textbox
	 * @return string
	 */
	private function makeForm( $title, $input ) {
		$self = $this->getTitle();
		$form = Xml::openElement(
			'form',
			array( 'method' => 'post', 'action' => $self->getLocalUrl() )
		);
		$form .= "<fieldset><legend>" . $this->msg( 'expandtemplates' )->escaped() . "</legend>\n";

		$form .= '<p>' . Xml::inputLabel(
			$this->msg( 'expand_templates_title' )->plain(),
			'wpContextTitle',
			'contexttitle',
			60,
			$title,
			array( 'autofocus' => true )
		) . '</p>';
		$form .= '<p>' . Xml::label(
			$this->msg( 'expand_templates_input' )->text(),
			'input'
		) . '</p>';
		$form .= Xml::textarea(
			'wpInput',
			$input,
			10,
			10,
			array( 'id' => 'input' )
		);

		$form .= '<p>' . Xml::checkLabel(
			$this->msg( 'expand_templates_remove_comments' )->text(),
			'wpRemoveComments',
			'removecomments',
			$this->removeComments
		) . '</p>';
		$form .= '<p>' . Xml::checkLabel(
			$this->msg( 'expand_templates_remove_nowiki' )->text(),
			'wpRemoveNowiki',
			'removenowiki',
			$this->removeNowiki
		) . '</p>';
		$form .= '<p>' . Xml::checkLabel(
			$this->msg( 'expand_templates_generate_xml' )->text(),
			'wpGenerateXml',
			'generate_xml',
			$this->generateXML
		) . '</p>';
		$form .= '<p>' . Xml::submitButton(
			$this->msg( 'expand_templates_ok' )->text(),
			array( 'accesskey' => 's' )
		) . '</p>';
		$form .= "</fieldset>\n";
		$form .= Xml::closeElement( 'form' );

		return $form;
	}

	/**
	 * Generate a nice little box with a heading for output
	 *
	 * @param string $output Wiki text output
	 * @param string $heading
	 * @return string
	 */
	private function makeOutput( $output, $heading = 'expand_templates_output' ) {
		$out = "<h2>" . $this->msg( $heading )->escaped() . "</h2>\n";
		$out .= Xml::textarea(
			'output',
			$output,
			10,
			10,
			array( 'id' => 'output', 'readonly' => 'readonly' )
		);

		return $out;
	}

	/**
	 * Render the supplied wiki text and append to the page as a preview
	 *
	 * @param Title $title
	 * @param string $text
	 * @param OutputPage $out
	 */
	private function showHtmlPreview( Title $title, $text, OutputPage $out ) {
		global $wgParser;

		$popts = ParserOptions::newFromContext( $this->getContext() );
		$popts->setTargetLanguage( $title->getPageLanguage() );
		$pout = $wgParser->parse( $text, $title, $popts );
		$lang = $title->getPageViewLanguage();

		$out->addHTML( "<h2>" . $this->msg( 'expand_templates_preview' )->escaped() . "</h2>\n" );
		$out->addHTML( Html::openElement( 'div', array(
			'class' => 'mw-content-' . $lang->getDir(),
			'dir' => $lang->getDir(),
			'lang' => $lang->getHtmlCode(),
		) ) );

		$out->addHTML( $pout->getText() );
		$out->addHTML( Html::closeElement( 'div' ) );
	}
}
