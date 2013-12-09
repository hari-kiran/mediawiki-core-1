<?php
/**
 * HTML form generation and submission handling.
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
 */

/**
 * Object handling generic submission, CSRF protection, layout and
 * other logic for UI forms. in a reusable manner.
 *
 * In order to generate the form, the HTMLForm object takes an array
 * structure detailing the form fields available. Each element of the
 * array is a basic property-list, including the type of field, the
 * label it is to be given in the form, callbacks for validation and
 * 'filtering', and other pertinent information.
 *
 * Field types are implemented as subclasses of the generic HTMLFormField
 * object, and typically implement at least getInputHTML, which generates
 * the HTML for the input field to be placed in the table.
 *
 * You can find extensive documentation on the www.mediawiki.org wiki:
 *  - http://www.mediawiki.org/wiki/HTMLForm
 *  - http://www.mediawiki.org/wiki/HTMLForm/tutorial
 *
 * The constructor input is an associative array of $fieldname => $info,
 * where $info is an Associative Array with any of the following:
 *
 *	'class'               -- the subclass of HTMLFormField that will be used
 *	                         to create the object.  *NOT* the CSS class!
 *	'type'                -- roughly translates into the <select> type attribute.
 *	                         if 'class' is not specified, this is used as a map
 *	                         through HTMLForm::$typeMappings to get the class name.
 *	'default'             -- default value when the form is displayed
 *	'id'                  -- HTML id attribute
 *	'cssclass'            -- CSS class
 *	'options'             -- varies according to the specific object.
 *	'label-message'       -- message key for a message to use as the label.
 *	                         can be an array of msg key and then parameters to
 *	                         the message.
 *	'label'               -- alternatively, a raw text message. Overridden by
 *	                         label-message
 *	'help'                -- message text for a message to use as a help text.
 *	'help-message'        -- message key for a message to use as a help text.
 *	                         can be an array of msg key and then parameters to
 *	                         the message.
 *	                         Overwrites 'help-messages' and 'help'.
 *	'help-messages'       -- array of message key. As above, each item can
 *	                         be an array of msg key and then parameters.
 *	                         Overwrites 'help'.
 *	'required'            -- passed through to the object, indicating that it
 *	                         is a required field.
 *	'size'                -- the length of text fields
 *	'filter-callback      -- a function name to give you the chance to
 *	                         massage the inputted value before it's processed.
 *	                         @see HTMLForm::filter()
 *	'validation-callback' -- a function name to give you the chance
 *	                         to impose extra validation on the field input.
 *	                         @see HTMLForm::validate()
 *	'name'                -- By default, the 'name' attribute of the input field
 *	                         is "wp{$fieldname}".  If you want a different name
 *	                         (eg one without the "wp" prefix), specify it here and
 *	                         it will be used without modification.
 *
 * Since 1.20, you can chain mutators to ease the form generation:
 * @par Example:
 * @code
 * $form = new HTMLForm( $someFields );
 * $form->setMethod( 'get' )
 *      ->setWrapperLegendMsg( 'message-key' )
 *      ->prepareForm()
 *      ->displayForm( '' );
 * @endcode
 * Note that you will have prepareForm and displayForm at the end. Other
 * methods call done after that would simply not be part of the form :(
 *
 * TODO: Document 'section' / 'subsection' stuff
 */
class HTMLForm extends ContextSource {

	// A mapping of 'type' inputs onto standard HTMLFormField subclasses
	public static $typeMappings = array(
		'api' => 'HTMLApiField',
		'text' => 'HTMLTextField',
		'textarea' => 'HTMLTextAreaField',
		'select' => 'HTMLSelectField',
		'radio' => 'HTMLRadioField',
		'multiselect' => 'HTMLMultiSelectField',
		'check' => 'HTMLCheckField',
		'toggle' => 'HTMLCheckField',
		'int' => 'HTMLIntField',
		'float' => 'HTMLFloatField',
		'info' => 'HTMLInfoField',
		'selectorother' => 'HTMLSelectOrOtherField',
		'selectandother' => 'HTMLSelectAndOtherField',
		'submit' => 'HTMLSubmitField',
		'hidden' => 'HTMLHiddenField',
		'edittools' => 'HTMLEditTools',
		'checkmatrix' => 'HTMLCheckMatrix',

		// HTMLTextField will output the correct type="" attribute automagically.
		// There are about four zillion other HTML5 input types, like url, but
		// we don't use those at the moment, so no point in adding all of them.
		'email' => 'HTMLTextField',
		'password' => 'HTMLTextField',
	);

	protected $mMessagePrefix;

	/** @var HTMLFormField[] */
	protected $mFlatFields;

	protected $mFieldTree;
	protected $mShowReset = false;
	protected $mShowSubmit = true;
	public $mFieldData;

	protected $mSubmitCallback;
	protected $mValidationErrorMessage;

	protected $mPre = '';
	protected $mHeader = '';
	protected $mFooter = '';
	protected $mSectionHeaders = array();
	protected $mSectionFooters = array();
	protected $mPost = '';
	protected $mId;
	protected $mTableId = '';

	protected $mSubmitID;
	protected $mSubmitName;
	protected $mSubmitText;
	protected $mSubmitTooltip;

	protected $mTitle;
	protected $mMethod = 'post';

	/**
	 * Form action URL. false means we will use the URL to set Title
	 * @since 1.19
	 * @var bool|string
	 */
	protected $mAction = false;

	protected $mUseMultipart = false;
	protected $mHiddenFields = array();
	protected $mButtons = array();

	protected $mWrapperLegend = false;

	/**
	 * If true, sections that contain both fields and subsections will
	 * render their subsections before their fields.
	 *
	 * Subclasses may set this to false to render subsections after fields
	 * instead.
	 */
	protected $mSubSectionBeforeFields = true;

	/**
	 * Format in which to display form. For viable options,
	 * @see $availableDisplayFormats
	 * @var String
	 */
	protected $displayFormat = 'table';

	/**
	 * Available formats in which to display the form
	 * @var Array
	 */
	protected $availableDisplayFormats = array(
		'table',
		'div',
		'raw',
		'vform',
	);

	/**
	 * Build a new HTMLForm from an array of field attributes
	 * @param array $descriptor of Field constructs, as described above
	 * @param $context IContextSource available since 1.18, will become compulsory in 1.18.
	 *     Obviates the need to call $form->setTitle()
	 * @param string $messagePrefix a prefix to go in front of default messages
	 */
	public function __construct( $descriptor, /*IContextSource*/ $context = null, $messagePrefix = '' ) {
		if ( $context instanceof IContextSource ) {
			$this->setContext( $context );
			$this->mTitle = false; // We don't need them to set a title
			$this->mMessagePrefix = $messagePrefix;
		} elseif ( is_null( $context ) && $messagePrefix !== '' ) {
			$this->mMessagePrefix = $messagePrefix;
		} elseif ( is_string( $context ) && $messagePrefix === '' ) {
			// B/C since 1.18
			// it's actually $messagePrefix
			$this->mMessagePrefix = $context;
		}

		// Expand out into a tree.
		$loadedDescriptor = array();
		$this->mFlatFields = array();

		foreach ( $descriptor as $fieldname => $info ) {
			$section = isset( $info['section'] )
				? $info['section']
				: '';

			if ( isset( $info['type'] ) && $info['type'] == 'file' ) {
				$this->mUseMultipart = true;
			}

			$field = self::loadInputFromParameters( $fieldname, $info );
			// FIXME During field's construct, the parent form isn't available!
			// could add a 'parent' name-value to $info, could add a third parameter.
			$field->mParent = $this;

			// vform gets too much space if empty labels generate HTML.
			if ( $this->isVForm() ) {
				$field->setShowEmptyLabel( false );
			}

			$setSection =& $loadedDescriptor;
			if ( $section ) {
				$sectionParts = explode( '/', $section );

				while ( count( $sectionParts ) ) {
					$newName = array_shift( $sectionParts );

					if ( !isset( $setSection[$newName] ) ) {
						$setSection[$newName] = array();
					}

					$setSection =& $setSection[$newName];
				}
			}

			$setSection[$fieldname] = $field;
			$this->mFlatFields[$fieldname] = $field;
		}

		$this->mFieldTree = $loadedDescriptor;
	}

	/**
	 * Set format in which to display the form
	 * @param string $format the name of the format to use, must be one of
	 *        $this->availableDisplayFormats
	 * @throws MWException
	 * @since 1.20
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	public function setDisplayFormat( $format ) {
		if ( !in_array( $format, $this->availableDisplayFormats ) ) {
			throw new MWException( 'Display format must be one of ' . print_r( $this->availableDisplayFormats, true ) );
		}
		$this->displayFormat = $format;
		return $this;
	}

	/**
	 * Getter for displayFormat
	 * @since 1.20
	 * @return String
	 */
	public function getDisplayFormat() {
		return $this->displayFormat;
	}

	/**
	 * Test if displayFormat is 'vform'
	 * @since 1.22
	 * @return Bool
	 */
	public function isVForm() {
		return $this->displayFormat === 'vform';
	}

	/**
	 * Add the HTMLForm-specific JavaScript, if it hasn't been
	 * done already.
	 * @deprecated since 1.18 load modules with ResourceLoader instead
	 */
	static function addJS() {
		wfDeprecated( __METHOD__, '1.18' );
	}

	/**
	 * Initialise a new Object for the field
	 * @param $fieldname string
	 * @param string $descriptor input Descriptor, as described above
	 * @throws MWException
	 * @return HTMLFormField subclass
	 */
	static function loadInputFromParameters( $fieldname, $descriptor ) {
		if ( isset( $descriptor['class'] ) ) {
			$class = $descriptor['class'];
		} elseif ( isset( $descriptor['type'] ) ) {
			$class = self::$typeMappings[$descriptor['type']];
			$descriptor['class'] = $class;
		} else {
			$class = null;
		}

		if ( !$class ) {
			throw new MWException( "Descriptor with no class: " . print_r( $descriptor, true ) );
		}

		$descriptor['fieldname'] = $fieldname;

		# TODO
		# This will throw a fatal error whenever someone try to use
		# 'class' to feed a CSS class instead of 'cssclass'. Would be
		# great to avoid the fatal error and show a nice error.
		$obj = new $class( $descriptor );

		return $obj;
	}

	/**
	 * Prepare form for submission.
	 *
	 * @attention When doing method chaining, that should be the very last
	 * method call before displayForm().
	 *
	 * @throws MWException
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	function prepareForm() {
		# Check if we have the info we need
		if ( !$this->mTitle instanceof Title && $this->mTitle !== false ) {
			throw new MWException( "You must call setTitle() on an HTMLForm" );
		}

		# Load data from the request.
		$this->loadData();
		return $this;
	}

	/**
	 * Try submitting, with edit token check first
	 * @return Status|boolean
	 */
	function tryAuthorizedSubmit() {
		$result = false;

		$submit = false;
		if ( $this->getMethod() != 'post' ) {
			$submit = true; // no session check needed
		} elseif ( $this->getRequest()->wasPosted() ) {
			$editToken = $this->getRequest()->getVal( 'wpEditToken' );
			if ( $this->getUser()->isLoggedIn() || $editToken != null ) {
				// Session tokens for logged-out users have no security value.
				// However, if the user gave one, check it in order to give a nice
				// "session expired" error instead of "permission denied" or such.
				$submit = $this->getUser()->matchEditToken( $editToken );
			} else {
				$submit = true;
			}
		}

		if ( $submit ) {
			$result = $this->trySubmit();
		}

		return $result;
	}

	/**
	 * The here's-one-I-made-earlier option: do the submission if
	 * posted, or display the form with or without funky validation
	 * errors
	 * @return Bool or Status whether submission was successful.
	 */
	function show() {
		$this->prepareForm();

		$result = $this->tryAuthorizedSubmit();
		if ( $result === true || ( $result instanceof Status && $result->isGood() ) ) {
			return $result;
		}

		$this->displayForm( $result );
		return false;
	}

	/**
	 * Validate all the fields, and call the submission callback
	 * function if everything is kosher.
	 * @throws MWException
	 * @return Mixed Bool true == Successful submission, Bool false
	 *     == No submission attempted, anything else == Error to
	 *     display.
	 */
	function trySubmit() {
		# Check for validation
		foreach ( $this->mFlatFields as $fieldname => $field ) {
			if ( !empty( $field->mParams['nodata'] ) ) {
				continue;
			}
			if ( $field->validate(
					$this->mFieldData[$fieldname],
					$this->mFieldData )
				!== true
			) {
				return isset( $this->mValidationErrorMessage )
					? $this->mValidationErrorMessage
					: array( 'htmlform-invalid-input' );
			}
		}

		$callback = $this->mSubmitCallback;
		if ( !is_callable( $callback ) ) {
			throw new MWException( 'HTMLForm: no submit callback provided. Use setSubmitCallback() to set one.' );
		}

		$data = $this->filterDataForSubmit( $this->mFieldData );

		$res = call_user_func( $callback, $data, $this );

		return $res;
	}

	/**
	 * Set a callback to a function to do something with the form
	 * once it's been successfully validated.
	 * @param string $cb function name.  The function will be passed
	 *	 the output from HTMLForm::filterDataForSubmit, and must
	 *	 return Bool true on success, Bool false if no submission
	 *	 was attempted, or String HTML output to display on error.
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	function setSubmitCallback( $cb ) {
		$this->mSubmitCallback = $cb;
		return $this;
	}

	/**
	 * Set a message to display on a validation error.
	 * @param $msg Mixed String or Array of valid inputs to wfMessage()
	 *	 (so each entry can be either a String or Array)
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	function setValidationErrorMessage( $msg ) {
		$this->mValidationErrorMessage = $msg;
		return $this;
	}

	/**
	 * Set the introductory message, overwriting any existing message.
	 * @param string $msg complete text of message to display
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	function setIntro( $msg ) {
		$this->setPreText( $msg );
		return $this;
	}

	/**
	 * Set the introductory message, overwriting any existing message.
	 * @since 1.19
	 * @param string $msg complete text of message to display
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	function setPreText( $msg ) {
		$this->mPre = $msg;
		return $this;
	}

	/**
	 * Add introductory text.
	 * @param string $msg complete text of message to display
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	function addPreText( $msg ) {
		$this->mPre .= $msg;
		return $this;
	}

	/**
	 * Add header text, inside the form.
	 * @param string $msg complete text of message to display
	 * @param string $section The section to add the header to
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	function addHeaderText( $msg, $section = null ) {
		if ( is_null( $section ) ) {
			$this->mHeader .= $msg;
		} else {
			if ( !isset( $this->mSectionHeaders[$section] ) ) {
				$this->mSectionHeaders[$section] = '';
			}
			$this->mSectionHeaders[$section] .= $msg;
		}
		return $this;
	}

	/**
	 * Set header text, inside the form.
	 * @since 1.19
	 * @param string $msg complete text of message to display
	 * @param $section The section to add the header to
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	function setHeaderText( $msg, $section = null ) {
		if ( is_null( $section ) ) {
			$this->mHeader = $msg;
		} else {
			$this->mSectionHeaders[$section] = $msg;
		}
		return $this;
	}

	/**
	 * Add footer text, inside the form.
	 * @param string $msg complete text of message to display
	 * @param string $section The section to add the footer text to
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	function addFooterText( $msg, $section = null ) {
		if ( is_null( $section ) ) {
			$this->mFooter .= $msg;
		} else {
			if ( !isset( $this->mSectionFooters[$section] ) ) {
				$this->mSectionFooters[$section] = '';
			}
			$this->mSectionFooters[$section] .= $msg;
		}
		return $this;
	}

	/**
	 * Set footer text, inside the form.
	 * @since 1.19
	 * @param string $msg complete text of message to display
	 * @param string $section The section to add the footer text to
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	function setFooterText( $msg, $section = null ) {
		if ( is_null( $section ) ) {
			$this->mFooter = $msg;
		} else {
			$this->mSectionFooters[$section] = $msg;
		}
		return $this;
	}

	/**
	 * Add text to the end of the display.
	 * @param string $msg complete text of message to display
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	function addPostText( $msg ) {
		$this->mPost .= $msg;
		return $this;
	}

	/**
	 * Set text at the end of the display.
	 * @param string $msg complete text of message to display
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	function setPostText( $msg ) {
		$this->mPost = $msg;
		return $this;
	}

	/**
	 * Add a hidden field to the output
	 * @param string $name field name.  This will be used exactly as entered
	 * @param string $value field value
	 * @param $attribs Array
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	public function addHiddenField( $name, $value, $attribs = array() ) {
		$attribs += array( 'name' => $name );
		$this->mHiddenFields[] = array( $value, $attribs );
		return $this;
	}

	/**
	 * Add an array of hidden fields to the output
	 *
	 * @since 1.22
	 * @param array $fields Associative array of fields to add;
	 *        mapping names to their values
	 * @return HTMLForm $this for chaining calls
	 */
	public function addHiddenFields( array $fields ) {
		foreach ( $fields as $name => $value ) {
			$this->mHiddenFields[] = array( $value, array( 'name' => $name ) );
		}
		return $this;
	}

	/**
	 * Add a button to the form
	 * @param string $name field name.
	 * @param string $value field value
	 * @param string $id DOM id for the button (default: null)
	 * @param $attribs Array
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	public function addButton( $name, $value, $id = null, $attribs = null ) {
		$this->mButtons[] = compact( 'name', 'value', 'id', 'attribs' );
		return $this;
	}

	/**
	 * Display the form (sending to the context's OutputPage object), with an
	 * appropriate error message or stack of messages, and any validation errors, etc.
	 *
	 * @attention You should call prepareForm() before calling this function.
	 * Moreover, when doing method chaining this should be the very last method
	 * call just after prepareForm().
	 *
	 * @param $submitResult Mixed output from HTMLForm::trySubmit()
	 * @return Nothing, should be last call
	 */
	function displayForm( $submitResult ) {
		$this->getOutput()->addHTML( $this->getHTML( $submitResult ) );
	}

	/**
	 * Returns the raw HTML generated by the form
	 * @param $submitResult Mixed output from HTMLForm::trySubmit()
	 * @return string
	 */
	function getHTML( $submitResult ) {
		# For good measure (it is the default)
		$this->getOutput()->preventClickjacking();
		$this->getOutput()->addModules( 'mediawiki.htmlform' );
		if ( $this->isVForm() ) {
			$this->getOutput()->addModuleStyles( 'mediawiki.ui' );
			// TODO should vertical form set setWrapperLegend( false )
			// to hide ugly fieldsets?
		}

		$html = ''
			. $this->getErrors( $submitResult )
			. $this->mHeader
			. $this->getBody()
			. $this->getHiddenFields()
			. $this->getButtons()
			. $this->mFooter;

		$html = $this->wrapForm( $html );

		return '' . $this->mPre . $html . $this->mPost;
	}

	/**
	 * Wrap the form innards in an actual "<form>" element
	 * @param string $html HTML contents to wrap.
	 * @return String wrapped HTML.
	 */
	function wrapForm( $html ) {

		# Include a <fieldset> wrapper for style, if requested.
		if ( $this->mWrapperLegend !== false ) {
			$html = Xml::fieldset( $this->mWrapperLegend, $html );
		}
		# Use multipart/form-data
		$encType = $this->mUseMultipart
			? 'multipart/form-data'
			: 'application/x-www-form-urlencoded';
		# Attributes
		$attribs = array(
			'action' => $this->getAction(),
			'method' => $this->getMethod(),
			'class' => array( 'visualClear' ),
			'enctype' => $encType,
		);
		if ( !empty( $this->mId ) ) {
			$attribs['id'] = $this->mId;
		}

		if ( $this->isVForm() ) {
			array_push( $attribs['class'], 'mw-ui-vform', 'mw-ui-container' );
		}
		return Html::rawElement( 'form', $attribs, $html );
	}

	/**
	 * Get the hidden fields that should go inside the form.
	 * @return String HTML.
	 */
	function getHiddenFields() {
		global $wgArticlePath;

		$html = '';
		if ( $this->getMethod() == 'post' ) {
			$html .= Html::hidden( 'wpEditToken', $this->getUser()->getEditToken(), array( 'id' => 'wpEditToken' ) ) . "\n";
			$html .= Html::hidden( 'title', $this->getTitle()->getPrefixedText() ) . "\n";
		}

		if ( strpos( $wgArticlePath, '?' ) !== false && $this->getMethod() == 'get' ) {
			$html .= Html::hidden( 'title', $this->getTitle()->getPrefixedText() ) . "\n";
		}

		foreach ( $this->mHiddenFields as $data ) {
			list( $value, $attribs ) = $data;
			$html .= Html::hidden( $attribs['name'], $value, $attribs ) . "\n";
		}

		return $html;
	}

	/**
	 * Get the submit and (potentially) reset buttons.
	 * @return String HTML.
	 */
	function getButtons() {
		$html = '<span class="mw-htmlform-submit-buttons">';

		if ( $this->mShowSubmit ) {
			$attribs = array();

			if ( isset( $this->mSubmitID ) ) {
				$attribs['id'] = $this->mSubmitID;
			}

			if ( isset( $this->mSubmitName ) ) {
				$attribs['name'] = $this->mSubmitName;
			}

			if ( isset( $this->mSubmitTooltip ) ) {
				$attribs += Linker::tooltipAndAccesskeyAttribs( $this->mSubmitTooltip );
			}

			$attribs['class'] = array( 'mw-htmlform-submit' );

			if ( $this->isVForm() ) {
				// mw-ui-block is necessary because the buttons aren't necessarily in an
				// immediate child div of the vform.
				array_push( $attribs['class'], 'mw-ui-button', 'mw-ui-big', 'mw-ui-primary', 'mw-ui-block' );
			}

			$html .= Xml::submitButton( $this->getSubmitText(), $attribs ) . "\n";

			// Buttons are top-level form elements in table and div layouts,
			// but vform wants all elements inside divs to get spaced-out block
			// styling.
			if ( $this->isVForm() ) {
				$html = Html::rawElement( 'div', null, "\n$html\n" );
			}
		}

		if ( $this->mShowReset ) {
			$html .= Html::element(
				'input',
				array(
					'type' => 'reset',
					'value' => $this->msg( 'htmlform-reset' )->text()
				)
			) . "\n";
		}

		foreach ( $this->mButtons as $button ) {
			$attrs = array(
				'type' => 'submit',
				'name' => $button['name'],
				'value' => $button['value']
			);

			if ( $button['attribs'] ) {
				$attrs += $button['attribs'];
			}

			if ( isset( $button['id'] ) ) {
				$attrs['id'] = $button['id'];
			}

			$html .= Html::element( 'input', $attrs );
		}

		$html .= '</span>';

		return $html;
	}

	/**
	 * Get the whole body of the form.
	 * @return String
	 */
	function getBody() {
		return $this->displaySection( $this->mFieldTree, $this->mTableId );
	}

	/**
	 * Format and display an error message stack.
	 * @param $errors String|Array|Status
	 * @return String
	 */
	function getErrors( $errors ) {
		if ( $errors instanceof Status ) {
			if ( $errors->isOK() ) {
				$errorstr = '';
			} else {
				$errorstr = $this->getOutput()->parse( $errors->getWikiText() );
			}
		} elseif ( is_array( $errors ) ) {
			$errorstr = $this->formatErrors( $errors );
		} else {
			$errorstr = $errors;
		}

		return $errorstr
			? Html::rawElement( 'div', array( 'class' => 'error' ), $errorstr )
			: '';
	}

	/**
	 * Format a stack of error messages into a single HTML string
	 * @param array $errors of message keys/values
	 * @return String HTML, a "<ul>" list of errors
	 */
	public static function formatErrors( $errors ) {
		$errorstr = '';

		foreach ( $errors as $error ) {
			if ( is_array( $error ) ) {
				$msg = array_shift( $error );
			} else {
				$msg = $error;
				$error = array();
			}

			$errorstr .= Html::rawElement(
				'li',
				array(),
				wfMessage( $msg, $error )->parse()
			);
		}

		$errorstr = Html::rawElement( 'ul', array(), $errorstr );

		return $errorstr;
	}

	/**
	 * Set the text for the submit button
	 * @param string $t plaintext.
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	function setSubmitText( $t ) {
		$this->mSubmitText = $t;
		return $this;
	}

	/**
	 * Set the text for the submit button to a message
	 * @since 1.19
	 * @param string $msg message key
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	public function setSubmitTextMsg( $msg ) {
		$this->setSubmitText( $this->msg( $msg )->text() );
		return $this;
	}

	/**
	 * Get the text for the submit button, either customised or a default.
	 * @return string
	 */
	function getSubmitText() {
		return $this->mSubmitText
			? $this->mSubmitText
			: $this->msg( 'htmlform-submit' )->text();
	}

	/**
	 * @param string $name Submit button name
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	public function setSubmitName( $name ) {
		$this->mSubmitName = $name;
		return $this;
	}

	/**
	 * @param string $name Tooltip for the submit button
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	public function setSubmitTooltip( $name ) {
		$this->mSubmitTooltip = $name;
		return $this;
	}

	/**
	 * Set the id for the submit button.
	 * @param $t String.
	 * @todo FIXME: Integrity of $t is *not* validated
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	function setSubmitID( $t ) {
		$this->mSubmitID = $t;
		return $this;
	}

	/**
	 * Stop a default submit button being shown for this form. This implies that an
	 * alternate submit method must be provided manually.
	 *
	 * @since 1.22
	 *
	 * @param bool $suppressSubmit Set to false to re-enable the button again
	 *
	 * @return HTMLForm $this for chaining calls
	 */
	function suppressDefaultSubmit( $suppressSubmit = true ) {
		$this->mShowSubmit = !$suppressSubmit;
		return $this;
	}

	/**
	 * Set the id of the \<table\> or outermost \<div\> element.
	 *
	 * @since 1.22
	 * @param string $id new value of the id attribute, or "" to remove
	 * @return HTMLForm $this for chaining calls
	 */
	public function setTableId( $id ) {
		$this->mTableId = $id;
		return $this;
	}

	/**
	 * @param string $id DOM id for the form
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	public function setId( $id ) {
		$this->mId = $id;
		return $this;
	}

	/**
	 * Prompt the whole form to be wrapped in a "<fieldset>", with
	 * this text as its "<legend>" element.
	 * @param string|false $legend HTML to go inside the "<legend>" element, or
	 * false for no <legend>
	 *	 Will be escaped
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	public function setWrapperLegend( $legend ) {
		$this->mWrapperLegend = $legend;
		return $this;
	}

	/**
	 * Prompt the whole form to be wrapped in a "<fieldset>", with
	 * this message as its "<legend>" element.
	 * @since 1.19
	 * @param string $msg message key
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	public function setWrapperLegendMsg( $msg ) {
		$this->setWrapperLegend( $this->msg( $msg )->text() );
		return $this;
	}

	/**
	 * Set the prefix for various default messages
	 * @todo currently only used for the "<fieldset>" legend on forms
	 * with multiple sections; should be used elsewhere?
	 * @param $p String
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	function setMessagePrefix( $p ) {
		$this->mMessagePrefix = $p;
		return $this;
	}

	/**
	 * Set the title for form submission
	 * @param $t Title of page the form is on/should be posted to
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	function setTitle( $t ) {
		$this->mTitle = $t;
		return $this;
	}

	/**
	 * Get the title
	 * @return Title
	 */
	function getTitle() {
		return $this->mTitle === false
			? $this->getContext()->getTitle()
			: $this->mTitle;
	}

	/**
	 * Set the method used to submit the form
	 * @param $method String
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	public function setMethod( $method = 'post' ) {
		$this->mMethod = $method;
		return $this;
	}

	public function getMethod() {
		return $this->mMethod;
	}

	/**
	 * @todo Document
	 * @param array[]|HTMLFormField[] $fields array of fields (either arrays or objects)
	 * @param string $sectionName ID attribute of the "<table>" tag for this section, ignored if empty
	 * @param string $fieldsetIDPrefix ID prefix for the "<fieldset>" tag of each subsection, ignored if empty
	 * @param boolean &$hasUserVisibleFields Whether the section had user-visible fields
	 * @return String
	 */
	public function displaySection( $fields, $sectionName = '', $fieldsetIDPrefix = '', &$hasUserVisibleFields = false ) {
		$displayFormat = $this->getDisplayFormat();

		$html = '';
		$subsectionHtml = '';
		$hasLabel = false;

		switch ( $displayFormat ) {
			case 'table':
				$getFieldHtmlMethod = 'getTableRow';
				break;
			case 'vform':
				// Close enough to a div.
				$getFieldHtmlMethod = 'getDiv';
				break;
			default:
				$getFieldHtmlMethod = 'get' . ucfirst( $displayFormat );
		}

		foreach ( $fields as $key => $value ) {
			if ( $value instanceof HTMLFormField ) {
				$v = empty( $value->mParams['nodata'] )
					? $this->mFieldData[$key]
					: $value->getDefault();
				$html .= $value->$getFieldHtmlMethod( $v );

				$labelValue = trim( $value->getLabel() );
				if ( $labelValue != '&#160;' && $labelValue !== '' ) {
					$hasLabel = true;
				}

				if ( get_class( $value ) !== 'HTMLHiddenField' &&
						get_class( $value ) !== 'HTMLApiField' ) {
					$hasUserVisibleFields = true;
				}
			} elseif ( is_array( $value ) ) {
				$subsectionHasVisibleFields = false;
				$section = $this->displaySection( $value, "mw-htmlform-$key", "$fieldsetIDPrefix$key-", $subsectionHasVisibleFields );
				$legend = null;

				if ( $subsectionHasVisibleFields === true ) {
					// Display the section with various niceties.
					$hasUserVisibleFields = true;

					$legend = $this->getLegend( $key );

					if ( isset( $this->mSectionHeaders[$key] ) ) {
						$section = $this->mSectionHeaders[$key] . $section;
					}
					if ( isset( $this->mSectionFooters[$key] ) ) {
						$section .= $this->mSectionFooters[$key];
					}

					$attributes = array();
					if ( $fieldsetIDPrefix ) {
						$attributes['id'] = Sanitizer::escapeId( "$fieldsetIDPrefix$key" );
					}
					$subsectionHtml .= Xml::fieldset( $legend, $section, $attributes ) . "\n";
				} else {
					// Just return the inputs, nothing fancy.
					$subsectionHtml .= $section;
				}
			}
		}

		if ( $displayFormat !== 'raw' ) {
			$classes = array();

			if ( !$hasLabel ) { // Avoid strange spacing when no labels exist
				$classes[] = 'mw-htmlform-nolabel';
			}

			$attribs = array(
				'class' => implode( ' ', $classes ),
			);

			if ( $sectionName ) {
				$attribs['id'] = Sanitizer::escapeId( $sectionName );
			}

			if ( $displayFormat === 'table' ) {
				$html = Html::rawElement( 'table', $attribs,
					Html::rawElement( 'tbody', array(), "\n$html\n" ) ) . "\n";
			} elseif ( $displayFormat === 'div' || $displayFormat === 'vform' ) {
				$html = Html::rawElement( 'div', $attribs, "\n$html\n" );
			}
		}

		if ( $this->mSubSectionBeforeFields ) {
			return $subsectionHtml . "\n" . $html;
		} else {
			return $html . "\n" . $subsectionHtml;
		}
	}

	/**
	 * Construct the form fields from the Descriptor array
	 */
	function loadData() {
		$fieldData = array();

		foreach ( $this->mFlatFields as $fieldname => $field ) {
			if ( !empty( $field->mParams['nodata'] ) ) {
				continue;
			} elseif ( !empty( $field->mParams['disabled'] ) ) {
				$fieldData[$fieldname] = $field->getDefault();
			} else {
				$fieldData[$fieldname] = $field->loadDataFromRequest( $this->getRequest() );
			}
		}

		# Filter data.
		foreach ( $fieldData as $name => &$value ) {
			$field = $this->mFlatFields[$name];
			$value = $field->filter( $value, $this->mFlatFields );
		}

		$this->mFieldData = $fieldData;
	}

	/**
	 * Stop a reset button being shown for this form
	 * @param bool $suppressReset set to false to re-enable the
	 *	 button again
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	function suppressReset( $suppressReset = true ) {
		$this->mShowReset = !$suppressReset;
		return $this;
	}

	/**
	 * Overload this if you want to apply special filtration routines
	 * to the form as a whole, after it's submitted but before it's
	 * processed.
	 * @param $data
	 * @return
	 */
	function filterDataForSubmit( $data ) {
		return $data;
	}

	/**
	 * Get a string to go in the "<legend>" of a section fieldset.
	 * Override this if you want something more complicated.
	 * @param $key String
	 * @return String
	 */
	public function getLegend( $key ) {
		return $this->msg( "{$this->mMessagePrefix}-$key" )->text();
	}

	/**
	 * Set the value for the action attribute of the form.
	 * When set to false (which is the default state), the set title is used.
	 *
	 * @since 1.19
	 *
	 * @param string|bool $action
	 * @return HTMLForm $this for chaining calls (since 1.20)
	 */
	public function setAction( $action ) {
		$this->mAction = $action;
		return $this;
	}

	/**
	 * Get the value for the action attribute of the form.
	 *
	 * @since 1.22
	 *
	 * @return string
	 */
	public function getAction() {
		global $wgScript, $wgArticlePath;

		// If an action is alredy provided, return it
		if ( $this->mAction !== false ) {
			return $this->mAction;
		}

		// Check whether we are in GET mode and $wgArticlePath contains a "?"
		// meaning that getLocalURL() would return something like "index.php?title=...".
		// As browser remove the query string before submitting GET forms,
		// it means that the title would be lost. In such case use $wgScript instead
		// and put title in an hidden field (see getHiddenFields()).
		if ( strpos( $wgArticlePath, '?' ) !== false && $this->getMethod() === 'get' ) {
			return $wgScript;
		}

		return $this->getTitle()->getLocalURL();
	}
}