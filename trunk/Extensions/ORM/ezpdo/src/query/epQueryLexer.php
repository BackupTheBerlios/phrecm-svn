<?php

/**
 * $Id: epQueryLexer.php,v 1.7 2005/12/10 22:45:41 nauhygon Exp $
 * 
 * Copyright(c) 2005 by Oak Nauhygon. All rights reserved.
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$
 * @package ezpdo
 * @subpackage ezpdo.query
 */

/**#@+
 * need epBase and epUtil
 */
include_once(EP_SRC_BASE.'/epBase.php');
include_once(EP_SRC_BASE.'/epUtils.php');
/**#@-*/

/**#@+
 * EZOQL tokens
 */
epDefine('EPQ_T_AND');
epDefine('EPQ_T_AS');
epDefine('EPQ_T_ASC');
epDefine('EPQ_T_AVG');
epDefine('EPQ_T_BETWEEN');
epDefine('EPQ_T_BY');
epDefine('EPQ_T_CONTAINS');
epDefine('EPQ_T_COUNT');
epDefine('EPQ_T_DESC');
epDefine('EPQ_T_EQUAL');
epDefine('EPQ_T_FLOAT');
epDefine('EPQ_T_FROM');
epDefine('EPQ_T_IDENTIFIER');
epDefine('EPQ_T_INTEGER');
epDefine('EPQ_T_IS');
epDefine('EPQ_T_GE');
epDefine('EPQ_T_LE');
epDefine('EPQ_T_LIKE');
epDefine('EPQ_T_LIMIT');
epDefine('EPQ_T_MAX');
epDefine('EPQ_T_MIN');
epDefine('EPQ_T_NEQUAL');
epDefine('EPQ_T_NEWLINE');
epDefine('EPQ_T_NOT');
epDefine('EPQ_T_NULL');
epDefine('EPQ_T_OR');
epDefine('EPQ_T_ORDER');
epDefine('EPQ_T_SELECT');
epDefine('EPQ_T_STRING');
epDefine('EPQ_T_SUM');
epDefine('EPQ_T_WHERE');
epDefine('EPQ_T_UNKNOWN');
/**#@-*/

/**
 * The class of EZOQL token
 * 
 * A token contains the following fields: 
 * + type, the token type, either primitive string or EPQ_T_xxx constants 
 * + value, the corresponding string value of the token, for example 
 * + line, the number of the line where this token is found
 * + char, the position of the starting char from which this token is found
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$
 * @package ezpdo
 * @subpackage ezpdo.query
 */
class epQueryToken extends epBase {

    /**
     * Token type (default to unknown)
     * @var integer 
     */
    public $type = EPQ_T_UNKNOWN;

    /**
     * Token value
     * @var string
     */
    public $value = '';

    /**
     * Line number where token is located
     * @var integer
     */
    public $line = -1;

    /**
     * Char number where token is located on the line
     * @var integer
     */
    public $char = 0;

    /**
     * Constructor 
     * @param integer $type (token type)
     * @param string $value (token value)
     * @param integer $line (line number)
     * @param integer $char (starting char in line)
     */
    public function __construct($type, $value, $line = -1, $char = -1) {
        $this->type = $type;
        $this->value = $value;
        $this->line = $line;
        $this->char = $char;
    }

    /**
     * Magic function __toString() (mostly for debugging)
     */
    public function __toString() {
        return $this->type . ': ' . $this->value . ' (' . $this->line . ', ' . $this->char . ')';
    }
}

/**
 * The error class for the EZOQL lexer and parser. It contains: 
 * + msg, the error message
 * + value, the corresponding string value of the current token being processed
 * + line, the number of the starting line in source code from which error occurs
 * + char, the position of the starting char in the starting line from which this error occurs
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$
 * @package ezpdo
 * @subpackage ezpdo.query
 */
class epQueryError extends epBase {
    
    /**
     * The error message
     * @var integer 
     */
    protected $msg = '';

    /**
     * Token value
     * @var string
     */
    protected $value = '';

    /**
     * Line number where the error occurs
     * @var integer
     */
    protected $line = -1;

    /**
     * Char number where the error occurs 
     * @var integer
     */
    protected $char = 0;

    /**
     * Constructor 
     * @param integer $msg (the error message)
     * @param string $value (token value)
     * @param integer $line (line number)
     * @param integer $char (starting char in line)
     */
    public function __construct($msg, $value, $line = -1, $char = -1) {
        $this->msg = $msg;
        $this->value = $value;
        $this->line = $line;
        $this->char = $char;
    }

    /**
     * Magic function __toString() 
     */
    public function __toString() {
        return $this->msg . ' @ line ' . $this->line .  ' col ' . $this->char . ' [ ... ' . $this->value . ' ... ]';
    }
}

/**
 * A stream class for the EZOQL lexer
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$
 * @package ezpdo
 * @subpackage ezpdo.query
 */
class epQueryStream extends epBase {
    
    /**
     * The string 
     * @var string 
     */
    protected $s = false;

    /**
     * The current position 
     * @var integer
     */
    protected $pos = 0;
    
    /**
     * The current line number (starting from 1 instead of 0)
     * @var integer
     */
    protected $line = 1;
    
    /**
     * The char position in the current line (starting from 1 instead of 0)
     * @var integer
     */
    protected $char = 1;
    
    /**
     * Constructor 
     * @param string $s
     */
    function __construct($s) {
        // need to remove cariage return
        $this->s = str_replace("\r", '', $s);
        $this->pos = 0;
        $this->line = 1;
        $this->char = 1;
    }

    /**
     * Returns the string associated to the stream
     * @return string
     */
    public function getInput() {
        return $this->s;
    }

    /**
     * Get one byte and move cursor forward
     * @return false|string (false if end has reached or the current byte)
     */
    public function getc() {
        if ($this->pos < strlen($this->s)) {
            $c = $this->s[$this->pos];
            $this->pos ++;
            if ($c == "\n") {
                $this->line ++;
                $this->char = 1;
            } else {
                $this->char ++;
            }
            return $c;
        }
        return false; // end has reached
    }
    
    /**
     * Unget one byte
     * @return false|string (false if the beginning has reached or the "ungotten" byte)
     */
    public function ungetc() {
        $this->pos --;
        if ($this->pos < 0) {
            $this->pos = 0;
            return false;
        }
        
        $c = $this->s[$this->pos];
        if ($c == "\n") {
            $this->line --;
            $this->char = $this->_line_length();
        } else {
            $this->char --;
        }
        
        return $c;
    }

    /**
     * Peek the next byte
     * @return false|string (same as get())
     */
    public function peek() {
        $c = $this->getc();
        $this->ungetc();
        return $c;
    }
    
    /**
     * Returns the current line number
     * @return integer
     */
    public function line() {
        return $this->line;
    }
    
    /**
     * Returns the char position in current line 
     * @return integer
     */
    public function char() {
        return $this->char;
    }

    /**
     * Compute the length of the current line
     * @return integer
     * @access private
     */
    protected function _line_length() {
        
        // line length
        $len = 0;
        
        // move backward to find the beginning
        $pos = $this->pos - 1;
        while ($pos >= 0 && $this->s[$pos] != "\n") {
            $pos --;
            $len ++;
        }
        
        // move forward to find the end
        $pos = $this->pos;
        while ($this->s[$pos] != "\n" && $pos < strlen($this->s)) {
            $pos ++;
            $len ++;
        }
        
        return $len;
    }
}

/**
 * Exception class for {@link epQueryLexer}
 * 
 * @author Oak Nauhygon <ezpdo4php@gmail.com>
 * @version $Revision$ $Date: 2005/12/10 22:45:41 $
 * @package ezpdo
 * @subpackage ezpdo.runtime
 */
class epExceptionQueryLexer extends epException {
}

/**
 * EZOQL Lexer that breaks an EZOQL query string into tokens. 
 * 
 * Usage: 
 * <code>
 * // $s contains the EZOQL source
 * $l = new epQueryLexer($s);
 * 
 * // now go through tokens one by one
 * while ($t = $l.next()) {
 *     // process the token
 *     // ......
 * }
 * </code>
 * 
 * You can also go back a token like this, 
 * <code>
 * $t = $l->back();
 * </code>
 * 
 * @package ezpdo
 * @subpackage ezpdo.runtime
 * @version $id$
 * @author Oak Nauhygon <ezpdo4php@ezpdo.net> 
 */
class epQueryLexer extends epBase {

    /**
     * Associative array holds all EZOQL keywords
     * @var array
     */
    static public $keywords = array(
        'and'        => EPQ_T_AND,
        'as'         => EPQ_T_AS,
        'asc'        => EPQ_T_ASC,
        'ascending'  => EPQ_T_ASC,
        'avg'        => EPQ_T_AVG,
        'between'    => EPQ_T_BETWEEN,
        'by'         => EPQ_T_BY,
        'contains'   => EPQ_T_CONTAINS,
        'count'      => EPQ_T_COUNT,
        'desc'       => EPQ_T_DESC,
        'descending' => EPQ_T_DESC,
        'from'       => EPQ_T_FROM,
        'is'         => EPQ_T_IS,
        'like'       => EPQ_T_LIKE,
        'limit'      => EPQ_T_LIMIT,
        'max'        => EPQ_T_MAX,
        'min'        => EPQ_T_MIN,
        'not'        => EPQ_T_NOT,
        'null'       => EPQ_T_NULL,
        'or'         => EPQ_T_OR,
        'order'      => EPQ_T_ORDER,
        'select'     => EPQ_T_SELECT,
        'sum'        => EPQ_T_SUM,
        'where'      => EPQ_T_WHERE,
        ); 

    /**
     * Array to keep all tokens
     * @var array 
     */
    protected $tokens = array();

    /**
     * Cursor of the tokens (to facilitate back and forth)
     * @var integer
     */
    protected $cursor = false; 

    /**
     * Array to keep all errors
     * @var array 
     */
    protected $errors = array();

    /**
     * The current error
     * @var epQueryError
     */
    protected $error = null;

    /**
     * The current token value
     * @var integer
     */
    protected $value = '';
    
    /**
     * The starting line number for the current value
     * @var integer
     */
    protected $line_start = 1;
    
    /**
     * The char start position for the current value
     * @var integer
     */
    protected $char_start = 1;
    
    /**
     * Are we looking an operand instead of operator?
     * This is feedback from the parser. 
     * @var boolean
     */
    protected $operand_now = false;

    /**
     * Constructor
     * @param string $s 
     */
    public function epQueryLexer($s = '') {
        $this->initialize($s);
    }

    /**
     * Initialize the lexer
     * @param string $s the input string
     * @return void
     */
    public function initialize($s = '') {
        $this->tokens = array();
        $this->errors = array();
        $this->cursor = false;
        $this->value = '';
        $this->line_start = 1;
        $this->char_start = 1;
        $this->stream = new epQueryStream($s);
    }

    /**
     * Returns the string being parsed
     * @return false|string
     */
    public function getInput() {
        if (!$this->stream) {
            return false;
        }
        return $this->stream->getInput();
    }

    /**
     * Return the previous cursor
     * @return false|epQueryToken
     */
    public function back() {

        if ($this->cursor == 0) {
            $this->cursor = false;
        }
        
        // no token parsed yet?
        if ($this->cursor === false) {
            return false;
        }

        // move back one token
        $this->cursor --;
        
        return $this->tokens[$this->cursor];
    }
    
    /**
     * Returns the next token
     * @param string $str
     * @return false|eqpToken
     */
    public function next() {

        // check if the next token has been parsed
        if (count($this->tokens) > 0) {
            if ($this->cursor === false) {
                $this->cursor = 0;
                return $this->tokens[$this->cursor];
            } 
            else if ($this->cursor + 1 < count($this->tokens)) {
                $this->cursor ++;
                return $this->tokens[$this->cursor];
            }
        }
        
        // get the next token. have we reached the end yet?
        if (($type = $this->_next()) === false) {
            return false;
        }
        
        // create a new token 
        $t = new epQueryToken($type, $this->value, $this->line_start, $this->char_start);
        
        // collect the token
        $this->tokens[] = $t;

        // point the cursor to the last token
        $this->cursor = count($this->tokens) - 1;

        // return the token
        return $t;
    }
    
    /**
     * Peek the next token
     * @return eqpToken|false
     */
    public function peek() {
        if (($t = $this->next()) !== false) {
            $this->back();
        }
        return $t;
    }

    /**
     * Returns the errors raised
     * @return array
     */
    public function errors() {
        return $this->errors;
    }
    
    /**
     * Raise an error message.
     * 
     * The error message is stored in an array that can be 
     * retrieved later.
     * 
     * @param string $msg
     * @return epQueryError
     */
    protected function error($msg = '') {
        
        // if empty message, return the current error 
        if (!$msg) {
            return $this->error;
        }

        // create a new error object
        $this->error = new epQueryError($msg, $this->value, $this->stream->line(), $this->stream->char());

        // keep all errors
        $this->errors[] = $this->error;

        // return this error
        return $this->error;
    }

    /**
     * Returns the next token
     * @return string 
     * @access private
     */
    protected function _next() {
        
        // reset the current error to null
        $this->error = null;
        
        // reset the token value
        $this->value = '';

        // keep track of starting line and char position of the current token 
        $this->line_start = $this->stream->line();
        $this->char_start = $this->stream->char();
        
        // ignore white space
        while (($ch = $this->getc()) !== false && $this->isWhiteSpace($ch)) {
            $this->value = '';
        }

        // have we reached the end of the stream?
        if ($ch === false) {
            return false;
        }
        
        // new line
        else if ($ch == "\n") {
            return EPQ_T_NEWLINE;
        }

        // String constant
        else if ($ch == '"' || $ch == "'") {
            $this->readString($ch);
            return EPQ_T_STRING;
        }
        
        // 
        // literals: ==, !=, <>, <=, >=, &&, ||
        // 
        
        // == 
        else if ($ch == '=' && $this->peekc() == '=') {
            $this->getc();
            return EPQ_T_EQUAL;
        } 
        
        // != 
        else if ($ch == '!' && $this->peekc() == '=') {
            $this->getc();
            return EPQ_T_NEQUAL;
        } 
        
        // <>
        else if ($ch == '<' && $this->peekc() == '>') {
            $this->getc();
            return EPQ_T_NEQUAL;
        }
        
        // <= 
        else if ($ch == '<' && $this->peekc() == '=') {
            $this->getc();
            return EPQ_T_LE;
        }
        
        // >=
        else if ($ch == '>' && $this->peekc() == '=') {
            $this->getc();
            return EPQ_T_GE;
        } 
        
        // &&
        else if ($ch == '&' && $this->peekc() == '&') {
            $this->getc();
            return EPQ_T_AND;
        } 
        
        // ||
        else if ($ch == '|' && $this->peekc() == '|') {
            $this->getc();
            return EPQ_T_OR;
        } 
        
        // number
        else if ($this->isDecimalDigit($ch) 
                 || ($ch == '.' && $this->isDecimalDigit($this->peekc()))) {
            return $this->readNumber($ch);
        }
        
        // identifier/keyword
        else if ($this->isIdentifierChar($ch) || $ch == '`') {
            
            // read identifier
            $id = $this->readIdentifier($ch);
            
            // check if its a keyword
            if (isset(self::$keywords[$id])) {
                return self::$keywords[$id];
            } else {
                return EPQ_T_IDENTIFIER;
            }
        }
        
        // just return this char
        return $ch;
    }

    /**
     * Get one char from the stream and append it to (token) value
     * @return char 
     */
    protected function getc() {
        $c = $this->stream->getc();
        if ($c !== false) {
            $this->value .= $c;
        }
        return $c;
    }

    /**
     * Put back the last char into stream and remove it from (token) value
     * @return char (the previous char)
     */
    protected function ungetc() {
        $c = $this->stream->ungetc();
        if ($c !== false && $this->value) {
            $this->value = substr($this->value, 0, strlen($this->value) - 1);
        }
        return $c;
    }

    /**
     * Take a peek at the next char (no cursor moving) 
     * @return char 
     */
    protected function peekc() {
        return $this->stream->peek();
    }

    /**
     * Returns an lower-cased identifier 
     * @param string $ch the starting char
     * @return string
     */
    protected function readIdentifier($ch) {
        
        // '`' allows keyword to be treated as identifier
        $q96 = $ch == '`';
        
        $id = $ch;
        while (($ch = $this->getc()) !== false && 
               ($this->isIdentifierChar($ch) 
                || $this->isDecimalDigit($ch)
                || $q96 && $ch != '`'
                )) {
            $id .= $ch;
        }
        
        if ($q96 && $ch == '`') {
            $id .= $ch;
        } else {
            if ($ch) {
                $this->ungetc();
            }
        }

        return strtolower($id);
    }

    /**
     * Read a string constant 
     * @param string $ender (the ending char, ' or ") 
     * @return char (the last char read)
     */
    protected function readString($ender) {
        $ch = '';
        $done = false; 
        while (!$done) {
            
            $ch = $this->getc();
            
            if ($ch == "\n" || $ch === false) {
                $this->error("String constant terminated unexpectedly");
                return $ch;
            }
            
            if ($ch == $ender) {
                $done = true;
                break;
            }

            if ($ch == "\\") {

                if ($this->peekc() == "\n") {
                    // ignore if backslash is followed by a newline 
                    $this->getc();
                    continue;
                }

                if ($this->readEscape() === false) {
                    $done = true;
                    break;
                }
            }
        }
        return $ch;
    }

    /**
     * Read escape char
     * @return false|string
     */
    protected function readEscape() {
        
        $ch = $this->getc();
        if ($ch == 'n' 
            || $ch == 't' 
            || $ch == 'v' 
            || $ch == 'b' 
            || $ch == 'r' 
            || $ch == 'f' 
            || $ch == 'a' 
            || $ch == "\\" 
            || $ch == '?' 
            || $ch == "\'" 
            || $ch == '"') {
            return $ch;
        }

        return false;
    }

    /**
     * Read a decimal number
     * @param string $ch (the starting char: either a digital or . followed with a digital)
     */
    protected function readNumber($ch) {
        
        $is_float = false;
        $seen_dot = false;

        // is it a float (ie starting with '.')?
        if ($ch == '.') {
            $is_float = true;
            $seen_dot = true;
            do {} while ( $this->isDecimalDigit( $ch = $this->getc() ) );
        } 
        // it starts with a decimal digit
        else {
            do {} while ( $this->isDecimalDigit( $ch = $this->getc() ) );
        }

        // not the end of the stream yet?
        if ($ch !== false) {
        
            // a float (we have seen the integer part before '.', 'e', or 'E')
            if ((!$seen_dot && $ch == '.') || $ch == 'e' || $ch == 'E') {
            
                $is_float = true;

                if ($ch == '.') {
                    do {} while ( $this->isDecimalDigit( $ch = $this->getc() ) );
                } 
            
                // scientific number?
                if ($ch == 'e' || $ch == 'E') {
                    
                    $ch = $this->getc();
                    if ($ch == '+' || $ch == '-') {
                        $ch = $this->getc();
                    }
                    
                    if (!$this->isDecimalDigit($ch)) {
                        $this->error('malformed exponent part in a decimal number');
                    }
                    
                    do {} while ( $this->isDecimalDigit( $ch = $this->getc() ) );
                }
            }
            
            // put the last character back to the stream (if we haven't reached the end yet)
            if ($ch !== false) {
                $this->ungetc();
            }
        }
        
        return $is_float ? EPQ_T_FLOAT : EPQ_T_INTEGER;
    }

    /**
     * Is it an identifier letter?
     * @param char $c
     * @return boolean
     */
    protected function isIdentifierChar($c) {
        return is_string($c) && ('a' <= $c && $c <= 'z') || ('A' <= $c && $c <= 'Z') || $c == '_';
    }

    /**
     * Is it a decimal digit?
     * @param char $c
     * @return boolean
     */
    protected function isDecimalDigit($c) {
        return is_string($c) && '0' <= $c && $c <= '9';
    }

    /**
     * Is it a whitespace? (newline excluded as it may become significant in EZOQL)
     * @param char $c
     * @return boolean
     */
    protected function isWhiteSpace($c) {
        return is_string($c) && $c == ' ' || $c == "\t" || $c == "\v" || $c == "\r" || $c == "\f";
    }
    
}

?>
