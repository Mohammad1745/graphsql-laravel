<?php

namespace Bitsmind\GraphSql;

trait Encryption
{
    /**
     * @var string
     */
    public string $refCharSet =',.-_:(){}[]0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * @param $str
     * @param $secret
     * @return string
     */
    public function encrypt($str, $secret): string
    {
        $shiftStrSet = explode('.', $secret);
        $encrypted = $this->generateCipher($str, $shiftStrSet[0]);
        if (count($shiftStrSet)>1){
            $encrypted = $this->scrambleString($encrypted, $shiftStrSet[1]);
        }
        return $encrypted;
    }

    /**
     * @param $encrypted
     * @param $secret
     * @return string
     */
    public function decrypt($encrypted, $secret): string
    {
        $shiftStrSet = explode('.', $secret);
        $decrypted = $encrypted;
        if (count($shiftStrSet)>1){
            $decrypted = $this->unscrambleString($decrypted, $shiftStrSet[1]);
        }
        return $this->decipher($decrypted, $shiftStrSet[0]);
    }

    /**
     * @param $str
     * @param $shiftStr
     * @return string
     */
    public function generateCipher($str, $shiftStr): string
    {
        $refCharArray = str_split($this->refCharSet);
        $cipherText = '';

        for ($i = 0; $i < strlen($str); $i++) {

            $shift = ord($shiftStr[$i % strlen($shiftStr)]);

            $shift = $shift >= 48 && $shift <= 57 ? $shift - 48 : $shift % count($refCharArray);

            $index = array_search($str[$i], $refCharArray);

            if ($index == 0 || $index) {
                $cipherText .= $refCharArray[($index + $shift) % count($refCharArray)];
            } else {
                $cipherText .= $str[$i];
            }
        }

        return $cipherText;
    }

    /**
     * @param $cipherText
     * @param $shiftStr
     * @return string
     */
    public function decipher($cipherText, $shiftStr): string
    {
        $refCharArray = str_split($this->refCharSet);
        $plainText = '';

        for ($i = 0; $i < strlen($cipherText); $i++) {

            $shift = ord($shiftStr[$i % strlen($shiftStr)]);

            $shift = $shift >= 48 && $shift <= 57 ? $shift - 48 : $shift % count($refCharArray);

            $index = array_search($cipherText[$i], $refCharArray);

            if ($index == 0 || $index) {
                $plainText .= $refCharArray[($index - $shift + count($refCharArray)) % count($refCharArray)];
            } else {
                $plainText .= $cipherText[$i];
            }
        }

        return $plainText;
    }

    /**
     * @param $str
     * @param $shiftStr
     * @return string
     */
    public function scrambleString($str, $shiftStr): string
    {
        $charArray = str_split($str);

        for ($i = 0; $i < strlen($str); $i++) {

            $shift = ord($shiftStr[$i % strlen($shiftStr)]);

            $newIndex = $shift >= 48 && $shift <= 57 ? $shift - 48 : $shift % strlen($str);

            $temp = $charArray[$i];
            $charArray[$i] = $charArray[$newIndex];
            $charArray[$newIndex] = $temp;
        }

        return implode('', $charArray);
    }

    /**
     * @param $str
     * @param $shiftStr
     * @return string
     */
    public function unscrambleString($str, $shiftStr): string
    {
        $charArray = str_split($str);

        for ($i = strlen($str) - 1; $i >= 0; $i--) {

            $shift = ord($shiftStr[$i % strlen($shiftStr)]);

            $originalIndex = $shift >= 48 && $shift <= 57 ? $shift - 48 : $shift % strlen($str);

            $temp = $charArray[$i];
            $charArray[$i] = $charArray[$originalIndex];
            $charArray[$originalIndex] = $temp;
        }

        return implode('', $charArray);
    }
}
