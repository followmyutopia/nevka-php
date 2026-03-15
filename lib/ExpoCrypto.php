<?php
/**
 * Nevka Project NE5 PHP encoding/decoding library
 * 2026 Avicus Delacroix
 */

class ExpoCrypto {
    private $r_base;
    private $m_size;

    public function __construct() {
        $this->r_base = (0x00D000 + 0x07FF - 0x20 + 0x01);
        $this->m_size = $this->r_base + (0x10FFFF - 0xE000 + 0x01);
    }

    private function _m($n, $m) {
        return (($n % $m) + $m) % $m;
    }

    private function _chk($s) {
        $v = 0;
        $l = mb_strlen($s, 'UTF-8');
        for ($i = 0; $i < $l; $i++) {
            $c = mb_substr($s, $i, 1, 'UTF-8');
            $p = mb_ord($c, 'UTF-8');
            $v = ($v * 0x1F + $p) & 0xFFFFFFFF;
        }
        return $v;
    }

    private function _ptr($p) {
        if ($p >= 0x20 && $p <= 0xD7FF) {
            return $p - 0x20;
        }
        if ($p >= 0xE000 && $p <= 0x10FFFF) {
            return $this->r_base + ($p - 0xE000);
        }
        return $this->_ptr(0xFFFD);
    }

    private function _val($p) {
        if ($p < $this->r_base) {
            return 0x20 + $p;
        }
        return 0xE000 + ($p - $this->r_base);
    }

    public function encode($data, $token) {
        $s = $this->_chk($token);
        $res = "";
        $l = mb_strlen($data, 'UTF-8');
        
        $step = (0x24 + 0x01); 
        
        for ($i = 0; $i < $l; $i++) {
            $c = mb_substr($data, $i, 1, 'UTF-8');
            $p = mb_ord($c, 'UTF-8');
            $idx = $this->_ptr($p);
            
            $next = $this->_m($idx + $s + ($i * $step), $this->m_size);
            $res .= mb_chr($this->_val($next), 'UTF-8');
        }
        return $res;
    }

    public function decode($data, $token) {
        $s = $this->_chk($token);
        $res = "";
        $l = mb_strlen($data, 'UTF-8');
        $step = (0x24 + 0x01);

        for ($i = 0; $i < $l; $i++) {
            $c = mb_substr($data, $i, 1, 'UTF-8');
            $p = mb_ord($c, 'UTF-8');
            $idx = $this->_ptr($p);
            
            $next = $this->_m($idx - ($s + ($i * $step)), $this->m_size);
            $res .= mb_chr($this->_val($next), 'UTF-8');
        }
        return $res;
    }
}
