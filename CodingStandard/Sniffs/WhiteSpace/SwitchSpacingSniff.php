<?php

class CodingStandard_Sniffs_WhiteSpace_SwitchSpacingSniff extends Squiz_Sniffs_ControlStructures_ControlSignatureSniff
{
    protected function getPatterns()
    {
        return array(
            'switch (...) {EOL',
        );
    }
}