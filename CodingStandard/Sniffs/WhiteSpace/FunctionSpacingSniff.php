<?php

class CodingStandard_Sniffs_WhiteSpace_FunctionSpacingSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = [ 'PHP' ];

    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * An example return value for a sniff that wants to listen for whitespace
     * and any comments would be:
     *
     * <code>
     *    return array(
     *            T_WHITESPACE,
     *            T_DOC_COMMENT,
     *            T_COMMENT,
     *           );
     * </code>
     *
     * @return int[]
     * @see    Tokens.php
     */
    public function register()
    {
        return [ T_FUNCTION ];
    }

    /**
     * Called when one of the token types that this sniff is listening for
     * is found.
     *
     * The stackPtr variable indicates where in the stack the token was found.
     * A sniff can acquire information this token, along with all the other
     * tokens within the stack by first acquiring the token stack:
     *
     * <code>
     *    $tokens = $phpcsFile->getTokens();
     *    echo 'Encountered a '.$tokens[$stackPtr]['type'].' token';
     *    echo 'token information: ';
     *    print_r($tokens[$stackPtr]);
     * </code>
     *
     * If the sniff discovers an anomaly in the code, they can raise an error
     * by calling addError() on the PHP_CodeSniffer_File object, specifying an error
     * message and the position of the offending token:
     *
     * <code>
     *    $phpcsFile->addError('Encountered an error', $stackPtr);
     * </code>
     *
     * @param PHP_CodeSniffer_File $phpcsFile The PHP_CodeSniffer file where the
     *                                        token was found.
     * @param int $stackPtr The position in the PHP_CodeSniffer
     *                                        file's token stack where the token
     *                                        was found.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] != T_FUNCTION) {
            return;
        }

        // Functions in interfaces can be skipped
        if (!empty($tokens[$stackPtr]['conditions'])) {
            foreach ($tokens[$stackPtr]['conditions'] as $condition) {
                if ($condition == T_INTERFACE) {
                    return;
                }
            }
        }

        // Abstract functions don't apply to this rule
        $token = $stackPtr;
        $line = $tokens[$stackPtr]['line'];
        while ($tokens[$token]['line'] == $line) {
            if ($tokens[$token--]['code'] == T_ABSTRACT) {
                return;
            }
        }

        $token = $stackPtr;
        $line = $tokens[$token]['line'] + 1;
        $nextLine = [];

        while ($token < count($tokens)) {
            if ($tokens[$token]['line'] > $line) {
                break;
            }

            if ($tokens[$token]['code'] == T_OPEN_CURLY_BRACKET) {
                $line = $tokens[$token]['line'] + 1;
            }

            if ($tokens[$token]['code'] != T_WHITESPACE && $tokens[$token]['line'] == $line) {
                $nextLine[] = $tokens[$token];
            } elseif (!empty($tokens[$token + 1]) && $tokens[$token + 1]['code'] != T_CLOSE_CURLY_BRACKET) {
                $nextLine[] = $tokens[$token];
            }

            $token++;
        }

        if (!count($nextLine)) {
            $phpcsFile->addError('There should be no whitespace at the beginning of a function.', $stackPtr);
        }
    }
}