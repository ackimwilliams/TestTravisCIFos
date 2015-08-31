<?php

class CodingStandard_Sniffs_WhiteSpace_ControlStructureSpacingSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array(
        'PHP',
        'JS',
    );

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
            T_IF,
            T_WHILE,
            T_FOREACH,
            T_FOR,
            T_SWITCH,
            T_DO,
            T_ELSE,
            T_ELSEIF,
            T_RETURN,
        );
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // If this is a return statement...
        if($tokens[$stackPtr]['code'] == T_RETURN) {
            $line = $tokens[$stackPtr]['line']+1;
            $token = $stackPtr;

            $nextLine = [];

            // Iterate through the tokens to find out what's on the next line
            while ($token < count($tokens)) {
                if($tokens[$token]['line'] > $line) {
                    break;
                }

                if($tokens[$token]['code'] != T_WHITESPACE && $tokens[$token]['line'] == $line) {
                    $nextLine[] = $tokens[$token];
                }

                $token++;
            }

            // If there's nothing but whitespace on the next line, error.
            if (count($nextLine) == 0) {
                $phpcsFile->addError('There should be no whitespace immediately following a return statement.',
                    $stackPtr);
            }
        }

        if (isset($tokens[$stackPtr]['scope_closer']) === false) {
            return;
        }

        if (isset($tokens[$stackPtr]['parenthesis_opener']) === true) {
            $parenOpener = $tokens[$stackPtr]['parenthesis_opener'];
            $parenCloser = $tokens[$stackPtr]['parenthesis_closer'];
            if ($tokens[($parenOpener + 1)]['code'] === T_WHITESPACE) {
                $gap   = strlen($tokens[($parenOpener + 1)]['content']);
                $error = 'Expected 0 spaces after opening bracket; %s found';
                $data  = array($gap);
                $phpcsFile->addError($error, ($parenOpener + 1), 'SpacingAfterOpenBrace', $data);
            }

            if ($tokens[$parenOpener]['line'] === $tokens[$parenCloser]['line'] && $tokens[($parenCloser - 1)]['code'] === T_WHITESPACE
            ) {
                $gap   = strlen($tokens[($parenCloser - 1)]['content']);
                $error = 'Expected 0 spaces before closing bracket; %s found';
                $data  = array($gap);
                $phpcsFile->addError($error, ($parenCloser - 1), 'SpaceBeforeCloseBrace', $data);
            }
        }



        $scopeOpener = $tokens[$stackPtr]['scope_opener'];
        $scopeCloser = $tokens[$stackPtr]['scope_closer'];

        $firstContent = $phpcsFile->findNext(
            T_WHITESPACE,
            ($scopeOpener + 1),
            null,
            true
        );

        if ($tokens[$firstContent]['line'] !== ($tokens[$scopeOpener]['line'] + 1)) {
            $error = 'Blank line found at start of control structure';
            $phpcsFile->addError($error, $scopeOpener, 'SpacingBeforeOpen');
        }

        $lastContent = $phpcsFile->findPrevious(
            T_WHITESPACE,
            ($scopeCloser - 1),
            null,
            true
        );

        if ($tokens[$lastContent]['line'] !== ($tokens[$scopeCloser]['line'] - 1)) {
            $errorToken = $scopeCloser;
            for ($i = ($scopeCloser - 1); $i > $lastContent; $i--) {
                if ($tokens[$i]['line'] < $tokens[$scopeCloser]['line']) {
                    $errorToken = $i;
                    break;
                }
            }

            $error = 'Blank line found at end of control structure';
            $phpcsFile->addError($error, $errorToken, 'SpacingAfterClose');
        }

        $trailingContent = $phpcsFile->findNext(
            T_WHITESPACE,
            ($scopeCloser + 1),
            null,
            true
        );

        if ($tokens[$trailingContent]['code'] === T_ELSE) {
            if ($tokens[$stackPtr]['code'] === T_IF) {
                // IF with ELSE.
                return;
            }
        }

        if ($tokens[$trailingContent]['code'] === T_COMMENT) {
            if ($tokens[$trailingContent]['line'] === $tokens[$scopeCloser]['line']) {
                if (substr($tokens[$trailingContent]['content'], 0, 5) === '//end') {
                    // There is an end comment, so we have to get the next piece of content.
                    $trailingContent = $phpcsFile->findNext(
                        T_WHITESPACE,
                        ($trailingContent + 1),
                        null,
                        true
                    );
                }
            }
        }

        // If this token is closing a CASE or DEFAULT, we don't need the blank line after this control structure.
        if (isset($tokens[$trailingContent]['scope_condition']) === true) {
            $condition = $tokens[$trailingContent]['scope_condition'];
            if ($tokens[$condition]['code'] === T_CASE
                || $tokens[$condition]['code'] === T_DEFAULT
            ) {
                return;
            }
        }

        if ($tokens[$trailingContent]['code'] === T_CLOSE_TAG) {
            // At the end of the script or embedded code.
            return;
        }

        if ($tokens[$trailingContent]['code'] === T_CLOSE_CURLY_BRACKET) {
            // Another control structure's closing brace.
            if (isset($tokens[$trailingContent]['scope_condition']) === true) {
                $owner = $tokens[$trailingContent]['scope_condition'];
                if ($tokens[$owner]['code'] === T_FUNCTION) {
                    // The next content is the closing brace of a function
                    // so normal function rules apply and we can ignore it.
                    return;
                }
            }

            if ($tokens[$trailingContent]['line'] !== ($tokens[$scopeCloser]['line'] + 1)) {
                $error = 'Blank line found after control structure';
                $phpcsFile->addError($error, $scopeCloser, 'LineAfterClose');
            }
        }
    }
}
