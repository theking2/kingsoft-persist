<?php declare(strict_types=1);

namespace Kingsoft\Persist;

abstract class Base implements IPersist
{
	/** @var bool $_valid true if a valid object contains valid record data */
	protected bool $_valid;
	public function isValid(): bool
	{
		return $this->_valid;
	}

	/** string[] $_dirty contains all modified elements */
	protected array $_dirty = [];

	/** _isField test if the fieldname is valid 
	 */
	protected function _isField( string $fieldname ): bool
	{
		return array_key_exists( $fieldname, $this->getFields() );
	}

	/* #region getters/setters */

	/**
	 * __get, get the value of a field
	 */
	public function __get( string $field ): mixed
	{
		return $this->{$field};
	}

	/**
	 * __set, set the value of a field
	 */
	public function __set( string $field, mixed $value ): void
	{
		if( $this->_isField( $field ) ) {
			$this->{$field} = $value;
			$this->_dirty[] = $field;
		} else {
			throw new \Exception( "Field $field does not exist" );
		}
	}

	/**
	 * createFromArray - Create or set from array
	 */
	static function createFromArray( array $array ): Base
	{
		$obj = new static();
		$obj->setFromArray( $array );
		return $obj;
	}
	/**
	 * setFromArray - Set object from array
	 */
	public function setFromArray( array $array ): Base
	{
		foreach( $array as $field => $value ) {
			$this->__set( $field, $value );
		}
		return $this;
	}
	/**
	 * Returns the json representation of the object
	 */
	public function getJson(): string
	{
		return json_encode( $this->getArrayCopy() );
	}
	/**
	 * Create a new object from json
	 */
	public static function createFromJson( string $json ): Base
	{
		return static::createFromArray( json_decode( $json, true ) );
	}
	/* #endregion */

	/* #region generic */
	/** 
	 * isRecord - true if record exists in database
	 */
	public function isRecord(): bool
	{
		return isset( $this->{static::getPrimaryKey()} ) and !is_null( $this->{static::getPrimaryKey()} );
	}

	/** getKeyValue - retrieve current key */
	public function getKeyValue(): mixed
	{
		if( !$this->isRecord() ) {
			return null;
		}
		return $this->{$this->getPrimaryKey()};
	}

	/**
	 * Convert those fields that are not strings to the correct type
	 *
	 * @param  string $fieldName
	 * @return string|int|float|bool|null
	 */
	protected function getFieldString( string $fieldName ): string|int|float|bool|null
	{
		// convert to string based on type of field
		switch($this->getFields()[ $fieldName ][0]) {
			default:
				return $this->$fieldName;
			case '\DateTime':
				return isset( $this->$fieldName ) ? ( $this->$fieldName )->format( 'Y-m-d H:i:s' ) : '0000-00-00 00:00:00';
			case 'Date':
				return isset( $this->$fieldName ) ? ( $this->$fieldName )->format( 'Y-m-d' ) : '0000-00-00';
		}
	}
	/**
	 * getArrayCopy - Returns an array copy of the object
	 *
	 * @return array
	 */
	public function getArrayCopy(): array
	{
		$array = [];
		foreach( array_keys( $this->getFields() ) as $field ) {
			$array[ $field ] = $this->getFieldString( $field );
		}
		return $array;
	}

	/**
	 * getStateHash - Returns a hash of the current state of the object
	 *
	 * @return string
	 */
	public function getStateHash(): string
	{
		return $this->getKeyValue() . '-' . hash( 'md5', json_encode( $this->getArrayCopy() ) );
	}
	/* #endregion */

	/* #region construction */
	public function __construct( mixed $param = null )
	{
		if( !is_null( $param ) ) {
			if( !\is_array( $param ) ) {
				$this->_valid = !is_null( $this->thaw( $param ) );
			} else {
				$this->setFromArray( $param );
			}
		} else {
			$this->_dirty                   = [];
			$this->{$this->getPrimaryKey()} = null;
		}
	}

	/* #endregion construction */
}