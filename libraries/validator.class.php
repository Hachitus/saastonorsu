<?php
################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 #
## --------------------------------------------------------------------------- #
##  ApPHP DataValidator Basic version 1.0.1 (14.04.2012)                       #
##  Developed by:  ApPHP <info@apphp.com>                                      #
##  License:       GNU GPL v.2                                                 #
##  Site:          http://www.apphp.com/php-tabs/                              #
##  Copyright:     ApPHP DataValidator (c) 2012. All rights reserved.          #
##                                                                             #
################################################################################

/**
 *  Main class, excepts validation types, runs validation
 */
class Validator
{
    /**
     * indicates whether validation runs on after error or stops after first one
     * @var bool
     */
    private $stopIfErrorFlag = false;
    /**
     * contains all found errors
     * @var array
     */
    private $errorArray = array();
    /**
     * @property $validationArray array
     * contains object of validation types
     * @var array
     */
    private $validationArray = array();
    /**
     * whether we have errors
     * @var int
     */
    private $hasErrorStatus = 0;

    /**
     * add an object to be validated
     * @param ValidatorType $type
     */
    public function AddType( ValidatorType $type ) {
        $this->validationArray[ ] = $type;
    }

    /**
     *  launch validation
     */
    public function Validate() {
        foreach ( $this->validationArray as $type ) {
            if ( !is_a( $type, 'ValidatorAnyType' ) ) {
                throw new Exception( 'Only objects of ValidatorAnyType child classes are to be sent to validation' );
            }
            if ( $this->_AllowToProceed() ) {
                $type->Validate();
                $this->_CollectError( $type );
            }
        }
    }

    /**
     * Getter for errorArray, consists of ValidatorError objects
     * @return array
     */
    public function GetErrorArray() {
        return $this->errorArray;
    }

    /**
     * Setter for stopIfErrorFlag
     * @param $stopIfErrorFlag
     */
    public function SetStopIfErrorFlag( $stopIfErrorFlag ) {
        $this->stopIfErrorFlag = $stopIfErrorFlag;
    }

    /**
     * Getter for hasErrorStatus
     * @return int
     */
    public function GetHasErrorStatus() {
        return $this->hasErrorStatus;
    }

    /**
     * if stopIfErrorFlag is set and errors found, validation will be stopped
     * @return bool
     */
    private function _AllowToProceed() {
        if ( !empty( $this->stopIfErrorFlag ) && !empty( $this->errorArray ) ) {
            return false;
        }
        return true;
    }

    /**
     * Collects errors from a validatorType into one for all errorArray
     * @param ValidatorType $type
     */
    private function _CollectError( ValidatorType $type ) {
        if ( $type->GetHasError() ) {
            $this->hasErrorStatus = 1;
            $this->errorArray = array_merge( $this->errorArray, $type->GetErrorArray() );
        }
    }
}

/**
 *  Stores data as array(key=>value), gets an array or object with data from user or uses $_REQUEST by default
 */
class ValidatorDataContainer
{
    /**
     * array where values for validation will be looked for
     * @var #Fget_object_vars|#V_REQUEST|array|?
     */
    private $dataContainer = array();

    /*
     * set custom array or $_REQUEST will be used
     * @param array $data
     */
    public function __construct( $data = array() ) {
        if ( is_object( $data ) ) {
            $this->dataContainer = get_object_vars( $data );
        }
        elseif ( is_array( $data ) ) {
            $this->dataContainer = ( !empty( $data ) ) ? $data : $_REQUEST;
        }
        else {
            $this->dataContainer = $_REQUEST;
        }
    }

    /**
     * gets a value from data array by name
     * @param $name
     * @return mixed
     * @throws Exception if wrong name
     */
    public function GetValue( $name ) {
        if ( !isset( $this->dataContainer[ $name ] ) ) {
            throw new Exception( "No such field '$name' in data container" );
        }
        return $this->dataContainer[ $name ];
    }

    /**
     * Checks if field exists
     * @param $name
     * @return bool
     */
    public function IfFieldExists( $name ) {
        if ( isset( $this->dataContainer[ $name ] ) ) {
            return true;
        }
        return false;
    }
}

/**
 * Validator types must be validated, give error messages, be able to show there description.
 */
interface ValidatorType
{
    public function Validate();

    public function GetHasError();

    public function GetErrorArray();

    public function ToString();
}

/**
 *  Validator type parent
 */
class ValidatorAnyType
{
    /**
     * if subtype is needed, it should be set from validation type static public properties which starts with "subtype"
     * @var string
     */
    protected $subtype;
    /**
     * name for field in array
     * @var
     */
    protected $name;
    /**
     * name for error messages - if needed. $name will be used if var is empty
     * @var string
     */
    protected $userFriendlyName;

    /**
     * if any errors has got while validating this type
     * @var
     */
    private $hasError;
    /**
     * all errors for this type
     * @var array
     */
    private $errorArray = array();

    /**
     * if field can be left empty (empty is: "", 0, null, "null")
     * @var 0
     */
    protected $canBeNullFlag = 0;

    /**
     * @param ValidatorDataContainer $dataContainer - where we look for fields
     * @param $name - by what name look for field
     * @param string $subtype - might be empty, but needed for string and numbers
     * @param string $userFriendlyName - might be empty, used for error messages
     */
    protected function __construct( ValidatorDataContainer $dataContainer, $name, $subtype = '', $userFriendlyName = '' ) {
        if ( empty( $name ) ) {
            throw new Exception( 'Field name should not be empty' );
        }

        $this->name = $name;
        $this->subtype = $subtype;
        $this->userFriendlyName = !empty( $userFriendlyName ) ? $userFriendlyName : $name;
        $this->value = $dataContainer->getValue( $name );
    }

    /**
     * getter for a has errors flag
     * @return mixed
     */
    public function GetHasError() {
        return $this->hasError;
    }

    /**
     *  Getter for errorArray
     * @return array
     */
    public function GetErrorArray() {
        return $this->errorArray;
    }

    /**
     * setter if string can be empty (null, "", "null", 0) default - 0
     * @param $canBeNullFlag
     */
    public function SetCanBeNullFlag( $canBeNullFlag ) {
        $this->canBeNullFlag = $canBeNullFlag;
    }

    /**
     * if string can be empty (null, "", "null", 0)
     * @return bool
     */
    protected function _EmptinessCheck() {
        // if empty and can be empty - consider checked
        if ( $this->canBeNullFlag && ( $this->value === null || $this->value === 'null' || $this->value === '' || $this->value === 0 ) ) {
            return true;
        }
        // if empty and can't be empty - error
        if ( !$this->canBeNullFlag && ( $this->value === null || $this->value === 'null' || $this->value === '' || $this->value === 0 ) ) {
            $this->_HasError( new ValidatorError ( 'CAN_NOT_BE_NULL', $this->name,  array( 'fieldName' => $this->userFriendlyName ) ) );
            return true;
        }
        return false;
    }

    /**
     * actions for error - add error to array, set status hasError
     * @param ValidatorError $error
     */
    protected function _HasError( ValidatorError $error ) {
        $this->errorArray[ ] = $error;
        $this->hasError = 1;
    }
}

/**
 * Strings for validation
 */
class ValidatorTypeString extends ValidatorAnyType implements ValidatorType
{
    /**
     * subtypes
     * @var string
     */
    public static $subtypeAlphabetic = 'alphabetic';
    public static $subtypeAlphanumeric = 'alphanumeric';

    /**
     * additional options
     * @var null
     */
    private $minLen = null;
    private $maxLen = null;
    private $spacesAllowedFlag = null;
    private $pointingAllowedFlag = null;

    /**
     * @param ValidatorDataContainer $dataContainer containter where all values are kept
     * @param $name a name of field in a container
     * @param $subtype a subtype, use public static parameters of this class named starting with "subtype"
     * @param string $userFriendlyName name used in user-friendly error messages, if not set $name will be used
     */
    function __construct( ValidatorDataContainer $dataContainer, $name, $subtype, $userFriendlyName = '' ) {
        if ( empty( $subtype ) ) {
            throw new Exception( "Subtype for string $name can not be empty" );
        }
        parent::__construct( $dataContainer, $name, $subtype, $userFriendlyName );
    }

    /**
     * Validates current string
     * @return mixed
     */
    public function Validate() {
        // if field empty, no use to check any more. Can have errors thou
        if ( $this->_EmptinessCheck() ) {
            return;
        }

        $this->_ValidateString();
        $this->_ValidateSubtype();
    }

    /**
     * setter if set string will be checked on maximum length, if no set - will not be checked
     * @param $maxLen
     */
    public function SetMaxLen( $maxLen ) {
        $this->maxLen = $maxLen;
    }

    /**
     * setter if set string will be checked on minimum length, if no set - will not be checked
     * @param $minLen
     */
    public function SetMinLen( $minLen ) {
        $this->minLen = $minLen;
    }

    /**
     * setter if set pointing presence or absence will be checked, if not set - will not be checked
     * @param $pointingAllowedFlag
     */
    public function SetPointingAllowedFlag( $pointingAllowedFlag ) {
        $this->pointingAllowedFlag = $pointingAllowedFlag;
    }

    /**
     * setter if set spaces presence or absence will be checked, if not set - will not be checked
     * @param $spacesAllowedFlag
     */
    public function SetSpacesAllowedFlag( $spacesAllowedFlag ) {
        $this->spacesAllowedFlag = $spacesAllowedFlag;
    }

    /**
     * forms string with current validation type parameters
     * @return string
     */
    public function ToString() {
        $toString = "String, subtype = '{$this->subtype}', value = '{$this->value}'";
        $toString .= ( $this->canBeNullFlag !== null ) ? ", canBeNullFlag = {$this->canBeNullFlag}" : '';
        $toString .= ( $this->maxLen !== null ) ? ", maxLen = {$this->maxLen}" : '';
        $toString .= ( $this->minLen !== null ) ? ", minLen = {$this->minLen}" : '';
        $toString .= ( $this->pointingAllowedFlag !== null ) ? ", pointingAllowedFlag = {$this->pointingAllowedFlag}" : '';
        $toString .= ( $this->spacesAllowedFlag !== null ) ? ", spacesAllowedFlag = {$this->spacesAllowedFlag}" : '';
        return $toString;
    }

    /**
     * validates stated subtypes together with pointing and spaces properties
     * @return mixed
     * @throws Exception
     */
    private function _ValidateSubtype() {
        switch ( $this->subtype ) {
            case self::$subtypeAlphabetic:
                // any text
                if ( $this->spacesAllowedFlag && $this->pointingAllowedFlag ) {
                    if ( !preg_match( '/^[\p{L}\p{P}\p{S} ]+$/', $this->value, $matches ) ) {
                        $this->_HasError( new ValidatorError ( 'WRONG_SUBTYPE', $this->name, array( 'fieldName'       => $this->userFriendlyName,
                                                                                       'requiredSubtype' => 'alphabetic' ) ) );
                    }
                }

                // one word, any sign
                if ( !$this->spacesAllowedFlag && $this->pointingAllowedFlag ) {
                    if ( !preg_match( '/^[\p{L}\p{P}\p{S}]+$/', $this->value, $matches ) ) {
                        $this->_HasError( new ValidatorError ( 'ALPHABETIC_SPACES_NOT_ALLOWED', $this->name, array( 'fieldName' => $this->userFriendlyName ) ) );
                    }
                }
                // spaces allowed, but no pointing
                if ( $this->spacesAllowedFlag && !$this->pointingAllowedFlag ) {
                    if ( !preg_match( '/^[\p{L} ]+$/', $this->value, $matches ) ) {
                        $this->_HasError( new ValidatorError ( 'ALPHABETIC_POINTING_NOT_ALLOWED', $this->name, array( 'fieldName' => $this->userFriendlyName ) ) );
                    }
                }

                // one word, no pointing
                if ( !$this->spacesAllowedFlag && !$this->pointingAllowedFlag ) {
                    if ( !preg_match( '/^[\p{L}]+$/', $this->value, $matches ) ) {
                        $this->_HasError( new ValidatorError ( 'ALPHABETIC_SPACES_POINTING_NOT_ALLOWED', $this->name, array( 'fieldName' => $this->userFriendlyName ) ) );
                    }
                }

                break;
            case self::$subtypeAlphanumeric:
                // any text
                if ( $this->spacesAllowedFlag && $this->pointingAllowedFlag ) {
                    return;
                }
                // one word, any sign
                if ( !$this->spacesAllowedFlag && $this->pointingAllowedFlag ) {
                    if ( strpos( $this->value, ' ' ) !== false ) {
                        $this->_HasError( new ValidatorError ( 'SPACES_NOT_ALLOWED', $this->name, array( 'fieldName' => $this->userFriendlyName ) ) );
                    }
                }

                // spaces allowed, but no pointing
                if ( $this->spacesAllowedFlag && !$this->pointingAllowedFlag ) {
                    if ( !preg_match( '/^[\w\d\s]+$/', $this->value, $matches ) ) {
                        $this->_HasError( new ValidatorError ( 'POINTING_NOT_ALLOWED', $this->name, array( 'fieldName' => $this->userFriendlyName ) ) );
                    }
                }

                // one word, no pointing
                if ( !$this->spacesAllowedFlag && !$this->pointingAllowedFlag ) {
                    if ( !preg_match( '/^[\w\d]+$/', $this->value, $matches ) ) {
                        $this->_HasError( new ValidatorError ( 'SPACES_POINTING_NOT_ALLOWED', $this->name, array( 'fieldName' => $this->userFriendlyName ) ) );
                    }
                }
                break;
            default:
                throw new Exception( "Unknown subtype for string {$this->userFriendlyName}" );
        }
    }

    /**
     * validates on length etc
     * @return mixed
     */
    private function _ValidateString() {
        // is string?
        if ( !is_string( $this->value ) ) {
            $this->_HasError( new ValidatorError ( 'WRONG_TYPE', $this->name, array( 'fieldName'    => $this->userFriendlyName,
                                                                        'requiredType' => 'string' ) ) );
            return;
        }
        //minlen
        if ( $this->minLen !== null && strlen( $this->value ) < $this->minLen ) {
            $this->_HasError( new ValidatorError ( 'LENGTH_LESS_THEN_MIN', $this->name, array( 'fieldName' => $this->userFriendlyName,
                                                                                  'minLength' => $this->minLen ) ) );
        }

        //maxlen
        if ( $this->maxLen !== null && strlen( $this->value ) > $this->maxLen ) {
            $this->_HasError( new ValidatorError ( 'LENGTH_MORE_THEN_MAX', $this->name, array( 'fieldName' => $this->userFriendlyName,
                                                                                  'maxLength' => $this->maxLen ) ) );
        }
    }
}

/**
 * Numbers for validation
 */
class ValidatorTypeNumeric extends ValidatorAnyType implements ValidatorType
{
    /**
     * SUBTYPES
     * @var string
     */
    public static $subtypeInt = 'int';
    public static $subtypeFloat = 'float';
    public static $subtypeNumeric = 'numeric';

    /**
     * Additional params
     * @var null
     */
    private $min = null;
    private $max = null;

    /**
     * create validation type for number
     * @param ValidatorDataContainer $dataContainer where values are kept
     * @param $name name of key in $dataContainer
     * @param $subtype to set subtype use parameters of class whose name starts with "subtype"
     * @param string $userFriendlyName friendly name will be used in error messages,  if not set $name will be used
     */
    function __construct( ValidatorDataContainer $dataContainer, $name, $subtype, $userFriendlyName = '' ) {
        if ( empty( $subtype ) ) {
            throw new Exception( "Subtype for number $name can not be empty" );
        }
        parent::__construct( $dataContainer, $name, $subtype, $userFriendlyName );
    }

    public function Validate() {
        // if field empty, no use to check any more. Can have errors thou
        if ( $this->_EmptinessCheck() ) {
            return;
        }

        // subtype
        $this->_CheckNumericSubtype( $this->userFriendlyName, $this->subtype, $this->value );

        //MIN
        if ( $this->min !== null ) {
            if ( $this->value < $this->min ) {
                $this->_HasError( new ValidatorError ( 'LESS_THEN_MIN', $this->name, array( 'fieldName' => $this->userFriendlyName,
                                                                               'min'       => $this->min ) ) );
            }
        }

        //MAX
        if ( $this->max !== null ) {
            if ( $this->value > $this->max ) {
                $this->_HasError( new ValidatorError ( 'MORE_THEN_MAX', $this->name, array( 'fieldName' => $this->userFriendlyName,
                                                                               'max'       => $this->max ) ) );
            }
        }
    }

    /**
     * check for exact type
     */
    private function _CheckNumericSubtype() {
        switch ( $this->subtype ) {
            //int
            case self::$subtypeInt:
                if ( !is_int( $this->value ) ) {
                    $this->_HasError( new ValidatorError ( 'WRONG_SUBTYPE', $this->name, array( 'fieldName'       => $this->userFriendlyName,
                                                                                   'requiredSubtype' => self::$subtypeInt ) ) );
                }
                break;
            //float
            case self::$subtypeFloat:
                if ( !is_float( $this->value ) ) {
                    $this->_HasError( new ValidatorError ( 'WRONG_SUBTYPE', $this->name, array( 'fieldName'       => $this->userFriendlyName,
                                                                                   'requiredSubtype' => self::$subtypeFloat ) ) );
                }
                break;
            //numeric
            case self::$subtypeNumeric:
                if ( !is_numeric( $this->value ) ) {
                    $this->_HasError( new ValidatorError ( 'WRONG_SUBTYPE', $this->name, array( 'fieldName'       => $this->userFriendlyName,
                                                                                   'requiredSubtype' => self::$subtypeNumeric ) ) );
                }
                break;
            //unknown
            default:
                $this->_HasError( 'UNKNOWN_SUBTYPE', array( 'fieldName'       => $this->userFriendlyName,
                                                            'requiredSubtype' => $this->subtype ) );
        }
    }

    /**
     * setter for max, if not set will not be checked
     * @param $max
     */
    public function SetMax( $max ) {
        $this->max = $max;
    }

    /**
     * setter for min if not set will not be checked
     * @param $min
     */
    public function SetMin( $min ) {
        $this->min = $min;
    }

    /**
     * returns a toString descriptions of properties for current validation type
     * @return string
     */
    public function ToString() {
        $toString = "Numeric, subtype = '{$this->subtype}', value = '{$this->value}', 'userFriendlyName' = '$this->userFriendlyName'";
        $toString .= ( $this->canBeNullFlag !== null ) ? ", canBeNullFlag = $this->canBeNullFlag" : '';
        $toString .= ( $this->max !== null ) ? ", max = $this->max" : '';
        $toString .= ( $this->min !== null ) ? ", min = $this->min" : '';
        return $toString;
    }

}

/**
 * validator for email
 */
class ValidatorTypeEmail extends ValidatorAnyType implements ValidatorType
{
    /**
     * create a validation for email
     * @param ValidatorDataContainer $dataContainer where the values are kept
     * @param $name key name for a value in $dataContainer
     * @param string $userFriendlyName used in error messages, if not set $name will be used
     */
    public function __construct( ValidatorDataContainer $dataContainer, $name, $userFriendlyName = '' ) {
        parent::__construct( $dataContainer, $name, '', $userFriendlyName );
    }

    /**
     * starts current type validation
     */
    public function Validate() {
        // if field empty, no use to check any more. Can have errors thou
        if ( $this->_EmptinessCheck() ) {
            return;
        }

        // if string
        if ( !is_string( $this->value ) ) {
            $this->_HasError( new ValidatorError ( 'WRONG_SUBTYPE', $this->name, array( 'fieldName'       => $this->value,
                                                                           'requiredSubtype' => 'string' ) ) );
        }
        // if correct email
        if ( !preg_match( '/^[A-Za-z0-9](([a-zA-Z0-9_\.\-]+)*)@[a-z0-9.]+[a-z]{2,6}$/', $this->value, $matches ) ) {
            $this->_HasError( new ValidatorError ( 'INVALID_EMAIL', $this->name, array( 'fieldName' => $this->userFriendlyName ) ) );
        }
    }

    /**
     * returns a toString descriptions of properties for current validation type
     * @return string
     */
    public function ToString() {
        return "Email value = '$this->value', userFriendlyName = '$this->userFriendlyName'";
    }
}

/**
 * validator for URL
 */
class ValidatorTypeUrl extends ValidatorAnyType implements ValidatorType
{
    /**
     * @param ValidatorDataContainer $dataContainer where all the values are kept
     * @param $name name of key in $dataContainer
     * @param string $userFriendlyName used in error messages, if not set $name will be used
     */
    public function __construct( ValidatorDataContainer $dataContainer, $name, $userFriendlyName = '' ) {
        parent::__construct( $dataContainer, $name, '', $userFriendlyName );
    }

    /**
     * starts current type validation
     */
    public function Validate() {
        // if field empty, no use to check any more. Can have errors thou
        if ( $this->_EmptinessCheck() ) {
            return;
        }

        if ( !is_string( $this->value ) ) {
            $this->_HasError( new ValidatorError( 'WRONG_SUBTYPE',  $this->name, array( 'fieldName'       => $this->userFriendlyName,
                                                                          'requiredSubtype' => 'string' ) ) );
        }

        if ( !preg_match( '/((http|https|ftp):\/\/|www)[a-z0-9\-\._]+\/?[a-z0-9_\.\-\?\+\/~=&#;,]*[a-z0-9\/]{1}/si', $this->value, $matches ) ) {
            $this->_HasError( new ValidatorError ( 'INVALID_URL', $this->name ) );
        }
    }

    /**
     * returns description
     * @return string
     */
    public function ToString() {
        return "URL value = '{$this->value}', 'userFriendlyName' = '{$this->userFriendlyName}'";
    }
}

/**
 * user friendly errors while validation
 */
class ValidatorError
{
    /**
     * which error
     * @var
     */
    private $errType;
    /**
     * current error data
     * @var array
     */
    private $data;

    /**
     * name of the field error belongs to
     * @var
     */
    private $fieldName;

    /**
     * error types
     * @var array
     */
    static $errorTypeArray = array(
        'DEFAULT'                                => 'Error',
        'NO_FIELD'                               => 'No such field: %fieldName%',
        'EMPTY_FIELD'                            => 'Empty required field: "%fieldName%"',
        'WRONG_SUBTYPE'                          => 'Wrong subtype of field: "%fieldName%". Required: %requiredSubtype%',
        'WRONG_TYPE'                             => 'Wrong type of field: "%fieldName%". Required: %requiredType%',
        'UNKNOWN_SUBTYPE'                        => 'Field "%fieldName%" is checked for an unknown subtype: %requiredSubtype%',
        'LESS_THEN_MIN'                          => 'Value of field "%fieldName%" can not be less then %min%',
        'MORE_THEN_MAX'                          => 'Value of field "%fieldName%" can not be more then %max%',
        'LENGTH_LESS_THEN_MIN'                   => 'Length of field "%fieldName%" should be more then %minLength%',
        'LENGTH_MORE_THEN_MAX'                   => 'Length of field "%fieldName%" should be less then %maxLength%',
        'SPACES_NOT_ALLOWED'                     => 'Field "%fieldName%" should contain only one world',
        'POINTING_NOT_ALLOWED'                   => 'Field "%fieldName%" contains wrong symbols: only letters, digits and spaces are allowed',
        'SPACES_POINTING_NOT_ALLOWED'            => 'Field "%fieldName%" contains wrong symbols: only letters and  digits are allowed',
        'ALPHABETIC_SPACES_NOT_ALLOWED'          => 'Field "%fieldName%" should contain only one world',
        'ALPHABETIC_POINTING_NOT_ALLOWED'        => 'Field "%fieldName%" contains wrong symbols: only letters and spaces are allowed',
        'ALPHABETIC_SPACES_POINTING_NOT_ALLOWED' => 'Field "%fieldName%" contains wrong symbols: only letters are allowed',
        'INVALID_EMAIL'                          => 'Email is not valid',
        'INVALID_URL'                            => 'URL is not valid',
        'CAN_NOT_BE_NULL'                        => 'Field "%fieldName%" can not be null',

    );

    public function __construct( $errType, $fieldName, array $additionalData = array() ) {
        $this->errType = $errType;
        $this->data = $additionalData;
        $this->fieldName = $fieldName;
    }

    /**
     * Form standard errors in a user friendly view
     * @param $errType standard error type
     * @param $userFriendlyName
     * @param null $requiredValue in case it is important
     * @return void
     */
    public function ToString() { //$userFriendlyName, $requiredValue = null){
        if ( isset( self::$errorTypeArray[ $this->errType ] ) ) {
            $error = self::$errorTypeArray[ $this->errType ];
            foreach ( $this->data as $infoName => $infoValue ) {
                $error = str_replace( "%$infoName%", $infoValue, $error );
            }
            return $error;
        }
        if ( isset( self::$errorTypeArray[ 'DEFAULT' ] ) ) {
            return self::$errorTypeArray[ 'DEFAULT' ];
        }
        return 'Unknown error';
    }

    /**
     * getter for error type
     * @return mixed
     */
    public function GetErrType() {
        return $this->errType;
    }

    /**
     * getter for current error data
     * @return array
     */
    public function GetErrData() {
        return $this->data;
    }

    /**
     * getter for name of field current error belongs to
     */
    public function GetFieldName(){
        return $this->fieldName;
    }
}

?>