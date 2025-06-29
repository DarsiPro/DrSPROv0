<?php

class Viewer_Token
{
    protected $value;
    protected $type;
    protected $lineno;

    const EOF_TYPE                  = -1;
    const TEXT_TYPE                 = 0;
    const BLOCK_START_TYPE          = 1;
    const VAR_START_TYPE            = 2;
    const BLOCK_END_TYPE            = 3;
    const VAR_END_TYPE              = 4;
    const NAME_TYPE                 = 5;
    const NUMBER_TYPE               = 6;
    const STRING_TYPE               = 7;
    const OPERATOR_TYPE             = 8;
    const PUNCTUATION_TYPE          = 9;
    const INTERPOLATION_START_TYPE  = 10;
    const INTERPOLATION_END_TYPE    = 11;
    const URL_START_TYPE              = 12;
    const URL_END_TYPE                = 13;
    const COMMENT_START_TYPE          = 14;
    const COMMENT_END_TYPE            = 15;

    /**
     * Constructor.
     *
     * @param integer $type   The type of the token
     * @param string  $value  The token value
     * @param integer $lineno The line position in the source
     */
    public function __construct($type, $value, $lineno)
    {
        $this->type   = $type;
        $this->value  = $value;
        $this->lineno = $lineno;
    }

    /**
     * Returns a string representation of the token.
     *
     * @return string A string representation of the token
     */
    public function __toString()
    {
        return sprintf('%s(%s)', self::typeToString($this->type, true, $this->lineno), $this->value);
    }

    /**
     * Tests the current token for a type and/or a value.
     *
     * Parameters may be:
     * * just type
     * * type and value (or array of possible values)
     * * just value (or array of possible values) (NAME_TYPE is used as type)
     *
     * @param array|integer     $type   The type to test
     * @param array|string|null $values The token value
     *
     * @return Boolean
     */
    public function test($type, $values = null)
    {
        if (null === $values && !is_int($type)) {
            $values = $type;
            $type = self::NAME_TYPE;
        }

        return ($this->type === $type) && (
            null === $values ||
            (is_array($values) && in_array($this->value, $values)) ||
            $this->value == $values
        );
    }

    /**
     * Gets the line.
     *
     * @return integer The source line
     */
    public function getLine()
    {
        return $this->lineno;
    }

    /**
     * Gets the token type.
     *
     * @return integer The token type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets the token value.
     *
     * @return string The token value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns the constant representation (internal) of a given type.
     *
     * @param integer $type  The type as an integer
     * @param Boolean $short Whether to return a short representation or not
     * @param integer $line  The code line
     *
     * @return string The string representation
     */
    static public function typeToString($type, $short = false, $line = -1)
    {
        switch ($type) {
            case self::EOF_TYPE:
                $name = 'EOF_TYPE';
                break;
            case self::TEXT_TYPE:
                $name = 'TEXT_TYPE';
                break;
            case self::BLOCK_START_TYPE:
                $name = 'BLOCK_START_TYPE';
                break;
            case self::VAR_START_TYPE:
                $name = 'VAR_START_TYPE';
                break;
            case self::BLOCK_END_TYPE:
                $name = 'BLOCK_END_TYPE';
                break;
            case self::VAR_END_TYPE:
                $name = 'VAR_END_TYPE';
                break;
            case self::NAME_TYPE:
                $name = 'NAME_TYPE';
                break;
            case self::NUMBER_TYPE:
                $name = 'NUMBER_TYPE';
                break;
            case self::STRING_TYPE:
                $name = 'STRING_TYPE';
                break;
            case self::OPERATOR_TYPE:
                $name = 'OPERATOR_TYPE';
                break;
            case self::PUNCTUATION_TYPE:
                $name = 'PUNCTUATION_TYPE';
                break;
            case self::INTERPOLATION_START_TYPE:
                $name = 'INTERPOLATION_START_TYPE';
                break;
            case self::INTERPOLATION_END_TYPE:
                $name = 'INTERPOLATION_END_TYPE';
                break;
            case self::URL_START_TYPE:
                $name = 'URL_START_TYPE';
                break;
            case self::URL_END_TYPE:
                $name = 'URL_END_TYPE';
                break;
            default:
                throw new Exception(sprintf('Token of type "%s" does not exist.', $type), $line);
        }

        return $short ? $name : 'Viewer_Token::'.$name;
    }
}